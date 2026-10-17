<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\PayrollRecord;
use App\Models\Ess\EssRequest;
use App\Models\Ess\EssAnnouncement;
use Carbon\Carbon;

class EssDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $employee->loadMissing(['position', 'department', 'branch', 'company', 'supervisor']);
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        // 1. Today's attendance punch record
        $todayRecord = AttendanceRecord::where('employee_id', $empIntId)
            ->whereDate('date', Carbon::today())
            ->first();

        // 2. Today's scheduled shift
        $todaySchedule = EmployeeSchedule::with('shiftTemplate')
            ->where('employee_id', $empIntId)
            ->whereDate('schedule_date', Carbon::today())
            ->first();

        // 3. Leave balances summary
        $leaveBalances = LeaveBalance::where('employee_id', $empIntId)->get();
        $totalLeaveCredits = (float) $leaveBalances->sum(function ($b) {
            return (float) ($b->remaining ?? ($b->leave_balance_remaining ?? 0));
        });

        // 4. Latest payslip for net pay metric
        $latestPayslip = PayrollRecord::with('payrollPeriod')
            ->where('employee_id', $empIntId)
            ->orderByDesc('payroll_record_id')
            ->first();

        // 5. Recent requests submitted by employee
        $recentRequests = EssRequest::where('employee_id', $empIntId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 6. Active company announcements & DOLE notices
        $announcements = EssAnnouncement::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return view('ess.dashboard', compact(
            'employee',
            'todayRecord',
            'todaySchedule',
            'totalLeaveCredits',
            'latestPayslip',
            'recentRequests',
            'announcements'
        ));
    }
}
