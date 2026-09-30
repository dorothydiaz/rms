@extends('layouts.app')

@section('title', 'Timekeeping Station - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance">
    <x-slot:actions>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="{{ route('hr.attendance.timekeeping') }}" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Select Date:</label>
                <div style="display: flex; gap: 3px; align-items: center;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px;" title="Previous Day">
                        <i class="ph ph-caret-left"></i>
                    </a>
                    <input type="date" name="date" class="hr-input" value="{{ $date }}" onchange="this.form.submit()" style="height: 38px;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px;" title="Next Day">
                        <i class="ph ph-caret-right"></i>
                    </a>
                    @if($date !== \Carbon\Carbon::today()->toDateString())
                        <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px; font-size: 12px; font-weight: 600;" title="Jump to Today">
                            Today
                        </a>
                    @endif
                </div>

                @if(isset($branches) && $branches->count() > 1)
                    <select name="branch_id" class="hr-select" onchange="this.form.submit()" style="height: 38px;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            <a href="{{ route('hr.attendance.corrections', ['date' => $date]) }}" class="hr-btn hr-btn-secondary" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;" title="View all manual entries for this date">
                <i class="ph ph-pencil-line"></i>
                <span>Manual Time Entries</span>
            </a>
            <button type="button" class="hr-btn hr-btn-primary" onclick="openTimekeepingEncodeModal()" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;" title="Encode Manual Time Entry (Hotkey: F2)">
                <i class="ph ph-plus-circle"></i>
                <span>Encode Time Entry</span>
            </button>
        </div>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="hr-table-title"><i class="ph ph-calendar-blank"></i> Staff Time Logs for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</span>
            <span class="hr-badge hr-badge-neutral" id="tkStaffCountBadge">{{ count($employees) }} Staff</span>
        </div>
        <div style="position: relative; min-width: 260px; margin-left: auto;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
            <input type="text" id="tkSearchInput" placeholder="Search staff name, ID, branch..." oninput="filterTimekeepingRows()" style="width: 100%; font-size: 12.5px; padding: 7px 28px 7px 30px; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none; height: 36px; box-sizing: border-box;">
            <button type="button" id="tkClearSearchBtn" onclick="clearTkSearch()" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: none; font-size: 16px; line-height: 1;">&times;</button>
        </div>
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
            <tbody id="timekeepingTableBody">
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
                            'status' => $att?->status ?? 'Auto',
                            'notes' => $att?->notes ?? '',
                        ];
                    @endphp
                    <tr class="timekeeping-row"
                        data-emp-id="{{ strtolower($emp->employee_id) }}"
                        data-emp-name="{{ strtolower($emp->full_name) }}"
                        data-emp-branch="{{ strtolower($emp->branch?->name ?? '') }}"
                        data-emp-dept="{{ strtolower($emp->department?->name ?? '') }}"
                        data-emp-pos="{{ strtolower($emp->position?->name ?? '') }}">
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

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="timekeepingPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="timekeepingPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="timekeepingPerPageSelect" onchange="tkChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="timekeepingPageNav"></nav>
    </div>
</div>

