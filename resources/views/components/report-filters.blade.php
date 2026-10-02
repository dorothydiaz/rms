@props([
    'id' => null,
    'action',
    'resetUrl' => null,
    'branches' => [],
    'departments' => [],
    'companies' => [],
    'employees' => [],
    'positions' => null,
    'startDate' => null,
    'endDate' => null,
    'showDates' => true,
    'startDateName' => 'date_from',
    'endDateName' => 'date_to',
    'placeholder' => 'Search employees, branch, department, position...',
    'showSearch' => true,
    'showQuickPresets' => true,
    'layout' => 'horizontal', // 'horizontal' or 'card'
    'buttonText' => 'Generate',
    'buttonIcon' => 'ph-funnel-simple',
    'extraControls' => null,
    'showEmployeeDropdown' => false,
    'showStatus' => false,
    'showBranch' => false,
    'showCompany' => false,
    'showDepartment' => false,
    'showSource' => false,
])

@php
    $uid = $id ?? ('hrFilter_' . bin2hex(random_bytes(4)));
    $formId = $id ? ($id . '_form') : 'hrReportFilterForm';
    $searchWrapId = $uid . '_searchWrap';
    $searchInputId = $id ? ($id . '_searchInput') : 'hrFilterSearchInput';
    $suggestionsId = $uid . '_suggestionsList';
    $chipsRowId = $id ? ($id . '_chipsRow') : 'hrFilterChipsRow';
    $tagsJsonId = $uid . '_filterTagsJson';
    $hiddenContainerId = $uid . '_hiddenFilterInputs';

    // Ensure positions list is populated
    if (empty($positions)) {
        try {
            $positions = \App\Models\Hr\Position::orderBy('name')->get();
        } catch (\Throwable $e) {
            $positions = collect();
        }
    }

    // 1. Initial chips rehydrated from URL query params
    $initialChips = [];
    if (request()->filled('filter_tags')) {
        $decoded = json_decode(request('filter_tags'), true);
        if (is_array($decoded)) {
            $initialChips = $decoded;
        }
    }

    if (empty($initialChips)) {
        // Multiple branches
        $branchInputs = request('branches', []);
        if (is_array($branchInputs)) {
            foreach ($branchInputs as $b) {
                if (!$b) continue;
                $bObj = collect($branches)->first(fn($item) => $item->id == $b || strcasecmp($item->name, $b) === 0);
                $bName = $bObj ? $bObj->name : $b;
                $initialChips[] = ['type' => 'branch', 'label' => 'Branch: ' . $bName, 'value' => $bName, 'id' => $bObj ? $bObj->id : null];
            }
        }
        if (request()->filled('branch_id')) {
            $bObj = collect($branches)->firstWhere('id', request('branch_id'));
            $bName = $bObj ? $bObj->name : 'Branch #' . request('branch_id');
            if (!collect($initialChips)->contains('label', 'Branch: ' . $bName)) {
                $initialChips[] = ['type' => 'branch', 'label' => 'Branch: ' . $bName, 'value' => $bName, 'id' => request('branch_id')];
            }
        }

        // Multiple departments
        $deptInputs = request('departments', []);
        if (is_array($deptInputs)) {
            foreach ($deptInputs as $d) {
                if (!$d) continue;
                $dObj = collect($departments)->first(fn($item) => $item->id == $d || strcasecmp($item->name, $d) === 0);
                $dName = $dObj ? $dObj->name : $d;
                $initialChips[] = ['type' => 'department', 'label' => 'Department: ' . $dName, 'value' => $dName, 'id' => $dObj ? $dObj->id : null];
            }
        }
        if (request()->filled('department_id')) {
            $dObj = collect($departments)->firstWhere('id', request('department_id'));
            $dName = $dObj ? $dObj->name : 'Dept #' . request('department_id');
            if (!collect($initialChips)->contains('label', 'Department: ' . $dName)) {
                $initialChips[] = ['type' => 'department', 'label' => 'Department: ' . $dName, 'value' => $dName, 'id' => request('department_id')];
            }
        }

        // Multiple positions
        $posInputs = request('positions', []);
        if (is_array($posInputs)) {
            foreach ($posInputs as $p) {
                if (!$p) continue;
                $pObj = collect($positions)->first(fn($item) => $item->id == $p || strcasecmp($item->name, $p) === 0);
                $pName = $pObj ? $pObj->name : $p;
                $initialChips[] = ['type' => 'position', 'label' => 'Position: ' . $pName, 'value' => $pName, 'id' => $pObj ? $pObj->id : null];
            }
        }
        if (request()->filled('position_id')) {
            $pObj = collect($positions)->firstWhere('id', request('position_id'));
            $pName = $pObj ? $pObj->name : 'Position #' . request('position_id');
            if (!collect($initialChips)->contains('label', 'Position: ' . $pName)) {
                $initialChips[] = ['type' => 'position', 'label' => 'Position: ' . $pName, 'value' => $pName, 'id' => request('position_id')];
            }
        }

        // Employees & Employee IDs
        if (request()->filled('employee_id')) {
            $eVal = request('employee_id');
            $eObj = collect($employees)->first(fn($item) => $item->id == $eVal || $item->employee_id == $eVal);
            if ($eObj) {
                $eName = $eObj->full_name ?? ($eObj->first_name . ' ' . $eObj->last_name);
                $initialChips[] = ['type' => 'employee', 'label' => 'Employee: ' . $eName, 'value' => $eName, 'id' => $eObj->id, 'empid' => $eObj->employee_id];
            } else {
                $initialChips[] = ['type' => 'employee_id', 'label' => 'Employee ID: ' . $eVal, 'value' => $eVal];
            }
        }
        $empInputs = request('employees', []);
        if (is_array($empInputs)) {
            foreach ($empInputs as $emp) {
                if (!$emp) continue;
                $initialChips[] = ['type' => 'employee', 'label' => 'Employee: ' . $emp, 'value' => $emp];
            }
        }
        $empCodeInputs = request('employee_ids', []);
        if (is_array($empCodeInputs)) {
            foreach ($empCodeInputs as $eid) {
                if (!$eid) continue;
                $initialChips[] = ['type' => 'employee_id', 'label' => 'Employee ID: ' . $eid, 'value' => $eid];
            }
        }

        // Statuses & All Active
        $statusVal = request('employment_status', request('status'));
        if ($statusVal) {
            if ($statusVal === 'ACTIVE_ALL' || strcasecmp($statusVal, 'All Active') === 0 || strcasecmp($statusVal, 'Active Staff Only') === 0) {
                $initialChips[] = ['type' => 'status', 'label' => 'Status: All Active', 'value' => 'ACTIVE_ALL'];
            } else {
                $initialChips[] = ['type' => 'status', 'label' => 'Status: ' . $statusVal, 'value' => $statusVal];
            }
        }

        // Scope All Employees
        if (request('scope') === 'ALL' || request('all_employees') || in_array('ALL', (array)request('statuses', []))) {
            $initialChips[] = ['type' => 'scope', 'label' => 'Scope: All Employees', 'value' => 'ALL'];
        }

        // Free-text Search
        if (request()->filled('search')) {
            $initialChips[] = ['type' => 'search', 'label' => 'Search: ' . request('search'), 'value' => request('search')];
        }
    }

    $hasAllActiveChip = collect($initialChips)->contains(fn($c) => ($c['type'] ?? '') === 'status' && in_array($c['value'] ?? '', ['ACTIVE_ALL', 'All Active', 'Active Staff Only']));
    $hasAllEmployeesChip = collect($initialChips)->contains(fn($c) => ($c['type'] ?? '') === 'scope' || in_array($c['value'] ?? '', ['ALL', 'All Employees']));

    // Lookup collections for autocomplete
    $lookupBranches = collect($branches)->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->values();
    $lookupDepartments = collect($departments)->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->values();
    $lookupPositions = collect($positions)->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values();
    $lookupEmployees = collect($employees)->map(fn($e) => [
        'id' => $e->id,
        'employee_id' => $e->employee_id,
        'name' => $e->full_name ?? ($e->first_name . ' ' . $e->last_name),
    ])->values();
    $lookupStatuses = ['All Active', 'All Employees', 'Regular', 'Probationary', 'Contractual', 'Part-time', 'Seasonal', 'Approved', 'Pending', 'Rejected'];
