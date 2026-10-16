(function() {
    'use strict';

    // =========================================================================
    // DEFAULT DATA & FALLBACK REPOSITORY SEED
    // =========================================================================
    const DEFAULT_CATEGORIES = [
        'Beverages',
        'Food & Snacks',
        'Raw Ingredients',
        'Packaging & Disposables',
        'Syrups & Flavors',
        'Pastries & Desserts',
        'Main Course'
    ];

    const FALLBACK_PRODUCTS = [
        { id: 1, sku: 'BEV-001', barcode: '4800019283710', name: 'Signature Spanish Latte', category: 'Beverages', subcategory: 'Espresso Specialty', unit: 'Cup', packSize: '16 oz', costPrice: 45.00, sellingPrice: 150.00, reorderPoint: 20, targetStock: 80, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 2, sku: 'BEV-002', barcode: '4800028391028', name: 'Iced Americano Grande', category: 'Beverages', subcategory: 'Iced Coffee', unit: 'Cup', packSize: '20 oz', costPrice: 28.00, sellingPrice: 120.00, reorderPoint: 15, targetStock: 60, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 3, sku: 'BEV-003', barcode: '4800039281039', name: 'Matcha Green Tea Fusion', category: 'Beverages', subcategory: 'Tea Lattes', unit: 'Cup', packSize: '16 oz', costPrice: 52.00, sellingPrice: 175.00, reorderPoint: 15, targetStock: 50, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 4, sku: 'MNC-101', barcode: '4800049281048', name: 'Truffle Mushroom Pasta', category: 'Main Course', subcategory: 'Artisan Pastas', unit: 'Plate', packSize: '320g Plate', costPrice: 115.00, sellingPrice: 380.00, reorderPoint: 10, targetStock: 40, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 7, sku: 'PST-202', barcode: '4800037190029', name: 'French Butter Croissant', category: 'Pastries & Desserts', subcategory: 'Viennoiserie', unit: 'Piece', packSize: '85g Piece', costPrice: 32.00, sellingPrice: 110.00, reorderPoint: 20, targetStock: 60, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 10, sku: 'RAW-COFFEE-01', barcode: '4800058291028', name: 'Espresso Roast Beans (1kg)', category: 'Raw Ingredients', subcategory: 'Coffee Beans', unit: 'Kg', packSize: '1000g Bag', costPrice: 650.00, sellingPrice: 0.00, reorderPoint: 10, targetStock: 50, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 11, sku: 'PKG-501', barcode: '4800067192039', name: 'Kraft Takeout Box (Medium)', category: 'Packaging & Disposables', subcategory: 'Takeout Containers', unit: 'Piece', packSize: 'Pack of 50', costPrice: 8.50, sellingPrice: 15.00, reorderPoint: 100, targetStock: 500, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 12, sku: 'SYR-601', barcode: '4800078291039', name: 'Caramel Macchiato Artisan Syrup', category: 'Syrups & Flavors', subcategory: 'Flavor Syrups', unit: 'Bottle', packSize: '750ml Bottle', costPrice: 290.00, sellingPrice: 420.00, reorderPoint: 8, targetStock: 25, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true }
    ];

    // =========================================================================
    // STATE STORE INITIALIZATION
    // =========================================================================
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
    
    function formatDateInput(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    const CATEGORY_PALETTES = {
        purple: {
            label: 'Purple / Violet',
            bg: '#faf5ff',
            text: '#7e22ce',
            border: '#e9d5ff',
            dot: '#a855f7'
        },
        amber: {
            label: 'Amber / Gold',
            bg: '#fffbeb',
            text: '#b45309',
            border: '#fde68a',
            dot: '#f59e0b'
        },
        rose: {
            label: 'Rose / Pink',
            bg: '#fdf2f8',
            text: '#be185d',
            border: '#fbcfe8',
            dot: '#ec4899'
        },
        emerald: {
            label: 'Emerald / Green',
            bg: '#f0fdf4',
            text: '#15803d',
            border: '#bbf7d0',
            dot: '#10b981'
        },
        blue: {
            label: 'Ocean / Blue',
            bg: '#eff6ff',
            text: '#1d4ed8',
            border: '#bfdbfe',
            dot: '#3b82f6'
        },
        orange: {
            label: 'Tangerine / Orange',
            bg: '#fff7ed',
            text: '#c2410c',
            border: '#fed7aa',
            dot: '#f97316'
        },
        teal: {
            label: 'Teal / Cyan',
            bg: '#f0fdfa',
            text: '#0f766e',
            border: '#99f6e4',
            dot: '#14b8a6'
        },
        indigo: {
            label: 'Indigo / Navy',
            bg: '#eef2ff',
            text: '#4338ca',
            border: '#c7d2fe',
            dot: '#6366f1'
        },
        slate: {
            label: 'Slate / Neutral',
            bg: '#f8fafc',
            text: '#475569',
            border: '#cbd5e1',
            dot: '#64748b'
        }
    };

    const DEFAULT_CATEGORY_META = {
        'Beverages': { icon: 'ph-coffee', color: 'purple' },
        'Main Course': { icon: 'ph-fork-knife', color: 'amber' },
        'Pastries & Desserts': { icon: 'ph-cookie', color: 'rose' },
        'Raw Ingredients': { icon: 'ph-plant', color: 'emerald' },
        'Packaging & Disposables': { icon: 'ph-box', color: 'blue' },
        'Syrups & Flavors': { icon: 'ph-drop', color: 'orange' },
        'Uncategorized': { icon: 'ph-tag', color: 'slate' }
    };

        const serverProducts = window.StocksOverviewConfig?.data?.initialProducts || [];
        const serverCategories = window.StocksOverviewConfig?.data?.initialCategories || [];
        const serverLedger = window.StocksOverviewConfig?.data?.initialLedger || [];

    let initialProductsList = FALLBACK_PRODUCTS;
    if (serverProducts && serverProducts.length > 0) {
        initialProductsList = serverProducts.map(p => ({
            id: p.id,
            sku: p.sku,
            barcode: p.barcode || ('4800' + p.id),
            name: p.name,
            category: p.category,
            subcategory: p.category,
            unit: p.uom || 'Unit',
            packSize: '1 ' + (p.uom || 'Unit'),
            costPrice: parseFloat(p.cost_price || 0),
            sellingPrice: parseFloat(p.selling_price || 0),
            reorderPoint: parseFloat(p.min_stock || 10),
            targetStock: parseFloat(p.max_stock || 50),
            currentStock: parseFloat(p.current_stock || 0),
            current_stock: parseFloat(p.current_stock || 0),
            production_produced: parseFloat(p.production_produced || 0),
            production_consumed: parseFloat(p.production_consumed || 0),
            production_net: parseFloat(p.production_net || 0),
            waste_total: parseFloat(p.waste_total || 0),
            waste_expired: parseFloat(p.waste_expired || 0),
            waste_damaged: parseFloat(p.waste_damaged || 0),
            waste_cost: parseFloat(p.waste_cost || 0),
            trackPhysicalStock: true,
            explodeBomOnSale: false
        }));
    } else {
        const stored = localStorage.getItem('rms_inventory_products');
        if (stored) {
            try { 
                const parsed = JSON.parse(stored);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    initialProductsList = parsed;
                }
            } catch(e) {}
        }
    }

    if (!initialProductsList || initialProductsList.length === 0) {
        initialProductsList = FALLBACK_PRODUCTS;
    }

    let initialCategoriesList = DEFAULT_CATEGORIES;
    if (serverCategories && serverCategories.length > 0) {
        initialCategoriesList = serverCategories.map(c => c.name);
    } else {
        const storedCat = localStorage.getItem('rms_product_categories');
        if (storedCat) {
            try { 
                const parsedCat = JSON.parse(storedCat);
                if (Array.isArray(parsedCat) && parsedCat.length > 0) {
                    initialCategoriesList = parsedCat;
                }
            } catch(e) {}
        }
    }

    if (!initialCategoriesList || initialCategoriesList.length === 0) {
        initialCategoriesList = DEFAULT_CATEGORIES;
    }

    window.AppStore = {
        products: initialProductsList,
        categories: initialCategoriesList,
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        stocksLedger: null,
        serverLedger: serverLedger || [],
        
        // Filter States
        searchQuery: '',
        datePreset: 'this_month',
        startDate: formatDateInput(firstDayOfMonth),
        endDate: formatDateInput(now),
        category: 'ALL',
        branch: 'ALL',
        status: 'ALL',
        
        // Pagination
        currentPage: 1,
        pageSize: 25,
        selectedProductId: null
    };

    localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
    localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));

    // =========================================================================
    // MODULAR CATEGORY & ICON HELPERS
    // =========================================================================
    function getCategoryMeta(cat) {
        if (window.AppStore && window.AppStore.categoryMeta && window.AppStore.categoryMeta[cat]) {
            return window.AppStore.categoryMeta[cat];
        }
        if (DEFAULT_CATEGORY_META[cat]) {
            return DEFAULT_CATEGORY_META[cat];
        }
        return { icon: 'ph-tag', color: 'purple' };
    }

    function getCategoryPalette(colorKey) {
        return CATEGORY_PALETTES[colorKey] || CATEGORY_PALETTES.purple;
    }

    function getCatSlug(cat) {
        if (!cat) return 'general';
        return cat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }

    function getItemIcon(cat) {
        return getCategoryMeta(cat).icon;
    }

    // =========================================================================
    // SYNTHETIC REALISTIC OPERATIONS MOVEMENTS GENERATOR
    // =========================================================================
    function getOrGenerateStocksData() {
        if (window.AppStore.stocksLedger && Array.isArray(window.AppStore.stocksLedger) && window.AppStore.stocksLedger.length > 0) {
            return window.AppStore.stocksLedger;
        }

        const ledgerItems = window.AppStore.serverLedger || [];

        const seeded = window.AppStore.products.map((p, idx) => {
            const seed = (p.id * 17 + idx * 3) % 100;
            const isHighVolume = p.category === 'Beverages' || p.category === 'Packaging & Disposables';
            
            // True live on-hand physical stock directly from Database InventoryItem model!
            const onHand = parseFloat(p.current_stock !== undefined ? p.current_stock : (p.currentStock !== undefined ? p.currentStock : (15 + (seed % 30))));
            const shelfStock = Math.floor(onHand * 0.7);
            const backroomStock = onHand - shelfStock;

            // Find real ledger records from Database StockLedger table for this SKU
            const matchingLedger = ledgerItems.filter(l => l.sku === p.sku);
            let movements = [];

            if (matchingLedger.length > 0) {
                movements = matchingLedger.map(l => {
                    const dStr = l.created_at ? String(l.created_at).slice(0, 10) : '';
                    const change = parseFloat(l.quantity_change) || 0;
                    let typeLabel = 'Stock Ledger Movement';
                    if (l.transaction_type === 'PURCHASE_RECEIPT') typeLabel = 'Stock In (Purchase Order)';
                    else if (l.transaction_type === 'DIRECT_RECEIVING') typeLabel = 'Stock In (Direct Receiving)';
                    else if (l.transaction_type === 'STOCK_IN') typeLabel = 'Stock In (Inbound)';
                    else if (l.transaction_type === 'STOCK_OUT') typeLabel = 'Stock Out (POS/Usage)';

                    return {
                        date: formatShortDate(dStr) || 'Recent',
                        ref: l.reference_no,
                        type: typeLabel,
                        qty: (change >= 0 ? `+${change}` : `${change}`),
                        balance: parseFloat(l.after_quantity) || onHand,
                        note: l.notes || `Ledger Voucher #${l.reference_no}`
                    };
                });
            } else {
                movements = [
                    { date: 'Recent', ref: 'BEG-BAL', type: 'Beginning Balance', qty: `+${onHand}`, balance: onHand, note: 'Opening verified inventory ledger' }
                ];
            }

            const ledgerStockIn = matchingLedger.filter(l => (parseFloat(l.quantity_change) || 0) > 0).reduce((sum, l) => sum + (parseFloat(l.quantity_change) || 0), 0);
            const ledgerStockOut = matchingLedger.filter(l => (parseFloat(l.quantity_change) || 0) < 0).reduce((sum, l) => sum + Math.abs(parseFloat(l.quantity_change) || 0), 0);
            const totalReceivedFromLedger = ledgerStockIn;

            return {
                id: p.id,
                sku: p.sku,
                barcode: p.barcode || ('4800' + Math.floor(100000000 + Math.random() * 900000000)),
                name: p.name,
                category: p.category,
                subcategory: p.subcategory || p.category || 'Standard Catalog',
                unit: p.unit || p.uom || 'Piece',
                packSize: p.packSize || ('1 ' + (p.unit || p.uom || 'pc')),
                costPrice: Number(p.costPrice || p.cost_price || 0),
                sellingPrice: Number(p.sellingPrice || p.selling_price || 0),
                reorderPoint: Number(p.reorderPoint || p.min_stock || 10),
                targetStock: Number(p.targetStock || p.max_stock || 50),
                trackPhysicalStock: p.trackPhysicalStock !== undefined ? p.trackPhysicalStock : true,
                explodeBomOnSale: p.explodeBomOnSale !== undefined ? p.explodeBomOnSale : false,
                
                begQty: onHand,
                stockIn: {
                    total: ledgerStockIn > 0 ? ledgerStockIn : (isHighVolume ? 40 : 15),
                    po: ledgerStockIn > 0 ? ledgerStockIn : (isHighVolume ? 30 : 10),
                    transfer: 0
                },
                stockOut: {
                    total: ledgerStockOut,
                    pos: 0,
                    recipe: 0
                },
                waste: {
                    total: Number(p.waste_total || 0),
                    damaged: Number(p.waste_damaged || 0),
                    expired: Number(p.waste_expired || 0)
                },
                adjustment: {
                    qty: 0,
                    reason: 'Physical Count Matched',
                    variancePct: '0.0%'
                },
                production: {
                    in: Number(p.production_produced || 0),
                    out: Number(p.production_consumed || 0),
                    net: Number(p.production_net || 0)
                },
                onHand: onHand,
                shelfStock: shelfStock,
                backroomStock: backroomStock,
                inTransit: {
                    incoming: 0,
                    outgoing: 0
                },
                projectedTotal: onHand,
                movements: movements
            };
        });

        window.AppStore.stocksLedger = seeded;
        localStorage.setItem('rms_stocks_ledger', JSON.stringify(seeded));
        return seeded;
    }

    // =========================================================================
    // FORMATTING HELPERS
    // =========================================================================
    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    function formatShortDate(dStr) {
        if (!dStr) return '';
        const parts = dStr.split('-');
        if (parts.length < 3) return dStr;
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const m = months[parseInt(parts[1], 10) - 1] || parts[1];
        return `${m} ${parts[2]}`;
    }

    function updateDateRangeDisplayLabel() {
        const displayEl = document.getElementById('stkDateRangeDisplay');
        if (!displayEl) return;

        const presetTitles = {
            today: 'Today',
            yesterday: 'Yesterday',
            last_7_days: 'Last 7 Days',
            this_month: 'This Month',
            last_30_days: 'Last 30 Days',
            this_quarter: 'This Quarter',
            custom: 'Custom Range'
        };

        const title = presetTitles[window.AppStore.datePreset] || 'Custom Range';
        const sFormatted = formatShortDate(window.AppStore.startDate);
        const eFormatted = formatShortDate(window.AppStore.endDate);

        displayEl.textContent = `${title} (${sFormatted} - ${eFormatted}, ${currentYear})`;
    }

    // =========================================================================
    // FILTERING ENGINE
    // =========================================================================
    function getFilteredStocks() {
        const all = getOrGenerateStocksData();
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const cat = window.AppStore.category;
        const status = window.AppStore.status;

        return all.filter(item => {
            // Must track physical stock to be shown in Stocks Overview for counting/receiving
            if (item.trackPhysicalStock === false || item.trackStock === false) {
                return false;
            }

            // Search Query
            if (query) {
                const matchName = item.name.toLowerCase().includes(query);
                const matchSku = item.sku.toLowerCase().includes(query);
                const matchBarcode = (item.barcode || '').toLowerCase().includes(query);
                const matchCat = item.category.toLowerCase().includes(query);
                if (!matchName && !matchSku && !matchBarcode && !matchCat) return false;
            }

            // Category Filter
            if (cat !== 'ALL' && item.category !== cat) {
                return false;
            }

            // Stock Status Filter
            if (status === 'LOW_STOCK') {
                if (item.onHand > item.reorderPoint || item.onHand <= 0) return false;
            } else if (status === 'OUT_OF_STOCK') {
                if (item.onHand > 0) return false;
            } else if (status === 'HEALTHY') {
                if (item.onHand <= item.reorderPoint) return false;
            } else if (status === 'HAS_TRANSIT') {
                if (item.inTransit.incoming === 0 && item.inTransit.outgoing === 0) return false;
            }

            return true;
        });
    }

    // =========================================================================
    // RENDERING: KPI METRICS BAR
    // =========================================================================
    function renderKpis(data) {
        let totalOnHand = 0;
        let totalValuation = 0;
        let totalIn = 0;
        let totalOut = 0;
        let totalIncTransit = 0;
        let totalOutTransit = 0;
        let lowStockCount = 0;

        data.forEach(item => {
            totalOnHand += item.onHand;
            totalValuation += (item.onHand * item.costPrice);
            totalIn += item.stockIn.total;
            totalOut += (item.stockOut.total + item.waste.total);
            totalIncTransit += item.inTransit.incoming;
            totalOutTransit += item.inTransit.outgoing;
            if (item.onHand <= item.reorderPoint) {
                lowStockCount++;
            }
        });

        const netTransit = totalIncTransit - totalOutTransit;

        document.getElementById('kpiTotalOnHand').textContent = `${totalOnHand.toLocaleString()} units`;
        document.getElementById('kpiOnHandValuation').textContent = `Valuation: ${formatPHP(totalValuation)}`;

        document.getElementById('kpiTotalStockIn').textContent = `+${totalIn.toLocaleString()} units`;
        document.getElementById('kpiTotalStockOut').textContent = `-${totalOut.toLocaleString()} units`;

        document.getElementById('kpiTotalInTransit').textContent = `${netTransit >= 0 ? '+' : ''}${netTransit.toLocaleString()} net`;
        document.getElementById('kpiInTransitBreakdown').textContent = `+${totalIncTransit} In • -${totalOutTransit} Out`;

        document.getElementById('kpiLowStockCount').textContent = `${lowStockCount} Items`;
        document.getElementById('kpiLowStockSub').textContent = lowStockCount > 0 ? 'Requires Purchase Order / Transfer' : 'All items above safety stock';
    }

    // =========================================================================
    // RENDERING: 10-COLUMN DATA TABLE WITH ADVANCED DESIGNS
    // =========================================================================
    function renderTable() {
        const filtered = getFilteredStocks();
        const tbody = document.getElementById('stocksTableTbody');
        const counterEl = document.getElementById('visibleRowCount');
        const totalEl = document.getElementById('totalRowCount');

        renderKpis(filtered);
        renderFilterChips(filtered.length);
        updateDateRangeDisplayLabel();

        const physicalCatalogCount = window.AppStore.products.filter(p => p.trackPhysicalStock !== false && p.trackStock !== false).length;
        if (counterEl) counterEl.textContent = filtered.length;
        if (totalEl) totalEl.textContent = physicalCatalogCount || window.AppStore.products.length;

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10">
                        <div class="stk-empty-state">
                            <i class="ph ph-magnifying-glass"></i>
                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--stk-text-strong);">No Inventory Items Found</h4>
                            <p style="margin: 0; font-size: 0.8rem; max-width: 380px;">No stocks match your active search or filter criteria. Try resetting the filters or modifying date intervals.</p>
                            <button type="button" class="stk-btn-secondary" onclick="resetAllFilters()" style="margin-top: 8px;">
                                <i class="ph ph-arrow-counter-clockwise"></i> Clear Filters
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            updateTableTotals([]);
            renderPagination(0);
            return;
        }

        const pageSize = window.AppStore.pageSize === 'ALL' ? filtered.length : Number(window.AppStore.pageSize);
        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (window.AppStore.currentPage > totalPages) {
            window.AppStore.currentPage = 1;
        }

        const startIndex = (window.AppStore.currentPage - 1) * pageSize;
        const pageItems = window.AppStore.pageSize === 'ALL' ? filtered : filtered.slice(startIndex, startIndex + pageSize);

        let rowsHtml = '';
        pageItems.forEach(item => {
            const catMeta = getCategoryMeta(item.category);
            const catPal = getCategoryPalette(catMeta.color);
            const iconGlyph = catMeta.icon || 'ph-tag';

            // Status tag logic
            let statusTagClass = 'is-healthy';
            let statusTagText = 'Healthy';
            if (item.onHand <= 0) {
                statusTagClass = 'is-out';
                statusTagText = 'Out of Stock';
            } else if (item.onHand <= item.reorderPoint) {
                statusTagClass = 'is-low';
                statusTagText = 'Low Stock';
            }

            // Adjustments formatting
            let adjSign = '';
            if (item.adjustment.qty > 0) adjSign = '+';

            // In-transit display (Both in 1 column!)
            const incText = item.inTransit.incoming > 0 
                ? `<span class="stk-pipe-capsule in"><i class="ph ph-arrow-down-left"></i> +${item.inTransit.incoming} Incoming</span>` 
                : `<span class="stk-pipe-capsule none">&mdash; No Incoming</span>`;
            
            const outText = item.inTransit.outgoing > 0 
                ? `<span class="stk-pipe-capsule out"><i class="ph ph-arrow-up-right"></i> -${item.inTransit.outgoing} Outgoing</span>` 
                : `<span class="stk-pipe-capsule none">&mdash; No Outgoing</span>`;

            const netTransit = item.inTransit.incoming - item.inTransit.outgoing;

            rowsHtml += `
                <tr onclick="openItemDetailDrawer(${item.id})">
                    <!-- Col 1: Item / SKU (Avatar Icon + Item Name + SKU Chip) -->
                    <td>
                        <div class="stk-item-cell">
                            <div class="stk-item-icon-box" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border};">
                                <i class="ph ${iconGlyph}"></i>
                            </div>
                            <div class="stk-cell-stack">
                                <span class="stk-primary-text font-bold" style="font-weight: 700;">${item.name}</span>
                                <div class="stk-sku-tag-wrap">
                                    <span class="stk-sku-pill">${item.sku}</span>
                                    <span class="stk-barcode-sub">${item.barcode}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Col 2: Category (Pill + Subcategory Breadcrumb) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-category-pill" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border}; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="ph ${iconGlyph}" style="font-size: 11px;"></i>
                                <span>${item.category}</span>
                            </span>
                            <span class="stk-sub-text"><span class="stk-subcat-bullet">&rsaquo;</span> ${item.subcategory}</span>
                        </div>
                    </td>

                    <!-- Col 3: Beginning -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-primary-text stk-val-tabular font-bold">${item.begQty.toLocaleString()}</span>
                            <span class="stk-uom-pill">${item.unit} (${item.packSize})</span>
                        </div>
                    </td>

                    <!-- Col 4: Stock In (+) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill pill-inflow">+${item.stockIn.total.toLocaleString()}</span>
                            <span class="stk-sub-text">PO: ${item.stockIn.po} &bull; Trf: ${item.stockIn.transfer}</span>
                        </div>
                    </td>

                    <!-- Col 5: Stock Out (-) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill pill-outflow">-${item.stockOut.total.toLocaleString()}</span>
                            <span class="stk-sub-text">POS: ${item.stockOut.pos} &bull; Recipe: ${item.stockOut.recipe}</span>
                        </div>
                    </td>

                    <!-- Col 6: Waste / Loss (-) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill ${item.waste.total > 0 ? 'pill-waste' : 'pill-neutral'}">${item.waste.total > 0 ? '-' + item.waste.total.toLocaleString() : '0'}</span>
                            <span class="stk-sub-text">Dmg: ${item.waste.damaged} &bull; Exp: ${item.waste.expired}</span>
                        </div>
                    </td>

                    <!-- Col 7: Adjustments (±) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill ${item.adjustment.qty > 0 ? 'pill-inflow' : (item.adjustment.qty < 0 ? 'pill-waste' : 'pill-neutral')}">${adjSign}${item.adjustment.qty.toLocaleString()}</span>
                            <span class="stk-sub-text">${item.adjustment.reason} &bull; ${item.adjustment.variancePct}</span>
                        </div>
                    </td>

                    <!-- Col 8: Production (±) -->
                    <td>
                        <div class="stk-cell-stack">
                            ${(() => {
                                const pIn = Number(item.production?.in || 0);
                                const pOut = Number(item.production?.out || 0);
                                if (pIn > 0) {
                                    return `
                                        <span class="stk-badge-pill pill-inflow" title="Manufactured finished goods batch yield">+${pIn.toLocaleString()}</span>
                                        <span class="stk-sub-text" style="color: #059669; font-weight: 600;">Yield (+)</span>
                                    `;
                                } else if (pOut > 0) {
                                    return `
                                        <span class="stk-badge-pill pill-outflow" title="Raw materials consumed in production batch">-${pOut.toLocaleString()}</span>
                                        <span class="stk-sub-text" style="color: #d97706; font-weight: 600;">Consumed (-)</span>
                                    `;
                                } else {
                                    return `
                                        <span class="stk-badge-pill pill-neutral">0</span>
                                        <span class="stk-sub-text">No batches</span>
                                    `;
                                }
                            })()}
                        </div>
                    </td>

                    <!-- Col 8: Physical On-Hand -->
                    <td>
                        <div class="stk-cell-stack">
                            <div class="stk-onhand-row">
                                <span class="stk-onhand-number">${item.onHand.toLocaleString()}</span>
                                <span class="stk-status-pill ${statusTagClass}">
                                    <span class="stk-status-dot"></span>
                                    <span>${statusTagText}</span>
                                </span>
                            </div>
                            <span class="stk-sub-text">Shelf: ${item.shelfStock} &bull; Back: ${item.backroomStock}</span>
                        </div>
                    </td>

                    <!-- Col 9: In-Transit Pipeline (Both in 1 column!) -->
                    <td>
                        <div class="stk-pipeline-capsule-wrap">
                            ${incText}
                            ${outText}
                        </div>
                    </td>

                    <!-- Col 10: Projected Total (Purple Highlighted Cell) -->
                    <td class="stk-projected-cell">
                        <div class="stk-cell-stack">
                            <span class="stk-projected-number">${item.projectedTotal.toLocaleString()} <small>${item.unit}</small></span>
                            <span class="stk-sub-text">On-Hand (${item.onHand}) ${netTransit >= 0 ? '+' : ''}${netTransit} Transit</span>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = rowsHtml;
        updateTableTotals(filtered);
        renderPagination(filtered.length);
    }

    // =========================================================================
    // TABLE TOTALS SUMMARY (TFOOT)
    // =========================================================================
    function updateTableTotals(data) {
        let beg = 0, inQty = 0, outQty = 0, wasteQty = 0, adjQty = 0, onHand = 0, inTransitInc = 0, inTransitOut = 0, proj = 0;

        data.forEach(d => {
            beg += d.begQty;
            inQty += d.stockIn.total;
            outQty += d.stockOut.total;
            wasteQty += d.waste.total;
            adjQty += d.adjustment.qty;
            onHand += d.onHand;
            inTransitInc += d.inTransit.incoming;
            inTransitOut += d.inTransit.outgoing;
            proj += d.projectedTotal;
        });

        const netTransit = inTransitInc - inTransitOut;

        document.getElementById('footBegQty').textContent = beg.toLocaleString();
        document.getElementById('footStockIn').textContent = `+${inQty.toLocaleString()}`;
        document.getElementById('footStockOut').textContent = `-${outQty.toLocaleString()}`;
        document.getElementById('footWaste').textContent = wasteQty > 0 ? `-${wasteQty.toLocaleString()}` : '0';
        document.getElementById('footAdj').textContent = (adjQty > 0 ? `+${adjQty.toLocaleString()}` : adjQty.toLocaleString());
        document.getElementById('footOnHand').textContent = onHand.toLocaleString();
        document.getElementById('footInTransit').textContent = `${netTransit >= 0 ? '+' : ''}${netTransit.toLocaleString()} net`;
        document.getElementById('footProjected').textContent = proj.toLocaleString();
    }

    // =========================================================================
    // PAGINATION CONTROLS
    // =========================================================================
    function renderPagination(totalCount) {
        const group = document.getElementById('stkPaginationGroup');
        if (!group) return;

        if (window.AppStore.pageSize === 'ALL' || totalCount === 0) {
            group.innerHTML = '';
            return;
        }

        const pageSize = Number(window.AppStore.pageSize);
        const totalPages = Math.ceil(totalCount / pageSize);
        const current = window.AppStore.currentPage;

        let html = `
            <button type="button" class="stk-page-btn" onclick="goToPage(${current - 1})" ${current <= 1 ? 'disabled' : ''} title="Previous Page">
                <i class="ph ph-caret-left"></i>
            </button>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - 1 && i <= current + 1)) {
                html += `
                    <button type="button" class="stk-page-btn ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>
                `;
            } else if (i === current - 2 || i === current + 2) {
                html += `<span style="padding: 0 4px; color: var(--stk-text-subtle);">&bull;&bull;&bull;</span>`;
            }
        }

        html += `
            <button type="button" class="stk-page-btn" onclick="goToPage(${current + 1})" ${current >= totalPages ? 'disabled' : ''} title="Next Page">
                <i class="ph ph-caret-right"></i>
            </button>
        `;

        group.innerHTML = html;
    }

    window.goToPage = function(p) {
        window.AppStore.currentPage = p;
        renderTable();
    };

    window.handlePageSizeChange = function(sz) {
        window.AppStore.pageSize = sz;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    // =========================================================================
    // ACTIVE FILTER CHIPS
    // =========================================================================
    function renderFilterChips(matchCount) {
        const container = document.getElementById('activeFilterChips');
        if (!container) return;

        let chips = [];

        // Date Period Chip
        chips.push(`
            <span class="stk-chip">
                <span>Period: <strong>${formatShortDate(window.AppStore.startDate)} &rarr; ${formatShortDate(window.AppStore.endDate)}, ${currentYear}</strong></span>
            </span>
        `);

        // Search Chip
        if (window.AppStore.searchQuery) {
            chips.push(`
                <span class="stk-chip">
                    <span>Search: <strong>"${window.AppStore.searchQuery}"</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="clearSearchFilter()"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Category Chip
        if (window.AppStore.category !== 'ALL') {
            chips.push(`
                <span class="stk-chip">
                    <span>Category: <strong>${window.AppStore.category}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleCategoryFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Branch Chip
        if (window.AppStore.branch !== 'ALL') {
            chips.push(`
                <span class="stk-chip">
                    <span>Branch: <strong>${window.AppStore.branch}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleBranchFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Status Chip
        if (window.AppStore.status !== 'ALL') {
            const labels = {
                LOW_STOCK: 'Low Stock Alerts',
                OUT_OF_STOCK: 'Out of Stock',
                HEALTHY: 'Healthy Levels',
                HAS_TRANSIT: 'Active In-Transit'
            };
            chips.push(`
                <span class="stk-chip">
                    <span>Status: <strong>${labels[window.AppStore.status]}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleStatusFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        container.innerHTML = chips.join('');
    }

    // =========================================================================
    // UNIFIED SINGLE DATE RANGE PICKER CONTROLS
    // =========================================================================
    window.toggleDateRangePopover = function(e) {
        if (e) e.stopPropagation();
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        const isOpen = popover.classList.contains('is-open');

        if (isOpen) {
            closeDateRangePopover();
        } else {
            popover.classList.add('is-open');
            trigger.classList.add('is-active');
            trigger.setAttribute('aria-expanded', 'true');
        }
    };

    window.closeDateRangePopover = function() {
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        if (popover) popover.classList.remove('is-open');
        if (trigger) {
            trigger.classList.remove('is-active');
            trigger.setAttribute('aria-expanded', 'false');
        }
    };

    window.selectDatePreset = function(preset) {
        document.querySelectorAll('.stk-popover-preset-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-preset') === preset);
        });

        window.AppStore.datePreset = preset;
        const today = new Date();
        let s = new Date();
        let e = new Date();

        if (preset === 'today') {
            s = today;
            e = today;
        } else if (preset === 'yesterday') {
            s = new Date(today);
            s.setDate(today.getDate() - 1);
            e = new Date(s);
        } else if (preset === 'last_7_days') {
            s = new Date(today);
            s.setDate(today.getDate() - 6);
            e = today;
        } else if (preset === 'this_month') {
            s = new Date(today.getFullYear(), today.getMonth(), 1);
            e = today;
        } else if (preset === 'last_30_days') {
            s = new Date(today);
            s.setDate(today.getDate() - 29);
            e = today;
        } else if (preset === 'this_quarter') {
            const currentQuarter = Math.floor(today.getMonth() / 3);
            s = new Date(today.getFullYear(), currentQuarter * 3, 1);
            e = today;
        }

        window.AppStore.startDate = formatDateInput(s);
        window.AppStore.endDate = formatDateInput(e);

        document.getElementById('stkStartDate').value = window.AppStore.startDate;
        document.getElementById('stkEndDate').value = window.AppStore.endDate;

        window.AppStore.currentPage = 1;
        closeDateRangePopover();
        renderTable();
    };

    window.handleManualDateChange = function() {
        const startVal = document.getElementById('stkStartDate').value;
        const endVal = document.getElementById('stkEndDate').value;
        if (startVal && endVal) {
            window.AppStore.startDate = startVal;
            window.AppStore.endDate = endVal;
            window.AppStore.datePreset = 'custom';
            document.querySelectorAll('.stk-popover-preset-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }
    };

    window.applyCustomDateRange = function() {
        const startVal = document.getElementById('stkStartDate').value;
        const endVal = document.getElementById('stkEndDate').value;
        if (!startVal || !endVal) {
            alert('Please select both a valid start and end date.');
            return;
        }
        if (startVal > endVal) {
            alert('Start date cannot be after end date.');
            return;
        }
        window.AppStore.startDate = startVal;
        window.AppStore.endDate = endVal;
        window.AppStore.datePreset = 'custom';
        window.AppStore.currentPage = 1;
        closeDateRangePopover();
        renderTable();
    };

    window.resetToThisMonth = function() {
        selectDatePreset('this_month');
    };

    // Close popover when clicking anywhere outside
    document.addEventListener('click', function(e) {
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        if (popover && popover.classList.contains('is-open')) {
            if (!popover.contains(e.target) && !trigger.contains(e.target)) {
                closeDateRangePopover();
            }
        }
    });

    // =========================================================================
    // OTHER FILTER EVENT HANDLERS
    // =========================================================================
    window.handleCategoryFilter = function(cat) {
        window.AppStore.category = cat;
        document.getElementById('stkCategoryFilter').value = cat;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.handleBranchFilter = function(b) {
        window.AppStore.branch = b;
        document.getElementById('stkBranchFilter').value = b;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.handleStatusFilter = function(st) {
        window.AppStore.status = st;
        document.getElementById('stkStatusFilter').value = st;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.clearSearchFilter = function() {
        window.AppStore.searchQuery = '';
        document.getElementById('stkSearchInput').value = '';
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.resetAllFilters = function() {
        window.AppStore.searchQuery = '';
        document.getElementById('stkSearchInput').value = '';
        window.AppStore.category = 'ALL';
        document.getElementById('stkCategoryFilter').value = 'ALL';
        window.AppStore.branch = 'ALL';
        document.getElementById('stkBranchFilter').value = 'ALL';
        window.AppStore.status = 'ALL';
        document.getElementById('stkStatusFilter').value = 'ALL';
        selectDatePreset('this_month');
    };

    window.refreshStocksData = async function() {
        const refreshBtn = document.querySelector('button[onclick="refreshStocksData()"]');
        let origHtml = '';
        if (refreshBtn) {
            origHtml = refreshBtn.innerHTML;
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = `<svg class="w-4 h-4 animate-spin text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Syncing...`;
        }

        try {
            const resp = await fetch(window.StocksOverviewConfig?.routes?.stocksOverviewData || '/inventory/api/stocks-overview-data', {
                headers: { 'Accept': 'application/json' }
            });
            if (resp.ok) {
                const json = await resp.json();
                const data = json.data || json;
                if (data.products && Array.isArray(data.products)) {
                    window.AppStore.products = data.products;
                }
                if (data.categories && Array.isArray(data.categories)) {
                    window.AppStore.categories = data.categories;
                }
                const incomingLedger = data.stocksLedger || data.serverLedger;
                if (incomingLedger && Array.isArray(incomingLedger)) {
                    window.AppStore.serverLedger = incomingLedger;
                }
            }
        } catch (err) {
            console.warn('Live API sync notice:', err);
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = origHtml;
            }
        }

        localStorage.removeItem('rms_stocks_ledger');
        window.AppStore.stocksLedger = null;
        getOrGenerateStocksData();
        renderTable();
        showNotification('Inventory ledger data re-synchronized from database!');
    };

    // =========================================================================
    // SLIDE-OUT DETAIL DRAWER (AUDIT & BREAKDOWN)
    // =========================================================================
    window.openItemDetailDrawer = function(productId) {
        const items = getOrGenerateStocksData();
        const item = items.find(i => i.id === productId);
        if (!item) return;

        window.AppStore.selectedProductId = productId;

        document.getElementById('drawerItemTitle').textContent = item.name;
        document.getElementById('drawerItemSubtitle').textContent = `SKU: ${item.sku} • Category: ${item.category} • UOM: ${item.unit}`;

        const eqEl = document.getElementById('drawerFormulaEquation');
        if (eqEl) {
            const adjSign = item.adjustment.qty >= 0 ? `+ ${item.adjustment.qty}` : `- ${Math.abs(item.adjustment.qty)}`;
            eqEl.innerHTML = `
                Beg (${item.begQty}) + In (${item.stockIn.total}) - Out (${item.stockOut.total}) - Waste (${item.waste.total}) ${adjSign} = <strong>Physical On-Hand (${item.onHand} ${item.unit})</strong><br>
                On-Hand (${item.onHand}) + Incoming (${item.inTransit.incoming}) - Outgoing (${item.inTransit.outgoing}) = <strong>Projected Total (${item.projectedTotal} ${item.unit})</strong>
            `;
        }

        const listEl = document.getElementById('drawerMovementList');
        if (listEl) {
            const movements = item.movements || [];
            if (movements.length === 0) {
                listEl.innerHTML = `<div style="text-align: center; padding: 20px; color: var(--stk-text-muted); font-size: 0.8rem;">No movement entries logged for this period.</div>`;
            } else {
                listEl.innerHTML = movements.map(m => `
                    <div class="stk-timeline-item">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 0.8125rem; font-weight: 700; color: var(--stk-text-strong);">${m.type}</span>
                                <span style="font-size: 0.67rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: var(--stk-text-muted);">${m.ref}</span>
                            </div>
                            <div style="font-size: 0.70rem; color: var(--stk-text-muted); margin-top: 3px;">${m.date} &bull; ${m.note}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; font-weight: 800; color: ${m.qty.startsWith('+') ? '#15803d' : '#b91c1c'};">${m.qty} ${item.unit}</div>
                            <div style="font-size: 0.68rem; color: var(--stk-text-muted);">Bal: <strong>${m.balance}</strong></div>
                        </div>
                    </div>
                `).join('');
            }
        }

        document.getElementById('drawerPeriodLabel').textContent = `${formatShortDate(window.AppStore.startDate)} to ${formatShortDate(window.AppStore.endDate)}, ${currentYear}`;

        document.getElementById('stkDrawerBackdrop').classList.add('is-open');
        document.getElementById('stkDetailDrawer').classList.add('is-open');
    };

    window.closeItemDetailDrawer = function() {
        document.getElementById('stkDrawerBackdrop').classList.remove('is-open');
        document.getElementById('stkDetailDrawer').classList.remove('is-open');
        window.AppStore.selectedProductId = null;
    };

    // =========================================================================
    // EXPORT TO CSV
    // =========================================================================
    window.exportStocksCSV = function() {
        const data = getFilteredStocks();
        if (data.length === 0) {
            alert('No records available to export.');
            return;
        }

        const headers = [
            'Item Name',
            'SKU',
            'Barcode',
            'Category',
            'Sub-category',
            'Beginning Qty',
            'Unit of Measure',
            'Stock In (Received)',
            'Stock In (POs)',
            'Stock In (Transfers)',
            'Stock Out (Sales & Consumption)',
            'Stock Out (POS)',
            'Stock Out (Recipe)',
            'Waste & Spoilage',
            'Waste (Damaged)',
            'Waste (Expired)',
            'Audit Adjustment Qty',
            'Adjustment Reason',
            'Production Yield (+)',
            'Production Consumed (-)',
            'Physical On-Hand',
            'Shelf Stock',
            'Backroom Stock',
            'In-Transit Incoming (+)',
            'In-Transit Outgoing (-)',
            'Projected Total Stock'
        ];

        function escapeCSV(val) {
            if (val === null || val === undefined) return '""';
            let str = String(val).replace(/"/g, '""');
            return `"${str}"`;
        }

        let csv = headers.join(',') + '\r\n';
        data.forEach(item => {
            const row = [
                escapeCSV(item.name),
                escapeCSV(item.sku),
                escapeCSV(item.barcode),
                escapeCSV(item.category),
                escapeCSV(item.subcategory),
                item.begQty,
                escapeCSV(item.unit),
                item.stockIn.total,
                item.stockIn.po,
                item.stockIn.transfer,
                item.stockOut.total,
                item.stockOut.pos,
                item.stockOut.recipe,
                item.waste.total,
                item.waste.damaged,
                item.waste.expired,
                item.adjustment.qty,
                escapeCSV(item.adjustment.reason),
                item.production ? item.production.in : 0,
                item.production ? item.production.out : 0,
                item.onHand,
                item.shelfStock,
                item.backroomStock,
                item.inTransit.incoming,
                item.inTransit.outgoing,
                item.projectedTotal
            ];
            csv += row.join(',') + '\r\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const filename = `RMS_Stocks_Overview_${window.AppStore.startDate}_to_${window.AppStore.endDate}.csv`;
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    // =========================================================================
    // INITIALIZATION & EVENT LISTENERS
    // =========================================================================
    function syncCategoryDropdown() {
        const sel = document.getElementById('stkCategoryFilter');
        if (!sel) return;
        const categories = window.AppStore.categories || DEFAULT_CATEGORIES;
        
        let opts = '<option value="ALL">All Categories</option>';
        categories.forEach(cat => {
            opts += `<option value="${cat}">${cat}</option>`;
        });
        sel.innerHTML = opts;
    }

    function initEventListeners() {
        const searchInput = document.getElementById('stkSearchInput');
        if (searchInput) {
            let debounceTimer = null;
            searchInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    window.AppStore.searchQuery = e.target.value.trim();
                    window.AppStore.currentPage = 1;
                    renderTable();
                }, 150);
            });
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            if (e.key === 'Escape') {
                const popover = document.getElementById('stkDateRangePopover');
                if (popover && popover.classList.contains('is-open')) {
                    closeDateRangePopover();
                    return;
                }
                const drawer = document.getElementById('stkDetailDrawer');
                if (drawer && drawer.classList.contains('is-open')) {
                    closeItemDetailDrawer();
                }
            }
        });
    }

    function showNotification(msg) {
        const el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0f172a;color:#ffffff;padding:12px 20px;border-radius:10px;font-size:0.8125rem;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:0 10px 25px rgba(0,0,0,0.2);z-index:2000;transition:all 0.25s ease;';
        el.innerHTML = `<i class="ph ph-check-circle" style="color:#10b981;font-size:18px;"></i> <span>${msg}</span>`;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            setTimeout(() => document.body.removeChild(el), 300);
        }, 3000);
    }

    // Run Initial Load
    syncCategoryDropdown();
    document.getElementById('stkStartDate').value = window.AppStore.startDate;
    document.getElementById('stkEndDate').value = window.AppStore.endDate;
    getOrGenerateStocksData();
    renderTable();
    initEventListeners();

})();