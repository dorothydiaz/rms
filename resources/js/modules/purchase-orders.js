/**
 * ==========================================================================
 * PURCHASE ORDERS (PO) ARCHITECTURAL CORE
 * Seamless Support: Standard Vendor POs + Direct Wet Market Cash Purchases,
 * Integrated Financial Tracking & Settlement, LocalStorage Persistence & Red-Green Verification
 * ==========================================================================
 */

// Seeded Item Master Dictionary
const PO_ITEM_MASTER = [
    { sku: 'RAW-301', name: 'Barista Whole Fresh Milk', specs: '100% Pure Cow Fresh Chilled Milk', unit: 'Liter', defaultCost: 85.00, category: 'Raw Ingredients' },
    { sku: 'RAW-302', name: 'Arabica Espresso Beans (Single Origin)', specs: 'Medium-Dark Roast, Highland Benguet Arabica', unit: 'Kg', defaultCost: 550.00, category: 'Raw Ingredients' },
    { sku: 'RAW-303', name: 'French Butter Blocks Unsalted', specs: '82% Butterfat Culinary Grade', unit: 'Pc', defaultCost: 120.00, category: 'Raw Ingredients' },
    { sku: 'RAW-304', name: 'Cheddar Melt Shredded Cheese', specs: 'High-Melt Blend for Burgers & Pasta', unit: 'Kg', defaultCost: 320.00, category: 'Raw Ingredients' },
    { sku: 'RAW-305', name: 'Culinary Whipping Cream 35%', specs: 'UHT Animal Fat Whipping Cream', unit: 'Liter', defaultCost: 180.00, category: 'Raw Ingredients' },
    { sku: 'RAW-306', name: 'All-Purpose Wheat Flour', specs: 'Unbleached Baking Wheat Flour', unit: 'Kg', defaultCost: 39.20, category: 'Raw Ingredients' },
    { sku: 'RAW-307', name: 'Farm Fresh Table Eggs XL', specs: 'Grade A Farm Fresh Eggs Tray of 30', unit: 'Pc', defaultCost: 7.50, category: 'Raw Ingredients' },
    { sku: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', unit: 'Kg', defaultCost: 840.00, category: 'Raw Ingredients' },
    { sku: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', unit: 'Kg', defaultCost: 340.00, category: 'Raw Ingredients' },
    { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', unit: 'Pc', defaultCost: 4.50, category: 'Packaging' },
    { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', unit: 'Pc', defaultCost: 8.20, category: 'Packaging' }
];

// Seeded PO Directory populated with realistic vendor orders and wet market runs
const SEEDED_PURCHASE_ORDERS = [
    {
        poNumber: 'PO-2026-0101',
        poType: 'vendor',
        rfqReference: 'RFQ-2026-0038',
        vendorId: 'VND-SAN-002',
        vendorName: 'San Miguel Pure Foods Company Inc.',
        vendorTradeName: 'San Miguel Foods',
        vendorContactPerson: 'Patricia Lim',
        vendorPhone: '+63 917 882 1044',
        vendorEmail: 'orders.foodservice@sanmiguel.com.ph',
        vendorAddress: '40 San Miguel Ave, Mandaluyong City',
        orderDate: '2026-09-25',
        expectedDelivery: '2026-10-02',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 34600.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: 'Trade invoice terms net 30 days.',
        approvalNotes: 'Approved as lowest compliant bidder for Q4 meat supply.',
        approvedBy: 'Procurement Director',
        status: 'Approved / Issued',
        items: [
            { sku: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 25.0, unitPrice: 840.00 },
            { sku: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 40.0, unitPrice: 340.00 }
        ]
    },
    {
        poNumber: 'PO-2026-0102',
        poType: 'wet_market',
        rfqReference: 'None (Direct Market Purchase)',
        vendorId: 'WET-MKT-001',
        vendorName: 'Balintawak Wet Market - Stall #14 (Aling Nena Meats)',
        vendorTradeName: 'Aling Nena Wet Market Meats',
        vendorContactPerson: 'Elena Bautista (Stall Owner)',
        vendorPhone: '+63 922 411 9044',
        vendorEmail: '',
        vendorAddress: 'Balintawak Public Market, EDSA, Quezon City',
        orderDate: '2026-09-28',
        expectedDelivery: '2026-09-28',
        deliveryLocation: 'Branch 1 - Makati Flagship',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'Cash / Petty Cash',
        amountPaid: 8500.00,
        balanceDue: 0.00,
        paymentReference: 'Cash Slip #4102',
        paymentDate: '2026-09-28',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: 'Immediate cash payment at Balintawak market run for urgent weekend catering beef.',
        approvalNotes: 'Emergency market purchase approved by Head Chef.',
        approvedBy: 'Executive Chef Marco',
        status: 'Fully Received',
        items: [
            { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', specs: 'Wet Market Fresh local Batangas beef cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 20.0, unitPrice: 380.00 },
            { sku: 'WET-BONE', name: 'Beef Marrow Bones', specs: 'Fresh soup bone knuckles', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, unitPrice: 90.00 }
        ]
    },
    {
        poNumber: 'PO-2026-0103',
        poType: 'vendor',
        rfqReference: 'RFQ-2026-0040',
        vendorId: 'VND-ECO-004',
        vendorName: 'EcoPack Solutions Philippines Corp.',
        vendorTradeName: 'EcoPack Packaging',
        vendorContactPerson: 'Grace Villanueva',
        vendorPhone: '+63 920 918 2234',
        vendorEmail: 'sales@ecopack.ph',
        vendorAddress: '14 Industrial Ave, Valenzuela City',
        orderDate: '2026-09-29',
        expectedDelivery: '2026-10-04',
        deliveryLocation: 'Central Warehouse - Dry Storage',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'Bank Transfer / Electronic',
        amountPaid: 17200.00,
        balanceDue: 0.00,
        paymentReference: 'InstaPay Ref 99041285',
        paymentDate: '2026-09-29',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: 'Advance bank transfer paid per terms.',
        approvalNotes: 'Approved for quarterly takeout cup inventory.',
        approvedBy: 'Procurement Specialist',
        status: 'Approved / Issued',
        items: [
            { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', category: 'Packaging', unit: 'Pc', quantity: 2000.0, unitPrice: 4.50 },
            { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', category: 'Packaging', unit: 'Pc', quantity: 1000.0, unitPrice: 8.20 }
        ]
    },
    {
        poNumber: 'PO-2026-0104',
        poType: 'wet_market',
        rfqReference: 'None (Direct Market Purchase)',
        vendorId: 'WET-MKT-002',
        vendorName: "Farmer's Market Cubao - Seafood Section Stall 8",
        vendorTradeName: "Farmer's Market Seafood",
        vendorContactPerson: 'Mang Rogelio',
        vendorPhone: '+63 919 443 1290',
        vendorEmail: '',
        vendorAddress: "Farmer's Market, Araneta City, Cubao, Quezon City",
        orderDate: '2026-09-30',
        expectedDelivery: '2026-09-30',
        deliveryLocation: 'Branch 2 - BGC Bistro',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'GCash / Maya',
        amountPaid: 12400.00,
        balanceDue: 0.00,
        paymentReference: 'GCash Ref 882019451',
        paymentDate: '2026-09-30',
        fundSource: 'Purchaser Cash Advance',
        paymentRemarks: 'Morning market run for fresh tiger prawns and lapu-lapu.',
        approvalNotes: 'Direct payment via purchaser GCash advance.',
        approvedBy: 'Branch Manager',
        status: 'Fully Received',
        items: [
            { sku: 'WET-SEAFOOD-1', name: 'Live Black Tiger Prawns', specs: '20-25 count/kg fresh seafood catch', category: 'Raw Ingredients', unit: 'Kg', quantity: 12.0, unitPrice: 650.00 },
            { sku: 'WET-SEAFOOD-2', name: 'Fresh White Squid Whole', specs: 'Medium fresh local calamares squid', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, unitPrice: 460.00 }
        ]
    }
];

// Master Application Store for PO
window.PoStore = {
    purchaseOrders: [],
    vendors: [],
    itemMaster: [],
    activePo: {
        poNumber: 'PO-2026-0105',
        poType: 'vendor', // 'vendor' or 'wet_market'
        title: '',
        rfqReference: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorContactPerson: '',
        vendorPhone: '',
        vendorEmail: '',
        vendorAddress: '',
        orderDate: '',
        expectedDelivery: '',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        specialNotes: '',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 0.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: '',
        approvalNotes: '',
        approvedBy: 'Procurement Specialist',
        status: 'Draft PO',
        items: []
    }
};

let activePoStatusFilter = 'all';
let activePoApprovalFilter = 'all';
let activePoPaymentFilter = 'all';
let activePoTypeFilter = 'all';
let activePoHeaderFilterType = null;
let poSearchTerm = '';
let poCurrentPage = 1;
let poPageSize = 10;

document.addEventListener('DOMContentLoaded', () => {
    initPoStore();
    initPoColumns();
    initPoDates();
    renderPoVendorDropdown();
    renderPoItemsTable();
    renderPoDirectory();

    // Close header filter popover when clicking outside
    document.addEventListener('click', (e) => {
        const popover = document.getElementById('poHeaderFilterPopover');
        if (popover && popover.style.display !== 'none') {
            if (!popover.contains(e.target) && !e.target.closest('.po-th-filter-wrapper')) {
                closePoHeaderFilter();
            }
        }
    });

    // Hotkey listener (F2: Item Master, F8: Document Preview, F10: Issue PO)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            openItemMasterQuickPicker();
        } else if (e.key === 'F8') {
            e.preventDefault();
            openPoDocumentPreview();
        } else if (e.key === 'F10') {
            e.preventDefault();
            issuePurchaseOrderSubmit();
        } else if (e.key === 'Escape') {
            closeAllPoModals();
        }
    });

    // Close column dropdown on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('poColConfigDropdown');
        const btn = document.getElementById('btnPoColConfig');
        const actionBtn = document.getElementById('btnPoActionColFilter');
        if (dropdown && dropdown.style.display !== 'none' && !dropdown.contains(e.target) && (!btn || !btn.contains(e.target)) && (!actionBtn || !actionBtn.contains(e.target))) {
            dropdown.style.display = 'none';
        }
    });
});

function initPoStore() {
    const serverPos = window.PurchaseOrderConfig?.data?.initialPurchaseOrders || [];
    const serverVendors = window.PurchaseOrderConfig?.data?.initialVendors || [];
    const serverItems = window.PurchaseOrderConfig?.data?.initialItems || [];

    // 1. Load Purchase Orders
    if (Array.isArray(serverPos) && serverPos.length > 0) {
        window.PoStore.purchaseOrders = serverPos.map(p => ({
            id: p.id,
            poNumber: p.po_number,
            poType: p.po_type || 'vendor',
            rfqReference: p.rfq_reference || '',
            vendorId: p.vendor_id || '',
            vendorName: p.vendor_name,
            vendorTradeName: p.vendor_trade_name || p.vendor_name,
            vendorContactPerson: p.vendor_contact_person || '',
            vendorPhone: p.vendor_phone || '',
            vendorEmail: p.vendor_email || '',
            vendorAddress: p.vendor_address || '',
            orderDate: p.order_date ? p.order_date.split('T')[0] : '',
            expectedDelivery: p.expected_delivery ? p.expected_delivery.split('T')[0] : '',
            deliveryLocation: p.delivery_location || 'Central Commissary - Receiving Dock A',
            specialNotes: p.special_notes || '',
            paymentStatus: p.payment_status || 'Unpaid / Credit',
            paymentMethod: p.payment_method || 'Trade Credit (Net 30/15)',
            amountPaid: parseFloat(p.amount_paid || 0),
            balanceDue: parseFloat(p.balance_due || 0),
            paymentReference: p.payment_reference || '',
            paymentDate: p.payment_date || '',
            fundSource: p.fund_source || '',
            paymentRemarks: p.payment_remarks || '',
            approvalNotes: p.approval_notes || '',
            approvedBy: p.approved_by || 'Procurement Specialist',
            status: p.status || 'Approved / Issued',
            items: (p.items || []).map(it => ({
                id: it.id,
                sku: it.sku,
                name: it.item_name,
                specs: it.specifications || '',
                category: it.category || 'Raw Ingredients',
                unit: it.unit || 'Kg',
                quantity: parseFloat(it.quantity_ordered),
                unitPrice: parseFloat(it.unit_price)
            }))
        }));
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    } else {
        const storedPos = localStorage.getItem('rms_purchase_orders');
        if (storedPos) {
            try {
                window.PoStore.purchaseOrders = JSON.parse(storedPos);
            } catch (e) {
                window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS));
            }
        } else {
            window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS));
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    if (!Array.isArray(window.PoStore.purchaseOrders) || window.PoStore.purchaseOrders.length === 0) {
        window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS));
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    }

    // 2. Automatically sync any newly approved / awarded RFQs into PO list
    try {
        syncApprovedRfqsToPoDirectory();
    } catch (e) {
        console.warn('syncApprovedRfqsToPoDirectory call error:', e);
    }

    // 3. Load Vendors
    if (Array.isArray(serverVendors) && serverVendors.length > 0) {
        window.PoStore.vendors = serverVendors.map(v => ({
            id: v.vendor_code || ('VEN-' + v.id),
            vendorCode: v.vendor_code,
            name: v.legal_name,
            legalName: v.legal_name,
            tradeName: v.trade_name || v.legal_name,
            contactPerson: v.contact_person || '',
            phone: v.phone || '',
            email: v.email || '',
            address: v.address || '',
            paymentTerms: v.payment_terms || 'Net 30 Days'
        }));
    } else {
        const storedVendors = localStorage.getItem('rms_vendor_database_v6');
        if (storedVendors) {
            try {
                window.PoStore.vendors = JSON.parse(storedVendors);
            } catch (e) {
                window.PoStore.vendors = [];
            }
        }
    }

    // 4. Load Item Master
    if (Array.isArray(serverItems) && serverItems.length > 0) {
        window.PoStore.itemMaster = serverItems.map(p => ({
            sku: p.sku || 'SKU-' + p.id,
            name: p.name || 'Unnamed Product',
            specs: p.description || p.category_name || '',
            unit: p.unit || 'Unit',
            defaultCost: parseFloat(p.unit_cost || 0),
            category: p.category_name || 'Raw Ingredients'
        }));
    } else {
        const storedProducts = localStorage.getItem('rms_inventory_products');
        if (storedProducts) {
            try {
                const parsed = JSON.parse(storedProducts);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    window.PoStore.itemMaster = parsed.map(p => ({
                        sku: p.sku || 'SKU-' + p.id,
                        name: p.product || p.name || 'Unnamed Product',
                        specs: p.description || p.specs || '',
                        unit: p.uom || 'Unit',
                        defaultCost: parseFloat(p.cost_price || p.cost || 0),
                        category: p.category || 'Raw Ingredients'
                    }));
                } else {
                    window.PoStore.itemMaster = PO_ITEM_MASTER;
                }
            } catch (e) {
                window.PoStore.itemMaster = PO_ITEM_MASTER;
            }
        } else {
            window.PoStore.itemMaster = PO_ITEM_MASTER;
        }
    }

    // Next PO Number
    const count = (window.PoStore.purchaseOrders || []).length;
    const poNum = `PO-2026-${String(count + 105).padStart(4, '0')}`;
    window.PoStore.activePo.poNumber = poNum;
    const poNumberInputEl = document.getElementById('poNumberInput');
    if (poNumberInputEl) poNumberInputEl.value = poNum;
    const poRefDisplayEl = document.getElementById('poRefDisplay');
    if (poRefDisplayEl) poRefDisplayEl.textContent = poNum;
}

function initPoDates() {
    const today = new Date().toISOString().split('T')[0];
    const delDate = new Date();
    delDate.setDate(delDate.getDate() + 3);
    const delDateStr = delDate.toISOString().split('T')[0];

    const orderDateEl = document.getElementById('poOrderDate');
    if (orderDateEl) orderDateEl.value = today;
    const expDelEl = document.getElementById('poExpectedDelivery');
    if (expDelEl) expDelEl.value = delDateStr;
    const payDateEl = document.getElementById('poPaymentDate');
    if (payDateEl) payDateEl.value = today;

    window.PoStore.activePo.orderDate = today;
    window.PoStore.activePo.expectedDelivery = delDateStr;
    window.PoStore.activePo.paymentDate = today;
}

