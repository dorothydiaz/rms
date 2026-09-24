@extends('layouts.app')

@section('title', 'Evaluation Criteria - Performance Management')

@section('content')
<x-hr-tabs parent="performance-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addCritModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Criterion</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Criterion Name</th>
                    <th>Weight (%)</th>
                    <th>Description</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($criteria as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td><strong style="color: #9333ea;">{{ $c->weight_percentage }}%</strong></td>
                        <td>{{ $c->description ?? 'Standard performance indicator' }}</td>
                        <td style="text-align: right;">
                            <span class="hr-badge {{ $c->is_active ? 'hr-badge-success' : 'hr-badge-neutral' }}">
                                {{ $c->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No criteria defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Criterion -->
<div id="addCritModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Evaluation Criterion</span>
            <button class="icon-btn" onclick="closeModal('addCritModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.performance.criteria.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Criterion Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Table Turnover Rate">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Weight Percentage (%) *</label>
                    <input type="number" name="weight_percentage" class="hr-input" required value="10" min="1" max="100">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Description & Scoring Rubric</label>
                    <textarea name="description" class="hr-input" rows="3" placeholder="Key observable behaviors..."></textarea>
                </div>
                <div class="hr-form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" id="critActive" value="1" checked>
                    <label for="critActive" style="font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;">Active Criterion</label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addCritModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Criterion</button>
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
