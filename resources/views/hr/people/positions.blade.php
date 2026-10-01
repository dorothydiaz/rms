@extends('layouts.app')

@section('title', 'Positions - Organization Management')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addPosModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Position</span>
        </button>
</x-hr-tabs>

<style>
.hr-badge-staff-link {
    text-decoration: none !important;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s ease;
}
.hr-badge-staff-link:hover {
    background: #7c3aed !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35);
    transform: translateY(-1px);
}
</style>

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Position Title</th>
                    <th>Department</th>
                    <th>Assigned Employees</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody id="posTableBody">
                @forelse($positions as $pos)
                    <tr class="pos-row"
                        data-code="{{ strtolower($pos->code) }}"
                        data-name="{{ strtolower($pos->name) }}"
                        data-dept="{{ strtolower($pos->department?->name ?? '') }}">
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $pos->code }}</strong></td>
                        <td><strong>{{ $pos->name }}</strong></td>
                        <td>{{ $pos->department?->name ?? 'General' }}</td>
                        <td>
                            <a href="{{ route('hr.people.employees', ['position_id' => $pos->id]) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="hr-badge hr-badge-purple hr-badge-staff-link"
                               title="Click to view staff members with position {{ $pos->name }} in a new window">
                                <i class="ph ph-users"></i>
                                <span>{{ $pos->employees_count }} {{ Str::plural('employee', $pos->employees_count) }}</span>
                                <i class="ph ph-arrow-square-out" style="font-size: 11px; opacity: 0.75;"></i>
                            </a>
                        </td>
                        <td style="text-align: right;"><span class="hr-badge hr-badge-success">Active</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No positions created.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="posPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="posPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="posPerPageSelect" onchange="posChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="posPageNav"></nav>
    </div>
</div>

<!-- Modal: Add Position -->
<div id="addPosModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Position</span>
            <button class="icon-btn" onclick="closeModal('addPosModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.positions.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Title *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Line Cook">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. LC-01">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Department *</label>
                    <select name="department_id" class="hr-select" required>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Job Description</label>
                    <textarea name="description" class="hr-input" rows="3" placeholder="Key responsibilities..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addPosModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Position</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Positions
// =========================================================================
let _posPage = 1, _posRows = [];
function posGetPerPage() { return parseInt(document.getElementById('posPerPageSelect')?.value || '15', 10); }
function posChangePerPage() { _posPage = 1; renderPosPage(); }
function posGoToPage(p) { _posPage = p; renderPosPage(); const w = document.getElementById('posTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderPosPage() {
    const pp = posGetPerPage(), total = _posRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_posPage > totalPages) _posPage = totalPages;
    const start = (_posPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#posTableBody tr.pos-row').forEach(r => r.style.display = 'none');
    _posRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('posPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('posPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> positions`;
    const nav = document.getElementById('posPageNav');
    if (!nav) return;
    let h = _posPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="posGoToPage(${_posPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _posPage-2), pe = Math.min(totalPages, _posPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="posGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _posPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="posGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="posGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _posPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="posGoToPage(${_posPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshPosPage = function() {
    _posRows = Array.from(document.querySelectorAll('#posTableBody tr.pos-row'));
    renderPosPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshPosPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshPosPage();
});
</script>
@endpush
@endsection
