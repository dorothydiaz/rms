@extends('layouts.app')

@section('title', 'Daily Time Record (DTR) - Attendance Management')

@push('styles')
    @vite('resources/css/modules/hr-dtr.css')
@endpush

@push('blade-data')
<script>
    window.HrDtrConfig = Object.freeze({
        routes: {
            tags: @json(route('hr.attendance.dtr.tags')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-dtr.js')
@endpush

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
@endsection
