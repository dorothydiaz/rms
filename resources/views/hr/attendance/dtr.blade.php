@extends('layouts.app')

@section('title', 'Daily Time Record (DTR) - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance">
    <x-slot:actions>
        <button type="button" class="hr-btn hr-btn-primary" onclick="openDtrExportModal()" id="btnOpenExportDtr" style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); border: none; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);">
            <i class="ph ph-file-pdf" style="font-size: 18px;"></i>
            <span>Export DTR</span>
        </button>
        <a href="{{ route('hr.reports.export.attendance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hr-btn hr-btn-secondary" title="Export as CSV spreadsheet">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
    </x-slot:actions>
</x-hr-tabs>

<!-- DTR Summary KPI Cards -->
<div class="hr-metrics-grid" style="margin-bottom: 20px;">
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ number_format($totals['hours'], 2) }}</span>
            <span class="hr-metric-label">Total Rendered Hours</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-clock"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #f59e0b;">{{ number_format($totals['late_min']) }} min</span>
            <span class="hr-metric-label">Accumulated Tardiness</span>
        </div>
        <div class="hr-metric-icon amber"><i class="ph ph-timer"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #6366f1;">{{ number_format($totals['ot_hours'], 2) }} hrs</span>
            <span class="hr-metric-label">Overtime Hours</span>
        </div>
        <div class="hr-metric-icon indigo"><i class="ph ph-trend-up"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #9333ea;">{{ number_format($totals['nd_hours'], 2) }} hrs</span>
            <span class="hr-metric-label">Night Differential (10PM-6AM)</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-moon-stars"></i></div>
    </div>