<!-- Modal: Manual Time Entry & Punch (Freely Encode 6 Punches or Quick Punch) -->
<div id="punchModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(124, 58, 237, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-fingerprint"></i>
                </div>
                <div>
                    <span class="hr-modal-title" style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;" id="punchModalHeaderTitle">Manual Time Entry & Punch</span>
                    <div style="font-size: 12px; color: #64748b;">Freely record, adjust, or punch time entries for chosen date</div>
                </div>
            </div>
            <button class="icon-btn" onclick="closeModal('punchModal')"><i class="ph ph-x"></i></button>
        </div>

        <!-- Mode Toggle Tabs -->
        <div style="display: flex; border-bottom: 1px solid #e2e8f0; background: #f8fafc; padding: 4px 20px 0 20px; gap: 8px;">
            <button type="button" id="tabBtnEncode" onclick="switchPunchTab('encode')" style="padding: 10px 16px; font-size: 13px; font-weight: 700; border: none; background: none; cursor: pointer; border-bottom: 2px solid #7c3aed; color: #7c3aed; display: flex; align-items: center; gap: 6px;">
                <i class="ph ph-pencil-line"></i> Freely Encode (All 6 Entries)
            </button>
            <button type="button" id="tabBtnSingle" onclick="switchPunchTab('single')" style="padding: 10px 16px; font-size: 13px; font-weight: 600; border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="ph ph-clock"></i> Quick Single Punch
            </button>
        </div>

        <!-- FORM 1: Freely Encode 6 Punches -->
        <form method="POST" action="{{ route('hr.attendance.corrections.store') }}" id="formEncode6Punches" style="display: flex; flex-direction: column; flex: 1 1 auto; height: 100%; min-height: 0; margin: 0;">
            @csrf
            <input type="hidden" id="encodeEmpId" name="employee_id" value="">
            <div class="hr-modal-body" style="padding: 20px 24px; flex: 1 1 auto; overflow-y: auto; min-height: 0;">
                
                <!-- Staff & Date Bar -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: center;">
                        <div id="wrapEmployeeSelect" style="display: none;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Staff Member *</label>
                            <select id="modalEmployeeDropdown" class="hr-select" onchange="onModalEmployeeChange(this.value)" style="height: 38px;">
                                <option value="">-- Choose Employee --</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="wrapEmployeeName">
                            <span style="font-size: 11px; text-transform: uppercase; color: #7e22ce; font-weight: 700; letter-spacing: 0.5px;">Employee</span>
                            <div style="font-weight: 700; font-size: 15px; color: #581c87;" id="punchEmployeeName">Employee Name</div>
                        </div>
                        <div>
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Chosen Date *</label>
                            <input type="date" name="date" id="punchDateInput" class="hr-input" required value="{{ $date }}" onchange="lookupPunchesForDateInModal()">
                        </div>
                    </div>
                    <div id="lookupAlertTimekeeping" style="display: none; margin-top: 10px; padding: 6px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;"></div>
                </div>

                <!-- 6 Punches Grid -->
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 6px;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px;">
                            6 Time Punches (24-Hour Format)
                        </span>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 2px 7px; height: auto;" onclick="applyPresetTimekeeping()">
                                <i class="ph ph-clock"></i> 8-Hr Preset
                            </button>
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 2px 7px; height: auto;" onclick="clearAllTimekeepingPunches()">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #059669; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>1. In</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkIn')">Now</span>
                            </label>
                            <input type="time" name="time_in" id="tkIn" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px; font-weight: 600;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #334155; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>2. Break Out</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkBreakOut')">Now</span>
                            </label>
                            <input type="time" name="break_out" id="tkBreakOut" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #334155; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>3. Break In</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkBreakIn')">Now</span>
                            </label>
                            <input type="time" name="break_in" id="tkBreakIn" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #b45309; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>4. Coffee Out</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkCoffeeOut')">Now</span>
                            </label>
                            <input type="time" name="coffee_break_out" id="tkCoffeeOut" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #b45309; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>5. Coffee In</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkCoffeeIn')">Now</span>
                            </label>
                            <input type="time" name="coffee_break_in" id="tkCoffeeIn" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #0284c7; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>6. Final Out</span>
                                <span style="font-size: 10px; color: #7c3aed; cursor: pointer;" onclick="setNowTk('tkFinalOut')">Now</span>
                            </label>
                            <input type="time" name="time_out" id="tkFinalOut" class="hr-input" style="height: 36px; font-family: monospace; font-size: 13.5px; font-weight: 600;">
                        </div>
                    </div>
                </div>

                <!-- Status & Notes -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label class="hr-form-label" style="font-size: 12px; font-weight: 600;">Status Override</label>
                        <select name="status" id="tkStatus" class="hr-select" style="height: 38px;">
                            <option value="Auto">Auto-Compute</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Rest Day">Rest Day</option>
                            <option value="Overtime">Overtime</option>
                            <option value="Absent">Absent</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    <div>
                        <label class="hr-form-label" style="font-size: 12px; font-weight: 600;">Notes / Reason</label>
                        <input type="text" name="notes" id="tkNotes" class="hr-input" placeholder="e.g. Biometric manual sync, supervisor override..." style="height: 38px;">
                    </div>
                </div>

            </div>
            <div class="hr-modal-footer" style="padding: 18px 24px; border-top: 1.5px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-top: auto; flex-shrink: 0; background: #ffffff;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('punchModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-check-circle"></i> Save Time Entries
                </button>
            </div>
        </form>

        <!-- FORM 2: Quick Single Punch -->
        <form method="POST" action="{{ route('hr.attendance.timekeeping.store') }}" id="formSinglePunch" style="display: none; flex-direction: column; flex: 1 1 auto; height: 100%; min-height: 0; margin: 0;">
            @csrf
            <input type="hidden" id="singlePunchEmpId" name="employee_id" value="">
            <input type="hidden" id="singlePunchDate" name="date" value="{{ $date }}">
            <div class="hr-modal-body" style="padding: 20px 24px; flex: 1 1 auto; overflow-y: auto; min-height: 0;">
                <!-- 6 Punches Status Overview -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        Current 6 Punches Status:
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">1. In</div>
                            <div id="statusIn" style="font-size: 12.5px; font-weight: 700; color: #059669; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">2. Break Out</div>
                            <div id="statusBreakOut" style="font-size: 12.5px; font-weight: 700; color: #334155; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">3. Break In</div>
                            <div id="statusBreakIn" style="font-size: 12.5px; font-weight: 700; color: #334155; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">4. Coffee Out</div>
                            <div id="statusCoffeeOut" style="font-size: 12.5px; font-weight: 700; color: #b45309; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">5. Coffee In</div>
                            <div id="statusCoffeeIn" style="font-size: 12.5px; font-weight: 700; color: #b45309; font-family: monospace;">--:--</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">6. Final Out</div>
                            <div id="statusFinalOut" style="font-size: 12.5px; font-weight: 700; color: #0284c7; font-family: monospace;">--:--</div>
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
                    <input type="text" name="notes" id="singlePunchNotes" class="hr-input" placeholder="e.g. Biometric station log, Manager manual punch">
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 18px 24px; border-top: 1.5px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-top: auto; flex-shrink: 0; background: #ffffff;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('punchModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
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

