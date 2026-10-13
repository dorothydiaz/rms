@php
    $checkedCompanyIds = $checkedCompanyIds ?? [];
    $checkedBranchIds = $checkedBranchIds ?? [];
    $totalCheckedCount = count($checkedCompanyIds) + count($checkedBranchIds);
@endphp

<div class="entity-context-switcher" id="entityContextSwitcher">
    <!-- Trigger Button in Header -->
    <button type="button" class="entity-switcher-btn {{ $isConsolidatedView ? 'is-consolidated' : '' }}" id="entitySwitcherBtn" aria-haspopup="true" aria-expanded="false" title="Click to open Entity Selector & View Scope">
        <div class="entity-btn-icon">
            @if($currentCompany && $currentCompany->company_logo)
                <img src="{{ asset('storage/' . $currentCompany->company_logo) }}" width="28" height="28" alt="Company Logo" class="entity-logo-mini">
            @else
                <i class="ph {{ $isConsolidatedView ? 'ph-stack' : 'ph-buildings' }}"></i>
            @endif
        </div>
        <div class="entity-btn-content">
            <div class="entity-btn-company">
                <span class="company-name" title="{{ $currentCompany?->company_trade_name ?: $currentCompany?->company_legal_name }}">
                    {{ $currentCompany ? ($currentCompany->company_trade_name ?: $currentCompany->company_legal_name) : 'Select Company' }}
                </span>
                @if($isConsolidatedView)
                    <span class="badge-mode consolidated">Consolidated</span>
                @elseif($totalCheckedCount > 1)
                    <span class="badge-mode scope" title="Multi-entity aggregated view active">{{ $totalCheckedCount }} in Scope</span>
                @endif
            </div>
            @if(!$isConsolidatedView && $currentBranch)
                <div class="entity-btn-branch">
                    <i class="ph ph-caret-right entity-sep"></i>
                    <span class="branch-name" title="{{ $currentBranch->branch_name }}">{{ $currentBranch->branch_name }}</span>
                    @if($currentBranch->branch_type === 'Central Commissary' || str_contains(strtolower($currentBranch->branch_name), 'commissary'))
                        <span class="badge-branch-type commissary">HQ / Commissary</span>
                    @elseif($currentBranch->branch_type === 'Warehouse')
                        <span class="badge-branch-type warehouse">Warehouse</span>
                    @else
                        <span class="badge-branch-type store">Store</span>
                    @endif
                </div>
            @endif
        </div>
        <i class="ph ph-caret-down entity-chevron"></i>
    </button>

    <!-- Context Switcher Dropdown Panel -->
    <div class="entity-switcher-dropdown" id="entitySwitcherDropdown">
        <!-- Pinned Header -->
        <div class="entity-dropdown-header">
            <div class="dropdown-title-row">
                <div class="dropdown-title">
                    <i class="ph ph-tree-structure" style="color: #4f46e5;"></i>
                    <span>Entity Hierarchy & Scope</span>
                </div>
                <button type="button" class="btn-consolidated-toggle {{ $isConsolidatedView ? 'active' : '' }}" id="btnToggleConsolidated" title="Toggle cross-branch consolidated view mode">
                    <i class="ph {{ $isConsolidatedView ? 'ph-check-circle' : 'ph-stack' }}"></i>
                    <span>{{ $isConsolidatedView ? 'Consolidated Active' : 'Consolidate View' }}</span>
                </button>
            </div>
            
            <div class="entity-scope-instruction">
                <span><i class="ph ph-check-square"></i> Checkbox = <strong>View Scope</strong></span>
                <span><i class="ph ph-cursor-click"></i> Click Row = <strong>Active Entity</strong></span>
            </div>

            <div class="entity-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="entitySearchInput" placeholder="Filter company, branch, or code..." autocomplete="off" aria-label="Filter company, branch, or code">
                <button type="button" class="search-clear-btn is-hidden" id="entitySearchClear" aria-label="Clear filter search">&times;</button>
            </div>

            <!-- Table Header: Strict Align-Left (No Active Status Column) -->
            <div class="entity-grid-head">
                <div class="col-head col-cb" title="Tick to include in view scope">View</div>
                <div class="col-head col-id">Entity & Branch</div>
                <div class="col-head col-type">Type</div>
            </div>
        </div>

        <!-- Scrollable Entity Grid -->
        <div class="entity-dropdown-body" id="entityTreeList">
            @forelse($navCompaniesTree as $comp)
                @php
                    $isCompActive = $currentCompany && $currentCompany->company_id === $comp->company_id;
                    $compBranches = $comp->branches;
                    $isCompChecked = in_array((int)$comp->company_id, $checkedCompanyIds, true);
                    $isCompanyWriteActive = $isCompActive && (!$currentBranch || $isConsolidatedView);
                @endphp
                <div class="entity-company-node {{ $isCompActive ? 'is-active-company' : '' }}" data-company-id="{{ $comp->company_id }}" data-search-text="{{ strtolower($comp->company_legal_name . ' ' . $comp->company_trade_name . ' ' . $comp->company_code) }}">
                    <!-- Company Row -->
                    <div class="entity-grid-row company-row {{ $isCompanyWriteActive ? 'row-active-write' : '' }}" data-company-id="{{ $comp->company_id }}" role="button" tabindex="0">
                        <!-- Col 1: Checkbox (Left-aligned) -->
                        <div class="col-grid col-cb">
                            <label class="custom-chk-label" aria-label="Toggle view scope for {{ $comp->company_trade_name ?: $comp->company_legal_name }}">
                                <input type="checkbox" class="scope-chk comp-scope-chk" data-type="company" data-id="{{ $comp->company_id }}" {{ $isCompChecked ? 'checked' : '' }}>
                                <span class="chk-box"></span>
                            </label>
                        </div>

                        <!-- Col 2: Identity & Name (Left-aligned, flexible width) -->
                        <div class="col-grid col-id">
                            @if($compBranches->isNotEmpty())
                                <button type="button" class="caret-box" title="Toggle branches" aria-label="Toggle branches">
                                    <i class="ph ph-caret-down"></i>
                                </button>
                            @else
                                <div class="caret-spacer"></div>
                            @endif

                            <div class="entity-avatar-box">
                                @if($comp->company_logo)
                                    <img src="{{ asset('storage/' . $comp->company_logo) }}" width="24" height="24" alt="Company Logo" class="avatar-img">
                                @else
                                    <i class="ph ph-buildings"></i>
                                @endif
                            </div>

                            <div class="entity-text-box">
                                <span class="entity-main-title" title="{{ $comp->company_trade_name ?: $comp->company_legal_name }}">
                                    {{ $comp->company_trade_name ?: $comp->company_legal_name }}
                                </span>
                                <span class="entity-code-chip">{{ $comp->company_code }}</span>
                                @if($isCompanyWriteActive)
                                    <span class="active-entity-pill" title="Currently Active Entity">
                                        <i class="ph-bold ph-check"></i> Active
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Col 3: Type Badge (Left-aligned) -->
                        <div class="col-grid col-type">
                            <span class="entity-type-badge comp-type">Company</span>
                        </div>
                    </div>

                    <!-- Nested Branches (Left-aligned hierarchy) -->
                    @if($compBranches->isNotEmpty())
                        <div class="company-branches-tree">
                            @foreach($compBranches as $br)
                                @php
                                    $isBrActive = $isCompActive && !$isConsolidatedView && $currentBranch && $currentBranch->branch_id === $br->branch_id;
                                    $isBrChecked = in_array((int)$br->branch_id, $checkedBranchIds, true);
                                @endphp
                                <div class="entity-grid-row branch-row {{ $isBrActive ? 'row-active-write' : '' }}" data-company-id="{{ $comp->company_id }}" data-branch-id="{{ $br->branch_id }}" data-search-text="{{ strtolower($br->branch_name . ' ' . $br->branch_code . ' ' . $br->branch_type) }}" role="button" tabindex="0">
                                    <!-- Col 1: Checkbox (Left-aligned) -->
                                    <div class="col-grid col-cb">
                                        <label class="custom-chk-label" aria-label="Toggle view scope for {{ $br->branch_name }}">
                                            <input type="checkbox" class="scope-chk branch-scope-chk" data-type="branch" data-id="{{ $br->branch_id }}" {{ $isBrChecked ? 'checked' : '' }}>
                                            <span class="chk-box"></span>
                                        </label>
                                    </div>

                                    <!-- Col 2: Identity & Name (Left-aligned, nested indent) -->
                                    <div class="col-grid col-id nested-branch-col">
                                        <div class="tree-branch-line"></div>
                                        <div class="entity-avatar-box branch-avatar">
                                            <i class="ph ph-storefront"></i>
                                        </div>
                                        <div class="entity-text-box">
                                            <span class="entity-main-title" title="{{ $br->branch_name }}">{{ $br->branch_name }}</span>
                                            <span class="entity-code-chip">{{ $br->branch_code }}</span>
                                            @if($isBrActive)
                                                <span class="active-entity-pill" title="Currently Active Branch">
                                                    <i class="ph-bold ph-check"></i> Active
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Col 3: Type Badge (Left-aligned) -->
                                    <div class="col-grid col-type">
                                        <span class="entity-type-badge branch-type" title="{{ $br->branch_type }}">
                                            {{ $br->branch_type === 'Central Commissary' ? 'Commissary' : ($br->branch_type === 'Warehouse' ? 'Warehouse' : 'Store') }}
                                        </span>
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
        <div class="entity-dropdown-footer" style="justify-content: flex-end;">
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

