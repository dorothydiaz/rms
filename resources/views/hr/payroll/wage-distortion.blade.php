@extends('layouts.app')

@section('title', 'Wage Distortion Converter - Enterprise Philippine Payroll')

@push('styles')
<style>
/* Wage Distortion Responsive Framework */
.wage-distortion-container {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.wage-distortion-container .hr-table-card {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    background: #ffffff;
}

.wage-distortion-container .hr-table-wrapper {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto !important;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

#empSelectionTable {
    width: 100%;
    min-width: 860px;
}

#resultsTable {
    width: 100%;
    min-width: 1080px;
}

/* Compliance Banner */
.wd-banner {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, rgba(79, 70, 229, 0.05) 100%);
    border: 1px solid rgba(124, 58, 237, 0.2);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-width: 0;
}

.wd-banner-left {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex: 1 1 480px;
    min-width: 0;
}

.wd-banner-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    flex-wrap: wrap;
}

@media (max-width: 900px) {
    .wd-banner-right {
        width: 100%;
        justify-content: space-between;
        border-top: 1px solid rgba(124, 58, 237, 0.15);
        padding-top: 12px;
        margin-top: 4px;
    }
}

/* Section Header Bar */
.wd-card-header {
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    width: 100%;
    box-sizing: border-box;
}

.wd-card-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.wd-card-actions-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Filter Controls Grid */
.wd-filter-panel {
    background: #f8fafc;
    padding: 10px 16px;
    border-bottom: 1px solid #e2e8f0;
    box-sizing: border-box;
    width: 100%;
}

.wd-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    width: 100%;
    box-sizing: border-box;
}

.wd-search-span {
    grid-column: span 2;
}

@media (max-width: 768px) {
    .wd-search-span {
        grid-column: span 1 !important;
    }
    .wd-filter-grid {
        grid-template-columns: 1fr;
    }
}

/* Section 2 Parameters Layout */
.wd-params-body {
    padding: 20px;
    background: #ffffff;
    box-sizing: border-box;
    width: 100%;
}

.wd-formula-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
    width: 100%;
    box-sizing: border-box;
}

.wd-params-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 992px) {
    .wd-params-grid {
        grid-template-columns: 1fr;
    }
}

/* Section 4 KPI Summary Grid */
.wd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 640px) {
    .wd-kpi-grid {
        grid-template-columns: 1fr;
    }
    .wd-card-header {
        padding: 12px 14px;
    }
    .wd-filter-panel {
        padding: 12px 14px;
    }
    .wd-params-body {
        padding: 14px;
    }
}

/* Modal 2-col Stats */
.wd-modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 13px;
}

@media (max-width: 480px) {
    .wd-modal-grid {
        grid-template-columns: 1fr;
    }
}

/* Horizontal Scroll Indicator */
.wd-scroll-hint {
    display: none;
    font-size: 11px;
    color: #64748b;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: #f1f5f9;
    border-radius: 6px;
    font-weight: 500;
}

