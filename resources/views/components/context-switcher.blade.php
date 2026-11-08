@php
    $checkedCompanyIds = $checkedCompanyIds ?? [];
    $checkedBranchIds = $checkedBranchIds ?? [];
    $totalCheckedCount = count($checkedCompanyIds) + count($checkedBranchIds);
    $isConsolidatedView = $isConsolidatedView ?? false;
    $currentCompany = $currentCompany ?? null;
    $currentBranch = $currentBranch ?? null;
    $navCompaniesTree = $navCompaniesTree ?? collect();
    $allowedCompanies = $allowedCompanies ?? collect();
@endphp

<div class="entity-context-switcher" id="entityContextSwitcher">
    <!-- Trigger Button in Header (Matches Header Date Badge Exactly) -->
    <button type="button" class="entity-switcher-btn {{ $isConsolidatedView ? 'is-consolidated' : '' }}" id="entitySwitcherBtn" aria-haspopup="true" aria-expanded="false" title="Click to open Entity Selector & View Scope">
        @if($currentCompany && $currentCompany->company_logo)
            <img src="{{ asset('storage/' . $currentCompany->company_logo) }}" alt="Logo" class="entity-logo-mini">
        @else
            <i class="ph {{ $isConsolidatedView ? 'ph-stack' : 'ph-buildings' }}"></i>
        @endif
        
        <span class="company-name" title="{{ $currentCompany?->company_trade_name ?: $currentCompany?->company_legal_name }}">
            {{ $currentCompany ? ($currentCompany->company_trade_name ?: $currentCompany->company_legal_name) : 'Select Company' }}
        </span>

        @if(!$isConsolidatedView && $currentBranch)
            <span class="entity-sep">•</span>
            <span class="branch-name" title="{{ $currentBranch->branch_name }}">{{ $currentBranch->branch_name }}</span>
        @endif

        @if($isConsolidatedView)
            <span class="badge-mode consolidated">Consolidated</span>
        @elseif($totalCheckedCount > 1)
            <span class="badge-mode scope" title="Multi-entity aggregated view active">{{ $totalCheckedCount }} in Scope</span>
        @endif

        <i class="ph ph-caret-down entity-chevron"></i>
    </button>

    <!-- Context Switcher Dropdown Panel (Spacious Minimalist Design) -->
    <div class="entity-switcher-dropdown" id="entitySwitcherDropdown">
        <!-- Pinned Header -->
        <div class="entity-dropdown-header">
            <div class="dropdown-title-row">
                <div class="dropdown-title">
                    <div class="dropdown-title-badge">
                        <i class="ph ph-tree-structure"></i>
                    </div>
                    <div>
                        <div class="dropdown-title-text">Entity Hierarchy & Scope</div>
                        <div class="dropdown-title-sub">Multi-branch view & active workspace</div>
                    </div>
                </div>
                <button type="button" class="btn-consolidated-toggle {{ $isConsolidatedView ? 'active' : '' }}" id="btnToggleConsolidated" title="Toggle cross-branch consolidated view mode" onclick="RMSContextSwitcher.toggleConsolidated()">
                    <i class="ph {{ $isConsolidatedView ? 'ph-check-circle' : 'ph-stack' }}"></i>
                    <span>{{ $isConsolidatedView ? 'Consolidated Active' : 'Consolidate View' }}</span>
                </button>
            </div>
            
            <!-- Minimalist Hint Banner -->
            <div class="entity-scope-instruction">
                <div class="instruction-item">
                    <span class="instruction-pill pill-scope"><i class="ph ph-check-square"></i> Scope</span>
                    <span class="instruction-text">Checkboxes set <strong>View Scope</strong></span>
                </div>
                <div class="instruction-dot"></div>
                <div class="instruction-item">
                    <span class="instruction-pill pill-write"><i class="ph ph-radio-button"></i> Active</span>
                    <span class="instruction-text">Click row to set <strong>Write Target</strong></span>
                </div>
            </div>

            <!-- Modern Search Box -->
            <div class="entity-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="entitySearchInput" placeholder="Search company, branch, or code..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="entitySearchClear" style="display: none;">&times;</button>
            </div>

            <!-- Table Header Legend for Aligned Columns -->
            <div class="entity-grid-head">
                <div class="col-head col-cb" title="Tick to include in view scope">View</div>
                <div class="col-head col-id">Entity & Name</div>
                <div class="col-head col-type">Type</div>
                <div class="col-head col-action">Active</div>
            </div>
        </div>

        <!-- Scrollable Entity Grid -->
        <div class="entity-dropdown-body" id="entityTreeList">
            @forelse($navCompaniesTree as $comp)
                @php
                    $isCompActive = $currentCompany && $currentCompany->company_id === $comp->company_id;
                    $compBranches = $comp->branches;
                    $isCompChecked = in_array((int)$comp->company_id, $checkedCompanyIds, true);
                @endphp
                <div class="entity-company-node {{ $isCompActive ? 'is-active-company' : '' }}" data-company-id="{{ $comp->company_id }}" data-search-text="{{ strtolower($comp->company_legal_name . ' ' . $comp->company_trade_name . ' ' . $comp->company_code) }}">
                    <!-- Company Row -->
                    <div class="entity-grid-row company-row {{ $isCompActive && !$currentBranch ? 'row-active-write' : '' }}" onclick="RMSContextSwitcher.selectActiveEntity({{ $comp->company_id }}, null)">
                        <!-- Col 1: Checkbox -->
                        <div class="col-grid col-cb" onclick="event.stopPropagation()">
                            <label class="custom-chk-label">
                                <input type="checkbox" class="scope-chk comp-scope-chk" data-type="company" data-id="{{ $comp->company_id }}" {{ $isCompChecked ? 'checked' : '' }} onchange="RMSContextSwitcher.onScopeChange()">
                                <span class="chk-box"></span>
                            </label>
                        </div>

                        <!-- Col 2: Identity -->
                        <div class="col-grid col-id">
                            @if($compBranches->isNotEmpty())
                                <button type="button" class="caret-box" onclick="event.stopPropagation(); RMSContextSwitcher.toggleAccordion(this)" title="Toggle branches">
                                    <i class="ph ph-caret-down"></i>
                                </button>
                            @else
                                <div class="caret-spacer"></div>
                            @endif

                            <div class="entity-avatar-box">
                                @if($comp->company_logo)
                                    <img src="{{ asset('storage/' . $comp->company_logo) }}" alt="Logo" class="avatar-img">
                                @else
                                    <i class="ph ph-buildings"></i>
                                @endif
                            </div>

                            <div class="entity-text-box">
                                <span class="entity-main-title" title="{{ $comp->company_trade_name ?: $comp->company_legal_name }}">
                                    {{ $comp->company_trade_name ?: $comp->company_legal_name }}
                                </span>
                                <span class="entity-code-chip">{{ $comp->company_code }}</span>
                            </div>
                        </div>

                        <!-- Col 3: Type Badge -->
                        <div class="col-grid col-type">
                            <span class="entity-type-badge comp-type">Company</span>
                        </div>

                        <!-- Col 4: Action / Active Indicator -->
                        <div class="col-grid col-action">
                            @if($isCompActive && (!$currentBranch || $isConsolidatedView))
                                <span class="badge-active-pill" title="Currently Active Entity">
                                    <i class="ph ph-check"></i> Active
                                </span>
                            @else
                                <span class="radio-target" title="Click row to set Active Write Entity"></span>
                            @endif
                        </div>
                    </div>

                    <!-- Nested Branches -->
                    @if($compBranches->isNotEmpty())
                        <div class="company-branches-tree">
                            @foreach($compBranches as $br)
                                @php
                                    $isBrActive = $isCompActive && !$isConsolidatedView && $currentBranch && $currentBranch->branch_id === $br->branch_id;
                                    $isBrChecked = in_array((int)$br->branch_id, $checkedBranchIds, true);
                                    $typeLabel = $br->branch_type === 'Central Commissary' || str_contains(strtolower($br->branch_name), 'commissary') ? 'Commissary' : ($br->branch_type === 'Warehouse' ? 'Warehouse' : 'Branch');
                                    $typeClass = $typeLabel === 'Commissary' ? 'commissary-type' : ($typeLabel === 'Warehouse' ? 'warehouse-type' : 'branch-type');
                                @endphp
                                <div class="entity-grid-row branch-row {{ $isBrActive ? 'row-active-write' : '' }}" data-branch-id="{{ $br->branch_id }}" data-search-text="{{ strtolower($br->branch_name . ' ' . $br->branch_code . ' ' . $br->branch_type) }}" onclick="RMSContextSwitcher.selectActiveEntity({{ $comp->company_id }}, {{ $br->branch_id }})">
                                    <!-- Col 1: Checkbox -->
                                    <div class="col-grid col-cb" onclick="event.stopPropagation()">
                                        <label class="custom-chk-label">
                                            <input type="checkbox" class="scope-chk branch-scope-chk" data-type="branch" data-id="{{ $br->branch_id }}" {{ $isBrChecked ? 'checked' : '' }} onchange="RMSContextSwitcher.onScopeChange()">
                                            <span class="chk-box"></span>
                                        </label>
                                    </div>

                                    <!-- Col 2: Identity with elegant curved tree connector -->
                                    <div class="col-grid col-id nested-branch-col">
                                        <div class="tree-branch-line"></div>
                                        <div class="entity-avatar-box branch-avatar">
                                            <i class="ph ph-storefront"></i>
                                        </div>
                                        <div class="entity-text-box">
                                            <span class="entity-main-title" title="{{ $br->branch_name }}">{{ $br->branch_name }}</span>
                                            <span class="entity-code-chip">{{ $br->branch_code }}</span>
                                        </div>
                                    </div>

                                    <!-- Col 3: Type Badge -->
                                    <div class="col-grid col-type">
                                        <span class="entity-type-badge {{ $typeClass }}" title="{{ $br->branch_type }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </div>

                                    <!-- Col 4: Action / Active Indicator -->
                                    <div class="col-grid col-action">
                                        @if($isBrActive)
                                            <span class="badge-active-pill" title="Currently Active Branch">
                                                <i class="ph ph-check"></i> Active
                                            </span>
                                        @else
                                            <span class="radio-target" title="Click row to set Active Write Entity"></span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="entity-empty-state">
                    <i class="ph ph-warning-circle"></i>
                    <p>No active entities accessible.</p>
                </div>
            @endforelse
        </div>

        <!-- Dropdown Footer -->
        <div class="entity-dropdown-footer">
            <a href="{{ route('config.companies-branches.index') }}" class="btn-manage-entities">
                <i class="ph ph-buildings"></i>
                <span>Companies & Branches Hub</span>
            </a>
            <div class="scope-status-pill" id="scopeStatusIndicator">
                <span id="scopeSelectedCount">{{ $totalCheckedCount }} In Scope</span>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Context Switching -->
    <form id="contextSwitchForm" method="POST" action="{{ route('context.switch') }}" style="display: none;">
        @csrf
        <input type="hidden" name="company_id" id="switchCompanyId" value="">
        <input type="hidden" name="branch_id" id="switchBranchId" value="">
        <input type="hidden" name="is_consolidated" id="switchIsConsolidated" value="0">
    </form>
