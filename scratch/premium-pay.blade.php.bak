@extends('layouts.app')

@section('title', 'Premium Pay - Enterprise Philippine Payroll')

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openDrawer('premiumPaySettingsDrawer')" title="Configure DOLE premium pay multipliers & rules">
                <i class="ph ph-sliders" style="font-size: 16px; color: #7c3aed;"></i>
                <span>Premium Pay Settings</span>
            </button>
        </div>
    </x-slot:actions>
</x-hr-tabs>

<!-- Main Container -->
<div class="hr-page-container" style="display: flex; flex-direction: column; gap: 20px;">

    <!-- TOP SECTION: HEADER -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 20px; box-shadow: 0 3px 8px rgba(124, 58, 237, 0.25);">
                <i class="ph ph-coins"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Premium Pay</h2>
                <p style="margin: 2px 0 0; font-size: 12.5px; color: #64748b;">Review and manage premium compensation for rest days, special days, and applicable work conditions.</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; background: #f3e8ff; color: #7c3aed; padding: 4px 10px; border-radius: 20px; border: 1px solid #e9d5ff;">
                <i class="ph ph-shield-check"></i> DOLE Statutory Labor Code Compliant
            </span>
        </div>
    </div>

    <!-- 4 COMPACT SUMMARY CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <!-- Card 1: Total Premium Pay -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Total Premium Pay</span>
                <div style="font-size: 25px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format($totalPremiumPay, 2) }}</div>
                <span style="font-size: 11.5px; color: #059669; font-weight: 600;"><i class="ph ph-trend-up"></i> Approved & Pending</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-currency-circle-dollar"></i>
            </div>
        </div>

        <!-- Card 2: Premium Hours -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Premium Hours</span>
                <div style="font-size: 25px; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ number_format($totalHours, 1) }}</div>
                <span style="font-size: 11.5px; color: #7c3aed; font-weight: 600;">Special & Rest Day Hours</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-clock-user"></i>
            </div>
        </div>

        <!-- Card 3: Employees -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Employees</span>
                <div style="font-size: 25px; font-weight: 800; color: #2563eb; margin-top: 4px;">{{ $uniqueEmployees }}</div>
                <span style="font-size: 11.5px; color: #2563eb; font-weight: 600;">Recipients recorded</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-users-three"></i>
            </div>
        </div>

        <!-- Card 4: Pending Approval -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Pending Approval</span>
                <div style="font-size: 25px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ $pendingApproval }}</div>
                <span style="font-size: 11.5px; color: #d97706; font-weight: 600;">Awaiting HR review</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-hourglass-high"></i>
            </div>
        </div>
    </div>

    @php
        $activeChips = [];
        if (request('search')) {
            $activeChips[] = ['key' => 'search', 'label' => 'Search', 'val' => '"' . request('search') . '"'];
        }
        if (request('start_date') || request('end_date')) {
            $dateVal = '';
            if (request('start_date') && request('end_date')) {
                $dateVal = \Carbon\Carbon::parse(request('start_date'))->format('M d') . ' - ' . \Carbon\Carbon::parse(request('end_date'))->format('M d, Y');
            } elseif (request('start_date')) {
                $dateVal = 'From ' . \Carbon\Carbon::parse(request('start_date'))->format('M d, Y');
            } else {
                $dateVal = 'Until ' . \Carbon\Carbon::parse(request('end_date'))->format('M d, Y');
            }
            $activeChips[] = ['key' => 'date', 'label' => 'Date', 'val' => $dateVal];
        }
        if (request('payroll_period_id')) {
            $activePeriod = $periods->firstWhere('id', request('payroll_period_id'));
            if ($activePeriod) {
                $activeChips[] = ['key' => 'payroll_period_id', 'label' => 'Period', 'val' => $activePeriod->period_name];
            }
        }
        if (request('department_id')) {
            $activeDept = $departments->firstWhere('id', request('department_id'));
            if ($activeDept) {
                $activeChips[] = ['key' => 'department_id', 'label' => 'Department', 'val' => $activeDept->name];
            }
        }
        if (request('branch_id')) {
            $activeBranch = $branches->firstWhere('id', request('branch_id'));
            if ($activeBranch) {
                $activeChips[] = ['key' => 'branch_id', 'label' => 'Branch', 'val' => $activeBranch->name];
            }
        }
        if (request('status')) {
            $activeChips[] = ['key' => 'status', 'label' => 'Status', 'val' => request('status')];
        }
        if (request('premium_type')) {
            $activeChips[] = ['key' => 'premium_type', 'label' => 'Type', 'val' => request('premium_type')];
        }
        if (request('holiday_type')) {
            $activeChips[] = ['key' => 'holiday_type', 'label' => 'Holiday', 'val' => request('holiday_type')];
        }
        $activeCount = count($activeChips);
    @endphp

    <!-- FILTER TOOLBAR WITH DROPDOWN & ACTIVE PILLS -->
    <form method="GET" action="{{ route('hr.payroll.premium-pay') }}" id="premiumFilterForm" style="margin: 0; width: 100%;">
        <input type="hidden" name="per_page" id="filterPerPage" value="{{ request('per_page', 10) }}">
        <div class="pp-filter-toolbar">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex: 1;">
                <!-- Filter Dropdown Container -->
                <div class="pp-filter-dropdown-container">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnPremiumFilterDropdown" onclick="togglePremiumFilterDropdown(event)" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; padding: 6px 13px; border-radius: 8px; white-space: nowrap;">
                        <i class="ph ph-funnel" style="font-size: 14px; color: #7c3aed;"></i>
                        <span>Filter Records</span>
                        <span id="premiumActiveFilterBadge" class="hr-badge hr-badge-purple" style="{{ $activeCount > 0 ? '' : 'display: none;' }} font-size: 10px; padding: 1.5px 6px; border-radius: 9999px;">{{ $activeCount }}</span>
                        <i class="ph ph-caret-down" id="premiumFilterCaret" style="transition: transform 0.2s ease; font-size: 11px;"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="premiumFilterDropdownMenu" class="pp-filter-dropdown-menu" style="display: none;">
                        <div class="pp-filter-dropdown-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18)); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                                    <i class="ph ph-sliders-horizontal"></i>
                                </div>
                                <div>
                                    <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Filter Premium Pay</span>
                                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;">Configure query criteria</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetPremiumFilters()" style="font-size: 11px; padding: 3px 9px; color: #64748b;">
                                    <i class="ph ph-funnel-simple-x"></i> Reset All
                                </button>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closePremiumFilterDropdown()" style="font-size: 11px; padding: 3px 9px;">
                                    <i class="ph ph-x"></i> Close
                                </button>
                            </div>
                        </div>

                        <div class="pp-filter-grid">
                            <!-- Row 1, Col 1: Search Employee (1 Col) -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-magnifying-glass"></i> Search Employee (Name or ID)
                                </label>
                                <div style="position: relative; width: 100%;">
                                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
                                    <input type="text" name="search" id="filterSearch" value="{{ request('search') }}" oninput="updatePremiumFilterUI()" placeholder="Search Employee..." class="hr-input" style="padding-left: 32px; width: 100%; box-sizing: border-box;">
                                </div>
                            </div>

                            <!-- Row 1, Col 2: Date Range (Start - End) -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-calendar-blank"></i> Date Range (Start - End)
                                </label>
                                <div class="pp-date-range">
                                    <input type="date" name="start_date" id="filterStartDate" value="{{ request('start_date') }}" oninput="updatePremiumFilterUI()" onchange="updatePremiumFilterUI()" class="hr-input" title="Start Date">
                                    <span style="color: #94a3b8; font-weight: 600;">-</span>
                                    <input type="date" name="end_date" id="filterEndDate" value="{{ request('end_date') }}" oninput="updatePremiumFilterUI()" onchange="updatePremiumFilterUI()" class="hr-input" title="End Date">
                                </div>
                            </div>

                            <!-- Row 1, Col 3: Quick Date Presets -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-lightning"></i> Quick Date Presets
                                </label>
                                <div class="pp-date-presets">
                                    <button type="button" class="hr-btn" onclick="setDatePreset('today')">Today</button>
                                    <button type="button" class="hr-btn" onclick="setDatePreset('this_week')">This Week</button>
                                    <button type="button" class="hr-btn" onclick="setDatePreset('this_month')">This Month</button>
                                    <button type="button" class="hr-btn" onclick="setDatePreset('clear')">Clear</button>
                                </div>
                            </div>

                            <!-- Row 2, Col 1: Payroll Period -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-clock-clockwise"></i> Payroll Period
                                </label>
                                <select name="payroll_period_id" id="filterPayrollPeriod" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Payroll Period (All)</option>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}" {{ request('payroll_period_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->period_name }} ({{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2, Col 2: Department -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-tree-structure"></i> Department
                                </label>
                                <select name="department_id" id="filterDepartment" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Department (All)</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2, Col 3: Branch -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-storefront"></i> Branch
                                </label>
                                <select name="branch_id" id="filterBranch" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Branch (All)</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 3, Col 1: Status -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-check-circle"></i> Status
                                </label>
                                <select name="status" id="filterStatus" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Status (All)</option>
                                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>

                            <!-- Row 3, Col 2: Work / Premium Type -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-briefcase"></i> Work / Premium Type
                                </label>
                                <select name="premium_type" id="filterPremiumType" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Work / Premium Type (All)</option>
                                    <option value="Rest Day" {{ request('premium_type') == 'Rest Day' ? 'selected' : '' }}>Rest Day (130%)</option>
                                    <option value="Special Non-Working Day" {{ request('premium_type') == 'Special Non-Working Day' ? 'selected' : '' }}>Special Non-Working (130%)</option>
                                    <option value="Special Non-Working + Rest Day" {{ request('premium_type') == 'Special Non-Working + Rest Day' ? 'selected' : '' }}>Special Non-Working + Rest Day (150%)</option>
                                    <option value="Regular Holiday" {{ request('premium_type') == 'Regular Holiday' ? 'selected' : '' }}>Regular Holiday (200%)</option>
                                    <option value="Regular Holiday + Rest Day" {{ request('premium_type') == 'Regular Holiday + Rest Day' ? 'selected' : '' }}>Regular Holiday + Rest Day (260%)</option>
                                </select>
                            </div>

                            <!-- Row 3, Col 3: Holiday Type -->
                            <div>
                                <label class="pp-filter-label">
                                    <i class="ph ph-sun-horizon"></i> Holiday Type
                                </label>
                                <select name="holiday_type" id="filterHolidayType" class="hr-select" onchange="updatePremiumFilterUI()">
                                    <option value="">Holiday Type (All)</option>
                                    <option value="Regular Holiday" {{ request('holiday_type') == 'Regular Holiday' ? 'selected' : '' }}>Regular Holiday</option>
                                    <option value="Special Non-Working" {{ request('holiday_type') == 'Special Non-Working' ? 'selected' : '' }}>Special Non-Working</option>
                                    <option value="Special Working" {{ request('holiday_type') == 'Special Working' ? 'selected' : '' }}>Special Working</option>
                                    <option value="Local Holiday" {{ request('holiday_type') == 'Local Holiday' ? 'selected' : '' }}>Local Holiday</option>
                                    <option value="Company Holiday" {{ request('holiday_type') == 'Company Holiday' ? 'selected' : '' }}>Company Holiday</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dropdown Footer Actions -->
                        <div class="pp-filter-dropdown-footer">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetPremiumFilters()" style="font-size: 11.5px; padding: 6px 14px; border-radius: 8px; color: #64748b;">
                                <i class="ph ph-arrow-counterclockwise"></i> Reset All Filters
                            </button>
                            <button type="submit" class="hr-btn hr-btn-primary hr-btn-sm" style="padding: 6px 20px; font-size: 12px; font-weight: 650; border-radius: 8px; background: linear-gradient(135deg, #ec4899, #8b5cf6); border: none; color: #fff; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);">
                                <i class="ph ph-check"></i> Apply & Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Separator line (shown when pills exist) -->
                <div id="premiumFilterToolbarSeparator" style="{{ $activeCount > 0 ? '' : 'display: none;' }} width: 1px; height: 22px; background: #e2e8f0; margin: 0 4px;"></div>

                <!-- Active Filter Pills (Horizontal Row) -->
                <div id="premiumActiveFilterPillsRow" class="hr-filter-chips-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin: 0; min-height: auto;">
                    @foreach($activeChips as $chip)
                        <span class="hr-filter-tag-chip" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                            <span class="hr-chip-text" style="display: inline-flex; align-items: center; gap: 4px;">
                                <strong class="hr-chip-category">{{ $chip['label'] }}:</strong>
                                <span class="hr-chip-value">{{ $chip['val'] }}</span>
                            </span>
                            <button type="button" class="hr-chip-remove-btn" onclick="removePremiumFilter('{{ $chip['key'] }}')" title="Remove {{ $chip['label'] }} filter" aria-label="Remove filter">&times;</button>
                        </span>
                    @endforeach

                    @if($activeCount >= 2)
                        <button type="button" class="hr-clear-all-chips-btn" onclick="resetPremiumFilters()" title="Clear all filters">
                            <i class="ph ph-x"></i> Clear all
                        </button>
                    @endif
                </div>
            </div>

            <!-- Right Toolbar Status / Quick Reset -->
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto;">
                <button type="button" id="btnQuickResetPremiumFilters" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetPremiumFilters()" style="{{ $activeCount > 0 ? '' : 'display: none;' }} font-size: 11px; padding: 4px 10px; color: #64748b; border-radius: 6px;">
                    <i class="ph ph-funnel-simple-x"></i> Reset Filters
                </button>
                <span class="hr-badge hr-badge-neutral" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 11.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                    Showing <strong id="lblFilteredCountToolbar" style="color: #0f172a;">{{ $items->total() ?? count($items) }}</strong> records
                </span>
            </div>
        </div>
    </form>

    <!-- BULK ACTIONS BAR (DYNAMICALLY APPEARS WHEN RECORDS ARE CHECKED) -->
    <div id="bulkActionBar" style="display: none; background: #fdf4ff; border: 1px solid #f0abfc; border-radius: 10px; padding: 12px 18px; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 2px 4px rgba(192, 38, 211, 0.08); transition: all 0.2s ease;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #7c3aed; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700;">
                <i class="ph ph-check-square-offset"></i>
            </div>
            <div>
                <strong style="font-size: 13.5px; color: #581c87;"><span id="selectedCountBadge">0</span> Records Selected</strong>
                <span style="font-size: 12px; color: #701a75; margin-left: 6px;">Choose a batch action to apply:</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <!-- Bulk Approve Form -->
            <form method="POST" action="{{ route('hr.payroll.premium-pay.bulk-approve') }}" id="bulkApproveForm" style="display: inline;">
                @csrf
                <div id="bulkApproveInputs"></div>
                <button type="button" class="hr-btn hr-btn-sm" onclick="confirmBulkApprove()" style="background: #059669; color: #ffffff; font-weight: 600; border: none; height: 32px; padding: 0 12px; border-radius: 6px;">
                    <i class="ph ph-check"></i> ✓ Approve Selected
                </button>
            </form>

            <!-- Bulk Reject Button (Opens Modal) -->
            <button type="button" class="hr-btn hr-btn-sm" onclick="openBulkRejectModal()" style="background: #dc2626; color: #ffffff; font-weight: 600; border: none; height: 32px; padding: 0 12px; border-radius: 6px;">
                <i class="ph ph-x"></i> × Reject Selected
            </button>

            <!-- Recalculate Selected -->
            <form method="POST" action="{{ route('hr.payroll.premium-pay.recalculate') }}" id="bulkRecalculateForm" style="display: inline;">
                @csrf
                <div id="bulkRecalcInputs"></div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="confirmBulkRecalculate()" style="height: 32px; padding: 0 12px; border-radius: 6px; font-weight: 600;" title="Recompute using current DOLE rule engine">
                    <i class="ph ph-arrows-clockwise" style="color: #7c3aed;"></i> Recalculate Selected
                </button>
            </form>
        </div>
    </div>

    <!-- PREMIUM PAY TABLE -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="overflow-x: auto;">
            <table class="hr-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 12px 14px; width: 44px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="cursor: pointer; width: 16px; height: 16px;">
                        </th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Employee</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Work Type</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Holiday</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Hours</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Rate</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Premium Pay</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">Status</th>
                        <th style="padding: 12px 14px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="premiumPayTableBody">
                    @forelse($items as $item)
                        @php
                            $emp = $item->employee;
                            $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Employee #' . $item->employee_id;
                            $dept = $emp && $emp->department ? $emp->department->name : 'General';
                            $branch = $emp && $emp->branch ? $emp->branch->name : 'Main';

                            // Work type badge styling
                            $workTypeBg = '#f1f5f9';
                            $workTypeColor = '#334155';
                            if (str_contains($item->work_type, 'Regular Holiday')) {
                                $workTypeBg = '#f3e8ff';
                                $workTypeColor = '#7c3aed';
                            } elseif (str_contains($item->work_type, 'Special Non-Working')) {
                                $workTypeBg = '#fef3c7';
                                $workTypeColor = '#b45309';
                            } elseif (str_contains($item->work_type, 'Rest Day')) {
                                $workTypeBg = '#e0e7ff';
                                $workTypeColor = '#4338ca';
                            }

                            // Status badge styling
                            $statusBg = '#fef3c7';
                            $statusColor = '#b45309';
                            if ($item->status === 'Approved') {
                                $statusBg = '#ecfdf5';
                                $statusColor = '#059669';
                            } elseif ($item->status === 'Rejected') {
                                $statusBg = '#fee2e2';
                                $statusColor = '#dc2626';
                            }
                        @endphp
                        <tr class="premium-row" data-id="{{ $item->id }}" data-status="{{ $item->status }}" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background='transparent'">
                            <!-- Select Checkbox -->
                            <td style="padding: 12px 14px; text-align: center;">
                                <input type="checkbox" class="row-checkbox" value="{{ $item->id }}" onchange="handleRowSelect()" style="cursor: pointer; width: 16px; height: 16px;">
                            </td>

                            <!-- Employee Info -->
                            <td style="padding: 12px 14px;">
                                <div style="display: flex; align-items: center; gap: 9px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; flex-shrink: 0;">
                                        {{ strtoupper(substr($empName, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 500; color: #0f172a;">{{ $empName }}</div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            {{ $emp->employee_id ?? 'ID: ' . $item->employee_id }} • {{ $dept }} ({{ $branch }})
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Date -->
                            <td style="padding: 12px 14px; font-size: 12.5px; color: #1e293b; white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($item->work_date)->format('M d, Y') }}
                                <div style="font-size: 10.5px; color: #64748b;">{{ \Carbon\Carbon::parse($item->work_date)->format('l') }}</div>
                            </td>

                            <!-- Work Type -->
                            <td style="padding: 12px 14px;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 500; padding: 3px 8px; border-radius: 6px; background: {{ $workTypeBg }}; color: {{ $workTypeColor }};">
                                    <i class="ph ph-tag"></i> {{ $item->work_type }}
                                </span>
                            </td>

                            <!-- Holiday -->
                            <td style="padding: 12px 14px; font-size: 12.5px; color: #334155;">
                                @if($item->holiday_name)
                                    <div style="font-weight: 500; color: #0f172a;">{{ $item->holiday_name }}</div>
                                    <span style="font-size: 10.5px; color: #7c3aed;">{{ $item->holiday_type }}</span>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>

                            <!-- Hours -->
                            <td style="padding: 12px 14px; text-align: right; font-size: 13px; font-weight: 500; color: #0f172a;">
                                {{ number_format($item->hours_worked, 1) }} hrs
                                @if($item->overtime_hours > 0)
                                    <div style="font-size: 11px; font-weight: 500; color: #d97706;">+ {{ number_format($item->overtime_hours, 1) }}h OT</div>
                                @endif
                            </td>

                            <!-- Rate Multiplier -->
                            <td style="padding: 12px 14px; text-align: right; font-size: 13px; font-weight: 600; color: #7c3aed;">
                                {{ number_format($item->applied_multiplier * 100, 0) }}%
                            </td>

                            <!-- Premium Pay Amount (Clickable to open calculation drawer) -->
                            <td style="padding: 12px 14px; text-align: right;">
                                <button type="button" class="view-calc-btn" onclick="openCalculationDrawer({{ json_encode($item) }})" style="background: none; border: none; cursor: pointer; text-align: right; padding: 0;" title="Click to view real-time calculation breakdown">
                                    <div style="font-size: 13.5px; font-weight: 600; color: #0f172a; text-decoration: underline dotted #a855f7;">
                                        ₱{{ number_format($item->premium_amount, 2) }}
                                    </div>
                                    <span style="font-size: 10.5px; color: #7c3aed;"><i class="ph ph-calculator"></i> Breakdown</span>
                                </button>
                            </td>

                            <!-- Status -->
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 500; padding: 3px 9px; border-radius: 20px; background: {{ $statusBg }}; color: {{ $statusColor }};">
                                    @if($item->status === 'Approved')
                                        <i class="ph ph-check-circle"></i> Approved
                                    @elseif($item->status === 'Rejected')
                                        <i class="ph ph-x-circle"></i> Rejected
                                    @else
                                        <i class="ph ph-clock"></i> Pending
                                    @endif
                                </span>
                            </td>

                            <!-- Action Column -->
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                    @if($item->status === 'Pending')
                                        <!-- Approve Button -->
                                        <form method="POST" action="{{ route('hr.payroll.premium-pay.approve', $item->id) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="hr-btn hr-btn-sm" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; height: 28px; padding: 0 8px; font-size: 11.5px; font-weight: 600; border-radius: 5px;" title="Approve Record">
                                                <i class="ph ph-check"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Reject Button (Opens Modal) -->
                                        <button type="button" class="hr-btn hr-btn-sm" onclick="openRejectModal({{ $item->id }}, '{{ addslashes($empName) }}', '{{ \Carbon\Carbon::parse($item->work_date)->format('M d, Y') }}')" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; height: 28px; padding: 0 8px; font-size: 11.5px; font-weight: 600; border-radius: 5px;" title="Reject Record">
                                            <i class="ph ph-x"></i> Reject
                                        </button>
                                    @elseif($item->status === 'Approved')
                                        <span style="font-size: 11.5px; font-weight: 600; color: #059669; display: inline-flex; align-items: center; gap: 4px;" title="Approved on {{ $item->approved_at ? \Carbon\Carbon::parse($item->approved_at)->format('M d, Y h:i A') : 'N/A' }}">
                                            <i class="ph ph-check"></i> Approved
                                        </span>
                                    @else
                                        <span style="font-size: 11.5px; font-weight: 600; color: #dc2626; display: inline-flex; align-items: center; gap: 4px;" title="Reason: {{ $item->rejection_reason ?? 'No reason provided' }}">
                                            <i class="ph ph-x"></i> Rejected
                                        </span>
                                    @endif

                                    <!-- Details Inspector Button -->
                                    <button type="button" class="icon-btn" onclick="openCalculationDrawer({{ json_encode($item) }})" style="width: 28px; height: 28px; border-radius: 5px; color: #64748b;" title="View Real-Time Calculation Details">
                                        <i class="ph ph-eye" style="font-size: 14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="padding: 40px; text-align: center; color: #94a3b8;">
                                <i class="ph ph-coins" style="font-size: 38px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                <span style="font-size: 14px; font-weight: 600; color: #64748b;">No premium pay items found</span>
                                <p style="font-size: 12px; margin: 4px 0 0; color: #94a3b8;">No rest day or special holiday work records match the selected filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->total() > 0)
            <div class="hr-table-footer" style="padding: 12px 18px; border-top: 1px solid #e2e8f0;">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ======================================================== -->
<!-- REAL-TIME PREMIUM CALCULATION DRAWER                     -->
<!-- ======================================================== -->
<div id="calculationDrawer" class="hr-modal-overlay" style="align-items: stretch; justify-content: flex-end; padding: 0;">
    <div style="background: #ffffff; width: 100%; max-width: 520px; height: 100vh; overflow-y: auto; box-shadow: -4px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; animation: slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Drawer Header -->
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-calculator"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Premium Pay Calculation</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Detailed statutory breakdown & equation tracing</p>
                </div>
            </div>
            <button class="icon-btn" onclick="closeDrawer('calculationDrawer')" style="width: 32px; height: 32px; border-radius: 6px;">
                <i class="ph ph-x" style="font-size: 16px;"></i>
            </button>
        </div>

        <!-- Drawer Content -->
        <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; gap: 20px;">
            <!-- Employee & Rate Header Card -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Employee</span>
                        <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px;" id="drawerEmpName">Juan Dela Cruz</div>
                        <div style="font-size: 12px; color: #64748b;" id="drawerEmpSub">EMP-001 • Service Staff</div>
                    </div>
                    <span id="drawerStatusBadge" style="font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: #fef3c7; color: #b45309;">
                        Pending
                    </span>
                </div>

                <div style="margin-top: 14px; pt-3; border-top: 1px dashed #cbd5e1; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-top: 12px;">
                    <div>
                        <span style="font-size: 11px; color: #64748b;">Basic Daily Rate</span>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;" id="drawerDailyRate">₱800.00</div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b;">Hourly Equivalent</span>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;" id="drawerHourlyRate">₱100.00 / hr</div>
                    </div>
                </div>
            </div>

            <!-- Work Condition Info -->
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                    Work Event & Context
                </h4>
                <div style="display: flex; flex-direction: column; gap: 9px; font-size: 12.5px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Work Date:</span>
                        <strong style="color: #0f172a;" id="drawerWorkDate">August 21, 2027</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Holiday Name:</span>
                        <strong style="color: #7c3aed;" id="drawerHolidayName">Ninoy Aquino Day</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Holiday Type:</span>
                        <strong style="color: #0f172a;" id="drawerHolidayType">Special Non-Working</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Employee Rest Day:</span>
                        <strong style="color: #059669;" id="drawerRestDay">Yes</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Total Hours Worked:</span>
                        <strong style="color: #0f172a;" id="drawerHoursWorked">8.0 hrs</strong>
                    </div>
                </div>
            </div>

            <!-- Classification & Multiplier Card -->
            <div style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 1px solid #e9d5ff; border-radius: 10px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #7c3aed; letter-spacing: 0.5px;">Applied Classification</span>
                        <div style="font-size: 15px; font-weight: 800; color: #581c87; margin-top: 2px;" id="drawerClassification">
                            Special Non-Working + Rest Day
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: #6b21a8; font-weight: 600;">Statutory Multiplier</span>
                        <div style="font-size: 20px; font-weight: 800; color: #7c3aed;" id="drawerMultiplier">150%</div>
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 11.5px; color: #6b21a8; background: #ffffff; padding: 8px 12px; border-radius: 6px; border: 1px solid #f0abfc;" id="drawerFormulaEquation">
                    Calculation: ₱800.00 × 150%
                </div>
            </div>

            <!-- OVERTIME SEPARATION BREAKDOWN -->
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                        DOLE Statutory Rate Breakdown
                    </h4>
                    <span style="font-size: 10.5px; background: #fee2e2; color: #b91c1c; font-weight: 600; padding: 2px 7px; border-radius: 4px;">
                        Strict DOLE Separation
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12.5px;">
                    <!-- Regular Hours Row -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 6px;">
                        <div>
                            <strong style="color: #0f172a;">Regular Hours (<span id="drawerRegHours">8.0</span> hrs)</strong>
                            <div style="font-size: 11px; color: #64748b;" id="drawerRegRateNote">Base Day Multiplier: 150%</div>
                        </div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;" id="drawerRegAmount">
                            ₱1,200.00
                        </div>
                    </div>

                    <!-- Overtime Hours Row -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px;">
                        <div>
                            <strong style="color: #b45309;">Overtime (<span id="drawerOtHours">0.0</span> hrs)</strong>
                            <div style="font-size: 11px; color: #92400e;" id="drawerOtRateNote">Hourly Rate × Multiplier × 130%</div>
                        </div>
                        <div style="font-size: 14px; font-weight: 700; color: #b45309;" id="drawerOtAmount">
                            ₱0.00
                        </div>
                    </div>

                    <!-- Total Row -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 10px; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; margin-top: 4px;">
                        <div>
                            <strong style="font-size: 13.5px; color: #581c87;">Total Premium Pay</strong>
                            <div style="font-size: 11px; color: #7c3aed;">DOLE compliant net compensation</div>
                        </div>
                        <div style="font-size: 18px; font-weight: 650; color: #7c3aed;" id="drawerTotalPremium">
                            ₱1,200.00
                        </div>
                    </div>
                </div>

                <p style="margin: 12px 0 0; font-size: 11px; color: #94a3b8; font-style: italic;">
                    * DOLE Labor Code Mandate: Regular premium and overtime hours are calculated as separate components and never combined into a single flat multiplier.
                </p>
            </div>

            <!-- Audit Trail & Version Note -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 11.5px; color: #64748b;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Rule Engine Version:</span>
                    <strong style="color: #334155;" id="drawerRuleVersion">v2026.1 (Statutory Default)</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Calculation Signature:</span>
                    <span style="color: #64748b; font-family: monospace;" id="drawerCalcHash">PPR-STATUTORY-OK</span>
                </div>
            </div>
        </div>

        <!-- Drawer Footer -->
        <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="closeDrawer('calculationDrawer')">Close</button>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: REJECT SINGLE PREMIUM PAY RECORD                  -->
<!-- ======================================================== -->
<div id="rejectModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title" style="color: #dc2626;"><i class="ph ph-warning-circle"></i> Reject Premium Pay</span>
            <button class="icon-btn" onclick="closeModal('rejectModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" id="singleRejectForm" action="">
            @csrf
            <div class="hr-modal-body">
                <p style="font-size: 13px; color: #334155; margin: 0 0 12px;">
                    You are rejecting the premium pay claim for <strong id="rejectModalEmpName">Employee</strong> on <strong id="rejectModalDate">Date</strong>.
                </p>

                <div class="hr-form-group">
                    <label class="hr-form-label">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="reason" id="singleRejectReason" class="hr-input" rows="3" placeholder="Enter reason (e.g. Schedule was swapped, rest day was unapproved, attendance adjustment pending)..." required></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="hr-btn" style="background: #dc2626; color: #ffffff; font-weight: 600;">Reject</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: BULK REJECT                                       -->
<!-- ======================================================== -->
<div id="bulkRejectModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title" style="color: #dc2626;"><i class="ph ph-warning-circle"></i> Reject Selected Records</span>
            <button class="icon-btn" onclick="closeModal('bulkRejectModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.premium-pay.bulk-reject') }}" id="bulkRejectForm">
            @csrf
            <div id="bulkRejectHiddenInputs"></div>
            <div class="hr-modal-body">
                <p style="font-size: 13px; color: #334155; margin: 0 0 12px;">
                    You are about to reject <strong id="bulkRejectCountText">0</strong> selected premium pay records.
                </p>

                <div class="hr-form-group">
                    <label class="hr-form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="hr-input" rows="3" placeholder="Enter reason for batch rejection..." required></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('bulkRejectModal')">Cancel</button>
                <button type="submit" class="hr-btn" style="background: #dc2626; color: #ffffff; font-weight: 600;">Reject Selected</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- DRAWER: PREMIUM PAY SETTINGS                             -->
<!-- ======================================================== -->
<div id="premiumPaySettingsDrawer" class="hr-modal-overlay" style="align-items: stretch; justify-content: flex-end; padding: 0;">
    <div style="background: #ffffff; width: 100%; max-width: 580px; height: 100vh; overflow-y: auto; box-shadow: -4px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; animation: slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Drawer Header -->
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-sliders"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Premium Pay Settings</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">DOLE statutory multipliers, CBA overrides, & policy configuration</p>
                </div>
            </div>
            <button class="icon-btn" onclick="closeDrawer('premiumPaySettingsDrawer')" style="width: 32px; height: 32px; border-radius: 6px;">
                <i class="ph ph-x" style="font-size: 16px;"></i>
            </button>
        </div>

        <!-- Drawer Content Form -->
        <form method="POST" action="{{ route('hr.payroll.premium-pay.settings') }}" style="display: flex; flex-direction: column; flex: 1;">
            @csrf
            <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; gap: 20px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #475569;">
                    <i class="ph ph-info" style="color: #7c3aed; font-size: 15px;"></i>
                    Configure rates per DOLE Labor Code standards. Any modification creates an immutable audit trail and will apply to future calculations.
                </div>

                <!-- Scope / Rule Type & Effective Dates -->
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 700; color: #334155;">General Policy Settings</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">Rule Type</label>
                            <select name="rule_type" class="hr-select">
                                <option value="Statutory Default" selected>Statutory Default (DOLE)</option>
                                <option value="Company Policy">Company Policy</option>
                                <option value="CBA Override">CBA Override</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Effective From</label>
                            <input type="date" name="effective_from" value="{{ date('Y-01-01') }}" class="hr-input">
                        </div>
                    </div>
                </div>

                <!-- Multiplier Rules Sections -->
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- 1. Rest Day -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">Rest Day</strong>
                                <div style="font-size: 11.5px; color: #64748b;">Statutory standard: 130%</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #475569;">Base Multiplier:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="rest_day_multiplier" value="130" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Special Non-Working -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">Special Non-Working</strong>
                                <div style="font-size: 11.5px; color: #64748b;">Statutory standard: 130%</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #475569;">Base Multiplier:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="special_day_multiplier" value="130" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Special Non-Working + Rest Day -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; background: #faf5ff;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #581c87;">Special Non-Working + Rest Day</strong>
                                <div style="font-size: 11.5px; color: #7c3aed;">Statutory standard: 150%</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #581c87;">Base Multiplier:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="special_day_rest_day_multiplier" value="150" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Regular Holiday -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">Regular Holiday</strong>
                                <div style="font-size: 11.5px; color: #64748b;">Statutory standard: 200%</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #475569;">Base Multiplier:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="regular_holiday_multiplier" value="200" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Regular Holiday + Rest Day -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; background: #faf5ff;">
                        <div>
                            <strong style="font-size: 13px; color: #581c87;">Regular Holiday + Rest Day</strong>
                            <div style="font-size: 11.5px; color: #7c3aed;">Statutory standard: 200% × 130% = 260%</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Holiday Rate:</label>
                                <div style="position: relative; width: 90px;">
                                    <input type="number" step="1" name="reg_hol_rest_day_holiday_multiplier" value="200" class="hr-input" style="padding-right: 24px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Rest Day Mult:</label>
                                <div style="position: relative; width: 90px;">
                                    <input type="number" step="1" name="reg_hol_rest_day_rest_multiplier" value="130" class="hr-input" style="padding-right: 24px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Overtime -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">Overtime Rate Multiplier</strong>
                                <div style="font-size: 11.5px; color: #64748b;">Applied to work beyond 8 hours on rest/special days</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #475569;">OT Rate:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="overtime_multiplier" value="130" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Night Shift Differential -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">Night Shift Differential (NSD)</strong>
                                <div style="font-size: 11.5px; color: #64748b;">Hours between 10:00 PM and 6:00 AM (110%)</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 12px; font-weight: 600; color: #475569;">NSD Rate:</label>
                                <div style="position: relative; width: 110px;">
                                    <input type="number" step="1" name="night_shift_multiplier" value="110" class="hr-input" style="padding-right: 28px; text-align: right; font-weight: 700;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" id="settingsIsActive" value="1" checked style="width: 16px; height: 16px;">
                    <label for="settingsIsActive" style="font-size: 13px; font-weight: 600; color: #0f172a; cursor: pointer;">
                        Active and Enforce in Central PremiumPayRuleEngine
                    </label>
                </div>
            </div>

            <!-- Drawer Footer -->
            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeDrawer('premiumPaySettingsDrawer')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                    Save Premium Pay Settings
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

/* Modal and Drawer Overlay Visibility */
.hr-modal-overlay.open,
.hr-modal-overlay.active {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    display: flex !important;
}

.hr-modal-overlay.open > div,
.hr-modal-overlay.active > div {
    pointer-events: auto !important;
}

/* Filter Dropdown & Toolbar */
.pp-filter-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    min-height: 48px;
    box-sizing: border-box;
    margin-bottom: 8px;
}

.pp-filter-dropdown-container {
    position: relative;
    display: inline-block;
}

.pp-filter-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 1000;
    width: 820px;
    max-width: min(820px, calc(100vw - 40px));
    max-height: 85vh;
    overflow-y: auto;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 16px;
    box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.22), 0 8px 24px rgba(124, 58, 237, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    padding: 20px;
    box-sizing: border-box;
    animation: ppDropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ppDropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.pp-filter-dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 12px;
    margin-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.pp-filter-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 650;
    color: #334155;
    margin-bottom: 5px;
    height: 18px;
    line-height: 18px;
    white-space: nowrap;
}

.pp-filter-label i {
    color: #7c3aed;
    font-size: 13.5px;
    flex-shrink: 0;
}

.pp-filter-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 14px 16px !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.pp-filter-grid > div {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.pp-filter-grid .hr-input,
.pp-filter-grid .hr-select {
    width: 100% !important;
    height: 36px !important;
    box-sizing: border-box !important;
    font-size: 12px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    background: #ffffff !important;
    color: #0f172a !important;
    transition: all 0.2s ease !important;
}

.pp-filter-grid .hr-input:focus,
.pp-filter-grid .hr-select:focus {
    border-color: #9333ea !important;
    box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.12) !important;
    outline: none !important;
}

/* Date Range: Two inputs divided evenly with a dash */
.pp-date-range {
    display: flex;
    align-items: center;
    gap: 6px;
    width: 100%;
    height: 36px;
    box-sizing: border-box;
}

.pp-date-range .hr-input {
    width: calc(50% - 7px) !important;
    flex: 1 1 0 !important;
    min-width: 0 !important;
    height: 36px !important;
    padding: 0 8px !important;
    text-align: left;
}

/* Quick Date Presets Buttons */
.pp-date-presets {
    display: flex;
    align-items: center;
    gap: 4px;
    width: 100%;
    height: 36px;
    box-sizing: border-box;
}

.pp-date-presets button {
    flex: 1 1 0;
    min-width: 0;
    height: 36px !important;
    padding: 0 2px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    background: #f8fafc !important;
    color: #475569 !important;
    white-space: nowrap !important;
    cursor: pointer;
    transition: all 0.15s ease !important;
}

.pp-date-presets button:hover {
    background: #f1f5f9 !important;
    border-color: #9333ea !important;
    color: #9333ea !important;
}

.pp-filter-dropdown-footer {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

/* Filter Tags / Active Chips */
.hr-filter-tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #1e293b;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(253, 242, 248, 0.85) 45%, rgba(243, 232, 255, 0.85) 100%);
    backdrop-filter: blur(14px) saturate(180%);
    -webkit-backdrop-filter: blur(14px) saturate(180%);
    border: 1px solid rgba(168, 85, 247, 0.35);
    border-radius: 9999px;
    padding: 3px 10px;
    line-height: 1.4;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 8px rgba(168, 85, 247, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    user-select: none;
}

.hr-filter-tag-chip:hover {
    border-color: #ec4899;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(236, 72, 153, 0.16);
}

.hr-chip-category {
    font-weight: 700;
    background: linear-gradient(135deg, #ec4899 0%, #9333ea 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 11px;
}

.hr-chip-value {
    color: #0f172a;
    font-weight: 600;
    font-size: 11.5px;
}

.hr-chip-remove-btn {
    cursor: pointer;
    color: #94a3b8;
    font-size: 15px;
    font-weight: 700;
    line-height: 1;
    background: none;
    border: none;
    padding: 0;
    width: 17px;
    height: 17px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.15s ease;
    margin-left: 2px;
}

.hr-chip-remove-btn:hover {
    color: #ffffff;
    background: linear-gradient(135deg, #ec4899, #db2777);
    box-shadow: 0 2px 6px rgba(236, 72, 153, 0.4);
}

.hr-clear-all-chips-btn {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.08) 0%, rgba(168, 85, 247, 0.12) 100%);
    border: 1px solid rgba(168, 85, 247, 0.28);
    color: #9333ea;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 9999px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none;
    transition: all 0.15s ease;
}

.hr-clear-all-chips-btn:hover {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.15) 0%, rgba(168, 85, 247, 0.22) 100%);
    color: #7c3aed;
    border-color: #ec4899;
}