function switchPunchTab(tab) {
    const formEncode = document.getElementById('formEncode6Punches');
    const formSingle = document.getElementById('formSinglePunch');
    const btnEncode = document.getElementById('tabBtnEncode');
    const btnSingle = document.getElementById('tabBtnSingle');

    if (tab === 'encode') {
        formEncode.style.display = 'block';
        formSingle.style.display = 'none';
        btnEncode.style.color = '#7c3aed';
        btnEncode.style.borderBottomColor = '#7c3aed';
        btnSingle.style.color = '#64748b';
        btnSingle.style.borderBottomColor = 'transparent';
    } else {
        formEncode.style.display = 'none';
        formSingle.style.display = 'block';
        btnSingle.style.color = '#0f172a';
        btnSingle.style.borderBottomColor = '#0f172a';
        btnEncode.style.color = '#64748b';
        btnEncode.style.borderBottomColor = 'transparent';
    }
}

function setNowTk(inputId) {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const el = document.getElementById(inputId);
    if (el) el.value = `${hh}:${mm}`;
}

function setCurrentTime() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('punchTimeInput').value = `${hh}:${mm}`;
}

function applyPresetTimekeeping() {
    document.getElementById('tkIn').value = '08:00';
    document.getElementById('tkBreakOut').value = '12:00';
    document.getElementById('tkBreakIn').value = '13:00';
    document.getElementById('tkCoffeeOut').value = '15:00';
    document.getElementById('tkCoffeeIn').value = '15:15';
    document.getElementById('tkFinalOut').value = '17:00';
}

function clearAllTimekeepingPunches() {
    document.getElementById('tkIn').value = '';
    document.getElementById('tkBreakOut').value = '';
    document.getElementById('tkBreakIn').value = '';
    document.getElementById('tkCoffeeOut').value = '';
    document.getElementById('tkCoffeeIn').value = '';
    document.getElementById('tkFinalOut').value = '';
}

function onModalEmployeeChange(empId) {
    document.getElementById('encodeEmpId').value = empId;
    document.getElementById('singlePunchEmpId').value = empId;
    lookupPunchesForDateInModal();
}