function syncApprovedRfqsToPoDirectory() {
    try {
        const storedRfqs = localStorage.getItem('rms_rfq_directory');
        if (!storedRfqs) return;
        const rfqs = JSON.parse(storedRfqs);
        if (!Array.isArray(rfqs)) return;

        const approvedRfqs = rfqs.filter(r => (r.status === 'Awarded' || r.isApproved === true));
        if (approvedRfqs.length === 0) return;

        const pos = window.PoStore.purchaseOrders || [];
        const existingRfqRefs = new Set(pos.map(p => p.rfqReference).filter(Boolean));
        // Available awarded RFQs can be imported on demand via openImportRfqModal
    } catch (e) {
        console.warn('syncApprovedRfqsToPoDirectory error:', e);
    }
}

let poVendorComboSearchTerm = '';

function renderPoVendorDropdown() {
    const select = document.getElementById('poVendorSelect');
    const vendors = window.PoStore.vendors || [];

    if (select) {
        select.innerHTML = `
            <option value="">-- Choose Approved Vendor from Masterlist --</option>
            ${vendors.map(v => `
                <option value="${v.id || v.code}">[${v.code || v.id}] ${v.legalName || v.tradeName} (${v.category || 'General'})</option>
            `).join('')}
        `;
    }

    renderPoVendorComboboxList();
}

function renderPoVendorComboboxList() {
    const listEl = document.getElementById('poVendorComboList');
    if (!listEl) return;

    const vendors = window.PoStore.vendors || [];
    const q = (poVendorComboSearchTerm || '').toLowerCase().trim();

    const filtered = vendors.filter(v => {
        if (!q) return true;
        const code = (v.code || v.id || '').toLowerCase();
        const trade = (v.tradeName || '').toLowerCase();
        const legal = (v.legalName || '').toLowerCase();
        const cat = (v.category || '').toLowerCase();
        const contact = (v.contacts && v.contacts[0] ? v.contacts[0].name : '').toLowerCase();
        return code.includes(q) || trade.includes(q) || legal.includes(q) || cat.includes(q) || contact.includes(q);
    });

    const activeId = document.getElementById('poVendorSelect') ? document.getElementById('poVendorSelect').value : '';

    if (filtered.length === 0) {
        listEl.innerHTML = `<div class="po-vendor-combo-empty">No suppliers match "${escapeHtml(poVendorComboSearchTerm)}"</div>`;
        return;
    }

    let itemsHtml = '';
    if (activeId) {
        itemsHtml += `
            <div class="po-vendor-combo-item" onclick="selectPoVendorFromCombo('')" style="color: #ef4444; border-bottom: 1px dashed #f1f5f9;">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-x-circle"></i> Clear Selected Vendor
                </span>
            </div>
        `;
    }

    itemsHtml += filtered.map(v => {
        const vId = v.id || v.code;
        const isSelected = vId === activeId;
        return `
            <div class="po-vendor-combo-item ${isSelected ? 'is-selected' : ''}" onclick="selectPoVendorFromCombo('${vId}')">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    <div style="width: 22px; height: 22px; border-radius: 5px; background: linear-gradient(135deg, #0284c7, #0d9488); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        ${(v.tradeName || v.legalName || 'V').substring(0, 1).toUpperCase()}
                    </div>
                    <div style="min-width: 0; line-height: 1.25;">
                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(v.tradeName || v.legalName)}
                        </div>
                        <div style="font-size: 0.72rem; color: #64748b;">
                            <span style="font-family: monospace; font-weight: 700;">${escapeHtml(v.code || v.id)}</span> • ${escapeHtml(v.category || 'General')}
                        </div>
                    </div>
                </div>
                ${isSelected ? '<i class="ph ph-check" style="color: var(--po-primary); font-size: 14px; flex-shrink: 0;"></i>' : ''}
            </div>
        `;
    }).join('');

    listEl.innerHTML = itemsHtml;
}

function togglePoVendorCombobox(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (!dropdown || !trigger) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        trigger.classList.remove('is-active');
    } else {
        dropdown.classList.add('is-active');
        trigger.classList.add('is-active');
        const input = document.getElementById('poVendorSearchInput');
        if (input) {
            input.value = '';
            poVendorComboSearchTerm = '';
            renderPoVendorComboboxList();
            setTimeout(() => input.focus(), 50);
        }
    }
}

function filterPoVendorCombobox(query) {
    poVendorComboSearchTerm = query || '';
    renderPoVendorComboboxList();
}

function selectPoVendorFromCombo(vendorId) {
    const select = document.getElementById('poVendorSelect');
    if (select) {
        select.value = vendorId;
    }
    handlePoVendorSelect(vendorId);

    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (dropdown) dropdown.classList.remove('is-active');
    if (trigger) trigger.classList.remove('is-active');
}

function updatePoVendorSummaryCard(vendor) {
    const summaryBox = document.getElementById('poVendorSummaryBox');
    const triggerText = document.getElementById('poVendorComboTriggerText');

    if (!vendor) {
        if (summaryBox) summaryBox.style.display = 'none';
        if (triggerText) {
            triggerText.textContent = '-- Choose Approved Vendor from Masterlist --';
            triggerText.style.color = '#64748b';
            triggerText.style.fontWeight = 'normal';
        }
        return;
    }

    if (triggerText) {
        triggerText.innerHTML = `<strong>[${vendor.code || vendor.id}]</strong> ${escapeHtml(vendor.tradeName || vendor.legalName)}`;
        triggerText.style.color = '#0f172a';
        triggerText.style.fontWeight = '600';
    }

    if (summaryBox) {
        summaryBox.style.display = 'flex';
        const tradeName = vendor.tradeName || vendor.legalName || 'Supplier Partner';
        document.getElementById('poVendorTradeName').textContent = tradeName;
        document.getElementById('poVendorCategoryTag').textContent = vendor.category || 'General';
        document.getElementById('poVendorTinDisplay').textContent = vendor.tin || '000-000-000-000';
        document.getElementById('poVendorLocationDisplay').textContent = vendor.address || vendor.city || 'Metro Manila, Philippines';
        document.getElementById('poVendorAvatarText').textContent = tradeName.substring(0, 2).toUpperCase();
    }
}

function switchPoTab(tabId) {
    if (tabId === 'tab-po-audit') {
        openPoAuditDrawer();
        return;
    }

    const btnList = document.getElementById('tabBtnPoList');
    const btnBuilder = document.getElementById('tabBtnPoBuilder');
    const paneList = document.getElementById('pane-tab-po-list');
    const paneBuilder = document.getElementById('pane-tab-po-builder');

    [btnList, btnBuilder].forEach(b => b && b.classList.remove('active'));
    [paneList, paneBuilder].forEach(p => p && (p.style.display = 'none'));

    const pos = window.PoStore.purchaseOrders || [];
    const countBadge = document.getElementById('tabPoCount');
    if (countBadge) countBadge.textContent = pos.length;

    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge && typeof getProcurementAuditTrail === 'function') {
        const { poNum, rfqNum } = (typeof getCurrentPoAuditTarget === 'function') ? getCurrentPoAuditTarget() : {};
        const logs = getProcurementAuditTrail();
        if (poNum) {
            const scoped = logs.filter(l => l.refNumber === poNum || (rfqNum && l.refNumber === rfqNum));
            auditCountBadge.textContent = scoped.length;
        } else {
            auditCountBadge.textContent = logs.filter(l => l.module === 'PO').length;
        }
    }

    if (tabId === 'tab-po-list') {
        if (btnList) btnList.classList.add('active');
        if (paneList) paneList.style.display = 'flex';
        renderPoDirectory();
    } else if (tabId === 'tab-po-builder') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) paneBuilder.style.display = 'flex';
    }
}

/**
 * PO Sourcing Mode: Standard Vendor vs Direct Wet Market Cash Purchase
 */
function setPoMode(mode) {
    window.PoStore.activePo.poType = mode;
    const btnVendor = document.getElementById('btnModeVendor');
    const btnMarket = document.getElementById('btnModeMarket');
    const secVendor = document.getElementById('sectionVendorSource');
    const secMarket = document.getElementById('sectionMarketSource');
    const titleEl = document.getElementById('sourceCardTitle');
    const badgeEl = document.getElementById('sourceTypeBadge');
    const iconEl = document.getElementById('sourceCardIcon');

    if (mode === 'wet_market') {
        btnMarket.classList.add('active');
        btnVendor.classList.remove('active');
        secMarket.style.display = 'block';
        secVendor.style.display = 'none';
        titleEl.textContent = 'Wet Market Stall & Purchaser Sourcing';
        badgeEl.textContent = 'Wet Market Cash Run';
        badgeEl.style.background = '#fef3c7';
        badgeEl.style.color = '#92400e';
        iconEl.className = 'ph ph-basket';

        // Auto default to Cash / Petty Cash and Paid in Full
        document.getElementById('poPaymentStatus').value = 'Paid in Full / Cash Out';
        document.getElementById('poPaymentMethod').value = 'Cash / Petty Cash';
        document.getElementById('poFundSource').value = 'Branch Petty Cash Fund';
        handlePaymentStatusChange('Paid in Full / Cash Out');
        showToast('Switched to Direct Wet Market Cash Purchase mode', 'success');
    } else {
        btnVendor.classList.add('active');
        btnMarket.classList.remove('active');
        secVendor.style.display = 'block';
        secMarket.style.display = 'none';
        titleEl.textContent = 'Vendor & Partner Profile';
        badgeEl.textContent = 'Approved Vendor';
        badgeEl.style.background = 'var(--po-primary-subtle)';
        badgeEl.style.color = 'var(--po-primary-dark)';
        iconEl.className = 'ph ph-buildings';

        document.getElementById('poPaymentStatus').value = 'Unpaid / Credit';
        document.getElementById('poPaymentMethod').value = 'Trade Credit (Net 30/15)';
        handlePaymentStatusChange('Unpaid / Credit');
    }
}

function handlePoVendorSelect(vendorId) {
    if (!vendorId) {
        document.getElementById('poContactPerson').value = '';
        document.getElementById('poContactTitle').value = '';
        document.getElementById('poContactPhone').value = '';
        document.getElementById('poContactEmail').value = '';
        document.getElementById('poFullAddress').value = '';
        window.PoStore.activePo.vendorId = '';
        window.PoStore.activePo.vendorName = '';
        window.PoStore.activePo.vendorTradeName = '';
        updatePoVendorSummaryCard(null);
        renderPoVendorComboboxList();
        return;
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    document.getElementById('poContactPerson').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].name : '';
    document.getElementById('poContactTitle').value = (vendor.contacts && vendor.contacts[0]) ? (vendor.contacts[0].title || vendor.contacts[0].designation || 'Account Representative') : 'Account Manager';
    document.getElementById('poContactPhone').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].phone : '';
    document.getElementById('poContactEmail').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].email : (vendor.email || '');
    document.getElementById('poFullAddress').value = vendor.address || '';

    window.PoStore.activePo.vendorId = vendor.id || vendor.code;
    window.PoStore.activePo.vendorName = vendor.legalName || vendor.tradeName;
    window.PoStore.activePo.vendorTradeName = vendor.tradeName || vendor.legalName;

    updatePoVendorSummaryCard(vendor);
    renderPoVendorComboboxList();
    showToast(`✓ Selected vendor ${vendor.tradeName || vendor.legalName}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * FINANCIAL PAYMENT CALCULATIONS & STATUS HANDLERS
 * --------------------------------------------------------------------------
 */
function handlePaymentStatusChange(status) {
    const badge = document.getElementById('paymentStatusBadge') || document.getElementById('poSummaryPayBadge');
    const gross = calculatePoGrossTotal();
    const inlineAmt = document.getElementById('poAmountPaid');

    if (status === 'Paid in Full / Cash Out') {
        if (badge) {
            badge.className = 'pay-badge paid';
            badge.textContent = 'Paid in Full';
        }
        if (inlineAmt) inlineAmt.value = gross.toFixed(2);
        window.PoStore.activePo.amountPaid = gross;
    } else if (status === 'Partial Payment') {
        if (badge) {
            badge.className = 'pay-badge partial';
            badge.textContent = 'Partial Payment';
        }
        if (inlineAmt) inlineAmt.value = (gross / 2).toFixed(2);
        window.PoStore.activePo.amountPaid = (gross / 2);
    } else {
        if (badge) {
            badge.className = 'pay-badge unpaid';
            badge.textContent = 'Unpaid / Credit';
        }
        if (inlineAmt) inlineAmt.value = '0.00';
        window.PoStore.activePo.amountPaid = 0;
    }
    calculateBalanceDue();
}

function handlePoInlineAmountPaid(val) {
    const paid = parseFloat(val) || 0;
    window.PoStore.activePo.amountPaid = paid;
    const gross = calculatePoGrossTotal();
    const balance = Math.max(0, gross - paid);
    window.PoStore.activePo.balanceDue = balance;

    const statusInput = document.getElementById('poPaymentStatus');
    let statusText = 'Unpaid / Credit';
    if (paid >= gross && gross > 0) {
        statusText = 'Paid in Full / Cash Out';
    } else if (paid > 0) {
        statusText = 'Partial Payment';
    }
    if (statusInput) statusInput.value = statusText;
    window.PoStore.activePo.paymentStatus = statusText;

    updatePoSummaryPayBadge();
    const docBadge = document.getElementById('docPoPaymentStatus');
    if (docBadge) docBadge.textContent = statusText;
}

function calculateBalanceDue() {
    const gross = calculatePoGrossTotal();
    const inlinePaid = document.getElementById('poAmountPaid') ? parseFloat(document.getElementById('poAmountPaid').value) : null;
    const paid = (inlinePaid !== null && !isNaN(inlinePaid)) ? inlinePaid : (parseFloat(window.PoStore.activePo.amountPaid) || 0);
    const balance = Math.max(0, gross - paid);
    const balInput = document.getElementById('poBalanceDue');
    if (balInput) balInput.value = `₱${formatMoney(balance)}`;

    const badge = document.getElementById('paymentStatusBadge') || document.getElementById('poSummaryPayBadge');
    if (badge) {
        if (paid >= gross && gross > 0) {
            badge.className = 'pay-badge paid';
            badge.textContent = 'Paid in Full';
        } else if (paid > 0 && paid < gross) {
            badge.className = 'pay-badge partial';
            badge.textContent = `Partial (${formatMoney(paid)})`;
        } else {
            badge.className = 'pay-badge unpaid';
            badge.textContent = 'Unpaid / Credit';
        }
    }
}

function calculatePoGrossTotal() {
    const items = window.PoStore.activePo.items || [];
    return items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
}

/**
 * --------------------------------------------------------------------------
 * LINE ITEMS MANAGEMENT
 * --------------------------------------------------------------------------
 */
function renderPoItemsTable() {
    const tbody = document.getElementById('poItemsTbody');
    const items = window.PoStore.activePo.items;
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 42px; color: var(--po-text-muted);">
                    <i class="ph ph-shopping-bag-open" style="font-size: 38px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--po-text-strong);">No Line Items in Purchase Order</div>
                    <div style="font-size: 0.8rem; margin-top: 4px;">Click <strong>"Select Master" (F2)</strong> or <strong>"Add Custom Row"</strong> to input items.</div>
                </td>
            </tr>
        `;
        updatePoTotals();
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        const lineTotal = (parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0);
        return `
            <tr>
                <td class="td-center" style="font-weight: 700; color: var(--po-text-muted);">${idx + 1}</td>
                <td>
                    <input type="text" value="${escapeHtml(it.name || '')}" placeholder="Item Description / Cut / Brand" onchange="updatePoItemField(${idx}, 'name', this.value)" required style="font-weight: 600;">
                </td>
                <td class="td-center" style="min-width: 135px;">
                    <select data-uom-select onchange="updatePoItemField(${idx}, 'unit', this.value)" style="text-align: center;">
                        ${(() => {
                            if (window.UomRegistry && window.UomRegistry.isLoaded) {
                                const units = window.UomRegistry.getUnits();
                                let opts = units.map(u => `<option value="${u.uom_code}" ${String(it.unit).toUpperCase() === u.uom_code.toUpperCase() ? 'selected' : ''}>${u.uom_code}</option>`).join('');
                                if (it.unit && !units.some(u => u.uom_code.toUpperCase() === String(it.unit).toUpperCase())) {
                                    opts = `<option value="${it.unit}" selected>${it.unit}</option>` + opts;
                                }
                                return opts;
                            }
                            const fallbackUnits = ['KG', 'L', 'PCS', 'BOX', 'PACK', 'TRAY', 'SACK'];
                            return fallbackUnits.map(u => `<option value="${u}" ${String(it.unit).toUpperCase() === u ? 'selected' : ''}>${u}</option>`).join('');
                        })()}
                    </select>
                </td>
                <td class="td-num">
                    <input type="number" step="0.01" min="0.01" value="${it.quantity || 1}" onchange="updatePoItemField(${idx}, 'quantity', this.value)" style="text-align: right; font-weight: 700;">
                </td>
                <td class="td-num">
                    <input type="number" step="0.01" min="0" value="${it.unitPrice !== undefined ? it.unitPrice : 0}" onchange="updatePoItemField(${idx}, 'unitPrice', this.value)" style="text-align: right; font-weight: 700;">
                </td>
                <td class="td-num" style="font-weight: 800; color: var(--po-text-strong);">
                    ₱${formatMoney(lineTotal)}
                </td>
                <td class="td-center">
                    <button type="button" class="inv-table-filter-btn" style="color: var(--po-danger);" onclick="deletePoItemRow(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    updatePoTotals();
    window.UomRegistry?.autoHydrate();
}

function updatePoItemField(idx, field, val) {
    const item = window.PoStore.activePo.items[idx];
    if (!item) return;

    if (field === 'quantity' || field === 'unitPrice') {
        item[field] = parseFloat(val) || 0;
    } else {
        item[field] = val;
    }

    renderPoItemsTable();
}

function addNewBlankPoItemRow() {
    window.PoStore.activePo.items.push({
        sku: 'WET-MKT',
        name: '',
        specs: '',
        category: 'Raw Ingredients',
        unit: 'Kg',
        quantity: 1.00,
        unitPrice: 0.00
    });
    renderPoItemsTable();
}

function deletePoItemRow(idx) {
    window.PoStore.activePo.items.splice(idx, 1);
    renderPoItemsTable();
}

function clearAllPoItems() {
    if (confirm('Clear all items from this Purchase Order?')) {
        window.PoStore.activePo.items = [];
        renderPoItemsTable();
    }
}

function updatePoTotals() {
    const items = window.PoStore.activePo.items;
    const totalLines = items.length;
    let totalUnits = 0;
    let totalGross = 0;

    items.forEach(it => {
        const q = parseFloat(it.quantity) || 0;
        const p = parseFloat(it.unitPrice) || 0;
        totalUnits += q;
        totalGross += (q * p);
    });

    document.getElementById('summaryPoLines').textContent = totalLines;
    document.getElementById('summaryPoUnits').textContent = totalUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryPoGross').textContent = `₱${formatMoney(totalGross)}`;
    document.getElementById('poItemCountBadge').textContent = `${totalLines} Line Item${totalLines === 1 ? '' : 's'}`;

    calculateBalanceDue();
    updatePoSummaryPayBadge();
}

/**
 * --------------------------------------------------------------------------
 * ITEM MASTER QUICK PICKER
 * --------------------------------------------------------------------------
 */
function openItemMasterQuickPicker() {
    const modal = document.getElementById('poItemMasterModal');
    if (!modal) return;
    renderPoItemMasterRows(window.PoStore.itemMaster);
    modal.classList.add('is-open');
}

function closeItemMasterQuickPicker() {
    const modal = document.getElementById('poItemMasterModal');
    if (modal) modal.classList.remove('is-open');
}

function filterPoItemMaster(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderPoItemMasterRows(window.PoStore.itemMaster);
        return;
    }
    const filtered = window.PoStore.itemMaster.filter(it => 
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.name && it.name.toLowerCase().includes(q)) ||
        (it.specs && it.specs.toLowerCase().includes(q))
    );
    renderPoItemMasterRows(filtered);
}

function renderPoItemMasterRows(items) {
    const tbody = document.getElementById('poItemMasterTbody');
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No products found</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(it => `
        <tr>
            <td style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">${escapeHtml(it.sku)}</td>
            <td>
                <div style="font-weight: 700;">${escapeHtml(it.name)}</div>
                <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(it.specs || '')}</div>
            </td>
            <td><span class="po-label-badge">${escapeHtml(it.category || 'Raw')}</span></td>
            <td style="text-align: center;">${escapeHtml(it.unit || 'Kg')}</td>
            <td style="text-align: right; font-weight: 700;">₱${formatMoney(it.defaultCost || 0)}</td>
            <td style="text-align: center;">
                <button type="button" class="po-btn po-btn-teal" style="padding: 4px 10px; font-size: 0.74rem;" onclick="addMasterItemToPoDirect('${it.sku}')">
                    + Add
                </button>
            </td>
        </tr>
    `).join('');
}

function addMasterItemToPoDirect(sku) {
    const master = window.PoStore.itemMaster.find(it => it.sku === sku);
    if (!master) return;

    window.PoStore.activePo.items.push({
        sku: master.sku,
        name: master.name,
        specs: master.specs || '',
        category: master.category || 'Raw Ingredients',
        unit: master.unit || 'Kg',
        quantity: 10.00,
        unitPrice: parseFloat(master.defaultCost) || 0
    });

    renderPoItemsTable();
    showToast(`✓ Added [${master.sku}] ${master.name}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * PO ISSUANCE & DIRECTORY TRACKING
 * --------------------------------------------------------------------------
 */
