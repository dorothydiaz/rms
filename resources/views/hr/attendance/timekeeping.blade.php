@extends('layouts.app')

@section('title', 'Timekeeping Station - Attendance Management')

@push('styles')
<style>
/* Responsive Timekeeping Station Matrix */
.hr-timekeeping-table {
    width: 100% !important;
    min-width: 960px;
    table-layout: auto;
    border-collapse: separate;
    border-spacing: 0;
}

.hr-timekeeping-table th,
.hr-timekeeping-table td {
    padding: 10px 8px !important;
    font-size: 12.5px;
    vertical-align: middle;
}

.hr-timekeeping-table .tk-col-id {
    width: 95px;
    font-family: monospace;
    font-size: 11.5px;
    white-space: nowrap;
}

.hr-timekeeping-table .tk-col-staff {
    min-width: 140px;
    max-width: 190px;
}

.hr-timekeeping-table .tk-col-dept {
    min-width: 120px;
    max-width: 160px;
}

.hr-timekeeping-table .tk-col-punch-th {
    text-align: center !important;
    padding: 10px 4px !important;
    font-size: 11px !important;
    color: #475569;
    font-weight: 700;
    white-space: nowrap;
    width: 58px;
}

.hr-timekeeping-table .tk-col-punch-td {
    text-align: center !important;
    padding: 10px 4px !important;
    white-space: nowrap;
    font-size: 12px;
}

.hr-timekeeping-table .tk-col-hours {
    text-align: center !important;
    white-space: nowrap;
    width: 78px;
    font-size: 12px;
}

.hr-timekeeping-table .tk-col-status {
    text-align: center !important;
    white-space: nowrap;
    width: 82px;
}


@media (max-width: 1200px) {
    .hr-timekeeping-table {
        min-width: 900px;
    }
    .hr-timekeeping-table th,
    .hr-timekeeping-table td {
        padding: 9px 5px !important;
        font-size: 12px;
    }
}
</style>
@endpush

@section('content')
<x-hr-tabs parent="time-attendance" />

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 12px 18px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="hr-table-title"><i class="ph ph-calendar-blank"></i> Staff Time Logs</span>
                <span class="hr-badge hr-badge-neutral" id="tkStaffCountBadge">{{ count($employees) }} Staff</span>
            </div>

            <!-- Date Filter Form -->
            <form method="GET" action="{{ route('hr.attendance.timekeeping') }}" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; gap: 3px; align-items: center;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 34px; padding: 0 9px;" title="Previous Day">
                        <i class="ph ph-caret-left"></i>
                    </a>
                    <input type="date" name="date" class="hr-input" value="{{ $date }}" onchange="this.form.submit()" style="height: 34px; padding: 4px 8px; font-size: 12.5px;">
                    <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 34px; padding: 0 9px;" title="Next Day">
                        <i class="ph ph-caret-right"></i>
                    </a>
                    @if($date !== \Carbon\Carbon::today()->toDateString())
                        <a href="{{ route('hr.attendance.timekeeping', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="hr-btn hr-btn-secondary" style="height: 34px; padding: 0 9px; font-size: 11.5px; font-weight: 600;" title="Jump to Today">
                            Today
                        </a>
                    @endif
                </div>

                @if(isset($branches) && $branches->count() > 1)
                    <select name="branch_id" class="hr-select" onchange="this.form.submit()" style="height: 34px; font-size: 12px; padding: 4px 10px;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
        </div>
        <div style="position: relative; min-width: 240px; margin-left: auto;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
            <input type="text" id="tkSearchInput" placeholder="Search staff name, ID, branch..." oninput="filterTimekeepingRows()" style="width: 100%; font-size: 12.5px; padding: 6px 28px 6px 30px; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none; height: 34px; box-sizing: border-box;">
            <button type="button" id="tkClearSearchBtn" onclick="clearTkSearch()" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: none; font-size: 16px; line-height: 1;">&times;</button>
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
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $emp->branch?->name }}">{{ $emp->branch?->name }}</div>
                            <small style="color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;" title="{{ $emp->department?->name }}">{{ $emp->department?->name }}</small>
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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

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


document.addEventListener('DOMContentLoaded', () => {
    filterTimekeepingRows();
});
</script>
@endpush
@endsection
