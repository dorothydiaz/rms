<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Ess\EssHelpdeskTicket;
use App\Models\Ess\EssHelpdeskMessage;

class EssHelpdeskController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $tickets = EssHelpdeskTicket::with(['messages.user'])
            ->where('employee_id', $empId)
            ->orderByDesc('id')
            ->paginate(15);

        return view('ess.helpdesk.index', compact('employee', 'tickets'));
    }

    public function storeTicket(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,URGENT',
        ]);

        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $ticket = EssHelpdeskTicket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'employee_id' => $empId,
            'category' => $request->category,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'OPEN',
        ]);

        EssHelpdeskMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->description,
        ]);

        return redirect()->route('ess.helpdesk')->with('success', 'Ticket #' . $ticket->ticket_number . ' submitted successfully. Our HR Operations team will review your inquiry.');
    }

    public function showTicket(Request $request, $id)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $ticket = EssHelpdeskTicket::with(['messages.user'])->findOrFail($id);

        if ((int)$ticket->employee_id !== (int)$empId) {
            abort(403, 'Unauthorized ticket access.');
        }

        return response()->json($ticket);
    }

    public function replyTicket(Request $request, $id)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $ticket = EssHelpdeskTicket::findOrFail($id);

        if ((int)$ticket->employee_id !== (int)$empId) {
            abort(403, 'Unauthorized ticket response.');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = 'tck_att_' . $ticket->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('uploads/ess_helpdesk', $fileName, 'public');
        }

        EssHelpdeskMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'attachment_url' => $attachmentPath,
        ]);

        $ticket->touch();

        return redirect()->route('ess.helpdesk')->with('success', 'Reply posted successfully to ticket #' . $ticket->ticket_number . '.');
    }
}