function issuePurchaseOrderSubmit() {
    const po = window.PoStore.activePo;
    const isMarket = po.poType === 'wet_market';

    const sourceName = isMarket ? 
        document.getElementById('poMarketStallName').value.trim() : 
        (po.vendorTradeName || po.vendorName);

    if (!sourceName) {
        showToast(isMarket ? 'Please enter the wet market stall name' : 'Please select an approved vendor', 'error');
        return;
    }

    if (po.items.length === 0) {
        showToast('Please add at least one line item to the purchase order', 'error');
        return;
    }

    // Collect inputs
    po.vendorName = sourceName;
    po.vendorTradeName = sourceName;
    po.vendorContactPerson = document.getElementById('poContactPerson').value.trim();
    po.vendorPhone = document.getElementById('poContactPhone').value.trim();
    po.vendorEmail = document.getElementById('poContactEmail').value.trim();
    po.vendorAddress = document.getElementById('poFullAddress').value.trim() || document.getElementById('poSourceLocation').value.trim();
    po.orderDate = document.getElementById('poOrderDate').value;
    po.expectedDelivery = document.getElementById('poExpectedDelivery').value;
    po.rfqReference = document.getElementById('poRfqReference').value.trim() || 'Direct Market Purchase';
    po.deliveryLocation = document.getElementById('poDeliveryLocation').value;
    po.specialNotes = document.getElementById('poSpecialNotes').value.trim();

    // Financial payment collection
    po.paymentStatus = document.getElementById('poPaymentStatus')?.value || window.PoStore.activePo.paymentStatus || 'Unpaid / Credit';
    po.paymentMethod = document.getElementById('poPaymentMethod')?.value || window.PoStore.activePo.paymentMethod || 'Trade Credit (Net 30/15)';
    po.amountPaid = parseFloat(document.getElementById('poAmountPaid')?.value ?? window.PoStore.activePo.amountPaid) || 0;
    po.balanceDue = Math.max(0, calculatePoGrossTotal() - po.amountPaid);
    po.paymentReference = document.getElementById('poPaymentRef')?.value?.trim() || window.PoStore.activePo.paymentReference || '';
    po.paymentDate = document.getElementById('poPaymentDate')?.value || window.PoStore.activePo.paymentDate || '';
    po.fundSource = document.getElementById('poFundSource')?.value || window.PoStore.activePo.fundSource || 'Main Commissary Checking Acct';
    po.paymentRemarks = document.getElementById('poPaymentRemarks')?.value?.trim() || window.PoStore.activePo.paymentRemarks || '';
    po.status = (po.paymentStatus === 'Paid in Full / Cash Out') ? 'Fully Received' : 'Approved / Issued';

    // Save to list
    const existingIdx = window.PoStore.purchaseOrders.findIndex(p => p.poNumber === po.poNumber);
    const grossTotal = calculatePoGrossTotal();

    if (existingIdx >= 0) {
        window.PoStore.purchaseOrders[existingIdx] = JSON.parse(JSON.stringify(po));
        logProcurementAudit(
            'PO',
            po.poNumber,
            'EDITED',
            'Purchase Order Updated',
            `Updated line items, payment terms, or delivery specs for ${po.vendorTradeName || po.vendorName}.`,
            [
                { label: 'Supplier / Stall', value: po.vendorTradeName || po.vendorName },
                { label: 'Line Items', value: `${(po.items || []).length} items` },
                { label: 'Grand Total', value: `₱${formatMoney(grossTotal)}` }
            ]
        );
    } else {
        window.PoStore.purchaseOrders.unshift(JSON.parse(JSON.stringify(po)));
        if (po.poType === 'wet_market') {
            logProcurementAudit(
                'PO',
                po.poNumber,
                'WET_MARKET_CREATED',
                'Direct Wet Market Cash Run Issued',
                `Custom PO created for wet market run at ${po.vendorName} without RFQ. Total: ₱${formatMoney(grossTotal)}.`,
                [
                    { label: 'Market Stall', value: po.vendorName },
                    { label: 'Designated Purchaser', value: po.vendorContactPerson || 'Kitchen Staff' },
                    { label: 'Line Items', value: `${(po.items || []).length} fresh goods` },
                    { label: 'Order Total', value: `₱${formatMoney(grossTotal)}` }
                ]
            );
        } else {
            logProcurementAudit(
                'PO',
                po.poNumber,
                'CREATED',
                'Purchase Order Created',
                `Standard vendor PO issued for ${po.vendorTradeName || po.vendorName}. Total: ₱${formatMoney(grossTotal)}.`,
                [
                    { label: 'Vendor Partner', value: po.vendorTradeName || po.vendorName },
                    { label: 'Line Items', value: `${(po.items || []).length} items` },
                    { label: 'Order Total', value: `₱${formatMoney(grossTotal)}` }
                ]
            );
        }
    }

    // Financial payment audit tracking if payment details recorded
    if (po.amountPaid > 0 || po.paymentStatus !== 'Unpaid / Credit') {
        logProcurementAudit(
            'PO',
            po.poNumber,
            'PAYMENT_RECORDED',
            'Payment Settlement Recorded',
            `Financial settlement recorded: ${po.paymentStatus}. Amount Paid: ₱${formatMoney(po.amountPaid)} via ${po.paymentMethod}.`,
            [
                { label: 'Payment Status', value: po.paymentStatus },
                { label: 'Amount Paid', value: `₱${formatMoney(po.amountPaid)}` },
                { label: 'Balance Due', value: `₱${formatMoney(po.balanceDue)}` },
                { label: 'Fund Source', value: po.fundSource },
                { label: 'Payment Method', value: po.paymentMethod },
                ...(po.paymentReference ? [{ label: 'Official Receipt #', value: po.paymentReference }] : [])
            ]
        );
    }

    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    // Asynchronously synchronize issued PO to database backend
    fetch(window.PurchaseOrderConfig?.routes?.ordersCreate || '/purchase/api/orders/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            po_number: po.poNumber,
            po_type: po.poType,
            rfq_reference: po.rfqReference,
            vendor_id: po.vendorId,
            vendor_name: po.vendorName,
            vendor_trade_name: po.vendorTradeName,
            vendor_contact_person: po.vendorContactPerson,
            vendor_phone: po.vendorPhone,
            vendor_email: po.vendorEmail,
            vendor_address: po.vendorAddress,
            order_date: po.orderDate,
            expected_delivery: po.expectedDelivery,
            delivery_location: po.deliveryLocation,
            special_notes: po.specialNotes,
            payment_status: po.paymentStatus,
            payment_method: po.paymentMethod,
            amount_paid: po.amountPaid,
            balance_due: po.balanceDue,
            payment_reference: po.paymentReference,
            payment_date: po.paymentDate,
            fund_source: po.fundSource,
            payment_remarks: po.paymentRemarks,
            approval_notes: po.approvalNotes,
            approved_by: po.approvedBy,
            status: po.status,
            items: (po.items || []).map(i => ({
                sku: i.sku,
                name: i.name,
                specs: i.specs,
                category: i.category,
                unit: i.unit,
                quantity: i.quantity,
                unitPrice: i.unitPrice
            }))
        })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            console.log('PO database synchronization confirmed:', res.data?.po?.po_number);
        }
    }).catch(e => console.warn('PO DB sync note:', e));

    showToast(`✓ Purchase Order ${po.poNumber} successfully issued!`, 'success');
    switchPoTab('tab-po-list');
}

/**
 * --------------------------------------------------------------------------
 * PO DIRECTORY COLUMN CONFIGURATION & PAGINATION SYSTEM
 * --------------------------------------------------------------------------
 */
const PO_DIRECTORY_COLUMNS = [
    { id: 'ref', label: 'Ref #', default: true, lockVisible: true, defaultWidth: '140px', align: 'left' },
    { id: 'vendor', label: 'Vendor & Contact', default: true, lockVisible: true, defaultWidth: '220px', align: 'left' },
    { id: 'dates', label: 'Order / Delivery Date', default: true, lockVisible: false, defaultWidth: '140px', align: 'left' },
    { id: 'destination', label: 'Ship To / Warehouse', default: true, lockVisible: false, defaultWidth: '170px', align: 'left' },
    { id: 'items', label: 'Line Items', default: true, lockVisible: false, defaultWidth: '110px', align: 'center' },
    { id: 'payment', label: 'Payment / Terms', default: true, lockVisible: false, defaultWidth: '145px', align: 'center' },
    { id: 'status', label: 'Status', default: true, lockVisible: false, defaultWidth: '130px', align: 'center' },
    { id: 'actions', label: 'Actions', default: true, lockVisible: true, defaultWidth: '150px', align: 'center' }
];

let activePoColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_cols_v3');
        if (saved) {
            const parsed = JSON.parse(saved);
            const validIds = PO_DIRECTORY_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length >= 6 && parsed.every(id => validIds.includes(id))) {
                return parsed;
            }
        }
    } catch (e) {}
    return PO_DIRECTORY_COLUMNS.map(c => c.id);
})();

let savedPoColWidths = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_col_widths_v2');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return {};
})();

function getPoColWidth(col) {
    return savedPoColWidths[col.id] || col.defaultWidth || '120px';
}

function initPoColumns() {
    renderPoTableHeader();
    renderPoColumnChecklist();
}

