@extends('layouts.app')

@section('title', 'Holidays Management - Enterprise Philippine Payroll')

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('importHolidayModal')" title="Import standard Philippine holiday calendar">
                <i class="ph ph-calendar-plus" style="font-size: 16px; color: #7c3aed;"></i>
                <span>Import Calendar</span>
            </button>
            <a href="{{ route('hr.payroll.holidays.export', ['year' => $year]) }}" class="hr-btn hr-btn-secondary" title="Export holidays to CSV">
                <i class="ph ph-export" style="font-size: 16px; color: #2563eb;"></i>
                <span>Export</span>
            </a>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('holidaySettingsModal')" title="Configure statutory holiday pay rules">
                <i class="ph ph-sliders" style="font-size: 16px; color: #d97706;"></i>
                <span>Holiday Settings</span>
            </button>
            <button type="button" class="hr-btn hr-btn-primary" onclick="openModal('addHolidayModal')" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                <i class="ph ph-plus-circle" style="font-size: 16px;"></i>
                <span>+ Add Holiday</span>
            </button>
        </div>
    </x-slot:actions>
</x-hr-tabs>

<!-- Main Container -->
<div class="hr-page-container" style="display: flex; flex-direction: column; gap: 20px;">

    <!-- TOP SECTION: HEADER & YEAR SELECTOR -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 20px; box-shadow: 0 3px 8px rgba(124, 58, 237, 0.25);">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Holidays</h2>
                    <p style="margin: 2px 0 0; font-size: 12.5px; color: #64748b;">Manage Philippine holidays, special working days, and holiday pay rules.</p>
                </div>
            </div>
        </div>

        <!-- Year Selector with [ ← ] Year [ → ] and Dropdown -->
        <div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 4px 8px;">
            <a href="{{ route('hr.payroll.holidays', ['year' => max(2020, $year - 1)]) }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="height: 30px; width: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Previous Year">
                <i class="ph ph-caret-left" style="font-size: 15px;"></i>
            </a>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 15px; font-weight: 700; color: #7c3aed; min-width: 48px; text-align: center;">{{ $year }}</span>
                <select id="headerYearSelector" onchange="window.location.href = '{{ route('hr.payroll.holidays') }}?year=' + this.value" style="border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; font-weight: 600; color: #334155; padding: 4px 8px; background: #ffffff; cursor: pointer;">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }} Calendar</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('hr.payroll.holidays', ['year' => min(2040, $year + 1)]) }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="height: 30px; width: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Next Year">
                <i class="ph ph-caret-right" style="font-size: 15px;"></i>
            </a>
        </div>
    </div>

    <!-- HOLIDAY SUMMARY CARDS (DYNAMIC) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <!-- Card 1: Total Holidays -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Total Holidays</span>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px;" id="kpiTotalHolidays">{{ $totalHolidays }}</div>
                <span style="font-size: 11.5px; color: #7c3aed; font-weight: 600;">Calendar Year {{ $year }}</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-calendar-blank"></i>
            </div>
        </div>

        <!-- Card 2: Regular Holidays -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Regular Holidays</span>
                <div style="font-size: 26px; font-weight: 800; color: #7c3aed; margin-top: 4px;" id="kpiRegularHolidays">{{ $regularHolidays }}</div>
                <span style="font-size: 11.5px; color: #059669; font-weight: 600;">200% worked rate</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-star"></i>
            </div>
        </div>

        <!-- Card 3: Special Non-Working -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Special Non-Working</span>
                <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;" id="kpiSpecialNonWorking">{{ $specialNonWorking }}</div>
                <span style="font-size: 11.5px; color: #d97706; font-weight: 600;">130% worked rate</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-sun"></i>
            </div>
        </div>

        <!-- Card 4: Special Working -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Special Working</span>
                <div style="font-size: 26px; font-weight: 800; color: #2563eb; margin-top: 4px;" id="kpiSpecialWorking">{{ $specialWorking }}</div>
                <span style="font-size: 11.5px; color: #475569; font-weight: 600;">100% standard rate</span>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="ph ph-briefcase"></i>
            </div>
        </div>
    </div>

    <!-- HOLIDAY FILTERS BAR (CLIENT-SIDE REACTIVE WITHOUT PAGE RELOAD) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
        <!-- Search Input -->
        <div style="flex: 1; min-width: 200px; position: relative;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
            <input type="text" id="holidaySearchInput" class="hr-input" placeholder="Search Holiday, Proclamation, or Keyword..." style="padding-left: 32px; height: 36px; font-size: 12.5px; width: 100%; border-radius: 8px;" oninput="applyHolidayFilters()">
        </div>

        <!-- Filter 1: Holiday Type -->
        <div style="min-width: 150px;">
            <select id="filterHolidayType" class="hr-select" style="height: 36px; font-size: 12.5px; border-radius: 8px;" onchange="applyHolidayFilters()">
                <option value="">All Holiday Types</option>
                <option value="Regular Holiday">Regular Holiday</option>
                <option value="Special Non-Working">Special Non-Working</option>
                <option value="Special Working">Special Working</option>
                <option value="Local Holiday">Local Holiday</option>
                <option value="Company Holiday">Company Holiday</option>
            </select>
        </div>

        <!-- Filter 2: Location / Scope -->
        <div style="min-width: 130px;">
            <select id="filterScope" class="hr-select" style="height: 36px; font-size: 12.5px; border-radius: 8px;" onchange="applyHolidayFilters()">
                <option value="">All Scopes</option>
                <option value="Nationwide">Nationwide</option>
                <option value="Regional">Regional</option>
                <option value="Provincial">Provincial</option>
                <option value="City/Local">City / Local</option>
                <option value="Company">Company-Wide</option>
            </select>
        </div>

        <!-- Filter 3: Branch -->
        <div style="min-width: 140px;">
            <select id="filterBranch" class="hr-select" style="height: 36px; font-size: 12.5px; border-radius: 8px;" onchange="applyHolidayFilters()">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filter 4: Status -->
        <div style="min-width: 120px;">
            <select id="filterStatus" class="hr-select" style="height: 36px; font-size: 12.5px; border-radius: 8px;" onchange="applyHolidayFilters()">
                <option value="">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>

        <!-- Filter 5: Year Selector -->
        <div style="min-width: 100px;">
            <select id="filterYear" class="hr-select" style="height: 36px; font-size: 12.5px; border-radius: 8px; font-weight: 700; color: #7c3aed;" onchange="window.location.href = '{{ route('hr.payroll.holidays') }}?year=' + this.value">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <button type="button" class="hr-btn hr-btn-secondary" style="height: 36px; padding: 0 12px; font-size: 12px;" onclick="resetHolidayFilters()" title="Reset Filters">
            <i class="ph ph-arrow-counter-clockwise"></i>
            <span>Reset</span>
        </button>
    </div>

    <!-- HOLIDAYS TABLE CARD -->
    <div class="hr-table-card" style="box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div class="hr-table-header" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; font-size: 14px; color: #0f172a;">Philippine Statutory Holiday Schedule &mdash; Year {{ $year }}</span>
                <span class="hr-badge hr-badge-purple" id="tableHolidayCountBadge" style="font-size: 11px;">{{ count($holidays) }} holidays</span>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                Compliant with <strong>DOLE Labor Code Art. 94 & Malacañang Statutory Advisories</strong>
            </div>
        </div>

        <div class="hr-table-wrapper" style="overflow-x: auto;">
            <table class="hr-table" id="holidaysTable">
                <thead>
                    <tr>
                        <th style="min-width: 130px;">Date</th>
                        <th style="min-width: 180px;">Holiday</th>
                        <th style="min-width: 140px;">Type</th>
                        <th style="min-width: 110px;">Scope</th>
                        <th style="min-width: 160px;">Applicable Branches</th>
                        <th style="min-width: 220px;">Pay Treatment</th>
                        <th style="min-width: 90px;">Status</th>
                        <th style="text-align: right; min-width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="holidaysTableBody">
                    @forelse($holidays as $h)
                        @php
                            $badgeClass = match($h->holiday_type) {
                                'Regular Holiday' => 'hr-badge-purple',
                                'Special Non-Working' => 'hr-badge-warning',
                                'Special Working' => 'hr-badge-info',
                                'Local Holiday' => 'hr-badge-success',
                                default => 'hr-badge-neutral',
                            };
                            $branchCount = is_array($h->applicable_branches) ? count($h->applicable_branches) : 0;
                        @endphp
                        <tr class="holiday-row" 
                            data-id="{{ $h->id }}"
                            data-name="{{ strtolower($h->name) }}"
                            data-type="{{ $h->holiday_type }}"
                            data-scope="{{ $h->scope }}"
                            data-status="{{ $h->is_active ? 'Active' : 'Inactive' }}"
                            data-branches="{{ json_encode($h->applicable_branches ?? []) }}"
                            data-json="{{ json_encode($h) }}">
                            
                            <!-- Date -->
                            <td>
                                <strong style="color: #0f172a; font-size: 13px;">{{ $h->date->format('F j, Y') }}</strong>
                                <span style="display: block; font-size: 11px; color: #64748b;">{{ $h->date->format('l') }}</span>
                            </td>

                            <!-- Holiday Name & Ref -->
                            <td>
                                <div style="font-weight: 700; color: #1e1b4b; font-size: 13.5px;">{{ $h->name }}</div>
                                @if($h->official_reference)
                                    <span style="font-size: 11px; color: #7c3aed; font-weight: 600;" title="Official Proclamation / DOLE reference">
                                        <i class="ph ph-certificate"></i> {{ $h->official_reference }}
                                    </span>
                                @endif
                            </td>

                            <!-- Type Badge -->
                            <td>
                                <span class="hr-badge {{ $badgeClass }}" style="font-size: 11px; font-weight: 700; padding: 3px 9px;">
                                    {{ $h->holiday_type }}
                                </span>
                            </td>

                            <!-- Scope -->
                            <td>
                                <span style="font-size: 12px; color: #334155; font-weight: 500;">
                                    <i class="ph ph-globe" style="color: #94a3b8;"></i> {{ $h->scope }}
                                </span>
                            </td>

                            <!-- Applicable Branches -->
                            <td>
                                @if($h->scope === 'Nationwide' || empty($h->applicable_branches))
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">
                                        <i class="ph ph-check"></i> All Branches
                                    </span>
                                @else
                                    <span class="hr-badge hr-badge-purple" style="font-size: 11px;">
                                        {{ $branchCount }} Selected Branch{{ $branchCount > 1 ? 'es' : '' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Pay Treatment -->
                            <td>
                                <span style="font-size: 12px; color: #475569; font-weight: 600; line-height: 1.4;">
                                    {{ $h->payroll_treatment ?? '100% unworked / 200% worked' }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td>
                                @if($h->is_active)
                                    <span class="hr-badge hr-badge-success" style="font-size: 10.5px;">Active</span>
                                @else
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px;">Inactive</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 4px 8px;" onclick="viewHolidayDetails({{ $h->id }})" title="View Holiday Details & Calculation Rules">
                                        <i class="ph ph-eye"></i>
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 4px 8px;" onclick="editHoliday({{ $h->id }})" title="Edit Holiday Configuration">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <form method="POST" action="{{ route('hr.payroll.holidays.destroy', $h->id) }}" style="display: inline;" onsubmit="return confirm('Delete this holiday: {{ $h->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 4px 8px; color: #dc2626;" title="Delete Holiday">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyHolidayRow">
                            <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="ph ph-calendar-x" style="font-size: 32px; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
                                No holidays found for the selected year {{ $year }}. Click <strong>"+ Add Holiday"</strong> or <strong>"Import Calendar"</strong> to seed standard DOLE statutory holidays.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: ADD / CREATE HOLIDAY                              -->
<!-- ======================================================== -->
<div id="addHolidayModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Philippine Holiday</span>
            <button class="icon-btn" onclick="closeModal('addHolidayModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.holidays.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="hr-input" placeholder="e.g. New Year's Day, Maundy Thursday" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" class="hr-input" value="{{ $year }}" min="2020" max="2040" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="hr-input" value="{{ $year }}-01-01" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Holiday Type <span class="text-danger">*</span></label>
                        <select name="holiday_type" id="newHolidayType" class="hr-select" required onchange="onHolidayTypeChange(this.value, 'new')">
                            <option value="Regular Holiday">Regular Holiday (200% worked / 100% unworked)</option>
                            <option value="Special Non-Working">Special Non-Working (130% worked / No work, no pay)</option>
                            <option value="Special Working">Special Working (100% standard rate)</option>
                            <option value="Local Holiday">Local Holiday (Regional/City scope)</option>
                            <option value="Company Holiday">Company Holiday</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Scope <span class="text-danger">*</span></label>
                        <select name="scope" id="newHolidayScope" class="hr-select" required onchange="onScopeChange(this.value, 'new')">
                            <option value="Nationwide">Nationwide (All Locations)</option>
                            <option value="Regional">Regional (Specific Region)</option>
                            <option value="Provincial">Provincial</option>
                            <option value="City/Local">City / Municipality Local</option>
                            <option value="Company">Company / Selected Branches</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Official Reference</label>
                        <input type="text" name="official_reference" class="hr-input" placeholder="e.g. Proclamation No. 727 / DOLE Advisory">
                    </div>
                </div>

                <!-- Applicable Branches (for local/company holidays) -->
                <div class="hr-form-group" id="newBranchSelectGroup" style="margin-top: 12px;">
                    <label class="hr-form-label">Applicable Branches (leave empty for all)</label>
                    <select name="applicable_branches[]" class="hr-select" multiple style="height: 80px;">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                    <span style="font-size: 11px; color: #64748b;">Hold Ctrl/Cmd to select multiple specific branches.</span>
                </div>

                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Payroll Treatment</label>
                    <select name="payroll_treatment" id="newPayrollTreatment" class="hr-select">
                        <option value="100% unworked / 200% worked (+30% rest day)">100% unworked / 200% worked (+30% rest day)</option>
                        <option value="No work no pay / 130% worked (+50% rest day)">No work no pay / 130% worked (+50% rest day)</option>
                        <option value="100% standard rate (no premium)">100% standard rate (no premium)</option>
                        <option value="130% worked rate">130% worked rate</option>
                        <option value="200% worked rate (No unworked pay)">200% worked rate (No unworked pay)</option>
                        <option value="Double Holiday (300% worked / 200% unworked)">Double Holiday (300% worked / 200% unworked)</option>
                        <option value="Company Policy / CBA Override">Company Policy / CBA Override</option>
                    </select>
                </div>

                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Description / Source</label>
                    <textarea name="description" class="hr-input" rows="2" placeholder="Official notes, advisory text, or proclamation background..."></textarea>
                </div>

                <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" id="newIsActive" value="1" checked style="width: 16px; height: 16px;">
                    <label for="newIsActive" style="font-size: 13px; font-weight: 600; color: #0f172a; cursor: pointer;">Active and Enforce in Payroll Calculations</label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addHolidayModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Holiday</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT HOLIDAY                                      -->
<!-- ======================================================== -->
<div id="editHolidayModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil-simple"></i> Edit Holiday Configuration</span>
            <button class="icon-btn" onclick="closeModal('editHolidayModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" id="editHolidayForm" action="">
            @csrf
            @method('PUT')
            <div class="hr-modal-body">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editHolidayName" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" id="editHolidayYear" class="hr-input" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="editHolidayDate" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Holiday Type <span class="text-danger">*</span></label>
                        <select name="holiday_type" id="editHolidayType" class="hr-select" required onchange="onHolidayTypeChange(this.value, 'edit')">
                            <option value="Regular Holiday">Regular Holiday</option>
                            <option value="Special Non-Working">Special Non-Working</option>
                            <option value="Special Working">Special Working</option>
                            <option value="Local Holiday">Local Holiday</option>
                            <option value="Company Holiday">Company Holiday</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Scope</label>
                        <select name="scope" id="editHolidayScope" class="hr-select" required>
                            <option value="Nationwide">Nationwide</option>
                            <option value="Regional">Regional</option>
                            <option value="Provincial">Provincial</option>
                            <option value="City/Local">City/Local</option>
                            <option value="Company">Company</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Official Reference</label>
                        <input type="text" name="official_reference" id="editHolidayRef" class="hr-input">
                    </div>
                </div>

                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Payroll Treatment</label>
                    <select name="payroll_treatment" id="editPayrollTreatment" class="hr-select">
                        <option value="100% unworked / 200% worked (+30% rest day)">100% unworked / 200% worked (+30% rest day)</option>
                        <option value="No work no pay / 130% worked (+50% rest day)">No work no pay / 130% worked (+50% rest day)</option>
                        <option value="100% standard rate (no premium)">100% standard rate (no premium)</option>
                        <option value="130% worked rate">130% worked rate</option>
                        <option value="200% worked rate (No unworked pay)">200% worked rate (No unworked pay)</option>
                        <option value="Double Holiday (300% worked / 200% unworked)">Double Holiday (300% worked / 200% unworked)</option>
                        <option value="Company Policy / CBA Override">Company Policy / CBA Override</option>
                    </select>
                </div>

                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Description</label>
                    <textarea name="description" id="editHolidayDesc" class="hr-input" rows="2"></textarea>
                </div>

                <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" id="editIsActive" value="1" style="width: 16px; height: 16px;">
                    <label for="editIsActive" style="font-size: 13px; font-weight: 600; color: #0f172a;">Active</label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editHolidayModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Holiday</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- DRAWER: HOLIDAY DETAILS & CALCULATION RULES              -->
<!-- ======================================================== -->
<div id="holidayDetailDrawer" class="hr-modal-overlay" style="align-items: stretch; justify-content: flex-end;">
    <div style="background: #ffffff; width: 480px; max-width: 90vw; height: 100vh; overflow-y: auto; padding: 24px; box-shadow: -4px 0 20px rgba(0,0,0,0.15); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Holiday Details & Rules</h3>
            </div>
            <button class="icon-btn" onclick="closeModal('holidayDetailDrawer')"><i class="ph ph-x"></i></button>
        </div>

        <div style="flex: 1; padding: 20px 0; display: flex; flex-direction: column; gap: 18px;">
            <!-- Main Title Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #7c3aed; letter-spacing: 0.5px;" id="dtlTypeBadge">Regular Holiday</div>
                <div style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px;" id="dtlName">New Year's Day</div>
                <div style="font-size: 13px; color: #64748b; margin-top: 2px;" id="dtlDate">January 1, 2026 (Thursday)</div>
            </div>

            <!-- Metadata Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12.5px;">
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block;">Scope</span>
                    <strong style="color: #0f172a;" id="dtlScope">Nationwide</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block;">Status</span>
                    <strong style="color: #059669;" id="dtlStatus">Active</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block;">Official Reference</span>
                    <span style="color: #0f172a; font-weight: 600;" id="dtlReference">Proclamation No. 727</span>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block;">Applicable Locations</span>
                    <span style="color: #0f172a; font-weight: 600;" id="dtlLocations">All Branches Nationwide</span>
                </div>
            </div>

            <!-- Payroll Treatment -->
            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 12px 14px;">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b21a8; display: block;">Statutory Pay Treatment</span>
                <div style="font-size: 13px; font-weight: 700; color: #581c87; margin-top: 3px;" id="dtlTreatment">100% unworked / 200% worked (+30% on rest day)</div>
            </div>

            <!-- PAYROLL RULES BREAKDOWN -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-scales" style="color: #7c3aed;"></i>
                    <span>DOLE Statutory Calculation Rules</span>
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12.5px;">
                    <!-- Rule 1: Unworked -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                        <div>
                            <span style="font-weight: 700; color: #0f172a; display: block;">Unworked Day</span>
                            <span style="font-size: 11px; color: #64748b;" id="dtlRuleUnworkedFormula">100% of basic daily wage if present before holiday</span>
                        </div>
                        <span class="hr-badge hr-badge-purple" style="font-size: 12px; font-weight: 700;" id="dtlRuleUnworked">100%</span>
                    </div>

                    <!-- Rule 2: Worked -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                        <div>
                            <span style="font-weight: 700; color: #0f172a; display: block;">Worked (First 8 Hours)</span>
                            <span style="font-size: 11px; color: #64748b;" id="dtlRuleWorkedFormula">Basic Daily Rate &times; 200%</span>
                        </div>
                        <span class="hr-badge hr-badge-success" style="font-size: 12px; font-weight: 700;" id="dtlRuleWorked">200%</span>
                    </div>

                    <!-- Rule 3: Rest Day -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                        <div>
                            <span style="font-weight: 700; color: #0f172a; display: block;">Worked on Scheduled Rest Day</span>
                            <span style="font-size: 11px; color: #64748b;" id="dtlRuleRestDayFormula">200% &times; 130% = 260% basic wage rate</span>
                        </div>
                        <span class="hr-badge hr-badge-warning" style="font-size: 12px; font-weight: 700;" id="dtlRuleRestDay">+30% (260%)</span>
                    </div>

                    <!-- Rule 4: Overtime -->
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;">
                        <div>
                            <span style="font-weight: 700; color: #0f172a; display: block;">Overtime Hours (Beyond 8 Hours)</span>
                            <span style="font-size: 11px; color: #64748b;" id="dtlRuleOtFormula">Hourly rate for that day &times; 130%</span>
                        </div>
                        <span class="hr-badge hr-badge-info" style="font-size: 12px; font-weight: 700;" id="dtlRuleOt">+30% of day rate</span>
                    </div>
                </div>

                <div style="margin-top: 14px; font-size: 11.5px; color: #64748b; line-height: 1.5; background: #f1f5f9; padding: 10px 12px; border-radius: 6px;">
                    <i class="ph ph-info" style="color: #7c3aed;"></i>
                    <strong>Effective-Dated Advisory Note:</strong> Holiday classifications are synchronized by year. Under DOLE's 2026/2027 advisories, special working days are non-premium, while work on special non-working days falling on rest days entitles staff to 150%.
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; display: flex; justify-content: flex-end;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('holidayDetailDrawer')">Close</button>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: IMPORT CALENDAR                                   -->
<!-- ======================================================== -->
<div id="importHolidayModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Import Philippine Holiday Calendar</span>
            <button class="icon-btn" onclick="closeModal('importHolidayModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.holidays.import') }}">
            @csrf
            <div class="hr-modal-body">
                <p style="font-size: 13px; color: #475569; margin-top: 0;">
                    Import official Philippine Regular Holidays, Special Non-Working Days, and Special Working Days for the target year based on official Malacañang Proclamations & DOLE Labor Advisories.
                </p>
                <div class="hr-form-group">
                    <label class="hr-form-label">Target Year</label>
                    <select name="year" class="hr-select">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }} DOLE Holiday Calendar</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('importHolidayModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Import Statutory Calendar</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: HOLIDAY SETTINGS                                  -->
<!-- ======================================================== -->
<div id="holidaySettingsModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-sliders"></i> Holiday Pay Computation Settings</span>
            <button class="icon-btn" onclick="closeModal('holidaySettingsModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.holidays.settings') }}">
            @csrf
            <div class="hr-modal-body" style="display: flex; flex-direction: column; gap: 16px;">
                <p style="margin: 0; font-size: 12.5px; color: #64748b;">
                    Configure multiplier policies for each holiday classification according to DOLE statutory minimums or company policy enhancements.
                </p>

                <!-- Regular Holiday -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; background: #fdf4ff;">
                    <strong style="color: #7c3aed; font-size: 13px;">Regular Holiday Settings</strong>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 8px;">
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Unworked Rate (%)</label>
                            <input type="number" step="1" name="rules[Regular Holiday][unworked_rate]" value="{{ $payRules['Regular Holiday']->unworked_rate ?? 100 }}" class="hr-input" style="height: 32px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Worked Rate (%)</label>
                            <input type="number" step="1" name="rules[Regular Holiday][worked_rate]" value="{{ $payRules['Regular Holiday']->worked_rate ?? 200 }}" class="hr-input" style="height: 32px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Rest Day Rate (%)</label>
                            <input type="number" step="1" name="rules[Regular Holiday][rest_day_worked_rate]" value="{{ $payRules['Regular Holiday']->rest_day_worked_rate ?? 260 }}" class="hr-input" style="height: 32px;">
                        </div>
                    </div>
                </div>

                <!-- Special Non-Working -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; background: #fffbeb;">
                    <strong style="color: #b45309; font-size: 13px;">Special Non-Working Settings</strong>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 8px;">
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Unworked Rate (%)</label>
                            <input type="number" step="1" name="rules[Special Non-Working][unworked_rate]" value="{{ $payRules['Special Non-Working']->unworked_rate ?? 0 }}" class="hr-input" style="height: 32px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Worked Rate (%)</label>
                            <input type="number" step="1" name="rules[Special Non-Working][worked_rate]" value="{{ $payRules['Special Non-Working']->worked_rate ?? 130 }}" class="hr-input" style="height: 32px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; color: #64748b;">Rest Day Rate (%)</label>
                            <input type="number" step="1" name="rules[Special Non-Working][rest_day_worked_rate]" value="{{ $payRules['Special Non-Working']->rest_day_worked_rate ?? 150 }}" class="hr-input" style="height: 32px;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('holidaySettingsModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter Holidays client-side without page reload
function applyHolidayFilters() {
    const q = (document.getElementById('holidaySearchInput').value || '').toLowerCase().trim();
    const type = document.getElementById('filterHolidayType').value;
    const scope = document.getElementById('filterScope').value;
    const branch = document.getElementById('filterBranch').value;
    const status = document.getElementById('filterStatus').value;

    const rows = document.querySelectorAll('.holiday-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const hType = row.getAttribute('data-type');
        const hScope = row.getAttribute('data-scope');
        const hStatus = row.getAttribute('data-status');
        const branches = JSON.parse(row.getAttribute('data-branches') || '[]');

        let match = true;

        if (q && !name.includes(q)) match = false;
        if (type && hType !== type) match = false;
        if (scope && hScope !== scope) match = false;
        if (status && hStatus !== status) match = false;
        if (branch && branches.length > 0 && !branches.includes(branch) && !branches.includes(parseInt(branch))) {
            match = false;
        }

        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    const badge = document.getElementById('tableHolidayCountBadge');
    if (badge) badge.innerText = `${visibleCount} holidays`;

    const emptyRow = document.getElementById('emptyHolidayRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

function resetHolidayFilters() {
    document.getElementById('holidaySearchInput').value = '';
    document.getElementById('filterHolidayType').value = '';
    document.getElementById('filterScope').value = '';
    document.getElementById('filterBranch').value = '';
    document.getElementById('filterStatus').value = '';
    applyHolidayFilters();
}

// Open Detail Drawer
function viewHolidayDetails(id) {
    const row = document.querySelector(`.holiday-row[data-id="${id}"]`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));

    document.getElementById('dtlName').innerText = data.name;
    document.getElementById('dtlDate').innerText = data.date ? new Date(data.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', weekday: 'long' }) : '';
    document.getElementById('dtlTypeBadge').innerText = data.holiday_type;
    document.getElementById('dtlScope').innerText = data.scope;
    document.getElementById('dtlStatus').innerText = data.is_active ? 'Active' : 'Inactive';
    document.getElementById('dtlReference').innerText = data.official_reference || 'DOLE Statutory Labor Code';
    document.getElementById('dtlTreatment').innerText = data.payroll_treatment || 'Statutory DOLE Rules Apply';
    document.getElementById('dtlLocations').innerText = (data.scope === 'Nationwide' || !data.applicable_branches || data.applicable_branches.length === 0) 
        ? 'All Branches Nationwide' 
        : `${data.applicable_branches.length} Selected Branches`;

    // Calculation rules display based on type
    if (data.holiday_type === 'Regular Holiday') {
        document.getElementById('dtlRuleUnworked').innerText = '100%';
        document.getElementById('dtlRuleWorked').innerText = '200%';
        document.getElementById('dtlRuleRestDay').innerText = '+30% (260%)';
        document.getElementById('dtlRuleOt').innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Non-Working') {
        document.getElementById('dtlRuleUnworked').innerText = '0% (No work no pay)';
        document.getElementById('dtlRuleWorked').innerText = '130%';
        document.getElementById('dtlRuleRestDay').innerText = '+50% (150%)';
        document.getElementById('dtlRuleOt').innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Working') {
        document.getElementById('dtlRuleUnworked').innerText = '100%';
        document.getElementById('dtlRuleWorked').innerText = '100%';
        document.getElementById('dtlRuleRestDay').innerText = '130%';
        document.getElementById('dtlRuleOt').innerText = '125%';
    } else {
        document.getElementById('dtlRuleUnworked').innerText = 'Per Company Policy';
        document.getElementById('dtlRuleWorked').innerText = '130%';
        document.getElementById('dtlRuleRestDay').innerText = '150%';
        document.getElementById('dtlRuleOt').innerText = '+30%';
    }

    openModal('holidayDetailDrawer');
}

// Edit Holiday
function editHoliday(id) {
    const row = document.querySelector(`.holiday-row[data-id="${id}"]`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));
    const form = document.getElementById('editHolidayForm');
    form.action = `{{ url('hr/payroll/holidays') }}/${id}`;

    document.getElementById('editHolidayName').value = data.name;
    document.getElementById('editHolidayYear').value = data.year;
    document.getElementById('editHolidayDate').value = data.date ? data.date.substring(0, 10) : '';
    document.getElementById('editHolidayType').value = data.holiday_type;
    document.getElementById('editHolidayScope').value = data.scope;
    document.getElementById('editHolidayRef').value = data.official_reference || '';

    // Set Payroll Treatment select dropdown
    const treatmentSelect = document.getElementById('editPayrollTreatment');
    if (treatmentSelect) {
        let found = false;
        for (let opt of treatmentSelect.options) {
            if (opt.value === data.payroll_treatment) {
                treatmentSelect.value = data.payroll_treatment;
                found = true;
                break;
            }
        }
        if (!found && data.payroll_treatment) {
            const customOpt = new Option(data.payroll_treatment, data.payroll_treatment, true, true);
            treatmentSelect.add(customOpt);
        }
    }

    document.getElementById('editHolidayDesc').value = data.description || '';
    document.getElementById('editIsActive').checked = !!data.is_active;

    openModal('editHolidayModal');
}

function onHolidayTypeChange(type, prefix) {
    const treatmentSelect = document.getElementById(`${prefix}PayrollTreatment`);
    if (!treatmentSelect) return;

    if (type === 'Regular Holiday') {
        treatmentSelect.value = '100% unworked / 200% worked (+30% rest day)';
    } else if (type === 'Special Non-Working') {
        treatmentSelect.value = 'No work no pay / 130% worked (+50% rest day)';
    } else if (type === 'Special Working') {
        treatmentSelect.value = '100% standard rate (no premium)';
    } else {
        treatmentSelect.value = '130% worked rate';
    }
}
</script>
@endsection
