@extends('layouts.app')

@section('title', 'Leave Types - Leave & Absence Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-list-bullets"></i>
            Configurable Leave Types
        </h1>
        <p class="hr-page-subtitle">Configure statutory Philippine leave types (VL, SL, Emergency, SIL, Maternity, Paternity, Solo Parent, Bereavement)</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addLeaveTypeModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Leave Type</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Leave Type Name</th>
                    <th>Compensation</th>
                    <th>Default Credits</th>
                    <th>Cumulative (Carried Over)</th>
                    <th>Description</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $type->code }}</strong></td>
                        <td><strong>{{ $type->name }}</strong></td>
                        <td>
                            @if($type->is_paid)
                                <span class="hr-badge hr-badge-success">Paid Leave</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">Unpaid</span>
                            @endif
                        </td>
                        <td><strong>{{ number_format($type->default_credits, 1) }} days/year</strong></td>
                        <td>{{ $type->is_cumulative ? 'Yes' : 'No' }}</td>
                        <td>{{ $type->description ?? 'No description' }}</td>
                        <td style="text-align: right;">
                            <span class="hr-badge hr-badge-success">Active</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No leave types configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Leave Type -->
<div id="addLeaveTypeModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Configurable Leave Type</span>
            <button class="icon-btn" onclick="closeModal('addLeaveTypeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.leave.types.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Leave Type Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Birthday Leave">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Leave Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. BDAY">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Default Annual Credits (Days) *</label>
                    <input type="number" step="0.5" name="default_credits" class="hr-input" required value="1" min="0">
                </div>
                <div class="hr-form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_paid" id="isPaid" value="1" checked>
                    <label for="isPaid" style="font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;">Paid Leave Benefit</label>
                </div>
                <div class="hr-form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_cumulative" id="isCumulative" value="1">
                    <label for="isCumulative" style="font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;">Cumulative (Unused credits roll over to next year)</label>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Description / Policy Guidelines</label>
                    <textarea name="description" class="hr-input" rows="2" placeholder="Rules regarding eligibility..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addLeaveTypeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Leave Type</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
@endpush
@endsection
