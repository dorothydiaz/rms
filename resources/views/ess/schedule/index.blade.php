@extends('layouts.ess')

@section('title', 'Work Schedule & Shifts')

@section('content')
<div class="ess-schedule-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-calendar"></i>
                <span>Work Schedule & Shift Calendar</span>
            </h1>
            <p class="hr-page-subtitle">
                Branch: <strong>{{ $employee->branch->branch_name ?? 'Branch' }}</strong> &bull; Department: <strong>{{ $employee->department->name ?? 'Dept' }}</strong> &bull; Monthly duty assignments and rest day scheduling
            </p>
        </div>

        <div class="hr-page-actions">
            <form method="GET" action="{{ route('ess.schedule') }}" style="margin:0;">
                <input type="month" name="month" value="{{ $month }}" class="ess-form-control" style="width:auto;padding:8px 12px;font-weight:600;" onchange="this.form.submit()">
            </form>

            <button type="button" class="hr-btn hr-btn-primary" onclick="openSwapModal()">
                <i class="ph ph-arrows-clockwise"></i>
                <span>Request Shift Swap</span>
            </button>
        </div>
    </div>

    <!-- Calendar Grid Legend -->
    <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px;flex-wrap:wrap;font-size:12px;font-weight:600;color:var(--ess-text-secondary);">
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(168,85,247,0.2);border:1px solid var(--ess-primary);"></span>
            <span>Regular Shift</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="width:12px;height:12px;border-radius:3px;background:#f1f5f9;border:1px solid #cbd5e1;"></span>
            <span>Rest Day (RD)</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(239,68,68,0.15);border:1px solid #dc2626;"></span>
            <span>Regular Holiday (RH - 200%)</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(245,158,11,0.15);border:1px solid #d97706;"></span>
            <span>Special Holiday (SH - 130%)</span>
        </div>
    </div>

    <!-- Monthly Calendar Matrix -->
    <div class="ess-card" style="padding:16px;">
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;">
            @php
                $daysInMonth = $startOfMonth->daysInMonth;
                $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
                $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            @endphp

            @foreach($dayNames as $dName)
                <div style="text-align:center;font-weight:700;font-size:12px;color:var(--ess-text-muted);padding:8px 0;text-transform:uppercase;">
                    {{ $dName }}
                </div>
            @endforeach

            {{-- Empty cells before first day of month --}}
            @for($i = 0; $i < $startDayOfWeek; $i++)
                <div style="background:#fafafa;border:1px dashed var(--ess-border);border-radius:8px;min-height:95px;opacity:0.4;"></div>
            @endfor

            {{-- Days of month --}}
            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $curDate = $startOfMonth->copy()->day($day)->toDateString();
                    $sched = $schedules->get($curDate);
                    $holiday = $holidays->get($curDate);
                    $isRestDay = $sched ? $sched->is_rest_day : ($startOfMonth->copy()->day($day)->isSunday());
                    $isToday = $curDate === now()->toDateString();
                @endphp

                <div style="background:{{ $isToday ? 'rgba(168,85,247,0.06)' : '#ffffff' }};border:{{ $isToday ? '2px solid var(--ess-primary)' : '1px solid var(--ess-border)' }};border-radius:10px;padding:8px;min-height:95px;display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s ease;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-weight:800;font-size:13px;color:{{ $isToday ? 'var(--ess-primary)' : 'var(--ess-text-main)' }};">
                            {{ $day }}
                        </span>
                        @if($isToday)
                            <span class="ess-badge ess-badge-success" style="font-size:9px;padding:1px 6px;">Today</span>
                        @endif
                    </div>

                    <div style="margin:6px 0;">
                        @if($holiday)
                            <div style="background:rgba(239,68,68,0.12);color:#dc2626;border-radius:6px;padding:3px 6px;font-size:10px;font-weight:700;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <i class="ph ph-flag"></i> {{ $holiday->name }}
                            </div>
                        @endif

                        @if($isRestDay)
                            <div style="background:#f1f5f9;color:#64748b;border-radius:6px;padding:4px 6px;font-size:11px;font-weight:700;text-align:center;">
                                Rest Day
                            </div>
                        @else
                            <div style="background:rgba(168,85,247,0.1);color:var(--ess-primary);border-radius:6px;padding:4px 6px;font-size:11px;font-weight:700;">
                                <i class="ph ph-clock"></i> 08:00 AM – 05:00 PM
                            </div>
                        @endif
                    </div>

                    <div style="font-size:10px;color:var(--ess-text-muted);text-align:right;">
                        {{ $employee->branch->branch_name ?? 'Makati' }}
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

<!-- Shift Swap Modal -->
<div id="shiftSwapModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-arrows-left-right"></i> Request Shift Swap</span>
            <button type="button" class="icon-btn" onclick="closeModal('shiftSwapModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('ess.schedule.swap') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Swap with Colleague (Same Branch) *</label>
                    <select name="swap_with_employee_id" class="ess-form-control" required>
                        <option value="">-- Select Colleague --</option>
                        @foreach($peers as $peer)
                            <option value="{{ $peer->employee_code ?? $peer->employee_id }}">
                                {{ $peer->first_name }} {{ $peer->last_name }} ({{ $peer->position->name ?? 'Staff' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div class="ess-form-group">
                        <label class="ess-form-label">My Shift Date *</label>
                        <input type="date" name="my_shift_date" class="ess-form-control" value="{{ now()->addDays(1)->toDateString() }}" required>
                    </div>
                    <div class="ess-form-group">
                        <label class="ess-form-label">Target Shift Date to Take *</label>
                        <input type="date" name="target_shift_date" class="ess-form-control" value="{{ now()->addDays(2)->toDateString() }}" required>
                    </div>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Reason for Shift Swap *</label>
                    <textarea name="reason" class="ess-form-control" rows="4" placeholder="Explain the mutual agreement and reason..." required></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('shiftSwapModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Swap Request</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openSwapModal() {
        openModal('shiftSwapModal');
    }
    function closeSwapModal() {
        closeModal('shiftSwapModal');
    }
</script>
@endpush
@endsection