async function lookupPunchesForDateInModal() {
    const empId = document.getElementById('encodeEmpId').value;
    const dateVal = document.getElementById('punchDateInput').value;
    const alertEl = document.getElementById('lookupAlertTimekeeping');

    if (!empId || !dateVal) return;
    document.getElementById('singlePunchDate').value = dateVal;

    try {
        const res = await fetch(`{{ route('hr.attendance.corrections.lookup') }}?employee_id=${empId}&date=${dateVal}`);
        const data = await res.json();

        if (data.exists) {
            document.getElementById('tkIn').value = data.time_in || '';
            document.getElementById('tkBreakOut').value = data.break_out || '';
            document.getElementById('tkBreakIn').value = data.break_in || '';
            document.getElementById('tkCoffeeOut').value = data.coffee_break_out || '';
            document.getElementById('tkCoffeeIn').value = data.coffee_break_in || '';
            document.getElementById('tkFinalOut').value = data.time_out || '';
            document.getElementById('tkStatus').value = data.status || 'Auto';
            document.getElementById('tkNotes').value = data.notes || '';

            alertEl.style.display = 'block';
            alertEl.style.background = '#eff6ff';
            alertEl.style.color = '#1d4ed8';
            alertEl.style.border = '1px solid #bfdbfe';
            alertEl.innerText = `Existing record loaded for ${dateVal} (${data.total_hours} hrs logged). You can freely adjust punches below.`;
        } else {
            clearAllTimekeepingPunches();
            document.getElementById('tkStatus').value = 'Auto';
            alertEl.style.display = 'block';
            alertEl.style.background = '#f0fdf4';
            alertEl.style.color = '#15803d';
            alertEl.style.border = '1px solid #bbf7d0';
            alertEl.innerText = `No punches yet for ${dateVal}. Freely encode new time entries below.`;
        }
    } catch (e) {
        console.error('Error looking up punches:', e);
    }
}

function openPunchModal(empId, empName, punches) {
    document.getElementById('encodeEmpId').value = empId;
    document.getElementById('singlePunchEmpId').value = empId;
    document.getElementById('punchEmployeeName').innerText = empName;
    document.getElementById('wrapEmployeeSelect').style.display = 'none';
    document.getElementById('wrapEmployeeName').style.display = 'block';
    document.getElementById('punchModalHeaderTitle').innerText = 'Manual Time Entry - ' + empName;

    const chosenDate = '{{ $date }}';
    document.getElementById('punchDateInput').value = chosenDate;
    document.getElementById('singlePunchDate').value = chosenDate;

    // Fill Encode Form inputs
    document.getElementById('tkIn').value = punches.in || '';
    document.getElementById('tkBreakOut').value = punches.break_out || '';
    document.getElementById('tkBreakIn').value = punches.break_in || '';
    document.getElementById('tkCoffeeOut').value = punches.coffee_break_out || '';
    document.getElementById('tkCoffeeIn').value = punches.coffee_break_in || '';
    document.getElementById('tkFinalOut').value = punches.final_out || '';
    document.getElementById('tkStatus').value = punches.status || 'Auto';
    document.getElementById('tkNotes').value = punches.notes || '';

    // Fill Single Punch Form overview
    const formatOrDash = (v) => v ? v : '--:--';
    document.getElementById('statusIn').innerText = formatOrDash(punches.in);
    document.getElementById('statusBreakOut').innerText = formatOrDash(punches.break_out);
    document.getElementById('statusBreakIn').innerText = formatOrDash(punches.break_in);
    document.getElementById('statusCoffeeOut').innerText = formatOrDash(punches.coffee_break_out);
    document.getElementById('statusCoffeeIn').innerText = formatOrDash(punches.coffee_break_in);
    document.getElementById('statusFinalOut').innerText = formatOrDash(punches.final_out);

    // Auto-select next punch
    const select = document.getElementById('punchTypeSelect');
    if (!punches.in) select.value = 'in';
    else if (!punches.break_out) select.value = 'break_out';
    else if (!punches.break_in) select.value = 'break_in';
    else if (!punches.coffee_break_out) select.value = 'coffee_break_out';
    else if (!punches.coffee_break_in) select.value = 'coffee_break_in';
    else if (!punches.final_out) select.value = 'final_out';
    else select.value = 'in';

    document.getElementById('lookupAlertTimekeeping').style.display = 'none';
    setCurrentTime();
    switchPunchTab('encode');
    openModal('punchModal');
}

function openTimekeepingEncodeModal() {
    document.getElementById('encodeEmpId').value = '';
    document.getElementById('singlePunchEmpId').value = '';
    document.getElementById('wrapEmployeeSelect').style.display = 'block';
    document.getElementById('wrapEmployeeName').style.display = 'none';
    document.getElementById('punchModalHeaderTitle').innerText = 'Encode Manual Time Entry';
    document.getElementById('modalEmployeeDropdown').value = '';

    const chosenDate = '{{ $date }}';
    document.getElementById('punchDateInput').value = chosenDate;
    document.getElementById('singlePunchDate').value = chosenDate;

    clearAllTimekeepingPunches();
    document.getElementById('tkStatus').value = 'Auto';
    document.getElementById('tkNotes').value = '';
    document.getElementById('lookupAlertTimekeeping').style.display = 'none';

    switchPunchTab('encode');
    openModal('punchModal');
}

