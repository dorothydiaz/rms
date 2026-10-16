(function() {
    'use strict';

    // =========================================================================
    // STATE STORE & ITEM MASTER SYNCHRONIZATION
    // =========================================================================
    const DEFAULT_CATEGORIES = [
        'Beverages', 'Main Course', 'Pastries & Desserts', 'Raw Ingredients',
        'Appetizers & Starters', 'Side Dishes', 'Packaging & Disposables', 'Syrups & Flavors'
    ];

    const INITIAL_BOMS = [
        {
            productId: 1, // Signature Spanish Latte
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans', qty: 18, unit: 'g', unitCost: 1.20 },
                { rawSku: 'RAW-MILK-COND', name: 'Sweetened Condensed Milk', qty: 30, unit: 'ml', unitCost: 0.45 },
                { rawSku: 'RAW-MILK-FRESH', name: 'Barista Whole Fresh Milk', qty: 220, unit: 'ml', unitCost: 0.12 },
                { rawSku: 'PKG-501', name: 'Hot Cup 16oz & Lid', qty: 1, unit: 'pc', unitCost: 3.50 }
            ]
        },
        {
            productId: 2, // Iced Americano Grande
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans', qty: 20, unit: 'g', unitCost: 1.20 },
                { rawSku: 'RAW-WATER-ICE', name: 'Purified Water & Ice', qty: 350, unit: 'ml', unitCost: 0.01 },
                { rawSku: 'PKG-501', name: 'Clear Cold Cup 20oz & Straw', qty: 1, unit: 'pc', unitCost: 4.20 }
            ]
        },
        {
            productId: 4, // Truffle Mushroom Pasta
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-PASTA-FETT', name: 'Fettuccine Dried Pasta', qty: 120, unit: 'g', unitCost: 0.25 },
                { rawSku: 'RAW-MUSH-SHIIT', name: 'Shiitake & Cremini Blend', qty: 80, unit: 'g', unitCost: 0.55 },
                { rawSku: 'RAW-TRUFF-OIL', name: 'White Truffle Infused Cream', qty: 60, unit: 'ml', unitCost: 0.85 },
                { rawSku: 'RAW-CHEESE-PARM', name: 'Parmigiano Reggiano', qty: 25, unit: 'g', unitCost: 1.10 }
            ]
        }
    ];

    const CATEGORY_PALETTES = {
        purple: { label: 'Purple / Violet', bg: '#faf5ff', text: '#7e22ce', border: '#e9d5ff', dot: '#a855f7' },
        amber: { label: 'Amber / Gold', bg: '#fffbeb', text: '#b45309', border: '#fde68a', dot: '#f59e0b' },
        rose: { label: 'Rose / Pink', bg: '#fdf2f8', text: '#be185d', border: '#fbcfe8', dot: '#ec4899' },
        emerald: { label: 'Emerald / Green', bg: '#f0fdf4', text: '#15803d', border: '#bbf7d0', dot: '#10b981' },
        blue: { label: 'Ocean / Blue', bg: '#eff6ff', text: '#1d4ed8', border: '#bfdbfe', dot: '#3b82f6' },
        orange: { label: 'Tangerine / Orange', bg: '#fff7ed', text: '#c2410c', border: '#fed7aa', dot: '#f97316' },
        teal: { label: 'Teal / Cyan', bg: '#f0fdfa', text: '#0f766e', border: '#99f6e4', dot: '#14b8a6' },
        indigo: { label: 'Indigo / Navy', bg: '#eef2ff', text: '#4338ca', border: '#c7d2fe', dot: '#6366f1' },
        slate: { label: 'Slate / Neutral', bg: '#f8fafc', text: '#475569', border: '#cbd5e1', dot: '#64748b' }
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

    window.AppStore = {
        categories: JSON.parse(localStorage.getItem('rms_product_categories')) || DEFAULT_CATEGORIES,
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        products: JSON.parse(localStorage.getItem('rms_inventory_products')) || [],
        boms: JSON.parse(localStorage.getItem('rms_boms')) || INITIAL_BOMS,
        searchQuery: '',
        categoryFilter: 'ALL',
        expandedProductIds: new Set([1]) // First expanded by default
    };

    // If products array is empty, fetch fallback from Item Master seed
    if (!window.AppStore.products || window.AppStore.products.length === 0) {
        window.AppStore.products = [
            { id: 1, sku: 'BEV-001', name: 'Signature Spanish Latte', category: 'Beverages', unit: 'Cup', packSize: '16 oz', costPrice: 45.00, sellingPrice: 150.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 2, sku: 'BEV-002', name: 'Iced Americano Grande', category: 'Beverages', unit: 'Cup', packSize: '20 oz', costPrice: 28.00, sellingPrice: 120.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 3, sku: 'BEV-003', name: 'Matcha Green Tea Fusion', category: 'Beverages', unit: 'Cup', packSize: '16 oz', costPrice: 52.00, sellingPrice: 175.00, hasBom: false, canBeSold: true, canBePurchased: false },
            { id: 4, sku: 'MNC-101', name: 'Truffle Mushroom Pasta', category: 'Main Course', unit: 'Plate', packSize: '320g', costPrice: 115.00, sellingPrice: 380.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 10, sku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans (1kg)', category: 'Raw Ingredients', unit: 'Kg', packSize: '1000g', costPrice: 650.00, sellingPrice: 0.00, hasBom: false, canBeSold: false, canBePurchased: true },
            { id: 11, sku: 'PKG-501', name: 'Kraft Takeout Box (Medium)', category: 'Packaging & Disposables', unit: 'Piece', packSize: 'Pack of 50', costPrice: 8.50, sellingPrice: 15.00, hasBom: false, canBeSold: false, canBePurchased: true }
        ];
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
    }

    // Save initial BOMs if not set
    if (!localStorage.getItem('rms_boms')) {
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
    }

    // Auto-sync: Guarantee any product in Item Master with hasBom === true is registered into BOM catalog
    let bomsModified = false;
    window.AppStore.products.forEach(p => {
        if (p.hasBom) {
            const hasBomEntry = window.AppStore.boms.some(b => b.productId === p.id);
            if (!hasBomEntry) {
                window.AppStore.boms.push({
                    productId: p.id,
                    yield: 1,
                    ingredients: []
                });
                bomsModified = true;
            }
        }
    });
    if (bomsModified) {
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
    }

    // DOM Caches
    const bomMasterTbody = document.getElementById('bomMasterTbody');
    const bomSearchInput = document.getElementById('bomSearchInput');
    const bomCategoryFilter = document.getElementById('bomCategoryFilter');
    const bomDrawer = document.getElementById('bomDrawer');
    const bomDrawerBackdrop = document.getElementById('bomDrawerBackdrop');
    const bomToast = document.getElementById('bomToast');
    const bomToastMsg = document.getElementById('bomToastMsg');

    // Drawer inputs
    const bomProductId = document.getElementById('bomProductId');
    const selectExistingItem = document.getElementById('selectExistingItem');
    const newProdSku = document.getElementById('newProdSku');
    const newProdName = document.getElementById('newProdName');
    const newProdCategory = document.getElementById('newProdCategory');
    const newProdUom = document.getElementById('newProdUom');
    const newProdPackSize = document.getElementById('newProdPackSize');
    const bomSellingPrice = document.getElementById('bomSellingPrice');
    const bomBatchYield = document.getElementById('bomBatchYield');
    const bomIngredientRowsContainer = document.getElementById('bomIngredientRowsContainer');

    // Drawer Mode: 'existing' vs 'new_product'
    let currentDrawerMode = 'existing';

    function showToast(msg) {
        if (!bomToast) return;
        bomToastMsg.textContent = msg;
        bomToast.style.transform = 'translateY(0)';
        bomToast.style.opacity = '1';
        setTimeout(() => {
            bomToast.style.transform = 'translateY(100px)';
            bomToast.style.opacity = '0';
        }, 3200);
    }

    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    // Calculate BOM recipe total cost
    function calculateBomCost(bom) {
        if (!bom || !bom.ingredients) return 0;
        const total = bom.ingredients.reduce((acc, ing) => acc + (Number(ing.qty || 0) * Number(ing.unitCost || 0)), 0);
        const yieldVal = Number(bom.yield) || 1;
        return yieldVal > 0 ? (total / yieldVal) : total;
    }

    // Auto-seed baseline audit logs for existing products if not present
    window.AppStore.products.forEach(p => {
        if (!p.auditLogs || p.auditLogs.length === 0) {
            p.createdAt = p.createdAt || 'Sep 15, 2026 • 09:30 AM';
            p.createdBy = p.createdBy || 'Dorothy Diaz (System Admin)';
            p.auditLogs = [
                {
                    id: 'LOG-INIT-' + p.id,
                    formattedDate: p.createdAt,
                    user: { name: 'Dorothy Diaz', email: 'dorothy@dorothydiaz.internal', role: 'System Administrator', avatar: 'DD' },
                    actionType: 'CREATED',
                    summary: `Initial catalog record created for "${p.name}"`,
                    changes: [
                        { field: 'SKU Code', oldValue: '—', newValue: p.sku },
                        { field: 'Product Name', oldValue: '—', newValue: p.name },
                        { field: 'Selling Price', oldValue: '—', newValue: formatPHP(p.sellingPrice) },
                        { field: 'Cost Price', oldValue: '—', newValue: formatPHP(p.costPrice) },
                        { field: 'Has BOM', oldValue: '—', newValue: p.hasBom ? 'Yes (Recipe Active)' : 'No' }
                    ]
                }
            ];
        }
    });

    function createAuditEntry(user, actionType, summary, changes = []) {
        const now = new Date();
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const month = months[now.getMonth()];
        const day = String(now.getDate()).padStart(2, '0');
        const year = now.getFullYear();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const formattedHours = String(hours).padStart(2, '0');
        const formattedDate = `${month} ${day}, ${year} • ${formattedHours}:${minutes} ${ampm}`;

        return {
            id: 'LOG-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
            timestamp: now.toISOString(),
            formattedDate: formattedDate,
            user: {
                name: user?.name || 'John Abiguero',
                email: user?.email || 'jabiguero@dorothydiaz.internal',
                role: user?.role || 'Lead Inventory Admin',
                avatar: (user?.name || 'JA').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
            },
            actionType: actionType || 'UPDATED',
            summary: summary || 'Recipe updated',
            changes: changes || []
        };
    }

    window.switchBomDrawerTab = function(tabId) {
        document.querySelectorAll('.bom-drawer-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.bom-tab-pane').forEach(pane => {
            pane.style.display = (pane.id === 'pane-' + tabId) ? 'block' : 'none';
        });

        if (tabId === 'tab-bom-audit') {
            const pId = Number(bomProductId.value || selectExistingItem.value);
            const product = window.AppStore.products.find(p => p.id === pId);
            const bom = window.AppStore.boms.find(b => b.productId === pId);
            renderBomAuditLogs(product, bom);
        }
    };

    function renderBomAuditLogs(product, bom) {
        const container = document.getElementById('bomAuditLogsContainer');
        if (!container) return;

        if (!product || currentDrawerMode === 'new_product') {
            container.innerHTML = `
                <div class="audit-overview-card" style="grid-template-columns: 1fr; text-align: center; padding: 28px 16px;">
                    <div style="color: var(--bom-text-muted); display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <i class="ph ph-sparkle" style="font-size: 2rem; color: var(--bom-primary);"></i>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--bom-text-strong);">New BOM Recipe Draft</h4>
                        <p style="margin: 0; font-size: 0.8rem; max-width: 320px; line-height: 1.4;">
                            Audit trail will automatically initialize with recipe creation timestamp and user identity upon saving.
                        </p>
                    </div>
                </div>
            `;
            return;
        }

        const logs = product.auditLogs || [];
        const createdAt = product.createdAt || 'Sep 15, 2026 • 09:30 AM';
        const createdBy = product.createdBy || 'Dorothy Diaz (System Admin)';
        const lastModified = logs.length > 0 ? logs[0].formattedDate : createdAt;

        let overviewHtml = `
            <div class="audit-overview-card">
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-calendar-plus"></i> Creation Date</span>
                    <span class="audit-metric-val" title="${createdAt}">${createdAt}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-user-circle"></i> Registered By</span>
                    <span class="audit-metric-val" title="${createdBy}">${createdBy}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-clock-counter-clockwise"></i> Total Revisions</span>
                    <span class="audit-metric-val">${logs.length} ${logs.length === 1 ? 'Entry' : 'Entries'}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-arrows-clockwise"></i> Last Modified</span>
                    <span class="audit-metric-val" title="${lastModified}">${lastModified}</span>
                </div>
            </div>
        `;

        if (logs.length === 0) {
            container.innerHTML = overviewHtml + `
                <div style="text-align: center; padding: 24px; color: var(--bom-text-muted); font-size: 0.82rem;">
                    No revision history recorded yet for this recipe.
                </div>
            `;
            return;
        }

        let timelineHtml = '<div class="audit-timeline-container">';

        logs.forEach(log => {
            let iconClass = '';
            let iconGlyph = 'ph-clock-counter-clockwise';
            let badgeClass = 'badge-updated';
            let badgeText = log.actionType || 'UPDATED';

            if (log.actionType === 'CREATED') {
                iconClass = 'is-created';
                iconGlyph = 'ph-plus-circle';
                badgeClass = 'badge-created';
                badgeText = 'RECIPE CREATED';
            } else if (log.actionType === 'PRICE_CHANGE') {
                iconClass = 'is-price';
                iconGlyph = 'ph-currency-circle-dollar';
                badgeClass = 'badge-price';
                badgeText = 'PRICE ADJUSTED';
            } else if (log.actionType === 'BOM_CHANGE' || log.actionType === 'BOM_MODIFIED') {
                iconClass = 'is-bom';
                iconGlyph = 'ph-cooking-pot';
                badgeClass = 'badge-bom';
                badgeText = 'RECIPE MODIFIED';
            }

            const userName = log.user?.name || 'Dorothy Diaz';
            const userRole = log.user?.role || 'Staff Member';
            const userEmail = log.user?.email || '';
            const userAvatar = log.user?.avatar || userName.substring(0, 2).toUpperCase();

            let diffRows = '';
            if (log.changes && log.changes.length > 0) {
                diffRows = `
                    <table class="audit-diff-table">
                        <thead>
                            <tr>
                                <th style="width: 32%;">Property / Field</th>
                                <th style="width: 34%;">Previous Value</th>
                                <th style="width: 34%;">New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${log.changes.map(ch => `
                                <tr>
                                    <td style="font-weight: 600; color: var(--bom-text-strong);">${ch.field}</td>
                                    <td><span class="diff-val-old">${ch.oldValue || '—'}</span></td>
                                    <td><span class="diff-val-new">${ch.newValue || '—'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            timelineHtml += `
                <div class="audit-timeline-item">
                    <div class="audit-timeline-node-icon ${iconClass}">
                        <i class="ph ${iconGlyph}"></i>
                    </div>
                    <div class="audit-item-header">
                        <div class="audit-user-profile">
                            <div class="audit-user-avatar" title="${userEmail}">${userAvatar}</div>
                            <div>
                                <div class="audit-user-name">${userName}</div>
                                <div class="audit-user-role">${userRole}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="audit-badge ${badgeClass}">${badgeText}</span>
                            <span style="font-size: 0.72rem; color: var(--bom-text-muted); font-weight: 500;">
                                <i class="ph ph-clock" style="vertical-align: middle;"></i> ${log.formattedDate}
                            </span>
                        </div>
                    </div>
                    <div class="audit-summary-text">${log.summary || 'Recipe modified'}</div>
                    ${diffRows}
                </div>
            `;
        });

        timelineHtml += '</div>';
        container.innerHTML = overviewHtml + timelineHtml;
    }

    // =========================================================================
    // RENDERING: BOM MASTER GRID & KPI STATS
    // =========================================================================
    function renderBomView() {
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const catFilter = window.AppStore.categoryFilter;

        // Reconcile any newly flagged hasBom products from Item Master into BOM state
        window.AppStore.products.forEach(p => {
            if (p.hasBom && !window.AppStore.boms.some(b => b.productId === p.id)) {
                window.AppStore.boms.push({
                    productId: p.id,
                    yield: 1,
                    ingredients: []
                });
            }
        });

        // Build list of products that have a BOM configured or flagged in Item Master
        const items = [];
        window.AppStore.boms.forEach(bom => {
            const product = window.AppStore.products.find(p => p.id === bom.productId);
            if (!product) return;
            // If product hasBom is explicitly set to false and has no ingredients, omit from BOM view
            if (product.hasBom === false && (!bom.ingredients || bom.ingredients.length === 0)) return;

            // Apply category filter
            if (catFilter !== 'ALL' && product.category !== catFilter) return;

            // Search filter
            if (query) {
                const matchName = product.name.toLowerCase().includes(query);
                const matchSku = product.sku.toLowerCase().includes(query);
                const matchCat = (product.category || '').toLowerCase().includes(query);
                const matchIng = (bom.ingredients || []).some(ing => (ing.name || '').toLowerCase().includes(query) || (ing.rawSku || '').toLowerCase().includes(query));
                if (!matchName && !matchSku && !matchCat && !matchIng) return;
            }

            const hasIngredients = bom.ingredients && bom.ingredients.length > 0;
            const totalCost = hasIngredients ? calculateBomCost(bom) : 0;
            const selling = Number(product.sellingPrice) || 0;
            const profit = hasIngredients ? (selling - totalCost) : 0;
            const marginPct = (hasIngredients && selling > 0) ? ((profit / selling) * 100) : 0;

            items.push({
                product,
                bom,
                hasIngredients,
                totalCost,
                selling,
                profit,
                marginPct
            });
        });

        // Update KPIs
        const totalBoms = items.length;
        const configuredItems = items.filter(i => i.hasIngredients);
        const pendingCount = totalBoms - configuredItems.length;

        document.getElementById('statActiveBoms').textContent = totalBoms;

        if (configuredItems.length > 0) {
            const avgCost = configuredItems.reduce((acc, i) => acc + i.totalCost, 0) / configuredItems.length;
            const avgMargin = configuredItems.reduce((acc, i) => acc + i.marginPct, 0) / configuredItems.length;
            const profitableCount = configuredItems.filter(i => i.profit > 0).length;
            const deficitCount = configuredItems.length - profitableCount;

            document.getElementById('statAvgCost').textContent = formatPHP(avgCost);
            document.getElementById('statAvgMargin').textContent = `${avgMargin.toFixed(1)}%`;
            document.getElementById('statProfitableCount').textContent = `${profitableCount} Gaining`;
            document.getElementById('statDeficitCount').textContent = pendingCount > 0 ? `${pendingCount} Pending` : `${deficitCount} At Deficit`;

            const healthEl = document.getElementById('statMarginHealth');
            if (avgMargin >= 30) {
                healthEl.textContent = 'High Profit Margin';
                healthEl.style.color = 'var(--bom-success)';
            } else if (avgMargin > 0) {
                healthEl.textContent = 'Moderate Margin';
                healthEl.style.color = 'var(--bom-warning)';
            } else {
                healthEl.textContent = 'Critical Margin Alert';
                healthEl.style.color = 'var(--bom-danger)';
            }
        } else {
            document.getElementById('statAvgCost').textContent = '₱0.00';
            document.getElementById('statAvgMargin').textContent = '0.0%';
            document.getElementById('statProfitableCount').textContent = '0 Gaining';
            document.getElementById('statDeficitCount').textContent = `${pendingCount} Pending`;
        }

        // Render Table Rows
        if (items.length === 0) {
            bomMasterTbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 48px 24px; color: var(--bom-text-muted);">
                        <i class="ph ph-cooking-pot" style="font-size: 38px; color: var(--bom-text-subtle); margin-bottom: 8px; display: inline-block;"></i>
                        <div style="font-weight: 700; color: var(--bom-text-strong); font-size: 0.95rem;">No Bill of Materials recipes found</div>
                        <div style="font-size: 0.8rem; margin-top: 4px;">Items flagged with "Has Bill of Materials" in Item Master or created here will automatically appear.</div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        items.forEach(({ product, bom, hasIngredients, totalCost, selling, profit, marginPct }) => {
            const isExpanded = window.AppStore.expandedProductIds.has(product.id);

            // Determine status
            let statusPillClass = 'profitable';
            let statusText = `Gaining Profit (+${marginPct.toFixed(1)}%)`;
            let statusIcon = 'ph-trend-up';

            if (!hasIngredients) {
                statusPillClass = 'is-pending';
                statusText = 'Recipe Pending';
                statusIcon = 'ph-clock-countdown';
            } else if (marginPct <= 0) {
                statusPillClass = 'unprofitable';
                statusText = `Loss Deficit (${marginPct.toFixed(1)}%)`;
                statusIcon = 'ph-trend-down';
            } else if (marginPct < 20) {
                statusPillClass = 'low-margin';
                statusText = `Low Margin (+${marginPct.toFixed(1)}%)`;
                statusIcon = 'ph-trend-up';
            }

            // 1. Parent Row
            html += `
                <tr class="bom-row" onclick="toggleBomBreakdown(${product.id})">
                    <td style="text-align: center;">
                        <button type="button" class="bom-expand-btn ${isExpanded ? 'is-expanded' : ''}" onclick="event.stopPropagation(); toggleBomBreakdown(${product.id})">
                            <i class="ph ph-caret-down"></i>
                        </button>
                    </td>
                    <td>
                        <div class="bom-product-title-group">
                            <div class="bom-product-icon"><i class="ph ph-cube"></i></div>
                            <div>
                                <div class="bom-product-name">${product.name}</div>
                                <div class="bom-product-sku">SKU: ${product.sku} &bull; Yield: ${bom.yield || 1} ${product.unit || 'unit'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; background: ${getCategoryPalette(getCategoryMeta(product.category).color).bg}; color: ${getCategoryPalette(getCategoryMeta(product.category).color).text}; border: 1px solid ${getCategoryPalette(getCategoryMeta(product.category).color).border};">
                            <i class="ph ${getCategoryMeta(product.category).icon}"></i>
                            <span>${product.category}</span>
                        </span>
                    </td>
                    <td class="td-right" style="font-weight: 700; color: var(--bom-text-strong);">
                        ${hasIngredients ? formatPHP(totalCost) : '<span style="color:var(--bom-text-muted); font-size:0.74rem;">Pending Setup</span>'}
                    </td>
                    <td class="td-right" style="font-weight: 700;">${formatPHP(selling)}</td>
                    <td class="td-right" style="font-weight: 700; color: ${hasIngredients ? (profit >= 0 ? 'var(--bom-success)' : 'var(--bom-danger)') : 'var(--bom-text-muted)'};">
                        ${hasIngredients ? ((profit >= 0 ? '+' : '') + formatPHP(profit)) : '<span style="color:var(--bom-text-muted);">—</span>'}
                    </td>
                    <td class="td-right" style="font-weight: 800; color: ${hasIngredients ? (marginPct >= 0 ? 'var(--bom-success)' : 'var(--bom-danger)') : 'var(--bom-text-muted)'};">
                        ${hasIngredients ? marginPct.toFixed(1) + '%' : '<span style="color:var(--bom-text-muted);">—</span>'}
                    </td>
                    <td>
                        <span class="bom-profit-pill ${statusPillClass}">
                            <i class="ph ${statusIcon}"></i>
                            <span>${statusText}</span>
                        </span>
                    </td>
                    <td class="td-right">
                        <button type="button" class="${hasIngredients ? 'bom-action-btn-secondary' : 'bom-action-btn-primary'}" onclick="event.stopPropagation(); openBomDrawer(${product.id})" style="padding: 5px 12px; font-size: 0.74rem;">
                            <i class="ph ${hasIngredients ? 'ph-pencil-simple' : 'ph-plus-circle'}"></i>
                            <span>${hasIngredients ? 'Edit BOM' : 'Set Recipe'}</span>
                        </button>
                    </td>
                </tr>
            `;

            // 2. Nested Raw Materials Breakdown Row
            const ingredients = bom.ingredients || [];
            html += `
                <tr class="bom-breakdown-row ${isExpanded ? '' : 'is-hidden'}" id="breakdown-row-${product.id}">
                    <td colspan="9" style="padding: 0;">
                        <div class="bom-breakdown-box">
            `;

            if (!hasIngredients) {
                html += `
                            <div style="text-align: center; padding: 26px 16px;">
                                <div style="font-size: 2rem; color: #d97706; margin-bottom: 8px;"><i class="ph ph-receipt"></i></div>
                                <div style="font-weight: 700; color: var(--bom-text-strong); font-size: 0.88rem;">Recipe Not Configured Yet</div>
                                <div style="font-size: 0.76rem; color: var(--bom-text-muted); max-width: 440px; margin: 4px auto 14px;">
                                    "${product.name}" is marked as having a Bill of Materials in Item Master. Define its component raw materials and automated inventory deductions.
                                </div>
                                <button type="button" class="bom-action-btn-primary" onclick="openBomDrawer(${product.id})" style="font-size: 0.76rem; margin: 0 auto; display: inline-flex;">
                                    <i class="ph ph-plus-circle"></i>
                                    <span>Configure Bill of Materials</span>
                                </button>
                            </div>
                `;
            } else {
                html += `
                            <div class="bom-breakdown-header">
                                <span class="bom-breakdown-title">
                                    <i class="ph ph-list-dashes" style="color: var(--bom-primary-dark);"></i>
                                    <span>Raw Materials Recipe Breakdown (${ingredients.length} ${ingredients.length === 1 ? 'component' : 'components'})</span>
                                </span>
                                <span style="font-size: 0.72rem; color: var(--bom-text-muted);">
                                    Batch Cost: <strong>${formatPHP(totalCost * (bom.yield || 1))}</strong> (Yield: ${bom.yield || 1})
                                </span>
                            </div>
                            <table class="bom-subtable">
                                <thead>
                                    <tr>
                                        <th>Component Material</th>
                                        <th>Material SKU</th>
                                        <th class="th-right">Recipe Quantity</th>
                                        <th class="th-right">Item Master Cost</th>
                                        <th class="th-right">Extended Cost</th>
                                        <th style="width: 160px;">Cost Contribution %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${ingredients.map(ing => {
                                        const extCost = (Number(ing.qty) || 0) * (Number(ing.unitCost) || 0);
                                        const batchCost = totalCost * (bom.yield || 1);
                                        const pct = batchCost > 0 ? ((extCost / batchCost) * 100).toFixed(1) : 0;
                                        return `
                                            <tr>
                                                <td style="font-weight: 600; color: var(--bom-text-strong);">${ing.name}</td>
                                                <td><span style="font-family: monospace; font-size: 0.72rem; color: var(--bom-text-muted);">${ing.rawSku || '—'}</span></td>
                                                <td class="td-right" style="font-weight: 600;">${ing.qty} ${ing.unit}</td>
                                                <td class="td-right">${formatPHP(ing.unitCost)}</td>
                                                <td class="td-right" style="font-weight: 700; color: var(--bom-text-strong);">${formatPHP(extCost)}</td>
                                                <td>
                                                    <div class="bom-cost-bar-wrap">
                                                        <div class="bom-cost-bar">
                                                            <div class="bom-cost-fill" style="width: ${Math.min(100, pct)}%;"></div>
                                                        </div>
                                                        <span style="font-size: 0.68rem; font-weight: 600; min-width: 38px; text-align: right;">${pct}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">Total Recipe Raw Material Cost</td>
                                        <td class="td-right">${formatPHP(totalCost * (bom.yield || 1))}</td>
                                        <td><strong>100% of Materials</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                `;
            }

            html += `
                        </div>
                    </td>
                </tr>
            `;
        });

        bomMasterTbody.innerHTML = html;
    }

    window.toggleBomBreakdown = function(productId) {
        if (window.AppStore.expandedProductIds.has(productId)) {
            window.AppStore.expandedProductIds.delete(productId);
        } else {
            window.AppStore.expandedProductIds.add(productId);
        }
        renderBomView();
    };

    window.toggleAllBomBreakdowns = function() {
        const anyCollapsed = window.AppStore.boms.some(b => !window.AppStore.expandedProductIds.has(b.productId));
        if (anyCollapsed) {
            window.AppStore.boms.forEach(b => window.AppStore.expandedProductIds.add(b.productId));
            document.getElementById('textExpandAllBoms').textContent = 'Collapse All';
            document.getElementById('iconExpandAllBoms').className = 'ph ph-arrows-in-line-vertical';
        } else {
            window.AppStore.expandedProductIds.clear();
            document.getElementById('textExpandAllBoms').textContent = 'Expand All';
            document.getElementById('iconExpandAllBoms').className = 'ph ph-arrows-out-line-vertical';
        }
        renderBomView();
    };

    window.handleBomCategoryFilter = function(val) {
        window.AppStore.categoryFilter = val;
        renderBomView();
    };

    bomSearchInput.addEventListener('input', function(e) {
        window.AppStore.searchQuery = e.target.value;
        renderBomView();
    });

    // Populate category dropdowns
    function syncCategoryDropdowns() {
        const cats = window.AppStore.categories;
        bomCategoryFilter.innerHTML = '<option value="ALL">All Categories</option>' + cats.map(c => `<option value="${c}">${c}</option>`).join('');
        newProdCategory.innerHTML = cats.map(c => `<option value="${c}">${c}</option>`).join('');
    }

    // =========================================================================
    // SLIDE-OUT DRAWER: CREATE / EDIT BOM CONTROLLER
    // =========================================================================
    window.switchBomDrawerMode = function(mode) {
        currentDrawerMode = mode;
        const btnExisting = document.getElementById('btnModeExisting');
        const btnNew = document.getElementById('btnModeNew');
        const secExisting = document.getElementById('sectionExistingProduct');
        const secNew = document.getElementById('sectionNewProduct');

        if (mode === 'existing') {
            btnExisting.classList.add('active');
            btnNew.classList.remove('active');
            secExisting.style.display = 'flex';
            secNew.style.display = 'none';
            handleSelectExistingProduct(selectExistingItem.value);
        } else {
            btnExisting.classList.remove('active');
            btnNew.classList.add('active');
            secExisting.style.display = 'none';
            secNew.style.display = 'flex';
            bomProductId.value = '';
            newProdSku.value = 'PRD-' + Math.floor(1000 + Math.random() * 9000);
            newProdName.value = '';
            newProdName.focus();
        }
        recalculateDrawerBomFinancials();
    };

    window.handleSelectExistingProduct = function(prodId) {
        const prod = window.AppStore.products.find(p => p.id === Number(prodId));
        if (!prod) return;
        bomProductId.value = prod.id;
        bomSellingPrice.value = Number(prod.sellingPrice || 0).toFixed(2);

        document.getElementById('bomTrackPhysicalStock').checked = prod.trackPhysicalStock !== undefined ? prod.trackPhysicalStock : (prod.trackStock !== false);
        document.getElementById('bomExplodeBomOnSale').checked = prod.explodeBomOnSale !== undefined ? prod.explodeBomOnSale : (prod.hasBom !== false);

        // If this product already has a configured BOM recipe, sync its yield and ingredients
        const existingBom = window.AppStore.boms.find(b => b.productId === prod.id);
        if (existingBom) {
            bomBatchYield.value = existingBom.yield || 1;
            bomIngredientRowsContainer.innerHTML = '';
            if (existingBom.ingredients && existingBom.ingredients.length > 0) {
                existingBom.ingredients.forEach(ing => addBomIngredientRow(ing));
            } else {
                addBomIngredientRow();
            }
        }
        recalculateDrawerBomFinancials();
    };

    window.openBomDrawer = function(idOrMode) {
        syncCategoryDropdowns();
        populateExistingItemsDropdown();
        switchBomDrawerTab('tab-bom-builder');

        if (idOrMode === 'new') {
            document.getElementById('drawerBomTitle').textContent = 'Create BOM & Recipe';
            document.getElementById('drawerBomSubtitle').textContent = 'Set component raw materials and automated inventory deductions';
            document.getElementById('btnSaveBomText').textContent = 'Save BOM & Product';

            // Reset inputs
            bomProductId.value = '';
            bomSellingPrice.value = '';
            bomBatchYield.value = 1;
            document.getElementById('bomTrackPhysicalStock').checked = true;
            document.getElementById('bomExplodeBomOnSale').checked = true;
            bomIngredientRowsContainer.innerHTML = '';

            // Add 2 initial ingredient rows
            addBomIngredientRow();
            addBomIngredientRow();

            switchBomDrawerMode('existing');
        } else {
            const product = window.AppStore.products.find(p => p.id === Number(idOrMode));
            if (!product) return;

            document.getElementById('drawerBomTitle').textContent = `Edit BOM: ${product.name}`;
            document.getElementById('drawerBomSubtitle').textContent = `SKU: ${product.sku} • Category: ${product.category}`;
            document.getElementById('btnSaveBomText').textContent = 'Update BOM';

            bomProductId.value = product.id;
            selectExistingItem.value = product.id;
            bomSellingPrice.value = Number(product.sellingPrice || 0).toFixed(2);
            document.getElementById('bomTrackPhysicalStock').checked = product.trackPhysicalStock !== undefined ? product.trackPhysicalStock : (product.trackStock !== false);
            document.getElementById('bomExplodeBomOnSale').checked = product.explodeBomOnSale !== undefined ? product.explodeBomOnSale : (product.hasBom !== false);

            const bom = window.AppStore.boms.find(b => b.productId === product.id);
            bomBatchYield.value = (bom && bom.yield) ? bom.yield : 1;

            bomIngredientRowsContainer.innerHTML = '';
            if (bom && bom.ingredients && bom.ingredients.length > 0) {
                bom.ingredients.forEach(ing => {
                    addBomIngredientRow(ing);
                });
            } else {
                addBomIngredientRow();
            }

            switchBomDrawerMode('existing');
            document.getElementById('bomModeSwitcher').style.display = 'none'; // Lock mode on edit
        }

        recalculateDrawerBomFinancials();
        bomDrawerBackdrop.classList.add('is-open');
        bomDrawer.classList.add('is-open');
    };

    window.closeBomDrawer = function() {
        bomDrawerBackdrop.classList.remove('is-open');
        bomDrawer.classList.remove('is-open');
        document.getElementById('bomModeSwitcher').style.display = 'flex';
        switchBomDrawerTab('tab-bom-builder');
    };

    function populateExistingItemsDropdown() {
        const prods = window.AppStore.products;
        selectExistingItem.innerHTML = prods.map(p => `
            <option value="${p.id}">
                ${p.name} (${p.sku}) &bull; Category: ${p.category} &bull; Price: ${formatPHP(p.sellingPrice)}
            </option>
        `).join('');
    }

    // Dynamic Raw Material / Ingredient Row in Drawer
    window.addBomIngredientRow = function(data) {
        const rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        const products = window.AppStore.products || [];

        const rowEl = document.createElement('div');
        rowEl.className = 'bom-builder-item-row';
        rowEl.id = rowId;

        // Check if data matches an existing item in Item Master
        const matchedProd = data ? products.find(p => p.sku === data.rawSku || p.name === data.name) : null;
        let extraOption = '';
        if (data && !matchedProd) {
            extraOption = `<option value="custom" data-cost="${data.unitCost || 0}" data-unit="${data.unit || 'unit'}" data-sku="${data.rawSku || 'RAW'}" selected>
                ${data.name} (${data.rawSku || 'RAW'})
            </option>`;
        }

        const opts = products.map(p => {
            const isSelected = matchedProd && matchedProd.id === p.id;
            return `<option value="${p.id}" data-cost="${p.costPrice}" data-unit="${p.unit || 'pc'}" data-sku="${p.sku}" ${isSelected ? 'selected' : ''}>
                ${p.name} (${p.sku}) &bull; Stock Cost: ${formatPHP(p.costPrice)}
            </option>`;
        }).join('');

        const defaultQty = data ? (data.qty !== undefined ? data.qty : 1) : 1;
        const initialUnit = data ? data.unit : (matchedProd ? (matchedProd.unit || 'pc') : (products[0]?.unit || 'pc'));

        let defaultUnitCost = 0;
        if (data && data.unitCost !== undefined) {
            defaultUnitCost = Number(data.unitCost);
        } else if (matchedProd) {
            defaultUnitCost = Number(matchedProd.costPrice || 0);
        } else if (products[0]) {
            defaultUnitCost = Number(products[0].costPrice || 0);
        }

        rowEl.innerHTML = `
            <div>
                <select class="bom-form-select ing-product-select" style="padding: 6px 8px; font-size: 0.78rem;" onchange="handleIngredientChange('${rowId}', true)">
                    ${extraOption}
                    ${opts}
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <input type="number" step="any" min="0.0001" class="bom-form-input ing-qty-input" style="padding: 6px 6px; font-size: 0.78rem;" value="${defaultQty}" placeholder="Qty" oninput="recalculateDrawerBomFinancials()">
                <span class="ing-unit-badge" style="font-size: 0.70rem; color: var(--bom-text-muted); font-weight: 600; min-width: 22px;">${initialUnit}</span>
            </div>
            <div>
                <input type="number" step="any" min="0" class="bom-form-input ing-cost-input" style="padding: 6px 6px; font-size: 0.78rem; text-align: right;" value="${defaultUnitCost}" placeholder="0.00" oninput="recalculateDrawerBomFinancials()">
            </div>
            <div style="text-align: right;">
                <span class="ing-subtotal-val" style="font-size: 0.76rem; font-weight: 700; color: var(--bom-text-strong); font-variant-numeric: tabular-nums;">${formatPHP(defaultQty * defaultUnitCost)}</span>
            </div>
            <div>
                <button type="button" class="bom-item-delete-btn" onclick="removeBomIngredientRow('${rowId}')" title="Remove ingredient">
                    <i class="ph ph-trash"></i>
                </button>
            </div>
        `;

        bomIngredientRowsContainer.appendChild(rowEl);
        handleIngredientChange(rowId, false);
    };

    window.removeBomIngredientRow = function(rowId) {
        const el = document.getElementById(rowId);
        if (el) {
            el.remove();
            recalculateDrawerBomFinancials();
        }
    };

    window.handleIngredientChange = function(rowId, userChanged = false) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const sel = row.querySelector('.ing-product-select');
        const unitBadge = row.querySelector('.ing-unit-badge');
        const costInput = row.querySelector('.ing-cost-input');
        const opt = sel ? sel.selectedOptions[0] : null;
        if (opt) {
            if (unitBadge) {
                unitBadge.textContent = opt.getAttribute('data-unit') || 'pc';
            }
            if (costInput && userChanged) {
                costInput.value = parseFloat(opt.getAttribute('data-cost')) || 0;
            }
        }
        recalculateDrawerBomFinancials();
    };

    // Live Financial Radar Recalculation
    window.recalculateDrawerBomFinancials = function() {
        const rows = bomIngredientRowsContainer.querySelectorAll('.bom-builder-item-row');
        let totalRawCost = 0;

        rows.forEach(row => {
            const qtyInput = row.querySelector('.ing-qty-input');
            const costInput = row.querySelector('.ing-cost-input');
            const subtotalEl = row.querySelector('.ing-subtotal-val');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const unitCost = parseFloat(costInput ? costInput.value : 0) || 0;
            const extCost = qty * unitCost;

            if (subtotalEl) {
                subtotalEl.textContent = formatPHP(extCost);
            }
            totalRawCost += extCost;
        });

        const yieldVal = Math.max(0.0001, parseFloat(bomBatchYield.value) || 1);
        const unitRecipeCost = totalRawCost / yieldVal;

        // Parse selling price safely without NaN spikes
        const rawSelling = (bomSellingPrice.value !== undefined && bomSellingPrice.value !== null) ? String(bomSellingPrice.value).trim() : '';
        const sellingPrice = (rawSelling === '') ? 0 : (parseFloat(rawSelling) || 0);

        const profit = sellingPrice - unitRecipeCost;

        // Stable Margin % calculation
        let marginPct = 0;
        if (sellingPrice > 0) {
            marginPct = (profit / sellingPrice) * 100;
        } else if (unitRecipeCost > 0) {
            marginPct = -100;
        } else {
            marginPct = 0;
        }

        const totalCostEl = document.getElementById('radarTotalCost');
        const grossProfitEl = document.getElementById('radarGrossProfit');
        const profitMarginEl = document.getElementById('radarProfitMargin');
        const banner = document.getElementById('radarBanner');
        const bannerIcon = document.getElementById('radarBannerIcon');
        const bannerText = document.getElementById('radarBannerText');
        const healthTag = document.getElementById('radarHealthTag');

        if (totalCostEl) totalCostEl.textContent = formatPHP(unitRecipeCost);
        if (grossProfitEl) grossProfitEl.textContent = (profit >= 0 ? '+' : '') + formatPHP(profit);
        if (profitMarginEl) profitMarginEl.textContent = `${marginPct >= 0 ? '+' : ''}${marginPct.toFixed(1)}%`;

        if (rawSelling === '' || sellingPrice === 0) {
            // Neutral state while typing or empty
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-text-muted)';
            if (profitMarginEl) {
                profitMarginEl.style.color = 'var(--bom-text-muted)';
                profitMarginEl.textContent = '0.0%';
            }
            if (healthTag) {
                healthTag.className = 'bom-profit-pill';
                healthTag.style.background = '#e2e8f0';
                healthTag.style.color = '#475569';
                healthTag.textContent = 'Enter Price';
            }
            if (banner) banner.className = 'bom-radar-banner is-neutral';
            if (bannerIcon) bannerIcon.className = 'ph ph-info';
            if (bannerText) bannerText.textContent = `Set Selling Price (PHP) above. Total raw recipe cost is ${formatPHP(unitRecipeCost)} per unit.`;
        } else if (profit >= 0 && marginPct >= 20) {
            // Gaining Profit
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-success)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-success)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill profitable';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'Gaining Profit';
            }
            if (banner) banner.className = 'bom-radar-banner is-profit';
            if (bannerIcon) bannerIcon.className = 'ph ph-trend-up';
            if (bannerText) bannerText.textContent = `Gaining Profit: You earn ${formatPHP(profit)} (${marginPct.toFixed(1)}%) per unit sold!`;
        } else if (profit >= 0 && marginPct < 20) {
            // Low Margin
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-warning)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-warning)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill low-margin';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'Low Margin';
            }
            if (banner) banner.className = 'bom-radar-banner is-warning';
            if (bannerIcon) bannerIcon.className = 'ph ph-warning';
            if (bannerText) bannerText.textContent = `Low Margin Warning: Profit is only ${formatPHP(profit)} (${marginPct.toFixed(1)}%). Review component costs or pricing.`;
        } else {
            // Deficit
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-danger)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-danger)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill unprofitable';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'At Deficit';
            }
            if (banner) banner.className = 'bom-radar-banner is-deficit';
            if (bannerIcon) bannerIcon.className = 'ph ph-warning-circle';
            if (bannerText) bannerText.textContent = `Deficit Warning: Raw material cost (${formatPHP(unitRecipeCost)}) exceeds selling price (${formatPHP(sellingPrice)}) by ${formatPHP(Math.abs(profit))}!`;
        }
    };

    // =========================================================================
    // SAVE BOM & COMMITTED ITEM MASTER MUTATION
    // =========================================================================
    window.handleSaveBom = function(e) {
        e.preventDefault();

        const rows = bomIngredientRowsContainer.querySelectorAll('.bom-builder-item-row');
        if (rows.length === 0) {
            alert('Please add at least one raw material / ingredient to the recipe.');
            return;
        }

        const ingredients = [];
        let totalRawCost = 0;

        rows.forEach(row => {
            const sel = row.querySelector('.ing-product-select');
            const qtyInput = row.querySelector('.ing-qty-input');
            const costInput = row.querySelector('.ing-cost-input');
            const opt = sel ? sel.selectedOptions[0] : null;
            if (opt && qtyInput) {
                const pId = Number(opt.value);
                const p = window.AppStore.products.find(item => item.id === pId);
                const qty = parseFloat(qtyInput.value) || 1;
                const unitCost = parseFloat(costInput ? costInput.value : opt.getAttribute('data-cost')) || 0;
                const unit = opt.getAttribute('data-unit') || 'pc';

                ingredients.push({
                    rawSku: opt.getAttribute('data-sku') || (p ? p.sku : 'RAW'),
                    name: p ? p.name : (opt.text.split('(')[0].trim() || 'Ingredient'),
                    qty: qty,
                    unit: unit,
                    unitCost: unitCost
                });
                totalRawCost += (qty * unitCost);
            }
        });

        const yieldVal = Math.max(0.0001, parseFloat(bomBatchYield.value) || 1);
        const unitBomCost = totalRawCost / yieldVal;
        const sellingPrice = parseFloat(bomSellingPrice.value) || 0;
        const trackPhysicalStock = document.getElementById('bomTrackPhysicalStock').checked;
        const explodeBomOnSale = document.getElementById('bomExplodeBomOnSale').checked;

        let targetProduct = null;

        const currentUser = {
            name: 'John Abiguero',
            email: 'jabiguero@dorothydiaz.internal',
            role: 'Lead Inventory Admin'
        };

        if (currentDrawerMode === 'new_product') {
            // Create New Product directly into Item Master
            const newSku = newProdSku.value.trim() || ('PRD-' + Math.floor(1000 + Math.random() * 9000));
            const newName = newProdName.value.trim();
            if (!newName) {
                alert('Please enter a product name.');
                return;
            }

            targetProduct = {
                id: Date.now(),
                sku: newSku,
                name: newName,
                category: newProdCategory.value || 'Beverages',
                subcategory: 'House Recipe',
                unit: newProdUom.value || 'Piece',
                packSize: newProdPackSize.value.trim() || '1 serving',
                costPrice: unitBomCost,
                sellingPrice: sellingPrice,
                taxRate: '12% VAT',
                supplier: 'In-House Production (BOM)',
                allergens: [],
                isActive: true,
                trackPhysicalStock: trackPhysicalStock,
                trackStock: trackPhysicalStock,
                explodeBomOnSale: explodeBomOnSale,
                hasBom: explodeBomOnSale,
                canBeSold: true,
                canBePurchased: false,
                reorderPoint: 10,
                targetStock: 50,
                branches: [
                    { name: 'Power Mac Center - Main HQ', onHand: 0, reserved: 0, reorder: 10 }
                ],
                ledger: [
                    { ref: 'BOM-INIT', type: 'Recipe Created', qty: '+0', balance: 0 }
                ]
            };

            const initialLog = createAuditEntry(currentUser, 'CREATED', `Initial BOM recipe and catalog registration for "${targetProduct.name}"`, [
                { field: 'Product Name', oldValue: '—', newValue: targetProduct.name },
                { field: 'SKU Code', oldValue: '—', newValue: targetProduct.sku },
                { field: 'Category', oldValue: '—', newValue: targetProduct.category },
                { field: 'Batch Yield', oldValue: '—', newValue: `${yieldVal} ${targetProduct.unit}` },
                { field: 'Calculated Unit Cost', oldValue: '—', newValue: formatPHP(unitBomCost) },
                { field: 'Selling Price', oldValue: '—', newValue: formatPHP(sellingPrice) },
                { field: 'Track Physical Stock', oldValue: '—', newValue: targetProduct.trackPhysicalStock ? 'Yes (Counted in Stocks Overview)' : 'No (Non-Physical / Service)' },
                { field: 'Explode Recipe on Sale', oldValue: '—', newValue: targetProduct.explodeBomOnSale ? 'Yes (Deducts Sub-ingredients)' : 'No (Deducts Item Directly)' },
                { field: 'Recipe Ingredients', oldValue: '—', newValue: `${ingredients.length} items (${ingredients.map(i => i.name).join(', ')})` }
            ]);
            targetProduct.createdAt = initialLog.formattedDate;
            targetProduct.createdBy = `${currentUser.name} (${currentUser.role})`;
            targetProduct.auditLogs = [initialLog];

            window.AppStore.products.unshift(targetProduct);
            showToast(`Product "${targetProduct.name}" and BOM created!`);
        } else {
            // Update Existing Product
            const pId = Number(bomProductId.value || selectExistingItem.value);
            targetProduct = window.AppStore.products.find(p => p.id === pId);
            if (targetProduct) {
                const existingBom = window.AppStore.boms.find(b => b.productId === targetProduct.id);
                const changes = [];
                const oldPrice = Number(targetProduct.sellingPrice || 0);
                const oldCost = Number(targetProduct.costPrice || 0);
                const oldYield = existingBom ? Number(existingBom.yield || 1) : 1;
                const oldIngredients = (existingBom && existingBom.ingredients) ? existingBom.ingredients : [];

                if (Math.abs(oldPrice - sellingPrice) > 0.009) {
                    changes.push({ field: 'Selling Price', oldValue: formatPHP(oldPrice), newValue: formatPHP(sellingPrice) });
                }
                if (Math.abs(oldCost - unitBomCost) > 0.009) {
                    changes.push({ field: 'Calculated Unit Cost', oldValue: formatPHP(oldCost), newValue: formatPHP(unitBomCost) });
                }
                if (Math.abs(oldYield - yieldVal) > 0.009) {
                    changes.push({ field: 'Batch Yield', oldValue: `${oldYield} ${targetProduct.unit || 'unit(s)'}`, newValue: `${yieldVal} ${targetProduct.unit || 'unit(s)'}` });
                }
                if ((targetProduct.trackPhysicalStock ?? targetProduct.trackStock) !== trackPhysicalStock) {
                    changes.push({
                        field: 'Track Physical Stock',
                        oldValue: (targetProduct.trackPhysicalStock ?? targetProduct.trackStock) ? 'Yes' : 'No',
                        newValue: trackPhysicalStock ? 'Yes' : 'No'
                    });
                }
                if ((targetProduct.explodeBomOnSale ?? targetProduct.hasBom) !== explodeBomOnSale) {
                    changes.push({
                        field: 'Explode Recipe on Sale',
                        oldValue: (targetProduct.explodeBomOnSale ?? targetProduct.hasBom) ? 'Yes' : 'No',
                        newValue: explodeBomOnSale ? 'Yes' : 'No'
                    });
                }
                
                const oldIngNames = oldIngredients.map(i => `${i.name} (${i.qty} ${i.unit})`).join('; ');
                const newIngNames = ingredients.map(i => `${i.name} (${i.qty} ${i.unit})`).join('; ');
                if (oldIngNames !== newIngNames) {
                    changes.push({
                        field: 'Recipe Ingredients',
                        oldValue: oldIngNames || 'None (Unconfigured)',
                        newValue: newIngNames
                    });
                }

                if (changes.length > 0) {
                    let actionType = 'BOM_MODIFIED';
                    if (changes.some(c => c.field === 'Selling Price')) actionType = 'PRICE_CHANGE';
                    const log = createAuditEntry(currentUser, actionType, `Updated recipe BOM (${changes.length} propert${changes.length === 1 ? 'y' : 'ies'} altered)`, changes);
                    targetProduct.auditLogs = [log, ...(targetProduct.auditLogs || [])];
                }

                targetProduct.trackPhysicalStock = trackPhysicalStock;
                targetProduct.trackStock = trackPhysicalStock;
                targetProduct.explodeBomOnSale = explodeBomOnSale;
                targetProduct.hasBom = explodeBomOnSale;
                targetProduct.canBeSold = true;
                targetProduct.costPrice = unitBomCost;
                targetProduct.sellingPrice = sellingPrice;
            }
            showToast(`BOM recipe for "${targetProduct ? targetProduct.name : 'product'}" saved!`);
        }

        // Commit BOM record
        const bomPayload = {
            productId: targetProduct.id,
            yield: yieldVal,
            ingredients: ingredients
        };

        const existingBomIdx = window.AppStore.boms.findIndex(b => b.productId === targetProduct.id);
        if (existingBomIdx !== -1) {
            window.AppStore.boms[existingBomIdx] = bomPayload;
        } else {
            window.AppStore.boms.unshift(bomPayload);
        }

        // Persist to localStorage for Item Master and BOM reactivity
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
        localStorage.removeItem('rms_stocks_ledger');

        // Re-render
        window.AppStore.expandedProductIds.add(targetProduct.id);
        renderBomView();
        closeBomDrawer();
    };

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bomDrawer.classList.contains('is-open')) {
            closeBomDrawer();
        }
        if (e.key === 'F2') {
            e.preventDefault();
            openBomDrawer('new');
        }
    });

    // Expose all functions to window for inline HTML event handlers & cross-module integration
    Object.assign(window, {
        openBomDrawer,
        closeBomDrawer,
        switchBomDrawerTab,
        switchBomDrawerMode,
        toggleBomBreakdown,
        toggleAllBomBreakdowns,
        handleBomCategoryFilter,
        handleSelectExistingProduct,
        addBomIngredientRow,
        removeBomIngredientRow,
        handleIngredientChange,
        recalculateDrawerBomFinancials,
        handleSaveBom,
        renderBomView,
        syncCategoryDropdowns,
        calculateBomCost,
        formatPHP,
        showToast
    });

    // Initialize View
    syncCategoryDropdowns();
    renderBomView();

})();