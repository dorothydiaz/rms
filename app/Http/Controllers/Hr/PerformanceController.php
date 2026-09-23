<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\PerformanceCriterion;
use App\Models\Hr\PerformanceEvaluation;
use App\Models\Hr\PerformancePeriod;
use App\Models\Hr\PerformanceRating;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    // ==========================================
    // 1. EVALUATION PERIODS
    // ==========================================

    public function periodsIndex(): View
    {
        $periods = PerformancePeriod::withCount('evaluations')
            ->orderBy('start_date', 'desc')
            ->paginate(10);
        return view('hr.performance.periods', compact('periods'));
    }

    public function periodStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:Draft,Active,Closed',
        ]);

        $period = PerformancePeriod::create($validated);
        AuditLogger::log('Create', 'Performance', $period->id, "Created performance period '{$period->name}'");

        return redirect()->back()->with('success', "Performance period '{$period->name}' created.");
    }

    // ==========================================
    // 2. EVALUATION CRITERIA
    // ==========================================

    public function criteriaIndex(): View
    {
        $criteria = PerformanceCriterion::orderBy('id', 'asc')->get();
        return view('hr.performance.criteria', compact('criteria'));
    }

    public function criterionStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'weight_percentage' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $criterion = PerformanceCriterion::create($validated);
        AuditLogger::log('Create', 'Performance', $criterion->id, "Created performance criterion '{$criterion->name}'");

        return redirect()->back()->with('success', "Performance criterion '{$criterion->name}' created.");
    }

    // ==========================================
    // 3. EVALUATIONS
    // ==========================================

    public function evaluationsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = PerformanceEvaluation::with(['employee.branch', 'employee.position', 'period', 'evaluator']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('performance_period_id')) {
            $query->where('performance_period_id', $request->performance_period_id);
        }

        $evaluations = $query->orderBy('created_at', 'desc')->paginate(15);
        $periods = PerformancePeriod::all();
        $employees = Employee::where('employment_status', 'Active')->get();
        $criteria = PerformanceCriterion::where('is_active', true)->get();

        return view('hr.performance.evaluations', compact('evaluations', 'periods', 'employees', 'criteria'));
    }

    public function evaluationCreate(int $id): View
    {
        $evaluation = PerformanceEvaluation::with(['employee.department', 'employee.position', 'period', 'ratings.criterion'])->findOrFail($id);
        $criteria = PerformanceCriterion::where('is_active', true)->get();
        return view('hr.performance.evaluation-form', compact('evaluation', 'criteria'));
    }

    public function evaluationStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'performance_period_id' => 'required|exists:performance_periods,id',
            'employee_id' => 'required|exists:employees,id',
            'ratings' => 'required|array',
            'ratings.*' => 'required|integer|min:1|max:5',
            'manager_comments' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($validated, $user) {
            $ratings = $validated['ratings'];
            $averageScore = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0.00;

            $eval = PerformanceEvaluation::updateOrCreate(
                [
                    'performance_period_id' => $validated['performance_period_id'],
                    'employee_id' => $validated['employee_id'],
                ],
                [
                    'evaluator_id' => $user->id,
                    'overall_score' => $averageScore,
                    'status' => 'Submitted',
                    'manager_comments' => $validated['manager_comments'] ?? null,
                    'recommendation' => $validated['recommendation'] ?? null,
                ]
            );

            foreach ($ratings as $critId => $score) {
                PerformanceRating::updateOrCreate(
                    [
                        'evaluation_id' => $eval->id,
                        'criterion_id' => $critId,
                    ],
                    [
                        'rating' => $score,
                    ]
                );
            }

            AuditLogger::log('Create', 'Performance', $eval->id, "Submitted performance evaluation for employee #{$eval->employee_id} (Score: {$averageScore})");
        });

        return redirect()->route('hr.performance.evaluations')->with('success', 'Performance evaluation submitted successfully.');
    }

    // ==========================================
    // 4. PERFORMANCE REPORTS
    // ==========================================

    public function reportsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = PerformanceEvaluation::with(['employee.branch', 'employee.department', 'period', 'evaluator']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $evaluations = $query->orderBy('overall_score', 'desc')->get();
        return view('hr.performance.reports', compact('evaluations'));
    }
}