</div>

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.attendance.dtr') }}" class="hr-filter-form">
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">From:</label>
            <input type="date" name="start_date" class="hr-input" value="{{ $startDate }}">
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">To:</label>
            <input type="date" name="end_date" class="hr-input" value="{{ $endDate }}">
        </div>

        @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
            <select name="branch_id" class="hr-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif

        <select name="employee_id" class="hr-select">
            <option value="">All Employees</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>
            @endforeach
        </select>

        <select name="status" class="hr-select">
            <option value="">All Statuses</option>
            @foreach(['Present', 'Late', 'Half Day', 'Absent', 'Rest Day', 'On Leave', 'Holiday'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-magnifying-glass"></i>
            <span>Filter</span>
        </button>
    </form>
</div>

<!-- DTR Grid Table -->
<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Total</th>
                    <th>Late</th>
                    <th>Under</th>
                    <th>OT</th>
                    <th>Night Diff</th>
                    <th>Type</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</strong><br>
                            <small style="color: #64748b;">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</small>
                        </td>
                        <td>
                            <strong>{{ $r->employee?->full_name }}</strong><br>
                            <small style="color: #9333ea; font-family: monospace;">{{ $r->employee?->employee_id }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $r->employee?->branch?->name }}</span></td>
                        <td>{{ $r->time_in ? substr($r->time_in, 0, 5) : '--:--' }}</td>
                        <td>{{ $r->time_out ? substr($r->time_out, 0, 5) : '--:--' }}</td>
                        <td><strong>{{ number_format($r->total_hours, 2) }} hrs</strong></td>
                        <td>
                            @if($r->late_minutes > 0)
                                <span style="color: #f59e0b; font-weight: 600;">{{ $r->late_minutes }}m</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->undertime_minutes > 0)
                                <span style="color: #ef4444; font-weight: 600;">{{ $r->undertime_minutes }}m</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->overtime_hours > 0)
                                <span style="color: #6366f1; font-weight: 700;">+{{ number_format($r->overtime_hours, 2) }}</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->night_diff_hours > 0)
                                <span style="color: #9333ea; font-weight: 700;">{{ number_format($r->night_diff_hours, 2) }}h</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->is_rest_day)
                                <span class="hr-badge hr-badge-neutral">Rest Day</span>
                            @elseif($r->holiday_type !== 'None')
                                <span class="hr-badge hr-badge-purple">{{ $r->holiday_type }}</span>
                            @else
                                <span style="font-size: 11px; color: #64748b;">Regular</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($r->status === 'Present')
                                <span class="hr-badge hr-badge-success">{{ $r->status }}</span>
                            @elseif($r->status === 'Late')
                                <span class="hr-badge hr-badge-warning">{{ $r->status }}</span>
                            @elseif($r->status === 'Rest Day')
                                <span class="hr-badge hr-badge-neutral">{{ $r->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-danger">{{ $r->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="text-align: center; color: #94a3b8; padding: 30px;">No attendance records found for this date range.</td></tr>
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

<!-- ========================================================
     DTR EXPORT MODAL & DYNAMIC TAG FILTERING
     Minimal Monochrome & White Glassmorphism UI
     ======================================================== -->
<div id="dtrExportModal" class="hr-modal-overlay">
    <div class="hr-modal dtr-export-modal-dialog">
        <div class="hr-modal-header">
            <span class="hr-modal-title" style="color: #5b21b6;">
                <i class="ph ph-file-pdf" style="color: #7c3aed; font-size: 20px;"></i>
                Export Daily Time Record (DTR)
            </span>
            <button type="button" class="icon-btn" onclick="closeDtrExportModal()" title="Close dialog">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <form action="{{ route('hr.attendance.dtr.export-pdf') }}" method="POST" id="dtrExportForm" target="_blank">
            @csrf
            <div class="hr-modal-body" style="padding: 20px 24px;">
                <!-- DTR Period Selection -->
                <div>
                    <div class="dtr-section-label">
                        <span>DTR Cut-Off Period</span>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 2px 8px; height: auto;" onclick="setCutoffPreset('first_half')">1st – 15th</button>
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 2px 8px; height: auto;" onclick="setCutoffPreset('second_half')">16th – End</button>
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 2px 8px; height: auto;" onclick="setCutoffPreset('full_month')">Full Month</button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Start Date</label>
                            <input type="date" name="start_date" id="dtrExportStartDate" class="hr-input" value="{{ $startDate }}" required>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">End Date</label>
                            <input type="date" name="end_date" id="dtrExportEndDate" class="hr-input" value="{{ $endDate }}" required>
                        </div>
                    </div>
                </div>

                <!-- Export Scope -->
                <div style="margin-top: 10px;">
                    <div class="dtr-section-label">
                        <span>1. Export Scope</span>
                    </div>
                    <div class="dtr-scope-grid">
                        <div class="dtr-scope-card active" id="scopeCardAll" onclick="selectDtrScope('all')">
                            <input type="radio" name="scope" value="all" id="scopeRadioAll" class="dtr-scope-radio" checked>
                            <div>
                                <div class="dtr-scope-title">All Active Employees</div>
                                <div class="dtr-scope-subtitle">Generate DTRs for all currently active employees</div>
                            </div>
                        </div>
                        <div class="dtr-scope-card" id="scopeCardFiltered" onclick="selectDtrScope('filtered')">
                            <input type="radio" name="scope" value="filtered" id="scopeRadioFiltered" class="dtr-scope-radio">
                            <div>
                                <div class="dtr-scope-title">Filtered Employees</div>
                                <div class="dtr-scope-subtitle">Search & select dynamic employee attributes as tags</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Employee Tag Filter (Shown when Filtered Employees selected) -->
                <div id="dtrTagFilterSection" style="display: none;">
                    <div class="dtr-section-label">
                        <span>2. Dynamic Employee Tag Filter</span>
                        <button type="button" class="dtr-clear-btn" id="dtrClearAllBtn" onclick="clearAllDtrTags()" style="display: none;">Clear All</button>
                    </div>

                    <!-- Searchable Tag Input Box -->
                    <div class="dtr-tag-search-container" id="dtrTagSearchContainer" onclick="focusDtrTagSearch(event)">
                        <div id="dtrTagPillsWrapper" style="display: inline-flex; flex-wrap: wrap; gap: 6px;"></div>
                        <input type="text" id="dtrTagSearchInput" class="dtr-tag-input-field" placeholder="🔍 Search employee attributes (status, branch, position, agency, name)..." autocomplete="off">
                        
                        <!-- Dynamic Suggestions Dropdown -->
                        <div id="dtrTagDropdown" class="dtr-tag-dropdown"></div>
                    </div>

                    <!-- Filter Logic Hint -->
                    <div class="dtr-logic-hint">
                        <i class="ph ph-info" style="font-size: 13px; color: #7c3aed;"></i>
                        <span>Logic: Tags across categories use <strong>AND</strong> logic (e.g. <code>Regular + Agency + Makati Branch</code>).</span>
                    </div>

                    <!-- Dynamic Matching Employee Count -->
                    <div class="dtr-filter-meta-bar">
                        <div class="dtr-match-badge" id="dtrMatchBadge">
                            <i class="ph ph-users"></i>
                            <span id="dtrMatchCountText">Calculating matching employees...</span>
                        </div>
                        <small style="color: #64748b;" id="dtrTagSummaryText">0 tags applied</small>
                    </div>

                    <!-- Hidden JSON field for submitted tags -->
                    <input type="hidden" name="tags" id="dtrSelectedTagsInput" value="[]">
                </div>

                <!-- DTR Output Format Options -->
                <div class="dtr-output-box">
                    <div class="dtr-section-label" style="margin-bottom: 4px;">
                        <span>3. Export Format & Output</span>
                        <span class="dtr-format-badge">PDF</span>
                    </div>
                    <div class="dtr-output-row">
                        <label class="dtr-output-option" title="Primary format: Generates individual DTR PDF per employee">
                            <input type="radio" name="output_format" value="individual" checked>
                            <span>
                                <strong>Individual PDF per employee</strong>
                                <span style="display: block; font-size: 11px; color: #64748b;">(Packaged as ZIP archive for multiple employees)</span>
                            </span>
                        </label>
                        <label class="dtr-output-option" title="Combines all employees into a single multi-page PDF document">
                            <input type="radio" name="output_format" value="combined">
                            <span>
                                <strong>Combined PDF</strong>
                                <span style="display: block; font-size: 11px; color: #64748b;">(Single PDF file containing all employees)</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeDtrExportModal()">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" id="btnSubmitDtrExport" style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); border: none; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);">
                    <i class="ph ph-file-arrow-down" style="font-size: 16px;"></i>
                    <span id="btnSubmitDtrExportText">Generate DTR PDF</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let dtrAvailableTags = [];
