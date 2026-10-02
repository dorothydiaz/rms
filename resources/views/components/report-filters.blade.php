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
    $statuses = ['Active Staff Only' => 'ACTIVE_ALL', 'Regular' => 'Regular', 'Probationary' => 'Probationary', 'Contractual' => 'Contractual', 'Part-time' => 'Part-time', 'Seasonal' => 'Seasonal', 'On Leave' => 'On Leave', 'Suspended' => 'Suspended', 'Resigned' => 'Resigned', 'Terminated' => 'Terminated'];

    // Map active query parameters to stacked tag badges
    $stackedTags = [];
    if (request()->filled('employment_status')) {
        $stVal = request('employment_status');
        $lbl = array_search($stVal, $statuses) ?: $stVal;
        $stackedTags[] = [
            'key' => 'employment_status',
            'value' => $stVal,
            'label' => strtolower($lbl),
        ];
    }
    if (request()->filled('branch_id')) {
        $bObj = collect($branches)->firstWhere('id', request('branch_id'));
        $bName = $bObj ? strtolower($bObj->name) : 'branch #' . request('branch_id');
        $stackedTags[] = [
            'key' => 'branch_id',
            'value' => request('branch_id'),
            'label' => $bName,
        ];
    }
    if (request()->filled('company_id')) {
        $cObj = collect($companies)->firstWhere('id', request('company_id'));
        $cName = $cObj ? strtolower($cObj->name) : 'company #' . request('company_id');
        $stackedTags[] = [
            'key' => 'company_id',
            'value' => request('company_id'),
            'label' => $cName,
        ];
    }
    if (request()->filled('department_id')) {
        $dObj = collect($departments)->firstWhere('id', request('department_id'));
        $dName = $dObj ? strtolower($dObj->name) : 'dept #' . request('department_id');
        $stackedTags[] = [
            'key' => 'department_id',
            'value' => request('department_id'),
            'label' => $dName,
        ];
    }
    if (request()->filled('employee_id')) {
        $eObj = collect($employees)->firstWhere('id', request('employee_id'));
        $eName = $eObj ? strtolower($eObj->full_name) : 'staff #' . request('employee_id');
        $stackedTags[] = [
            'key' => 'employee_id',
            'value' => request('employee_id'),
            'label' => $eName,
        ];
    }
    if (request()->filled('employment_source')) {
        $stackedTags[] = [
            'key' => 'employment_source',
            'value' => request('employment_source'),
            'label' => strtolower(request('employment_source')) . ' source',
        ];
    }
    if (request()->filled('status')) {
        $stackedTags[] = [
            'key' => 'status',
            'value' => request('status'),
            'label' => strtolower(request('status')),
        ];
    }
    if (request()->filled('leave_type_id')) {
        $stackedTags[] = [
            'key' => 'leave_type_id',
            'value' => request('leave_type_id'),
            'label' => 'leave #' . request('leave_type_id'),
        ];
    }
    if (request()->filled('is_paid')) {
        $stackedTags[] = [
            'key' => 'is_paid',
            'value' => request('is_paid'),
            'label' => request('is_paid') == '1' ? 'paid leave' : 'unpaid leave',
        ];
    }
@endphp