<style>
/* -------------------------------------------------------------------------
   HR CORPORATE THEME - ENTITY CONTEXT SWITCHER (REDESIGNED)
   Left-Aligned Grid, High Scannability, Removed Redundant Active Column
   ------------------------------------------------------------------------- */
.entity-context-switcher {
    position: relative;
    display: inline-block;
    z-index: 100;
}

/* Header Trigger Button */
.entity-switcher-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    color: #0f172a;
    font-family: inherit;
}

.entity-switcher-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px 0 rgba(15, 23, 42, 0.08);
}

.entity-switcher-btn.is-consolidated {
    border-color: #818cf8;
    background: #f5f3ff;
}

.entity-btn-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #4f46e5;
    color: #ffffff;
    border-radius: 6px;
    font-size: 15px;
    flex-shrink: 0;
}

.entity-logo-mini {
    width: 28px;
    height: 28px;
    object-fit: contain;
    border-radius: 6px;
}

.entity-btn-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    max-width: 240px;
}

.entity-btn-company {
    display: flex;
    align-items: center;
    gap: 6px;
}

.company-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.badge-mode.consolidated {
    font-size: 9.5px;
    font-weight: 700;
    padding: 1.5px 6px;
    border-radius: 4px;
    background: #ede9fe;
    color: #6366f1;
    border: 1px solid #c7d2fe;
}

