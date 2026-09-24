@extends('layouts.app')

@section('title', 'Timekeeping Station - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance">
    <x-slot:actions>
        <form method="GET" action="{{ route('hr.attendance.timekeeping') }}" style="display: flex; gap: 8px; align-items: center;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Select Date:</label>
            <input type="date" name="date" class="hr-input" value="{{ $date }}" onchange="this.form.submit()">
        </form>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-header">
        <span class="hr-table-title"><i class="ph ph-calendar-blank"></i> Staff Time Logs for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</span>
        <span class="hr-badge hr-badge-neutral">{{ count($employees) }} Scheduled Staff</span>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Staff Name</th>
                    <th>Branch & Dept</th>
                    <th>In</th>
                    <th>Break Out</th>
                    <th>Break In</th>
                    <th>CB Out</th>
                    <th>CB In</th>
                    <th>Final Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                    <th style="text-align: right;">Manual Punch</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    @php
                        $att = $attendanceMap->get($emp->id);
                        $pIn = $att?->time_in ?? $att?->in_1;
                        $pBreakOut = $att?->break_out ?? $att?->out_1;
                        $pBreakIn = $att?->break_in ?? $att?->in_2;
                        $pCoffeeOut = $att?->coffee_break_out ?? $att?->out_2;
                        $pCoffeeIn = $att?->coffee_break_in ?? $att?->in_3;
                        $pFinalOut = $att?->time_out ?? $att?->out_3;

                        $punchesData = [
                            'in' => $pIn ? substr($pIn, 0, 5) : null,
                            'break_out' => $pBreakOut ? substr($pBreakOut, 0, 5) : null,
                            'break_in' => $pBreakIn ? substr($pBreakIn, 0, 5) : null,
                            'coffee_break_out' => $pCoffeeOut ? substr($pCoffeeOut, 0, 5) : null,
                            'coffee_break_in' => $pCoffeeIn ? substr($pCoffeeIn, 0, 5) : null,
                            'final_out' => $pFinalOut ? substr($pFinalOut, 0, 5) : null,
                        ];
                    @endphp
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $emp->employee_id }}</strong></td>
                        <td>
                            <div class="hr-emp-avatar-wrap">
                                @if($emp->photo_url)
                                    <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="hr-avatar-img-sm">
                                @else
                                    <div class="hr-avatar-circle-sm">
                                        {{ $emp->initials }}
                                    </div>
                                @endif
                                <div>
                                    <strong>{{ $emp->full_name }}</strong><br>
                                    <small style="color: #64748b;">{{ $emp->position?->name }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $emp->branch?->name }}</div>
                            <small style="color: #64748b;">{{ $emp->department?->name }}</small>
                        </td>

                        <!-- 1. In -->
                        <td>
                            @if($pIn)
                                <strong style="color: #059669;">{{ substr($pIn, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 2. Break Out -->
                        <td>
                            @if($pBreakOut)
                                <span>{{ substr($pBreakOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 3. Break In -->
                        <td>
                            @if($pBreakIn)
                                <span>{{ substr($pBreakIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 4. Coffee Break Out -->
                        <td>
                            @if($pCoffeeOut)
                                <span style="color: #b45309;">{{ substr($pCoffeeOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 5. Coffee Break In -->
                        <td>
                            @if($pCoffeeIn)
                                <span style="color: #b45309;">{{ substr($pCoffeeIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 6. Final Out -->
                        <td>
                            @if($pFinalOut)
                                <strong style="color: #0284c7;">{{ substr($pFinalOut, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- Total Hours -->
                        <td>
                            <strong>{{ number_format($att?->total_hours ?? 0, 2) }} hrs</strong>
                        </td>

                        <!-- Status Badge -->
                        <td>
                            @if($att)
                                @if($att->status === 'Present')
                                    <span class="hr-badge hr-badge-success">{{ $att->status }}</span>
                                @elseif($att->status === 'Late')
                                    <span class="hr-badge hr-badge-warning">{{ $att->status }} ({{ $att->late_minutes }}m)</span>
                                @elseif($att->status === 'Rest Day')
                                    <span class="hr-badge hr-badge-neutral">{{ $att->status }}</span>
                                @else
                                    <span class="hr-badge hr-badge-danger">{{ $att->status }}</span>
                                @endif
                            @else
                                <span class="hr-badge hr-badge-neutral">Pending</span>
                            @endif
                        </td>

                        <!-- Action -->
                        <td style="text-align: right;">
                            <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick='openPunchModal({{ $emp->id }}, "{{ addslashes($emp->full_name) }}", {{ json_encode($punchesData) }})'>
                                <i class="ph ph-fingerprint"></i> Punch
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="text-align: center; color: #94a3b8; padding: 30px;">No employees active for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Record Time Punch (6 Time Entries) -->
<div id="punchModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 560px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-fingerprint"></i> Record Time Punch (6 Time Entries)</span>
            <button class="icon-btn" onclick="closeModal('punchModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.timekeeping.store') }}">
            @csrf
            <input type="hidden" id="punchEmployeeId" name="employee_id" value="">
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="hr-modal-body">
                <!-- Employee Header Banner -->
                <div style="background: rgba(147, 51, 234, 0.08); border: 1px solid rgba(147, 51, 234, 0.2); border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; color: #7e22ce; font-weight: 700; letter-spacing: 0.5px;">Employee</span>
                        <div style="font-weight: 700; font-size: 15px; color: #581c87;" id="punchEmployeeName">Employee Name</div>
                    </div>
                    <span class="hr-badge hr-badge-purple">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
                </div>

                <!-- 6 Punches Today's Status Overview -->
                <div>
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        Today's 6 Punch Entries Status:
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">1. In</div>
                            <div id="statusIn" style="font-size: 13px; font-weight: 700; color: #059669; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">2. Break Out</div>
                            <div id="statusBreakOut" style="font-size: 13px; font-weight: 700; color: #334155; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">3. Break In</div>
                            <div id="statusBreakIn" style="font-size: 13px; font-weight: 700; color: #334155; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">4. Coffee Out</div>
                            <div id="statusCoffeeOut" style="font-size: 13px; font-weight: 700; color: #b45309; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">5. Coffee In</div>
                            <div id="statusCoffeeIn" style="font-size: 13px; font-weight: 700; color: #b45309; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">6. Final Out</div>
                            <div id="statusFinalOut" style="font-size: 13px; font-weight: 700; color: #0284c7; font-family: monospace;">--:--</div>
                        </div>
                    </div>
                </div>

                <!-- Punch Selection -->
                <div class="hr-form-group">
                    <label class="hr-form-label">Select Punch Type *</label>
                    <select name="punch_type" id="punchTypeSelect" class="hr-select" required>
                        <option value="in">1. In (Shift Start)</option>
                        <option value="break_out">2. Break Out (Lunch / Meal Break)</option>
                        <option value="break_in">3. Break In (Resume from Lunch)</option>
                        <option value="coffee_break_out">4. Coffee Break Out</option>
                        <option value="coffee_break_in">5. Coffee Break In (Resume from Coffee)</option>
                        <option value="final_out">6. Final Out (Shift End)</option>
                    </select>
                </div>

                <!-- Time Input -->
                <div class="hr-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="hr-form-label" style="margin-bottom: 0;">Time (24-Hour Format) *</label>
                        <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 1px 7px; height: auto;" onclick="setCurrentTime()">Set Current Time</button>
                    </div>
                    <input type="time" name="time" id="punchTimeInput" class="hr-input" required value="{{ date('H:i') }}">
                </div>

                <!-- Notes Input -->
                <div class="hr-form-group">
                    <label class="hr-form-label">Notes / Source</label>
                    <input type="text" name="notes" class="hr-input" placeholder="e.g. Biometric station log, Manager manual punch">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('punchModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: #0f172a; border-color: #0f172a; color: #fff;">
                    <i class="ph ph-check"></i> Record Punch
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function setCurrentTime() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('punchTimeInput').value = `${hh}:${mm}`;
}

function openPunchModal(empId, empName, punches) {
    document.getElementById('punchEmployeeId').value = empId;
    document.getElementById('punchEmployeeName').innerText = empName;

    // Display current values in the mini status overview
    const formatOrDash = (v) => v ? v : '--:--';
    document.getElementById('statusIn').innerText = formatOrDash(punches.in);
    document.getElementById('statusBreakOut').innerText = formatOrDash(punches.break_out);
    document.getElementById('statusBreakIn').innerText = formatOrDash(punches.break_in);
    document.getElementById('statusCoffeeOut').innerText = formatOrDash(punches.coffee_break_out);
    document.getElementById('statusCoffeeIn').innerText = formatOrDash(punches.coffee_break_in);
    document.getElementById('statusFinalOut').innerText = formatOrDash(punches.final_out);

    // Auto-select the next pending punch in the 6-punch sequence
    const select = document.getElementById('punchTypeSelect');
    if (!punches.in) {
        select.value = 'in';
    } else if (!punches.break_out) {
        select.value = 'break_out';
    } else if (!punches.break_in) {
        select.value = 'break_in';
    } else if (!punches.coffee_break_out) {
        select.value = 'coffee_break_out';
    } else if (!punches.coffee_break_in) {
        select.value = 'coffee_break_in';
    } else if (!punches.final_out) {
        select.value = 'final_out';
    } else {
        select.value = 'in'; // All punched, allow override
    }

    setCurrentTime();
    openModal('punchModal');
}
</script>
@endpush
@endsection
