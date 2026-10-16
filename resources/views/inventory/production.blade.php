@extends('layouts.app')

@section('title', 'Production & Kitchen Assembly - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/production.css')
@endpush

@push('blade-data')
<script>
    window.ProductionConfig = Object.freeze({
        routes: {
            createProductionBatch: @json(route('inventory.api.create-production-batch')),
            productionData: @json(route('inventory.api.production-data')),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialBoms: @json($initialBoms ?? []),
            initialProducts: @json($initialProducts ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/production.js')
@endpush

@section('content')

<div class="prd-workspace">

    <!-- 1. Page Header -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row prd-page-header">
            <div>
                <h1 class="hr-parent-title prd-page-title">
                    <i class="ph ph-factory"></i>
                    <span>Production & Kitchen Assembly</span>
                    <span class="hr-badge hr-badge-purple" style="font-size: 11px;">BOM Connected</span>
                </h1>
                <p class="hr-parent-subtitle prd-page-subtitle">Batch assembly runs, recipe yield scaling, real-time ingredient stock lookup, and automated ledger deductions.</p>
            </div>
            <div class="hr-page-actions prd-header-actions">
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-package"></i>
                    <span>Item Master</span>
                </a>
                <a href="{{ route('inventory.recipe-management') }}" class="hr-btn hr-btn-secondary" title="Manage Bill of Materials & Recipe Formulations">
                    <i class="ph ph-cooking-pot"></i>
                    <span>Bill of Materials (BOM)</span>
                </a>
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Live Stock Overview">
                    <i class="ph ph-squares-four"></i>
                    <span>Stocks Overview</span>
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchPrdTab('tab-new-batch')">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Production Batch</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Universal HR Tabs Bar -->
    <nav class="hr-tabs-wrapper tabs-wrapper">
        <button type="button" class="tab-btn active" id="btn-tab-batch-list" onclick="switchPrdTab('tab-batch-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Production Batches & Runs</span>
            <span class="tab-count" id="badgeOrderCount">{{ count($initialOrders ?? []) }}</span>
        </button>
        <button type="button" class="tab-btn" id="btn-tab-new-batch" onclick="switchPrdTab('tab-new-batch')">
            <i class="ph ph-cooking-pot"></i>
            <span>New Production Batch (Assembly Run)</span>
        </button>
        <button type="button" class="tab-btn" id="btn-tab-bom-catalog" onclick="switchPrdTab('tab-bom-catalog')">
            <i class="ph ph-book-open"></i>
            <span>BOM Recipe Reference Catalog</span>
            <span class="tab-count" id="badgeBomCount">{{ count($initialBoms ?? []) }}</span>
        </button>
    </nav>

    <!-- =========================================================================
         TAB 1: PRODUCTION BATCHES & RUNS (MASTER REGISTER)
         ========================================================================= -->
    <div class="prd-tab-panel active" id="tab-batch-list">
        <div class="prd-filter-bar">
            <div class="prd-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="prdSearchInput" class="prd-search-input" placeholder="Search batch number, lot #, product, chef, station..." oninput="handlePrdFilter()">
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select id="prdStatusFilter" class="prd-select" style="min-width: 160px;" onchange="handlePrdFilter()">
                    <option value="ALL">All Statuses</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="PLANNED">Planned</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveProductionData()" title="Sync live batch list">
                    <i class="ph ph-arrows-clockwise"></i>
                    <span>Sync</span>
                </button>
            </div>
        </div>

        <div class="prd-table-wrapper">
            <table class="prd-table">
                <thead>
                    <tr>
                        <th>Batch / Lot Reference</th>
                        <th>Finished Product</th>
                        <th>Kitchen Station</th>
                        <th style="text-align: right;">Planned Qty</th>
                        <th style="text-align: right;">Actual Yield</th>
                        <th style="text-align: center;">Efficiency</th>
                        <th style="text-align: right;">Batch Cost (₱)</th>
                        <th>Date & Chef</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="prdOrdersTbody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         TAB 2: NEW PRODUCTION BATCH (BATCH RUN ASSEMBLY ENGINE)
         ========================================================================= -->
    <div class="prd-tab-panel" id="tab-new-batch">
        <!-- Shortage Deficit Banner -->
        <div class="prd-deficit-alert-banner" id="prdDeficitBanner">
            <div class="prd-deficit-alert-title">
                <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
                <span>Raw Material Shortage Detected!</span>
            </div>
            <p class="prd-deficit-alert-desc" id="prdDeficitDesc">
                One or more ingredients have insufficient inventory. You can adjust the batch size or proceed with supervisory shortage override.
            </p>
        </div>

        <div class="prd-form-grid">
            <!-- Left Column: Batch Setup & Formulation Configuration -->
            <div class="prd-form-card">
                <h3 class="prd-form-title">
                    <i class="ph ph-sliders" style="color: var(--prd-primary);"></i>
                    <span>Batch Setup & Target Output</span>
                </h3>

                <!-- Product / Recipe Selection -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Target Finished Product <span style="color: #e11d48;">*</span></label>
                    <select id="prdFinishedProductSelect" class="prd-form-control" onchange="handleProductSelection(this.value)">
                        <option value="">-- Choose Finished Product with BOM --</option>
                        @foreach($initialBoms ?? [] as $bom)
                            <option value="{{ $bom->id }}" data-product-id="{{ $bom->finished_item_id }}" data-sku="{{ $bom->sku }}" data-uom="{{ $bom->uom }}" data-yield="{{ $bom->yield_quantity }}" data-shelf-life="{{ $bom->shelf_life_days }}" data-station="{{ $bom->category }}">
                                {{ $bom->item_name }} ({{ $bom->sku }}) - Yield: {{ $bom->yield_quantity }} {{ $bom->uom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Recipe Summary Card -->
                <div class="prd-recipe-pill-box" id="prdRecipePillBox" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: var(--prd-primary); font-size: 13px;" id="dispBomCode">BOM-CODE</span>
                        <span class="hr-badge hr-badge-purple" id="dispBomCategory">Category</span>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-medium); display: flex; justify-content: space-between;">
                        <span>Standard Recipe Yield:</span>
                        <strong id="dispBomYield">1.00 Portion</strong>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-medium); display: flex; justify-content: space-between;">
                        <span>Standard Unit Cost:</span>
                        <strong id="dispBomUnitCost">₱0.00</strong>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-muted); font-style: italic;" id="dispBomInstructions">
                        Recipe instructions...
                    </div>
                </div>

                <!-- Target Output Quantity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Planned Quantity <span style="color: #e11d48;">*</span></label>
                        <input type="number" id="prdPlannedQty" class="prd-form-control" min="0.1" step="0.1" value="10" oninput="recalculateBatchScaling()">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Actual Yield <small>(Produced)</small></label>
                        <input type="number" id="prdActualYield" class="prd-form-control" min="0.1" step="0.1" value="10" oninput="recalculateYieldEfficiency()">
                    </div>
                </div>

                <!-- Scaling & Efficiency Preview Pill -->
                <div style="display: flex; justify-content: space-between; align-items: center; background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; color: #7c3aed; font-weight: 700;">Scaling Factor</span>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; color: #9333ea;" id="dispBatchMultiplier">10.0x</div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #7c3aed; font-weight: 700;">Yield Efficiency</span>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; color: #9333ea;" id="dispYieldEfficiency">100.0%</div>
                    </div>
                </div>

                <!-- Kitchen Station & Date -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Kitchen Station / Line</label>
                    <select id="prdKitchenStation" class="prd-form-control">
                        <option value="Central Commissary - Prep Kitchen">Central Commissary - Prep Kitchen</option>
                        <option value="Hot Line / Sauces & Soups">Hot Line / Sauces & Soups</option>
                        <option value="Butchery & Meat Prep Unit">Butchery & Meat Prep Unit</option>
                        <option value="Bakery & Pastry Studio">Bakery & Pastry Studio</option>
                        <option value="Beverage & Barista Station">Beverage & Barista Station</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Production Date</label>
                        <input type="date" id="prdProductionDate" class="prd-form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Expiry Date <small>(Auto)</small></label>
                        <input type="date" id="prdExpiryDate" class="prd-form-control">
                    </div>
                </div>

                <!-- Operator / Chef Info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Produced By (Lead Chef)</label>
                        <input type="text" id="prdProducedBy" class="prd-form-control" value="{{ auth()->user()->full_name ?? auth()->user()->name ?? 'Executive Sous Chef' }}">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Verified By (QA / Sous)</label>
                        <input type="text" id="prdVerifiedBy" class="prd-form-control" placeholder="Optional QA stamp">
                    </div>
                </div>

                <!-- Quality Notes -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Batch Notes & Sensory QC</label>
                    <textarea id="prdQualityNotes" class="prd-form-control" style="height: 60px; padding: 8px 12px; resize: vertical;" placeholder="Organoleptic check, taste test notes, temperature log..."></textarea>
                </div>

                <!-- Cost Summary Card -->
                <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                        <span>Estimated Raw Materials:</span>
                        <strong id="dispTotalRawCost">₱0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                        <span>Labor & Overhead:</span>
                        <strong id="dispLaborOverheadCost">₱0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; color: var(--prd-text-strong); border-top: 1px dashed #d8b4fe; padding-top: 6px; margin-top: 6px;">
                        <span>Total Batch Cost:</span>
                        <span style="color: var(--prd-primary);" id="dispTotalProductionCost">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--prd-text-muted); margin-top: 4px;">
                        <span>Calculated Unit Cost:</span>
                        <strong id="dispUnitProductionCost">₱0.00 / unit</strong>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="button" id="btnProduceBatch" class="hr-btn hr-btn-primary" style="width: 100%; height: 44px; font-size: 14px;" onclick="submitProductionBatch()">
                    <i class="ph ph-check-circle"></i>
                    <span>Produce & Complete Batch</span>
                </button>
            </div>

            <!-- Right Column: Live BOM Raw Materials Consumption & Stock Availability Grid -->
            <div class="prd-form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 class="prd-form-title" style="margin: 0; border: none; padding: 0;">
                        <i class="ph ph-list-checks" style="color: var(--prd-primary);"></i>
                        <span>BOM Recipe Components & Live Stock Lookup</span>
                    </h3>
                    <span class="hr-badge hr-badge-purple" id="dispIngredientCountBadge">0 Ingredients</span>
                </div>

                <p style="font-size: 12.5px; color: var(--prd-text-muted); margin-top: -6px; margin-bottom: 14px;">
                    Ingredient quantities automatically scale proportionally with planned batch output. Available stock is verified live from Central Inventory.
                </p>

                <div class="prd-table-wrapper">
                    <table class="prd-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Raw Material / Ingredient</th>
                                <th style="text-align: right;">Required Qty</th>
                                <th style="text-align: right;">Stock On-Hand</th>
                                <th style="text-align: center;">Stock Status</th>
                                <th style="text-align: right;">Unit Cost</th>
                                <th style="text-align: right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody id="prdIngredientsTbody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                                    Please select a Target Finished Product on the left to load its Bill of Materials recipe.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         TAB 3: BOM RECIPE REFERENCE CATALOG
         ========================================================================= -->
    <div class="prd-tab-panel" id="tab-bom-catalog">
        <div class="prd-card-strip" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--prd-text-strong);">Active Bill of Materials Formulas</h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: var(--prd-text-muted);">Standard recipes configured in the database linking finished items to raw ingredients.</p>
                </div>
                <a href="{{ route('inventory.recipe-management') }}" class="hr-btn hr-btn-secondary">
                    <i class="ph ph-pencil-simple"></i>
                    <span>Configure in Recipe Management</span>
                </a>
            </div>

            <div class="prd-table-wrapper">
                <table class="prd-table">
                    <thead>
                        <tr>
                            <th>BOM Code</th>
                            <th>Finished Product</th>
                            <th>Category</th>
                            <th style="text-align: right;">Standard Yield</th>
                            <th style="text-align: right;">Raw Material Cost</th>
                            <th style="text-align: right;">Labor + Overhead</th>
                            <th style="text-align: right;">Total Cost</th>
                            <th style="text-align: right;">Selling Price</th>
                            <th style="text-align: center;">Margin %</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="prdBomCatalogTbody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL: PRINTABLE KITCHEN BATCH WORK ORDER TICKET
     ========================================================================= -->
<div class="prd-modal-backdrop" id="prdTicketModal">
    <div class="prd-modal-content" id="printableBatchTicket">
        <div style="padding: 24px; border-bottom: 1px solid var(--prd-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(147, 51, 234, 0.12); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--prd-text-strong);" id="modalTicketTitle">Kitchen Production Work Order</h3>
                    <span style="font-size: 12px; color: var(--prd-text-muted);" id="modalTicketBatchNo">PRD-20261005-0001</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="window.print()">
                    <i class="ph ph-printer"></i>
                    <span>Print Ticket</span>
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closePrdTicketModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <div style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">
            <!-- Metadata Grid -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; background: #faf5ff; padding: 14px; border-radius: 12px; border: 1px solid #f3e8ff; font-size: 12px;">
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Finished Item:</span>
                    <strong style="color: var(--prd-text-strong); font-size: 13px;" id="modalTicketProduct">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Batch Lot Number:</span>
                    <strong style="font-family: monospace; color: var(--prd-primary);" id="modalTicketLot">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Station / Prep Line:</span>
                    <strong id="modalTicketStation">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Planned Output:</span>
                    <strong id="modalTicketPlanned">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Actual Yield Produced:</span>
                    <strong style="color: #059669;" id="modalTicketActual">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Yield Efficiency:</span>
                    <strong id="modalTicketEfficiency">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Production Date:</span>
                    <strong id="modalTicketDate">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Shelf Life Expiry:</span>
                    <strong id="modalTicketExpiry">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Prepared By:</span>
                    <strong id="modalTicketChef">-</strong>
                </div>
            </div>

            <!-- Scaled Ingredients Table -->
            <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--prd-text-strong);">Raw Materials & Ingredients Consumed</h4>
            <div class="prd-table-wrapper">
                <table class="prd-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Raw Ingredient</th>
                            <th style="text-align: right;">Consumed Qty</th>
                            <th style="text-align: right;">Unit Cost</th>
                            <th style="text-align: right;">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody id="modalTicketItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Signatures Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #cbd5e1;">
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #475569; height: 35px; margin-bottom: 6px;"></div>
                    <span style="font-size: 11px; color: var(--prd-text-muted); text-transform: uppercase;">Lead Cook / Kitchen Operator Signature</span>
                </div>
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #475569; height: 35px; margin-bottom: 6px;"></div>
                    <span style="font-size: 11px; color: var(--prd-text-muted); text-transform: uppercase;">Executive Sous Chef / QA Inspector Sign-Off</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