@endphp

<div class="hr-report-filters-bar {{ $layout === 'card' ? 'hr-layout-card' : '' }}" id="{{ $uid }}_bar">
    <form method="GET" action="{{ $action }}" id="{{ $formId }}">
        <!-- Hidden input holding all active filter tags as JSON -->
        <input type="hidden" name="filter_tags" id="{{ $tagsJsonId }}" value="{{ json_encode($initialChips) }}">
        
        <!-- Dynamically managed individual inputs for backend compatibility -->
        <div id="{{ $hiddenContainerId }}" class="hr-hidden-inputs-container" style="display: none;"></div>

        {{-- Optional Extra Controls (e.g. Pay Period select) --}}
        @if(isset($extraControls) && $extraControls)
            {{ $extraControls }}
        @endif

        @if($layout === 'card')
            {{-- Card / Stacked Layout --}}
            <div class="hr-filter-controls-row">
                @if($showDates)
                    <div class="hr-card-dates-grid">
                        <!-- 1. Start Date -->
                        <div class="hr-card-date-field-group">
                            <label class="hr-card-date-label">
                                <i class="ph ph-calendar-blank" style="color: #ec4899;"></i> Start Date
                            </label>
                            <input type="date" 
                                   name="{{ $startDateName }}" 
                                   value="{{ $startDate ?? request($startDateName, request('date_from')) }}" 
                                   class="hr-glass-date-input" 
                                   title="Start Date"
                                   aria-label="Start Date">
                        </div>

                        <!-- 2. End Date -->
                        <div class="hr-card-date-field-group">
                            <label class="hr-card-date-label">
                                <i class="ph ph-calendar-blank" style="color: #a855f7;"></i> End Date
                            </label>
                            <input type="date" 
                                   name="{{ $endDateName }}" 
                                   value="{{ $endDate ?? request($endDateName, request('date_to')) }}" 
                                   class="hr-glass-date-input" 
                                   title="End Date"
                                   aria-label="End Date">
                        </div>
                    </div>
                @endif

                @if($showQuickPresets)
                    <div class="hr-filter-quick-presets" id="{{ $uid }}_presets">
                        <span class="hr-preset-label"><i class="ph ph-sparkle"></i> Quick scope:</span>
                        <button type="button" 
                                class="hr-preset-pill {{ $hasAllActiveChip ? 'active' : '' }}" 
                                id="{{ $uid }}_preset_active" 
                                data-preset="all-active" 
                                title="Filter for active staff only">
                            <i class="ph ph-check-circle"></i> All Active
                        </button>
                        <button type="button" 
                                class="hr-preset-pill {{ $hasAllEmployeesChip ? 'active' : '' }}" 
                                id="{{ $uid }}_preset_all" 
                                data-preset="all-employees" 
                                title="Include all active & inactive staff">
                            <i class="ph ph-users"></i> All Employees
                        </button>
                    </div>
                @endif

                <!-- Search Bar -->
                <div class="hr-search-bar-wrap" id="{{ $searchWrapId }}">
                    <i class="ph ph-magnifying-glass hr-search-bar-icon"></i>
                    <input type="text" 
                           id="{{ $searchInputId }}" 
                           class="hr-search-bar-input hrFilterSearchInput" 
                           placeholder="{{ $placeholder }}" 
                           autocomplete="off" 
                           aria-label="Search criteria">
                    
                    <!-- Autocomplete suggestions dropdown -->
                    <div id="{{ $suggestionsId }}" class="hr-filter-suggestions-list" style="display: none;"></div>
                </div>

                <!-- Removable Tags / Chips Row -->
                <div class="hr-filter-chips-row hrFilterChipsRow" id="{{ $chipsRowId }}">
                    @foreach($initialChips as $idx => $chip)
                        @php
                            $parts = explode(':', $chip['label'] ?? '', 2);
                            $cat = count($parts) === 2 ? trim($parts[0]) : '';
                            $val = count($parts) === 2 ? trim($parts[1]) : $chip['label'];
                        @endphp
                        <span class="hr-filter-tag-chip" data-index="{{ $idx }}">
                            <span class="hr-chip-text">
                                @if($cat)
                                    <strong class="hr-chip-category">{{ $cat }}:</strong>
                                @endif
                                <span class="hr-chip-value">{{ $val }}</span>
                            </span>
                            <button type="button" class="hr-chip-remove-btn" data-index="{{ $idx }}" aria-label="Remove filter">&times;</button>
                        </span>
                    @endforeach
                    @if(count($initialChips) >= 2)
                        <button type="button" class="hr-clear-all-chips-btn"><i class="ph ph-x"></i> Clear all</button>
                    @endif
                </div>

                <!-- Submit / Download Button -->
                <button type="submit" class="hr-sketch-generate-btn" title="{{ $buttonText }}">
                    <i class="ph {{ $buttonIcon }}"></i>
                    <span>{{ $buttonText }}</span>
                </button>
            </div>

        @else
            {{-- Horizontal Report Layout (3 Main Controls Row) --}}
            <div class="hr-filter-controls-row">
                @if($showDates)
                    <!-- 1. Start Date -->
                    <div class="hr-date-picker-box" title="Start Date">
                        <span class="hr-date-box-label">Start Date</span>
                        <input type="date" 
                               name="{{ $startDateName }}" 
                               value="{{ $startDate ?? request($startDateName, request('date_from')) }}" 
                               class="hr-date-field" 
                               aria-label="Start Date">
                    </div>

                    <!-- 2. End Date -->
                    <div class="hr-date-picker-box" title="End Date">
                        <span class="hr-date-box-label">End Date</span>
                        <input type="date" 
                               name="{{ $endDateName }}" 
                               value="{{ $endDate ?? request($endDateName, request('date_to')) }}" 
                               class="hr-date-field" 
                               aria-label="End Date">
                    </div>
                @endif

                <!-- 3. Search Bar -->
                <div class="hr-search-bar-wrap" id="{{ $searchWrapId }}">
                    <i class="ph ph-magnifying-glass hr-search-bar-icon"></i>
                    <input type="text" 
                           id="{{ $searchInputId }}" 
                           class="hr-search-bar-input hrFilterSearchInput" 
                           placeholder="{{ $placeholder }}" 
                           autocomplete="off" 
                           aria-label="Search criteria">
                    
                    <!-- Autocomplete suggestions dropdown -->
                    <div id="{{ $suggestionsId }}" class="hr-filter-suggestions-list" style="display: none;"></div>
                </div>

                <!-- Generate Button -->
                <button type="submit" class="hr-sketch-generate-btn" title="Generate report with active criteria">
                    <i class="ph {{ $buttonIcon }}"></i>
                    <span>{{ $buttonText }}</span>
                </button>
            </div>

            <!-- Quick Presets Row & Removable Tags / Chips Row -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px;">
                <!-- Removable Tags / Chips Row -->
                <div class="hr-filter-chips-row hrFilterChipsRow" id="{{ $chipsRowId }}">
                    @foreach($initialChips as $idx => $chip)
                        @php
                            $parts = explode(':', $chip['label'] ?? '', 2);
                            $cat = count($parts) === 2 ? trim($parts[0]) : '';
                            $val = count($parts) === 2 ? trim($parts[1]) : $chip['label'];
                        @endphp
                        <span class="hr-filter-tag-chip" data-index="{{ $idx }}">
                            <span class="hr-chip-text">
                                @if($cat)
                                    <strong class="hr-chip-category">{{ $cat }}:</strong>
                                @endif
                                <span class="hr-chip-value">{{ $val }}</span>
                            </span>
                            <button type="button" class="hr-chip-remove-btn" data-index="{{ $idx }}" aria-label="Remove filter">&times;</button>
                        </span>
                    @endforeach
                    @if(count($initialChips) >= 2)
                        <button type="button" class="hr-clear-all-chips-btn"><i class="ph ph-x"></i> Clear all</button>
                    @endif
                </div>

                @if($showQuickPresets)
                    <div class="hr-filter-quick-presets" id="{{ $uid }}_presets">
                        <span class="hr-preset-label"><i class="ph ph-sparkle"></i> Quick scope:</span>
                        <button type="button" 
                                class="hr-preset-pill {{ $hasAllActiveChip ? 'active' : '' }}" 
                                id="{{ $uid }}_preset_active" 
                                data-preset="all-active" 
                                title="Filter for active staff only">
                            <i class="ph ph-check-circle"></i> All Active
                        </button>
                        <button type="button" 
                                class="hr-preset-pill {{ $hasAllEmployeesChip ? 'active' : '' }}" 
                                id="{{ $uid }}_preset_all" 
                                data-preset="all-employees" 
                                title="Include all active & inactive staff">
                            <i class="ph ph-users"></i> All Employees
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </form>
</div>

