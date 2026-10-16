@extends('layouts.app')

@section('title', 'Waste & Expiry Management - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/waste-expiry.css')
@endpush

@push('blade-data')
<script>
    window.WasteExpiryConfig = Object.freeze({
        routes: {
            createWasteRecord: @json(route('inventory.api.create-waste-record')),
            updateWasteStatus: @json(route('inventory.api.update-waste-status')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/waste-expiry.js')
@endpush

@section('content')

<div class="wst-workspace">

    <!-- Top Header -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row wst-page-header">
            <div>
                <h1 class="hr-parent-title wst-page-title">
                    <i class="ph ph-trash-simple"></i>
                    <span>Waste, Defect & Expiry Tracking</span>
                    <span class="hr-badge hr-badge-purple">Kitchen Audit</span>
                </h1>
                <p class="hr-parent-subtitle wst-page-subtitle">Track spoilage, handling defects, batch expirations, and stock write-offs synchronized with Item Masterlist and Stocks Overview.</p>
            </div>
            <div class="hr-page-actions wst-header-actions">
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Live Stocks Overview">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <button class="hr-btn hr-btn-primary" onclick="switchTab('tab-log-waste')">
                    <i class="ph ph-plus-circle"></i> Log Waste / Defect
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="hr-tabs-wrapper tabs-wrapper">
        <button type="button" class="tab-btn active" id="btnTabRecords" onclick="switchTab('tab-records')">
            <i class="ph ph-list-dashes"></i> Waste & Defect Logs
            <span class="tab-count" id="badgeCountRecords">{{ $wasteRecords->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="btnTabLogWaste" onclick="switchTab('tab-log-waste')">
            <i class="ph ph-pencil-simple-line"></i> Log Waste / Defect Entry
            <span class="tab-count"><i class="ph ph-plus" style="font-size: 10px;"></i></span>
        </button>
        <button type="button" class="tab-btn" id="btnTabExpiryWatchlist" onclick="switchTab('tab-expiry-watchlist')">
            <i class="ph ph-clock-countdown"></i> Expiry & At-Risk Watchlist
            <span class="tab-count" id="badgeCountWatchlist">{{ $atRiskLots->count() }}</span>
        </button>
    </nav>

    <!-- =========================================================================
         TAB 1: WASTE & DEFECT LOGS / REGISTRY
         ========================================================================= -->
    <div class="wst-tab-panel active" id="tab-records">
        <!-- Search & Filter Controls -->
        <div class="wst-filter-bar">
            <div class="wst-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="wstSearchInput" class="wst-search-input" placeholder="Search by SKU, item name, voucher #, or reporter..." oninput="filterWasteTable()">
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <select id="wstTypeFilter" class="wst-select" onchange="filterWasteTable()">
                    <option value="ALL">All Incident Types</option>
                    <option value="SPOILAGE">Spoilage & Curdling</option>
                    <option value="EXPIRED">Expired Batch</option>
                    <option value="DAMAGED">Handling Damage</option>
                    <option value="DEFECTIVE">Packaging Defect</option>
                    <option value="PREP_FALLOUT">Prep Kitchen Fallout</option>
                    <option value="STORAGE_FAILURE">Cold Storage Failure</option>
                </select>

                <select id="wstStatusFilter" class="wst-select" onchange="filterWasteTable()">
                    <option value="ALL">All Statuses</option>
                    <option value="APPROVED">Approved / Written Off</option>
                    <option value="PENDING_REVIEW">Pending Review</option>
                    <option value="DISPOSED">Physically Disposed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>

                <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetWasteFilters()">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="wst-table-wrapper">
            <table class="wst-table" id="wasteLogsTable">
                <thead>
                    <tr>
                        <th>Waste Voucher #</th>
                        <th>Incident Type</th>
                        <th>Items & Primary SKU</th>
                        <th>Qty / UOM</th>
                        <th>Cost Valuation <span style="display:none;">Total Waste Valuation</span></th>
                        <th>Disposal Method</th>
                        <th>Reported By / Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="wasteLogsTbody">
                    @forelse($wasteRecords as $w)
                    @php
                        $firstItem = $w->items->first();
                        $itemSummary = $firstItem ? $firstItem->item_name : 'No items';
                        if ($w->items->count() > 1) {
                            $itemSummary .= ' (+' . ($w->items->count() - 1) . ' more)';
                        }
                        $skuSummary = $firstItem ? $firstItem->sku : '-';
                        $qtySummary = $firstItem ? number_format($firstItem->quantity, 2) . ' ' . $firstItem->uom : '-';
                    @endphp
                    <tr data-type="{{ $w->waste_type }}" data-status="{{ $w->status }}" data-search="{{ strtolower($w->waste_number . ' ' . $skuSummary . ' ' . $itemSummary . ' ' . $w->reported_by . ' ' . ($firstItem->reason_code ?? '')) }}">
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif; color: var(--wst-primary);">{{ $w->waste_number }}</strong>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $w->storage_location }}</div>
                        </td>
                        <td>
                            @if($w->waste_type === 'EXPIRED')
                                <span class="hr-badge hr-badge-red"><i class="ph ph-calendar-x"></i> Expired</span>
                            @elseif($w->waste_type === 'SPOILAGE')
                                <span class="hr-badge hr-badge-amber"><i class="ph ph-drop-half-bottom"></i> Spoilage</span>
                            @elseif($w->waste_type === 'DAMAGED')
                                <span class="hr-badge hr-badge-purple"><i class="ph ph-hammer"></i> Damaged</span>
                            @elseif($w->waste_type === 'DEFECTIVE')
                                <span class="hr-badge hr-badge-blue"><i class="ph ph-warning"></i> Defect</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $w->waste_type }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--wst-text-strong);">{{ $itemSummary }}</div>
                            <div style="font-size: 11px; color: var(--wst-text-muted);"><span style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px;">{{ $skuSummary }}</span> @if($firstItem && $firstItem->reason_code) &bull; {{ $firstItem->reason_code }} @endif</div>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-family: 'Outfit', sans-serif;">{{ $qtySummary }}</span>
                        </td>
                        <td>
                            <strong style="color: var(--wst-danger); font-family: 'Outfit', sans-serif;">-₱{{ number_format($w->total_cost, 2) }}</strong>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: var(--wst-text-medium);">{{ $w->disposal_method }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $w->reported_by }}</div>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ \Carbon\Carbon::parse($w->waste_date)->format('M d, Y') }}</div>
                        </td>
                        <td>
                            @if($w->status === 'APPROVED')
                                <span class="hr-badge hr-badge-green"><i class="ph ph-check-circle"></i> Approved</span>
                            @elseif($w->status === 'PENDING_REVIEW')
                                <span class="hr-badge hr-badge-amber"><i class="ph ph-clock"></i> In Review</span>
                            @elseif($w->status === 'DISPOSED')
                                <span class="hr-badge hr-badge-blue"><i class="ph ph-trash"></i> Disposed</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $w->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="viewWasteDetail({{ json_encode($w) }})">
                                <i class="ph ph-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px; color: var(--wst-text-muted);">
                            <i class="ph ph-trash-simple" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            No waste or defect records found. Use the "Log Waste / Defect Entry" tab to record write-offs.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         TAB 2: LOG WASTE / DEFECT ENTRY FORM
         ========================================================================= -->
    <div class="wst-tab-panel" id="tab-log-waste">
        <div class="wst-form-grid">
            <!-- Left Column: Master Form Inputs -->
            <div class="wst-form-card">
                <h3 class="wst-form-title">
                    <i class="ph ph-pencil-simple-line" style="color: var(--wst-primary);"></i>
                    Record New Incident / Loss
                </h3>

                <form id="formLogWaste" onsubmit="submitWasteRecord(event)">
                    @csrf

                    <!-- Incident Classification -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Incident Classification <small>(Required)</small></label>
                        <select id="inputWasteType" class="wst-form-control" required onchange="onWasteTypeChange()">
                            <option value="SPOILAGE">Spoilage & Curdling (Biological)</option>
                            <option value="EXPIRED">Expired Batch (Past Expiry Date)</option>
                            <option value="DAMAGED">Handling / Dropped Damage</option>
                            <option value="DEFECTIVE">Packaging / Seal Defect</option>
                            <option value="PREP_FALLOUT">Prep Kitchen Fallout / Trim</option>
                            <option value="STORAGE_FAILURE">Cold Storage / Temperature Breach</option>
                            <option value="OTHER">Other Unforeseen Loss</option>
                        </select>
                    </div>

                    <!-- Target Item Selection from Masterlist -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Select Item from Masterlist <small>(Required)</small></label>
                        <select id="inputInventoryItem" class="wst-form-control" required onchange="onSelectItem(this.value)">
                            <option value="">-- Choose Item / SKU --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" 
                                    data-sku="{{ $p->sku }}"
                                    data-name="{{ $p->name }}"
                                    data-category="{{ $p->category }}"
                                    data-uom="{{ $p->uom }}"
                                    data-stock="{{ $p->current_stock }}"
                                    data-cost="{{ $p->cost_price }}"
                                    data-location="{{ $p->storage_location }}">
                                    [{{ $p->sku }}] {{ $p->name }} — On Hand: {{ number_format($p->current_stock, 2) }} {{ $p->uom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Live Item Preview Box -->
                    <div class="wst-item-preview" id="itemPreviewBox" style="display: none;">
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Current Physical Stock:</span>
                            <span class="wst-preview-val" id="prevStock">-</span>
                        </div>
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Unit Cost:</span>
                            <span class="wst-preview-val" id="prevCost">-</span>
                        </div>
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Storage Location:</span>
                            <span style="font-weight: 600;" id="prevLocation">-</span>
                        </div>
                    </div>

                    <!-- Quantity & Reason Code Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Write-Off Quantity <small>(Required)</small></label>
                            <input type="number" step="0.01" min="0.01" id="inputQuantity" class="wst-form-control" required placeholder="0.00" oninput="calculateLossValuation()">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Loss Valuation <small>(Computed)</small></label>
                            <input type="text" id="inputValuation" class="wst-form-control" readonly style="background: #f8fafc; font-weight: 700; color: var(--wst-danger);" value="₱0.00">
                        </div>
                    </div>

                    <div class="wst-form-group">
                        <label class="wst-form-label">Reason Code <small>(Required)</small></label>
                        <select id="inputReasonCode" class="wst-form-control" required>
                            <option value="Expired on Shelf">Expired on Shelf</option>
                            <option value="Mold / Spoilage">Mold / Spoilage</option>
                            <option value="Cold Chain Temperature Breach">Cold Chain Temperature Breach</option>
                            <option value="Physical Handling Damage">Physical Handling Damage</option>
                            <option value="Packaging Defect">Packaging Defect</option>
                            <option value="Preparation Fallout">Preparation Fallout</option>
                            <option value="Pest Contamination">Pest Contamination</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Batch / Lot # <small>(Optional)</small></label>
                            <input type="text" id="inputBatchLot" class="wst-form-control" placeholder="e.g. LOT-202610-01">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Expiry Date <small>(If applicable)</small></label>
                            <input type="date" id="inputExpiryDate" class="wst-form-control">
                        </div>
                    </div>

                    <!-- Disposal Action -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Disposal Action <small>(Required)</small></label>
                        <select id="inputDisposalMethod" class="wst-form-control" required>
                            <option value="Discarded / Trashed">Discarded / Trashed</option>
                            <option value="Composted">Composted</option>
                            <option value="Supplier Return / Credit Claim">Supplier Return / Credit Claim</option>
                            <option value="Hazardous Disposal">Hazardous Disposal</option>
                            <option value="Animal Feed / Bio-waste">Animal Feed / Bio-waste</option>
                        </select>
                    </div>

                    <!-- Reporter & Date Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Reported By</label>
                            <input type="text" id="inputReportedBy" class="wst-form-control" required value="{{ auth()->user()->full_name ?? 'Kitchen Staff' }}">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Incident Date</label>
                            <input type="date" id="inputWasteDate" class="wst-form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- Investigation Notes -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Incident Notes & Evidence</label>
                        <textarea id="inputNotes" class="wst-form-control" placeholder="Describe root cause, storage conditions, or immediate corrective action..."></textarea>
                    </div>

                    <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; padding: 12px; font-size: 14px;">
                        <i class="ph ph-check-circle"></i> Commit Waste Write-Off & Deduct Stock
                    </button>
                </form>
            </div>

            <!-- Right Column: Standard Operating Procedures & Guidelines -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="wst-form-card">
                    <h3 class="wst-form-title">
                        <i class="ph ph-book-open" style="color: var(--wst-primary);"></i>
                        Loss Logging & Audit Principles
                    </h3>
                    <div style="font-size: 13px; color: var(--wst-text-medium); display: flex; flex-direction: column; gap: 14px;">
                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">1</div>
                            <div>
                                <strong>Direct Stock Ledger Impact (SYS_LEDGER)</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Every logged incident automatically appends an immutable record with negative quantity to the central Stock Ledger with reference number <code>WST-XXXX</code>.</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">2</div>
                            <div>
                                <strong>Stocks Overview Synchronization</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Reflects live under <strong>Col 6: Waste / Loss (-)</strong> in Stocks Overview, segmented by damaged vs. expired quantities.</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">3</div>
                            <div>
                                <strong>Supplier Return & Credit Claims</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Defects discovered at receiving dock can be assigned to <code>Supplier Return / Credit Claim</code> for reimbursement against outstanding vendor bills.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Write-Offs Quick Card -->
                <div class="wst-form-card">
                    <h3 class="wst-form-title">
                        <i class="ph ph-clock-counter-clockwise" style="color: var(--wst-primary);"></i>
                        Recent Approved Write-Offs
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($wasteRecords->take(4) as $rec)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 8px;">
                            <div>
                                <strong style="font-size: 12.5px; color: var(--wst-text-strong);">{{ $rec->waste_number }}</strong>
                                <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $rec->waste_type }} &bull; {{ $rec->reported_by }}</div>
                            </div>
                            <span style="font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--wst-danger); font-size: 13px;">
                                -₱{{ number_format($rec->total_cost, 2) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         TAB 3: EXPIRY & AT-RISK WATCHLIST
         ========================================================================= -->
    <div class="wst-tab-panel" id="tab-expiry-watchlist">
        <!-- Watchlist Filter Bar -->
        <div class="wst-filter-bar">
            <div class="wst-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="watchlistSearchInput" class="wst-search-input" placeholder="Search at-risk lot numbers or SKU..." oninput="filterWatchlistTable()">
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <select id="watchlistRiskFilter" class="wst-select" onchange="filterWatchlistTable()">
                    <option value="ALL">All Risk Levels</option>
                    <option value="EXPIRED">Expired (&lt; 0 Days)</option>
                    <option value="CRITICAL">Critical (0 - 3 Days)</option>
                    <option value="WARNING">Warning (4 - 7 Days)</option>
                    <option value="HEALTHY">Healthy (&gt; 7 Days)</option>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="wst-table-wrapper">
            <table class="wst-table" id="expiryWatchlistTable">
                <thead>
                    <tr>
                        <th>Batch / Lot Number</th>
                        <th>Item & SKU</th>
                        <th>Estimated Balance</th>
                        <th>Expiry Date</th>
                        <th>Days Remaining</th>
                        <th>Risk Assessment</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="expiryWatchlistTbody">
                    @forelse($atRiskLots as $lot)
                    <tr data-risk="{{ $lot->risk_status }}" data-search="{{ strtolower(($lot->batch_lot_no ?? '') . ' ' . $lot->sku . ' ' . $lot->item_name) }}">
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif; color: var(--wst-primary);">{{ $lot->batch_lot_no ?? 'UNBATCHED' }}</strong>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $lot->storage_location ?? 'Commissary Chiller' }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--wst-text-strong);">{{ $lot->item_name }}</div>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 10px;">{{ $lot->sku }}</span>
                        </td>
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif;">{{ number_format($lot->est_quantity ?? 0, 2) }}</strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ \Carbon\Carbon::parse($lot->expiry_date)->format('M d, Y') }}</div>
                        </td>
                        <td>
                            @if($lot->days_remaining < 0)
                                <strong style="color: var(--wst-danger);">Expired {{ abs($lot->days_remaining) }} days ago</strong>
                            @elseif($lot->days_remaining === 0)
                                <strong style="color: var(--wst-danger);">Expires today!</strong>
                            @else
                                <span style="font-weight: 600;">{{ $lot->days_remaining }} days left</span>
                            @endif
                        </td>
                        <td>
                            <span class="hr-badge {{ $lot->badge_class }}">
                                @if($lot->risk_status === 'EXPIRED')
                                    <i class="ph ph-warning-octagon"></i> Expired
                                @elseif($lot->risk_status === 'CRITICAL')
                                    <i class="ph ph-alarm"></i> Critical (Urgent)
                                @elseif($lot->risk_status === 'WARNING')
                                    <i class="ph ph-hourglass-high"></i> High Risk
                                @else
                                    <i class="ph ph-check-circle"></i> Healthy
                                @endif
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="hr-btn hr-btn-danger hr-btn-sm" onclick="quickWriteOffLot({{ json_encode($lot) }})">
                                <i class="ph ph-trash"></i> Write Off Loss
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px; color: var(--wst-text-muted);">
                            <i class="ph ph-shield-check" style="font-size: 36px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                            All monitored inventory lots are well within their safety shelf life. No near-expiry items detected.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL: WASTE RECORD DETAIL AUDIT
     ========================================================================= -->
<div class="wst-modal-backdrop" id="wasteDetailModal">
    <div class="wst-modal-box">
        <div class="wst-modal-header">
            <h3 class="wst-modal-title">
                <i class="ph ph-file-text" style="color: var(--wst-primary);"></i>
                <span id="modalWasteNumber">WST-XXXX</span>
            </h3>
            <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--wst-text-muted);" onclick="closeWasteModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="wst-modal-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Incident Type:</span>
                    <strong id="modalWasteType">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Date Recorded:</span>
                    <strong id="modalWasteDate">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Reported By:</span>
                    <strong id="modalReportedBy">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Disposal Method:</span>
                    <strong id="modalDisposalMethod">-</strong>
                </div>
            </div>

            <h4 style="font-family: 'Outfit', sans-serif; margin: 16px 0 8px 0;">Item Write-Off Breakdown</h4>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 8px 12px; text-align: left;">Item</th>
                            <th style="padding: 8px 12px; text-align: center;">Qty</th>
                            <th style="padding: 8px 12px; text-align: right;">Unit Cost</th>
                            <th style="padding: 8px 12px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTbody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; padding: 14px; background: #f8fafc; border-radius: 10px;">
                <span style="font-weight: 600; color: var(--wst-text-muted); font-size: 11.5px; display: block; margin-bottom: 4px;">Auditor & Investigation Notes:</span>
                <p id="modalNotes" style="margin: 0; color: var(--wst-text-medium); font-size: 12.5px;">-</p>
            </div>
        </div>
        <div class="wst-modal-footer">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="closeWasteModal()">Close</button>
            <button type="button" class="hr-btn hr-btn-danger" id="btnCancelRecord" onclick="cancelWasteRecord()">
                <i class="ph ph-prohibit"></i> Cancel & Reverse Write-Off
            </button>
        </div>
    </div>
</div>

@endsection
