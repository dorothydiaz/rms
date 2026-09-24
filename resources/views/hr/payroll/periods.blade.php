@extends('layouts.app')

@section('title', 'Payroll Periods - Philippine Payroll Engine')

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addPeriodModal')">
            <i class="ph ph-plus-circle"></i>
            <span>New Payroll Period</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Period Name</th>
                    <th>Date Range (Cutoff)</th>
                    <th>Payout Date</th>
                    <th>Frequency</th>
                    <th>Processed Records</th>
                    <th>Status</th>
                    <th style="text-align: right;">Workflow Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $p)
                    <tr>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }}</strong>
                        </td>
                        <td>
                            <strong style="color: #059669;">{{ \Carbon\Carbon::parse($p->payout_date)->format('M d, Y') }}</strong>
                        </td>
                        <td>{{ $p->pay_frequency }}</td>
                        <td><span class="hr-badge hr-badge-purple">{{ $p->records_count }} employees</span></td>
                        <td>
                            @if($p->status === 'Finalized')
                                <span class="hr-badge hr-badge-success"><i class="ph ph-lock-key"></i> Finalized</span>
                            @elseif($p->status === 'Approved')
                                <span class="hr-badge hr-badge-info"><i class="ph ph-check"></i> Approved</span>
                            @elseif($p->status === 'Review')
                                <span class="hr-badge hr-badge-warning"><i class="ph ph-eye"></i> In Review</span>
                            @else
                                <span class="hr-badge hr-badge-neutral"><i class="ph ph-note"></i> Draft</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('hr.payroll.register', ['payroll_period_id' => $p->id]) }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                                    <i class="ph ph-list-numbers"></i> Register
                                </a>
                                @if($p->status !== 'Finalized')
                                    <a href="{{ route('hr.payroll.process', ['payroll_period_id' => $p->id]) }}" class="hr-btn hr-btn-primary hr-btn-sm">
                                        <i class="ph ph-calculator"></i> Process
                                    </a>
                                    <!-- Workflow Status dropdown -->
                                    <form method="POST" action="{{ route('hr.payroll.periods.status', $p->id) }}" style="display: inline;">
                                        @csrf
                                        <select name="status" class="hr-select" style="padding: 4px 8px; font-size: 11px;" onchange="this.form.submit()">
                                            <option value="">Move Status...</option>
                                            <option value="Draft" {{ $p->status === 'Draft' ? 'disabled' : '' }}>Draft</option>
                                            <option value="Review" {{ $p->status === 'Review' ? 'disabled' : '' }}>Review</option>
                                            <option value="Approved" {{ $p->status === 'Approved' ? 'disabled' : '' }}>Approve</option>
                                            <option value="Finalized">Finalize & Lock</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Permanently Locked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No payroll periods created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Payroll Period -->
<div id="addPeriodModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Create Payroll Period</span>
            <button class="icon-btn" onclick="closeModal('addPeriodModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.periods.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Period Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. September 2026 - 2nd Half">
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Date *</label>
                        <input type="date" name="start_date" class="hr-input" required value="{{ date('Y-m-16') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Date *</label>
                        <input type="date" name="end_date" class="hr-input" required value="{{ date('Y-m-t') }}">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Payout Date *</label>
                        <input type="date" name="payout_date" class="hr-input" required value="{{ date('Y-m-t') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency *</label>
                        <select name="pay_frequency" class="hr-select" required>
                            <option value="Semi-Monthly">Semi-Monthly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addPeriodModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Create Period</button>
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