<script>
(function() {
    const rootForm = document.getElementById(@json($formId));
    if (!rootForm) return;

    let filterTags = @json($initialChips);
    const lookupData = {
        branches: @json($lookupBranches),
        departments: @json($lookupDepartments),
        positions: @json($lookupPositions),
        employees: @json($lookupEmployees),
        statuses: @json($lookupStatuses)
    };

    let activeSuggestionIndex = -1;
    let currentSuggestions = [];

    const searchInput = rootForm.querySelector('.hrFilterSearchInput');
    const suggestionsList = document.getElementById(@json($suggestionsId));
    const chipsRow = rootForm.querySelector('.hrFilterChipsRow');
    const hiddenJsonInput = document.getElementById(@json($tagsJsonId));
    const hiddenInputsContainer = document.getElementById(@json($hiddenContainerId));
    const searchWrap = document.getElementById(@json($searchWrapId));
    const presetActiveBtn = document.getElementById(@json($uid . '_preset_active'));
    const presetAllBtn = document.getElementById(@json($uid . '_preset_all'));

    // Sync state on load
    syncHiddenInputs();
    updatePresetPillStates();

    // Event delegation on chips row for removing chips
    if (chipsRow) {
        chipsRow.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.hr-chip-remove-btn');
            if (removeBtn) {
                const idx = parseInt(removeBtn.getAttribute('data-index'), 10);
                if (!isNaN(idx)) {
                    removeFilterTag(idx);
                }
                return;
            }
            const clearBtn = e.target.closest('.hr-clear-all-chips-btn');
            if (clearBtn) {
                clearAllFilterTags();
            }
        });
    }

    // Preset button click handlers
    if (presetActiveBtn) {
        presetActiveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            togglePresetActive();
        });
    }

    if (presetAllBtn) {
        presetAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            togglePresetAll();
        });
    }

    function togglePresetActive() {
        const activeIdx = filterTags.findIndex(t => 
            (t.type === 'status' && (t.value === 'ACTIVE_ALL' || t.value === 'All Active')) ||
            t.label === 'Status: All Active'
        );

        if (activeIdx >= 0) {
            // Toggle off
            filterTags.splice(activeIdx, 1);
        } else {
            // Remove any "Scope: All Employees" tag
            filterTags = filterTags.filter(t => t.type !== 'scope' && t.value !== 'ALL' && t.label !== 'Scope: All Employees');
            // Remove any other individual status tags to avoid contradiction
            filterTags = filterTags.filter(t => t.type !== 'status');
            filterTags.push({
                type: 'status',
                label: 'Status: All Active',
                value: 'ACTIVE_ALL'
            });
        }
        renderChips();
        syncHiddenInputs();
        updatePresetPillStates();
        if (searchInput) searchInput.focus();
    }

    function togglePresetAll() {
        const allIdx = filterTags.findIndex(t => 
            t.type === 'scope' || t.value === 'ALL' || t.label === 'Scope: All Employees'
        );

        if (allIdx >= 0) {
            // Toggle off
            filterTags.splice(allIdx, 1);
        } else {
            // Remove "Status: All Active" and other status tags
            filterTags = filterTags.filter(t => t.label !== 'Status: All Active' && t.value !== 'ACTIVE_ALL');
            filterTags = filterTags.filter(t => t.type !== 'status');
            filterTags.push({
                type: 'scope',
                label: 'Scope: All Employees',
                value: 'ALL'
            });
        }
        renderChips();
        syncHiddenInputs();
        updatePresetPillStates();
        if (searchInput) searchInput.focus();
    }

    function updatePresetPillStates() {
        const hasActive = filterTags.some(t => 
            (t.type === 'status' && (t.value === 'ACTIVE_ALL' || t.value === 'All Active')) ||
            t.label === 'Status: All Active'
        );
        const hasAll = filterTags.some(t => 
            t.type === 'scope' || t.value === 'ALL' || t.label === 'Scope: All Employees'
        );

        if (presetActiveBtn) {
            if (hasActive) {
                presetActiveBtn.classList.add('active');
            } else {
                presetActiveBtn.classList.remove('active');
            }
        }

        if (presetAllBtn) {
            if (hasAll) {
                presetAllBtn.classList.add('active');
            } else {
                presetAllBtn.classList.remove('active');
            }
        }
    }

    function removeFilterTag(index) {
        if (index >= 0 && index < filterTags.length) {
            filterTags.splice(index, 1);
            renderChips();
            syncHiddenInputs();
            updatePresetPillStates();
            if (searchInput) searchInput.focus();
        }
    }

    // Expose removeFilterTag and clearAllFilterTags on form for fallback
    rootForm.removeFilterTag = removeFilterTag;
    if (!window.removeFilterTag) {
        window.removeFilterTag = removeFilterTag;
    }

    function clearAllFilterTags() {
        filterTags = [];
        renderChips();
        syncHiddenInputs();
        updatePresetPillStates();
        if (searchInput) searchInput.focus();
    }

    function addFilterTag(tagObj) {
        if (!tagObj || !tagObj.label) return;
        
        // Prevent duplicate tags with exact same label
        const exists = filterTags.some(t => t.label.toLowerCase() === tagObj.label.toLowerCase());
        if (!exists) {
            // If adding "All Active", remove "All Employees"
            if (tagObj.value === 'ACTIVE_ALL' || tagObj.label === 'Status: All Active') {
                filterTags = filterTags.filter(t => t.type !== 'scope' && t.value !== 'ALL');
            }
            // If adding "All Employees", remove "All Active"
            if (tagObj.value === 'ALL' || tagObj.label === 'Scope: All Employees') {
                filterTags = filterTags.filter(t => t.value !== 'ACTIVE_ALL' && t.label !== 'Status: All Active');
            }
            filterTags.push(tagObj);
            renderChips();
            syncHiddenInputs();
            updatePresetPillStates();
        }

        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        closeSuggestions();
    }

    function renderChips() {
        if (!chipsRow) return;
        chipsRow.innerHTML = '';

        filterTags.forEach((chip, idx) => {
            const span = document.createElement('span');
            span.className = 'hr-filter-tag-chip';
            span.setAttribute('data-index', idx);

            const parts = (chip.label || '').split(':');
            let cat = '';
            let val = chip.label || '';
            if (parts.length >= 2) {
                cat = parts[0].trim();
                val = parts.slice(1).join(':').trim();
            }

            span.innerHTML = `
                <span class="hr-chip-text">
                    ${cat ? `<strong class="hr-chip-category">${escapeHtml(cat)}:</strong> ` : ''}
                    <span class="hr-chip-value">${escapeHtml(val)}</span>
                </span>
                <button type="button" class="hr-chip-remove-btn" data-index="${idx}" aria-label="Remove filter">&times;</button>
            `;
            chipsRow.appendChild(span);
        });

        if (filterTags.length >= 2) {
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'hr-clear-all-chips-btn';
            clearBtn.innerHTML = '<i class="ph ph-x"></i> Clear all';
            chipsRow.appendChild(clearBtn);
        }
    }

    function syncHiddenInputs() {
        if (hiddenJsonInput) {
            hiddenJsonInput.value = JSON.stringify(filterTags);
        }

        if (!hiddenInputsContainer) return;
        hiddenInputsContainer.innerHTML = '';

        let branchCount = 0;
        let lastBranchId = null;
        let deptCount = 0;
        let lastDeptId = null;
        let posCount = 0;
        let lastPosId = null;
        let empCount = 0;
        let lastEmpId = null;
        let statusCount = 0;
        let lastStatus = null;
        let searchCount = 0;
        let lastSearch = null;
        let scopeAll = false;

        filterTags.forEach(t => {
            const type = (t.type || '').toLowerCase();
            const val = t.value || '';
            const id = t.id || null;
            const empid = t.empid || null;

            if (type === 'branch') {
                branchCount++;
                if (id) lastBranchId = id;
                createHiddenInput('branches[]', val);
            } else if (type === 'department') {
                deptCount++;
                if (id) lastDeptId = id;
                createHiddenInput('departments[]', val);
            } else if (type === 'position') {
                posCount++;
                if (id) lastPosId = id;
                createHiddenInput('positions[]', val);
            } else if (type === 'employee') {
                empCount++;
                if (id) lastEmpId = id;
                createHiddenInput('employees[]', val);
                if (empid) createHiddenInput('employee_ids[]', empid);
            } else if (type === 'employee_id') {
                empCount++;
                lastEmpId = val;
                createHiddenInput('employee_ids[]', val);
            } else if (type === 'status') {
                statusCount++;
                lastStatus = val;
                createHiddenInput('statuses[]', val);
                if (val === 'ACTIVE_ALL') {
                    createHiddenInput('employment_status', 'ACTIVE_ALL');
                }
            } else if (type === 'scope' || val === 'ALL') {
                scopeAll = true;
                createHiddenInput('scope', 'ALL');
                createHiddenInput('all_employees', '1');
            } else if (type === 'search') {
                searchCount++;
                lastSearch = val;
            }
        });

        // Set backwards-compatible single parameters
        if (branchCount === 1 && lastBranchId) createHiddenInput('branch_id', lastBranchId);
        if (deptCount === 1 && lastDeptId) createHiddenInput('department_id', lastDeptId);
        if (posCount === 1 && lastPosId) createHiddenInput('position_id', lastPosId);
        if (empCount === 1 && lastEmpId) createHiddenInput('employee_id', lastEmpId);
        if (statusCount === 1 && lastStatus) createHiddenInput('status', lastStatus);
        if (searchCount === 1 && lastSearch) createHiddenInput('search', lastSearch);
    }

    function createHiddenInput(name, value) {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value;
        hiddenInputsContainer.appendChild(inp);
    }

    function parseFreeText(rawText) {
        const text = rawText.trim();
        if (!text) return null;

        const lower = text.toLowerCase();

        // Check for "all active" or "active"
        if (lower === 'all active' || lower === 'active staff only') {
            return {
                type: 'status',
                label: 'Status: All Active',
                value: 'ACTIVE_ALL'
            };
        }

        // Check for "all employee" or "all employees"
        if (lower === 'all employee' || lower === 'all employees' || lower === 'all staff') {
            return {
                type: 'scope',
                label: 'Scope: All Employees',
                value: 'ALL'
            };
        }

        // 1. Explicit prefix matching
        const prefixPatterns = [
            { regex: /^(branch|branches)\s*:\s*(.+)$/i, type: 'branch', labelPrefix: 'Branch: ' },
            { regex: /^(department|departments|dept)\s*:\s*(.+)$/i, type: 'department', labelPrefix: 'Department: ' },
            { regex: /^(position|positions|pos)\s*:\s*(.+)$/i, type: 'position', labelPrefix: 'Position: ' },
            { regex: /^(employee\s*id|empid|id)\s*:\s*(.+)$/i, type: 'employee_id', labelPrefix: 'Employee ID: ' },
            { regex: /^(employee|employees|emp|staff)\s*:\s*(.+)$/i, type: 'employee', labelPrefix: 'Employee: ' },
            { regex: /^(status|statuses)\s*:\s*(.+)$/i, type: 'status', labelPrefix: 'Status: ' },
            { regex: /^(search)\s*:\s*(.+)$/i, type: 'search', labelPrefix: 'Search: ' },
        ];

        for (let p of prefixPatterns) {
            const m = text.match(p.regex);
            if (m && m[2]) {
                const val = m[2].trim();
                let matchedId = null;
                let matchedEmpId = null;

                if (p.type === 'branch') {
                    const b = lookupData.branches.find(x => x.name.toLowerCase() === val.toLowerCase());
                    if (b) matchedId = b.id;
                } else if (p.type === 'department') {
                    const d = lookupData.departments.find(x => x.name.toLowerCase() === val.toLowerCase());
                    if (d) matchedId = d.id;
                } else if (p.type === 'position') {
                    const pos = lookupData.positions.find(x => x.name.toLowerCase() === val.toLowerCase());
                    if (pos) matchedId = pos.id;
                } else if (p.type === 'employee') {
                    const emp = lookupData.employees.find(x => x.name.toLowerCase() === val.toLowerCase() || (x.employee_id && x.employee_id.toLowerCase() === val.toLowerCase()));
                    if (emp) {
                        matchedId = emp.id;
                        matchedEmpId = emp.employee_id;
                    }
                }

                return {
                    type: p.type,
                    label: p.labelPrefix + val,
                    value: val,
                    id: matchedId,
                    empid: matchedEmpId
                };
            }
        }

        // 2. Intelligent Category Matching without explicit prefix
        // Branch match
        const bMatch = lookupData.branches.find(b => b.name.toLowerCase() === lower || b.name.toLowerCase().includes(lower));
        if (bMatch && (lower.length > 2 || bMatch.name.toLowerCase() === lower)) {
            return {
                type: 'branch',
                label: 'Branch: ' + bMatch.name,
                value: bMatch.name,
                id: bMatch.id
            };
        }

        // Department match
        const dMatch = lookupData.departments.find(d => d.name.toLowerCase() === lower || d.name.toLowerCase().includes(lower));
        if (dMatch && (lower.length > 2 || dMatch.name.toLowerCase() === lower)) {
            return {
                type: 'department',
                label: 'Department: ' + dMatch.name,
                value: dMatch.name,
                id: dMatch.id
            };
        }

        // Position match
        const pMatch = lookupData.positions.find(p => p.name.toLowerCase() === lower || p.name.toLowerCase().includes(lower));
        if (pMatch && (lower.length > 2 || pMatch.name.toLowerCase() === lower)) {
            return {
                type: 'position',
                label: 'Position: ' + pMatch.name,
                value: pMatch.name,
                id: pMatch.id
            };
        }

        // Employee match
        const eMatch = lookupData.employees.find(e => {
            const nameMatch = e.name && e.name.toLowerCase().includes(lower);
            const idMatch = e.employee_id && e.employee_id.toLowerCase().includes(lower);
            return nameMatch || idMatch;
        });
        if (eMatch && lower.length >= 3) {
            return {
                type: 'employee',
                label: 'Employee: ' + eMatch.name,
                value: eMatch.name,
                id: eMatch.id,
                empid: eMatch.employee_id
            };
        }

        // Status match
        const sMatch = lookupData.statuses.find(s => s.toLowerCase() === lower || s.toLowerCase().includes(lower));
        if (sMatch) {
            if (sMatch === 'All Active') {
                return { type: 'status', label: 'Status: All Active', value: 'ACTIVE_ALL' };
            }
            if (sMatch === 'All Employees') {
                return { type: 'scope', label: 'Scope: All Employees', value: 'ALL' };
            }
            return {
                type: 'status',
                label: 'Status: ' + sMatch,
                value: sMatch
            };
        }

        // Pure digits or EMP- prefix -> Employee ID
        if (/^\d{3,}$/.test(text) || /^emp[-\s]?\d+$/i.test(text)) {
            return {
                type: 'employee_id',
                label: 'Employee ID: ' + text,
                value: text
            };
        }

        // Unknown text does not generate a fake keyword tag
        return null;
    }

    // Autocomplete Suggestions Generator
    function getSuggestions(query) {
        const q = (query || '').trim().toLowerCase();
        const results = [];

        // When search is blank or matches "all" / "act" / "emp", place presets at the very top
        const showPresets = !q || 'all active'.includes(q) || 'all employees'.includes(q) || 'active'.includes(q) || 'all'.includes(q) || 'staff'.includes(q);
        
        if (showPresets) {
            results.push({
                type: 'status',
                badge: 'Preset',
                badgeClass: 'hr-badge-status',
                icon: 'ph-check-circle',
                label: 'Status: All Active',
                subtext: 'Active workforce only - exclude resigned & terminated',
                value: 'ACTIVE_ALL'
            });
            results.push({
                type: 'scope',
                badge: 'Preset',
                badgeClass: 'hr-badge-employee',
                icon: 'ph-users',
                label: 'Scope: All Employees',
                subtext: 'All staff records - active & inactive masterlist',
                value: 'ALL'
            });
        }

        if (!q) {
            // Show top branches & departments as quick starters
            lookupData.branches.slice(0, 3).forEach(b => {
                results.push({
                    type: 'branch',
                    badge: 'Branch',
                    badgeClass: 'hr-badge-branch',
                    icon: 'ph-storefront',
                    label: 'Branch: ' + b.name,
                    value: b.name,
                    id: b.id
                });
            });
            lookupData.departments.slice(0, 2).forEach(d => {
                results.push({
                    type: 'department',
                    badge: 'Department',
                    badgeClass: 'hr-badge-department',
                    icon: 'ph-buildings',
                    label: 'Department: ' + d.name,
                    value: d.name,
                    id: d.id
                });
            });
            return results.slice(0, 8);
        }

        // Branches
        lookupData.branches.forEach(b => {
            if (b.name.toLowerCase().includes(q)) {
                results.push({
                    type: 'branch',
                    badge: 'Branch',
                    badgeClass: 'hr-badge-branch',
                    icon: 'ph-storefront',
                    label: 'Branch: ' + b.name,
                    value: b.name,
                    id: b.id
                });
            }
        });

        // Departments
        lookupData.departments.forEach(d => {
            if (d.name.toLowerCase().includes(q)) {
                results.push({
                    type: 'department',
                    badge: 'Department',
                    badgeClass: 'hr-badge-department',
                    icon: 'ph-buildings',
                    label: 'Department: ' + d.name,
                    value: d.name,
                    id: d.id
                });
            }
        });

        // Positions
        lookupData.positions.forEach(p => {
            if (p.name.toLowerCase().includes(q)) {
                results.push({
                    type: 'position',
                    badge: 'Position',
                    badgeClass: 'hr-badge-position',
                    icon: 'ph-identification-badge',
                    label: 'Position: ' + p.name,
                    value: p.name,
                    id: p.id
                });
            }
        });

        // Employees (by name or employee_id)
        lookupData.employees.forEach(e => {
            const nameMatch = e.name && e.name.toLowerCase().includes(q);
            const idMatch = e.employee_id && e.employee_id.toLowerCase().includes(q);
            if (nameMatch || idMatch) {
                results.push({
                    type: 'employee',
                    badge: 'Employee',
                    badgeClass: 'hr-badge-employee',
                    icon: 'ph-user',
                    label: 'Employee: ' + e.name,
                    subtext: e.employee_id ? `ID: ${e.employee_id}` : '',
                    value: e.name,
                    id: e.id,
                    empid: e.employee_id
                });
            }
        });

        // Statuses
        lookupData.statuses.forEach(s => {
            if (s !== 'All Active' && s !== 'All Employees' && s.toLowerCase().includes(q)) {
                results.push({
                    type: 'status',
                    badge: 'Status',
                    badgeClass: 'hr-badge-status',
                    icon: 'ph-tag',
                    label: 'Status: ' + s,
                    value: s
                });
            }
        });

        return results.slice(0, 8); // Top 8 results
    }

    function renderSuggestions(suggestions) {
        if (!suggestionsList) return;
        currentSuggestions = suggestions;
        activeSuggestionIndex = -1;

        if (suggestions.length === 0) {
            closeSuggestions();
            return;
        }

        suggestionsList.innerHTML = '';
        suggestions.forEach((item, i) => {
            const div = document.createElement('div');
            div.className = 'hr-suggestion-item';
            div.setAttribute('data-index', i);
            const cleanSub = item.subtext ? item.subtext.replace(/^\(|\)$/g, '').trim() : '';
            const subtextHtml = cleanSub ? `<span class="hr-suggestion-subtext">(${escapeHtml(cleanSub)})</span>` : '';
            div.innerHTML = `
                <div class="hr-suggestion-left">
                    <i class="ph ${item.icon}" style="color: #64748b; font-size: 14px; flex-shrink: 0;"></i>
                    <span class="hr-suggestion-badge ${item.badgeClass}">${escapeHtml(item.badge)}</span>
                    <span class="hr-suggestion-title">${escapeHtml(item.label || item.value)}</span>
                    ${subtextHtml}
                </div>
                <i class="ph ph-plus" style="font-size: 13px; color: #94a3b8; flex-shrink: 0; margin-left: auto;"></i>
            `;
            div.addEventListener('click', () => {
                addFilterTag(item);
            });
            suggestionsList.appendChild(div);
        });

        suggestionsList.scrollTop = 0;
        suggestionsList.style.display = 'block';
    }

    function closeSuggestions() {
        if (suggestionsList) suggestionsList.style.display = 'none';
        activeSuggestionIndex = -1;
        currentSuggestions = [];
    }

    if (searchInput) {
        // Focus opens suggestions
        searchInput.addEventListener('focus', function() {
            renderSuggestions(getSuggestions(searchInput.value));
        });

        // Typing in search input triggers autocomplete
        searchInput.addEventListener('input', function() {
            renderSuggestions(getSuggestions(searchInput.value));
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown') {
                if (currentSuggestions.length > 0) {
                    e.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % currentSuggestions.length;
                    highlightSuggestion();
                }
            } else if (e.key === 'ArrowUp') {
                if (currentSuggestions.length > 0) {
                    e.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex - 1 + currentSuggestions.length) % currentSuggestions.length;
                    highlightSuggestion();
                }
            } else if (e.key === 'Enter') {
                if (activeSuggestionIndex >= 0 && activeSuggestionIndex < currentSuggestions.length) {
                    e.preventDefault();
                    addFilterTag(currentSuggestions[activeSuggestionIndex]);
                } else if (searchInput.value.trim() !== '') {
                    e.preventDefault();
                    const tag = parseFreeText(searchInput.value);
                    if (tag) {
                        addFilterTag(tag);
                    }
                } else {
                    // Empty input on Enter: Allow regular form submit
                }
            } else if (e.key === 'Backspace' && searchInput.value === '') {
                // Remove last chip on backspace if input is empty
                if (filterTags.length > 0) {
                    removeFilterTag(filterTags.length - 1);
                }
            } else if (e.key === 'Escape') {
                closeSuggestions();
            }
        });

        // Close on blur / click outside
        document.addEventListener('click', function(e) {
            if (searchWrap && !searchWrap.contains(e.target)) {
                closeSuggestions();
            }
        });
    }

    function highlightSuggestion() {
        if (!suggestionsList) return;
        const items = suggestionsList.querySelectorAll('.hr-suggestion-item');
        items.forEach((item, idx) => {
            if (idx === activeSuggestionIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
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
})();
</script>