// =========================================================================
// Fuzzy Search & Client-Side Pagination for Timekeeping
// =========================================================================
function _tkLevenshtein(a, b) {
    const m = a.length, n = b.length;
    const dp = Array.from({ length: m + 1 }, (_, i) =>
        Array.from({ length: n + 1 }, (_, j) => (i === 0 ? j : j === 0 ? i : 0))
    );
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = a[i-1] === b[j-1]
                ? dp[i-1][j-1]
                : 1 + Math.min(dp[i-1][j], dp[i][j-1], dp[i-1][j-1]);
        }
    }
    return dp[m][n];
}

function _tkFuzzyMatch(haystack, query) {
    if (!query) return true;
    if (haystack.includes(query)) return true;
    const tokens = haystack.split(/[\s,._\-\/]+/).filter(Boolean);
    const qLen = query.length;
    for (const token of tokens) {
        if (token.length < 2) continue;
        const threshold = Math.floor(Math.min(token.length, qLen) / 4) + 1;
        if (_tkLevenshtein(token, query) <= threshold) return true;
    }
    return false;
}

let _tkPage = 1, _tkRows = [];

function tkGetPerPage() {
    return parseInt(document.getElementById('timekeepingPerPageSelect')?.value || '15', 10);
}

function tkChangePerPage() {
    _tkPage = 1;
    renderTkPage();
}

function tkGoToPage(p) {
    _tkPage = p;
    renderTkPage();
    const w = document.getElementById('timekeepingTableBody')?.closest('.hr-table-wrapper');
    if (w) w.scrollTop = 0;
}

function renderTkPage() {
    const pp = tkGetPerPage(), total = _tkRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_tkPage > totalPages) _tkPage = totalPages;
    const start = (_tkPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#timekeepingTableBody tr.timekeeping-row').forEach(r => r.style.display = 'none');
    _tkRows.forEach((r, i) => {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    const bar = document.getElementById('timekeepingPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    const info = document.getElementById('timekeepingPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> staff`;

    const badge = document.getElementById('tkStaffCountBadge');
    if (badge) badge.innerText = `${total} Staff`;

    const nav = document.getElementById('timekeepingPageNav');
    if (!nav) return;

    let h = _tkPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="tkGoToPage(${_tkPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;

    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _tkPage - 2), pe = Math.min(totalPages, _tkPage + 2);
    if (ps > 1) {
        h += `<span class="hr-page-num" onclick="tkGoToPage(1)" style="cursor:pointer">1</span>`;
        if (ps > 2) h += `<span class="hr-page-num dots">…</span>`;
    }
    for (let p = ps; p <= pe; p++) {
        h += p === _tkPage
            ? `<span class="hr-page-num active">${p}</span>`
            : `<span class="hr-page-num" onclick="tkGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    }
    if (pe < totalPages) {
        if (pe < totalPages - 1) h += `<span class="hr-page-num dots">…</span>`;
        h += `<span class="hr-page-num" onclick="tkGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`;
    }
    h += `</div>`;

    h += _tkPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="tkGoToPage(${_tkPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;

    nav.innerHTML = h;
}

function filterTimekeepingRows() {
    const q = (document.getElementById('tkSearchInput')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('tkClearSearchBtn');
    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

    const allRows = Array.from(document.querySelectorAll('#timekeepingTableBody tr.timekeeping-row'));
    if (!q) {
        _tkRows = allRows;
    } else {
        _tkRows = allRows.filter(r => {
            const id = r.getAttribute('data-emp-id') || '';
            const name = r.getAttribute('data-emp-name') || '';
            const branch = r.getAttribute('data-emp-branch') || '';
            const dept = r.getAttribute('data-emp-dept') || '';
            const pos = r.getAttribute('data-emp-pos') || '';
            return _tkFuzzyMatch(id, q) || _tkFuzzyMatch(name, q) || _tkFuzzyMatch(branch, q) || _tkFuzzyMatch(dept, q) || _tkFuzzyMatch(pos, q);
        });
    }

    _tkPage = 1;
    renderTkPage();
}

function clearTkSearch() {
    const inp = document.getElementById('tkSearchInput');
    if (inp) inp.value = '';
    filterTimekeepingRows();
    inp?.focus();
}

// Hotkey support: F2 to encode time entry, Escape to close
document.addEventListener('keydown', (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        openTimekeepingEncodeModal();
    } else if (e.key === 'Escape') {
        closeModal('punchModal');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    filterTimekeepingRows();
});
</script>
@endpush
@endsection
