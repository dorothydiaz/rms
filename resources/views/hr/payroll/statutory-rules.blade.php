@extends('layouts.app')

@section('title', 'Philippine Statutory Rules - Payroll')

@section('content')
<x-hr-tabs parent="payroll" />

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Agency / Rule</th>
                    <th>Rate / Fixed</th>
                    <th>Salary Min Floor</th>
                    <th>Salary Max Ceiling</th>
                    <th>Employee Share</th>
                    <th>Employer Share</th>
                    <th>Effective Date</th>
                    <th>Active</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>
                            <strong style="color: #9333ea;">{{ $rule->rule_name }}</strong><br>
                            <span class="hr-badge hr-badge-neutral">{{ $rule->rule_type }}</span>
                        </td>
                        <td>
                            @if($rule->fixed_amount > 0)
                                ₱{{ number_format($rule->fixed_amount, 2) }} fixed
                            @elseif($rule->rate > 0)
                                {{ number_format($rule->rate * 100, 2) }}%
                            @else
                                <span style="color: #94a3b8;">Graduated Brackets</span>
                            @endif
                        </td>
                        <td>₱{{ number_format($rule->min_salary, 2) }}</td>
                        <td>₱{{ number_format($rule->max_salary, 2) }}</td>
                        <td>
                            @if($rule->employee_share > 0)
                                {{ number_format($rule->employee_share * 100, 2) }}%
                            @elseif($rule->fixed_amount > 0)
                                ₱{{ number_format($rule->fixed_amount, 2) }}
                            @else
                                Bracket
                            @endif
                        </td>
                        <td>
                            @if($rule->employer_share > 0)
                                {{ number_format($rule->employer_share * 100, 2) }}%
                            @elseif($rule->fixed_amount > 0)
                                ₱{{ number_format($rule->fixed_amount, 2) }}
                            @else
                                Bracket
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($rule->effective_date)->format('M d, Y') }}</td>
                        <td>
                            <span class="hr-badge {{ $rule->is_active ? 'hr-badge-success' : 'hr-badge-neutral' }}">
                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openRuleModal({{ $rule->id }}, '{{ addslashes($rule->rule_name) }}', '{{ $rule->rate }}', '{{ $rule->min_salary }}', '{{ $rule->max_salary }}', '{{ $rule->employee_share }}', '{{ $rule->employer_share }}', '{{ $rule->fixed_amount }}', '{{ $rule->effective_date }}', {{ $rule->is_active ? 'true' : 'false' }})">
                                <i class="ph ph-pencil"></i> Configure
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">No statutory rules configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Configure Rule -->
<div id="ruleModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-sliders"></i> Configure Statutory Rule</span>
            <button class="icon-btn" onclick="closeModal('ruleModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="ruleForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Rule Name *</label>
                    <input type="text" name="rule_name" id="ruleName" class="hr-input" required>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Rate (e.g. 0.0500 for 5%)</label>
                        <input type="number" step="0.0001" name="rate" id="ruleRate" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Fixed Amount (PHP)</label>
                        <input type="number" step="0.01" name="fixed_amount" id="ruleFixed" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Min Salary Floor (PHP)</label>
                        <input type="number" step="0.01" name="min_salary" id="ruleMin" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Max Salary Ceiling (PHP)</label>
                        <input type="number" step="0.01" name="max_salary" id="ruleMax" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employee Share Ratio</label>
                        <input type="number" step="0.0001" name="employee_share" id="ruleEe" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employer Share Ratio</label>
                        <input type="number" step="0.0001" name="employer_share" id="ruleEr" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Effective Date *</label>
                        <input type="date" name="effective_date" id="ruleEff" class="hr-input" required>
                    </div>
                </div>
                <div class="hr-form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" id="ruleActive" value="1">
                    <label for="ruleActive" style="font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;">Active in Payroll Calculations</label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('ruleModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Statutory Rule</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openRuleModal(id, name, rate, min, max, ee, er, fixed, eff, active) {
    document.getElementById('ruleForm').action = '/hr/payroll/statutory-rules/' + id;
    document.getElementById('ruleName').value = name;
    document.getElementById('ruleRate').value = rate;
    document.getElementById('ruleMin').value = min;
    document.getElementById('ruleMax').value = max;
    document.getElementById('ruleEe').value = ee;
    document.getElementById('ruleEr').value = er;
    document.getElementById('ruleFixed').value = fixed;
    document.getElementById('ruleEff').value = eff;
    document.getElementById('ruleActive').checked = active;
    openModal('ruleModal');
}
</script>
@endpush
@endsection
