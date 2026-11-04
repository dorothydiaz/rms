@extends('layouts.app')

@section('title', 'Movement Types & Supply Routes - Inventory Configuration')

@push('styles')
    @vite('resources/css/modules/movement-supply-flows.css')
@endpush

@push('blade-data')
<script>
    window.MovementFlowsConfig = Object.freeze({
        initialWarehouses: @json($initialWarehouses ?? []),
        initialStorageAreas: @json($initialStorageAreas ?? []),
        initialBoundaryAreas: @json($initialBoundaryAreas ?? []),
        initialMovementTypes: @json($initialMovementTypes ?? []),
        initialSupplyFlows: @json($initialSupplyFlows ?? []),
        initialFlowRules: @json($initialFlowRules ?? []),
        initialCategories: @json($initialCategories ?? []),
        initialProducts: @json($initialProducts ?? []),
        csrfToken: @json(csrf_token()),
        routes: {
            warehousesPage: @json(route('inventory.warehouses')),
            movementFlowsApi: @json(route('inventory.api.movement-flows')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/movement-supply-flows.js')
@endpush

@section('content')
<div class="msf-workspace" id="msfAppRoot">

    <!-- Toast Notifications Container -->
    <div class="msf-toast-container" id="msfToastContainer"></div>

    <!-- 1. Header Row -->
    <div class="msf-header-row">
        <div class="msf-header-left">
            <div class="msf-title-icon">
                <i class="ph ph-arrows-split"></i>
            </div>
            <div>
                <h1 class="msf-title">Movement Types & Supply Routes</h1>
                <p class="msf-subtitle">Standard warehouse operation templates with built-in multi-step routing paths and scanner validation rules</p>
            </div>
        </div>
        <div class="msf-header-actions">
            <a href="{{ route('inventory.warehouses') }}" class="msf-btn msf-btn-outline" style="text-decoration: none;">
                <i class="ph ph-warehouse"></i>
                <span>Warehouse & Storage Area</span>
            </a>
            <button type="button" class="msf-btn msf-btn-primary" onclick="openMovementTypeModal()">
                <i class="ph ph-plus-circle"></i>
                <span>New Movement Type</span>
            </button>
        </div>
    </div>

    <!-- 2. Clean 3-Card KPI Summary Bar -->
    <div class="msf-kpi-grid">
        <div class="msf-kpi-card">
            <div class="msf-kpi-head">
                <span class="msf-kpi-label">Active Movement Types</span>
                <span class="msf-kpi-icon purple"><i class="ph ph-barcode"></i></span>
            </div>
            <div class="msf-kpi-val" id="msfKpiTotalTypes">0</div>
            <div class="msf-kpi-sub">Standard operations available across warehouses</div>
        </div>

        <div class="msf-kpi-card">
            <div class="msf-kpi-head">
                <span class="msf-kpi-label">Configured Operations</span>
                <span class="msf-kpi-icon blue"><i class="ph ph-flow-arrow"></i></span>
            </div>
            <div class="msf-kpi-val" id="msfKpiOpsBreakdown">0 Inbound · 0 Outbound</div>
            <div class="msf-kpi-sub">Receipts, dispatches, transfers & write-offs</div>
        </div>

        <div class="msf-kpi-card">
            <div class="msf-kpi-head">
                <span class="msf-kpi-label">Facilities Covered</span>
                <span class="msf-kpi-icon emerald"><i class="ph ph-buildings"></i></span>
            </div>
            <div class="msf-kpi-val" id="msfKpiFacilities">0 Warehouses</div>
            <div class="msf-kpi-sub" id="msfKpiFacilitiesList">Central, Cold-Chain & Branch Hubs</div>
        </div>
    </div>

    <!-- 3. Filter & Search Controls Bar -->
    <div class="msf-controls-bar">
        <div class="msf-filter-group">
            <span class="msf-filter-label"><i class="ph ph-warehouse"></i> Warehouse:</span>
            <div class="msf-pills" id="msfWarehousePills">
                <button type="button" class="msf-pill-btn active" data-wh="ALL" onclick="filterByWarehouse('ALL')">All Warehouses</button>
                <!-- Populated dynamically -->
            </div>
        </div>

        <div class="msf-controls-right">
            <div class="msf-pills" id="msfKindPills">
                <button type="button" class="msf-pill-btn active" data-kind="ALL" onclick="filterByKind('ALL')">All Types</button>
                <button type="button" class="msf-pill-btn" data-kind="Inbound Receipt" onclick="filterByKind('Inbound Receipt')">Inbound Receipts</button>
                <button type="button" class="msf-pill-btn" data-kind="Outbound Dispatch" onclick="filterByKind('Outbound Dispatch')">Outbound Dispatches</button>
                <button type="button" class="msf-pill-btn" data-kind="Internal Transfer" onclick="filterByKind('Internal Transfer')">Internal Transfers</button>
                <button type="button" class="msf-pill-btn" data-kind="Adjustment / Scrap" onclick="filterByKind('Adjustment / Scrap')">Adjustments & Scrap</button>
            </div>
            <div class="msf-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="msfSearchInput" placeholder="Search code, name, or route..." oninput="onSearchInput(this.value)">
            </div>
        </div>
    </div>

    <!-- 4. Movement Types & Configured Routes Cards Grid -->
    <div class="msf-cards-container" id="msfCardsContainer">
        <!-- Rendered dynamically by renderMovementTypeCards() -->
    </div>

    <!-- 5. Single Unified Modal: Configure Movement Type & Route -->
    <div class="msf-modal-overlay" id="msfMovementTypeModal" style="display: none;" onclick="onModalOverlayClick(event)">
        <div class="msf-modal-box" onclick="event.stopPropagation()">
            <div class="msf-modal-header">
                <div>
                    <h3 class="msf-modal-title" id="msfModalTitle">Configure Movement Type & Route</h3>
                    <p class="msf-modal-sub">Define the operation properties and configure its multi-step supply route sequence</p>
                </div>
                <button type="button" class="msf-modal-close" onclick="closeMovementTypeModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <div class="msf-modal-body">
                <form id="msfMovementTypeForm" onsubmit="event.preventDefault(); saveMovementType();">
                    <input type="hidden" id="msfFormId" value="">

                    <!-- Section A: Basic Movement Type Attributes -->
                    <div class="msf-form-section">
                        <div class="msf-form-section-title">
                            <i class="ph ph-identification-card"></i>
                            <span>Operation Specification</span>
                        </div>
                        <div class="msf-form-grid-4">
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormName">Operation Name <span class="req">*</span></label>
                                <input type="text" id="msfFormName" class="msf-input" placeholder="e.g. Vendor Goods Receipt (Dry)" required oninput="updateLivePreview()">
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormCode">Operation Code <span class="req">*</span></label>
                                <input type="text" id="msfFormCode" class="msf-input" placeholder="e.g. WH-MAIN-IN" required style="font-family: monospace;">
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormWarehouse">Target Warehouse <span class="req">*</span></label>
                                <select id="msfFormWarehouse" class="msf-select" required onchange="onModalWarehouseChange()">
                                    <!-- Populated dynamically -->
                                </select>
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormKind">Operation Kind <span class="req">*</span></label>
                                <select id="msfFormKind" class="msf-select" required onchange="onModalKindChange()">
                                    <option value="Inbound Receipt">Inbound Receipt (+ Stock In)</option>
                                    <option value="Outbound Dispatch">Outbound Dispatch (- Stock Out)</option>
                                    <option value="Internal Transfer">Internal Transfer (Relocation)</option>
                                    <option value="Adjustment / Scrap">Adjustment / Scrap (Write-Off)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Supply Flow / Route Steps Sequence -->
                    <div class="msf-form-section" style="margin-top: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div class="msf-form-section-title" style="margin-bottom: 0;">
                                <i class="ph ph-git-fork"></i>
                                <span>Configured Route (Supply Flow)</span>
                            </div>
                            <!-- Fast Route Presets -->
                            <div class="msf-presets-bar">
                                <span class="msf-presets-label">Quick Presets:</span>
                                <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('1-Step Direct')">
                                    <i class="ph ph-arrow-right"></i> 1-Step Direct
                                </button>
                                <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('2-Step Staged')">
                                    <i class="ph ph-arrows-split"></i> 2-Step Staged
                                </button>
                                <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('3-Step Quality Check')">
                                    <i class="ph ph-shield-check"></i> 3-Step Quality Check
                                </button>
                            </div>
                        </div>

                        <!-- Live Visual Pipeline Preview inside modal -->
                        <div class="msf-modal-route-preview" id="msfModalRoutePreview">
                            <!-- Live interactive step preview updated on the fly -->
                        </div>

                        <!-- Steps Container -->
                        <div class="msf-steps-container" id="msfModalStepsContainer">
                            <!-- Populated dynamically by renderModalSteps() -->
                        </div>

                        <div style="margin-top: 10px;">
                            <button type="button" class="msf-btn msf-btn-secondary msf-btn-sm" onclick="addNewStepToModal()">
                                <i class="ph ph-plus"></i>
                                <span>Add Next Routing Step</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="msf-modal-footer">
                <button type="button" class="msf-btn msf-btn-outline" onclick="closeMovementTypeModal()">Cancel</button>
                <button type="button" class="msf-btn msf-btn-primary" onclick="saveMovementType()">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Movement Type & Route</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
