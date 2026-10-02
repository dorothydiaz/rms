@props([
    'action',
    'resetUrl' => null,
    'branches' => [],
    'departments' => [],
    'companies' => [],
    'employees' => [],
    'startDate' => null,
    'endDate' => null,
    'showDates' => true,
    'showSearch' => true,
    'showEmployeeDropdown' => true,
    'showStatus' => true,
    'showBranch' => true,
    'showCompany' => true,
    'showDepartment' => true,
    'showSource' => false,
])

@php
    $reset = $resetUrl ?: $action;
    $statuses = ['Active', 'Regular', 'Probationary', 'Contractual', 'Part-time', 'Seasonal', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Inactive'];

    $activeCount = 0;
    if (request()->filled('search')) $activeCount++;
    if (request()->filled('date_from')) $activeCount++;
    if (request()->filled('date_to')) $activeCount++;
    if (request()->filled('branch_id')) $activeCount++;
    if (request()->filled('company_id')) $activeCount++;
    if (request()->filled('department_id')) $activeCount++;
    if (request()->filled('employee_id')) $activeCount++;
    if (request()->filled('employment_status')) $activeCount++;
    if (request()->filled('employment_source')) $activeCount++;
    if (request()->filled('status')) $activeCount++;
    if (request()->filled('leave_type_id')) $activeCount++;
    if (request()->filled('is_paid')) $activeCount++;
@endphp

<div class="hr-card hr-report-filter-bar" style="margin-bottom: 20px; padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
    <form method="GET" action="{{ $action }}" style="display: flex; flex-direction: column; gap: 14px;">
        
        <!-- Multi-Filter Grid -->
        <div class="hr-report-filter-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px 14px; align-items: flex-end;">
            
            @if($showSearch)
                <!-- Search Individual Employee -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-magnifying-glass"></i> Search Employee
                    </label>
                    <div style="position: relative; width: 100%;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
                        <input type="text" name="search" value="{{ request('search') }}" class="hr-input" placeholder="Name or EMP-ID..." style="padding-left: 32px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box;">
                    </div>
                </div>
            @endif

            @if($showDates)
                <!-- Date From -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-calendar"></i> Date From
                    </label>
                    <input type="date" name="date_from" value="{{ $startDate ?? request('date_from') }}" class="hr-input" style="padding: 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box;">
                </div>

                <!-- Date To -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-calendar"></i> Date To
                    </label>
                    <input type="date" name="date_to" value="{{ $endDate ?? request('date_to') }}" class="hr-input" style="padding: 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box;">
                </div>
            @endif

            @if($showStatus)
                <!-- Employee Status Multi-Filter -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-user-check"></i> Employee Status
                    </label>
                    <select name="employment_status" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Statuses</option>
                        <option value="ACTIVE_ALL" {{ request('employment_status') === 'ACTIVE_ALL' ? 'selected' : '' }}>Active Staff Only</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" {{ request('employment_status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showBranch && count($branches) > 0)
                <!-- Branch Filter -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-storefront"></i> Branch
                    </label>
                    <select name="branch_id" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showCompany && count($companies) > 0)
                <!-- Company / Agency Multi-Filter -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-buildings"></i> Company / Agency
                    </label>
                    <select name="company_id" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Companies &amp; Agencies</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ (string) request('company_id') === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->type ?? 'Company' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showDepartment && count($departments) > 0)
                <!-- Department Filter -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-tree-structure"></i> Department
                    </label>
                    <select name="department_id" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ (string) request('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showEmployeeDropdown && count($employees) > 0)
                <!-- Specific Employee Dropdown -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-user"></i> Select Staff
                    </label>
                    <select name="employee_id" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Employees</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ (string) request('employee_id') === (string) $e->id ? 'selected' : '' }}>
                                {{ $e->full_name }} ({{ $e->employee_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showSource)
                <!-- Employment Source (Direct vs Agency) -->
                <div style="min-width: 0;">
                    <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-briefcase"></i> Source
                    </label>
                    <select name="employment_source" class="hr-select" style="padding: 6px 28px 6px 10px; font-size: 12px; height: 34px; width: 100%; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                        <option value="">All Sources</option>
                        <option value="Direct" {{ request('employment_source') === 'Direct' ? 'selected' : '' }}>Direct Hire</option>
                        <option value="Agency" {{ request('employment_source') === 'Agency' ? 'selected' : '' }}>Agency Deployed</option>
                    </select>
                </div>
            @endif

            <!-- Custom Injected Slots (e.g. Leave Type, Undertime Status) -->
            {{ $slot ?? '' }}

        </div>

        <!-- Filter Action Toolbar & Active Filter Badges -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
            <!-- Left: Active Badges / Helper Note -->
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 11.5px; flex: 1; min-width: 200px;">
                @if($activeCount > 0)
                    <span style="font-weight: 600; color: #64748b; margin-right: 2px;">Active Filters ({{ $activeCount }}):</span>
                    @if(request()->filled('search'))
                        <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Search: "{{ request('search') }}"</span>
                    @endif
                    @if(request()->filled('date_from') && request()->filled('date_to'))
                        <span class="hr-badge hr-badge-info" style="font-size: 11px;">Dates: {{ request('date_from') }} to {{ request('date_to') }}</span>
                    @endif
                    @if(request()->filled('employment_status'))
                        <span class="hr-badge hr-badge-purple" style="font-size: 11px;">Status: {{ request('employment_status') }}</span>
                    @endif
                    @if(request()->filled('branch_id'))
                        @php $bName = optional(collect($branches)->firstWhere('id', request('branch_id')))->name ?? 'Branch #' . request('branch_id'); @endphp
                        <span class="hr-badge hr-badge-info" style="font-size: 11px;">Branch: {{ $bName }}</span>
                    @endif
                    @if(request()->filled('company_id'))
                        @php $cName = optional(collect($companies)->firstWhere('id', request('company_id')))->name ?? 'Company #' . request('company_id'); @endphp
                        <span class="hr-badge hr-badge-warning" style="font-size: 11px;">Company: {{ $cName }}</span>
                    @endif
                    @if(request()->filled('department_id'))
                        @php $dName = optional(collect($departments)->firstWhere('id', request('department_id')))->name ?? 'Dept #' . request('department_id'); @endphp
                        <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Dept: {{ $dName }}</span>
                    @endif
                    @if(request()->filled('employee_id'))
                        @php $eName = optional(collect($employees)->firstWhere('id', request('employee_id')))->full_name ?? 'Emp #' . request('employee_id'); @endphp
                        <span class="hr-badge hr-badge-success" style="font-size: 11px;">Staff: {{ $eName }}</span>
                    @endif
                    @if(request()->filled('employment_source'))
                        <span class="hr-badge hr-badge-purple" style="font-size: 11px;">Source: {{ request('employment_source') }}</span>
                    @endif
                    <a href="{{ $reset }}" style="color: #ef4444; font-size: 11px; text-decoration: none; margin-left: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 2px;">
                        <i class="ph ph-x"></i> Clear All
                    </a>
                @else
                    <span style="color: #94a3b8; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="ph ph-funnel" style="font-size: 13px;"></i> Use the filters above to refine report records by employee, branch, or date.
                    </span>
                @endif
            </div>

            <!-- Right: Action Buttons -->
            <div style="display: flex; gap: 8px; align-items: center; margin-left: auto;">
                <a href="{{ $reset }}" class="hr-btn hr-btn-secondary" style="height: 33px; padding: 0 14px; font-size: 12px; color: #64748b;" title="Reset all filters">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    <span>Reset</span>
                </a>
                <button type="submit" class="hr-btn hr-btn-primary" style="height: 33px; padding: 0 18px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 6px rgba(124, 58, 237, 0.25);">
                    <i class="ph ph-funnel"></i>
                    <span>Apply Filter</span>
                </button>
            </div>
        </div>

    </form>
</div>