</div>

<script>
window.RMSContextSwitcher = {
    init() {
        const switcher = document.getElementById('entityContextSwitcher');
        const trigger = document.getElementById('entitySwitcherBtn');
        const dropdown = document.getElementById('entitySwitcherDropdown');
        const searchInput = document.getElementById('entitySearchInput');
        const clearBtn = document.getElementById('entitySearchClear');

        if (!trigger || !dropdown) return;

        // Toggle open/close
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            switcher.classList.toggle('is-open');
            if (switcher.classList.contains('is-open')) {
                if (searchInput) searchInput.focus();
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                switcher.classList.remove('is-open');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                switcher.classList.remove('is-open');
            }
        });

        // Filter search
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';
                this.filterEntities(query);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    clearBtn.style.display = 'none';
                    this.filterEntities('');
                    searchInput.focus();
                });
            }
        }
    },

    filterEntities(query) {
        const companyNodes = document.querySelectorAll('.entity-company-node');
        companyNodes.forEach(node => {
            const compText = node.getAttribute('data-search-text') || '';
            const branchRows = node.querySelectorAll('.branch-row');
            let hasMatchingBranch = false;

            branchRows.forEach(br => {
                const brText = br.getAttribute('data-search-text') || '';
                if (!query || brText.includes(query) || compText.includes(query)) {
                    br.style.display = 'flex';
                    hasMatchingBranch = true;
                } else {
                    br.style.display = 'none';
                }
            });

            if (!query || compText.includes(query) || hasMatchingBranch) {
                node.style.display = 'block';
                if (query && hasMatchingBranch) {
                    node.classList.remove('collapsed');
                }
            } else {
                node.style.display = 'none';
            }
        });
    },

    toggleAccordion(btn) {
        const node = btn.closest('.entity-company-node');
        if (node) {
            node.classList.toggle('collapsed');
        }
    },

    // Set Single Active Write Entity
    selectActiveEntity(companyId, branchId) {
        document.getElementById('switchCompanyId').value = companyId;
        document.getElementById('switchBranchId').value = branchId || '';
        document.getElementById('switchIsConsolidated').value = '0';
        document.getElementById('contextSwitchForm').submit();
    },

    // Toggle Consolidated Read-Only View
    toggleConsolidated() {
        const currentActiveCompId = "{{ $currentCompany?->company_id ?? '' }}";
        if (!currentActiveCompId) return;

        const isCurrentlyConsolidated = "{{ $isConsolidatedView ? '1' : '0' }}" === '1';
        document.getElementById('switchCompanyId').value = currentActiveCompId;
        document.getElementById('switchBranchId').value = '';
        document.getElementById('switchIsConsolidated').value = isCurrentlyConsolidated ? '0' : '1';
        document.getElementById('contextSwitchForm').submit();
    },

    // Handle Checkbox View Scope Changes (Asynchronous Multi-Entity Update)
    async onScopeChange() {
        const compCheckboxes = document.querySelectorAll('.comp-scope-chk:checked');
        const branchCheckboxes = document.querySelectorAll('.branch-scope-chk:checked');

        const companyIds = Array.from(compCheckboxes).map(cb => parseInt(cb.getAttribute('data-id'), 10));
        const branchIds = Array.from(branchCheckboxes).map(cb => parseInt(cb.getAttribute('data-id'), 10));

        const totalCount = companyIds.length + branchIds.length;
        const countIndicator = document.getElementById('scopeSelectedCount');
        if (countIndicator) {
            countIndicator.textContent = `${totalCount} In Scope`;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('context.scope') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    company_ids: companyIds,
                    branch_ids: branchIds,
                })
            });

            if (res.ok) {
                const indicator = document.getElementById('scopeStatusIndicator');
                if (indicator) {
                    indicator.style.background = '#a7f3d0';
                    setTimeout(() => {
                        indicator.style.background = '#d1fae5';
                    }, 600);
                }
            }
        } catch (err) {
            console.error('Failed to update entity scope:', err);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    RMSContextSwitcher.init();
});
</script>
