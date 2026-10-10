@extends('layouts.app')

@section('title', 'Evaluation Periods - Performance Management')

@section('content')
<x-hr-tabs parent="performance-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addPeriodModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Create Period</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Review Cycle Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Reviews Completed</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $p)
                    <tr>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($p->start_date)->format('M d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }}</td>
                        <td><span class="hr-badge hr-badge-purple">{{ $p->evaluations_count }} reviews</span></td>
                        <td style="text-align: right;">
                            <span class="hr-badge {{ $p->status === 'Active' ? 'hr-badge-success' : 'hr-badge-neutral' }}">
                                {{ $p->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No performance periods created.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Performance Period -->
<div id="addPeriodModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Create Review Period</span>
            <button class="icon-btn" onclick="closeModal('addPeriodModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.performance.periods.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Period Title *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Q4 2026 Staff Appraisal">
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Date *</label>
                        <input type="date" name="start_date" class="hr-input" required value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Date *</label>
                        <input type="date" name="end_date" class="hr-input" required value="{{ date('Y-m-t') }}">
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Status *</label>
                    <select name="status" class="hr-select" required>
                        <option value="Active">Active</option>
                        <option value="Draft">Draft</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addPeriodModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Period</button>
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
