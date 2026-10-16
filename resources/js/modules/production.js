/* ==========================================================================
   PRODUCTION ENGINE - CLIENT DATA STORE & REACTIVE CONTROLLER
   ========================================================================== */
    let rawOrders = window.ProductionConfig?.data?.initialOrders || [];
    let rawBoms = window.ProductionConfig?.data?.initialBoms || [];
    let rawProducts = window.ProductionConfig?.data?.initialProducts || [];
let activeBom = null;
let currentShortages = [];

document.addEventListener('DOMContentLoaded', () => {
    renderProductionOrders();
    renderBomCatalog();
    
    // Auto-select first BOM if available
    const select = document.getElementById('prdFinishedProductSelect');
    if (select && select.options && select.options.length > 1) {
        select.selectedIndex = 1;
        handleProductSelection(select.value);
    }
});

/* --------------------------------------------------------------------------
   TAB SWITCHING
   -------------------------------------------------------------------------- */
function switchPrdTab(tabId) {
    document.querySelectorAll('.prd-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(tabId);
    if (panel) panel.classList.add('active');

    const btn = document.getElementById('btn-' + tabId);
    if (btn) btn.classList.add('active');
}

/* --------------------------------------------------------------------------
   TAB 1: RENDER PRODUCTION ORDERS
   -------------------------------------------------------------------------- */
function renderProductionOrders() {
    const tbody = document.getElementById('prdOrdersTbody');
    if (!tbody) return;

    const query = (document.getElementById('prdSearchInput')?.value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('prdStatusFilter')?.value || 'ALL';

    const filtered = rawOrders.filter(o => {
        if (statusFilter !== 'ALL' && o.status !== statusFilter) return false;
        if (query) {
            const num = (o.production_number || '').toLowerCase();
            const lot = (o.batch_lot_number || '').toLowerCase();
            const item = (o.item_name || '').toLowerCase();
            const sku = (o.sku || '').toLowerCase();
            const station = (o.kitchen_station || '').toLowerCase();
            const chef = (o.produced_by || '').toLowerCase();
            if (!num.includes(query) && !lot.includes(query) && !item.includes(query) && !sku.includes(query) && !station.includes(query) && !chef.includes(query)) {
                return false;
            }
        }
        return true;
    });

    document.getElementById('badgeOrderCount').textContent = rawOrders.length;

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; color: var(--prd-text-muted); padding: 36px;">
                    <i class="ph ph-factory" style="font-size: 32px; opacity: 0.35; display: block; margin-bottom: 8px; color: var(--prd-primary);"></i>
                    No production batches found matching filter criteria.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    filtered.forEach(o => {
        const planned = parseFloat(o.planned_quantity) || 0;
        const actual = parseFloat(o.actual_quantity) || 0;
        const eff = parseFloat(o.yield_efficiency_percent) || 100;
        const cost = parseFloat(o.total_production_cost) || 0;

        let effBadgeClass = 'hr-badge-green';
        if (eff < 95) effBadgeClass = 'hr-badge-amber';
        if (eff < 85) effBadgeClass = 'hr-badge-red';

        let statusClass = 'hr-badge-green';
        if (o.status === 'PLANNED') statusClass = 'hr-badge-blue';
        if (o.status === 'IN_PROGRESS') statusClass = 'hr-badge-amber';
        if (o.status === 'CANCELLED') statusClass = 'hr-badge-red';

        html += `
            <tr>
                <td>
                    <div style="font-weight: 700; color: var(--prd-text-strong);">${o.production_number}</div>
                    <div style="font-size: 11px; font-family: monospace; color: var(--prd-primary);">${o.batch_lot_number || '-'}</div>
                </td>
                <td>
                    <div style="font-weight: 600; color: var(--prd-text-strong);">${escapeHtml(o.item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted); font-family: monospace;">SKU: ${o.sku}</div>
                </td>
                <td>
                    <span class="hr-badge hr-badge-neutral">${escapeHtml(o.kitchen_station || 'Prep Kitchen')}</span>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ${planned.toFixed(1)} ${o.uom}
                </td>
                <td style="text-align: right; font-weight: 700; color: #059669; font-variant-numeric: tabular-nums;">
                    ${actual.toFixed(1)} ${o.uom}
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge ${effBadgeClass}">${eff.toFixed(1)}%</span>
                </td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">
                    ₱${cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td>
                    <div style="font-size: 12px; font-weight: 600;">${formatDate(o.production_date)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted);">${escapeHtml(o.produced_by)}</div>
                </td>
                <td>
                    <span class="hr-badge ${statusClass}">${o.status}</span>
                    ${o.proceed_with_shortage ? '<span class="hr-badge hr-badge-red" style="margin-left: 3px;" title="Shortage override logged">Shortage</span>' : ''}
                </td>
                <td style="text-align: right;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPrdTicketModal(${o.id})" title="Print or view batch ticket">
                        <i class="ph ph-receipt"></i>
                        <span>Ticket</span>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function handlePrdFilter() {
    renderProductionOrders();
}

/* --------------------------------------------------------------------------
   TAB 2: PRODUCT SELECTION & BATCH FORMULATION
   -------------------------------------------------------------------------- */
function handleProductSelection(bomId) {
    activeBom = rawBoms.find(b => b.id == bomId);
    const pillBox = document.getElementById('prdRecipePillBox');

    if (!activeBom) {
        if (pillBox) pillBox.style.display = 'none';
        document.getElementById('prdIngredientsTbody').innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    Please select a Target Finished Product on the left to load its Bill of Materials recipe.
                </td>
            </tr>
        `;
        return;
    }

    if (pillBox) pillBox.style.display = 'flex';
    document.getElementById('dispBomCode').textContent = activeBom.bom_code;
    document.getElementById('dispBomCategory').textContent = activeBom.category || 'Recipe';
    document.getElementById('dispBomYield').textContent = `${parseFloat(activeBom.yield_quantity).toFixed(2)} ${activeBom.uom}`;
    document.getElementById('dispBomUnitCost').textContent = `₱${parseFloat(activeBom.unit_cost || 0).toFixed(2)} / ${activeBom.uom}`;
    document.getElementById('dispBomInstructions').textContent = activeBom.instructions || 'Standard culinary assembly procedure.';

    // Auto calculate expiry
    const shelfLife = parseInt(activeBom.shelf_life_days) || 7;
    const prodDateStr = document.getElementById('prdProductionDate').value;
    if (prodDateStr) {
        const d = new Date(prodDateStr);
        d.setDate(d.getDate() + shelfLife);
        document.getElementById('prdExpiryDate').value = d.toISOString().split('T')[0];
    }

    recalculateBatchScaling();
}

function recalculateBatchScaling() {
    if (!activeBom) return;

    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value) || 0;
    const baseYield = parseFloat(activeBom.yield_quantity) || 1.0;
    const scale = baseYield > 0 ? (plannedQty / baseYield) : 1.0;

    document.getElementById('dispBatchMultiplier').textContent = scale.toFixed(2) + 'x';
    
    // Auto sync actual yield with planned if unmodified
    const actualInput = document.getElementById('prdActualYield');
    if (!actualInput.dataset.manualEdited) {
        actualInput.value = plannedQty;
    }
    recalculateYieldEfficiency();

    // Render scaled ingredients table & lookup available stock
    renderScaledIngredients(scale);
}

function recalculateYieldEfficiency() {
    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value) || 0;
    const actualQty = parseFloat(document.getElementById('prdActualYield').value) || 0;
    const eff = plannedQty > 0 ? ((actualQty / plannedQty) * 100) : 100.0;
    document.getElementById('dispYieldEfficiency').textContent = eff.toFixed(1) + '%';
}

document.getElementById('prdActualYield')?.addEventListener('input', function() {
    this.dataset.manualEdited = "true";
});

function renderScaledIngredients(scale) {
    const tbody = document.getElementById('prdIngredientsTbody');
    const items = activeBom.items || [];
    document.getElementById('dispIngredientCountBadge').textContent = `${items.length} ${items.length === 1 ? 'Ingredient' : 'Ingredients'}`;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    This BOM recipe currently has no component ingredients registered.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    let totalRawCost = 0.0;
    currentShortages = [];

    items.forEach(it => {
        const reqQty = scale * (parseFloat(it.quantity) || 0);
        
        // Find real-time stock from rawProducts or it.raw_item
        const rawProd = rawProducts.find(p => p.id === it.raw_item_id) || it.raw_item || {};
        const onHand = parseFloat(rawProd.current_stock !== undefined ? rawProd.current_stock : (it.raw_item?.current_stock || 0));
        const unitCost = parseFloat(rawProd.cost_price || it.unit_cost || 0);
        const lineCost = reqQty * unitCost;
        totalRawCost += lineCost;

        const isShort = reqQty > onHand;
        if (isShort) {
            currentShortages.push({
                sku: it.sku,
                name: it.raw_item_name,
                uom: it.uom,
                required: reqQty,
                available: onHand,
                deficit: reqQty - onHand
            });
        }

        html += `
            <tr style="${isShort ? 'background-color: #fff1f2;' : ''}">
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${it.sku}</td>
                <td>
                    <div style="font-weight: 600;">${escapeHtml(it.raw_item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted);">${escapeHtml(it.notes || '')}</div>
                </td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">
                    ${reqQty.toFixed(4)} <small>${it.uom}</small>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; ${isShort ? 'color: #e11d48; font-weight: 700;' : ''}">
                    ${onHand.toFixed(2)} <small>${it.uom}</small>
                </td>
                <td style="text-align: center;">
                    ${isShort ? 
                        `<span class="hr-badge hr-badge-red" title="Deficit: -${(reqQty - onHand).toFixed(2)} ${it.uom}"><i class="ph ph-warning"></i> Deficit</span>` : 
                        `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Sufficient</span>`
                    }
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${unitCost.toFixed(2)}</td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">₱${lineCost.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Update cost calculations
    const laborCost = (parseFloat(activeBom.labor_cost) || 0) * scale;
    const overheadCost = (parseFloat(activeBom.overhead_cost) || 0) * scale;
    const totalProdCost = totalRawCost + laborCost + overheadCost;
    const actualQty = parseFloat(document.getElementById('prdActualYield').value) || 1.0;
    const unitProdCost = actualQty > 0 ? (totalProdCost / actualQty) : 0;

    document.getElementById('dispTotalRawCost').textContent = '₱' + totalRawCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispLaborOverheadCost').textContent = '₱' + (laborCost + overheadCost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispTotalProductionCost').textContent = '₱' + totalProdCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispUnitProductionCost').textContent = '₱' + unitProdCost.toFixed(2) + ` / ${activeBom.uom}`;

    // Overdraft Alert Banner
    const banner = document.getElementById('prdDeficitBanner');
    const bannerDesc = document.getElementById('prdDeficitDesc');
    if (currentShortages.length > 0) {
        banner.style.display = 'block';
        bannerDesc.innerHTML = `
            <strong>Warning:</strong> ${currentShortages.length} raw material(s) have insufficient stock on hand:
            <ul style="margin: 6px 0 0 16px; padding: 0;">
                ${currentShortages.map(s => `<li><strong>${s.name}:</strong> Requires ${s.required.toFixed(2)} ${s.uom}, only ${s.available.toFixed(2)} ${s.uom} on hand (Deficit: -${s.deficit.toFixed(2)} ${s.uom})</li>`).join('')}
            </ul>
        `;
    } else {
        banner.style.display = 'none';
    }
}

/* --------------------------------------------------------------------------
   SUBMIT PRODUCTION BATCH (WITH POPUP SHORTAGE NOTIFICATION)
   -------------------------------------------------------------------------- */
async function submitProductionBatch(forceShortageOverride = false) {
    if (!activeBom) {
        Swal.fire({ icon: 'warning', title: 'Target Product Required', text: 'Please select a finished product with an active BOM.', confirmButtonColor: '#9333ea' });
        return;
    }

    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value);
    const actualQty = parseFloat(document.getElementById('prdActualYield').value);
    if (!plannedQty || plannedQty <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid planned output quantity.', confirmButtonColor: '#9333ea' });
        return;
    }

    // 1. Check for shortages & Trigger popup notification if unavailable
    if (currentShortages.length > 0 && !forceShortageOverride) {
        let shortageListHtml = '<div style="text-align: left; font-size: 13px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 12px; margin-top: 10px;">';
        shortageListHtml += '<strong style="color: #9f1239;">Deficient Ingredients:</strong><ul style="margin: 6px 0 0 16px; padding: 0;">';
        currentShortages.forEach(s => {
            shortageListHtml += `<li><strong>${escapeHtml(s.name)}:</strong> Need ${s.required.toFixed(2)} ${s.uom} &bull; On Hand: ${s.available.toFixed(2)} (Deficit: <span style="color: #e11d48; font-weight: 700;">-${s.deficit.toFixed(2)} ${s.uom}</span>)</li>`;
        });
        shortageListHtml += '</ul></div>';
        shortageListHtml += '<p style="margin-top: 12px; font-size: 12px; color: #475569;">Proceeding will deduct stock into negative balance or require emergency kitchen inventory replenishment. Do you still want to proceed?</p>';

        const result = await Swal.fire({
            icon: 'warning',
            title: 'Raw Material Shortage Detected!',
            html: shortageListHtml,
            showCancelButton: true,
            confirmButtonText: 'Proceed Anyway (Allow Shortage)',
            cancelButtonText: 'Cancel & Adjust Batch Size',
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            focusCancel: true
        });

        if (!result.isConfirmed) {
            return; // User canceled to adjust batch
        }
        forceShortageOverride = true;
    }

    // 2. Transmit batch run to backend
    const payload = {
        bill_of_materials_id: activeBom.id,
        planned_quantity: plannedQty,
        actual_quantity: actualQty,
        production_date: document.getElementById('prdProductionDate').value,
        expiry_date: document.getElementById('prdExpiryDate').value || null,
        kitchen_station: document.getElementById('prdKitchenStation').value,
        produced_by: document.getElementById('prdProducedBy').value || 'Executive Sous Chef',
        verified_by: document.getElementById('prdVerifiedBy').value || null,
        proceed_with_shortage: forceShortageOverride,
        quality_notes: document.getElementById('prdQualityNotes').value || null,
    };

    try {
        Swal.fire({
            title: 'Executing Production Batch...',
            text: 'Deducting raw materials and incrementing finished inventory in Master Stock Ledger.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.ProductionConfig?.routes?.createProductionBatch || '/inventory/api/create-production-batch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Failed to complete production batch.');
        }

        await fetchLiveProductionData();

        Swal.fire({
            icon: 'success',
            title: 'Production Completed!',
            html: `
                <p>Batch <strong>${json.data.order.production_number}</strong> (${json.data.order.batch_lot_number}) successfully manufactured.</p>
                <p style="font-size: 12px; color: #64748b;">
                    Added <strong>+${actualQty} ${activeBom.uom}</strong> to <strong>${activeBom.item_name}</strong> in Stocks Overview.
                    All raw materials consumed and logged to Master Ledger.
                </p>
            `,
            confirmButtonColor: '#9333ea'
        });

        switchPrdTab('tab-batch-list');
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Production Error',
            text: e.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveProductionData() {
    try {
        const res = await fetch(window.ProductionConfig?.routes?.productionData || '/inventory/api/production-data');
        const json = await res.json();
        if (json.success && json.data) {
            rawOrders = json.data.orders || [];
            rawBoms = json.data.boms || [];
            rawProducts = json.data.products || [];

            renderProductionOrders();
            renderBomCatalog();
        }
    } catch (e) {
        console.error('Failed to sync live production data:', e);
    }
}

/* --------------------------------------------------------------------------
   TAB 3: BOM CATALOG
   -------------------------------------------------------------------------- */
function renderBomCatalog() {
    const tbody = document.getElementById('prdBomCatalogTbody');
    if (!tbody) return;
    document.getElementById('badgeBomCount').textContent = rawBoms.length;

    if (rawBoms.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    No BOM recipes configured yet.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    rawBoms.forEach(b => {
        const yieldVal = parseFloat(b.yield_quantity) || 1.0;
        const rawCost = parseFloat(b.total_raw_cost) || 0;
        const laborOverhead = (parseFloat(b.labor_cost) || 0) + (parseFloat(b.overhead_cost) || 0);
        const totalCost = parseFloat(b.total_cost) || (rawCost + laborOverhead);
        const sellingPrice = parseFloat(b.selling_price) || 0;
        const margin = parseFloat(b.margin_percentage) || 0;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${b.bom_code}</td>
                <td>
                    <div style="font-weight: 700; color: var(--prd-text-strong);">${escapeHtml(b.item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted); font-family: monospace;">SKU: ${b.sku}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${b.category || 'General'}</span></td>
                <td style="text-align: right; font-weight: 600;">${yieldVal.toFixed(2)} ${b.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${rawCost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${laborOverhead.toFixed(2)}</td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">₱${totalCost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${sellingPrice.toFixed(2)}</td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-green">${margin.toFixed(1)}%</span>
                </td>
                <td style="text-align: right;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="selectBomForBatch(${b.id})" title="Start production run for this recipe">
                        <i class="ph ph-plus-circle"></i>
                        <span>Produce</span>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function selectBomForBatch(bomId) {
    const select = document.getElementById('prdFinishedProductSelect');
    if (select) {
        select.value = bomId;
        handleProductSelection(bomId);
    }
    switchPrdTab('tab-new-batch');
}

/* --------------------------------------------------------------------------
   MODAL BATCH TICKET
   -------------------------------------------------------------------------- */
function openPrdTicketModal(orderId) {
    const o = rawOrders.find(ord => ord.id === orderId);
    if (!o) return;

    document.getElementById('modalTicketTitle').textContent = `Kitchen Batch Work Order: ${o.item_name}`;
    document.getElementById('modalTicketBatchNo').textContent = `${o.production_number} [${o.status}]`;
    document.getElementById('modalTicketProduct').textContent = `${o.item_name} (${o.sku})`;
    document.getElementById('modalTicketLot').textContent = o.batch_lot_number || '-';
    document.getElementById('modalTicketStation').textContent = o.kitchen_station || 'Prep Kitchen';
    document.getElementById('modalTicketPlanned').textContent = `${parseFloat(o.planned_quantity).toFixed(1)} ${o.uom}`;
    document.getElementById('modalTicketActual').textContent = `${parseFloat(o.actual_quantity).toFixed(1)} ${o.uom}`;
    document.getElementById('modalTicketEfficiency').textContent = `${parseFloat(o.yield_efficiency_percent || 100).toFixed(1)}%`;
    document.getElementById('modalTicketDate').textContent = formatDate(o.production_date);
    document.getElementById('modalTicketExpiry').textContent = formatDate(o.expiry_date);
    document.getElementById('modalTicketChef').textContent = o.produced_by;

    const tbody = document.getElementById('modalTicketItemsTbody');
    let html = '';
    (o.items || []).forEach(it => {
        const qty = parseFloat(it.actual_consumed_qty || it.required_qty) || 0;
        const unit = parseFloat(it.unit_cost) || 0;
        const line = parseFloat(it.total_cost) || (qty * unit);
        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">${qty.toFixed(4)} ${it.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${unit.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${line.toFixed(2)}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    document.getElementById('prdTicketModal').classList.add('open');
}

function closePrdTicketModal() {
    document.getElementById('prdTicketModal').classList.remove('open');
}

/* --------------------------------------------------------------------------
   HELPERS
   -------------------------------------------------------------------------- */
function formatDate(dStr) {
    if (!dStr) return '-';
    try {
        const d = new Date(dStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return dStr;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  switchPrdTab,
  renderProductionOrders,
  handlePrdFilter,
  handleProductSelection,
  recalculateBatchScaling,
  recalculateYieldEfficiency,
  renderScaledIngredients,
  submitProductionBatch,
  fetchLiveProductionData,
  renderBomCatalog,
  selectBomForBatch,
  openPrdTicketModal,
  closePrdTicketModal,
  formatDate,
  escapeHtml
});