@media (max-width: 1024px) {
    .wd-scroll-hint {
        display: inline-flex;
    }
}
</style>
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
    <div class="hr-table-card">
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

        <!-- Filter Controls Panel -->
        <div class="wd-filter-panel">
            <div class="wd-filter-grid">
                
                <!-- Quick Search -->
                <div class="wd-search-span">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-magnifying-glass"></i> Search Employee (Name or ID)
                    </label>
                    <div style="position: relative;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" id="filterSearch" class="hr-input" placeholder="Type name, EMP-XXXX, or title..." oninput="onFilterChange()" style="padding-left: 30px; width: 100%; box-sizing: border-box; height: 31px; font-size: 12px;">
                    </div>
                </div>

                <!-- Salary Range Min/Max -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-currency-circle-dollar"></i> Salary Range (Min - Max)
                    </label>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <input type="number" id="filterSalaryMin" class="hr-input" placeholder="Min ₱" oninput="onFilterChange()" style="width: 50%; height: 31px; font-size: 12px; padding: 0 8px;">
                        <span style="color: #94a3b8;">-</span>
                        <input type="number" id="filterSalaryMax" class="hr-input" placeholder="Max ₱" oninput="onFilterChange()" style="width: 50%; height: 31px; font-size: 12px; padding: 0 8px;">
                    </div>
                </div>

                <!-- Department -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-tree-structure"></i> Department
                    </label>
                    <select id="filterDept" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->name }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Position -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-identification-card"></i> Position
                    </label>
                    <select id="filterPosition" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Positions</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->name }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Branch -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-storefront"></i> Branch
                    </label>
                    <select id="filterBranch" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->name }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Employment Status -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-user-check"></i> Employment Status
                    </label>
                    <select id="filterStatus" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
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

                <!-- Employee Type -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-briefcase"></i> Employee Type
                    </label>
                    <select id="filterType" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Types</option>
                        <option value="Regular">Regular</option>
                        <option value="Probationary">Probationary</option>
                        <option value="Contractual">Contractual</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Seasonal">Seasonal</option>
                        <option value="Intern / OJT">Intern / OJT</option>
                    </select>
                </div>

                <!-- Pay Group (Frequency) -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-clock-clockwise"></i> Pay Group
                    </label>
                    <select id="filterPayGroup" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Pay Groups</option>
                        <option value="Semi-Monthly">Semi-Monthly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Weekly">Weekly</option>
                    </select>
                </div>

                <!-- Salary Grade / Job Level -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-chart-bar"></i> Salary Grade / Job Level
                    </label>
                    <select id="filterJobLevel" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Salary Grades</option>
                        @foreach($jobLevels as $jl)
                            <option value="{{ $jl->name }}">{{ $jl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Employment Classification / Source -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-buildings"></i> Classification
                    </label>
                    <select id="filterSource" class="hr-select" onchange="onFilterChange()" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">All Classifications</option>
                        <option value="Direct">Direct Company Hire</option>
                        <option value="Agency">Agency Deployed</option>
                    </select>
                </div>

                <!-- Current Payroll Period Reference -->
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 3px;">
                        <i class="ph ph-calendar-blank"></i> Payroll Period Target
                    </label>
                    <select id="filterPayrollPeriod" class="hr-select" style="height: 31px; font-size: 12px; width: 100%;">
                        <option value="">Immediate Effective Date</option>
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Quick Filter Chips & Reset -->
            <div style="margin-top: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Salary Quick Filters:</span>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="setSalaryPreset(0, 20000)">Under ₱20k</button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="setSalaryPreset(20000, 30000)">₱20k - ₱30k</button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="setSalaryPreset(30000, 50000)">₱30k - ₱50k</button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="setSalaryPreset(50000, 999999)">Above ₱50k</button>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetFilters()" style="color: #64748b;">
                    <i class="ph ph-funnel-simple-x"></i> Reset Filters
                </button>
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
<style>
.formula-card.active {
    border-color: #7c3aed !important;
    background: #faf5ff !important;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15) !important;
}
.formula-card:hover {
    border-color: #c084fc;
    transform: translateY(-1px);
}
@keyframes hrModalFade {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
@media print {
    body * {
        visibility: hidden !important;
    }
    #printReportTemplate, #printReportTemplate * {
        visibility: visible !important;
        display: block !important;
    }
    #printReportTemplate {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
    }
    #printReportTemplate table {
        display: table !important;
    }
    #printReportTemplate thead {
        display: table-header-group !important;
    }
    #printReportTemplate tbody {
        display: table-row-group !important;
    }
    #printReportTemplate tr {
        display: table-row !important;
    }
    #printReportTemplate th, #printReportTemplate td {
        display: table-cell !important;
    }
}
</style>


<!-- ======================================================== -->
<!-- JAVASCRIPT: REAL-TIME CONVERTER & SELECTION ENGINE       -->
<!-- ======================================================== -->
<script>
// Raw server data payload
const ALL_EMPLOYEES = @json($employeesData);
const FORMULAS_CONFIG = @json($formulas);

// Application State
let state = {
    selectedEmpIds: new Set(),
    filteredEmployees: [...ALL_EMPLOYEES],
    activeFormulaKey: 'pineda',
    monthlyDivisor: {{ $defaultDivisor }},
    params: {
        previous_min_wage: 610.00,
        mandated_increase: 35.00,
        existing_min_wage: 695.00,
        creditable_increase: 10.00,
        formula_base_range: 750.00,
        exponent: 1.20,
        percentile_weight: 85.00
    },
    sortColumn: 'name',
    sortAsc: true,
    results: []
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Default selection: select all active employees
    ALL_EMPLOYEES.forEach(emp => {
        if (!['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) {
            state.selectedEmpIds.add(emp.id);
        }
    });

    renderFormulaCards();
    renderDynamicInputFields();
    updateFormulaInfoCard();
    applyFilters();
});