@media (max-width: 900px) {
    .pp-filter-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .pp-filter-grid {
        grid-template-columns: 1fr !important;
    }
    .pp-filter-dropdown-menu {
        width: calc(100vw - 32px);
    }
}
</style>

<script>
// Filter Dropdown & Active Pills Handler
function togglePremiumFilterDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('premiumFilterDropdownMenu');
    const caret = document.getElementById('premiumFilterCaret');
    if (!menu) return;
    const isHidden = (menu.style.display === 'none' || menu.style.display === '');
    menu.style.display = isHidden ? 'block' : 'none';
    if (caret) caret.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
}

function closePremiumFilterDropdown() {
    const menu = document.getElementById('premiumFilterDropdownMenu');
    const caret = document.getElementById('premiumFilterCaret');
    if (menu) menu.style.display = 'none';
    if (caret) caret.style.transform = 'rotate(0deg)';
}

document.addEventListener('click', function(e) {
    const container = document.querySelector('.pp-filter-dropdown-container');
    const menu = document.getElementById('premiumFilterDropdownMenu');
    if (container && menu && !container.contains(e.target)) {
        closePremiumFilterDropdown();
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatDateShort(dateStr) {
    if (!dateStr) return '';
    try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const m = months[parseInt(parts[1], 10) - 1];
            return `${m} ${parseInt(parts[2], 10)}, ${parts[0]}`;
        }
    } catch(e) {}
    return dateStr;
}

function updatePremiumFilterUI() {
    const search = (document.getElementById('filterSearch')?.value || '').trim();
    const startDate = document.getElementById('filterStartDate')?.value || '';
    const endDate = document.getElementById('filterEndDate')?.value || '';
    const periodSelect = document.getElementById('filterPayrollPeriod');
    const deptSelect = document.getElementById('filterDepartment');
    const branchSelect = document.getElementById('filterBranch');
    const statusSelect = document.getElementById('filterStatus');
    const typeSelect = document.getElementById('filterPremiumType');
    const holidaySelect = document.getElementById('filterHolidayType');

    const chips = [];

    if (search) {
        chips.push({ key: 'search', label: 'Search', val: `"${search}"` });
    }

    if (startDate || endDate) {
        let dateVal = '';
        if (startDate && endDate) {
            dateVal = `${formatDateShort(startDate)} - ${formatDateShort(endDate)}`;
        } else if (startDate) {
            dateVal = `From ${formatDateShort(startDate)}`;
        } else {
            dateVal = `Until ${formatDateShort(endDate)}`;
        }
        chips.push({ key: 'date', label: 'Date', val: dateVal });
    }

    if (periodSelect && periodSelect.value && periodSelect.selectedIndex > 0) {
        const text = periodSelect.options[periodSelect.selectedIndex].text.split('(')[0].trim();
        chips.push({ key: 'payroll_period_id', label: 'Period', val: text });
    }

    if (deptSelect && deptSelect.value && deptSelect.selectedIndex > 0) {
        const text = deptSelect.options[deptSelect.selectedIndex].text.trim();
        chips.push({ key: 'department_id', label: 'Department', val: text });
    }

    if (branchSelect && branchSelect.value && branchSelect.selectedIndex > 0) {
        const text = branchSelect.options[branchSelect.selectedIndex].text.trim();
        chips.push({ key: 'branch_id', label: 'Branch', val: text });
    }

    if (statusSelect && statusSelect.value) {
        chips.push({ key: 'status', label: 'Status', val: statusSelect.value });
    }

    if (typeSelect && typeSelect.value) {
        chips.push({ key: 'premium_type', label: 'Type', val: typeSelect.value });
    }

    if (holidaySelect && holidaySelect.value) {
        chips.push({ key: 'holiday_type', label: 'Holiday', val: holidaySelect.value });
    }

    // Update badge counter
    const badge = document.getElementById('premiumActiveFilterBadge');
    if (badge) {
        if (chips.length > 0) {
            badge.textContent = chips.length;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Update separator
    const sep = document.getElementById('premiumFilterToolbarSeparator');
    if (sep) {
        sep.style.display = chips.length > 0 ? 'block' : 'none';
    }

    // Update quick reset button on right
    const quickReset = document.getElementById('btnQuickResetPremiumFilters');
    if (quickReset) {
        quickReset.style.display = chips.length > 0 ? 'inline-flex' : 'none';
    }

    // Render pills
    renderPremiumFilterPills(chips);
}

function renderPremiumFilterPills(chips) {
    const row = document.getElementById('premiumActiveFilterPillsRow');
    if (!row) return;

    if (!chips || chips.length === 0) {
        row.innerHTML = '';
        return;
    }

    let html = '';
    chips.forEach(chip => {
        html += `
            <span class="hr-filter-tag-chip" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                <span class="hr-chip-text" style="display: inline-flex; align-items: center; gap: 4px;">
                    <strong class="hr-chip-category">${escapeHtml(chip.label)}:</strong>
                    <span class="hr-chip-value">${escapeHtml(chip.val)}</span>
                </span>
                <button type="button" class="hr-chip-remove-btn" onclick="removePremiumFilter('${chip.key}')" title="Remove ${escapeHtml(chip.label)} filter" aria-label="Remove filter">&times;</button>
            </span>
        `;
    });

    if (chips.length >= 2) {
        html += `
            <button type="button" class="hr-clear-all-chips-btn" onclick="resetPremiumFilters()" title="Clear all filters">
                <i class="ph ph-x"></i> Clear all
            </button>
        `;
    }

    row.innerHTML = html;
}

function removePremiumFilter(key) {
    if (key === 'search') {
        const el = document.getElementById('filterSearch');
        if (el) el.value = '';
    } else if (key === 'date') {
        const s = document.getElementById('filterStartDate');
        const e = document.getElementById('filterEndDate');
        if (s) s.value = '';
        if (e) e.value = '';
    } else if (key === 'payroll_period_id') {
        const el = document.getElementById('filterPayrollPeriod');
        if (el) el.value = '';
    } else if (key === 'department_id') {
        const el = document.getElementById('filterDepartment');
        if (el) el.value = '';
    } else if (key === 'branch_id') {
        const el = document.getElementById('filterBranch');
        if (el) el.value = '';
    } else if (key === 'status') {
        const el = document.getElementById('filterStatus');
        if (el) el.value = '';
    } else if (key === 'premium_type') {
        const el = document.getElementById('filterPremiumType');
        if (el) el.value = '';
    } else if (key === 'holiday_type') {
        const el = document.getElementById('filterHolidayType');
        if (el) el.value = '';
    }
    
    updatePremiumFilterUI();

    const hasQuery = window.location.search && window.location.search.length > 1;
    if (hasQuery) {
        document.getElementById('premiumFilterForm').submit();
    }
}

function resetPremiumFilters() {
    const search = document.getElementById('filterSearch');
    const start = document.getElementById('filterStartDate');
    const end = document.getElementById('filterEndDate');
    const period = document.getElementById('filterPayrollPeriod');
    const dept = document.getElementById('filterDepartment');
    const branch = document.getElementById('filterBranch');
    const status = document.getElementById('filterStatus');
    const pType = document.getElementById('filterPremiumType');
    const hType = document.getElementById('filterHolidayType');

    if (search) search.value = '';
    if (start) start.value = '';
    if (end) end.value = '';
    if (period) period.value = '';
    if (dept) dept.value = '';
    if (branch) branch.value = '';
    if (status) status.value = '';
    if (pType) pType.value = '';
    if (hType) hType.value = '';

    updatePremiumFilterUI();

    const hasQuery = window.location.search && window.location.search.length > 1;
    if (hasQuery) {
        window.location.href = "{{ route('hr.payroll.premium-pay') }}";
    }
}

function setDatePreset(type) {
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');
    if (!startInput || !endInput) return;
    
    const today = new Date();
    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (type === 'today') {
        const tStr = formatDate(today);
        startInput.value = tStr;
        endInput.value = tStr;
    } else if (type === 'this_week') {
        const dayOfWeek = today.getDay(); // 0 is Sunday
        const monday = new Date(today);
        monday.setDate(today.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        startInput.value = formatDate(monday);
        endInput.value = formatDate(sunday);
    } else if (type === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startInput.value = formatDate(firstDay);
        endInput.value = formatDate(lastDay);
    } else if (type === 'clear') {
        startInput.value = '';
        endInput.value = '';
    }

    updatePremiumFilterUI();
}

document.addEventListener('DOMContentLoaded', function() {
    updatePremiumFilterUI();
});
// Modal / Drawer Helper
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        modal.classList.add('active');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
        modal.classList.remove('active');
    }
}

function openDrawer(id) {
    const drawer = document.getElementById(id);
    if (drawer) {
        drawer.classList.add('open');
        drawer.classList.add('active');
    }
}

function closeDrawer(id) {
    const drawer = document.getElementById(id);
    if (drawer) {
        drawer.classList.remove('open');
        drawer.classList.remove('active');
    }
}

// Close on backdrop click
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('hr-modal-overlay')) {
        closeDrawer(e.target.id);
        closeModal(e.target.id);
    }
});

