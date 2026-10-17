<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\LeaveBalance;
use App\Models\Ess\EssRequest;
use App\Models\Ess\EssAnnouncement;
use Carbon\Carbon;

class EssDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');

        // Today's attendance
        $todayRecord = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereDate('date', Carbon::today())
            ->first();

        // Leave balances
        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)->get();
        $totalLeaveCredits = $leaveBalances->sum('remaining_credits');

        // Recent requests
        $recentRequests = EssRequest::where('employee_id', $employee->employee_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $announcements = EssAnnouncement::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return view('ess.dashboard', compact(
            'employee',
            'todayRecord',
            'totalLeaveCredits',
            'recentRequests',
            'announcements'
        ));
    }
}