let dtrEmployeesData = [];
let dtrSelectedTags = [];
let dtrTagsLoaded = false;
let currentDtrScope = 'all';

function openDtrExportModal() {
    const modal = document.getElementById('dtrExportModal');
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    if (!dtrTagsLoaded) {
        loadDtrTags();
    } else {
        updateDtrMatchCount();
    }
}

function closeDtrExportModal() {
    const modal = document.getElementById('dtrExportModal');
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
    const dropdown = document.getElementById('dtrTagDropdown');
    if (dropdown) dropdown.style.display = 'none';
}

function selectDtrScope(scope) {
    currentDtrScope = scope;
    const cardAll = document.getElementById('scopeCardAll');
    const cardFiltered = document.getElementById('scopeCardFiltered');
    const radioAll = document.getElementById('scopeRadioAll');
    const radioFiltered = document.getElementById('scopeRadioFiltered');
    const filterSection = document.getElementById('dtrTagFilterSection');

    if (scope === 'all') {
        cardAll.classList.add('active');
        cardFiltered.classList.remove('active');
        radioAll.checked = true;
        if (filterSection) filterSection.style.display = 'none';
    } else {
        cardFiltered.classList.add('active');
        cardAll.classList.remove('active');
        radioFiltered.checked = true;
        if (filterSection) filterSection.style.display = 'block';
        setTimeout(() => {
            const input = document.getElementById('dtrTagSearchInput');
            if (input) input.focus();
        }, 50);
    }
    updateDtrMatchCount();
}

function focusDtrTagSearch(e) {
    if (e.target.id !== 'dtrTagSearchInput' && !e.target.classList.contains('dtr-tag-remove')) {
        const input = document.getElementById('dtrTagSearchInput');
        if (input) input.focus();
    }
}

function setCutoffPreset(type) {
    const startInput = document.getElementById('dtrExportStartDate');
    const endInput = document.getElementById('dtrExportEndDate');
    if (!startInput || !endInput) return;

    let base = new Date(startInput.value || new Date());
    if (isNaN(base.getTime())) base = new Date();
    const year = base.getFullYear();
    const month = base.getMonth(); // 0-indexed

    const pad = (n) => String(n).padStart(2, '0');
    const monthStr = pad(month + 1);

    if (type === 'first_half') {
        startInput.value = `${year}-${monthStr}-01`;
        endInput.value = `${year}-${monthStr}-15`;
    } else if (type === 'second_half') {
        const lastDay = new Date(year, month + 1, 0).getDate();
        startInput.value = `${year}-${monthStr}-16`;
        endInput.value = `${year}-${monthStr}-${pad(lastDay)}`;
    } else if (type === 'full_month') {
        const lastDay = new Date(year, month + 1, 0).getDate();
        startInput.value = `${year}-${monthStr}-01`;
        endInput.value = `${year}-${monthStr}-${pad(lastDay)}`;
    }
}

