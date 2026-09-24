<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Branch;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollAdjustment;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\StatutoryContributionRule;
use App\Services\AuditLogger;
use App\Services\PayrollCalculationService;
use App\Services\StatutoryContributionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    protected PayrollCalculationService $payrollService;
    protected StatutoryContributionService $statutoryService;

    public function __construct(
        PayrollCalculationService $payrollService,
        StatutoryContributionService $statutoryService
    ) {
        $this->payrollService = $payrollService;
        $this->statutoryService = $statutoryService;
    }

    // ==========================================
    // 1. PAYROLL PERIODS
    // ==========================================

    public function periodsIndex(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);
        $periods = PayrollPeriod::with(['processor', 'approver'])
            ->withCount('records')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage)->withQueryString();

        return view('hr.payroll.periods', compact('periods'));
    }

    public function periodStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payout_date' => 'required|date',
            'pay_frequency' => 'required|in:Semi-Monthly,Monthly,Weekly',
        ]);

        $period = PayrollPeriod::create(array_merge($validated, [
            'status' => 'Draft',
            'processed_by' => Auth::id(),
        ]));

        AuditLogger::log('Create', 'Payroll', $period->id, "Created payroll period '{$period->name}'");

        return redirect()->back()->with('success', "Payroll period '{$period->name}' created.");
    }

    public function periodStatusUpdate(Request $request, int $id): RedirectResponse
    {
        $period = PayrollPeriod::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:Draft,Review,Approved,Finalized',
        ]);

        $newStatus = $validated['status'];
        $user = Auth::user();

        if ($period->status === 'Finalized') {
            return redirect()->back()->with('error', "Payroll period '{$period->name}' is finalized and permanently locked.");
        }

        if ($newStatus === 'Approved') {
            $period->approved_by = $user->id;
        } elseif ($newStatus === 'Finalized') {
            $period->finalized_at = now();
            // Lock all child records
            PayrollRecord::where('payroll_period_id', $period->id)->update(['status' => 'Finalized']);
        }

        $period->status = $newStatus;
        $period->save();

        AuditLogger::log(
            $newStatus === 'Finalized' ? 'Finalize' : 'Approve',
            'Payroll',
            $period->id,
            "Updated payroll period '{$period->name}' status to {$newStatus}"
        );

        return redirect()->back()->with('success', "Payroll period status updated to {$newStatus}.");
    }

    // ==========================================
    // 2. PROCESS PAYROLL
    // ==========================================

    public function processIndex(Request $request): View
    {
        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();
        $selectedPeriodId = $request->get('payroll_period_id', $periods->first()?->id);
        $period = $selectedPeriodId ? PayrollPeriod::with('records.employee')->find($selectedPeriodId) : null;
        $branches = Branch::where('is_active', true)->get();

        return view('hr.payroll.process', compact('periods', 'period', 'branches'));
    }

    public function processRun(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $period = PayrollPeriod::findOrFail($validated['payroll_period_id']);

        if ($period->status === 'Finalized') {
            return redirect()->back()->with('error', "Cannot recalculate finalized payroll period. Finalized records are permanently locked.");
        }

        $branchId = !empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        try {
            $processedCount = $this->payrollService->calculatePeriod($period, $branchId);
            $period->status = 'Review';
            $period->processed_by = Auth::id();
            $period->save();

            return redirect()->route('hr.payroll.register', ['payroll_period_id' => $period->id])
                ->with('success', "Payroll calculation completed for {$processedCount} employees.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Payroll calculation failed: " . $e->getMessage());
        }
    }

    // ==========================================
    // 3. PAYROLL REGISTER
    // ==========================================

    public function registerIndex(Request $request): View
    {
        $user = Auth::user();
        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();
        $selectedPeriodId = $request->get('payroll_period_id', $periods->first()?->id);

        $query = PayrollRecord::with(['employee.branch', 'employee.department', 'adjustments', 'payrollPeriod']);
        if ($selectedPeriodId) {
            $query->where('payroll_period_id', $selectedPeriodId);
        }

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        $perPage = (int) $request->get('per_page', 10);
        $records = $query->paginate($perPage)->withQueryString();
        $currentPeriod = $selectedPeriodId ? PayrollPeriod::find($selectedPeriodId) : null;
        $branches = Branch::where('is_active', true)->get();

        // Summary Totals
        $totals = [
            'gross' => (clone $query)->sum('gross_pay'),
            'basic' => (clone $query)->sum('basic_pay'),
            'ot' => (clone $query)->sum('overtime_pay'),
            'nd' => (clone $query)->sum('night_diff_pay'),
            'sss' => (clone $query)->sum('sss_employee'),
            'philhealth' => (clone $query)->sum('philhealth_employee'),
            'pagibig' => (clone $query)->sum('pagibig_employee'),
            'tax' => (clone $query)->sum('withholding_tax'),
            'deductions' => (clone $query)->sum('total_deductions'),
            'net' => (clone $query)->sum('net_pay'),
        ];

        return view('hr.payroll.register', compact('records', 'periods', 'currentPeriod', 'branches', 'totals'));
    }

    // ==========================================
    // 4. PAYSLIPS
    // ==========================================

    public function payslipsIndex(Request $request): View
    {
        $user = Auth::user();
        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();
        $selectedPeriodId = $request->get('payroll_period_id', $periods->first()?->id);

        $query = PayrollRecord::with(['employee.branch', 'payrollPeriod']);
        if ($selectedPeriodId) {
            $query->where('payroll_period_id', $selectedPeriodId);
        }

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->whereHas('employee', function ($q) use ($term) {
                $q->where('employee_id', 'like', $term)
                  ->orWhere('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term);
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $payslips = $query->paginate($perPage)->withQueryString();
        $currentPeriod = $selectedPeriodId ? PayrollPeriod::find($selectedPeriodId) : null;

        return view('hr.payroll.payslips', compact('payslips', 'periods', 'currentPeriod'));
    }

    public function payslipShow(int $id): View
    {
        $record = PayrollRecord::with([
            'employee.branch.company',
            'employee.department',
            'employee.position',
            'payrollPeriod',
            'adjustments'
        ])->findOrFail($id);

        $user = Auth::user();
        if ($record->employee && !$user->canAccessBranch($record->employee->branch_id)) {
            abort(403, 'Unauthorized access to payslip in another branch.');
        }

        return view('hr.payroll.payslip-detail', compact('record'));
    }

    // ==========================================
    // 5. PAYROLL ADJUSTMENTS (Audited corrections)
    // ==========================================

    public function adjustmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payroll_record_id' => 'required|exists:payroll_records,id',
            'adjustment_type' => 'required|in:Earning,Deduction',
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string',
        ]);

        $record = PayrollRecord::with('payrollPeriod')->findOrFail($validated['payroll_record_id']);

        if ($record->payrollPeriod->status === 'Finalized') {
            return redirect()->back()->with('error', 'Cannot add adjustment: Payroll period is finalized and permanently locked.');
        }

        DB::transaction(function () use ($record, $validated) {
            $adjustment = PayrollAdjustment::create([
                'payroll_record_id' => $record->id,
                'adjustment_type' => $validated['adjustment_type'],
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'remarks' => $validated['remarks'] ?? null,
                'approved_by' => Auth::id(),
            ]);

            // Adjust Net Pay
            if ($adjustment->adjustment_type === 'Earning') {
                $record->other_earnings += $adjustment->amount;
                $record->gross_pay += $adjustment->amount;
                $record->net_pay += $adjustment->amount;
            } else {
                $record->other_deductions += $adjustment->amount;
                $record->total_deductions += $adjustment->amount;
                $record->net_pay = max(0, $record->net_pay - $adjustment->amount);
            }
            $record->save();

            AuditLogger::log(
                'Adjust',
                'Payroll',
                $adjustment->id,
                "Applied {$adjustment->adjustment_type} adjustment of PHP " . number_format($adjustment->amount, 2) . " to payroll record #{$record->id} ({$adjustment->name})"
            );
        });

        return redirect()->back()->with('success', 'Payroll adjustment applied successfully.');
    }

    // ==========================================
    // 6. STATUTORY CONTRIBUTION RULES
    // ==========================================

    public function statutoryRulesIndex(): View
    {
        $rules = StatutoryContributionRule::orderBy('effective_date', 'desc')->get();
        return view('hr.payroll.statutory-rules', compact('rules'));
    }

    public function statutoryRuleUpdate(Request $request, int $id): RedirectResponse
    {
        $rule = StatutoryContributionRule::findOrFail($id);
        $validated = $request->validate([
            'rule_name' => 'required|string|max:100',
            'rate' => 'nullable|numeric|min:0|max:1',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'employee_share' => 'nullable|numeric|min:0|max:1',
            'employer_share' => 'nullable|numeric|min:0|max:1',
            'fixed_amount' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $rule->update($validated);

        AuditLogger::log('Update', 'Payroll', $rule->id, "Updated statutory rule '{$rule->rule_name}'");

        return redirect()->back()->with('success', "Statutory contribution rule '{$rule->rule_name}' updated.");
    }
}