.badge-mode.scope {
    font-size: 9.5px;
    font-weight: 700;
    padding: 1.5px 6px;
    border-radius: 4px;
    background: #ccfbf1;
    color: #0d9488;
    border: 1px solid #99f6e4;
}

.entity-btn-branch {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #475569;
}

.entity-sep {
    font-size: 10px;
    color: #94a3b8;
}

.branch-name {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
}

.badge-branch-type {
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 3px;
    font-weight: 600;
}

.badge-branch-type.commissary { background: #e0f2fe; color: #0284c7; }
.badge-branch-type.warehouse { background: #fef3c7; color: #d97706; }
.badge-branch-type.store { background: #f1f5f9; color: #475569; }

.entity-chevron {
    font-size: 12px;
    color: #94a3b8;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
}

.entity-context-switcher.is-open .entity-chevron {
    transform: rotate(180deg);
}

/* -------------------------------------------------------------------------
   DROPDOWN PANEL: FIXED 420px WIDTH & RIGID LEFT-ALIGNED COLUMNS
   ------------------------------------------------------------------------- */
.entity-switcher-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 420px;
    min-width: 420px;
    max-width: min(420px, calc(100vw - 32px));
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.06);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 999;
}

.entity-context-switcher.is-open .entity-switcher-dropdown {
    display: flex;
}

/* Dropdown Header */
.entity-dropdown-header {
    padding: 12px 14px 8px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.dropdown-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.dropdown-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.btn-consolidated-toggle {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-consolidated-toggle:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.btn-consolidated-toggle.active {
    background: #ede9fe;
    color: #4f46e5;
    border-color: #c7d2fe;
}

.entity-scope-instruction {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f1f5f9;
    padding: 5px 9px;
    border-radius: 6px;
    font-size: 10.5px;
    color: #475569;
    margin-bottom: 8px;
    border: 1px solid #e2e8f0;
}

.entity-scope-instruction i {
    color: #4f46e5;
}

.entity-search-box {
    position: relative;
    margin-bottom: 8px;
}

.entity-search-box i.ph-magnifying-glass {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.entity-search-box input {
    width: 100%;
    padding: 7px 28px 7px 32px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 12px;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}

.entity-search-box input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.12);
}

.search-clear-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    font-size: 16px;
    color: #94a3b8;
    cursor: pointer;
}

.search-clear-btn.is-hidden {
    display: none !important;
}

/* -------------------------------------------------------------------------
   REDESIGNED 3-COLUMN RIGID GRID (ALL STRICTLY ALIGN LEFT)
   Checkbox: 40px | Identity & Branch: Flex | Type: 82px
   No "Active" column - active state rendered on row + inline badge
   ------------------------------------------------------------------------- */
.entity-grid-head {
    display: flex;
    align-items: center;
    padding: 5px 8px;
    font-size: 10.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-top: 1px solid #e2e8f0;
    margin-top: 4px;
}

.col-head.col-cb {
    width: 40px;
    min-width: 40px;
    text-align: left;
    padding-left: 6px;
}

.col-head.col-id {
    flex: 1;
    text-align: left;
    padding-left: 6px;
}

.col-head.col-type {
    width: 82px;
    min-width: 82px;
    text-align: left;
    padding-left: 6px;
}

/* Scrollable Body */
.entity-dropdown-body {
    max-height: 360px;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 4px 6px;
}

.entity-dropdown-body::-webkit-scrollbar {
    width: 5px;
}
.entity-dropdown-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

/* Row Grid Styling */
.entity-grid-row {
    display: flex;
    align-items: center;
    padding: 7px 8px;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
    margin-bottom: 2px;
    border-left: 3px solid transparent;
}

.entity-grid-row:hover {
    background: #f1f5f9;
}

.entity-grid-row.row-active-write {
    background: #eef2ff;
    border-left-color: #4f46e5;
}

/* Col 1: Checkbox (Left-aligned) */
.col-grid.col-cb {
    width: 40px;
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 6px;
}

/* Custom Checkbox */
.custom-chk-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.custom-chk-label input[type="checkbox"] {
    display: none;
}

.custom-chk-label .chk-box {
    width: 16px;
    height: 16px;
    border: 1.5px solid #cbd5e1;
    border-radius: 4px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.custom-chk-label input[type="checkbox"]:checked + .chk-box {
    background: #0d9488;
    border-color: #0d9488;
}

.custom-chk-label input[type="checkbox"]:checked + .chk-box::after {
    content: '';
    width: 4px;
    height: 8px;
    border: solid #ffffff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-bottom: 2px;
}

/* Col 2: Identity & Name (Left-aligned, flexible width) */
.col-grid.col-id {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 7px;
    padding-left: 6px;
    text-align: left;
}

.caret-box {
    width: 20px;
    height: 20px;
    min-width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    border-radius: 4px;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background 0.15s ease;
}

.caret-box:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.entity-company-node.collapsed .caret-box i {
    transform: rotate(-90deg);
}

.caret-spacer {
    width: 20px;
    min-width: 20px;
}

.entity-avatar-box {
    width: 24px;
    height: 24px;
    min-width: 24px;
    border-radius: 5px;
    background: #e0e7ff;
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.entity-avatar-box.branch-avatar {
    background: #ccfbf1;
    color: #0f766e;
    font-size: 12px;
}

.avatar-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    border-radius: 5px;
}

.entity-text-box {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    min-width: 0;
    text-align: left;
}

.entity-main-title {
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.entity-code-chip {
    font-size: 9.5px;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 1px 4px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* Inline Active Badge inside Name Column */
.active-entity-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 9px;
    font-weight: 700;
    padding: 1.5px 6px;
    border-radius: 9999px;
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
    flex-shrink: 0;
    letter-spacing: 0.02em;
}

.active-entity-pill i {
    font-size: 9px;
}

/* Nested Branch Alignment (Strict Left-Aligned Step) */
.nested-branch-col {
    padding-left: 22px !important;
    position: relative;
}

.tree-branch-line {
    position: absolute;
    left: 8px;
    top: 50%;
    width: 10px;
    height: 1px;
    background: #cbd5e1;
}

.entity-company-node.collapsed .company-branches-tree {
    display: none;
}

/* Col 3: Type Badge (Strict Left-Aligned) */
.col-grid.col-type {
    width: 82px;
    min-width: 82px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 6px;
    text-align: left;
}

.entity-type-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    display: inline-block;
    text-align: left;
    white-space: nowrap;
}

.entity-type-badge.comp-type {
    background: #e0e7ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
}

.entity-type-badge.branch-type {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Dropdown Footer */
.entity-dropdown-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 11.5px;
}

.link-manage-entities {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #4f46e5;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.15s ease;
}

.link-manage-entities:hover {
    color: #3730a3;
}

.scope-status-pill {
    font-size: 10.5px;
    font-weight: 600;
    color: #0d9488;
    background: #ccfbf1;
    padding: 2px 8px;
    border-radius: 9999px;
    border: 1px solid #99f6e4;
}

.entity-empty-state {
    padding: 24px;
    text-align: center;
    color: #64748b;
    font-size: 12px;
}
</style>

<script>
window.RMSContextSwitcher = {
    init() {
        const switcher = document.getElementById('entityContextSwitcher');
        const trigger = document.getElementById('entitySwitcherBtn');
        const dropdown = document.getElementById('entitySwitcherDropdown');
        const treeList = document.getElementById('entityTreeList');
        const searchInput = document.getElementById('entitySearchInput');
        const clearBtn = document.getElementById('entitySearchClear');
        const consolidatedBtn = document.getElementById('btnToggleConsolidated');

        if (!trigger || !dropdown) return;

        // Toggle open/close
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = switcher.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen && searchInput) {
                searchInput.focus();
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                switcher.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && switcher.classList.contains('is-open')) {
                switcher.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });

        // Consolidated Mode Toggle Button
        if (consolidatedBtn) {
            consolidatedBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleConsolidated();
            });
        }

        // Event Delegation for Tree List (Handles row selection, accordion toggle, checkbox view scope)
        if (treeList) {
            treeList.addEventListener('click', (e) => {
                // 1. Checkbox toggle
                const chk = e.target.closest('.scope-chk');
                if (chk) {
                    this.onScopeChange();
                    return;
                }

                // 2. Accordion Caret toggle
                const caret = e.target.closest('.caret-box');
                if (caret) {
                    e.stopPropagation();
                    this.toggleAccordion(caret);
                    return;
                }

                // 3. Row selection (Active Entity Switch)
                const row = e.target.closest('.entity-grid-row');
                if (row) {
                    const compId = row.getAttribute('data-company-id');
                    const brId = row.getAttribute('data-branch-id') || null;
                    if (compId) {
                        this.selectActiveEntity(compId, brId);
                    }
                }
            });

            // Keyboard navigation (Enter / Space on row)
            treeList.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    const row = e.target.closest('.entity-grid-row');
                    if (row && !e.target.closest('.scope-chk') && !e.target.closest('.caret-box')) {
                        e.preventDefault();
                        const compId = row.getAttribute('data-company-id');
                        const brId = row.getAttribute('data-branch-id') || null;
                        if (compId) {
                            this.selectActiveEntity(compId, brId);
                        }
                    }
                }
            });
        }

        // Filter search
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                if (clearBtn) {
                    if (query) {
                        clearBtn.classList.remove('is-hidden');
                        clearBtn.removeAttribute('aria-hidden');
                        clearBtn.removeAttribute('tabindex');
                    } else {
                        clearBtn.classList.add('is-hidden');
                        clearBtn.setAttribute('aria-hidden', 'true');
                        clearBtn.setAttribute('tabindex', '-1');
                    }
                }
                this.filterEntities(query);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    clearBtn.classList.add('is-hidden');
                    clearBtn.setAttribute('aria-hidden', 'true');
                    clearBtn.setAttribute('tabindex', '-1');
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
                        indicator.style.background = '#ccfbf1';
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