function loadDtrTags() {
    const matchText = document.getElementById('dtrMatchCountText');
    if (matchText) matchText.textContent = 'Loading employee data & tags...';

    fetch('{{ route('hr.attendance.dtr.tags') }}')
        .then(res => res.json())
        .then(data => {
            dtrAvailableTags = data.tags || [];
            dtrEmployeesData = data.employees || [];
            dtrTagsLoaded = true;
            updateDtrMatchCount();
        })
        .catch(err => {
            console.error('Failed to load DTR tags:', err);
            if (matchText) matchText.textContent = 'Failed to load dynamic tags';
        });
}

// Tag search autocomplete
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dtrTagSearchInput');
    const dropdown = document.getElementById('dtrTagDropdown');

    if (searchInput && dropdown) {
        searchInput.addEventListener('input', function() {
            renderTagDropdown(this.value);
        });

        searchInput.addEventListener('focus', function() {
            renderTagDropdown(this.value);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && dtrSelectedTags.length > 0) {
                removeDtrTag(dtrSelectedTags.length - 1);
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('dtrTagSearchContainer');
        const dropdown = document.getElementById('dtrTagDropdown');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

function renderTagDropdown(query) {
    const dropdown = document.getElementById('dtrTagDropdown');
    if (!dropdown) return;

    const q = (query || '').trim().toLowerCase();
    
    // Filter tags not already selected
    const unselectedTags = dtrAvailableTags.filter(t => {
        return !dtrSelectedTags.some(sel => sel.type === t.type && String(sel.value).toLowerCase() === String(t.value).toLowerCase());
    });

    let matched = unselectedTags;
    if (q) {
        matched = unselectedTags.filter(t => {
            const label = (t.label || '').toLowerCase();
            const cat = (t.category || '').toLowerCase();
            const sub = (t.subtext || '').toLowerCase();
            return label.includes(q) || cat.includes(q) || sub.includes(q);
        });
    }

    if (matched.length === 0) {
        dropdown.innerHTML = `<div style="padding: 12px; color: #94a3b8; font-size: 12px; text-align: center;">No matching employee attributes found</div>`;
        dropdown.style.display = 'block';
        return;
    }

    // Limit to top 25 matches for speed
    const list = matched.slice(0, 25);
    let html = '';
    list.forEach((t, i) => {
        const safeLabel = escapeHtml(t.label);
        const safeCat = escapeHtml(t.category);
        html += `
            <div class="dtr-tag-item" onclick="addDtrTag('${escapeJs(t.category)}', '${escapeJs(t.type)}', '${escapeJs(t.label)}', '${escapeJs(t.value)}')">
                <span style="font-weight: 500;">${safeLabel}</span>
                <span class="dtr-tag-item-cat">${safeCat}</span>
            </div>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
}

function addDtrTag(category, type, label, value) {
    dtrSelectedTags.push({ category, type, label, value });
    const searchInput = document.getElementById('dtrTagSearchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    const dropdown = document.getElementById('dtrTagDropdown');
    if (dropdown) dropdown.style.display = 'none';

    renderDtrTagPills();
    updateDtrMatchCount();
}

function removeDtrTag(index) {
    if (index >= 0 && index < dtrSelectedTags.length) {
        dtrSelectedTags.splice(index, 1);
        renderDtrTagPills();
        updateDtrMatchCount();
    }
}

function clearAllDtrTags() {
    dtrSelectedTags = [];
    renderDtrTagPills();
    updateDtrMatchCount();
    const searchInput = document.getElementById('dtrTagSearchInput');
    if (searchInput) searchInput.focus();
}

function renderDtrTagPills() {
    const wrapper = document.getElementById('dtrTagPillsWrapper');
    const clearBtn = document.getElementById('dtrClearAllBtn');
    const summaryText = document.getElementById('dtrTagSummaryText');
    const hiddenInput = document.getElementById('dtrSelectedTagsInput');

    if (!wrapper) return;

    if (dtrSelectedTags.length === 0) {
        wrapper.innerHTML = '';
        if (clearBtn) clearBtn.style.display = 'none';
        if (summaryText) summaryText.textContent = '0 tags applied';
        if (hiddenInput) hiddenInput.value = '[]';
        return;
    }

    if (clearBtn) clearBtn.style.display = 'inline-block';
    if (summaryText) summaryText.textContent = `${dtrSelectedTags.length} ${dtrSelectedTags.length === 1 ? 'tag' : 'tags'} applied`;
    if (hiddenInput) hiddenInput.value = JSON.stringify(dtrSelectedTags);

    let html = '';
    dtrSelectedTags.forEach((t, idx) => {
        html += `
            <span class="dtr-tag-pill">
                <span class="dtr-tag-cat">${escapeHtml(t.category)}</span>
                <span>${escapeHtml(t.label)}</span>
                <span class="dtr-tag-remove" onclick="removeDtrTag(${idx})" title="Remove tag">&times;</span>
            </span>
        `;
    });
    wrapper.innerHTML = html;
}

function updateDtrMatchCount() {
    const badge = document.getElementById('dtrMatchBadge');
    const countText = document.getElementById('dtrMatchCountText');
    const submitBtn = document.getElementById('btnSubmitDtrExport');
    const submitBtnText = document.getElementById('btnSubmitDtrExportText');

    if (!countText) return;

    if (currentDtrScope === 'all') {
        const total = dtrEmployeesData.length;
        countText.innerHTML = `<strong>${total}</strong> active employees selected`;
        if (submitBtn) submitBtn.disabled = total === 0;
        if (submitBtnText) submitBtnText.textContent = total > 0 ? `Generate DTR PDF (${total})` : 'Generate DTR PDF';
        return;
    }

    // Filtered mode
    if (dtrSelectedTags.length === 0) {
        countText.innerHTML = `<span style="color: #64748b;">No filter tags applied — add tags above to filter employees</span>`;
        if (submitBtn) submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Generate DTR PDF';
        return;
    }

    // Group selected tags by type for intuitive faceted matching
    const grouped = {};
    dtrSelectedTags.forEach(t => {
        if (!grouped[t.type]) grouped[t.type] = [];
        grouped[t.type].push(String(t.value).toLowerCase());
    });

    let matchCount = 0;
    dtrEmployeesData.forEach(emp => {
        let employeeMatches = true;

        for (const type in grouped) {
            const values = grouped[type];
            let typeMatches = false;

            if (type === 'status') {
                const empStatuses = (emp.statuses || []).map(s => String(s).toLowerCase());
                typeMatches = values.some(v => empStatuses.includes(v));
            } else if (type === 'source') {
                const empSrc = String(emp.source || 'Company').toLowerCase();
                typeMatches = values.includes(empSrc);
            } else if (type === 'company_agency') {
                const empCos = (emp.companies || []).map(c => String(c).toLowerCase());
                typeMatches = values.some(v => empCos.includes(v));
            } else if (type === 'branch') {
                const bId = String(emp.branch_id || '').toLowerCase();
                const bName = String(emp.branch_name || '').toLowerCase();
                typeMatches = values.some(v => v === bId || v === bName);
            } else if (type === 'department') {
                const dId = String(emp.department_id || '').toLowerCase();
                const dName = String(emp.department_name || '').toLowerCase();
                typeMatches = values.some(v => v === dId || v === dName);
            } else if (type === 'position') {
                const pId = String(emp.position_id || '').toLowerCase();
                const pName = String(emp.position_name || '').toLowerCase();
                typeMatches = values.some(v => v === pId || v === pName);
            } else if (type === 'employee') {
                const eId = String(emp.id || '').toLowerCase();
                const empCode = String(emp.employee_id || '').toLowerCase();
                typeMatches = values.some(v => v === eId || v === empCode);
            } else {
                typeMatches = true;
            }

            if (!typeMatches) {
                employeeMatches = false;
                break; // AND across different categories
            }
        }

        if (employeeMatches) {
            matchCount++;
        }
    });

    if (matchCount === 0) {
        countText.innerHTML = `<span style="color: #ef4444; font-weight: 600;">0 employees match your filters</span>`;
        if (submitBtn) submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Generate DTR PDF (0)';
    } else {
        const noun = matchCount === 1 ? 'employee matches' : 'employees match';
        countText.innerHTML = `<strong>${matchCount}</strong> ${noun} your filters`;
        if (submitBtn) submitBtn.disabled = false;
        if (submitBtnText) submitBtnText.textContent = `Generate DTR PDF (${matchCount})`;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>
@endsection
