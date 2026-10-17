<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\ShiftTemplate;
use App\Models\Hr\Holiday;
use App\Models\Ess\EssRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EssScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        // Selected month
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Fetch shift schedules
        $schedules = EmployeeSchedule::where(function ($q) use ($empIntId, $employee) {
                $q->where('employee_id', $empIntId)
                  ->orWhere('employee_id', $employee->employee_id);
            })
            ->whereBetween('schedule_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('shiftTemplate')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->schedule_date)->toDateString();
            });

        // Fetch official holidays in this month
        $holidays = Holiday::whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($h) {
                return Carbon::parse($h->date)->toDateString();
            });

        // Eligible peers in same branch for Shift Swap
        $peers = Employee::where('branch_id', $employee->branch_id)
            ->where('employee_id', '!=', $employee->employee_id)
            ->activeWorkforce()
            ->get();

        // Shift templates available
        $shiftTemplates = ShiftTemplate::all();

        return view('ess.schedule.index', compact(
            'employee',
            'month',
            'startOfMonth',
            'endOfMonth',
            'schedules',
            'holidays',
            'peers',
            'shiftTemplates'
        ));
    }

    public function submitSwap(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $validated = $request->validate([
            'swap_with_employee_id' => 'required|exists:hr_employees,employee_code',
            'my_shift_date' => 'required|date',
            'target_shift_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        $peer = Employee::where('employee_code', $validated['swap_with_employee_id'])
            ->where('branch_id', $employee->branch_id)
            ->firstOrFail();

        EssRequest::create([
            'request_number' => EssRequest::generateRequestNumber('SWAP'),
            'employee_id' => $employee->employee_id,
            'request_type' => 'SHIFT_SWAP',
            'details' => [
                'swap_with_employee_id' => $peer->employee_id,
                'swap_with_name' => $peer->first_name . ' ' . $peer->last_name,
                'my_shift_date' => $validated['my_shift_date'],
                'target_shift_date' => $validated['target_shift_date'],
            ],
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'approval_step' => 1,
        ]);

        return redirect()->back()->with('success', "Shift swap request with {$peer->first_name} {$peer->last_name} submitted successfully.");
    }

    public function submitAdjustment(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $validated = $request->validate([
            'effective_date' => 'required|date',
            'requested_shift_template_id' => 'required|exists:hr_shift_templates,id',
            'reason' => 'required|string|max:500',
        ]);

        $shift = ShiftTemplate::findOrFail($validated['requested_shift_template_id']);

        EssRequest::create([
            'request_number' => EssRequest::generateRequestNumber('SCHED'),
            'employee_id' => $employee->employee_id,
            'request_type' => 'SHIFT_SWAP',
            'details' => [
                'effective_date' => $validated['effective_date'],
                'requested_shift_template_id' => $shift->id,
                'requested_shift_name' => $shift->name,
                'adjustment_type' => 'Shift Template Change',
            ],
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'approval_step' => 1,
        ]);

        return redirect()->back()->with('success', 'Schedule adjustment request submitted for manager review.');
    }
}
