@php
    $checkedCompanyIds = $checkedCompanyIds ?? [];
    $checkedBranchIds = $checkedBranchIds ?? [];
    $totalCheckedCount = count($checkedCompanyIds) + count($checkedBranchIds);
@endphp

<div class="entity-context-switcher" id="entityContextSwitcher">
    <!-- Trigger Button in Header (Glassmorphic Pill) -->
    <button type="button" class="entity-switcher-btn {{ $isConsolidatedView ? 'is-consolidated' : '' }}" id="entitySwitcherBtn" aria-haspopup="true" aria-expanded="false" title="Click to open Entity Selector & View Scope">
        <div class="entity-btn-icon">
            @if($currentCompany && $currentCompany->company_logo)
                <img src="{{ asset('storage/' . $currentCompany->company_logo) }}" alt="Logo" class="entity-logo-mini">
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
            <a href="{{ route('config.business-settings') }}" class="btn-manage-entities">
                <i class="ph ph-gear"></i>
                <span>Business Settings & Hub</span>
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

<style>
/* -------------------------------------------------------------------------
   RMS ENTERPRISE THEME - ENTITY CONTEXT SWITCHER
   Spacious, Minimalist, Glassmorphic Design Token Integration
   ------------------------------------------------------------------------- */
.entity-context-switcher {
    position: relative;
    display: inline-block;
    z-index: 100;
}

/* Header Trigger Button (Matches Ambient Pill Styling) */
.entity-switcher-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 14px 5px 8px;
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    color: #0f172a;
    font-family: inherit;
}

.entity-switcher-btn:hover {
    background: rgba(255, 255, 255, 0.98);
    border-color: rgba(168, 85, 247, 0.35);
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.08);
}

.entity-switcher-btn.is-consolidated {
    border-color: rgba(129, 140, 248, 0.5);
    background: rgba(245, 243, 255, 0.85);
}

.entity-btn-icon {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.14) 0%, rgba(168, 85, 247, 0.20) 100%);
    color: #9333ea;
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 9999px;
    font-size: 14px;
    flex-shrink: 0;
}

.entity-logo-mini {
    width: 28px;
    height: 28px;
    object-fit: contain;
    border-radius: 9999px;
}

.entity-btn-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    max-width: 260px;
}

.entity-btn-company {
    display: flex;
    align-items: center;
    gap: 6px;
    line-height: 1.2;
}