<div class="hr-sketch-filter-wrap">
    <form method="GET" action="{{ $action }}" id="hrStackedFilterForm">
        
        <!-- Hidden Inputs for Stacking Filters -->
        <input type="hidden" name="employment_status" id="hr_input_employment_status" value="{{ request('employment_status') }}">
        <input type="hidden" name="branch_id" id="hr_input_branch_id" value="{{ request('branch_id') }}">
        <input type="hidden" name="company_id" id="hr_input_company_id" value="{{ request('company_id') }}">
        <input type="hidden" name="department_id" id="hr_input_department_id" value="{{ request('department_id') }}">
        <input type="hidden" name="employee_id" id="hr_input_employee_id" value="{{ request('employee_id') }}">
        <input type="hidden" name="employment_source" id="hr_input_employment_source" value="{{ request('employment_source') }}">

        @if($showDates)
        <!-- 1. Top Row matching sketch: from [  ]   to [  ] -->
        <div class="hr-sketch-date-row">
            <span class="hr-sketch-date-label">from</span>
            <div class="hr-sketch-date-box">
                <input type="date" name="date_from" value="{{ $startDate ?? request('date_from') }}" class="hr-sketch-date-input" title="Date From">
            </div>
            <span class="hr-sketch-date-label" style="margin-left: 10px;">to</span>
            <div class="hr-sketch-date-box">
                <input type="date" name="date_to" value="{{ $endDate ?? request('date_to') }}" class="hr-sketch-date-input" title="Date To">
            </div>
        </div>
        @endif

        <!-- 2. Bottom Row matching sketch: [ all employee ][ regular; bgc branch; ... ][ Generate ] -->
        <div style="position: relative;">
            <div class="hr-stacked-bar-container">
                
                <!-- Unified Input Box (Purple Prefix + White Body) -->
                <div class="hr-stacked-input-group" id="hrStackedInputGroup">
                    
                    <!-- Left Solid Purple Prefix Box: all employee -->
                    <div class="hr-filter-prefix-pill" id="hrFilterPrefixPill" title="Default is all employees. Click to clear all stacked filters back to default.">
                        <span id="hrFilterPrefixText">all employee</span>
                    </div>

                    <!-- Center Body for Stacked Filter Chips and Search Input -->
                    <div class="hr-stacked-tags-body" id="hrStackedTagsBody" onclick="focusStackedInput(event)">
                        
                        <!-- Stacked Filter Tag Chips -->
                        <div id="hrStackedChipsContainer" style="display: contents;">
                            @foreach($stackedTags as $tag)
                                <span class="hr-stacked-tag-chip" data-key="{{ $tag['key'] }}" data-value="{{ $tag['value'] }}">
                                    <span>{{ $tag['label'] }};</span>
                                    <button type="button" class="remove-tag" onclick="event.stopPropagation(); removeFilterTag('{{ $tag['key'] }}')">&times;</button>
                                </span>
                            @endforeach
                        </div>

                        <!-- Typing Search Input -->
                        <input type="text" name="search" id="hrStackedSearchInput" value="{{ request('search') }}" class="hr-stacked-live-input" placeholder="{{ count($stackedTags) === 0 ? 'Click to stack filters (status, branch, company, dept...) or search employee...' : 'Add more filters or search...' }}" autocomplete="off">

                        <!-- Dropdown Open Indicator Button -->
                        <button type="button" id="hrFilterDropdownToggle" onclick="event.stopPropagation(); toggleFilterDropdown()" style="background: none; border: none; padding: 2px 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; gap: 3px; font-size: 13px;" title="Browse and select filters">
                            <i class="ph ph-funnel" style="font-size: 15px; color: #8e44ad;"></i>
                            <i class="ph ph-caret-down" id="hrDropdownCaretIcon" style="font-size: 11px;"></i>
                        </button>
                    </div>

                </div>

                <!-- Right Pink/Magenta Rounded Generate Button -->
                <button type="submit" class="hr-sketch-generate-btn" title="Generate report records">
                    Generate
                </button>

            </div>

            <!-- Sleek Popover Drawer for Selecting Filters -->
            <div id="hrFilterPickerPopover" class="hr-filter-picker-popover" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-sliders" style="color: #8e44ad; font-size: 16px;"></i>
                        <span>Stack Filter Criteria</span>
                        <span style="font-size: 11.5px; font-weight: 400; color: #64748b;">(Click tags to add/remove from filter bar)</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" onclick="clearAllFilterTags()" style="background: none; border: none; font-size: 11.5px; font-weight: 600; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;">
                            <i class="ph ph-arrow-counter-clockwise"></i> Clear All to Default
                        </button>
                        <button type="button" onclick="closeFilterDropdown()" style="background: #f1f5f9; border: none; border-radius: 6px; padding: 4px 8px; font-size: 12px; cursor: pointer; color: #64748b;">
                            Done
                        </button>
                    </div>
                </div>

                <!-- Scrollable Categories Grid -->
                <div style="max-height: 380px; overflow-y: auto; padding-right: 6px;">
                    
                    @if($showStatus)
                        <!-- 1. Employee Status -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-user-check" style="color: #8e44ad;"></i> Employee Status
                        </div>
                        <div class="hr-picker-pill-grid">
                            @foreach($statuses as $lbl => $val)
                                @php $isActive = (request('employment_status') === (string)$val); @endphp
                                <span class="hr-picker-pill {{ $isActive ? 'active' : '' }}" 
                                      data-filter-key="employment_status" 
                                      data-filter-val="{{ $val }}" 
                                      data-filter-label="{{ strtolower($lbl) }}"
                                      onclick="togglePickerPill(this)">
                                    {{ $lbl }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($showBranch && count($branches) > 0)
                        <!-- 2. Branches -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-storefront" style="color: #0284c7;"></i> Branches
                        </div>
                        <div class="hr-picker-pill-grid">
                            @foreach($branches as $b)
                                @php $isActive = ((string)request('branch_id') === (string)$b->id); @endphp
                                <span class="hr-picker-pill {{ $isActive ? 'active' : '' }}" 
                                      data-filter-key="branch_id" 
                                      data-filter-val="{{ $b->id }}" 
                                      data-filter-label="{{ strtolower($b->name) }}"
                                      onclick="togglePickerPill(this)">
                                    {{ $b->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($showCompany && count($companies) > 0)
                        <!-- 3. Company / Agency -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-buildings" style="color: #d97706;"></i> Company / Agency
                        </div>
                        <div class="hr-picker-pill-grid">
                            @foreach($companies as $c)
                                @php $isActive = ((string)request('company_id') === (string)$c->id); @endphp
                                <span class="hr-picker-pill {{ $isActive ? 'active' : '' }}" 
                                      data-filter-key="company_id" 
                                      data-filter-val="{{ $c->id }}" 
                                      data-filter-label="{{ strtolower($c->name) }}"
                                      onclick="togglePickerPill(this)">
                                    {{ $c->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($showDepartment && count($departments) > 0)
                        <!-- 4. Department -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-tree-structure" style="color: #059669;"></i> Departments
                        </div>
                        <div class="hr-picker-pill-grid">
                            @foreach($departments as $d)
                                @php $isActive = ((string)request('department_id') === (string)$d->id); @endphp
                                <span class="hr-picker-pill {{ $isActive ? 'active' : '' }}" 
                                      data-filter-key="department_id" 
                                      data-filter-val="{{ $d->id }}" 
                                      data-filter-label="{{ strtolower($d->name) }}"
                                      onclick="togglePickerPill(this)">
                                    {{ $d->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($showSource)
                        <!-- 5. Employment Source -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-briefcase" style="color: #7c3aed;"></i> Employment Source
                        </div>
                        <div class="hr-picker-pill-grid">
                            <span class="hr-picker-pill {{ request('employment_source') === 'Direct' ? 'active' : '' }}" 
                                  data-filter-key="employment_source" 
                                  data-filter-val="Direct" 
                                  data-filter-label="direct source"
                                  onclick="togglePickerPill(this)">Direct Hire</span>
                            <span class="hr-picker-pill {{ request('employment_source') === 'Agency' ? 'active' : '' }}" 
                                  data-filter-key="employment_source" 
                                  data-filter-val="Agency" 
                                  data-filter-label="agency source"
                                  onclick="togglePickerPill(this)">Agency Deployed</span>
                        </div>
                    @endif

                    @if($showEmployeeDropdown && count($employees) > 0)
                        <!-- 6. Specific Employee -->
                        <div class="hr-picker-sec-title">
                            <i class="ph ph-user" style="color: #6366f1;"></i> Specific Staff Member
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">
                            <select id="hrPickerEmployeeSelect" class="hr-select" style="font-size: 12px; height: 32px; padding: 4px 8px; flex: 1;" onchange="selectEmployeePill(this)">
                                <option value="">-- Choose specific staff --</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}" data-name="{{ strtolower($e->full_name) }}" {{ ((string)request('employee_id') === (string)$e->id) ? 'selected' : '' }}>
                                        {{ $e->full_name }} ({{ $e->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(isset($slot) && trim($slot) !== '')
                        <!-- Custom Injected Slot Filters (Leave Type, Status, etc.) -->
                        <div class="hr-picker-sec-title" style="margin-top: 14px; border-top: 1px dashed #cbd5e1; padding-top: 12px;">
                            <i class="ph ph-funnel" style="color: #ec4899;"></i> Report-Specific Filters
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                            {{ $slot }}
                        </div>
                    @endif

                </div>

            </div>

        </div>

    </form>
</div>

<script>
    function focusStackedInput(e) {
        if (e.target.tagName !== 'BUTTON' && !e.target.closest('.remove-tag')) {
            var input = document.getElementById('hrStackedSearchInput');
            if (input) input.focus();
            openFilterDropdown();
        }
    }

    function toggleFilterDropdown() {
        var popover = document.getElementById('hrFilterPickerPopover');
        if (!popover) return;
        if (popover.style.display === 'none' || popover.style.display === '') {
            openFilterDropdown();
        } else {
            closeFilterDropdown();
        }
    }

    function openFilterDropdown() {
        var popover = document.getElementById('hrFilterPickerPopover');
        var caret = document.getElementById('hrDropdownCaretIcon');
        if (popover) popover.style.display = 'block';
        if (caret) caret.className = 'ph ph-caret-up';
    }

    function closeFilterDropdown() {
        var popover = document.getElementById('hrFilterPickerPopover');
        var caret = document.getElementById('hrDropdownCaretIcon');
        if (popover) popover.style.display = 'none';
        if (caret) caret.className = 'ph ph-caret-down';
    }

    // Close popover when clicking outside
    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('hrStackedInputGroup');
        var popover = document.getElementById('hrFilterPickerPopover');
        if (popover && popover.style.display === 'block') {
            if (!popover.contains(e.target) && !wrap.contains(e.target)) {
                closeFilterDropdown();
            }
        }
    });

    function togglePickerPill(el) {
        var key = el.getAttribute('data-filter-key');
        var val = el.getAttribute('data-filter-val');
        var label = el.getAttribute('data-filter-label');
        var isActive = el.classList.contains('active');

        var hiddenInput = document.getElementById('hr_input_' + key);

        if (isActive) {
            // Deactivate
            el.classList.remove('active');
            if (hiddenInput) hiddenInput.value = '';
            removeChipByKey(key);
        } else {
            // Remove previous sibling active in the same category
            var group = el.parentElement.querySelectorAll('.hr-picker-pill');
            group.forEach(function(p) { p.classList.remove('active'); });

            el.classList.add('active');
            if (hiddenInput) hiddenInput.value = val;
            addOrUpdateChip(key, val, label);
        }
        updatePlaceholder();
    }

    function selectEmployeePill(selectEl) {
        var val = selectEl.value;
        var opt = selectEl.options[selectEl.selectedIndex];
        var name = opt ? (opt.getAttribute('data-name') || opt.text) : '';
        var hiddenInput = document.getElementById('hr_input_employee_id');

        if (val) {
            if (hiddenInput) hiddenInput.value = val;
            addOrUpdateChip('employee_id', val, name);
        } else {
            if (hiddenInput) hiddenInput.value = '';
            removeChipByKey('employee_id');
        }
        updatePlaceholder();
    }

    function addOrUpdateChip(key, val, label) {
        removeChipByKey(key);
        var container = document.getElementById('hrStackedChipsContainer');
        if (!container) return;

        var chip = document.createElement('span');
        chip.className = 'hr-stacked-tag-chip';
        chip.setAttribute('data-key', key);
        chip.setAttribute('data-value', val);
        chip.innerHTML = '<span>' + label + ';</span>' +
            '<button type="button" class="remove-tag" onclick="event.stopPropagation(); removeFilterTag(\'' + key + '\')">&times;</button>';
        container.appendChild(chip);
    }

    function removeChipByKey(key) {
        var container = document.getElementById('hrStackedChipsContainer');
        if (!container) return;
        var existing = container.querySelector('[data-key="' + key + '"]');
        if (existing) existing.remove();
    }

    function removeFilterTag(key) {
        var hiddenInput = document.getElementById('hr_input_' + key);
        if (hiddenInput) hiddenInput.value = '';
        removeChipByKey(key);

        // Deactivate pill in popover if open
        var pill = document.querySelector('.hr-picker-pill[data-filter-key="' + key + '"].active');
        if (pill) pill.classList.remove('active');

        if (key === 'employee_id') {
            var empSelect = document.getElementById('hrPickerEmployeeSelect');
            if (empSelect) empSelect.value = '';
        }
        updatePlaceholder();
    }

    function clearAllFilterTags() {
        ['employment_status', 'branch_id', 'company_id', 'department_id', 'employee_id', 'employment_source'].forEach(function(k) {
            var inp = document.getElementById('hr_input_' + k);
            if (inp) inp.value = '';
            removeChipByKey(k);
        });

        document.querySelectorAll('.hr-picker-pill.active').forEach(function(p) {
            p.classList.remove('active');
        });

        var empSelect = document.getElementById('hrPickerEmployeeSelect');
        if (empSelect) empSelect.value = '';

        var searchInput = document.getElementById('hrStackedSearchInput');
        if (searchInput) searchInput.value = '';

        updatePlaceholder();
        closeFilterDropdown();
    }

    // Prefix pill click handler: resets back to all employee default
    document.addEventListener('DOMContentLoaded', function() {
        var prefix = document.getElementById('hrFilterPrefixPill');
        if (prefix) {
            prefix.addEventListener('click', function(e) {
                e.stopPropagation();
                clearAllFilterTags();
            });
        }
    });

    function updatePlaceholder() {
        var chips = document.querySelectorAll('.hr-stacked-tag-chip');
        var input = document.getElementById('hrStackedSearchInput');
        if (!input) return;
        if (chips.length === 0) {
            input.placeholder = 'Click to stack filters (status, branch, company, dept...) or search employee...';
        } else {
            input.placeholder = 'Add more filters or search...';
        }
    }
</script>
