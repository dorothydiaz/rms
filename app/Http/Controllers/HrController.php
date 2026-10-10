<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HrController extends Controller
{
    public function dashboard(): View
    {
        return view('hr.dashboard');
    }

    public function employee(): View
    {
        return view('hr.employee');
    }

    public function usersAuth(): View
    {
        return view('hr.users-auth');
    }

    public function attendanceSchedule(): View
    {
        return view('hr.attendance-schedule');
    }

    public function attendanceCheckin(): View
    {
        return view('hr.attendance-checkin');
    }

    public function employeeLeave(): View
    {
        return view('hr.employee-leave');
    }
}