function renderPoTableHeader() {
    const theadRow = document.getElementById('poDirectoryTheadRow');
    if (!theadRow) return;

    const isStatusFiltered = (activePoStatusFilter !== 'all' || activePoApprovalFilter !== 'all');
    const isPaymentFiltered = (activePoPaymentFilter !== 'all' || activePoTypeFilter !== 'all');

    let thHtml = '';
    PO_DIRECTORY_COLUMNS.forEach(col => {
        if (!activePoColIds.includes(col.id)) return;
        const w = getPoColWidth(col);
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');

        if (col.id === 'status') {
            thHtml += `
                <th class="${alignClass}" data-col-id="status" style="width: ${w}; position: relative; user-select: none;">
                    <div class="po-th-filter-wrapper" onclick="togglePoHeaderFilter(event, 'status')" title="Click to filter by Approval Status & Order Status">
                        <span>${col.label}</span>
                        <button type="button" class="po-th-funnel-btn ${isStatusFiltered ? 'is-active' : ''}">
                            <i class="ph ph-funnel"></i>
                            ${isStatusFiltered ? '<span class="po-th-funnel-dot"></span>' : ''}
                        </button>
                    </div>
                    <div class="po-col-resizer" onmousedown="initPoColResize(event, '${col.id}')"></div>
                </th>
            `;
        } else if (col.id === 'payment') {
            thHtml += `
                <th class="${alignClass}" data-col-id="payment" style="width: ${w}; position: relative; user-select: none;">
                    <div class="po-th-filter-wrapper" onclick="togglePoHeaderFilter(event, 'payment')" title="Click to filter by Payment & Terms">
                        <span>${col.label}</span>
                        <button type="button" class="po-th-funnel-btn ${isPaymentFiltered ? 'is-active' : ''}">
                            <i class="ph ph-funnel"></i>
                            ${isPaymentFiltered ? '<span class="po-th-funnel-dot"></span>' : ''}
                        </button>
                    </div>
                    <div class="po-col-resizer" onmousedown="initPoColResize(event, '${col.id}')"></div>
                </th>
            `;
        } else if (col.id === 'actions') {
            thHtml += `
                <th class="${alignClass}" data-col-id="actions" style="width: ${w}; position: relative; user-select: none;">
                    <div class="inv-header-action-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <span>Action</span>
                        <button type="button" class="inv-table-filter-btn" id="btnPoActionColFilter" onclick="togglePoColumnConfigDropdown(event)" title="Column Display Filter" style="width: 22px; height: 22px; font-size: 11px; border-radius: 5px; color: #0284c7; background: rgba(2, 132, 199, 0.10); border: 1px solid rgba(2, 132, 199, 0.25);">
                            <i class="ph ph-funnel"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            thHtml += `
                <th class="${alignClass}" data-col-id="${col.id}" style="width: ${w}; position: relative; user-select: none;">
                    <span>${col.label}</span>
                    <div class="po-col-resizer" onmousedown="initPoColResize(event, '${col.id}')"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = thHtml;
    renderPoColumnChecklist();
    renderPoActiveFilterChips();
}

function renderPoColumnChecklist() {
    const listEl = document.getElementById('poColChecklist');
    if (!listEl) return;

    listEl.innerHTML = PO_DIRECTORY_COLUMNS.map(col => {
        const isChecked = activePoColIds.includes(col.id);
        const isDisabled = col.lockVisible ? 'disabled' : '';
        return `
            <label class="inv-col-item-row" title="${col.label}" style="display: flex; align-items: center; justify-content: space-between; padding: 5px 8px; font-size: 12px; border-radius: 6px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                <span style="display: flex; align-items: center; gap: 7px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${isChecked ? '600' : '400'};">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="togglePoColumnVisibility('${col.id}', this.checked)" style="accent-color: #9333ea; width: 14px; height: 14px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                    ${col.label}
                </span>
                ${col.lockVisible ? '<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">(Locked)</span>' : ''}
            </label>
        `;
    }).join('');

    const counter = document.getElementById('poColActiveCounter');
    if (counter) {
        counter.textContent = `${activePoColIds.length} of ${PO_DIRECTORY_COLUMNS.length} visible`;
    }
}

function togglePoColumnConfigDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('poColConfigDropdown');
    const btn = document.getElementById('btnPoColConfig');
    if (!dropdown) return;

    const isVisible = dropdown.style.display !== 'none';
    dropdown.style.display = isVisible ? 'none' : 'block';
    if (btn) btn.classList.toggle('active', !isVisible);
}

function togglePoColumnVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activePoColIds.includes(colId)) {
            activePoColIds.push(colId);
        }
    } else {
        activePoColIds = activePoColIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_po_directory_cols_v3', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
}

function showAllPoColumns() {
    activePoColIds = PO_DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_po_directory_cols_v3', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ All columns visible', 'success');
}

function resetPoColumnDefaults() {
    activePoColIds = PO_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    localStorage.setItem('rms_po_directory_cols_v3', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ Reset to default columns', 'success');
}

function resetPoColumns() {
    resetPoColumnDefaults();
}

function resetPoColumnWidths() {
    savedPoColWidths = {};
    localStorage.removeItem('rms_po_directory_col_widths_v2');
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ Column widths reset to defaults', 'success');
}

// Column Header Resizer Logic
let poResizingColId = null;
let poStartX = 0;
let poStartW = 0;

function initPoColResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    poResizingColId = colId;
    poStartX = e.pageX;

    const thEl = document.querySelector(`#poDirectoryTheadRow th[data-col-id="${colId}"]`);
    poStartW = thEl ? thEl.offsetWidth : 120;

    document.addEventListener('mousemove', handlePoColMouseMove);
    document.addEventListener('mouseup', handlePoColMouseUp);
}

function handlePoColMouseMove(e) {
    if (!poResizingColId) return;
    const diff = e.pageX - poStartX;
    const newWidth = Math.max(60, poStartW + diff);
    savedPoColWidths[poResizingColId] = `${newWidth}px`;

    const thEl = document.querySelector(`#poDirectoryTheadRow th[data-col-id="${poResizingColId}"]`);
    if (thEl) {
        thEl.style.width = `${newWidth}px`;
    }
}

function handlePoColMouseUp() {
    if (!poResizingColId) return;
    localStorage.setItem('rms_po_directory_col_widths', JSON.stringify(savedPoColWidths));
    poResizingColId = null;
    document.removeEventListener('mousemove', handlePoColMouseMove);
    document.removeEventListener('mouseup', handlePoColMouseUp);
}

function syncPoFilterPillActive(filterVal) {
    const bar = document.getElementById('poStatusFilterBar');
    if (!bar) return;
    bar.querySelectorAll('.po-filter-pill').forEach(p => {
        const val = p.getAttribute('data-filter');
        if (val === filterVal) {
            p.classList.add('active');
        } else {
            p.classList.remove('active');
        }
    });
}

