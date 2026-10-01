@extends('layouts.app')

@section('title', 'Overtime Records - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance" />

<!-- Filter & Search Toolbar -->
<div class="hr-filter-bar" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
        <!-- Real-Time Text Search -->
        <div style="position: relative; width: 260px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
            <input type="text" id="otSearchInput" class="hr-input" placeholder="Search staff, position, branch…" oninput="filterOvertimeTable()" style="width: 100%; box-sizing: border-box; padding-left: 36px; height: 38px;">
        </div>

        <!-- Date Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" id="otDateFilter" class="hr-input" value="{{ request('date') }}" onchange="applyOtDateFilter(this.value)" style="height: 38px; width: 160px;" title="Filter by date">
            @if(request('date'))
                <a href="{{ route('hr.attendance.overtime') }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px;" title="Clear date filter">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </div>

        <!-- Branch Filter -->
        @if(isset($branches) && $branches->isNotEmpty())
            <select id="otBranchFilter" class="hr-select" style="height: 38px; max-width: 180px;" onchange="filterOvertimeTable()">
                <option value="">-- All Branches --</option>
                @foreach($branches as $b)
                    <option value="{{ $b->name }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; padding: 6px 12px;">
            Showing <strong id="otVisibleCount" style="color: #0f172a; margin: 0 3px;">{{ $records->count() }}</strong> of {{ $records->total() }} records
        </span>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table" id="overtimeTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff Name</th>
                    <th>Branch & Dept</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Overtime Rendered</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody id="overtimeTableBody">
                @forelse($records as $rec)
                    @php
                        $staffName = $rec->employee?->full_name ?? 'Staff';
                        $branchName = $rec->employee?->branch?->name ?? 'Unassigned';
                        $deptName = $rec->employee?->department?->name ?? '';
                        $posName = $rec->employee?->position?->name ?? '';
                        $dateStr = \Carbon\Carbon::parse($rec->date)->format('Y-m-d');
                    @endphp
                    <tr class="overtime-row"
                        data-search="{{ strtolower($staffName . ' ' . $branchName . ' ' . $deptName . ' ' . $posName) }}"
                        data-branch="{{ $branchName }}"
                        data-date="{{ $dateStr }}">
                        <td><strong>{{ \Carbon\Carbon::parse($rec->date)->format('M d, Y') }}</strong></td>
                        <td>
                            <strong>{{ $staffName }}</strong><br>
                            <small style="color: #64748b;">{{ $posName }}</small>
                        </td>
                        <td>
                            <div>{{ $branchName }}</div>
                            <small style="color: #64748b;">{{ $deptName }}</small>
                        </td>
                        <td>{{ substr($rec->time_in, 0, 5) }}</td>
                        <td>{{ substr($rec->time_out, 0, 5) }}</td>
                        <td>{{ number_format($rec->total_hours, 2) }} hrs</td>
                        <td>
                            <strong style="color: #6366f1; font-size: 14px;">+{{ number_format($rec->overtime_hours, 2) }} hrs</strong>
                        </td>
                        <td style="text-align: right;">
                            <span class="hr-badge hr-badge-success">Approved / Recorded</span>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyOtRow"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">No overtime hours logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->total() > 0)
        <div class="hr-table-footer">
            {{ $records->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function filterOvertimeTable() {
    const q = (document.getElementById('otSearchInput')?.value || '').toLowerCase().trim();
    const branch = (document.getElementById('otBranchFilter')?.value || '').trim();
    const rows = document.querySelectorAll('.overtime-row');
    let visible = 0;

    rows.forEach(r => {
        const search = r.getAttribute('data-search') || '';
        const rBranch = r.getAttribute('data-branch') || '';
        const matchQ = !q || search.includes(q);
        const matchB = !branch || rBranch === branch;

        if (matchQ && matchB) {
            r.style.display = '';
            visible++;
        } else {
            r.style.display = 'none';
        }
    });

    const badge = document.getElementById('otVisibleCount');
    if (badge) badge.innerText = visible;

    const empty = document.getElementById('emptyOtRow');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyOtDateFilter(val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set('date', val);
    } else {
        url.searchParams.delete('date');
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
