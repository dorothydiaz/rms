/**
 * STOCKS OVERVIEW MODULE - MODERN MULTI-WAREHOUSE CLIENT CONTROLLER
 * High-performance client-side filtering, multi-facility breakdown, and drill-down modal.
 */

(function () {
    'use strict';

    // Store state
    window.AppStore = {
        products: [],
        warehouses: [],
        storageAreas: [],
        categories: [],
        ledger: [],
        
        // Active Filter States
        searchQuery: '',
        warehouse: 'ALL',
        location: 'ALL',
        category: 'ALL',
        status: 'ALL',
        pageSize: 25,
        currentPage: 1,
    };

    /**
     * Initial Hydration from Blade Data
     */
    function initHydration() {
        const config = window.StocksOverviewConfig || {};
        const data = config.data || {};

        window.AppStore.products = (data.initialProducts || []).map(p => ({
            id: p.id,
            sku: p.sku,
            barcode: p.barcode || '',
            name: p.name,
            category: p.category || 'General',
            uom: p.uom || 'Unit',
            cost_price: parseFloat(p.cost_price || 0),
            selling_price: parseFloat(p.selling_price || 0),
            min_stock: parseFloat(p.min_stock || 10),
            max_stock: parseFloat(p.max_stock || 50),
            current_stock: parseFloat(p.current_stock || 0),
            inbound_po_qty: parseFloat(p.inbound_po_qty || 0),
            open_pos: p.open_pos || [],
            primary_warehouse: p.primary_warehouse || 'WH-MAIN',
            primary_location: p.primary_location || (p.storage_location || 'Stock Zone A'),
            warehouse_breakdown: p.warehouse_breakdown || {},
            storage_location: p.storage_location || 'Stock Zone A',
            production_produced: parseFloat(p.production_produced || 0),
            production_consumed: parseFloat(p.production_consumed || 0),
            production_net: parseFloat(p.production_net || 0),
            waste_total: parseFloat(p.waste_total || 0),
        }));

        window.AppStore.warehouses = data.initialWarehouses || [];
        window.AppStore.storageAreas = data.initialStorageAreas || [];
        window.AppStore.categories = data.initialCategories || [];
        window.AppStore.ledger = data.initialLedger || [];

        // Setup DOM event listeners
        setupEventListeners();

        // Initial render
        renderFilteredTable();
        updateKpis();
    }

    /**
     * DOM Event Listeners & Shortcuts
     */
    function setupEventListeners() {
        const searchInput = document.getElementById('stkSearchInput');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    window.AppStore.searchQuery = e.target.value.trim().toLowerCase();
                    window.AppStore.currentPage = 1;
                    renderFilteredTable();
                    updateKpis();
                }, 150);
            });
        }

        // Keyboard Shortcut: '/' to focus search, 'Escape' to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== searchInput) {
                e.preventDefault();
                if (searchInput) searchInput.focus();
            } else if (e.key === 'Escape') {
                closeLocationBreakdownModal();
            }
        });

        // Backdrop click to dismiss modal
        const backdrop = document.getElementById('stkLocationModalBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeLocationBreakdownModal);
        }
    }

    /**
     * Filter Helpers
     */
    window.handleWarehouseFilter = function (whId) {
        window.AppStore.warehouse = whId;
        window.AppStore.currentPage = 1;

        // Cascade storage areas filter
        const locSelect = document.getElementById('stkLocationFilter');
        if (locSelect) {
            locSelect.innerHTML = '<option value="ALL">All Storage Bins & Zones</option>';
            const areas = window.AppStore.storageAreas;
            areas.forEach(area => {
                if (whId === 'ALL' || area.warehouse_id === whId) {
                    const opt = document.createElement('option');
                    opt.value = area.name;
                    opt.textContent = `${area.warehouse_id} • ${area.name}`;
                    locSelect.appendChild(opt);
                }
            });
            window.AppStore.location = 'ALL';
            locSelect.value = 'ALL';
        }

        renderFilteredTable();
        updateKpis();
        updateActiveFilterChips();
    };

    window.handleLocationFilter = function (locName) {
        window.AppStore.location = locName;
        window.AppStore.currentPage = 1;
        renderFilteredTable();
        updateKpis();
        updateActiveFilterChips();
    };

    window.handleCategoryFilter = function (cat) {
        window.AppStore.category = cat;
        window.AppStore.currentPage = 1;
        renderFilteredTable();
        updateKpis();
        updateActiveFilterChips();
    };

    window.handleStatusFilter = function (status) {
        window.AppStore.status = status;
        window.AppStore.currentPage = 1;
        renderFilteredTable();
        updateKpis();
        updateActiveFilterChips();
    };

    window.handlePageSizeChange = function (size) {
        window.AppStore.pageSize = size === 'ALL' ? 999999 : parseInt(size, 10);
        window.AppStore.currentPage = 1;
        renderFilteredTable();
    };

    window.resetAllFilters = function () {
        window.AppStore.searchQuery = '';
        window.AppStore.warehouse = 'ALL';
        window.AppStore.location = 'ALL';
        window.AppStore.category = 'ALL';
        window.AppStore.status = 'ALL';
        window.AppStore.currentPage = 1;

        const sInput = document.getElementById('stkSearchInput');
        if (sInput) sInput.value = '';

        const whSelect = document.getElementById('stkWarehouseFilter');
        if (whSelect) whSelect.value = 'ALL';

        const catSelect = document.getElementById('stkCategoryFilter');
        if (catSelect) catSelect.value = 'ALL';

        const stSelect = document.getElementById('stkStatusFilter');
        if (stSelect) stSelect.value = 'ALL';

        handleWarehouseFilter('ALL');
    };

    /**
     * Compute Filtered Products
     */
    function getFilteredProducts() {
        const store = window.AppStore;
        const q = store.searchQuery;
        const wh = store.warehouse;
        const loc = store.location;
        const cat = store.category;
        const status = store.status;

        return store.products.filter(p => {
            // 1. Text Search
            if (q) {
                const matchSku = p.sku.toLowerCase().includes(q);
                const matchName = p.name.toLowerCase().includes(q);
                const matchBarcode = (p.barcode || '').toLowerCase().includes(q);
                const matchCat = (p.category || '').toLowerCase().includes(q);
                if (!matchSku && !matchName && !matchBarcode && !matchCat) {
                    return false;
                }
            }

            // 2. Category Filter
            if (cat !== 'ALL' && p.category !== cat) {
                return false;
            }

            // 3. Warehouse Filter
            if (wh !== 'ALL') {
                const breakdown = p.warehouse_breakdown || {};
                const whData = breakdown[wh];
                const hasStockInWh = whData && (whData.on_hand > 0 || whData.inbound_po > 0);
                const isPrimary = p.primary_warehouse === wh;
                if (!hasStockInWh && !isPrimary) {
                    return false;
                }
            }

            // 4. Storage Location Filter
            if (loc !== 'ALL') {
                let hasLocation = (p.primary_location || '').toLowerCase() === loc.toLowerCase() ||
                                  (p.storage_location || '').toLowerCase() === loc.toLowerCase();
                
                if (!hasLocation && p.warehouse_breakdown) {
                    for (const wKey in p.warehouse_breakdown) {
                        const locs = p.warehouse_breakdown[wKey].locations || [];
                        if (locs.some(l => l.location_name.toLowerCase() === loc.toLowerCase())) {
                            hasLocation = true;
                            break;
                        }
                    }
                }
                if (!hasLocation) return false;
            }

            // 5. Stock Health Status Filter
            if (status !== 'ALL') {
                const stock = p.current_stock;
                const min = p.min_stock;
                if (status === 'OUT_OF_STOCK' && stock > 0) return false;
                if (status === 'LOW_STOCK' && (stock <= 0 || stock > min)) return false;
                if (status === 'HEALTHY' && stock <= min) return false;
                if (status === 'HAS_INBOUND' && (p.inbound_po_qty || 0) <= 0) return false;
            }

            return true;
        });
    }

    /**
     * Render 6-Column Modern Table
     */
    function renderFilteredTable() {
        const tbody = document.getElementById('stocksTableTbody');
        if (!tbody) return;

        const filtered = getFilteredProducts();
        const total = filtered.length;
        const pageSize = window.AppStore.pageSize;
        const currentPage = window.AppStore.currentPage;

        const startIdx = (currentPage - 1) * pageSize;
        const pageItems = filtered.slice(startIdx, startIdx + pageSize);

        // Update counts
        const visCount = document.getElementById('visibleRowCount');
        const totCount = document.getElementById('totalRowCount');
        if (visCount) visCount.textContent = total;
        if (totCount) totCount.textContent = window.AppStore.products.length;

        if (pageItems.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="stk-empty-state">
                        <i class="ph ph-magnifying-glass"></i>
                        <p>No inventory items match the selected warehouse and filter criteria.</p>
                    </td>
                </tr>
            `;
            renderPagination(0, 1);
            return;
        }

        const selWh = window.AppStore.warehouse;

        tbody.innerHTML = pageItems.map(item => {
            let onHand = item.current_stock;
            let inboundQty = item.inbound_po_qty || 0;
            let whCode = item.primary_warehouse || 'WH-MAIN';
            let locName = item.primary_location || (item.storage_location || 'Stock Zone A');

            // If a specific warehouse is filtered, show that warehouse's specific stock
            if (selWh !== 'ALL' && item.warehouse_breakdown && item.warehouse_breakdown[selWh]) {
                const whInfo = item.warehouse_breakdown[selWh];
                onHand = whInfo.on_hand;
                inboundQty = whInfo.inbound_po;
                whCode = selWh;
                if (whInfo.locations && whInfo.locations.length > 0) {
                    locName = whInfo.locations[0].location_name;
                }
            }

            const minStock = item.min_stock || 10;
            const maxStock = item.max_stock || 50;
            const atp = Math.max(0, onHand + inboundQty);

            let statusClass = 'is-healthy';
            let statusLabel = 'In Stock';
            if (onHand <= 0) {
                statusClass = 'is-danger';
                statusLabel = 'Out of Stock';
            } else if (onHand <= minStock) {
                statusClass = 'is-warning';
                statusLabel = 'Low Stock';
            }

            const healthPct = maxStock > 0 ? Math.min(100, Math.max(5, Math.round((onHand / maxStock) * 100))) : 50;
            const whClass = whCode === 'WH-COLD' ? 'wh-cold' : (whCode === 'WH-BGC' ? 'wh-bgc' : 'wh-main');

            return `
                <tr class="stk-row" data-sku="${escapeHtml(item.sku)}">
                    <!-- Col 1: Item & Category -->
                    <td>
                        <div class="stk-item-cell">
                            <div class="stk-item-name-wrap">
                                <button type="button" class="stk-item-name-link" onclick="openLocationBreakdownModal('${escapeHtml(item.sku)}')">
                                    ${escapeHtml(item.name)}
                                </button>
                                <div class="stk-item-badges">
                                    <span class="stk-sku-pill">${escapeHtml(item.sku)}</span>
                                    <span class="stk-cat-pill">${escapeHtml(item.category)}</span>
                                    <span class="stk-uom-pill">${escapeHtml(item.uom)}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Col 2: Primary Facility & Bin -->
                    <td>
                        <div class="stk-wh-cell">
                            <div class="stk-facility-tag ${whClass}">
                                <i class="ph ph-buildings"></i>
                                <span>${escapeHtml(whCode)}</span>
                            </div>
                            <div class="stk-loc-text" title="${escapeHtml(locName)}">
                                <i class="ph ph-map-pin"></i>
                                <span>${escapeHtml(locName)}</span>
                            </div>
                        </div>
                    </td>

                    <!-- Col 3: Physical On-Hand -->
                    <td class="text-right">
                        <div class="stk-qty-cell">
                            <div class="stk-qty-val">${formatNumber(onHand, 2)} <span class="stk-qty-unit">${escapeHtml(item.uom)}</span></div>
                            <div class="stk-stock-tag ${statusClass}">
                                <span class="stk-dot"></span>
                                <span>${statusLabel}</span>
                            </div>
                        </div>
                    </td>

                    <!-- Col 4: Inbound Supply (Open POs) -->
                    <td class="text-right">
                        <div class="stk-inbound-cell">
                            ${inboundQty > 0 ? `
                                <button type="button" class="stk-inbound-badge" onclick="openLocationBreakdownModal('${escapeHtml(item.sku)}')" title="Click to view inbound PO details">
                                    <i class="ph ph-arrow-down-left"></i>
                                    <span>+${formatNumber(inboundQty, 2)}</span>
                                </button>
                                <div class="stk-atp-sub">ATP: ${formatNumber(atp, 2)}</div>
                            ` : `<span class="stk-inbound-none">—</span>`}
                        </div>
                    </td>

                    <!-- Col 5: Replenishment Health Bar -->
                    <td>
                        <div class="stk-health-cell">
                            <div class="stk-health-bar-track">
                                <div class="stk-health-bar-fill ${statusClass}" style="width: ${healthPct}%;"></div>
                            </div>
                            <div class="stk-health-limits">
                                <span>Min: ${formatNumber(minStock, 0)}</span>
                                <span>Max: ${formatNumber(maxStock, 0)}</span>
                            </div>
                        </div>
                    </td>

                    <!-- Col 6: Action -->
                    <td class="text-center">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm stk-drill-btn" onclick="openLocationBreakdownModal('${escapeHtml(item.sku)}')" title="View Facility Bins & Inbound Orders">
                            <i class="ph ph-magnifying-glass-plus"></i>
                            <span>Bins</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(total, pageSize);
    }

    /**
     * Render Pagination Controls
     */
    function renderPagination(total, pageSize) {
        const wrap = document.getElementById('stkPaginationGroup');
        if (!wrap) return;

        const totalPages = Math.ceil(total / pageSize);
        if (totalPages <= 1) {
            wrap.innerHTML = '';
            return;
        }

        const curr = window.AppStore.currentPage;
        let html = '';

        html += `<button type="button" class="stk-page-btn" ${curr <= 1 ? 'disabled' : ''} onclick="goToPage(${curr - 1})"><i class="ph ph-caret-left"></i></button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= curr - 1 && i <= curr + 1)) {
                html += `<button type="button" class="stk-page-btn ${i === curr ? 'is-active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            } else if (i === curr - 2 || i === curr + 2) {
                html += `<span style="padding: 0 4px; color: #94a3b8;">...</span>`;
            }
        }

        html += `<button type="button" class="stk-page-btn" ${curr >= totalPages ? 'disabled' : ''} onclick="goToPage(${curr + 1})"><i class="ph ph-caret-right"></i></button>`;

        wrap.innerHTML = html;
    }

    window.goToPage = function (page) {
        window.AppStore.currentPage = page;
        renderFilteredTable();
    };

    /**
     * Update KPI Summary Cards
     */
    function updateKpis() {
        const filtered = getFilteredProducts();

        let totalUnits = 0;
        let totalValuation = 0;
        let totalInbound = 0;
        let alertCount = 0;

        filtered.forEach(p => {
            const stock = p.current_stock || 0;
            const min = p.min_stock || 10;
            totalUnits += stock;
            totalValuation += stock * (p.cost_price || 0);
            totalInbound += p.inbound_po_qty || 0;

            if (stock <= min) {
                alertCount++;
            }
        });

        const kpiOnHand = document.getElementById('kpiTotalOnHand');
        if (kpiOnHand) kpiOnHand.textContent = `${formatNumber(totalUnits, 0)} units`;

        const kpiInbound = document.getElementById('kpiInboundPO');
        if (kpiInbound) kpiInbound.textContent = `+${formatNumber(totalInbound, 0)} units`;

        const kpiVal = document.getElementById('kpiTotalValuation');
        if (kpiVal) kpiVal.textContent = `₱${formatNumber(totalValuation, 2)}`;

        const kpiAlert = document.getElementById('kpiAlertCount');
        if (kpiAlert) kpiAlert.textContent = `${alertCount} Items`;
    }

    /**
     * Update Active Filter Chips
     */
    function updateActiveFilterChips() {
        const wrap = document.getElementById('activeFilterChips');
        if (!wrap) return;

        const chips = [];
        const store = window.AppStore;

        if (store.warehouse !== 'ALL') {
            chips.push(`Warehouse: ${store.warehouse}`);
        }
        if (store.location !== 'ALL') {
            chips.push(`Location: ${store.location}`);
        }
        if (store.category !== 'ALL') {
            chips.push(`Category: ${store.category}`);
        }
        if (store.status !== 'ALL') {
            chips.push(`Status: ${store.status}`);
        }

        wrap.innerHTML = chips.map(c => `
            <span class="stk-filter-chip">
                <span>${escapeHtml(c)}</span>
            </span>
        `).join('');
    }

    /**
     * Facility & Bin Drill-Down Modal Controller
     */
    window.openLocationBreakdownModal = function (sku) {
        const product = window.AppStore.products.find(p => p.sku === sku);
        if (!product) return;

        const title = document.getElementById('stkModalItemTitle');
        const skuEl = document.getElementById('stkModalSku');
        const catEl = document.getElementById('stkModalCategory');
        const uomEl = document.getElementById('stkModalUom');

        if (title) title.textContent = product.name;
        if (skuEl) skuEl.textContent = `SKU: ${product.sku}`;
        if (catEl) catEl.textContent = `Category: ${product.category}`;
        if (uomEl) uomEl.textContent = `Base UOM: ${product.uom}`;

        // 1. Warehouse Distribution Cards Grid
        const cardsWrap = document.getElementById('stkModalWhCards');
        if (cardsWrap) {
            const warehouses = window.AppStore.warehouses || [];
            const breakdown = product.warehouse_breakdown || {};

            cardsWrap.innerHTML = warehouses.map(wh => {
                const whData = breakdown[wh.id] || { on_hand: 0, inbound_po: 0, locations: [] };
                const locs = whData.locations || [];

                return `
                    <div class="stk-wh-card">
                        <div class="stk-wh-card-head">
                            <span class="stk-wh-card-code">${escapeHtml(wh.code)} — ${escapeHtml(wh.name)}</span>
                            <span class="stk-wh-card-qty">${formatNumber(whData.on_hand, 2)} ${escapeHtml(product.uom)}</span>
                        </div>
                        ${whData.inbound_po > 0 ? `
                            <div class="stk-wh-card-inbound">
                                <i class="ph ph-truck"></i> +${formatNumber(whData.inbound_po, 2)} Inbound from Open POs
                            </div>
                        ` : ''}
                        <div class="stk-wh-card-locs">
                            ${locs.length > 0 ? locs.map(l => `
                                <div class="stk-wh-card-loc-row">
                                    <span><i class="ph ph-map-pin"></i> ${escapeHtml(l.location_name)}</span>
                                    <strong>${formatNumber(l.qty, 2)}</strong>
                                </div>
                            `).join('') : `
                                <div class="stk-wh-card-loc-row" style="color: #94a3b8;">
                                    <span>No allocated bin stock</span>
                                    <span>0.00</span>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 2. Open Purchase Orders Pipeline
        const poTbody = document.getElementById('stkModalOpenPoTbody');
        if (poTbody) {
            const openPos = product.open_pos || [];
            if (openPos.length === 0) {
                poTbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 14px;">
                            No active inbound purchase orders scheduled for this item.
                        </td>
                    </tr>
                `;
            } else {
                poTbody.innerHTML = openPos.map(po => `
                    <tr>
                        <td style="font-weight: 600; color: #0284c7;">${escapeHtml(po.po_number)}</td>
                        <td>${escapeHtml(po.vendor_name || '—')}</td>
                        <td><span class="stk-sku-pill">${escapeHtml(po.warehouse_id || 'WH-MAIN')}</span></td>
                        <td>${escapeHtml(po.expected_delivery || 'Pending')}</td>
                        <td style="text-align: right; font-weight: 700; color: #059669;">
                            +${formatNumber(po.pending_qty, 2)} ${escapeHtml(product.uom)}
                        </td>
                    </tr>
                `).join('');
            }
        }

        // 3. Recent Master Ledger Movements
        const ledgerWrap = document.getElementById('stkModalLedgerList');
        if (ledgerWrap) {
            const ledger = (window.AppStore.ledger || []).filter(l => l.sku === product.sku).slice(0, 8);
            if (ledger.length === 0) {
                ledgerWrap.innerHTML = `
                    <div style="text-align: center; color: #94a3b8; font-size: 0.75rem; padding: 10px;">
                        No recent ledger movement entries recorded for this SKU.
                    </div>
                `;
            } else {
                ledgerWrap.innerHTML = ledger.map(l => {
                    const isPositive = (l.quantity_change || 0) >= 0;
                    return `
                        <div class="stk-timeline-item">
                            <div class="stk-timeline-left">
                                <span class="stk-timeline-tag ${isPositive ? 'in' : 'out'}">
                                    ${escapeHtml(l.transaction_type)}
                                </span>
                                <span>${escapeHtml(l.storage_location || 'Warehouse')}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong style="color: ${isPositive ? '#15803d' : '#b91c1c'}; font-variant-numeric: tabular-nums;">
                                    ${isPositive ? '+' : ''}${formatNumber(l.quantity_change, 2)}
                                </strong>
                                <span style="color: #94a3b8; font-size: 0.68rem;">${escapeHtml(l.performed_by || 'System')}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        // Show Modal
        const backdrop = document.getElementById('stkLocationModalBackdrop');
        const modal = document.getElementById('stkLocationModal');
        if (backdrop) backdrop.style.display = 'block';
        if (modal) modal.style.display = 'block';
    };

    window.closeLocationBreakdownModal = function () {
        const backdrop = document.getElementById('stkLocationModalBackdrop');
        const modal = document.getElementById('stkLocationModal');
        if (backdrop) backdrop.style.display = 'none';
        if (modal) modal.style.display = 'none';
    };

    /**
     * Refresh Stocks from Backend API
     */
    window.refreshStocksData = async function () {
        const url = window.StocksOverviewConfig?.routes?.stocksOverviewData;
        if (!url) {
            window.location.reload();
            return;
        }

        try {
            const resp = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await resp.json();
            if (json.success && json.data) {
                window.StocksOverviewConfig.data = {
                    initialProducts: json.data.products,
                    initialCategories: json.data.categories,
                    initialLedger: json.data.stocksLedger,
                    initialWarehouses: json.data.warehouses,
                    initialStorageAreas: json.data.storageAreas,
                };
                initHydration();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Inventory Synchronized',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            }
        } catch (err) {
            console.error('Failed to sync inventory data:', err);
            window.location.reload();
        }
    };

    /**
     * Export to CSV
     */
    window.exportStocksCSV = function () {
        const filtered = getFilteredProducts();
        if (filtered.length === 0) return;

        const headers = ['SKU', 'Item Name', 'Category', 'Base UOM', 'Primary Warehouse', 'Storage Location', 'Physical On-Hand', 'Inbound PO Qty', 'Min Stock', 'Max Stock', 'Cost Price'];
        const rows = filtered.map(p => [
            `"${p.sku}"`,
            `"${p.name.replace(/"/g, '""')}"`,
            `"${p.category}"`,
            `"${p.uom}"`,
            `"${p.primary_warehouse}"`,
            `"${(p.primary_location || '').replace(/"/g, '""')}"`,
            p.current_stock,
            p.inbound_po_qty,
            p.min_stock,
            p.max_stock,
            p.cost_price
        ]);

        const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `inventory_stocks_overview_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Number Formatting Helper
    function formatNumber(val, decimals) {
        const num = parseFloat(val) || 0;
        return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // HTML Escaping Helper
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHydration);
    } else {
        initHydration();
    }

})();