function renderPoDirectory() {
    const tbody = document.getElementById('poDirectoryTbody');
    const pos = window.PoStore.purchaseOrders || [];
    if (!tbody) return;

    // Filter
    const filtered = pos.filter(p => {
        const matchesStatus = (activePoStatusFilter === 'all') || (p.status === activePoStatusFilter);
        
        let matchesApproval = true;
        if (activePoApprovalFilter === 'approved') {
            matchesApproval = (p.status === 'Approved / Issued' || p.status === 'Partially Received' || p.status === 'Fully Received' || p.isApproved === true);
        } else if (activePoApprovalFilter === 'pending') {
            matchesApproval = (p.status === 'Draft PO' || p.status === 'Draft' || p.status === 'Pending Approval');
        }

        const matchesPayment = (activePoPaymentFilter === 'all') || 
            (p.paymentStatus && p.paymentStatus.toLowerCase().includes(activePoPaymentFilter.toLowerCase()));

        const matchesType = (activePoTypeFilter === 'all') || (p.poType === activePoTypeFilter);

        const q = (poSearchTerm || '').toLowerCase().trim();
        const matchesSearch = !q || 
            (p.poNumber && p.poNumber.toLowerCase().includes(q)) ||
            (p.vendorName && p.vendorName.toLowerCase().includes(q)) ||
            (p.vendorTradeName && p.vendorTradeName.toLowerCase().includes(q)) ||
            (p.rfqReference && p.rfqReference.toLowerCase().includes(q)) ||
            (p.vendorContactPerson && p.vendorContactPerson.toLowerCase().includes(q)) ||
            (p.deliveryLocation && p.deliveryLocation.toLowerCase().includes(q)) ||
            (p.shippingNotes && p.shippingNotes.toLowerCase().includes(q)) ||
            (p.paymentStatus && p.paymentStatus.toLowerCase().includes(q)) ||
            (p.paymentMethod && p.paymentMethod.toLowerCase().includes(q));

        return matchesStatus && matchesApproval && matchesPayment && matchesType && matchesSearch;
    });

    // KPI Counts
    const total = pos.length;
    const pending = pos.filter(p => p.status === 'Approved / Issued').length;
    const received = pos.filter(p => p.status === 'Fully Received').length;
    const paid = pos.filter(p => p.paymentStatus === 'Paid in Full / Cash Out').length;

    const elKpiTotal = document.getElementById('kpiPoTotal');
    const elKpiPending = document.getElementById('kpiPoPending');
    const elKpiReceived = document.getElementById('kpiPoReceived');
    const elKpiPaid = document.getElementById('kpiPoPaid');
    const elTabCount = document.getElementById('tabPoCount');

    if (elKpiTotal) elKpiTotal.textContent = total;
    if (elKpiPending) elKpiPending.textContent = pending;
    if (elKpiReceived) elKpiReceived.textContent = received;
    if (elKpiPaid) elKpiPaid.textContent = paid;
    if (elTabCount) elTabCount.textContent = total;

    const elCountAll = document.getElementById('countPoAll');
    const elCountPending = document.getElementById('countPoPending');
    const elCountReceived = document.getElementById('countPoReceived');
    const elCountPaid = document.getElementById('countPoPaid');
    if (elCountAll) elCountAll.textContent = total;
    if (elCountPending) elCountPending.textContent = pending;
    if (elCountReceived) elCountReceived.textContent = received;
    if (elCountPaid) elCountPaid.textContent = paid;

    renderPoActiveFilterChips();

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${activePoColIds.length || 8}" style="text-align: center; padding: 36px; color: var(--po-text-muted);">No Purchase Orders found matching the filter criteria.</td></tr>`;
        const pStart = document.getElementById('poPaginationStart');
        const pEnd = document.getElementById('poPaginationEnd');
        const pTotal = document.getElementById('poPaginationTotal');
        if (pStart) pStart.textContent = '0';
        if (pEnd) pEnd.textContent = '0';
        if (pTotal) pTotal.textContent = '0';
        renderPoPaginationControls(0);
        return;
    }

    // Pagination Calculation
    const totalFiltered = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalFiltered / poPageSize));
    if (poCurrentPage > totalPages) poCurrentPage = totalPages;
    if (poCurrentPage < 1) poCurrentPage = 1;

    const startIndex = (poCurrentPage - 1) * poPageSize;
    const endIndex = Math.min(startIndex + poPageSize, totalFiltered);
    const pageItems = filtered.slice(startIndex, endIndex);

    const pStart = document.getElementById('poPaginationStart');
    const pEnd = document.getElementById('poPaginationEnd');
    const pTotal = document.getElementById('poPaginationTotal');
    if (pStart) pStart.textContent = (startIndex + 1).toString();
    if (pEnd) pEnd.textContent = endIndex.toString();
    if (pTotal) pTotal.textContent = totalFiltered.toString();
    renderPoPaginationControls(totalPages);

    tbody.innerHTML = pageItems.map(p => {
        const gross = (p.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        const isMarket = p.poType === 'wet_market';
        let payBadgeClass = 'unpaid';
        if (p.paymentStatus === 'Paid in Full / Cash Out') payBadgeClass = 'paid';
        else if (p.paymentStatus === 'Partial Payment') payBadgeClass = 'partial';

        let poStatusClass = 'draft';
        if (p.status === 'Approved / Issued') poStatusClass = 'approved';
        else if (p.status === 'Fully Received') poStatusClass = 'received';

        let cellsHtml = '';
        PO_DIRECTORY_COLUMNS.forEach(col => {
            if (!activePoColIds.includes(col.id)) return;

            if (col.id === 'ref') {
                cellsHtml += `
                    <td>
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" style="font-family: monospace; color: var(--po-primary-dark, #0284c7);">
                                <a href="javascript:void(0)" onclick="editPoFromDirectory('${escapeHtml(p.poNumber)}')" title="PO: ${escapeHtml(p.poNumber)}" style="color: inherit; text-decoration: underline; font-weight: 700;">
                                    ${escapeHtml(p.poNumber)}
                                </a>
                            </div>
                            <div class="rms-cell-sub" title="RFQ: ${escapeHtml(p.rfqReference || 'Direct Buy')}">
                                <span style="color: #94a3b8; font-family: monospace;">RFQ:</span> <span style="font-family: monospace;">${escapeHtml(p.rfqReference || 'Direct Buy')}</span>
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'vendor') {
                cellsHtml += `
                    <td>
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" title="${escapeHtml(p.vendorTradeName || p.vendorName || 'Supplier')}">
                                ${escapeHtml(p.vendorTradeName || p.vendorName || 'Supplier')}
                                ${isMarket ? '<span style="font-size: 0.65rem; background: #fef3c7; color: #b45309; padding: 1px 4px; border-radius: 3px; font-weight: 600; margin-left: 4px;">Market</span>' : ''}
                            </div>
                            <div class="rms-cell-sub" title="${escapeHtml((p.vendorContactPerson || 'Purchaser') + ' • ' + (p.vendorPhone || p.vendorEmail || ''))}">
                                ${escapeHtml(p.vendorContactPerson || 'Purchaser')} • ${escapeHtml(p.vendorPhone || p.vendorEmail || '')}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'dates') {
                cellsHtml += `
                    <td>
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" style="font-size: 0.78rem;" title="Order Date: ${formatDateDisplay(p.orderDate)}">
                                <span style="color: #64748b; font-weight: 500;">Order:</span> ${formatDateDisplay(p.orderDate)}
                            </div>
                            <div class="rms-cell-sub" style="color: var(--po-teal-dark, #0f766e); font-weight: 600;" title="Expected Delivery: ${formatDateDisplay(p.expectedDelivery)}">
                                <span style="color: #94a3b8; font-weight: 500;">Arrival:</span> ${formatDateDisplay(p.expectedDelivery)}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'destination') {
                cellsHtml += `
                    <td>
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" style="font-weight: 600; color: #334155;" title="${escapeHtml(p.deliveryLocation || 'Main Commissary / Outlet')}">
                                <i class="ph ph-map-pin" style="color: #94a3b8; font-size: 12px; margin-right: 2px;"></i>${escapeHtml(p.deliveryLocation || 'Main Commissary')}
                            </div>
                            <div class="rms-cell-sub" title="${escapeHtml(p.shippingNotes || 'Central Warehouse')}">
                                ${escapeHtml(p.shippingNotes || 'Central Warehouse')}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'items') {
                cellsHtml += `
                    <td class="td-center">
                        <div class="rms-cell-stack" style="align-items: center;">
                            <span class="po-tab-count">${(p.items || []).length} items</span>
                            <span style="font-size: 0.72rem; color: #0f172a; font-weight: 800;">₱${formatMoney(gross)}</span>
                        </div>
                    </td>
                `;
            } else if (col.id === 'payment') {
                cellsHtml += `
                    <td class="td-center">
                        <div class="rms-cell-stack" style="align-items: center; gap: 2px;">
                            <span class="pay-badge ${payBadgeClass}" style="cursor: pointer; padding: 2px 7px; font-size: 10px;" onclick="openPaymentSettlementModal('${escapeHtml(p.poNumber)}')" title="View Financial Settlement">
                                ${escapeHtml(p.paymentStatus || 'Unpaid')}
                            </span>
                            <span class="rms-cell-sub" style="font-size: 0.70rem; color: #64748b;" title="${escapeHtml(p.paymentMethod || p.paymentRemarks || 'Standard Terms')}">
                                ${escapeHtml(p.paymentMethod || 'Terms')}
                            </span>
                        </div>
                    </td>
                `;
            } else if (col.id === 'status') {
                const isApproved = (p.status === 'Approved / Issued' || p.status === 'Partially Received' || p.status === 'Fully Received' || p.isApproved === true);
                cellsHtml += `
                    <td class="td-center">
                        <div class="rms-cell-stack" style="align-items: center; gap: 2px;">
                            <span class="po-status-badge ${poStatusClass}" style="font-size: 0.70rem; padding: 1px 7px;">
                                <i class="ph ph-dot"></i> ${escapeHtml(p.status)}
                            </span>
                            ${isApproved ? `
                                <span class="po-approval-badge approved" title="Procurement Approval: Approved">
                                    <i class="ph ph-check-circle"></i> Approved
                                </span>
                            ` : `
                                <span class="po-approval-badge pending" title="Procurement Approval: Pending Approval">
                                    <i class="ph ph-hourglass-simple"></i> Pending
                                </span>
                            `}
                        </div>
                    </td>
                `;
            } else if (col.id === 'actions') {
                cellsHtml += `
                    <td class="td-center">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                            ${(p.status === 'Approved / Issued' || p.status === 'Partially Received') ? `
                                <a href="${window.PurchaseOrderConfig?.routes?.inventoryStockIn || '/inventory/stock-in'}?po=${encodeURIComponent(p.poNumber)}" class="inv-table-filter-btn" style="color: #059669; text-decoration: none; width: 24px; height: 24px; font-size: 12px;" title="Receive Stock in Inventory (Stock In / GRN)">
                                    <i class="ph ph-tray-arrow-down"></i>
                                </a>
                            ` : ''}
                            <button type="button" class="inv-table-filter-btn" onclick="openPaymentSettlementModal('${escapeHtml(p.poNumber)}')" title="Financial Settlement & Payments" style="color: #0284c7; width: 24px; height: 24px; font-size: 12px;">
                                <i class="ph ph-bank"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="openPoSingleAuditModal('${escapeHtml(p.poNumber)}')" title="View Audit Trail for this PO" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ph ph-clock-counter-clockwise"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="previewPoFromDirectory('${escapeHtml(p.poNumber)}')" title="Preview Printable PO" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ph ph-eye"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="editPoFromDirectory('${escapeHtml(p.poNumber)}')" title="Edit Purchase Order" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" style="color: var(--po-danger); width: 24px; height: 24px; font-size: 12px;" onclick="deletePo('${escapeHtml(p.poNumber)}')" title="Delete PO">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
            }
        });

        return `<tr>${cellsHtml}</tr>`;
    }).join('');
}

function renderPoPaginationControls(totalPages) {
    const container = document.getElementById('poPaginationButtons');
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 2px 8px; height: 26px; font-size: 11px;" ${poCurrentPage === 1 ? 'disabled' : ''} onclick="changePoPage(${poCurrentPage - 1})">
            <i class="ph ph-caret-left"></i> Prev
        </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= poCurrentPage - 1 && p <= poCurrentPage + 1)) {
            const isActive = (p === poCurrentPage);
            html += `
                <button type="button" class="hr-btn hr-btn-sm ${isActive ? 'hr-btn-primary' : 'hr-btn-secondary'}" style="padding: 2px 8px; height: 26px; font-size: 11px; min-width: 26px;" onclick="changePoPage(${p})">
                    ${p}
                </button>
            `;
        } else if (p === poCurrentPage - 2 || p === poCurrentPage + 2) {
            html += `<span style="padding: 0 4px; color: #94a3b8; font-size: 11px;">...</span>`;
        }
    }

    html += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 2px 8px; height: 26px; font-size: 11px;" ${poCurrentPage === totalPages ? 'disabled' : ''} onclick="changePoPage(${poCurrentPage + 1})">
            Next <i class="ph ph-caret-right"></i>
        </button>
    `;

    container.innerHTML = html;
}

function changePoPage(page) {
    poCurrentPage = page;
    renderPoDirectory();
}

function changePoPageSize(size) {
    poPageSize = parseInt(size, 10) || 10;
    poCurrentPage = 1;
    renderPoDirectory();
}

function filterPoStatus(status, btn) {
    activePoStatusFilter = status;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
}

function filterPoType(type, btn) {
    activePoTypeFilter = type;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
}

function filterPoPayment(payStatus, btn) {
    activePoPaymentFilter = payStatus;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
}

function togglePoHeaderFilter(event, filterType) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const popover = document.getElementById('poHeaderFilterPopover');
    if (!popover) return;

    if (activePoHeaderFilterType === filterType && popover.style.display !== 'none') {
        closePoHeaderFilter();
        return;
    }

    activePoHeaderFilterType = filterType;
    const triggerEl = event.currentTarget;
    const rect = triggerEl.getBoundingClientRect();

    popover.innerHTML = filterType === 'status' ? buildPoStatusFilterPopoverHtml() : buildPoPaymentFilterPopoverHtml();
    popover.style.display = 'block';
    popover.style.position = 'fixed';
    popover.style.top = `${rect.bottom + 6}px`;

    const popoverWidth = filterType === 'status' ? 360 : 320;
    let left = rect.left;
    if (left + popoverWidth > window.innerWidth - 16) {
        left = window.innerWidth - popoverWidth - 16;
    }
    popover.style.left = `${Math.max(10, left)}px`;
    popover.style.zIndex = '1200';
}

function closePoHeaderFilter() {
    const popover = document.getElementById('poHeaderFilterPopover');
    if (popover) popover.style.display = 'none';
    activePoHeaderFilterType = null;
}

function buildPoStatusFilterPopoverHtml() {
    const pos = window.PoStore.purchaseOrders || [];
    const totalCount = pos.length;
    const approvedCount = pos.filter(p => p.status === 'Approved / Issued' || p.status === 'Partially Received' || p.status === 'Fully Received' || p.isApproved === true).length;
    const pendingCount = totalCount - approvedCount;

    const draftCount = pos.filter(p => p.status === 'Draft PO' || p.status === 'Draft').length;
    const issuedCount = pos.filter(p => p.status === 'Approved / Issued').length;
    const partialCount = pos.filter(p => p.status === 'Partially Received').length;
    const receivedCount = pos.filter(p => p.status === 'Fully Received').length;
    const cancelledCount = pos.filter(p => p.status === 'Cancelled').length;

    return `
        <div class="po-filter-popover-card">
            <div class="po-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-sliders-horizontal" style="color: #0284c7; font-size: 15px;"></i>
                    <span>Status & Approval Filters</span>
                </div>
                <button type="button" class="po-popover-reset-btn" onclick="resetPoStatusFilters(event)">Reset Both</button>
            </div>
            
            <div class="po-filter-popover-body">
                <!-- ROW 1: APPROVAL STATUS -->
                <div class="po-filter-section">
                    <div class="po-filter-section-title">
                        <span>Row 1: Approval Status</span>
                        ${activePoApprovalFilter !== 'all' ? `<span class="po-section-active-badge">${escapeHtml(activePoApprovalFilter)}</span>` : ''}
                    </div>
                    <div class="po-filter-pill-row">
                        <button type="button" class="po-popover-chip ${activePoApprovalFilter === 'all' ? 'is-active' : ''}" onclick="setPoApprovalFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="po-popover-chip is-success ${activePoApprovalFilter === 'approved' ? 'is-active' : ''}" onclick="setPoApprovalFilter(event, 'approved')">
                            <i class="ph ph-check-circle"></i> Approved (${approvedCount})
                        </button>
                        <button type="button" class="po-popover-chip is-warning ${activePoApprovalFilter === 'pending' ? 'is-active' : ''}" onclick="setPoApprovalFilter(event, 'pending')">
                            <i class="ph ph-hourglass-simple"></i> Pending (${pendingCount})
                        </button>
                    </div>
                </div>

                <!-- ROW 2: PO STATUS -->
                <div class="po-filter-section" style="margin-top: 10px;">
                    <div class="po-filter-section-title">
                        <span>Row 2: PO Status</span>
                        ${activePoStatusFilter !== 'all' ? `<span class="po-section-active-badge">${escapeHtml(activePoStatusFilter)}</span>` : ''}
                    </div>
                    <div class="po-filter-pill-grid">
                        <button type="button" class="po-popover-chip ${activePoStatusFilter === 'all' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="po-popover-chip ${activePoStatusFilter === 'Draft PO' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'Draft PO')">
                            Draft (${draftCount})
                        </button>
                        <button type="button" class="po-popover-chip ${activePoStatusFilter === 'Approved / Issued' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'Approved / Issued')">
                            Issued (${issuedCount})
                        </button>
                        <button type="button" class="po-popover-chip ${activePoStatusFilter === 'Partially Received' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'Partially Received')">
                            Partial (${partialCount})
                        </button>
                        <button type="button" class="po-popover-chip is-success ${activePoStatusFilter === 'Fully Received' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'Fully Received')">
                            Received (${receivedCount})
                        </button>
                        <button type="button" class="po-popover-chip is-danger ${activePoStatusFilter === 'Cancelled' ? 'is-active' : ''}" onclick="setPoStatusFilter(event, 'Cancelled')">
                            Cancelled (${cancelledCount})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildPoPaymentFilterPopoverHtml() {
    const pos = window.PoStore.purchaseOrders || [];
    const totalCount = pos.length;
    const paidCount = pos.filter(p => p.paymentStatus === 'Paid in Full / Cash Out').length;
    const unpaidCount = pos.filter(p => !p.paymentStatus || p.paymentStatus === 'Pending Settlement / Unpaid' || p.paymentStatus === 'Unpaid').length;
    const partialCount = pos.filter(p => p.paymentStatus === 'Partial Payment' || p.paymentStatus === 'Partial').length;

    const vendorCount = pos.filter(p => p.poType === 'vendor' || !p.poType).length;
    const wetMarketCount = pos.filter(p => p.poType === 'wet_market').length;

    return `
        <div class="po-filter-popover-card">
            <div class="po-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-credit-card" style="color: #0284c7; font-size: 15px;"></i>
                    <span>Payment & Sourcing Filter</span>
                </div>
                <button type="button" class="po-popover-reset-btn" onclick="resetPoPaymentFilters(event)">Reset Both</button>
            </div>
            
            <div class="po-filter-popover-body">
                <!-- Section 1: Payment Status -->
                <div class="po-filter-section">
                    <div class="po-filter-section-title">
                        <span>Payment Status</span>
                        ${activePoPaymentFilter !== 'all' ? `<span class="po-section-active-badge">${escapeHtml(activePoPaymentFilter)}</span>` : ''}
                    </div>
                    <div class="po-filter-pill-row">
                        <button type="button" class="po-popover-chip ${activePoPaymentFilter === 'all' ? 'is-active' : ''}" onclick="setPoPaymentFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="po-popover-chip is-success ${activePoPaymentFilter === 'Paid in Full / Cash Out' ? 'is-active' : ''}" onclick="setPoPaymentFilter(event, 'Paid in Full / Cash Out')">
                            Paid (${paidCount})
                        </button>
                        <button type="button" class="po-popover-chip is-warning ${activePoPaymentFilter === 'Pending Settlement / Unpaid' ? 'is-active' : ''}" onclick="setPoPaymentFilter(event, 'Pending Settlement / Unpaid')">
                            Unpaid (${unpaidCount})
                        </button>
                        <button type="button" class="po-popover-chip ${activePoPaymentFilter === 'Partial Payment' ? 'is-active' : ''}" onclick="setPoPaymentFilter(event, 'Partial Payment')">
                            Partial (${partialCount})
                        </button>
                    </div>
                </div>

                <!-- Section 2: Sourcing Type -->
                <div class="po-filter-section" style="margin-top: 10px;">
                    <div class="po-filter-section-title">
                        <span>Sourcing Channel</span>
                        ${activePoTypeFilter !== 'all' ? `<span class="po-section-active-badge">${activePoTypeFilter === 'wet_market' ? 'Wet Market' : 'Vendor'}</span>` : ''}
                    </div>
                    <div class="po-filter-pill-row">
                        <button type="button" class="po-popover-chip ${activePoTypeFilter === 'all' ? 'is-active' : ''}" onclick="setPoTypeFilter(event, 'all')">
                            All Channels (${totalCount})
                        </button>
                        <button type="button" class="po-popover-chip ${activePoTypeFilter === 'vendor' ? 'is-active' : ''}" onclick="setPoTypeFilter(event, 'vendor')">
                            Standard Vendor (${vendorCount})
                        </button>
                        <button type="button" class="po-popover-chip is-warning ${activePoTypeFilter === 'wet_market' ? 'is-active' : ''}" onclick="setPoTypeFilter(event, 'wet_market')">
                            Wet Market Run (${wetMarketCount})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function setPoApprovalFilter(event, val) {
    if (event) event.stopPropagation();
    activePoApprovalFilter = val;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    const popover = document.getElementById('poHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activePoHeaderFilterType === 'status') {
        popover.innerHTML = buildPoStatusFilterPopoverHtml();
    }
}

function setPoStatusFilter(event, val) {
    if (event) event.stopPropagation();
    activePoStatusFilter = val;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    const popover = document.getElementById('poHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activePoHeaderFilterType === 'status') {
        popover.innerHTML = buildPoStatusFilterPopoverHtml();
    }
}

function resetPoStatusFilters(event) {
    if (event) event.stopPropagation();
    activePoStatusFilter = 'all';
    activePoApprovalFilter = 'all';
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    closePoHeaderFilter();
}

function setPoPaymentFilter(event, val) {
    if (event) event.stopPropagation();
    activePoPaymentFilter = val;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    const popover = document.getElementById('poHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activePoHeaderFilterType === 'payment') {
        popover.innerHTML = buildPoPaymentFilterPopoverHtml();
    }
}

function setPoTypeFilter(event, val) {
    if (event) event.stopPropagation();
    activePoTypeFilter = val;
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    const popover = document.getElementById('poHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activePoHeaderFilterType === 'payment') {
        popover.innerHTML = buildPoPaymentFilterPopoverHtml();
    }
}

function resetPoPaymentFilters(event) {
    if (event) event.stopPropagation();
    activePoPaymentFilter = 'all';
    activePoTypeFilter = 'all';
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    closePoHeaderFilter();
}

function clearAllPoFilters() {
    activePoStatusFilter = 'all';
    activePoApprovalFilter = 'all';
    activePoPaymentFilter = 'all';
    activePoTypeFilter = 'all';
    poSearchTerm = '';
    const searchInput = document.getElementById('poSearchInput');
    if (searchInput) searchInput.value = '';
    poCurrentPage = 1;
    renderPoTableHeader();
    renderPoDirectory();
    closePoHeaderFilter();
}

function renderPoActiveFilterChips() {
    const container = document.getElementById('poActiveFilterChips');
    if (!container) return;

    let chipsHtml = '';

    if (activePoApprovalFilter !== 'all') {
        chipsHtml += `
            <span class="po-active-chip" title="Active Approval Filter">
                <span>Approval: <strong>${escapeHtml(activePoApprovalFilter)}</strong></span>
                <button type="button" onclick="setPoApprovalFilter(event, 'all')" title="Clear approval filter">&times;</button>
            </span>
        `;
    }

    if (activePoStatusFilter !== 'all') {
        chipsHtml += `
            <span class="po-active-chip" title="Active PO Status Filter">
                <span>Status: <strong>${escapeHtml(activePoStatusFilter)}</strong></span>
                <button type="button" onclick="setPoStatusFilter(event, 'all')" title="Clear status filter">&times;</button>
            </span>
        `;
    }

    if (activePoPaymentFilter !== 'all') {
        chipsHtml += `
            <span class="po-active-chip" title="Active Payment Filter">
                <span>Payment: <strong>${escapeHtml(activePoPaymentFilter)}</strong></span>
                <button type="button" onclick="setPoPaymentFilter(event, 'all')" title="Clear payment filter">&times;</button>
            </span>
        `;
    }

    if (activePoTypeFilter !== 'all') {
        const typeLabel = activePoTypeFilter === 'wet_market' ? 'Wet Market' : 'Vendor';
        chipsHtml += `
            <span class="po-active-chip" title="Active Sourcing Filter">
                <span>Type: <strong>${escapeHtml(typeLabel)}</strong></span>
                <button type="button" onclick="setPoTypeFilter(event, 'all')" title="Clear sourcing filter">&times;</button>
            </span>
        `;
    }

    if (chipsHtml) {
        chipsHtml += `
            <button type="button" class="po-clear-all-chip-btn" onclick="clearAllPoFilters()" title="Reset all directory filters">
                Clear all
            </button>
        `;
    }

    container.innerHTML = chipsHtml;
}

function handlePoSearch(q) {
    poSearchTerm = q;
    poCurrentPage = 1;
    renderPoDirectory();
}

function startNewPoFromDirectory() {
    resetPoForm();
    switchPoTab('tab-po-builder');
}

function editPoFromDirectory(poNumber) {
    const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    window.PoStore.activePo = JSON.parse(JSON.stringify(po));

    // Update Form
    document.getElementById('poNumberInput').value = po.poNumber;
    document.getElementById('poRefDisplay').textContent = po.poNumber;
    if (document.getElementById('poOrderTitle')) {
        document.getElementById('poOrderTitle').value = po.orderTitle || po.purpose || '';
    }

    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) {
        if (po.rfqReference) {
            rfqBadge.style.display = 'inline-flex';
            rfqBadge.textContent = `Tender: ${po.rfqReference}`;
        } else {
            rfqBadge.style.display = 'none';
        }
    }

    setPoMode(po.poType || 'vendor');

    if (po.poType === 'wet_market') {
        document.getElementById('poMarketStallName').value = po.vendorName || '';
    } else {
        document.getElementById('poVendorSelect').value = po.vendorId || '';
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === po.vendorId || v.code === po.vendorId)) || (po.vendorName ? {
        code: po.vendorId || 'VND',
        tradeName: po.vendorTradeName || po.vendorName,
        legalName: po.vendorName,
        category: 'Vendor',
        address: po.vendorAddress
    } : null);
    updatePoVendorSummaryCard(vendor);

    document.getElementById('poContactPerson').value = po.vendorContactPerson || '';
    document.getElementById('poContactPhone').value = po.vendorPhone || '';
    document.getElementById('poContactEmail').value = po.vendorEmail || '';
    document.getElementById('poFullAddress').value = po.vendorAddress || '';
    document.getElementById('poOrderDate').value = po.orderDate || '';
    document.getElementById('poExpectedDelivery').value = po.expectedDelivery || '';
    document.getElementById('poRfqReference').value = po.rfqReference || '';
    document.getElementById('poDeliveryLocation').value = po.deliveryLocation || '';
    document.getElementById('poSpecialNotes').value = po.specialNotes || '';

    // Payment fields
    document.getElementById('poPaymentStatus').value = po.paymentStatus || 'Unpaid / Credit';
    document.getElementById('poPaymentMethod').value = po.paymentMethod || 'Cash / Petty Cash';
    document.getElementById('poAmountPaid').value = parseFloat(po.amountPaid || 0).toFixed(2);
    document.getElementById('poPaymentRef').value = po.paymentReference || '';
    document.getElementById('poPaymentDate').value = po.paymentDate || '';
    document.getElementById('poFundSource').value = po.fundSource || 'Branch Petty Cash Fund';
    document.getElementById('poPaymentRemarks').value = po.paymentRemarks || '';

    renderPoItemsTable();
    handlePaymentStatusChange(po.paymentStatus || 'Unpaid / Credit');

    document.getElementById('tabPoBuilderBadge').textContent = `Editing ${po.poNumber}`;
    switchPoTab('tab-po-builder');
    showToast(`Loaded ${po.poNumber} into Purchase Order Builder`, 'success');
}

function previewPoFromDirectory(poNumber) {
    const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    window.PoStore.activePo = JSON.parse(JSON.stringify(po));
    openPoDocumentPreview();
}

function deletePo(poNumber) {
    if (confirm(`Are you sure you want to delete purchase order ${poNumber}?`)) {
        const deletedPo = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
        window.PoStore.purchaseOrders = window.PoStore.purchaseOrders.filter(p => p.poNumber !== poNumber);
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

        logProcurementAudit(
            'PO',
            poNumber,
            'DELETED',
            'Purchase Order Cancelled / Deleted',
            `Purchase Order ${poNumber} was archived and removed from registry.`,
            [{ label: 'Supplier / Stall', value: deletedPo ? (deletedPo.vendorTradeName || deletedPo.vendorName) : 'N/A' }]
        );

        renderPoDirectory();
        showToast(`Purchase Order ${poNumber} deleted`, 'success');
    }
}

function resetPoForm() {
    const count = (window.PoStore.purchaseOrders || []).length;
    const poNum = `PO-2026-${String(count + 105).padStart(4, '0')}`;
    window.PoStore.activePo = {
        poNumber: poNum,
        poType: 'vendor',
        rfqReference: '',
        orderTitle: '',
        purpose: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorContactPerson: '',
        vendorPhone: '',
        vendorEmail: '',
        vendorAddress: '',
        orderDate: new Date().toISOString().split('T')[0],
        expectedDelivery: '',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        specialNotes: '',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 0.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: '',
        approvalNotes: '',
        approvedBy: 'Procurement Specialist',
        status: 'Draft PO',
        items: []
    };

    document.getElementById('poNumberInput').value = poNum;
    document.getElementById('poRefDisplay').textContent = poNum;
    if (document.getElementById('poOrderTitle')) document.getElementById('poOrderTitle').value = '';
    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) { rfqBadge.style.display = 'none'; rfqBadge.textContent = ''; }
    document.getElementById('poVendorSelect').value = '';
    updatePoVendorSummaryCard(null);
    renderPoVendorComboboxList();

    document.getElementById('poMarketStallName').value = '';
    document.getElementById('poContactPerson').value = '';
    document.getElementById('poContactPhone').value = '';
    document.getElementById('poContactEmail').value = '';
    document.getElementById('poFullAddress').value = '';
    document.getElementById('poRfqReference').value = '';
    document.getElementById('poPaymentStatus').value = 'Unpaid / Credit';
    document.getElementById('poAmountPaid').value = '0.00';
    document.getElementById('poPaymentRef').value = '';
    document.getElementById('tabPoBuilderBadge').textContent = 'New PO';

    setPoMode('vendor');
    initPoDates();
    renderPoItemsTable();
    showToast('PO Form Reset', 'success');
}

/**
 * --------------------------------------------------------------------------
 * FORMAL PRINTABLE PO DOCUMENT PREVIEW
 * --------------------------------------------------------------------------
 */
function openPoDocumentPreview() {
    const po = window.PoStore.activePo;
    const isMarket = po.poType === 'wet_market';
    const gross = calculatePoGrossTotal();
    const paid = parseFloat(document.getElementById('poAmountPaid').value) || (po.amountPaid || 0);
    const balance = Math.max(0, gross - paid);

    document.getElementById('docPoNumber').textContent = po.poNumber;
    document.getElementById('docPoDate').textContent = formatDateDisplay(document.getElementById('poOrderDate').value || po.orderDate);
    document.getElementById('docPoDeliveryDue').textContent = formatDateDisplay(document.getElementById('poExpectedDelivery').value || po.expectedDelivery);

    const vendorTitle = isMarket ? 
        (document.getElementById('poMarketStallName').value || po.vendorName || 'Wet Market Stall') : 
        (po.vendorTradeName || po.vendorName || 'Vendor Partner');

    document.getElementById('docPoVendor').textContent = vendorTitle;
    document.getElementById('docPoVendorDetails').innerHTML = `
        <strong>Attn:</strong> ${escapeHtml(document.getElementById('poContactPerson').value || po.vendorContactPerson || 'Representative')}<br>
        <strong>Phone:</strong> ${escapeHtml(document.getElementById('poContactPhone').value || po.vendorPhone || 'N/A')}<br>
        <strong>Address / Market:</strong> ${escapeHtml(document.getElementById('poFullAddress').value || po.vendorAddress || 'Metro Manila')}
    `;

    document.getElementById('docPoDeliveryDest').textContent = document.getElementById('poDeliveryLocation').value || po.deliveryLocation;
    document.getElementById('docPoPaymentStatus').textContent = document.getElementById('poPaymentStatus').value || po.paymentStatus;
    document.getElementById('docPoPaymentMethod').textContent = document.getElementById('poPaymentMethod').value || po.paymentMethod;
    document.getElementById('docPoPaymentRef').textContent = document.getElementById('poPaymentRef').value || po.paymentReference || 'N/A';

    // Populate table
    const tbody = document.getElementById('docPoItemsTbody');
    if (tbody) {
        if (po.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No items in purchase order</td></tr>`;
        } else {
            tbody.innerHTML = po.items.map((it, idx) => {
                const total = (parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0);
                return `
                    <tr>
                        <td style="text-align: center; font-weight: 700;">${idx + 1}</td>
                        <td style="font-family: monospace; font-weight: 700;">${escapeHtml(it.sku || '--')}</td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(it.name || 'Custom Product')}</div>
                            ${it.specs ? `<div style="font-size: 0.76rem; color: #475569;">${escapeHtml(it.specs)}</div>` : ''}
                        </td>
                        <td style="text-align: center;">${escapeHtml(it.unit || 'Kg')}</td>
                        <td class="num" style="font-weight: 700;">${parseFloat(it.quantity || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                        <td class="num">₱${formatMoney(it.unitPrice || 0)}</td>
                        <td class="num" style="font-weight: 800;">₱${formatMoney(total)}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    document.getElementById('docPoGross').textContent = `₱${formatMoney(gross)}`;
    document.getElementById('docPoPaid').textContent = `₱${formatMoney(paid)}`;
    document.getElementById('docPoBalance').textContent = `₱${formatMoney(balance)}`;

    const modal = document.getElementById('poDocumentModal');
    if (modal) modal.classList.add('is-open');
}

function closePoDocumentPreview() {
    const modal = document.getElementById('poDocumentModal');
    if (modal) modal.classList.remove('is-open');
}

function closeAllPoModals() {
    closePoDocumentPreview();
    closeItemMasterQuickPicker();
}

/**
 * --------------------------------------------------------------------------
 * UTILITIES
 * --------------------------------------------------------------------------
 */
function formatMoney(amount) {
    const val = Number(amount) || 0;
    return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateDisplay(isoDate) {
    if (!isoDate) return '--';
    try {
        const d = new Date(isoDate);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        return isoDate;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('poToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `rfq-toast is-${type}`;
    toast.innerHTML = `
        <i class="ph ${type === 'success' ? 'ph-check-circle' : 'ph-warning-circle'}" style="font-size: 20px;"></i>
        <span>${escapeHtml(message)}</span>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.25s ease';
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}
/**
 * --------------------------------------------------------------------------
 * PROCUREMENT AUDIT TRAIL ENGINE FOR PURCHASE ORDERS
 * --------------------------------------------------------------------------
 */
let activePoAuditActionFilter = 'ALL';
let activePoAuditModuleFilter = 'ALL';
let poAuditSearchTerm = '';

function getProcurementAuditTrail() {
    let logs = [];
    try {
        const stored = localStorage.getItem('rms_procurement_audit_trail');
        if (stored) logs = JSON.parse(stored);
    } catch (e) {
        logs = [];
    }

    if (!logs || logs.length === 0) {
        logs = seedProcurementAuditTrail();
        localStorage.setItem('rms_procurement_audit_trail', JSON.stringify(logs));
    }
    return logs;
}

function seedProcurementAuditTrail() {
    return [
        {
            id: 'AUD-2026-1006',
            timestamp: new Date(Date.now() - 3600000 * 2).toISOString(),
            formattedDate: 'Sep 30, 2026 • 10:45 AM',
            module: 'PO',
            refNumber: 'PO-2026-0103',
            action: 'PAYMENT_RECORDED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Payment Settlement Recorded',
            description: 'Disbursed ₱18,750.00 cash payment for direct wet market procurement run.',
            details: [
                { label: 'Payment Status', value: 'Paid in Full / Cash Out' },
                { label: 'Disbursement Method', value: 'Cash / Petty Cash' },
                { label: 'Fund Source', value: 'Branch Petty Cash Box' },
                { label: 'Official Receipt #', value: 'OR-BKT-88412' }
            ]
        },
        {
            id: 'AUD-2026-1005',
            timestamp: new Date(Date.now() - 3600000 * 4).toISOString(),
            formattedDate: 'Sep 30, 2026 • 08:30 AM',
            module: 'PO',
            refNumber: 'PO-2026-0103',
            action: 'WET_MARKET_CREATED',
            actor: 'Roberto Cruz',
            role: 'Store Supervisor',
            title: 'Direct Wet Market Cash Run Issued',
            description: 'Custom PO created for wet market run at Balintawak Wholesale Market Stall #14 without RFQ.',
            details: [
                { label: 'Market Stall', value: 'Aling Nena Fresh Produce (Stall #14)' },
                { label: 'Purchaser', value: 'Roberto Cruz (Kitchen Lead)' },
                { label: 'Order Total', value: '₱18,750.00' }
            ]
        },
        {
            id: 'AUD-2026-1004',
            timestamp: new Date(Date.now() - 3600000 * 18).toISOString(),
            formattedDate: 'Sep 29, 2026 • 04:15 PM',
            module: 'PO',
            refNumber: 'PO-2026-0101',
            action: 'PO_TRANSFERRED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Purchase Order Issued from RFQ',
            description: 'Issued formal PO-2026-0101 transferred from approved quotation RFQ-2026-0041.',
            details: [
                { label: 'Supplier', value: 'Golden Acre Produce Corp.' },
                { label: 'Awarded Total', value: '₱48,600.00' },
                { label: 'Payment Terms', value: 'Net 15 Days' }
            ]
        },
        {
            id: 'AUD-2026-1003',
            timestamp: new Date(Date.now() - 3600000 * 19).toISOString(),
            formattedDate: 'Sep 29, 2026 • 03:50 PM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0041',
            action: 'APPROVED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Manager Approval Granted',
            description: 'RFQ-2026-0041 approved after bidding review. Authorized for immediate purchase order issuance.',
            details: [
                { label: 'Supplier', value: 'Golden Acre Produce Corp.' },
                { label: 'Evaluation', value: 'Lowest compliant tender meeting commissary freshness standards.' }
            ]
        }
    ];
}

function logProcurementAudit(module, refNumber, action, title, description, details = [], metadata = {}) {
    const logs = getProcurementAuditTrail();
    const now = new Date();
    const formattedDate = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + 
        ' • ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const entry = {
        id: `AUD-${now.getFullYear()}-${String(logs.length + 1001)}`,
        timestamp: now.toISOString(),
        formattedDate: formattedDate,
        module: module,
        refNumber: refNumber,
        action: action,
        actor: window.PurchaseOrderConfig?.user?.name || 'Dorothy Diaz',
        role: 'Procurement Manager',
        title: title,
        description: description,
        details: details,
        metadata: metadata
    };

    logs.unshift(entry);
    localStorage.setItem('rms_procurement_audit_trail', JSON.stringify(logs));

    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge) {
        auditCountBadge.textContent = logs.filter(l => l.module === 'PO' || l.action === 'PO_TRANSFERRED').length || logs.length;
    }

    return entry;
}

let poAuditScopeMode = 'CURRENT';

function getCurrentPoAuditTarget() {
    const active = window.PoStore.activePo || {};
    const poNum = (active.poNumber || document.getElementById('poRefDisplay')?.textContent || '').trim();
    const rfqNum = (active.rfqReference || document.getElementById('poRfqReference')?.value || '').trim();
    return { poNum, rfqNum };
}

function openPoAuditDrawer() {
    const drawer = document.getElementById('poAuditDrawer');
    const overlay = document.getElementById('poAuditDrawerOverlay');
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    renderPoAuditTrail();
}

function closePoAuditDrawer() {
    const drawer = document.getElementById('poAuditDrawer');
    const overlay = document.getElementById('poAuditDrawerOverlay');
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

function setPoAuditScope(mode) {
    poAuditScopeMode = mode;
    const btnCurrent = document.getElementById('btnPoAuditScopeCurrent');
    const btnAll = document.getElementById('btnPoAuditScopeAll');
    if (btnCurrent) btnCurrent.classList.toggle('active', mode === 'CURRENT');
    if (btnAll) btnAll.classList.toggle('active', mode === 'ALL');
    renderPoAuditTrail();
}

function renderPoAuditTrail() {
    const container = document.getElementById('poAuditTimeline');
    if (!container) return;

    const allLogs = getProcurementAuditTrail();
    const { poNum, rfqNum } = getCurrentPoAuditTarget();

    // Update target badges in drawer
    const badgePo = document.getElementById('poAuditTargetRefBadge');
    if (badgePo) badgePo.textContent = poNum || 'Current PO';

    const badgeRfq = document.getElementById('poAuditTargetRfqBadge');
    if (badgeRfq) {
        if (rfqNum && rfqNum !== 'None' && rfqNum !== 'Direct Market Purchase') {
            badgeRfq.textContent = rfqNum;
            badgeRfq.style.display = 'inline-block';
        } else {
            badgeRfq.style.display = 'none';
        }
    }

    // Process optimization: Index ONLY active PO / RFQ when in CURRENT mode
    let targetLogs = allLogs;
    if (poAuditScopeMode === 'CURRENT' && (poNum || rfqNum)) {
        targetLogs = allLogs.filter(log => {
            const ref = (log.refNumber || '').trim();
            if (poNum && (ref === poNum || (log.description && log.description.includes(poNum)))) {
                return true;
            }
            if (rfqNum && (ref === rfqNum || (log.description && log.description.includes(rfqNum)))) {
                return true;
            }
            return false;
        });

        // Ensure newly drafted PO has baseline entry
        if (targetLogs.length === 0 && poNum) {
            const active = window.PoStore.activePo || {};
            targetLogs = [{
                id: `AUD-INIT-${poNum}`,
                timestamp: new Date().toISOString(),
                formattedDate: 'Just now',
                module: 'PO',
                refNumber: poNum,
                action: 'CREATED',
        actor: window.PurchaseOrderConfig?.user?.name || 'Dorothy Diaz',
                role: 'Procurement Specialist',
                title: 'Purchase Order Initialized',
                description: `Draft order ${poNum} initialized for ${active.vendorTradeName || active.vendorName || 'selected supplier'}.`,
                details: [
                    { label: 'Order Ref', value: poNum },
                    { label: 'Sourcing Type', value: active.poType === 'wet_market' ? 'Direct Wet Market' : 'Contracted Vendor' },
                    { label: 'Originating RFQ', value: active.rfqReference || 'None (Direct Order)' }
                ]
            }];
        }
    }

    // Update scoped event counter in drawer header
    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = targetLogs.length;

    const q = (poAuditSearchTerm || '').toLowerCase().trim();

    const filtered = targetLogs.filter(log => {
        const matchesModule = (activePoAuditModuleFilter === 'ALL') || (log.module === activePoAuditModuleFilter);
        const matchesAction = (activePoAuditActionFilter === 'ALL') || (log.action === activePoAuditActionFilter);
        const matchesSearch = !q ||
            (log.refNumber && log.refNumber.toLowerCase().includes(q)) ||
            (log.title && log.title.toLowerCase().includes(q)) ||
            (log.description && log.description.toLowerCase().includes(q)) ||
            (log.actor && log.actor.toLowerCase().includes(q));

        return matchesModule && matchesAction && matchesSearch;
    });

    // Update KPI numbers calculated ONLY from the target set
    const kpiTotal = document.getElementById('poAuditKpiTotal');
    const kpiWetMarket = document.getElementById('poAuditKpiWetMarket');
    const kpiPayments = document.getElementById('poAuditKpiPayments');
    const kpiTransfers = document.getElementById('poAuditKpiTransfers');

    if (kpiTotal) kpiTotal.textContent = targetLogs.length;
    if (kpiWetMarket) kpiWetMarket.textContent = targetLogs.filter(l => l.action === 'WET_MARKET_CREATED').length;
    if (kpiPayments) kpiPayments.textContent = targetLogs.filter(l => l.action === 'PAYMENT_RECORDED').length;
    if (kpiTransfers) kpiTransfers.textContent = targetLogs.filter(l => l.action === 'PO_TRANSFERRED' || l.action === 'CREATED').length;

    if (filtered.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px solid var(--po-border-subtle); border-radius: 10px; color: var(--po-text-muted);">
                <i class="ph ph-clock-countdown" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 600; color: #475569; margin-bottom: 4px;">No Activity Records Found</div>
                <div style="font-size: 12px;">No logged activities match the current filter criteria for ${poNum || 'this order'}.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(log => {
        let nodeClass = 'is-created';
        let nodeIcon = 'ph-plus-circle';
        let badgeClass = 'badge-created';

        if (log.action === 'APPROVED') {
            nodeClass = 'is-approved';
            nodeIcon = 'ph-seal-check';
            badgeClass = 'badge-approved';
        } else if (log.action === 'EDITED') {
            nodeClass = 'is-edited';
            nodeIcon = 'ph-pencil-simple';
            badgeClass = 'badge-edited';
        } else if (log.action === 'PO_TRANSFERRED' || log.action === 'WET_MARKET_CREATED') {
            nodeClass = 'is-po';
            nodeIcon = 'ph-receipt';
            badgeClass = 'badge-po';
        } else if (log.action === 'EMAIL_SENT') {
            nodeClass = 'is-email';
            nodeIcon = 'ph-paper-plane-tilt';
            badgeClass = 'badge-email';
        } else if (log.action === 'PAYMENT_RECORDED') {
            nodeClass = 'is-payment';
            nodeIcon = 'ph-currency-circle-dollar';
            badgeClass = 'badge-payment';
        } else if (log.action === 'APPROVAL_REVOKED' || log.action === 'DELETED') {
            nodeClass = 'is-revoked';
            nodeIcon = 'ph-arrow-u-up-left';
            badgeClass = 'badge-revoked';
        }

        const detailsHtml = (log.details && log.details.length > 0) ? `
            <table class="audit-diff-table">
                <tbody>
                    ${log.details.map(d => `
                        <tr>
                            <th>${escapeHtml(d.label)}</th>
                            <td>${escapeHtml(d.value)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        ` : '';

        return `
            <div class="audit-timeline-item">
                <div class="audit-timeline-node-icon ${nodeClass}">
                    <i class="ph ${nodeIcon}"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="audit-badge ${badgeClass}">${escapeHtml(log.action.replace('_', ' '))}</span>
                        <span style="font-weight: 800; font-size: 0.94rem; color: var(--po-text-strong);">${escapeHtml(log.title)}</span>
                        <span style="font-family: monospace; font-size: 0.78rem; font-weight: 700; color: var(--po-primary); background: #eff6ff; padding: 2px 6px; border-radius: 4px;">
                            ${escapeHtml(log.refNumber)}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: var(--po-text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-clock"></i> ${escapeHtml(log.formattedDate)}
                    </div>
                </div>
                <div style="font-size: 0.84rem; color: #334155; line-height: 1.45; margin-bottom: 6px;">
                    ${escapeHtml(log.description)}
                </div>
                ${detailsHtml}
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 0.75rem; color: var(--po-text-muted);">
                    <div style="width: 20px; height: 20px; border-radius: 50%; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;">
                        ${(log.actor || 'U').charAt(0)}
                    </div>
                    <span><strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})</span>
                    <span>•</span>
                    <span style="font-family: monospace;">Ref: ${escapeHtml(log.id)}</span>
                </div>
            </div>
        `;
    }).join('');
}

function onPoAuditSearchChange(query) {
    poAuditSearchTerm = query;
    renderPoAuditTrail();
}

function filterPoAuditByModule(mod, btn) {
    activePoAuditModuleFilter = mod;
    const parent = btn.parentElement;
    parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderPoAuditTrail();
}

function filterPoAuditByAction(action, btn) {
    activePoAuditActionFilter = action;
    const parent = document.getElementById('poAuditActionFilterPills');
    if (parent) {
        parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    }
    btn.classList.add('active');
    renderPoAuditTrail();
}

function openPoSingleAuditModal(poNumber) {
    const modal = document.getElementById('poSingleAuditModal');
    if (!modal) return;

    document.getElementById('singlePoAuditHeaderTitle').textContent = `Purchase Order Audit Trail • ${poNumber}`;
    document.getElementById('singlePoAuditRefTitle').textContent = poNumber;
    document.getElementById('singlePoAuditSubTitle').textContent = `Activity, fulfillment changes, and payment disbursement trail for ${poNumber}`;

    const container = document.getElementById('singlePoAuditTimelineContainer');
    const allLogs = getProcurementAuditTrail();
    const itemLogs = allLogs.filter(l => l.refNumber === poNumber);

    if (itemLogs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px; color: var(--po-text-muted);">
                <i class="ph ph-clock" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                No audit events recorded yet for this Purchase Order.
            </div>
        `;
    } else {
        container.innerHTML = itemLogs.map(log => `
            <div class="audit-timeline-item" style="padding: 12px 16px;">
                <div class="audit-timeline-node-icon is-created" style="top: 14px;">
                    <i class="ph ph-check"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <strong style="font-size: 0.88rem; color: var(--po-text-strong);">${escapeHtml(log.title)}</strong>
                    <span style="font-size: 0.75rem; color: var(--po-text-muted);">${escapeHtml(log.formattedDate)}</span>
                </div>
                <div style="font-size: 0.82rem; color: #334155; margin-bottom: 4px;">
                    ${escapeHtml(log.description)}
                </div>
                ${(log.details && log.details.length > 0) ? `
                    <div style="font-size: 0.76rem; background: #f8fafc; padding: 6px 10px; border-radius: 6px; margin-top: 6px;">
                        ${log.details.map(d => `<div><strong>${escapeHtml(d.label)}:</strong> ${escapeHtml(d.value)}</div>`).join('')}
                    </div>
                ` : ''}
                <div style="font-size: 0.72rem; color: var(--po-text-muted); margin-top: 6px;">
                    Logged by <strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})
                </div>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closePoSingleAuditModal() {
    const modal = document.getElementById('poSingleAuditModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * PO REFERENCE & TITLE UTILITIES
 * --------------------------------------------------------------------------
 */
function copyPoReference() {
    const ref = document.getElementById('poRefDisplay') ? document.getElementById('poRefDisplay').textContent : '';
    if (!ref) return;
    navigator.clipboard.writeText(ref).then(() => {
        showToast(`Copied ${ref} to clipboard`, 'success');
    }).catch(() => {
        showToast(`Reference: ${ref}`, 'success');
    });
}

function handlePoTitleChange(val) {
    window.PoStore.activePo.orderTitle = val;
    window.PoStore.activePo.purpose = val;
}

/**
 * --------------------------------------------------------------------------
 * IMPORT AWARDED RFQ MODAL UTILITIES
 * --------------------------------------------------------------------------
 */
function openImportRfqModal() {
    const modal = document.getElementById('poImportRfqModal');
    const container = document.getElementById('importRfqListContainer');
    if (!modal || !container) return;

    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const approvedRfqs = rfqs.filter(r => (r.status === 'Awarded' || r.isApproved === true));

    if (approvedRfqs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px 16px; color: #64748b;">
                <i class="ph ph-files" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 700; color: #0f172a;">No Approved RFQs Available</div>
                <div style="font-size: 12px; margin-top: 4px;">Only tenders with <strong>Awarded / Approved</strong> status can be converted into Purchase Orders.</div>
            </div>
        `;
    } else {
        container.innerHTML = approvedRfqs.map(r => `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.15s ease;">
                <div style="display: flex; flex-direction: column; gap: 3px; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-family: monospace; font-weight: 800; color: #9333ea; font-size: 13px;">${escapeHtml(r.rfqNumber)}</span>
                        <span class="status-pill approved" style="font-size: 10.5px; padding: 2px 7px;"><i class="ph ph-check-circle"></i> Awarded Tender</span>
                    </div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${escapeHtml(r.vendorName || r.vendorTradeName || 'Contracted Supplier')}
                    </div>
                    <div style="font-size: 11.5px; color: #64748b;">
                        ${escapeHtml(r.projectTitle || r.purpose || 'General Procurement')} • Issued: ${formatDateDisplay(r.dateIssued)} • Items: ${(r.items || []).length} lines
                    </div>
                </div>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="importApprovedRfqToBuilder('${escapeHtml(r.rfqNumber)}')" style="flex-shrink: 0; padding: 6px 14px; font-size: 12px;">
                    <i class="ph ph-arrow-down-left"></i> Import to Builder
                </button>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closeImportRfqModal() {
    const modal = document.getElementById('poImportRfqModal');
    if (modal) modal.classList.remove('is-open');
}

function importApprovedRfqToBuilder(rfqNum) {
    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const rfq = rfqs.find(r => r.rfqNumber === rfqNum);
    if (!rfq) {
        showToast('RFQ record not found', 'danger');
        return;
    }

    closeImportRfqModal();

    // Populate Active PO
    const count = (window.PoStore.purchaseOrders || []).length;
    const nextPoNum = rfq.poReference || `PO-2026-${String(count + 105).padStart(4, '0')}`;

    window.PoStore.activePo.poNumber = nextPoNum;
    window.PoStore.activePo.rfqReference = rfq.rfqNumber;
    window.PoStore.activePo.orderTitle = rfq.projectTitle || rfq.purpose || `Procurement via ${rfq.rfqNumber}`;
    window.PoStore.activePo.vendorId = rfq.vendorId || '';
    window.PoStore.activePo.vendorName = rfq.vendorName || '';
    window.PoStore.activePo.vendorTradeName = rfq.vendorTradeName || rfq.vendorName || '';
    window.PoStore.activePo.vendorContactPerson = rfq.vendorContactPerson || '';
    window.PoStore.activePo.vendorPhone = rfq.vendorPhone || '';
    window.PoStore.activePo.vendorEmail = rfq.vendorEmail || '';
    window.PoStore.activePo.vendorAddress = rfq.vendorAddress || '';
    window.PoStore.activePo.deliveryLocation = rfq.deliveryLocation || 'Central Commissary - Receiving Dock A';
    window.PoStore.activePo.paymentTerms = rfq.paymentTerms || 'Net 30 Days';
    window.PoStore.activePo.expectedDelivery = rfq.expectedDelivery || '';
    window.PoStore.activePo.specialNotes = `Imported from Tender Evaluation ${rfq.rfqNumber}. ${rfq.specialInstructions || ''}`;
    window.PoStore.activePo.status = 'Draft PO';

    // Map items
    window.PoStore.activePo.items = (rfq.items || []).map(it => ({
        sku: it.sku || 'CAT-DIR',
        name: it.name || it.itemDescription || 'Awarded Specification Item',
        specs: it.specs || '',
        category: it.category || 'Raw Ingredients',
        unit: it.unit || it.uom || 'Kg',
        quantity: parseFloat(it.quantity) || 1,
        unitPrice: parseFloat(it.quotedUnitPrice || it.unitPrice || it.budgetPrice || 0)
    }));

    // Update DOM inputs
    document.getElementById('poNumberInput').value = nextPoNum;
    document.getElementById('poRefDisplay').textContent = nextPoNum;
    document.getElementById('poOrderTitle').value = window.PoStore.activePo.orderTitle;
    document.getElementById('poVendorSelect').value = rfq.vendorId || '';
    document.getElementById('poContactPerson').value = window.PoStore.activePo.vendorContactPerson;
    document.getElementById('poContactPhone').value = window.PoStore.activePo.vendorPhone;
    document.getElementById('poContactEmail').value = window.PoStore.activePo.vendorEmail;
    document.getElementById('poFullAddress').value = window.PoStore.activePo.vendorAddress;
    document.getElementById('poPaymentTerms').value = window.PoStore.activePo.paymentTerms;
    if (document.getElementById('poExpectedDelivery')) {
        document.getElementById('poExpectedDelivery').value = window.PoStore.activePo.expectedDelivery;
    }
    document.getElementById('poDeliveryLocation').value = window.PoStore.activePo.deliveryLocation;
    document.getElementById('poSpecialNotes').value = window.PoStore.activePo.specialNotes;

    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) {
        rfqBadge.style.display = 'inline-flex';
        rfqBadge.textContent = `Tender: ${rfq.rfqNumber}`;
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === rfq.vendorId || v.code === rfq.vendorId)) || {
        code: rfq.vendorId || 'VND-AWD',
        tradeName: rfq.vendorTradeName || rfq.vendorName,
        legalName: rfq.vendorName,
        category: 'Awarded Partner',
        address: rfq.vendorAddress
    };
    updatePoVendorSummaryCard(vendor);

    renderPoItemsTable();
    switchPoTab('tab-po-builder');
    showToast(`✓ Imported tender ${rfq.rfqNumber} with ${(rfq.items || []).length} items into PO Builder!`, 'success');
}

function openLinkedRfqModal(rfqNum) {
    const modal = document.getElementById('poLinkedRfqModal');
    if (!modal) return;

    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const rfq = rfqs.find(r => r.rfqNumber === rfqNum);
    const content = document.getElementById('linkedRfqDetailContent');
    const title = document.getElementById('linkedRfqModalRef');

    if (title) title.textContent = rfqNum;

    if (!rfq) {
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 24px; color: #64748b;">
                    <i class="ph ph-warning-circle" style="font-size: 32px; color: #f59e0b; display: block; margin-bottom: 6px;"></i>
                    <div style="font-weight: 700; color: #0f172a;">Originating RFQ Archive Not Found</div>
                    <div style="font-size: 12px; margin-top: 4px;">The reference <strong>${escapeHtml(rfqNum)}</strong> is stored in history, but its complete raw tender dataset was cleared from local memory.</div>
                </div>
            `;
        }
    } else if (content) {
        content.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b;">Selected Vendor</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">${escapeHtml(rfq.vendorTradeName || rfq.vendorName)}</div>
                        <div style="font-size: 12px; color: #64748b;">${escapeHtml(rfq.vendorAddress || 'Metro Manila')}</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-pill approved" style="font-size: 11px; padding: 3px 9px;"><i class="ph ph-check-circle"></i> Awarded Tender</span>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Issued: ${formatDateDisplay(rfq.dateIssued)}</div>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Quotation Line Items (${(rfq.items || []).length})</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left;">
                                <th style="padding: 6px 8px;">Item Description</th>
                                <th style="padding: 6px 8px; text-align: center;">UOM</th>
                                <th style="padding: 6px 8px; text-align: right;">Quantity</th>
                                <th style="padding: 6px 8px; text-align: right;">Quoted Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(rfq.items || []).map(it => `
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 6px 8px; font-weight: 600;">${escapeHtml(it.name || it.itemDescription)}</td>
                                    <td style="padding: 6px 8px; text-align: center;">${escapeHtml(it.unit || it.uom || 'Kg')}</td>
                                    <td style="padding: 6px 8px; text-align: right;">${parseFloat(it.quantity || 0).toFixed(2)}</td>
                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: #047857;">₱${formatMoney(it.quotedUnitPrice || it.unitPrice || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    modal.classList.add('is-open');
}

function closeLinkedRfqModal() {
    const modal = document.getElementById('poLinkedRfqModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * FINANCIAL TRACKING & PAYMENT SETTLEMENT (FILTERED BY REFERENCE)
 * --------------------------------------------------------------------------
 */
let currentSettlementRef = '';

function getStoredPayments() {
    try {
        return JSON.parse(localStorage.getItem('rms_po_payments') || '[]');
    } catch(e) {
        return [];
    }
}

function saveStoredPayments(payments) {
    try {
        localStorage.setItem('rms_po_payments', JSON.stringify(payments));
    } catch(e) {
        console.error('Error saving payments:', e);
    }
}

function handlePaymentTermsChange(val) {
    window.PoStore.activePo.paymentTerms = val;
    showToast(`Payment terms set to ${val}`, 'success');
}

function updatePoSummaryPayBadge() {
    const badge = document.getElementById('poSummaryPayBadge');
    if (!badge) return;

    const po = window.PoStore.activePo;
    const gross = calculatePoGrossTotal();
    const payments = getStoredPayments().filter(p => p.poNumber === po.poNumber);
    const inlinePaid = document.getElementById('poAmountPaid') ? parseFloat(document.getElementById('poAmountPaid').value) : null;
    const paid = (payments.length > 0)
        ? payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0)
        : ((inlinePaid !== null && !isNaN(inlinePaid)) ? inlinePaid : (parseFloat(po.amountPaid) || 0));

    if (paid >= gross && gross > 0) {
        badge.className = 'pay-badge paid';
        badge.textContent = 'Paid in Full';
        window.PoStore.activePo.paymentStatus = 'Paid in Full / Cash Out';
    } else if (paid > 0) {
        badge.className = 'pay-badge partial';
        badge.textContent = `Partial (${formatMoney(paid)})`;
        window.PoStore.activePo.paymentStatus = 'Partial Payment';
    } else {
        badge.className = 'pay-badge unpaid';
        badge.textContent = 'Unpaid';
        window.PoStore.activePo.paymentStatus = 'Unpaid / Credit';
    }

    window.PoStore.activePo.amountPaid = paid;
    window.PoStore.activePo.balanceDue = Math.max(0, gross - paid);
}

function openPaymentSettlementModal(targetRef) {
    const modal = document.getElementById('poPaymentSettlementModal');
    if (!modal) return;

    currentSettlementRef = targetRef || (window.PoStore.activePo.poNumber || 'PO-2026-0105');

    // Populate References Dropdown
    const select = document.getElementById('payModalRefSelect');
    if (select) {
        const pos = window.PoStore.purchaseOrders || [];
        const refSet = new Set();
        if (window.PoStore.activePo.poNumber) refSet.add(window.PoStore.activePo.poNumber);
        pos.forEach(p => { if (p.poNumber) refSet.add(p.poNumber); });

        select.innerHTML = Array.from(refSet).map(ref => `
            <option value="${ref}" ${ref === currentSettlementRef ? 'selected' : ''}>${ref}</option>
        `).join('');
    }

    // Default payment date to today
    const dateInput = document.getElementById('newPayDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }

    renderPaymentSettlementData();
    modal.classList.add('is-open');
}

function closePaymentSettlementModal() {
    const modal = document.getElementById('poPaymentSettlementModal');
    if (modal) modal.classList.remove('is-open');
}

function filterPaymentsByReference(ref) {
    if (!ref) return;
    currentSettlementRef = ref;
    renderPaymentSettlementData();
}

function resetPaymentViewToActivePo() {
    currentSettlementRef = window.PoStore.activePo.poNumber || 'PO-2026-0105';
    const select = document.getElementById('payModalRefSelect');
    if (select) select.value = currentSettlementRef;
    renderPaymentSettlementData();
}

function renderPaymentSettlementData() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const isActivePo = (ref === window.PoStore.activePo.poNumber);

    // Resolve Gross and Linked RFQ
    let gross = 0;
    let linkedRfq = '';
    let vendorName = '';

    if (isActivePo) {
        gross = calculatePoGrossTotal();
        linkedRfq = window.PoStore.activePo.rfqReference || '';
        vendorName = window.PoStore.activePo.vendorTradeName || window.PoStore.activePo.vendorName || '';
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            linkedRfq = po.rfqReference || '';
            vendorName = po.vendorTradeName || po.vendorName || '';
        }
    }

    // Filter payments strictly by currentSettlementRef
    const allPayments = getStoredPayments();
    const payments = allPayments.filter(p => p.poNumber === ref);
    const totalPaid = payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
    const balanceDue = Math.max(0, gross - totalPaid);

    // DOM Updates
    document.getElementById('payModalActiveRef').textContent = ref;
    document.getElementById('payLedgerRefLabel').textContent = ref;
    document.getElementById('payModalGross').textContent = `₱${formatMoney(gross)}`;
    document.getElementById('payModalPaid').textContent = `₱${formatMoney(totalPaid)}`;
    document.getElementById('payModalBalance').textContent = `₱${formatMoney(balanceDue)}`;
    document.getElementById('payLedgerCountBadge').textContent = `${payments.length} Entr${payments.length === 1 ? 'y' : 'ies'}`;

    const pct = gross > 0 ? ((totalPaid / gross) * 100) : (totalPaid > 0 ? 100 : 0);
    document.getElementById('payModalPercentPaid').textContent = `${pct.toFixed(1)}% settled${vendorName ? ' • ' + vendorName : ''}`;

    const statusBadge = document.getElementById('payModalStatusBadge');
    if (totalPaid >= gross && gross > 0) {
        statusBadge.className = 'pay-badge paid';
        statusBadge.textContent = 'Paid in Full';
    } else if (totalPaid > 0) {
        statusBadge.className = 'pay-badge partial';
        statusBadge.textContent = 'Partial Payment';
    } else {
        statusBadge.className = 'pay-badge unpaid';
        statusBadge.textContent = 'Unpaid / Credit';
    }

    // Linked RFQ Badge
    const linkedBadge = document.getElementById('payModalLinkedRfqBadge');
    if (linkedBadge) {
        if (linkedRfq) {
            linkedBadge.style.display = 'inline-flex';
            linkedBadge.textContent = `Tender: ${linkedRfq}`;
        } else {
            linkedBadge.style.display = 'none';
        }
    }

    // Render Ledger Table
    const tbody = document.getElementById('payModalLedgerTbody');
    if (tbody) {
        if (payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 28px 14px; color: #94a3b8;">
                        <i class="ph ph-receipt" style="font-size: 28px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                        <div style="font-weight: 700; color: #475569;">No Payment Disbursements Recorded</div>
                        <div style="font-size: 11.5px; margin-top: 2px;">Record an advance, downpayment, or full payment for reference <strong>${escapeHtml(ref)}</strong> using the form below.</div>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = payments.map((p, idx) => `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 7px 10px; text-align: center; color: #64748b; font-weight: 700;">${idx + 1}</td>
                    <td style="padding: 7px 10px; font-weight: 600; color: #1e293b;">${formatDateDisplay(p.paymentDate)}</td>
                    <td style="padding: 7px 10px; color: #475569;"><span class="po-label-badge">${escapeHtml(p.method || 'Cash')}</span></td>
                    <td style="padding: 7px 10px; font-family: monospace; font-weight: 700; color: #0284c7;">${escapeHtml(p.reference || '--')}</td>
                    <td style="padding: 7px 10px; color: #475569; font-size: 11px;">${escapeHtml(p.fundSource || 'Petty Cash')}</td>
                    <td style="padding: 7px 10px; text-align: right; font-weight: 800; color: #059669;">₱${formatMoney(p.amount)}</td>
                    <td style="padding: 7px 10px; text-align: center;">
                        <button type="button" class="inv-table-filter-btn" onclick="deletePaymentDisbursement('${p.id}')" title="Delete payment entry" style="color: #ef4444; width: 24px; height: 24px; font-size: 13px;">
                            <i class="ph ph-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }

    // Synchronize to Active PO if editing current active PO
    if (isActivePo) {
        updatePoSummaryPayBadge();
    }
}

function fillFullRemainingBalance() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const isActivePo = (ref === window.PoStore.activePo.poNumber);

    let gross = 0;
    if (isActivePo) {
        gross = calculatePoGrossTotal();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        }
    }

    const allPayments = getStoredPayments();
    const payments = allPayments.filter(p => p.poNumber === ref);
    const totalPaid = payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
    const balanceDue = Math.max(0, gross - totalPaid);

    const amountInput = document.getElementById('newPayAmount');
    if (amountInput) {
        amountInput.value = balanceDue.toFixed(2);
        amountInput.focus();
    }
    const inlineInput = document.getElementById('poAmountPaid');
    if (inlineInput && isActivePo) {
        inlineInput.value = balanceDue.toFixed(2);
        handlePoInlineAmountPaid(balanceDue);
    }
}

function addPaymentDisbursement() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const amountVal = parseFloat(document.getElementById('newPayAmount').value) || 0;

    if (amountVal <= 0) {
        showToast('Please enter a valid disbursement amount greater than 0', 'danger');
        return;
    }

    const method = document.getElementById('newPayMethod').value;
    const paymentRef = document.getElementById('newPayRef').value.trim() || `DISB-${Date.now().toString().slice(-4)}`;
    const paymentDate = document.getElementById('newPayDate').value || new Date().toISOString().split('T')[0];
    const fundSource = document.getElementById('newPayFundSource').value;
    const remarks = document.getElementById('newPayRemarks').value.trim();

    const paymentEntry = {
        id: `PAY-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
        poNumber: ref,
        amount: amountVal,
        method: method,
        reference: paymentRef,
        paymentDate: paymentDate,
        fundSource: fundSource,
        remarks: remarks,
        recordedAt: new Date().toISOString(),
        actor: window.PurchaseOrderConfig?.user?.name || 'Procurement Specialist'
    };

    const payments = getStoredPayments();
    payments.push(paymentEntry);
    saveStoredPayments(payments);

    // Audit log
    logProcurementAudit(
        'PO',
        ref,
        'PAYMENT_RECORDED',
        'Payment Disbursement Added',
        `Disbursed ₱${formatMoney(amountVal)} via ${method} for ${ref} (${paymentRef}).`,
        [
            { label: 'Amount Paid', value: `₱${formatMoney(amountVal)}` },
            { label: 'Method', value: method },
            { label: 'Receipt / Check #', value: paymentRef },
            { label: 'Fund Source', value: fundSource }
        ]
    );

    // Update in-memory PO state
    if (ref === window.PoStore.activePo.poNumber) {
        updatePoSummaryPayBadge();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            const poPayments = payments.filter(p => p.poNumber === ref);
            const totalPaid = poPayments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
            const gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            po.amountPaid = totalPaid;
            po.balanceDue = Math.max(0, gross - totalPaid);
            po.paymentStatus = (totalPaid >= gross && gross > 0) ? 'Paid in Full / Cash Out' : (totalPaid > 0 ? 'Partial Payment' : 'Unpaid / Credit');
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    // Reset inputs
    document.getElementById('newPayAmount').value = '';
    document.getElementById('newPayRef').value = '';
    document.getElementById('newPayRemarks').value = '';

    renderPaymentSettlementData();
    renderPoDirectory();
    showToast(`✓ Recorded ₱${formatMoney(amountVal)} disbursement for ${ref}`, 'success');
}

function deletePaymentDisbursement(id) {
    if (!confirm('Remove this payment disbursement entry?')) return;

    let payments = getStoredPayments();
    const deleted = payments.find(p => p.id === id);
    payments = payments.filter(p => p.id !== id);
    saveStoredPayments(payments);

    if (deleted) {
        logProcurementAudit(
            'PO',
            deleted.poNumber,
            'PAYMENT_REMOVED',
            'Payment Disbursement Voided',
            `Voided payment disbursement ₱${formatMoney(deleted.amount)} (${deleted.reference}) for ${deleted.poNumber}.`,
            [{ label: 'Voided Amount', value: `₱${formatMoney(deleted.amount)}` }]
        );
    }

    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    if (ref === window.PoStore.activePo.poNumber) {
        updatePoSummaryPayBadge();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            const poPayments = payments.filter(p => p.poNumber === ref);
            const totalPaid = poPayments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
            const gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            po.amountPaid = totalPaid;
            po.balanceDue = Math.max(0, gross - totalPaid);
            po.paymentStatus = (totalPaid >= gross && gross > 0) ? 'Paid in Full / Cash Out' : (totalPaid > 0 ? 'Partial Payment' : 'Unpaid / Credit');
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    renderPaymentSettlementData();
    renderPoDirectory();
    showToast('Payment disbursement entry removed', 'success');
}

// Global click handler to close vendor combobox dropdown
window.addEventListener('click', (e) => {
    const combobox = document.getElementById('poVendorCombobox');
    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (combobox && !combobox.contains(e.target)) {
        if (dropdown) dropdown.classList.remove('is-active');
        if (trigger) trigger.classList.remove('is-active');
    }
});

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  initPoStore,
  initPoDates,
  syncApprovedRfqsToPoDirectory,
  renderPoVendorDropdown,
  renderPoVendorComboboxList,
  togglePoVendorCombobox,
  filterPoVendorCombobox,
  selectPoVendorFromCombo,
  updatePoVendorSummaryCard,
  switchPoTab,
  setPoMode,
  handlePoVendorSelect,
  handlePaymentStatusChange,
  handlePoInlineAmountPaid,
  calculateBalanceDue,
  calculatePoGrossTotal,
  renderPoItemsTable,
  updatePoItemField,
  addNewBlankPoItemRow,
  deletePoItemRow,
  clearAllPoItems,
  updatePoTotals,
  openItemMasterQuickPicker,
  closeItemMasterQuickPicker,
  filterPoItemMaster,
  renderPoItemMasterRows,
  addMasterItemToPoDirect,
  issuePurchaseOrderSubmit,
  getPoColWidth,
  initPoColumns,
  renderPoTableHeader,
  renderPoColumnChecklist,
  togglePoColumnConfigDropdown,
  togglePoColumnVisibility,
  showAllPoColumns,
  resetPoColumnDefaults,
  resetPoColumns,
  resetPoColumnWidths,
  initPoColResize,
  handlePoColMouseMove,
  handlePoColMouseUp,
  syncPoFilterPillActive,
  renderPoDirectory,
  renderPoPaginationControls,
  changePoPage,
  changePoPageSize,
  filterPoStatus,
  filterPoType,
  filterPoPayment,
  togglePoHeaderFilter,
  closePoHeaderFilter,
  buildPoStatusFilterPopoverHtml,
  buildPoPaymentFilterPopoverHtml,
  setPoApprovalFilter,
  setPoStatusFilter,
  resetPoStatusFilters,
  setPoPaymentFilter,
  setPoTypeFilter,
  resetPoPaymentFilters,
  clearAllPoFilters,
  renderPoActiveFilterChips,
  handlePoSearch,
  startNewPoFromDirectory,
  editPoFromDirectory,
  previewPoFromDirectory,
  deletePo,
  resetPoForm,
  openPoDocumentPreview,
  closePoDocumentPreview,
  closeAllPoModals,
  formatMoney,
  formatDateDisplay,
  escapeHtml,
  showToast,
  getProcurementAuditTrail,
  seedProcurementAuditTrail,
  logProcurementAudit,
  getCurrentPoAuditTarget,
  openPoAuditDrawer,
  closePoAuditDrawer,
  setPoAuditScope,
  renderPoAuditTrail,
  onPoAuditSearchChange,
  filterPoAuditByModule,
  filterPoAuditByAction,
  openPoSingleAuditModal,
  closePoSingleAuditModal,
  copyPoReference,
  handlePoTitleChange,
  openImportRfqModal,
  closeImportRfqModal,
  importApprovedRfqToBuilder,
  openLinkedRfqModal,
  closeLinkedRfqModal,
  getStoredPayments,
  saveStoredPayments,
  handlePaymentTermsChange,
  updatePoSummaryPayBadge,
  openPaymentSettlementModal,
  closePaymentSettlementModal,
  filterPaymentsByReference,
  resetPaymentViewToActivePo,
  renderPaymentSettlementData,
  fillFullRemainingBalance,
  addPaymentDisbursement,
  deletePaymentDisbursement
});