.company-name {
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    max-width: 170px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.badge-mode.consolidated {
    font-size: 9.5px;
    font-weight: 600;
    padding: 1.5px 6px;
    border-radius: 9999px;
    background: #ede9fe;
    color: #6366f1;
}

.badge-mode.scope {
    font-size: 9.5px;
    font-weight: 600;
    padding: 1.5px 6px;
    border-radius: 9999px;
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.entity-btn-branch {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #64748b;
    line-height: 1.2;
    margin-top: 1px;
}

.entity-sep {
    font-size: 10px;
    color: #94a3b8;
}

.branch-name {
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
}

.badge-branch-type {
    font-size: 9.5px;
    padding: 1px 5px;
    border-radius: 4px;
    font-weight: 600;
}

.badge-branch-type.commissary { background: #e0f2fe; color: #0284c7; }
.badge-branch-type.warehouse { background: #fef3c7; color: #b45309; }
.badge-branch-type.store { background: #f1f5f9; color: #475569; }

.entity-chevron {
    font-size: 12px;
    color: #94a3b8;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    margin-left: 2px;
}

.entity-context-switcher.is-open .entity-chevron {
    transform: rotate(180deg);
}

/* -------------------------------------------------------------------------
   DROPDOWN PANEL: SPATIOUS 530px WIDTH & PREMIUM GLASSMORPHISM
   ------------------------------------------------------------------------- */
.entity-switcher-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 530px;
    min-width: 480px;
    max-width: min(560px, calc(100vw - 32px));
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 18px;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.16), 0 0 0 1px rgba(255, 255, 255, 0.9) inset;
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 999;
    animation: switcherSlideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes switcherSlideDown {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.entity-context-switcher.is-open .entity-switcher-dropdown {
    display: flex;
}

/* Dropdown Header */
.entity-dropdown-header {
    padding: 14px 18px 10px 18px;
    background: #faf7fd;
    border-bottom: 1px solid rgba(226, 232, 240, 0.85);
}

.dropdown-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.dropdown-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dropdown-title-badge {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18));
    color: #9333ea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    border: 1px solid rgba(168, 85, 247, 0.2);
}

.dropdown-title-text {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.dropdown-title-sub {
    font-size: 10.5px;
    color: #64748b;
    margin-top: 1px;
}

.btn-consolidated-toggle {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.btn-consolidated-toggle:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

.btn-consolidated-toggle.active {
    background: #ede9fe;
    color: #6366f1;
    border-color: rgba(99, 102, 241, 0.35);
    box-shadow: 0 2px 6px rgba(99, 102, 241, 0.12);
}

/* Minimalist Scope Instruction Strip */
.entity-scope-instruction {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(233, 213, 255, 0.7);
    padding: 6px 12px;
    border-radius: 10px;
    margin-bottom: 10px;
}

.instruction-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #475569;
}

.instruction-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    padding: 1.5px 6px;
    border-radius: 9999px;
}

.instruction-pill.pill-scope {
    background: rgba(13, 148, 136, 0.12);
    color: #0d9488;
}

.instruction-pill.pill-write {
    background: rgba(147, 51, 234, 0.12);
    color: #9333ea;
}

.instruction-dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: #cbd5e1;
}

.instruction-text {
    font-size: 11px;
}

.instruction-text strong {
    color: #1e293b;
    font-weight: 600;
}

/* Modern Search Box */
.entity-search-box {
    position: relative;
    margin-bottom: 8px;
}

.entity-search-box i.ph-magnifying-glass {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 15px;
    pointer-events: none;
}

.entity-search-box input {
    width: 100%;
    padding: 8px 30px 8px 36px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 12px;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.entity-search-box input::placeholder {
    color: #94a3b8;
}

.entity-search-box input:focus {
    border-color: #a855f7;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.12);
}

.search-clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    font-size: 16px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.search-clear-btn:hover {
    color: #475569;
}

/* -------------------------------------------------------------------------
   COLUMN GRID: SPATIOUS & PERFECTLY BALANCED
   Checkbox: 36px | Identity: Flex-1 (min 220px) | Type: 88px | Action: 72px
   ------------------------------------------------------------------------- */
.entity-grid-head {
    display: flex;
    align-items: center;
    padding: 8px 12px 6px 12px;
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-top: 1px solid rgba(226, 232, 240, 0.8);
    margin-top: 4px;
}

.col-head.col-cb { width: 36px; min-width: 36px; text-align: center; }
.col-head.col-id { flex: 1; padding-left: 8px; }
.col-head.col-type { width: 88px; min-width: 88px; text-align: center; }
.col-head.col-action { width: 72px; min-width: 72px; text-align: center; }

/* Scrollable Body */
.entity-dropdown-body {
    max-height: 380px;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 6px 10px;
    background: #ffffff;
}

.entity-dropdown-body::-webkit-scrollbar {
    width: 5px;
}

.entity-dropdown-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

.entity-dropdown-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Row Grid Styling */
.entity-grid-row {
    display: flex;
    align-items: center;
    padding: 7px 8px;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 3px;
    border: 1px solid transparent;
}

.entity-grid-row:hover {
    background: rgba(245, 243, 255, 0.7);
    border-color: rgba(233, 213, 255, 0.6);
}

.entity-grid-row.row-active-write {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.04) 0%, rgba(168, 85, 247, 0.08) 100%);
    border-color: rgba(168, 85, 247, 0.28);
    box-shadow: 0 1px 3px rgba(168, 85, 247, 0.06);
}

.col-grid.col-cb {
    width: 36px;
    min-width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Custom Minimalist Checkbox */
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
    width: 17px;
    height: 17px;
    border: 1.5px solid #cbd5e1;
    border-radius: 5px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.custom-chk-label:hover .chk-box {
    border-color: #0d9488;
}

.custom-chk-label input[type="checkbox"]:checked + .chk-box {
    background: #0d9488;
    border-color: #0d9488;
    box-shadow: 0 1px 3px rgba(13, 148, 136, 0.25);
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

/* Col 2: Identity */
.col-grid.col-id {
    flex: 1;
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-left: 4px;
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
    font-size: 12px;
    cursor: pointer;
    border-radius: 4px;
    transition: transform 0.2s ease, background 0.15s ease, color 0.15s ease;
}

.caret-box:hover {
    background: #ede9fe;
    color: #7c3aed;
}

.entity-company-node.collapsed .caret-box i {
    transform: rotate(-90deg);
}

.caret-spacer {
    width: 20px;
    min-width: 20px;
}

.entity-avatar-box {
    width: 26px;
    height: 26px;
    min-width: 26px;
    border-radius: 7px;
    background: linear-gradient(135deg, #f3e8ff, #ede9fe);
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border: 1px solid rgba(168, 85, 247, 0.18);
    flex-shrink: 0;
}

.entity-avatar-box.branch-avatar {
    background: #ecfdf5;
    color: #059669;
    font-size: 13px;
    border-color: rgba(5, 150, 105, 0.18);
}

.avatar-img {
    width: 26px;
    height: 26px;
    object-fit: contain;
    border-radius: 7px;
}

.entity-text-box {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    min-width: 0;
    flex: 1;
}

.entity-main-title {
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.branch-row .entity-main-title {
    font-weight: 500;
    color: #1e293b;
}

.entity-code-chip {
    font-size: 9.5px;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 1.5px 5px;
    border-radius: 4px;
    flex-shrink: 0;
    font-family: ui-monospace, SFMono-Regular, monospace;
    border: 1px solid rgba(226, 232, 240, 0.8);
}

/* Nested Branch Curved Connector */
.nested-branch-col {
    position: relative;
    padding-left: 28px !important;
}

.tree-branch-line {
    position: absolute;
    left: 10px;
    top: 50%;
    width: 12px;
    height: 12px;
    border-left: 1.5px solid #cbd5e1;
    border-bottom: 1.5px solid #cbd5e1;
    border-bottom-left-radius: 4px;
    transform: translateY(-9px);
    pointer-events: none;
}

.entity-company-node.collapsed .company-branches-tree {
    display: none;
}

/* Col 3: Type Badge */
.col-grid.col-type {
    width: 88px;
    min-width: 88px;
    text-align: center;
    display: flex;
    justify-content: center;
}

.entity-type-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-block;
    letter-spacing: 0.01em;
}

.entity-type-badge.comp-type {
    background: #f3e8ff;
    color: #7e22ce;
    border: 1px solid rgba(168, 85, 247, 0.15);
}

.entity-type-badge.branch-type {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.18);
}

.entity-type-badge.commissary-type {
    background: #e0f2fe;
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.18);
}

.entity-type-badge.warehouse-type {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.18);
}

/* Col 4: Action / Active Indicator */
.col-grid.col-action {
    width: 72px;
    min-width: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-active-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 2.5px 8px;
    border-radius: 9999px;
    background: #dcfce7;
    color: #15803d;
    display: flex;
    align-items: center;
    gap: 3px;
    border: 1px solid rgba(21, 128, 61, 0.2);
    box-shadow: 0 1px 2px rgba(21, 128, 61, 0.08);
}

.radio-target {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 1.5px solid #cbd5e1;
    display: inline-block;
    transition: all 0.15s ease;
}

.entity-grid-row:hover .radio-target {
    border-color: #a855f7;
    background: #f3e8ff;
}

/* Dropdown Footer */
.entity-dropdown-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #faf7fd;
    border-top: 1px solid rgba(226, 232, 240, 0.85);
    font-size: 11.5px;
}

.btn-manage-entities {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #9333ea;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}

.btn-manage-entities:hover {
    color: #7c3aed;
    transform: translateX(2px);
}

.scope-status-pill {
    font-size: 10.5px;
    font-weight: 600;
    color: #047857;
    background: #d1fae5;
    border: 1px solid rgba(16, 185, 129, 0.25);
    padding: 2.5px 9px;
    border-radius: 9999px;
    transition: background 0.2s ease;
}

.entity-empty-state {
    padding: 28px;
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
                // Subtle non-intrusive feedback
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
