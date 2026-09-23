<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HrDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = $user->isSuperAdmin() || $user->isHrAdmin()
            ? ($request->filled('branch_id') ? (int) $request->branch_id : null)
            : $user->branch_id;

        $today = Carbon::today()->toDateString();

        // Base employee query with branch scoping
        $empQuery = Employee::query();
        if ($branchId) {
            $empQuery->where('branch_id', $branchId);
        }

        $totalEmployees = (clone $empQuery)->count();
        $activeEmployees = (clone $empQuery)->where('employment_status', 'Active')->count();
        $probationaryEmployees = (clone $empQuery)->where('employment_status', 'Probationary')->count();
        $onLeaveEmployees = (clone $empQuery)->where('employment_status', 'On Leave')->count();

        // Today's attendance
        $todayAttQuery = AttendanceRecord::where('date', $today);
        if ($branchId) {
            $todayAttQuery->where('branch_id', $branchId);
        }
        $todayPresent = (clone $todayAttQuery)->whereIn('status', ['Present', 'Late'])->count();
        $todayLate = (clone $todayAttQuery)->where('status', 'Late')->count();
        $todayAbsent = (clone $todayAttQuery)->where('status', 'Absent')->count();

        // Pending Leave & Overtime
        $pendingLeaveQuery = LeaveRequest::where('status', 'Pending');
        if ($branchId) {
            $pendingLeaveQuery->whereHas('employee', fn($q) => $q->where('branch_id', $branchId));
        }
        $pendingLeaveRequests = $pendingLeaveQuery->count();

        $pendingOtQuery = AttendanceRecord::where('overtime_hours', '>', 0)
            ->whereDate('date', '>=', Carbon::now()->subDays(7)->toDateString());
        if ($branchId) {
            $pendingOtQuery->where('branch_id', $branchId);
        }
        $pendingOtRequests = $pendingOtQuery->count();

        // Upcoming Birthdays (next 30 days)
        $upcomingBirthdays = (clone $empQuery)
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(function ($emp) {
                if (!$emp->date_of_birth) return false;
                $dob = Carbon::parse($emp->date_of_birth);
                $thisYearDob = $dob->copy()->year(Carbon::now()->year);
                if ($thisYearDob->isPast() && !$thisYearDob->isToday()) {
                    $thisYearDob->addYear();
                }
                return $thisYearDob->diffInDays(Carbon::now()) <= 30;
            })
            ->take(5);

        // Upcoming Regularization Dates (within next 45 days)
        $upcomingRegularizations = (clone $empQuery)
            ->where('employment_status', 'Probationary')
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', $today)
            ->whereDate('contract_end_date', '<=', Carbon::now()->addDays(45)->toDateString())
            ->orderBy('contract_end_date', 'asc')
            ->take(5)
            ->get();

        // Upcoming Contract Expirations (contractual / casual within 30 days)
        $upcomingExpirations = (clone $empQuery)
            ->whereIn('employment_type', ['Contractual', 'Casual', 'Part-time'])
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', $today)
            ->whereDate('contract_end_date', '<=', Carbon::now()->addDays(30)->toDateString())
            ->orderBy('contract_end_date', 'asc')
            ->take(5)
            ->get();

        // Current Payroll Status
        $currentPayroll = PayrollPeriod::latest('end_date')->first();

        // Chart Data: Employees by Department
        $departments = Department::withCount(['employees' => function ($q) use ($branchId) {
            if ($branchId) $q->where('branch_id', $branchId);
        }])->get();

        $deptLabels = $departments->pluck('name')->toArray();
        $deptCounts = $departments->pluck('employees_count')->toArray();

        // Chart Data: Employment Status
        $statusCounts = [
            'Active' => (clone $empQuery)->where('employment_status', 'Active')->count(),
            'Probationary' => (clone $empQuery)->where('employment_status', 'Probationary')->count(),
            'On Leave' => (clone $empQuery)->where('employment_status', 'On Leave')->count(),
            'Resigned/Terminated' => (clone $empQuery)->whereIn('employment_status', ['Resigned', 'Terminated'])->count(),
        ];

        // Chart Data: Attendance Summary (past 7 days)
        $past7Days = [];
        $attSummaryPresent = [];
        $attSummaryLate = [];
        $attSummaryAbsent = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i)->toDateString();
            $label = Carbon::parse($d)->format('M d');
            $past7Days[] = $label;

            $dayQ = AttendanceRecord::where('date', $d);
            if ($branchId) $dayQ->where('branch_id', $branchId);

            $attSummaryPresent[] = (clone $dayQ)->whereIn('status', ['Present', 'Late'])->count();
            $attSummaryLate[] = (clone $dayQ)->where('status', 'Late')->count();
            $attSummaryAbsent[] = (clone $dayQ)->where('status', 'Absent')->count();
        }

        // Chart Data: Payroll Summary
        $payrollSummary = null;
        if ($currentPayroll) {
            $payRecQuery = PayrollRecord::where('payroll_period_id', $currentPayroll->id);
            if ($branchId) {
                $payRecQuery->whereHas('employee', fn($q) => $q->where('branch_id', $branchId));
            }
            $payrollSummary = [
                'period_name' => $currentPayroll->name,
                'status' => $currentPayroll->status,
                'total_gross' => (clone $payRecQuery)->sum('gross_pay'),
                'total_net' => (clone $payRecQuery)->sum('net_pay'),
                'total_deductions' => (clone $payRecQuery)->sum('total_deductions'),
                'total_ot' => (clone $payRecQuery)->sum('overtime_pay'),
                'total_nd' => (clone $payRecQuery)->sum('night_diff_pay'),
            ];
        }

        $allBranches = Branch::where('is_active', true)->get();

        return view('hr.dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'probationaryEmployees',
            'onLeaveEmployees',
            'todayPresent',
            'todayLate',
            'todayAbsent',
            'pendingLeaveRequests',
            'pendingOtRequests',
            'upcomingBirthdays',
            'upcomingRegularizations',
            'upcomingExpirations',
            'currentPayroll',
            'deptLabels',
            'deptCounts',
            'statusCounts',
            'past7Days',
            'attSummaryPresent',
            'attSummaryLate',
            'attSummaryAbsent',
            'payrollSummary',
            'allBranches',
            'branchId'
        ));
    }
}