// Format currency in Philippine Peso
function formatPhp(amount) {
    const num = parseFloat(amount) || 0.0;
    return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ---------------------------------------------------------
// 1. FILTERING & SELECTION ENGINE
// ---------------------------------------------------------
function applyFilters() {
    const search = (document.getElementById('filterSearch').value || '').trim().toLowerCase();
    const salMin = parseFloat(document.getElementById('filterSalaryMin').value) || 0;
    const salMax = parseFloat(document.getElementById('filterSalaryMax').value) || 999999999;
    const dept = document.getElementById('filterDept').value;
    const pos = document.getElementById('filterPosition').value;
    const branch = document.getElementById('filterBranch').value;
    const status = document.getElementById('filterStatus').value;
    const empType = document.getElementById('filterType').value;
    const payGroup = document.getElementById('filterPayGroup').value;
    const jobLevel = document.getElementById('filterJobLevel').value;
    const source = document.getElementById('filterSource').value;

    state.filteredEmployees = ALL_EMPLOYEES.filter(emp => {
        // Search
        if (search) {
            const matchName = (emp.full_name || '').toLowerCase().includes(search);
            const matchId = (emp.employee_id || '').toLowerCase().includes(search);
            const matchPos = (emp.position_name || '').toLowerCase().includes(search);
            if (!matchName && !matchId && !matchPos) return false;
        }

        // Salary Range
        if (emp.basic_salary < salMin || emp.basic_salary > salMax) return false;

        // Department
        if (dept && emp.department_name !== dept) return false;

        // Position
        if (pos && emp.position_name !== pos) return false;

        // Branch
        if (branch && emp.branch_name !== branch) return false;

        // Status
        if (status === 'ACTIVE_ALL') {
            if (['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) return false;
        } else if (status && emp.employment_status !== status) {
            return false;
        }

        // Employee Type
        if (empType && emp.employment_type !== empType) return false;

        // Pay Group
        if (payGroup && emp.pay_frequency !== payGroup) return false;

        // Job Level
        if (jobLevel && emp.job_level !== jobLevel) return false;

        // Classification / Source
        if (source && emp.employment_source !== source) return false;

        return true;
    });

    sortFilteredEmployees();
    renderEmployeeTable();
    recalculateAll();
}

function onFilterChange() {
    applyFilters();
}

function setSalaryPreset(min, max) {
    document.getElementById('filterSalaryMin').value = min;
    document.getElementById('filterSalaryMax').value = (max === 999999) ? '' : max;
    applyFilters();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSalaryMin').value = '';
    document.getElementById('filterSalaryMax').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('filterPosition').value = '';
    document.getElementById('filterBranch').value = '';
    document.getElementById('filterStatus').value = 'ACTIVE_ALL';
    document.getElementById('filterType').value = '';
    document.getElementById('filterPayGroup').value = '';
    document.getElementById('filterJobLevel').value = '';
    document.getElementById('filterSource').value = '';
    applyFilters();
}

function sortTable(column) {
    if (state.sortColumn === column) {
        state.sortAsc = !state.sortAsc;
    } else {
        state.sortColumn = column;
        state.sortAsc = true;
    }
    sortFilteredEmployees();
    renderEmployeeTable();
}

function sortFilteredEmployees() {
    state.filteredEmployees.sort((a, b) => {
        let valA, valB;
        switch (state.sortColumn) {
            case 'name':
                valA = a.full_name.toLowerCase();
                valB = b.full_name.toLowerCase();
                break;
            case 'department':
                valA = (a.department_name || '').toLowerCase();
                valB = (b.department_name || '').toLowerCase();
                break;
            case 'position':
                valA = (a.position_name || '').toLowerCase();
                valB = (b.position_name || '').toLowerCase();
                break;
            case 'status':
                valA = (a.employment_status || '').toLowerCase();
                valB = (b.employment_status || '').toLowerCase();
                break;
            case 'salary':
                valA = parseFloat(a.basic_salary) || 0;
                valB = parseFloat(b.basic_salary) || 0;
                break;
            case 'grade':
                valA = (a.salary_grade || '').toLowerCase();
                valB = (b.salary_grade || '').toLowerCase();
                break;
            default:
                valA = a.id;
                valB = b.id;
        }

        if (valA < valB) return state.sortAsc ? -1 : 1;
        if (valA > valB) return state.sortAsc ? 1 : -1;
        return 0;
    });
}

function renderEmployeeTable() {
    const tbody = document.getElementById('empTableBody');
    if (!tbody) return;

    if (state.filteredEmployees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 32px; color: #94a3b8;">
                    <i class="ph ph-magnifying-glass" style="font-size: 24px; display: block; margin-bottom: 6px;"></i>
                    No employees found matching the filter criteria.
                </td>
            </tr>
        `;
        updateSelectionCounters();
        return;
    }

    let html = '';
    state.filteredEmployees.forEach(emp => {
        const isChecked = state.selectedEmpIds.has(emp.id);
        
        let statusBadgeClass = 'hr-badge-neutral';
        if (emp.employment_status === 'Regular' || emp.employment_status === 'Active') statusBadgeClass = 'hr-badge-success';
        else if (emp.employment_status === 'Probationary') statusBadgeClass = 'hr-badge-warning';
        else if (emp.employment_status === 'Contractual') statusBadgeClass = 'hr-badge-info';
        else if (['Resigned', 'Terminated', 'Inactive'].includes(emp.employment_status)) statusBadgeClass = 'hr-badge-danger';

        const avatar = emp.photo_url 
            ? `<img src="${emp.photo_url}" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0;">`
            : `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">${emp.initials}</div>`;

        html += `
            <tr style="background: ${isChecked ? 'rgba(124, 58, 237, 0.03)' : '#ffffff'}; transition: background 0.15s ease;">
                <td style="text-align: center;">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleSelectEmp(${emp.id}, this.checked)" style="cursor: pointer; width: 16px; height: 16px;">
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${avatar}
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">${emp.full_name}</div>
                            <div style="font-family: monospace; font-size: 11px; color: #7c3aed;">${emp.employee_id}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size: 12.5px; color: #334155; font-weight: 500;">${emp.department_name}</td>
                <td style="font-size: 12.5px; color: #0f172a;">${emp.position_name}</td>
                <td style="font-size: 12px; color: #64748b;">${emp.branch_name}</td>
                <td>
                    <span class="hr-badge ${statusBadgeClass}" style="font-size: 11px;">${emp.employment_status}</span>
                </td>
                <td style="font-size: 12px; color: #475569;">${emp.pay_frequency}</td>
                <td style="text-align: right;">
                    <div style="font-weight: 700; font-size: 13px; color: #0f172a;">${formatPhp(emp.basic_salary)}</div>
                    <div style="font-size: 11px; color: #64748b;">${formatPhp(emp.daily_rate)} / day</div>
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px;">${emp.salary_grade}</span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    updateSelectionCounters();
}

function toggleSelectEmp(empId, isSelected) {
    if (isSelected) {
        state.selectedEmpIds.add(empId);
    } else {
        state.selectedEmpIds.delete(empId);
    }
    updateSelectionCounters();
    recalculateAll();
}

function selectAllFiltered(doSelect) {
    state.filteredEmployees.forEach(emp => {
        if (doSelect) {
            state.selectedEmpIds.add(emp.id);
        } else {
            state.selectedEmpIds.delete(emp.id);
        }
    });
    renderEmployeeTable();
    recalculateAll();
}

function toggleSelectAll(isChecked) {
    selectAllFiltered(isChecked);
}

function updateSelectionCounters() {
    const totalFiltered = state.filteredEmployees.length;
    const selectedCount = state.selectedEmpIds.size;

    document.getElementById('lblSelectedCount').innerText = selectedCount;
    document.getElementById('lblFilteredCount').innerText = totalFiltered;
    document.getElementById('lblVisibleRows').innerText = totalFiltered;
    document.getElementById('lblSelectedCountFooter').innerText = selectedCount;
    document.getElementById('scopeSelectedCount').innerText = selectedCount;
    document.getElementById('btnApplyCount').innerText = selectedCount;

    // Checkbox master state
    const chkMaster = document.getElementById('chkSelectAll');
    if (chkMaster) {
        if (totalFiltered === 0) {
            chkMaster.checked = false;
            chkMaster.indeterminate = false;
        } else {
            const allSelected = state.filteredEmployees.every(emp => state.selectedEmpIds.has(emp.id));
            const someSelected = state.filteredEmployees.some(emp => state.selectedEmpIds.has(emp.id));
            chkMaster.checked = allSelected;
            chkMaster.indeterminate = !allSelected && someSelected;
        }
    }
}


// ---------------------------------------------------------
// 2. FORMULA SELECTION & DYNAMIC PARAMETER FIELDS
// ---------------------------------------------------------
function selectFormula(formulaKey) {
    if (!FORMULAS_CONFIG[formulaKey]) return;
    state.activeFormulaKey = formulaKey;

    // Sync dropdown
    const dd = document.getElementById('formulaDropdown');
    if (dd) dd.value = formulaKey;

    renderFormulaCards();
    renderDynamicInputFields();
    updateFormulaInfoCard();
    recalculateAll();
}

function renderFormulaCards() {
    document.querySelectorAll('.formula-card').forEach(card => {
        const id = card.id.replace('formulaCard_', '');
        const isCurrent = (id === state.activeFormulaKey);
        card.classList.toggle('active', isCurrent);
        const radioIcon = card.querySelector('.formula-radio i');
        if (radioIcon) {
            radioIcon.className = 'ph ' + (isCurrent ? 'ph-radio-button' : 'ph-circle');
        }
    });
}

function renderDynamicInputFields() {
    const container = document.getElementById('dynamicFieldsContainer');
    const formula = FORMULAS_CONFIG[state.activeFormulaKey];
    if (!container || !formula) return;

    let html = '';
    const fields = formula.fields || {};

    Object.keys(fields).forEach(fKey => {
        const field = fields[fKey];
        const curVal = state.params[fKey] ?? field.default;

        let prefix = '₱';
        if (field.type === 'percentage') prefix = '%';
        if (field.type === 'number') prefix = '#';

        html += `
            <div>
                <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">
                    <span>${field.label}</span>
                    <span style="font-size: 11px; color: #64748b; font-weight: normal;">${field.description}</span>
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #7c3aed; font-size: 13px;">${prefix}</span>
                    <input type="number" 
                           id="param_${fKey}"
                           step="${field.step || '0.01'}" 
                           value="${curVal}" 
                           class="hr-input"
                           placeholder="${field.placeholder}"
                           oninput="onParamChange('${fKey}', this.value)"
                           style="padding-left: 28px; width: 100%; box-sizing: border-box; height: 36px; font-size: 13px; font-weight: 600;">
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('lblActiveFormulaName').innerText = formula.name;
}

function onParamChange(key, value) {
    state.params[key] = parseFloat(value) || 0.0;
    recalculateAll();
}

function onDivisorChange(val) {
    state.monthlyDivisor = parseFloat(val) || {{ $defaultDivisor }};
    recalculateAll();
}

function updateFormulaInfoCard() {
    const f = FORMULAS_CONFIG[state.activeFormulaKey];
    if (!f) return;

    document.getElementById('lblFormulaBadge').innerText = f.badge;
    document.getElementById('lblInfoFormulaName').innerText = f.name;
    document.getElementById('lblInfoFormulaDesc').innerText = f.description;
    document.getElementById('lblInfoFormulaMath').innerText = f.latex;
    document.getElementById('lblInfoLegalNote').innerText = f.notes;

    const list = document.getElementById('lblInfoRequiredInputs');
    if (list) {
        let items = '';
        Object.values(f.fields || {}).forEach(fld => {
            items += `<li><strong>${fld.label}:</strong> ${fld.description}</li>`;
        });
        items += `<li><strong>Employee Wage:</strong> Retrieved from employee masterlist</li>`;
        list.innerHTML = items;
    }
}


// ---------------------------------------------------------
// 3. REAL-TIME CALCULATION & CONVERSION ENGINE (0ms Latency)
// ---------------------------------------------------------
function recalculateAll() {
    const selectedEmps = ALL_EMPLOYEES.filter(e => state.selectedEmpIds.has(e.id));
    const resultsTableBody = document.getElementById('resultsTableBody');
    const emptyPlaceholder = document.getElementById('resultsEmptyPlaceholder');

    if (selectedEmps.length === 0) {
        state.results = [];
        if (resultsTableBody) resultsTableBody.innerHTML = '';
        if (emptyPlaceholder) emptyPlaceholder.style.display = 'block';
        updateSummaryKPIs([]);
        return;
    }

    if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';

    // Compute for each selected employee
    const formulaKey = state.activeFormulaKey;
    const divisor = state.monthlyDivisor;
    const computed = [];

    selectedEmps.forEach(emp => {
        const item = computeSingleEmployee(emp, formulaKey, state.params, divisor);
        computed.push(item);
    });

    state.results = computed;
    renderResultsTable(computed);
    updateSummaryKPIs(computed);
}

function computeSingleEmployee(emp, formulaKey, params, divisor) {
    const currentMonthly = parseFloat(emp.basic_salary) || 0.0;
    const dailyWage = (emp.salary_type === 'Daily')
        ? currentMonthly
        : (divisor > 0 ? (currentMonthly / divisor) : 0.0);

    const prevMin = parseFloat(params.previous_min_wage) || 610.00;
    const mandatedInc = parseFloat(params.mandated_increase) || 35.00;
    const existMin = parseFloat(params.existing_min_wage) || 695.00;
    const creditableInc = parseFloat(params.creditable_increase) || 10.00;
    const fbr = parseFloat(params.formula_base_range) || 750.00;
    const exponent = parseFloat(params.exponent) || 1.20;
    const rawWeight = parseFloat(params.percentile_weight) || 85.00;
    const percentileWeight = (rawWeight > 1.0) ? (rawWeight / 100.0) : rawWeight;

    let dailyAdj = 0.0;
    let equation = '';

    switch (formulaKey) {
        case 'pineda':
            if (dailyWage > 0) {
                dailyAdj = (prevMin / dailyWage) * mandatedInc;
                equation = `(₱${prevMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)}) × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'pineda_cruz_so':
            if (dailyWage > 0 && exponent > 0) {
                const ratio = prevMin / dailyWage;
                dailyAdj = Math.pow(ratio, exponent) * mandatedInc;
                equation = `(₱${prevMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)})^${exponent.toFixed(2)} × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'percentile_carian':
            dailyAdj = percentileWeight * mandatedInc;
            equation = `${(percentileWeight * 100).toFixed(1)}% × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            break;

        case 'pcs':
            if (fbr > 0) {
                dailyAdj = (existMin / fbr) * mandatedInc;
                equation = `(₱${existMin.toFixed(2)} ÷ ₱${fbr.toFixed(2)}) × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'joda':
            const diff = Math.max(0, dailyWage - prevMin);
            dailyAdj = diff / 2.0;
            equation = `(₱${dailyWage.toFixed(2)} – ₱${prevMin.toFixed(2)}) ÷ 2 = ₱${dailyAdj.toFixed(2)}/day`;
            break;

        case 'bagtas':
            if (existMin > 0) {
                dailyAdj = (mandatedInc * dailyWage) / existMin;
                equation = `(₱${mandatedInc.toFixed(2)} × ₱${dailyWage.toFixed(2)}) ÷ ₱${existMin.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'wirerope':
            if (dailyWage > 0 && creditableInc > 0) {
                dailyAdj = (existMin / dailyWage) * (mandatedInc / creditableInc);
                equation = `(₱${existMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)}) × (₱${mandatedInc.toFixed(2)} ÷ ₱${creditableInc.toFixed(2)}) = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;
    }

    dailyAdj = Math.max(0, Math.round(dailyAdj * 100) / 100);
    const monthlyAdj = (emp.salary_type === 'Daily')
        ? dailyAdj
        : Math.round(dailyAdj * divisor * 100) / 100;

    const newMonthly = Math.round((currentMonthly + monthlyAdj) * 100) / 100;
    const newDaily = Math.round((dailyWage + dailyAdj) * 100) / 100;
    const pctInc = currentMonthly > 0 ? ((monthlyAdj / currentMonthly) * 100) : 0.0;

    return {
        employee_id: emp.id,
        employee_code: emp.employee_id,
        employee_name: emp.full_name,
        photo_url: emp.photo_url,
        initials: emp.initials,
        department_name: emp.department_name,
        position_name: emp.position_name,
        branch_name: emp.branch_name,
        employment_status: emp.employment_status,
        salary_type: emp.salary_type,
        current_monthly_salary: currentMonthly,
        current_daily_wage: dailyWage,
        formula_key: formulaKey,
        formula_name: FORMULAS_CONFIG[formulaKey]?.name || 'Wage Converter',
        daily_adjustment: dailyAdj,
        monthly_adjustment: monthlyAdj,
        new_monthly_salary: newMonthly,
        new_daily_wage: newDaily,
        percent_increase: pctInc,
        equation_breakdown: equation,
        status: 'Distortion Resolved'
    };
}

function renderResultsTable(items) {
    const tbody = document.getElementById('resultsTableBody');
    if (!tbody) return;

    let html = '';
    items.forEach((row, idx) => {
        const avatar = row.photo_url 
            ? `<img src="${row.photo_url}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0;">`
            : `<div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0;">${row.initials}</div>`;

        html += `
            <tr>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="removeSingleFromCalc(${row.employee_id})" style="padding: 2px 6px; color: #dc2626;" title="Remove from wage distortion calculation">
                        <i class="ph ph-x"></i>
                    </button>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${avatar}
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">${row.employee_name}</div>
                            <div style="font-family: monospace; font-size: 11px; color: #7c3aed;">${row.employee_code}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight: 500; font-size: 12.5px; color: #334155;">${row.department_name}</div>
                    <div style="font-size: 11.5px; color: #64748b;">${row.position_name}</div>
                </td>
                <td style="text-align: right; font-weight: 600; color: #334155; font-size: 13px;">
                    ${formatPhp(row.current_monthly_salary)}
                </td>
                <td style="text-align: right; font-size: 12px; color: #64748b;">
                    ${formatPhp(row.current_daily_wage)}/d
                </td>
                <td style="text-align: center;">
                    <button type="button" onclick="showBreakdownModal(${idx})" class="hr-badge hr-badge-purple" style="cursor: pointer; border: none; font-size: 10.5px;" title="Click to view mathematical substitution">
                        <i class="ph ph-function"></i> ${FORMULAS_CONFIG[row.formula_key]?.badge || 'Standard'}
                    </button>
                </td>
                <td style="text-align: right; font-weight: 700; color: #7c3aed; background: #faf5ff; font-size: 13px;">
                    +${formatPhp(row.daily_adjustment)}
                </td>
                <td style="text-align: right; font-weight: 800; color: #6b21a8; background: #faf5ff; font-size: 13.5px;">
                    +${formatPhp(row.monthly_adjustment)}
                </td>
                <td style="text-align: right; font-weight: 800; color: #15803d; background: #f0fdf4; font-size: 13.5px;">
                    ${formatPhp(row.new_monthly_salary)}
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-success" style="font-size: 11px; font-weight: 700;">
                        +${row.percent_increase.toFixed(2)}%
                    </span>
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-success" style="font-size: 11px;">
                        <i class="ph ph-check"></i> Resolved
                    </span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('resultsEmpCount').innerText = items.length;
}

function removeSingleFromCalc(empId) {
    state.selectedEmpIds.delete(empId);
    renderEmployeeTable();
    recalculateAll();
}

function updateSummaryKPIs(items) {
    const totalCount = items.length;
    let prevTotal = 0.0;
    let totalAdj = 0.0;
    let newTotal = 0.0;

    items.forEach(it => {
        prevTotal += it.current_monthly_salary;
        totalAdj += it.monthly_adjustment;
        newTotal += it.new_monthly_salary;
    });

    const avgAdj = totalCount > 0 ? (totalAdj / totalCount) : 0.0;
    const totalPct = prevTotal > 0 ? ((totalAdj / prevTotal) * 100) : 0.0;
    const totalActiveWorkforce = ALL_EMPLOYEES.filter(e => !['Resigned', 'Terminated', 'Inactive'].includes(e.employment_status)).length;
    const pctOfWorkforce = totalActiveWorkforce > 0 ? Math.round((totalCount / totalActiveWorkforce) * 100) : 0;

    document.getElementById('kpiEmpCount').innerText = totalCount;
    document.getElementById('kpiEmpPercent').innerText = pctOfWorkforce + '%';
    document.getElementById('kpiTotalAdjustment').innerText = formatPhp(totalAdj);
    document.getElementById('kpiAvgAdjustment').innerText = formatPhp(avgAdj);
    document.getElementById('kpiNewPayroll').innerText = formatPhp(newTotal);
    document.getElementById('kpiPrevPayroll').innerText = formatPhp(prevTotal);
    document.getElementById('kpiPercentIncrease').innerText = '+' + totalPct.toFixed(2) + '%';
}


// ---------------------------------------------------------
// 4. BREAKDOWN MODAL & APPLY MODAL WORKFLOW
// ---------------------------------------------------------
function showBreakdownModal(idx) {
    const item = state.results[idx];
    if (!item) return;

    document.getElementById('breakdownEmpName').innerText = item.employee_name;
    document.getElementById('breakdownEmpMeta').innerText = `${item.employee_code} • ${item.department_name} • ${item.position_name}`;
    document.getElementById('breakdownFormulaName').innerText = item.formula_name;
    document.getElementById('breakdownEquation').innerText = item.equation_breakdown;
    document.getElementById('breakdownDailyAdj').innerText = '+' + formatPhp(item.daily_adjustment);
    document.getElementById('breakdownMonthlyAdj').innerText = '+' + formatPhp(item.monthly_adjustment);
    document.getElementById('breakdownCurrentSalary').innerText = formatPhp(item.current_monthly_salary);
    document.getElementById('breakdownNewSalary').innerText = formatPhp(item.new_monthly_salary);

    document.getElementById('breakdownModal').style.display = 'flex';
}

function closeBreakdownModal() {
    document.getElementById('breakdownModal').style.display = 'none';
}

function openApplyAdjustmentModal() {
    if (state.results.length === 0) {
        alert('Please select at least one employee in Section 1 to calculate and apply wage distortion adjustments.');
        return;
    }

    let totalSpend = 0.0;
    state.results.forEach(r => totalSpend += r.monthly_adjustment);
    const avgSpend = state.results.length > 0 ? (totalSpend / state.results.length) : 0.0;

    document.getElementById('modalEmpCount').innerText = state.results.length + ' Employees';
    document.getElementById('modalFormulaName').innerText = FORMULAS_CONFIG[state.activeFormulaKey]?.name || 'Standard';
    document.getElementById('modalTotalSpend').innerText = '+' + formatPhp(totalSpend);
    document.getElementById('modalAvgSpend').innerText = '+' + formatPhp(avgSpend);
    document.getElementById('applyReason').value = `Compliance with Wage Order (${FORMULAS_CONFIG[state.activeFormulaKey]?.name})`;

    document.getElementById('applyModal').style.display = 'flex';
}

function closeApplyModal() {
    document.getElementById('applyModal').style.display = 'none';
}

async function submitApplyAdjustments(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitApply');
    btn.disabled = true;
    btn.innerHTML = `<i class="ph ph-spinner ph-spin"></i> Processing updates...`;

    const payload = {
        _token: '{{ csrf_token() }}',
        formula_key: state.activeFormulaKey,
        effective_date: document.getElementById('applyEffectiveDate').value,
        reason: document.getElementById('applyReason').value,
        params: state.params,
        adjustments: state.results.map(r => ({
            employee_id: r.employee_id,
            new_monthly_salary: r.new_monthly_salary,
            monthly_adjustment: r.monthly_adjustment,
            current_monthly_salary: r.current_monthly_salary
        }))
    };

    try {
        const res = await fetch('{{ route('hr.payroll.wage-distortion.apply') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            closeApplyModal();
            alert('Success! ' + data.message);
            window.location.reload();
        } else {
            alert('Error applying adjustments: ' + (data.message || 'Unknown error occurred'));
            btn.disabled = false;
            btn.innerHTML = `<i class="ph ph-check"></i> Confirm & Apply Adjustments`;
        }
    } catch (err) {
        alert('Network or server error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = `<i class="ph ph-check"></i> Confirm & Apply Adjustments`;
    }
}


// ---------------------------------------------------------
// 5. EXPORT CSV & PRINT COMPLIANCE REPORT
// ---------------------------------------------------------
function exportConversionCSV() {
    if (state.results.length === 0) {
        alert('No calculation results available to export. Please select employees first.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('hr.payroll.wage-distortion.export') }}';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'export_data';
    dataInput.value = JSON.stringify(state.results);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Prepare printable DOLE table before print dialog opens
window.onbeforeprint = () => {
    const printTbody = document.getElementById('printTableBody');
    if (!printTbody) return;

    document.getElementById('printFormulaName').innerText = FORMULAS_CONFIG[state.activeFormulaKey]?.name || 'Standard';
    document.getElementById('printDivisor').innerText = state.monthlyDivisor;
    document.getElementById('printEmpCount').innerText = state.results.length;

    let totalSpend = 0.0;
    state.results.forEach(r => totalSpend += r.monthly_adjustment);
    const avgSpend = state.results.length > 0 ? (totalSpend / state.results.length) : 0.0;

    document.getElementById('printTotalSpend').innerText = formatPhp(totalSpend);
    document.getElementById('printAvgSpend').innerText = formatPhp(avgSpend);

    let html = '';
    state.results.forEach(r => {
        html += `
            <tr>
                <td style="padding: 5px; text-align: center;">${r.employee_code}</td>
                <td style="padding: 5px;">${r.employee_name}</td>
                <td style="padding: 5px;">${r.department_name}</td>
                <td style="padding: 5px;">${r.position_name}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.current_monthly_salary)}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.current_daily_wage)}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.daily_adjustment)}</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">${formatPhp(r.monthly_adjustment)}</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">${formatPhp(r.new_monthly_salary)}</td>
                <td style="padding: 5px; text-align: center;">+${r.percent_increase.toFixed(2)}%</td>
            </tr>
        `;
    });
    printTbody.innerHTML = html;
};

function resetAllConverter() {
    if (confirm('Reset all parameters, formulas, and employee selections?')) {
        state.selectedEmpIds.clear();
        ALL_EMPLOYEES.forEach(emp => {
            if (!['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) {
                state.selectedEmpIds.add(emp.id);
            }
        });
        state.activeFormulaKey = 'pineda';
        state.monthlyDivisor = {{ $defaultDivisor }};
        state.params = {
            previous_min_wage: 610.00,
            mandated_increase: 35.00,
            existing_min_wage: 695.00,
            creditable_increase: 10.00,
            formula_base_range: 750.00,
            exponent: 1.20,
            percentile_weight: 85.00
        };
        resetFilters();
        selectFormula('pineda');
    }
}
</script>
@endsection