// Select All & Row Selection handling
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    handleRowSelect();
}

function handleRowSelect() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulkActionBar');
    const badge = document.getElementById('selectedCountBadge');

    if (count > 0) {
        bulkBar.style.display = 'flex';
        badge.innerText = count;
    } else {
        bulkBar.style.display = 'none';
        badge.innerText = '0';
        const master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
    }
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// Bulk Actions
function confirmBulkApprove() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    if (!confirm(`Are you sure you want to approve ${ids.length} selected premium pay record(s)?`)) {
        return;
    }

    const container = document.getElementById('bulkApproveInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    document.getElementById('bulkApproveForm').submit();
}

function openBulkRejectModal() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    document.getElementById('bulkRejectCountText').innerText = ids.length;

    const container = document.getElementById('bulkRejectHiddenInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    openModal('bulkRejectModal');
}

function confirmBulkRecalculate() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    const container = document.getElementById('bulkRecalcInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    document.getElementById('bulkRecalculateForm').submit();
}

// Single Reject Modal
function openRejectModal(id, empName, workDate) {
    document.getElementById('rejectModalEmpName').innerText = empName;
    document.getElementById('rejectModalDate').innerText = workDate;
    document.getElementById('singleRejectForm').action = `{{ url('hr/payroll/premium-pay') }}/${id}/reject`;
    document.getElementById('singleRejectReason').value = '';
    openModal('rejectModal');
}

