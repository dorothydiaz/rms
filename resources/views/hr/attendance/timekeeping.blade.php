@extends('layouts.app')

@section('title', 'Staff Timekeeping - Time & Attendance Management')

@push('styles')
    @vite('resources/css/modules/hr-timekeeping.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-timekeeping.js')
@endpush

@section('content')
<x-hr-tabs parent="time-attendance" />

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 8px 14px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span class="hr-table-title"><i class="ph ph-calendar-blank"></i> Staff Time Logs</span>
                <span class="hr-badge hr-badge-neutral" id="tkStaffCountBadge">{{ count($employees) }} Staff</span>
            </div>

            <!-- Date Filter Form -->
            <form method="GET" action="{{ route('hr.attendance.timekeeping') }}" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; gap: 3px; align-items: center;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 31px; padding: 0 8px;" title="Previous Day">
                        <i class="ph ph-caret-left"></i>
                    </a>
                    <input type="date" name="date" class="hr-input" value="{{ $date }}" onchange="this.form.submit()" style="height: 31px; padding: 2px 8px; font-size: 12px;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 31px; padding: 0 8px;" title="Next Day">
                        <i class="ph ph-caret-right"></i>
                    </a>
                    @if($date !== \Carbon\Carbon::today()->toDateString())
                        <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 31px; padding: 0 8px; font-size: 11px; font-weight: 600;" title="Jump to Today">
                            Today
                        </a>
                    @endif
                </div>

                @if(isset($branches) && $branches->count() > 1)
                    <select name="branch_id" class="hr-select" onchange="this.form.submit()" style="height: 31px; font-size: 12px; padding: 2px 8px;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
        </div>
        <div style="position: relative; min-width: 220px; margin-left: auto;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
            <input type="text" id="tkSearchInput" placeholder="Search staff name, ID, branch..." oninput="filterTimekeepingRows()" style="width: 100%; font-size: 12px; padding: 4px 26px 4px 28px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; height: 31px; box-sizing: border-box;">
            <button type="button" id="tkClearSearchBtn" onclick="clearTkSearch()" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: none; font-size: 15px; line-height: 1;">&times;</button>
        </div>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table hr-timekeeping-table" id="timekeepingTable">
            <thead>
                <tr>
                    <th class="tk-col-id">Employee ID</th>
                    <th class="tk-col-staff">Staff Name</th>
                    <th class="tk-col-dept">Branch & Dept</th>
                    <th class="tk-col-punch-th" title="Time In (1. In)">In</th>
                    <th class="tk-col-punch-th" title="Lunch Break Out (2. Break Out)">Break Out</th>
                    <th class="tk-col-punch-th" title="Lunch Break In (3. Break In)">Break In</th>
                    <th class="tk-col-punch-th" title="Coffee Break Out (4. Coffee Break Out)">CB Out</th>
                    <th class="tk-col-punch-th" title="Coffee Break In (5. Coffee Break In)">CB In</th>
                    <th class="tk-col-punch-th" title="Final Time Out (6. Final Out)">Final Out</th>
                    <th class="tk-col-hours">Total Hours</th>
                    <th class="tk-col-status">Status</th>
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
                        <td class="tk-col-id"><strong style="color: #9333ea; font-family: monospace;">{{ $emp->employee_id }}</strong></td>
                        <td class="tk-col-staff">
                            <div class="hr-emp-avatar-wrap">
                                @if($emp->photo_url)
                                    <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="hr-avatar-img-sm">
                                @else
                                    <div class="hr-avatar-circle-sm">
                                        {{ $emp->initials }}
                                    </div>
                                @endif
                                <div style="min-width: 0;">
                                    <strong style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;" title="{{ $emp->full_name }}">{{ $emp->full_name }}</strong>
                                    <small style="color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">{{ $emp->position?->name }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="tk-col-dept">
                            <div style="font-weight: 600; color: #1e293b; font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 3px;" title="{{ $emp->branch?->name }}">
                                <i class="ph ph-storefront" style="color: #7c3aed; font-size: 12px;"></i> {{ $emp->branch?->name }}
                            </div>
                            @php
                                $tkDeptName = $emp->department?->name ?? 'Front of House';
                                $deptUpper = strtoupper(trim($tkDeptName));
                                $pillClass = 'hr-dept-pill-neutral';
                                $pillIcon = 'ph-buildings';
                                if (str_contains($deptUpper, 'HR') || str_contains($deptUpper, 'HUMAN')) {
                                    $pillClass = 'hr-dept-pill-hr';
                                    $pillIcon = 'ph-users';
                                } elseif (str_contains($deptUpper, 'IT') || str_contains($deptUpper, 'TECH')) {
                                    $pillClass = 'hr-dept-pill-it';
                                    $pillIcon = 'ph-cpu';
                                } elseif (str_contains($deptUpper, 'FINANCE') || str_contains($deptUpper, 'FIN') || str_contains($deptUpper, 'ADMIN')) {
                                    $pillClass = 'hr-dept-pill-fin';
                                    $pillIcon = 'ph-coins';
                                } elseif (str_contains($deptUpper, 'FRONT') || str_contains($deptUpper, 'FOH')) {
                                    $pillClass = 'hr-dept-pill-foh';
                                    $pillIcon = 'ph-storefront';
                                } elseif (str_contains($deptUpper, 'BACK') || str_contains($deptUpper, 'BOH') || str_contains($deptUpper, 'KITCHEN')) {
                                    $pillClass = 'hr-dept-pill-boh';
                                    $pillIcon = 'ph-cooking-pot';
                                } elseif (str_contains($deptUpper, 'MANAGE')) {
                                    $pillClass = 'hr-dept-pill-mgmt';
                                    $pillIcon = 'ph-briefcase';
                                }
                            @endphp
                            <span class="hr-dept-pill {{ $pillClass }}" style="font-size: 9.5px; padding: 1.5px 7px;" title="{{ $tkDeptName }}">
                                <i class="ph {{ $pillIcon }}"></i>
                                <span>{{ $tkDeptName }}</span>
                            </span>
                        </td>

                        <!-- 1. In -->
                        <td class="tk-col-punch-td">
                            @if($pIn)
                                <strong style="color: #059669;">{{ substr($pIn, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 2. Break Out -->
                        <td class="tk-col-punch-td">
                            @if($pBreakOut)
                                <span>{{ substr($pBreakOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 3. Break In -->
                        <td class="tk-col-punch-td">
                            @if($pBreakIn)
                                <span>{{ substr($pBreakIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 4. Coffee Break Out -->
                        <td class="tk-col-punch-td">
                            @if($pCoffeeOut)
                                <span style="color: #b45309;">{{ substr($pCoffeeOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 5. Coffee Break In -->
                        <td class="tk-col-punch-td">
                            @if($pCoffeeIn)
                                <span style="color: #b45309;">{{ substr($pCoffeeIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- 6. Final Out -->
                        <td class="tk-col-punch-td">
                            @if($pFinalOut)
                                <strong style="color: #0284c7;">{{ substr($pFinalOut, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>

                        <!-- Total Hours -->
                        <td class="tk-col-hours">
                            <strong>{{ number_format($att?->total_hours ?? 0, 2) }} hrs</strong>
                        </td>

                        <!-- Status Badge -->
                        <td class="tk-col-status">
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

                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align: center; color: #94a3b8; padding: 30px;">No employees active for this date.</td></tr>
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
@endsection
