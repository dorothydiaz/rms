@extends('layouts.ess')

@section('title', 'HR Helpdesk & Inquiries')

@section('content')
<div class="ess-helpdesk-container">
    <div class="ess-page-header">
        <div>
            <h1 class="ess-page-title">HR Helpdesk & Inquiries</h1>
            <p class="ess-page-subtitle">Submit direct HR inquiries, benefits questions, statutory concerns, and formal workplace communications.</p>
        </div>
        <div class="ess-page-actions">
            <button type="button" class="ess-btn ess-btn-primary" onclick="openNewTicketModal()">
                <i class="bi bi-chat-left-dots"></i> Open New Ticket
            </button>
        </div>
    </div>

    <!-- Active Tickets Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h2 class="ess-card-title"><i class="bi bi-ticket-perforated"></i> My Support & Grievance Tickets</h2>
        </div>

        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Ticket No.</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td><strong style="font-family: monospace; color: #1e293b;">{{ $ticket->ticket_number }}</strong></td>
                        <td>
                            <span class="ess-badge ess-badge-neutral">{{ str_replace('_', ' ', $ticket->category) }}</span>
                        </td>
                        <td>
                            <strong style="color: #1e293b;">{{ $ticket->subject }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b; text-overflow: ellipsis; white-space: nowrap; max-width: 280px; overflow: hidden;">
                                {{ $ticket->description }}
                            </div>
                        </td>
                        <td>
                            @php
                                $priColors = [
                                    'LOW' => 'ess-badge-neutral',
                                    'MEDIUM' => 'ess-badge-primary',
                                    'HIGH' => 'ess-badge-warning',
                                    'URGENT' => 'ess-badge-danger',
                                ];
                            @endphp
                            <span class="ess-badge {{ $priColors[$ticket->priority] ?? 'ess-badge-neutral' }}">
                                {{ $ticket->priority }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'OPEN' => 'ess-badge-primary',
                                    'IN_PROGRESS' => 'ess-badge-warning',
                                    'RESOLVED' => 'ess-badge-success',
                                    'CLOSED' => 'ess-badge-neutral',
                                ];
                            @endphp
                            <span class="ess-badge {{ $statusColors[$ticket->status] ?? 'ess-badge-neutral' }}">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>{{ $ticket->created_at?->format('M d, Y h:i A') }}</td>
                        <td style="text-align: right;">
                            <button type="button" class="ess-btn ess-btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;" onclick="viewConversation({{ $ticket->id }}, '{{ $ticket->ticket_number }}', '{{ addslashes($ticket->subject) }}', '{{ $ticket->status }}')">
                                <i class="bi bi-chat-square-text"></i> Messages ({{ $ticket->messages->count() }})
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: #94a3b8;">
                            <i class="bi bi-chat-square-heart" style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            No support tickets filed yet. If you have questions regarding compensation, benefits, or workplace conditions, feel free to open a ticket.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div style="padding: 1rem;">
            {{ $tickets->links() }}
        </div>
        @endif
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1" style="display: none; background: rgba(15,23,42,0.6); position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px; width: 100%; margin: 1.5rem auto;">
        <div class="modal-content" style="background: white; border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <form action="{{ route('ess.helpdesk.ticket') }}" method="POST">
                @csrf
                <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                        <i class="bi bi-chat-left-dots text-primary me-1"></i> Submit HR Support Ticket
                    </h5>
                    <button type="button" class="btn-close" onclick="closeNewTicketModal()"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="ess-form-group">
                            <label class="ess-form-label">Inquiry Category <span class="text-danger">*</span></label>
                            <select name="category" class="ess-form-control" required>
                                <option value="">Select category...</option>
                                <option value="PAYROLL_COMPENSATION">Payroll & Compensation</option>
                                <option value="ATTENDANCE_TIMEKEEPING">Attendance & Biometrics</option>
                                <option value="BENEFITS_STATUTORY">Benefits & Statutory (SSS/PhilHealth/Pag-IBIG)</option>
                                <option value="LEAVE_CREDITS">Leave Administration</option>
                                <option value="WORKPLACE_SAFETY">Workplace Safety & Health (OSH)</option>
                                <option value="GRIEVANCE_DISCIPLINE">Formal Grievance / Workplace Ethics</option>
                                <option value="GENERAL_HR">General HR Inquiry</option>
                            </select>
                        </div>
                        <div class="ess-form-group">
                            <label class="ess-form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="ess-form-control" required>
                                <option value="LOW">Low (General Inquiry)</option>
                                <option value="MEDIUM" selected>Medium (Standard Response)</option>
                                <option value="HIGH">High (Urgent Resolution)</option>
                                <option value="URGENT">Urgent (Immediate Safety/Payroll Block)</option>
                            </select>
                        </div>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Subject / Topic <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="ess-form-control" placeholder="Brief summary of your question or concern" required>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 0.5rem;">
                        <label class="ess-form-label">Detailed Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="ess-form-control" rows="5" placeholder="Provide full details, relevant dates, or specific questions..." required></textarea>
                        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 0.35rem;">
                            All inquiries are treated with strict confidentiality under Stellar One HR Governance.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="ess-btn ess-btn-secondary" onclick="closeNewTicketModal()">Cancel</button>
                    <button type="submit" class="ess-btn ess-btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Conversation / Thread Modal -->
<div class="modal fade" id="conversationModal" tabindex="-1" style="display: none; background: rgba(15,23,42,0.6); position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 650px; width: 100%; margin: 1.5rem auto;">
        <div class="modal-content" style="background: white; border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between;">
                <div>
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b; font-size: 1.05rem;" id="modalTicketTitle"></h5>
                    <div style="font-size: 0.8rem; color: #64748b;" id="modalTicketNumber"></div>
                </div>
                <button type="button" class="btn-close" onclick="closeConversationModal()"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; max-height: 400px; overflow-y: auto;" id="conversationStream">
                <div style="text-align: center; color: #94a3b8; padding: 2rem;">Loading conversation...</div>
            </div>
            <form id="replyForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: block;">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="message" class="ess-form-control" placeholder="Write a reply or follow-up message..." required autocomplete="off">
                        <button type="submit" class="ess-btn ess-btn-primary">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewTicketModal() {
    document.getElementById('newTicketModal').style.display = 'flex';
}
function closeNewTicketModal() {
    document.getElementById('newTicketModal').style.display = 'none';
}
function closeConversationModal() {
    document.getElementById('conversationModal').style.display = 'none';
}

function viewConversation(ticketId, ticketNumber, subject, status) {
    document.getElementById('modalTicketTitle').innerText = subject;
    document.getElementById('modalTicketNumber').innerText = ticketNumber + ' (' + status + ')';
    document.getElementById('replyForm').action = '/ess/helpdesk/ticket/' + ticketId + '/reply';
    const stream = document.getElementById('conversationStream');
    stream.innerHTML = '<div style="text-align:center; padding: 2rem; color: #94a3b8;">Loading conversation...</div>';
    document.getElementById('conversationModal').style.display = 'flex';

    fetch('/ess/helpdesk/ticket/' + ticketId)
        .then(res => res.json())
        .then(ticket => {
            if (!ticket.messages || ticket.messages.length === 0) {
                stream.innerHTML = '<div style="text-align:center; padding: 2rem; color: #94a3b8;">No messages yet.</div>';
                return;
            }
            let html = '';
            ticket.messages.forEach(msg => {
                const isEmployee = msg.user_id === {{ auth()->id() ?? 0 }};
                html += `
                    <div style="margin-bottom: 1rem; display: flex; flex-direction: column; align-items: ${isEmployee ? 'flex-end' : 'flex-start'};">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.2rem;">
                            ${isEmployee ? 'You' : (msg.user?.name || 'HR Operations Team')} &bull; ${new Date(msg.created_at).toLocaleDateString()} ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </div>
                        <div style="background: ${isEmployee ? '#2563eb' : '#f1f5f9'}; color: ${isEmployee ? 'white' : '#1e293b'}; border-radius: 10px; padding: 0.75rem 1rem; max-width: 80%; font-size: 0.875rem; line-height: 1.4;">
                            ${msg.message}
                        </div>
                    </div>
                `;
            });
            stream.innerHTML = html;
            stream.scrollTop = stream.scrollHeight;
        });
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>
@endsection