// Real-Time Calculation Drawer Inspector
function openCalculationDrawer(item) {
    const emp = item.employee || {};
    const empName = emp.first_name ? `${emp.first_name} ${emp.last_name}` : `Employee #${item.employee_id}`;
    const empSub = `${emp.employee_id || 'EMP'} • ${emp.position ? emp.position.title : 'Staff'}`;

    document.getElementById('drawerEmpName').innerText = empName;
    document.getElementById('drawerEmpSub').innerText = empSub;

    // Status Badge
    const badge = document.getElementById('drawerStatusBadge');
    badge.innerText = item.status;
    if (item.status === 'Approved') {
        badge.style.background = '#ecfdf5';
        badge.style.color = '#059669';
    } else if (item.status === 'Rejected') {
        badge.style.background = '#fee2e2';
        badge.style.color = '#dc2626';
    } else {
        badge.style.background = '#fef3c7';
        badge.style.color = '#b45309';
    }

    // Daily & Hourly Rates
    const dailyRate = parseFloat(item.base_daily_rate || 800);
    const hourlyRate = parseFloat(item.base_hourly_rate || (dailyRate / 8));
    document.getElementById('drawerDailyRate').innerText = `₱${dailyRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerHourlyRate').innerText = `₱${hourlyRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / hr`;

    // Date & Context
    const d = new Date(item.work_date);
    const dateFormatted = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    document.getElementById('drawerWorkDate').innerText = dateFormatted;
    document.getElementById('drawerHolidayName').innerText = item.holiday_name || 'None (Regular Day)';
    document.getElementById('drawerHolidayType').innerText = item.holiday_type || '—';
    document.getElementById('drawerRestDay').innerText = item.is_rest_day ? 'Yes' : 'No';

    const totalHours = parseFloat(item.hours_worked || 8);
    const otHours = parseFloat(item.overtime_hours || 0);
    const regHours = Math.max(0, totalHours - otHours);

    document.getElementById('drawerHoursWorked').innerText = `${totalHours.toFixed(1)} hrs`;
    document.getElementById('drawerRegHours').innerText = regHours.toFixed(1);
    document.getElementById('drawerOtHours').innerText = otHours.toFixed(1);

    // Classification & Multipliers
    document.getElementById('drawerClassification').innerText = item.work_type || 'Rest Day';
    const multPercent = Math.round(parseFloat(item.applied_multiplier || 1.3) * 100);
    document.getElementById('drawerMultiplier').innerText = `${multPercent}%`;

    // Formula equation
    let equation = `₱${dailyRate.toFixed(2)} × ${multPercent}%`;
    document.getElementById('drawerFormulaEquation').innerText = `Calculation: ${equation}`;

    // DOLE Separate Breakdown
    const regularRateMult = parseFloat(item.applied_multiplier || 1.3);
    const regPremiumPay = (hourlyRate * regularRateMult * regHours);
    const otMultiplier = parseFloat(item.overtime_multiplier || 1.3);
    const otPremiumPay = (hourlyRate * regularRateMult * otMultiplier * otHours);
    const totalAmount = parseFloat(item.premium_amount || (regPremiumPay + otPremiumPay));

    document.getElementById('drawerRegAmount').innerText = `₱${regPremiumPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerRegRateNote').innerText = `Base Day Multiplier: ${multPercent}% (${regHours}h × ₱${(hourlyRate * regularRateMult).toFixed(2)})`;

    document.getElementById('drawerOtAmount').innerText = `₱${otPremiumPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerOtRateNote').innerText = otHours > 0
        ? `₱${hourlyRate.toFixed(2)}/hr × ${multPercent}% × 130% OT (${otHours}h)`
        : 'No overtime hours on this shift';

    document.getElementById('drawerTotalPremium').innerText = `₱${totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    // Breakdown details from breakdown_json if present
    if (item.breakdown_json) {
        try {
            const b = typeof item.breakdown_json === 'string' ? JSON.parse(item.breakdown_json) : item.breakdown_json;
            if (b.rule_version) {
                document.getElementById('drawerRuleVersion').innerText = b.rule_version;
            }
        } catch(e) {}
    }

    openDrawer('calculationDrawer');
}
</script>
@endsection
