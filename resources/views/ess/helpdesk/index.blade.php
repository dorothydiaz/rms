@extends('layouts.ess')

@section('title', 'HR Helpdesk & Inquiries')

@section('content')
<div class="ess-helpdesk-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-chats-circle"></i>
                <span>HR Helpdesk & Inquiries</span>
            </h1>
            <p class="hr-page-subtitle">Submit direct HR inquiries, benefits questions, statutory concerns, and formal workplace communications.</p>
        </div>
        <div class="hr-page-actions">
            <button type="button" class="hr-btn hr-btn-primary" onclick="openNewTicketModal()">
                <i class="ph ph-chat-circle-dots"></i>
                <span>Open New Ticket</span>
            </button>
        </div>
    </div>

    <!-- Active Tickets Table -->
    <div class="ess-card">
        <div class="ess-card-accent-strip"></div>
        <div class="ess-card-header">
            <h2 class="ess-card-title">
                <div class="hr-bento-icon-box purple" style="width:30px;height:30px;font-size:16px;">
                    <i class="ph ph-ticket"></i>
                </div>
                <span>My Support & Grievance Tickets</span>
            </h2>
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
                        <td><strong style="font-family: monospace; color: #9333ea;">{{ $ticket->ticket_number }}</strong></td>
                        <td>
                            <span class="ess-badge ess-badge-neutral">{{ str_replace('_', ' ', $ticket->category) }}</span>
                        </td>
                        <td>
                            <strong style="color: #0f172a;">{{ $ticket->subject }}</strong>
                            <div style="font-size: 11.5px; color: #64748b; text-overflow: ellipsis; white-space: nowrap; max-width: 280px; overflow: hidden;">
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
                            <button type="button" class="hr-btn hr-btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="viewConversation({{ $ticket->id }}, '{{ $ticket->ticket_number }}', '{{ addslashes($ticket->subject) }}', '{{ $ticket->status }}')">
                                <i class="ph ph-chat-text"></i> Messages ({{ $ticket->messages->count() }})
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                            <i class="ph ph-chat-circle-dots" style="font-size: 40px; display: block; margin: 0 auto 10px; opacity: 0.5;"></i>
                            No support tickets filed yet. If you have questions regarding compensation, benefits, or workplace conditions, feel free to open a ticket.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div style="padding: 16px;">
            {{ $tickets->links() }}
        </div>
        @endif
    </div>
</div>

<!-- New Ticket Modal -->
<div id="newTicketModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 580px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-chat-circle-dots"></i> Submit HR Support Ticket</span>
            <button type="button" class="icon-btn" onclick="closeModal('newTicketModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <form action="{{ route('ess.helpdesk.ticket') }}" method="POST">
            @csrf
            <div class="hr-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Inquiry Category <span style="color:#dc2626;">*</span></label>
                        <select name="category" class="ess-form-control" style="width:100%;" required>
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
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Priority <span style="color:#dc2626;">*</span></label>
                        <select name="priority" class="ess-form-control" style="width:100%;" required>
                            <option value="LOW">Low (General Inquiry)</option>
                            <option value="MEDIUM" selected>Medium (Standard Response)</option>
                            <option value="HIGH">High (Urgent Resolution)</option>
                            <option value="URGENT">Urgent (Immediate Safety/Payroll Block)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Subject / Topic <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="subject" class="ess-form-control" style="width:100%;" placeholder="Brief summary of your question or concern" required>
                </div>

                <div style="margin-bottom: 8px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Detailed Description <span style="color:#dc2626;">*</span></label>
                    <textarea name="description" class="ess-form-control" style="width:100%;" rows="5" placeholder="Provide full details, relevant dates, or specific questions..." required></textarea>
                    <small style="color: #94a3b8; font-size: 11px; display: block; margin-top: 5px;">
                        All inquiries are treated with strict confidentiality under Stellar One HR Governance.
                    </small>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('newTicketModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Conversation / Thread Modal -->
<div id="conversationModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 680px;">
        <div class="hr-modal-header">
            <div>
                <span class="hr-modal-title" id="modalTicketTitle" style="display:flex;align-items:center;gap:8px;"><i class="ph ph-chat-teardrop-text"></i> Ticket Discussion</span>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;" id="modalTicketNumber"></div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('conversationModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <div class="hr-modal-body" style="padding: 20px; overflow-y: auto; flex: 1 1 auto;" id="conversationStream">
            <div style="text-align: center; color: #94a3b8; padding: 2rem;">Loading conversation...</div>
        </div>
        <form id="replyForm" method="POST" enctype="multipart/form-data" style="flex: 0 0 auto !important; height: auto !important; margin: 0;">
            @csrf
            <div class="hr-modal-footer" style="padding: 16px 24px; display: block; border-top: 1.5px solid #e2e8f0;">
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="message" class="ess-form-control" style="flex: 1;" placeholder="Write a reply or follow-up message..." required autocomplete="off">
                    <button type="submit" class="hr-btn hr-btn-primary" style="white-space:nowrap;">
                        <i class="ph ph-paper-plane-tilt"></i> Send
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openNewTicketModal() {
    openModal('newTicketModal');
}
function closeNewTicketModal() {
    closeModal('newTicketModal');
}
function closeConversationModal() {
    closeModal('conversationModal');
}

function viewConversation(ticketId, ticketNumber, subject, status) {
    document.getElementById('modalTicketTitle').innerHTML = '<i class="ph ph-chat-teardrop-text"></i> ' + subject;
    document.getElementById('modalTicketNumber').innerText = ticketNumber + ' (' + status + ')';
    document.getElementById('replyForm').action = '/ess/helpdesk/ticket/' + ticketId + '/reply';
    const stream = document.getElementById('conversationStream');
    stream.innerHTML = '<div style="text-align:center; padding: 2rem; color: #94a3b8;">Loading conversation...</div>';
    openModal('conversationModal');

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
                    <div style="margin-bottom: 12px; display: flex; flex-direction: column; align-items: ${isEmployee ? 'flex-end' : 'flex-start'};">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 3px;">
                            ${isEmployee ? 'You' : (msg.user?.name || 'HR Operations Team')} &bull; ${new Date(msg.created_at).toLocaleDateString()} ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </div>
                        <div style="background: ${isEmployee ? 'linear-gradient(135deg, #ec4899, #a855f7)' : '#ffffff'}; color: ${isEmployee ? 'white' : '#0f172a'}; border: ${isEmployee ? 'none' : '1px solid #e2e8f0'}; border-radius: 12px; padding: 10px 14px; max-width: 80%; font-size: 13px; line-height: 1.4; box-shadow: ${isEmployee ? '0 4px 12px rgba(168,85,247,0.2)' : '0 1px 3px rgba(0,0,0,0.03)'};">
                            ${msg.message}
                        </div>
                    </div>
                `;
            });
            stream.innerHTML = html;
            stream.scrollTop = stream.scrollHeight;
        });
}
</script>
@endsection
