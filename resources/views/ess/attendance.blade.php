@extends('layouts.app')

@section('title', 'Daily Time Record (DTR) & Attendance')

@section('content')
<div class="ess-attendance-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">Daily Time Record (DTR) & Attendance</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">DOLE-aligned attendance logs with overtime, night differential, and holiday classifications.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="{{ route('ess.attendance') }}" style="display: flex; gap: 8px;">
                <input type="month" name="month" value="{{ $month }}" class="form-input" onchange="this.form.submit()">
            </form>
            <a href="{{ route('ess.attendance.export-dtr') }}?month={{ $month }}" target="_blank" class="btn btn-outline">
                <i class="ph ph-printer"></i> Print DTR (DOLE Format)
            </a>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('correctionModal').style.display = 'flex'">
                <i class="ph ph-pencil-simple-line"></i> Request Correction
            </button>
        </div>
    </div>

    <!-- Monthly Attendance KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-radius: 12px; border-left: 4px solid #2563eb;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">TOTAL REGULAR HOURS</span>
            <div style="font-size: 22px; font-weight: 800; color: #1e293b; margin-top: 4px;">{{ number_format($totalHours, 2) }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">hrs</span></div>
        </div>
        <div class="card" style="padding: 16px; border-radius: 12px; border-left: 4px solid #f59e0b;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">OVERTIME (DOLE ART. 87)</span>
            <div style="font-size: 22px; font-weight: 800; color: #1e293b; margin-top: 4px;">{{ number_format($totalOt, 2) }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">hrs</span></div>
        </div>
        <div class="card" style="padding: 16px; border-radius: 12px; border-left: 4px solid #8b5cf6;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">NIGHT DIFF (10PM - 6AM)</span>
            <div style="font-size: 22px; font-weight: 800; color: #1e293b; margin-top: 4px;">{{ number_format($totalNightDiff, 2) }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">hrs</span></div>
        </div>
        <div class="card" style="padding: 16px; border-radius: 12px; border-left: 4px solid #ef4444;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">TARDINESS & UNDERTIME</span>
            <div style="font-size: 22px; font-weight: 800; color: #1e293b; margin-top: 4px;">{{ $totalLate + $totalUndertime }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">mins</span></div>
        </div>
    </div>

    <!-- DTR Table Card -->
    <div class="card" style="padding: 24px; border-radius: 14px;">
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 12px;">
                        <th style="padding: 10px;">Date & Day</th>
                        <th style="padding: 10px;">Shift Schedule</th>
                        <th style="padding: 10px;">Time In</th>
                        <th style="padding: 10px;">Time Out</th>
                        <th style="padding: 10px;">Hours</th>
                        <th style="padding: 10px;">Overtime</th>
                        <th style="padding: 10px;">Night Diff</th>
                        <th style="padding: 10px;">Late/UT</th>
                        <th style="padding: 10px;">Status / Classification</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr style="border-bottom: 1px solid #f1f5f9; {{ $rec->is_rest_day ? 'background: #faf5ff;' : '' }}">
                            <td style="padding: 12px 10px; font-weight: 600; color: #1e293b;">
                                {{ $rec->date->format('M d, Y (D)') }}
                            </td>
                            <td style="padding: 12px 10px; color: #64748b;">
                                {{ $rec->shift_template_name ?? 'Standard (08:00 - 17:00)' }}
                            </td>
                            <td style="padding: 12px 10px; font-weight: 600; color: {{ $rec->time_in ? '#0f172a' : '#94a3b8' }};">
                                {{ $rec->time_in ?: '--:--' }}
                            </td>
                            <td style="padding: 12px 10px; font-weight: 600; color: {{ $rec->time_out ? '#0f172a' : '#94a3b8' }};">
                                {{ $rec->time_out ?: '--:--' }}
                            </td>
                            <td style="padding: 12px 10px; color: #334155;">
                                {{ number_format($rec->regular_hours, 2) }}
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($rec->overtime_hours > 0)
                                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                        +{{ $rec->overtime_hours }}h ({{ (int)($rec->ot_multiplier * 100) }}%)
                                    </span>
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($rec->night_diff_hours > 0)
                                    <span class="badge" style="background: #f5f3ff; color: #6b21a8; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                        {{ $rec->night_diff_hours }}h (+10%)
                                    </span>
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($rec->late_minutes > 0 || $rec->undertime_minutes > 0)
                                    <span style="color: #dc2626; font-weight: 600;">
                                        {{ $rec->late_minutes > 0 ? "L:{$rec->late_minutes}m " : '' }}
                                        {{ $rec->undertime_minutes > 0 ? "UT:{$rec->undertime_minutes}m" : '' }}
                                    </span>
                                @else
                                    <span style="color: #10b981;">✓ On time</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($rec->holiday_type)
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-weight: 700;">
                                        {{ $rec->holiday_type }} ({{ $rec->holiday_type === 'Regular' ? '200%' : '130%' }})
                                    </span>
                                @elseif($rec->is_rest_day)
                                    <span class="badge" style="background: #f3e8ff; color: #6b21a8; padding: 3px 8px; border-radius: 4px; font-weight: 700;">
                                        Rest Day (130%)
                                    </span>
                                @elseif($rec->status === 'Present')
                                    <span class="badge" style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
                                        Present
                                    </span>
                                @else
                                    <span class="badge" style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px;">
                                        {{ $rec->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No attendance records found for {{ $month }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Correction Modal -->
<div id="correctionModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700;">Request Attendance Correction</h3>
        <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">Submit missing punch or correction. Original time logs are never overwritten directly; your supervisor will review and approve.</p>
        <form action="{{ route('ess.attendance.correction.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Date of Discrepancy</label>
                <input type="date" name="date" class="form-input" style="width: 100%;" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Actual Time In</label>
                    <input type="time" name="time_in" class="form-input" style="width: 100%;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Actual Time Out</label>
                    <input type="time" name="time_out" class="form-input" style="width: 100%;">
                </div>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Reason / Explanation</label>
                <textarea name="reason" class="form-input" style="width: 100%;" rows="3" placeholder="e.g. Biometric scanner malfunction during closing, verified by Shift Manager" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('correctionModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit for Approval</button>
            </div>
        </form>
    </div>
</div>
@endsection
