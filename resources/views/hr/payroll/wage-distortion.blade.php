@extends('layouts.app')

@section('title', 'Wage Distortion Correction Engine - Payroll & Compliance')

@push('styles')
    @vite('resources/css/modules/hr-wage-distortion.css')
@endpush

@push('blade-data')
<script>
    window.HrWageDistortionConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
        allEmployees: @json($employeesData),
        formulasConfig: @json($formulas),
        defaultDivisor: {{ $defaultDivisor }},
        routes: {
            apply: @json(route('hr.payroll.wage-distortion.apply')),
            export: @json(route('hr.payroll.wage-distortion.export')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-wage-distortion.js')
@endpush

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="exportConversionCSV()" title="Export conversion calculations to Excel / CSV">
                <i class="ph ph-file-csv" style="font-size: 16px; color: #059669;"></i>
                <span>Export CSV</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="window.print()" title="Print DOLE wage distortion compliance report">
                <i class="ph ph-printer" style="font-size: 16px; color: #2563eb;"></i>
                <span>Print / Compliance PDF</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="resetAllConverter()" title="Reset all parameters and selections">
                <i class="ph ph-arrow-counter-clockwise" style="font-size: 16px; color: #dc2626;"></i>
                <span>Reset</span>
            </button>
            <button type="button" class="hr-btn hr-btn-primary" onclick="openApplyAdjustmentModal()" id="btnOpenApplyModal" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                <i class="ph ph-check-circle" style="font-size: 16px;"></i>
                <span>Apply Adjustments (<span id="btnApplyCount">0</span>)</span>
            </button>
        </div>
    </x-slot:actions>
</x-hr-tabs>

<!-- Main Container -->
<div class="wage-distortion-container" id="wageDistortionApp">

    <!-- TOP ALERT / COMPLIANCE BANNER -->
    <div class="wd-banner">
        <div class="wd-banner-left">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 22px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);">
                <i class="ph ph-scales"></i>
            </div>
            <div>
                <div style="font-size: 15px; font-weight: 700; color: #1e1b4b; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span>DOLE Statutory Wage Distortion Converter</span>
                    <span class="hr-badge hr-badge-purple" style="font-size: 11px;">NWPC Guidelines</span>
                    <span class="hr-badge hr-badge-success" style="font-size: 11px;"><i class="ph ph-check-circle"></i> TRAIN Law Compliant</span>
                </div>
                <div style="font-size: 12.5px; color: #475569; margin-top: 3px; line-height: 1.5;">
                    Eliminate salary compression caused by regional minimum wage orders. Select affected staff, configure authorized mathematical formulas (Pineda, Bagtas, PCS, JODA, WireRope, etc.), inspect real-time adjustments, and apply audited updates directly to 201 records.
                </div>
            </div>
        </div>
        <div class="wd-banner-right">
            <div style="text-align: right; border-right: 1px solid rgba(124, 58, 237, 0.2); padding-right: 14px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">Workforce Scope</div>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a;"><span id="scopeSelectedCount">0</span> / {{ count($employeesData) }} Employees</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">Monthly Divisor</div>
                <div style="font-size: 13px; font-weight: 600; color: #7c3aed;">
                    <select id="settingMonthlyDivisor" onchange="onDivisorChange(this.value)" style="border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; padding: 2px 6px; background: #ffffff; color: #1e293b; font-weight: 600; cursor: pointer;">
                        <option value="26.0833" selected>313 Days (26.08 d/mo - Resto Std)</option>
                        <option value="26.0">312 Days (26.00 d/mo - 6-Day)</option>
                        <option value="21.75">261 Days (21.75 d/mo - 5-Day)</option>
                        <option value="30.0">365 Days (30.42 d/mo)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- SECTION 1: EMPLOYEE SELECTION & ADVANCED FILTERING       -->
    <!-- ======================================================== -->
    <div class="hr-table-card" style="overflow: visible !important;">
        <div class="hr-table-header wd-card-header">
            <div class="wd-card-title-group">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700; flex-shrink: 0;">
                    1
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Employee Selection & Targeting</h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Filter and target employees eligible for wage distortion correction</p>
                </div>
            </div>

            <!-- Top Selection Helper Buttons -->
            <div class="wd-card-actions-group">
                <span class="wd-scroll-hint"><i class="ph ph-arrows-left-right"></i> Scroll table</span>
                <span class="hr-badge hr-badge-purple" id="selectedBadge" style="font-size: 12px; font-weight: 700; padding: 4px 10px;">
                    <i class="ph ph-users-three"></i> <span id="lblSelectedCount">0</span> of <span id="lblFilteredCount">{{ count($employeesData) }}</span> Selected
                </span>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="selectAllFiltered(true)">
                    <i class="ph ph-check-square"></i> Select All Filtered
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="selectAllFiltered(false)">
                    <i class="ph ph-square"></i> Clear Selection
                </button>
            </div>
        </div>

        <!-- Filter Controls Toolbar with Dropdown & Active Filter Pills -->
        <div class="wd-filter-toolbar">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex: 1; min-width: 0;">
                <!-- Filter Dropdown Container -->
                <div class="wd-filter-dropdown-container">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnFilterDropdown" onclick="toggleFilterDropdown(event)" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 13px; border-radius: 8px; white-space: nowrap;">
                        <i class="ph ph-funnel" style="font-size: 14px; color: #7c3aed;"></i>
                        <span>Filter Employees</span>
                        <span id="activeFilterBadge" class="hr-badge hr-badge-purple" style="display: none; font-size: 10.5px; padding: 1.5px 6px;">0</span>
                        <i class="ph ph-caret-down" id="filterDropdownCaret" style="transition: transform 0.2s ease; font-size: 11px;"></i>
                    </button>

                    <!-- The Dropdown Menu containing everything inside -->
                    <div id="wdFilterDropdownMenu" class="wd-filter-dropdown-menu" style="display: none;">
                        <div class="wd-filter-dropdown-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18)); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                                    <i class="ph ph-sliders-horizontal"></i>
                                </div>
                                <div>
                                    <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Filter Workforce</span>
                                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;">Configure target criteria</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetFilters()" style="font-size: 11px; padding: 3px 9px; color: #64748b;">
                                    <i class="ph ph-funnel-simple-x"></i> Reset All
                                </button>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeFilterDropdown()" style="font-size: 11px; padding: 3px 9px;">
                                    <i class="ph ph-x"></i> Close
                                </button>
                            </div>
                        </div>

                        <div class="wd-filter-grid">
                            <!-- Row 1, Col 1: Search Employee (1 Col) -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-magnifying-glass"></i> Search Employee (Name or ID)
                                </label>
                                <div style="position: relative; width: 100%;">
                                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
                                    <input type="text" id="filterSearch" class="hr-input" placeholder="Type name, EMP-XXXX, or title..." oninput="onFilterChange()" style="padding-left: 32px; width: 100%; box-sizing: border-box;">
                                </div>
                            </div>

                            <!-- Row 1, Col 2: Salary Range Min/Max -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-currency-circle-dollar"></i> Salary Range (Min - Max)
                                </label>
                                <div class="wd-salary-range">
                                    <input type="number" id="filterSalaryMin" class="hr-input" placeholder="Min ₱" oninput="onFilterChange()">
                                    <span style="color: #94a3b8; font-weight: 600;">-</span>
                                    <input type="number" id="filterSalaryMax" class="hr-input" placeholder="Max ₱" oninput="onFilterChange()">
                                </div>
                            </div>

                            <!-- Row 1, Col 3: Salary Quick Presets (1 Col) -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-lightning"></i> Salary Presets
                                </label>
                                <div class="wd-salary-presets">
                                    <button type="button" class="hr-btn" onclick="setSalaryPreset(0, 20000)">&lt;20k</button>
                                    <button type="button" class="hr-btn" onclick="setSalaryPreset(20000, 30000)">20k-30k</button>
                                    <button type="button" class="hr-btn" onclick="setSalaryPreset(30000, 50000)">30k-50k</button>
                                    <button type="button" class="hr-btn" onclick="setSalaryPreset(50000, 999999)">&gt;50k</button>
                                </div>
                            </div>

                            <!-- Row 2, Col 1: Department -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-tree-structure"></i> Department
                                </label>
                                <select id="filterDept" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->name }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2, Col 2: Position -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-identification-card"></i> Position
                                </label>
                                <select id="filterPosition" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Positions</option>
                                    @foreach($positions as $p)
                                        <option value="{{ $p->name }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2, Col 3: Branch -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-storefront"></i> Branch
                                </label>
                                <select id="filterBranch" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->name }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 3, Col 1: Employment Status -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-user-check"></i> Employment Status
                                </label>
                                <select id="filterStatus" class="hr-select" onchange="onFilterChange()">
                                    <option value="ACTIVE_ALL" selected>Active Workforce Only</option>
                                    <option value="">All Statuses (Inc. Separated)</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Contractual">Contractual</option>
                                    <option value="Project-Based">Project-Based</option>
                                    <option value="Temporary">Temporary</option>
                                    <option value="On-the-Job Training">On-the-Job Training</option>
                                    <option value="Resigned">Resigned</option>
                                    <option value="Terminated">Terminated</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <!-- Row 3, Col 2: Employee Type -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-briefcase"></i> Employee Type
                                </label>
                                <select id="filterType" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Types</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Contractual">Contractual</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Seasonal">Seasonal</option>
                                    <option value="Intern / OJT">Intern / OJT</option>
                                </select>
                            </div>

                            <!-- Row 3, Col 3: Pay Group -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-clock-clockwise"></i> Pay Group
                                </label>
                                <select id="filterPayGroup" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Pay Groups</option>
                                    <option value="Semi-Monthly">Semi-Monthly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Weekly">Weekly</option>
                                </select>
                            </div>

                            <!-- Row 4, Col 1: Salary Grade / Job Level -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-chart-bar"></i> Salary Grade / Job Level
                                </label>
                                <select id="filterJobLevel" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Salary Grades</option>
                                    @foreach($jobLevels as $jl)
                                        <option value="{{ $jl->name }}">{{ $jl->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 4, Col 2: Employment Classification -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-buildings"></i> Classification
                                </label>
                                <select id="filterSource" class="hr-select" onchange="onFilterChange()">
                                    <option value="">All Classifications</option>
                                    <option value="Direct">Direct Company Hire</option>
                                    <option value="Agency">Agency Deployed</option>
                                </select>
                            </div>

                            <!-- Row 4, Col 3: Current Payroll Period Reference -->
                            <div>
                                <label class="wd-filter-label">
                                    <i class="ph ph-calendar-blank"></i> Payroll Period Target
                                </label>
                                <select id="filterPayrollPeriod" class="hr-select" onchange="onFilterChange()">
                                    <option value="">Immediate Effective Date</option>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Dropdown Footer Actions -->
                        <div class="wd-filter-dropdown-footer">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetFilters()" style="font-size: 11.5px; padding: 6px 14px; border-radius: 8px; color: #64748b;">
                                <i class="ph ph-arrow-counterclockwise"></i> Reset All Filters
                            </button>
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="closeFilterDropdown()" style="padding: 6px 20px; font-size: 12px; font-weight: 650; border-radius: 8px; background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; color: #fff; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);">
                                <i class="ph ph-check"></i> Apply & Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Separator line (shown when pills exist) -->
                <div id="filterToolbarSeparator" style="display: none; width: 1px; height: 22px; background: #e2e8f0; margin: 0 4px;"></div>

                <!-- Active Filter Pills Row with X -->
                <div id="activeFilterPillsRow" class="hr-filter-chips-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin: 0; min-height: auto;"></div>
            </div>

            <!-- Right Toolbar Status / Quick Reset -->
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto;">
                <button type="button" id="btnQuickResetFilters" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetFilters()" style="display: none; font-size: 11px; padding: 4px 10px; color: #64748b; border-radius: 6px;">
                    <i class="ph ph-funnel-simple-x"></i> Reset Filters
                </button>
                <span class="hr-badge hr-badge-neutral" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 11.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                    Showing <strong id="lblFilteredCountToolbar" style="color: #0f172a;">{{ count($employeesData) }}</strong> of {{ count($employeesData) }} employees
                </span>
            </div>
        </div>

        <!-- Employee Selection Table -->
        <div class="hr-table-wrapper" style="max-height: 420px; overflow: auto; width: 100%; max-width: 100%;">
            <table class="hr-table" id="empSelectionTable" style="min-width: 860px; width: 100%;">
                <thead style="position: sticky; top: 0; background: #ffffff; z-index: 5; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="chkSelectAll" onchange="toggleSelectAll(this.checked)" style="cursor: pointer; width: 16px; height: 16px;">
                        </th>
                        <th class="sortable" onclick="sortTable('name')" style="cursor: pointer;" title="Sort by Employee Name">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Employee</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortTable('department')" style="cursor: pointer;" title="Sort by Department">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Department</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortTable('position')" style="cursor: pointer;" title="Sort by Position">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Position</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                        <th>Branch</th>
                        <th class="sortable" onclick="sortTable('status')" style="cursor: pointer; width: 120px;" title="Sort by Status">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Status</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                        <th>Pay Group</th>
                        <th class="sortable" onclick="sortTable('salary')" style="cursor: pointer; text-align: right;" title="Sort by Salary">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                <span>Current Salary</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortTable('grade')" style="cursor: pointer; text-align: center; width: 110px;" title="Sort by Salary Grade">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <span>Salary Grade</span>
                                <i class="ph ph-arrows-down-up" style="color: #94a3b8; font-size: 13px;"></i>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="empTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
        <div style="padding: 10px 20px; background: #ffffff; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #64748b;">
            <div>Showing <strong id="lblVisibleRows">0</strong> matching employees in directory</div>
            <div><strong id="lblSelectedCountFooter">0</strong> employees currently selected for calculation</div>
        </div>
    </div>


    <!-- ======================================================== -->
    <!-- SECTION 2: WAGE DISTORTION PARAMETERS & FORMULAS         -->
    <!-- ======================================================== -->
    <div class="hr-table-card" style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <div class="hr-table-header wd-card-header">
            <div class="wd-card-title-group">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700; flex-shrink: 0;">
                    2
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Wage Distortion Parameters & Formula</h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Select mathematical approach and specify minimum wage order values</p>
                </div>
            </div>

            <!-- Formula Dropdown Selector Alternative -->
            <div class="wd-card-actions-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Active Formula:</label>
                <select id="formulaDropdown" class="hr-select" onchange="selectFormula(this.value)" style="height: 32px; font-size: 12.5px; font-weight: 600; color: #4338ca; min-width: 180px; max-width: 100%;">
                    <option value="pineda">1. Pineda Formula</option>
                    <option value="pineda_cruz_so">2. Pineda-Cruz-So Formula</option>
                    <option value="percentile_carian">3. Percentile Approach / Carian Formula</option>
                    <option value="pcs">4. Philippine Construction Supply (PCS) Formula</option>
                    <option value="joda">5. Jimenez-Ofreneo-De Las Alas (JODA) Formula</option>
                    <option value="bagtas">6. Bagtas Formula</option>
                    <option value="wirerope">7. WireRope Formula</option>
                </select>
            </div>
        </div>

        <div class="wd-params-body">
            <!-- Formula Visual Card Grid (Clickable) -->
            <div class="wd-formula-cards-grid">
                @foreach($formulas as $fKey => $f)
                    <div class="formula-card {{ $fKey === 'pineda' ? 'active' : '' }}" id="formulaCard_{{ $fKey }}" onclick="selectFormula('{{ $fKey }}')" 
                         style="border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; cursor: pointer; transition: all 0.2s ease; position: relative; background: #ffffff;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 10px; padding: 1px 6px;">{{ $f['badge'] }}</span>
                            <span class="formula-radio" style="font-size: 14px; color: #7c3aed;">
                                <i class="ph {{ $fKey === 'pineda' ? 'ph-radio-button' : 'ph-circle' }}"></i>
                            </span>
                        </div>
                        <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 2px;">{{ $f['name'] }}</div>
                        <div style="font-size: 11px; color: #64748b; line-height: 1.3;">{{ $f['category'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Two-Column Configuration + Formula Specs -->
            <div class="wd-params-grid">
                
                <!-- Dynamic Input Fields Panel (Only Relevant Fields Shown!) -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                    <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-sliders-horizontal" style="color: #7c3aed;"></i>
                        <span>Input Parameters for <span id="lblActiveFormulaName" style="color: #7c3aed;">Pineda Formula</span></span>
                    </div>

                    <div id="dynamicFieldsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                        <!-- Injected dynamically via JS based on selected formula -->
                    </div>

                    <!-- Automatically Retrieved Inputs Info Box -->
                    <div style="margin-top: 16px; background: rgba(124, 58, 237, 0.05); border: 1px dashed rgba(124, 58, 237, 0.3); border-radius: 10px; padding: 12px; font-size: 12px; color: #475569;">
                        <div style="font-weight: 600; color: #6b21a8; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="ph ph-info"></i> Automatically Retrieved from Employee Record:
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11.5px;">
                            <div>&bull; <strong>Employee Current Wage:</strong> Read-only (Basic Salary)</div>
                            <div>&bull; <strong>Employee Present Wage:</strong> Computed daily equivalent</div>
                            <div>&bull; <strong>Salary Grade / Job Level:</strong> Active 201 rank</div>
                            <div>&bull; <strong>Department / Branch:</strong> Operational assignment</div>
                        </div>
                    </div>
                </div>

                <!-- Active Formula Mathematical Details & DOLE Legal Basis -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <span class="hr-badge hr-badge-purple" id="lblFormulaBadge" style="font-size: 11px;">DOLE Standard</span>
                        <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Philippine Labor Jurisprudence</span>
                    </div>

                    <h4 id="lblInfoFormulaName" style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700; color: #0f172a;">
                        Pineda Formula
                    </h4>
                    <p id="lblInfoFormulaDesc" style="margin: 0 0 14px 0; font-size: 12.5px; color: #475569; line-height: 1.5;">
                        Adjusts wages above the minimum in decreasing proportions based on the distance between the employee's current wage and the previous minimum wage.
                    </p>

                    <!-- Math Equation Display Card -->
                    <div style="background: #0f172a; color: #38bdf8; border-radius: 10px; padding: 14px; margin-bottom: 14px; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; line-height: 1.6; text-align: center; box-shadow: inset 0 2px 6px rgba(0,0,0,0.3);">
                        <div style="color: #94a3b8; font-size: 11px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Mathematical Formulation</div>
                        <div id="lblInfoFormulaMath" style="color: #38bdf8; font-weight: 700; font-size: 13.5px;">
                            (Previous Minimum Wage ÷ Employee Current Wage) × Mandated Wage Increase = Wage Distortion Adjustment
                        </div>
                    </div>

                    <!-- Required Inputs Checklist -->
                    <div style="font-size: 12px; color: #334155; line-height: 1.6;">
                        <strong style="color: #0f172a; display: block; margin-bottom: 4px;">Required Inputs for this formula:</strong>
                        <ul id="lblInfoRequiredInputs" style="margin: 0; padding-left: 20px; color: #475569;">
                            <!-- Filled dynamically -->
                        </ul>
                    </div>

                    <div id="lblInfoLegalNote" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 11.5px; color: #64748b; font-style: italic;">
                        DOLE / NWPC Benchmark Formula. Tapers adjustment proportionally as employee wage increases above the previous floor.
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- ======================================================== -->
    <!-- SECTION 3: REAL-TIME CONVERSION RESULTS TABLE            -->
    <!-- ======================================================== -->
    <div class="hr-table-card" style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <div class="hr-table-header wd-card-header">
            <div class="wd-card-title-group">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700; flex-shrink: 0;">
                    3
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Real-Time Conversion Results</h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Instantaneous calculation updates as parameter values are adjusted</p>
                </div>
            </div>

            <div class="wd-card-actions-group">
                <span class="wd-scroll-hint"><i class="ph ph-arrows-left-right"></i> Scroll table</span>
                <span class="hr-badge hr-badge-success" id="liveBadge" style="font-size: 11px;">
                    <i class="ph ph-lightning"></i> Real-Time Active (0ms Latency)
                </span>
                <span style="font-size: 12px; color: #64748b;">
                    <strong id="resultsEmpCount">0</strong> Employees Computed
                </span>
            </div>
        </div>

        <!-- Real-Time Results Table -->
        <div class="hr-table-wrapper" style="max-height: 480px; overflow: auto; width: 100%; max-width: 100%;">
            <table class="hr-table" id="resultsTable" style="min-width: 1080px; width: 100%;">
                <thead style="position: sticky; top: 0; background: #ffffff; z-index: 5; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <tr>
                        <th style="width: 40px; text-align: center;">Action</th>
                        <th>Employee</th>
                        <th>Department & Position</th>
                        <th style="text-align: right;">Current Basic Salary</th>
                        <th style="text-align: right;">Daily Rate</th>
                        <th style="text-align: center;">Formula Used</th>
                        <th style="text-align: right; background: #faf5ff;">Daily Adjustment</th>
                        <th style="text-align: right; background: #faf5ff;">Monthly Adjustment</th>
                        <th style="text-align: right; background: #f0fdf4;">New Basic Salary</th>
                        <th style="text-align: center;">% Change</th>
                        <th style="text-align: center;">Impact Status</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>

        <!-- Empty state placeholder if 0 selected -->
        <div id="resultsEmptyPlaceholder" style="padding: 48px 20px; text-align: center; background: #ffffff;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 12px;">
                <i class="ph ph-user-plus"></i>
            </div>
            <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: #334155;">No Employees Selected Yet</h4>
            <p style="margin: 0 auto 16px auto; max-width: 420px; font-size: 12.5px; color: #64748b; line-height: 1.5;">
                Select one or more employees in Section 1 above to preview real-time wage distortion adjustments, percentage increases, and new basic salaries.
            </p>
            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="selectAllFiltered(true)">
                <i class="ph ph-check-square"></i> Select All Filtered Employees
            </button>
        </div>
    </div>


    <!-- ======================================================== -->
    <!-- SECTION 4: CONVERSION SUMMARY & ACTIONS                  -->
    <!-- ======================================================== -->
    <div class="hr-table-card" style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <div class="hr-table-header wd-card-header">
            <div class="wd-card-title-group">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700; flex-shrink: 0;">
                    4
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Conversion Summary & Actions</h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Overall payroll budget impact, statutory audit trail, and batch application</p>
                </div>
            </div>
        </div>

        <div style="padding: 24px; background: #ffffff; width: 100%; box-sizing: border-box;">
            <!-- KPI Summary Cards Grid -->
            <div class="wd-kpi-grid">
                
                <!-- Card 1: Selected Workforce -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Selected Employees</span>
                        <div style="width: 30px; height: 30px; border-radius: 8px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="ph ph-users"></i>
                        </div>
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a;" id="kpiEmpCount">0</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        <span id="kpiEmpPercent">0%</span> of active workforce
                    </div>
                </div>

                <!-- Card 2: Total Monthly Distortion Cost -->
                <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 14px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #7e22ce; text-transform: uppercase; letter-spacing: 0.5px;">Monthly Distortion Cost</span>
                        <div style="width: 30px; height: 30px; border-radius: 8px; background: #f3e8ff; color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="ph ph-coins"></i>
                        </div>
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #7e22ce;" id="kpiTotalAdjustment">₱0.00</div>
                    <div style="font-size: 12px; color: #6b21a8; margin-top: 4px;">
                        Projected additional monthly wage spend
                    </div>
                </div>

                <!-- Card 3: Average Adjustment -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px;">Average Monthly Raise</span>
                        <div style="width: 30px; height: 30px; border-radius: 8px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="ph ph-trend-up"></i>
                        </div>
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #15803d;" id="kpiAvgAdjustment">₱0.00</div>
                    <div style="font-size: 12px; color: #166534; margin-top: 4px;">
                        Average per selected employee
                    </div>
                </div>

                <!-- Card 4: Previous vs New Payroll -->
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px;">New Monthly Payroll</span>
                        <div style="width: 30px; height: 30px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="ph ph-receipt"></i>
                        </div>
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: #0369a1;" id="kpiNewPayroll">₱0.00</div>
                    <div style="font-size: 12px; color: #075985; margin-top: 4px;">
                        Previous: <span id="kpiPrevPayroll">₱0.00</span> (<strong id="kpiPercentIncrease">+0.00%</strong>)
                    </div>
                </div>

            </div>

            <!-- Action Buttons Banner -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0f172a;">Ready to apply adjustments?</h4>
                    <p style="margin: 0; font-size: 12.5px; color: #64748b;">
                        Applying adjustments will update the active basic salary on each employee's 201 file and create an indelible audit trail under <code style="color: #7c3aed; background: #f3e8ff; padding: 1px 4px; border-radius: 4px;">salary_history</code>.
                    </p>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="exportConversionCSV()">
                        <i class="ph ph-download-simple"></i>
                        <span>Download CSV / Excel</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="window.print()">
                        <i class="ph ph-printer"></i>
                        <span>Print DOLE Report</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="openApplyAdjustmentModal()" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); padding: 10px 20px; font-size: 13.5px; font-weight: 700; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);">
                        <i class="ph ph-check-circle" style="font-size: 18px;"></i>
                        <span>Apply Adjustments to Employees</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>


<!-- ======================================================== -->
<!-- MODAL: CONFIRM APPLY WAGE ADJUSTMENTS                     -->
<!-- ======================================================== -->
<div class="hr-modal-overlay" id="applyModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 18px; width: 100%; max-width: 540px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; animation: hrModalFade 0.2s ease-out; margin: 20px;">
        
        <div style="padding: 20px 24px; background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-scales"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #ffffff;">Confirm Wage Distortion Adjustments</h3>
                    <p style="margin: 0; font-size: 12px; color: rgba(255,255,255,0.8);">Batch update employee basic salaries & salary history</p>
                </div>
            </div>
            <button type="button" onclick="closeApplyModal()" style="background: transparent; border: none; color: #ffffff; font-size: 20px; cursor: pointer; opacity: 0.8;">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <form id="frmApplyAdjustments" onsubmit="submitApplyAdjustments(event)" style="padding: 24px;">
            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <div class="wd-modal-grid">
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11.5px;">Target Employees:</span>
                        <strong style="color: #0f172a; font-size: 16px;" id="modalEmpCount">0</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11.5px;">Formula Used:</span>
                        <strong style="color: #7c3aed;" id="modalFormulaName">Pineda Formula</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11.5px;">Total Monthly Additional Spend:</span>
                        <strong style="color: #059669; font-size: 16px;" id="modalTotalSpend">₱0.00</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11.5px;">Average Increase / Employee:</span>
                        <strong style="color: #0284c7;" id="modalAvgSpend">₱0.00</strong>
                    </div>
                </div>
            </div>

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #334155;">Effective Date *</label>
                <input type="date" id="applyEffectiveDate" class="hr-input" required value="{{ date('Y-m-d') }}" style="width: 100%; box-sizing: border-box; height: 38px;">
                <span style="font-size: 11px; color: #64748b;">The date when the new wage adjustment takes legal effect in payroll.</span>
            </div>

            <div class="hr-form-group" style="margin-bottom: 20px;">
                <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #334155;">Adjustment Notes / Legal Basis (Optional)</label>
                <input type="text" id="applyReason" class="hr-input" placeholder="e.g. Compliance with RTWPB Wage Order No. NCR-25" style="width: 100%; box-sizing: border-box; height: 38px;">
                <span style="font-size: 11px; color: #64748b;">Recorded into the employee's permanent 201 salary history audit log.</span>
            </div>

            <div style="background: rgba(220, 38, 38, 0.05); border: 1px solid rgba(220, 38, 38, 0.2); border-radius: 10px; padding: 12px; margin-bottom: 24px; font-size: 12px; color: #991b1b; display: flex; align-items: flex-start; gap: 8px;">
                <i class="ph ph-warning-circle" style="font-size: 18px; flex-shrink: 0; color: #dc2626;"></i>
                <div>
                    <strong>Audit Notice:</strong> This action will permanently update each selected employee's basic salary. All past salary records are safely archived in the audit trail.
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeApplyModal()">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" id="btnSubmitApply" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                    <i class="ph ph-check"></i>
                    <span>Confirm & Apply Adjustments</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ======================================================== -->
<!-- MODAL: MATHEMATICAL BREAKDOWN INSPECTION                 -->
<!-- ======================================================== -->
<div class="hr-modal-overlay" id="breakdownModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; animation: hrModalFade 0.2s ease-out; margin: 20px;">
        <div style="padding: 16px 20px; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-function" style="color: #38bdf8; font-size: 18px;"></i>
                <h4 style="margin: 0; font-size: 14.5px; font-weight: 700; color: #ffffff;">Calculation Breakdown</h4>
            </div>
            <button type="button" onclick="closeBreakdownModal()" style="background: transparent; border: none; color: #ffffff; font-size: 18px; cursor: pointer;">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Employee</span>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a;" id="breakdownEmpName">Juan Dela Cruz</div>
                <div style="font-size: 12px; color: #64748b;" id="breakdownEmpMeta">EMP-001 &bull; Management</div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px; font-family: monospace; font-size: 13px; color: #1e293b;">
                <div style="color: #7c3aed; font-weight: 700; margin-bottom: 4px;" id="breakdownFormulaName">Pineda Formula</div>
                <div id="breakdownEquation" style="word-break: break-all; color: #0f172a; font-size: 13.5px; font-weight: 600;">(₱610.00 ÷ ₱1,000.00) × ₱35.00 = ₱21.35/day</div>
            </div>

            <div class="wd-modal-grid" style="margin-bottom: 16px;">
                <div style="background: #faf5ff; padding: 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                    <span style="color: #6b21a8; font-size: 11px; display: block;">Daily Adjustment:</span>
                    <strong style="color: #7c3aed; font-size: 15px;" id="breakdownDailyAdj">₱0.00</strong>
                </div>
                <div style="background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px solid #dcfce7;">
                    <span style="color: #15803d; font-size: 11px; display: block;">Monthly Adjustment:</span>
                    <strong style="color: #16a34a; font-size: 15px;" id="breakdownMonthlyAdj">₱0.00</strong>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="color: #64748b; font-size: 11px; display: block;">Current Basic Salary:</span>
                    <strong style="color: #334155; font-size: 14px;" id="breakdownCurrentSalary">₱0.00</strong>
                </div>
                <div style="background: #eff6ff; padding: 10px; border-radius: 8px; border: 1px solid #dbeafe;">
                    <span style="color: #1d4ed8; font-size: 11px; display: block;">New Basic Salary:</span>
                    <strong style="color: #2563eb; font-size: 14px;" id="breakdownNewSalary">₱0.00</strong>
                </div>
            </div>

            <button type="button" class="hr-btn hr-btn-secondary" style="width: 100%; justify-content: center;" onclick="closeBreakdownModal()">
                Close
            </button>
        </div>
    </div>
</div>


<!-- ======================================================== -->
<!-- PRINT / DOLE COMPLIANCE TEMPLATE                         -->
<!-- ======================================================== -->
<div id="printReportTemplate" style="display: none;">
    <div style="padding: 24px; font-family: Arial, sans-serif; color: #000000;">
        <div style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #000; padding-bottom: 12px;">
            <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;">Republic of the Philippines</h2>
            <h3 style="margin: 4px 0; font-size: 16px;">National Wages and Productivity Commission (NWPC) / DOLE</h3>
            <h1 style="margin: 6px 0; font-size: 20px; text-transform: uppercase;">Wage Distortion Adjustment Report</h1>
            <p style="margin: 4px 0; font-size: 12px;">Date of Generation: {{ date('F d, Y') }} &bull; Enterprise Compliance Record</p>
        </div>

        <div style="margin-bottom: 20px; font-size: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <strong>Applied Formula:</strong> <span id="printFormulaName">Pineda Formula</span><br>
                <strong>Effective Date:</strong> {{ date('F d, Y') }}<br>
                <strong>Monthly Working Divisor:</strong> <span id="printDivisor">26.0833</span> days/month
            </div>
            <div style="text-align: right;">
                <strong>Total Employees Covered:</strong> <span id="printEmpCount">0</span><br>
                <strong>Total Monthly Wage Adjustment:</strong> <span id="printTotalSpend">₱0.00</span><br>
                <strong>Average Adjustment:</strong> <span id="printAvgSpend">₱0.00</span>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 30px;" border="1">
            <thead>
                <tr style="background: #f2f2f2;">
                    <th style="padding: 6px;">ID</th>
                    <th style="padding: 6px; text-align: left;">Employee Name</th>
                    <th style="padding: 6px; text-align: left;">Department</th>
                    <th style="padding: 6px; text-align: left;">Position</th>
                    <th style="padding: 6px; text-align: right;">Current Basic</th>
                    <th style="padding: 6px; text-align: right;">Daily Rate</th>
                    <th style="padding: 6px; text-align: right;">Daily Adj.</th>
                    <th style="padding: 6px; text-align: right;">Monthly Adj.</th>
                    <th style="padding: 6px; text-align: right;">New Basic</th>
                    <th style="padding: 6px; text-align: center;">% Inc.</th>
                </tr>
            </thead>
            <tbody id="printTableBody">
                <!-- Injected before print -->
            </tbody>
        </table>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 48px; font-size: 11px; text-align: center;">
            <div>
                <div style="border-bottom: 1px solid #000; margin-bottom: 4px; height: 36px;"></div>
                <strong>Prepared By:</strong><br>
                {{ Auth::user()->name }}<br>
                Payroll Officer
            </div>
            <div>
                <div style="border-bottom: 1px solid #000; margin-bottom: 4px; height: 36px;"></div>
                <strong>Reviewed By:</strong><br>
                HR Director / Manager
            </div>
            <div>
                <div style="border-bottom: 1px solid #000; margin-bottom: 4px; height: 36px;"></div>
                <strong>Approved & Authorized:</strong><br>
                General Manager / President
            </div>
        </div>
    </div>
</div>


<!-- Inline Style for Active Formula Card and Animations -->

<!-- ======================================================== -->
<!-- JAVASCRIPT: REAL-TIME CONVERTER & SELECTION ENGINE       -->
<!-- ======================================================== -->
@endsection
