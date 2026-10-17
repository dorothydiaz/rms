<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\EssRequest;
use App\Models\Hr\Holiday;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\PayrollRecord;
use App\Services\Hr\DoleComplianceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssDashboardController extends Controller
{
    public function __construct(
        protected DoleComplianceService $doleService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            // Graceful fallback if user has no linked employee profile yet
            return view('ess.no-employee-profile', compact('user'));
        }

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // 1. Today's attendance & punch status
        $todayRecord = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereDate('date', $today)
            ->first();

        // 2. Today's work schedule
        $todaySchedule = EmployeeSchedule::with('shiftTemplate')
            ->where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->employee_id)
                  ->orWhere('employee_id', $employee->id);
            })
            ->whereDate('schedule_date', $today)
            ->first();

        // 3. Month attendance KPIs
        $monthRecords = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $presentDays = $monthRecords->whereIn('status', ['Present', 'Late', 'Half Day'])->count();
        $totalLateMinutes = $monthRecords->sum('late_minutes');
        $totalUndertimeMinutes = $monthRecords->sum('undertime_minutes');
        $totalOtHours = $monthRecords->sum('overtime_hours');
        $totalAbsences = $monthRecords->where('status', 'Absent')->count();

        // 4. Leave balances
        $leaveBalances = LeaveBalance::where('employee_id', $employee->employee_id)
            ->with('leaveType')
            ->get();

        // 5. Latest finalized payslip
        $latestPayslip = PayrollRecord::where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->employee_id)
                  ->orWhere('employee_id', $employee->id);
            })
            ->with('payrollPeriod')
            ->latest('id')
            ->first();

        // 6. Recent requests
        $recentRequests = EssRequest::forEmployee($employee->employee_id)
            ->latest()
            ->take(5)
            ->get();

        // 7. Upcoming holidays
        $upcomingHolidays = Holiday::whereDate('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->take(3)
            ->get();

        return view('ess.dashboard', compact(
            'user',
            'employee',
            'todayRecord',
            'todaySchedule',
            'presentDays',
            'totalLateMinutes',
            'totalUndertimeMinutes',
            'totalOtHours',
            'totalAbsences',
            'leaveBalances',
            'latestPayslip',
            'recentRequests',
            'upcomingHolidays'
        ));
    }
}
