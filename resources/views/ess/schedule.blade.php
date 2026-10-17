@extends('layouts.app')

@section('title', 'Work Schedule & Shifts')

@section('content')
<div class="ess-schedule-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">Work Schedule & Shifts</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Weekly assigned working shifts, meal periods, and rest days.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('ess.schedule') }}?start_date={{ $weekStart->copy()->subWeek()->toDateString() }}" class="btn btn-outline">
                <i class="ph ph-caret-left"></i> Previous Week
            </a>
            <span style="font-weight: 700; color: #1e293b; font-size: 14px;">
                {{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d, Y') }}
            </span>
            <a href="{{ route('ess.schedule') }}?start_date={{ $weekStart->copy()->addWeek()->toDateString() }}" class="btn btn-outline">
                Next Week <i class="ph ph-caret-right"></i>
            </a>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('scheduleModal').style.display = 'flex'">
                <i class="ph ph-arrows-clockwise"></i> Request Shift Swap / Adjustment
            </button>
        </div>
    </div>

    <!-- Weekly Schedule Grid -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 14px; margin-bottom: 24px;">
        @php
            $cursor = $weekStart->copy();
        @endphp
        @for($i = 0; $i < 7; $i++)
            @php
                $daySchedule = $schedules->first(fn($s) => \Carbon\Carbon::parse($s->schedule_date)->isSameDay($cursor));
                $isToday = $cursor->isToday();
            @endphp
            <div class="card" style="padding: 16px; border-radius: 12px; {{ $isToday ? 'border: 2px solid #2563eb; background: #eff6ff;' : '' }}">
                <div style="font-size: 12px; font-weight: 700; color: {{ $isToday ? '#2563eb' : '#64748b' }}; text-transform: uppercase;">
                    {{ $cursor->format('D, M d') }}
                </div>
                <div style="margin-top: 14px;">
                    @if($daySchedule)
                        <div style="font-size: 14px; font-weight: 700; color: #1e293b;">
                            {{ $daySchedule->shiftTemplate?->name ?? 'Standard Shift' }}
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                            @if($daySchedule->shiftTemplate)
                                {{ \Carbon\Carbon::parse($daySchedule->shiftTemplate->start_time)->format('h:i A') }} - 
                                {{ \Carbon\Carbon::parse($daySchedule->shiftTemplate->end_time)->format('h:i A') }}
                            @else
                                08:00 AM - 05:00 PM
                            @endif
                        </div>
                        <span class="badge" style="background: #dcfce7; color: #166534; font-size: 11px; margin-top: 8px; display: inline-block;">
                            Assigned Shift
                        </span>
                    @else
                        <div style="font-size: 14px; font-weight: 700; color: #94a3b8;">
                            Rest Day
                        </div>
                        <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px;">
                            Off Duty
                        </div>
                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 11px; margin-top: 8px; display: inline-block;">
                            Rest Day
                        </span>
                    @endif
                </div>
            </div>
            @php $cursor->addDay(); @endphp
        @endfor
    </div>
</div>

<!-- Shift Adjustment / Swap Modal -->
<div id="scheduleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">Request Schedule Adjustment</h3>
        <form action="{{ route('ess.schedule.adjustment.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Request Type</label>
                <select name="request_type" class="form-input" style="width: 100%;" required>
                    <option value="SHIFT_SWAP">Shift Swap with Colleague</option>
                    <option value="SCHEDULE_ADJUSTMENT">Change Working Shift / Hours</option>
                </select>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Shift Date</label>
                <input type="date" name="date" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Desired Shift Template</label>
                <select name="target_shift_id" class="form-input" style="width: 100%;">
                    <option value="">Select Target Shift (Optional)</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Reason for Adjustment</label>
                <textarea name="reason" class="form-input" style="width: 100%;" rows="3" placeholder="Explain the operational reason or personal emergency" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('scheduleModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection
