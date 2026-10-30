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
    updatePoLifecycleProgressBar(window.PoStore.activePo.status || 'Draft PO');
    renderPoItemsTable();
    renderPoDirectory();
    updatePoLiveDocumentMockup();
    handlePoDeepLinkParams();

    // Close header filter popover when clicking outside
    document.addEventListener('click', (e) => {
        const popover = document.getElementById('poHeaderFilterPopover');
        if (popover && popover.style.display !== 'none') {
            if (!popover.contains(e.target) && !e.target.closest('.po-th-filter-wrapper')) {
                closePoHeaderFilter();
            }
        }
        const tableSettings = document.getElementById('poTableSettingsDropdown');
        const btnCol = document.getElementById('btnPoColConfig');
        const btnActionGear = document.getElementById('btnPoActionColFilter');
        if (tableSettings && tableSettings.style.display !== 'none' && !tableSettings.contains(e.target) && (!btnCol || !btnCol.contains(e.target)) && (!btnActionGear || !btnActionGear.contains(e.target))) {
            tableSettings.style.display = 'none';
        }
        const builderSettings = document.getElementById('poBuilderSettingsDropdown');
        const btnBuilderGear = document.getElementById('btnPoBuilderSettings');
        if (builderSettings && builderSettings.style.display !== 'none' && !builderSettings.contains(e.target) && (!btnBuilderGear || !btnBuilderGear.contains(e.target))) {
            builderSettings.style.display = 'none';
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

function getNextUniquePoNumber() {
    const pos = window.PoStore?.purchaseOrders || [];
    const existingSet = new Set(pos.map(p => p.poNumber));
    let seq = Math.max(105, pos.length + 105);
    let candidate = `PO-2026-${String(seq).padStart(4, '0')}`;
    while (existingSet.has(candidate)) {
        seq++;
        candidate = `PO-2026-${String(seq).padStart(4, '0')}`;
    }
    return candidate;
}

function initPoStore() {
    const serverPos = window.PurchaseOrderConfig?.data?.initialPurchaseOrders || [];
    const serverVendors = window.PurchaseOrderConfig?.data?.initialVendors || [];
    const serverItems = window.PurchaseOrderConfig?.data?.initialItems || [];
    let deletedPoSet = new Set();
    try {
        deletedPoSet = new Set(JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]'));
    } catch (e) {}

    // 1. Load Purchase Orders (Server + LocalStorage Merge)
    if (Array.isArray(serverPos) && serverPos.length > 0) {
        const mappedServerPos = serverPos
            .filter(p => p && p.po_number && !deletedPoSet.has(p.po_number))
            .map(p => ({
                id: p.id,
                poNumber: p.po_number,
                poType: p.po_type || 'vendor',
                rfqReference: p.rfq_reference || '',
                orderTitle: p.special_notes || '',
                vendorId: p.vendor_id || '',
                vendorName: p.vendor_name,
                vendorTradeName: p.vendor_trade_name || p.vendor_name,
                vendorContactPerson: p.vendor_contact_person || '',
                vendorPhone: p.vendor_phone || '',
                vendorEmail: p.vendor_email || '',
                vendorAddress: p.vendor_address || '',
                orderDate: p.order_date ? String(p.order_date).split('T')[0] : '',
                expectedDelivery: p.expected_delivery ? String(p.expected_delivery).split('T')[0] : '',
                deliveryLocation: p.delivery_location || 'Central Commissary - Receiving Dock 1',
                paymentTerms: p.payment_terms || 'Net 30 Days',
                specialNotes: p.special_notes || '',
                paymentStatus: p.payment_status || 'Unpaid / Credit',
                paymentMethod: p.payment_method || 'Trade Credit (Net 30/15)',
                amountPaid: parseFloat(p.amount_paid || 0),
                balanceDue: parseFloat(p.balance_due || 0),
                paymentReference: p.payment_reference || '',
                paymentDate: p.payment_date ? String(p.payment_date).split('T')[0] : '',
                fundSource: p.fund_source || '',
                paymentRemarks: p.payment_remarks || '',
                approvalNotes: p.approval_notes || '',
                approvedBy: p.approved_by || 'Procurement Specialist',
                status: p.status || 'Approved / Issued',
                items: (p.items || []).map(it => ({
                    id: it.id,
                    sku: it.sku || 'CAT-01',
                    name: it.item_name || it.name || 'Ordered Item',
                    specs: it.specs || it.specifications || '',
                    category: it.category || 'Raw Ingredients',
                    unit: it.uom || it.unit || 'PCS',
                    quantity: parseFloat(it.quantity !== undefined ? it.quantity : (it.quantity_ordered || 1)),
                    unitPrice: parseFloat(it.unit_price !== undefined ? it.unit_price : (it.unitPrice || 0)),
                    receivedQuantity: parseFloat(it.received_quantity !== undefined ? it.received_quantity : (it.receivedQuantity || 0))
                }))
            }));

        const serverPoNums = new Set(mappedServerPos.map(p => p.poNumber));
        const seedIds = new Set((SEEDED_PURCHASE_ORDERS || []).map(s => s.poNumber));
        let localUnsynced = [];
        try {
            const rawLocal = JSON.parse(localStorage.getItem('rms_purchase_orders') || '[]');
            if (Array.isArray(rawLocal)) {
                localUnsynced = rawLocal.filter(lp =>
                    lp && lp.poNumber &&
                    !serverPoNums.has(lp.poNumber) &&
                    !deletedPoSet.has(lp.poNumber) &&
                    !seedIds.has(lp.poNumber)
                );
            }
        } catch (e) {}

        window.PoStore.purchaseOrders = [...localUnsynced, ...mappedServerPos];
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    } else {
        const storedPos = localStorage.getItem('rms_purchase_orders');
        if (storedPos) {
            try {
                const parsed = JSON.parse(storedPos);
                window.PoStore.purchaseOrders = Array.isArray(parsed)
                    ? parsed.filter(p => p && !deletedPoSet.has(p.poNumber))
                    : [];
            } catch (e) {
                window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS)).filter(p => !deletedPoSet.has(p.poNumber));
            }
        } else {
            window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS)).filter(p => !deletedPoSet.has(p.poNumber));
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
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
    const poNum = getNextUniquePoNumber();
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
    [paneList, paneBuilder].forEach(p => {
        if (p) {
            p.classList.remove('active');
            p.style.display = 'none';
        }
    });

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
        if (paneList) {
            paneList.classList.add('active');
            paneList.style.display = 'block';
        }
        renderPoDirectory();
    } else if (tabId === 'tab-po-builder') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) {
            paneBuilder.classList.add('active');
            paneBuilder.style.display = 'block';
        }
        updatePoLifecycleProgressBar(window.PoStore?.activePo?.status || 'Draft PO');
        renderPoBuilderTableHeader();
        updatePoLiveDocumentMockup();
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
    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };

    if (mode === 'wet_market') {
        if (btnMarket) btnMarket.classList.add('active');
        if (btnVendor) btnVendor.classList.remove('active');
        if (secMarket) secMarket.style.display = 'block';
        if (secVendor) secVendor.style.display = 'none';
        if (titleEl) titleEl.textContent = 'Wet Market Stall & Purchaser Sourcing';
        if (badgeEl) {
            badgeEl.textContent = 'Wet Market Cash Run';
            badgeEl.style.background = '#fef3c7';
            badgeEl.style.color = '#92400e';
        }
        if (iconEl) iconEl.className = 'ph ph-basket';

        // Auto default to Cash / Petty Cash and Paid in Full
        setVal('poPaymentStatus', 'Paid in Full / Cash Out');
        setVal('poPaymentMethod', 'Cash / Petty Cash');
        setVal('poFundSource', 'Branch Petty Cash Fund');
        handlePaymentStatusChange('Paid in Full / Cash Out');
        showToast('Switched to Direct Wet Market Cash Purchase mode', 'success');
    } else {
        if (btnVendor) btnVendor.classList.add('active');
        if (btnMarket) btnMarket.classList.remove('active');
        if (secVendor) secVendor.style.display = 'block';
        if (secMarket) secMarket.style.display = 'none';
        if (titleEl) titleEl.textContent = 'Vendor & Partner Profile';
        if (badgeEl) {
            badgeEl.textContent = 'Approved Vendor';
            badgeEl.style.background = 'var(--po-primary-subtle)';
            badgeEl.style.color = 'var(--po-primary-dark)';
        }
        if (iconEl) iconEl.className = 'ph ph-buildings';

        setVal('poPaymentStatus', 'Unpaid / Credit');
        setVal('poPaymentMethod', 'Trade Credit (Net 30/15)');
        handlePaymentStatusChange('Unpaid / Credit');
    }
}

function handlePoVendorSelect(vendorId) {
    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    if (!vendorId) {
        setVal('poContactPerson', '');
        setVal('poContactTitle', '');
        setVal('poContactPhone', '');
        setVal('poContactEmail', '');
        setVal('poFullAddress', '');
        window.PoStore.activePo.vendorId = '';
        window.PoStore.activePo.vendorName = '';
        window.PoStore.activePo.vendorTradeName = '';
        updatePoVendorSummaryCard(null);
        renderPoVendorComboboxList();
        updatePoLiveDocumentMockup();
        return;
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    setVal('poContactPerson', (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].name : '');
    setVal('poContactTitle', (vendor.contacts && vendor.contacts[0]) ? (vendor.contacts[0].title || vendor.contacts[0].designation || 'Account Representative') : 'Account Manager');
    setVal('poContactPhone', (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].phone : '');
    setVal('poContactEmail', (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].email : (vendor.email || ''));
    setVal('poFullAddress', vendor.address || '');

    window.PoStore.activePo.vendorId = vendor.id || vendor.code;
    window.PoStore.activePo.vendorName = vendor.legalName || vendor.tradeName;
    window.PoStore.activePo.vendorTradeName = vendor.tradeName || vendor.legalName;

    updatePoVendorSummaryCard(vendor);
    renderPoVendorComboboxList();
    updatePoLiveDocumentMockup();
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
/**
 * --------------------------------------------------------------------------
 * PO BUILDER STAGE-PROGRESSIVE COLUMN MATRIX (RFQ PARITY)
 * --------------------------------------------------------------------------
 */
const PO_ALL_COLUMNS = [
    // 1. Base Order Fields
    { id: 'index', label: '#', category: 'Base Order', default: true, align: 'center', width: '44px', lockVisible: true },
    { id: 'sku', label: 'Item ID / SKU', category: 'Base Order', default: true, align: 'left', width: '120px', lockVisible: true },
    { id: 'item_name', label: 'Item Description', category: 'Base Order', default: true, align: 'left', minWidth: '185px', lockVisible: true },
    { id: 'uom', label: 'Purchase UOM', category: 'Base Order', default: true, align: 'center', width: '130px' },
    { id: 'quantity', label: 'Ordered Qty', category: 'Base Order', default: true, align: 'right', width: '115px' },
    { id: 'unit_price', label: 'Unit Price (₱)', category: 'Base Order', default: true, align: 'right', width: '125px' },
    { id: 'line_total', label: 'Line Total (₱)', category: 'Base Order', default: true, align: 'right', width: '130px' },
    { id: 'specs', label: 'Specifications / Notes', category: 'Base Order', default: false, align: 'left', minWidth: '150px' },

    // 2. RFQ Benchmark & Variance Fields (Revealed in Approval Stage or RFQ-Linked PO)
    { id: 'rfq_quoted_qty', label: 'RFQ Quoted Qty', category: 'RFQ Benchmark & Variance', default: false, align: 'right', width: '115px' },
    { id: 'rfq_target_price', label: 'Benchmark Price (₱)', category: 'RFQ Benchmark & Variance', default: false, align: 'right', width: '130px' },
    { id: 'price_variance_amount', label: 'Price Diff (₱)', category: 'RFQ Benchmark & Variance', default: false, align: 'right', width: '120px' },
    { id: 'price_variance_pct', label: 'Variance (%)', category: 'RFQ Benchmark & Variance', default: false, align: 'right', width: '115px' },
    { id: 'is_within_budget', label: 'Budget Check', category: 'RFQ Benchmark & Variance', default: false, align: 'center', width: '120px' },

    // 3. Receiving & Fulfillment Fields (Revealed in Issued & Status Stages)
    { id: 'received_quantity', label: 'Received Qty (GRN)', category: 'Receiving & Fulfillment', default: false, align: 'right', width: '130px' },
    { id: 'remaining_quantity', label: 'Pending / Balance', category: 'Receiving & Fulfillment', default: false, align: 'right', width: '125px' },
    { id: 'fulfillment_status', label: 'Fulfillment', category: 'Receiving & Fulfillment', default: false, align: 'center', width: '125px' }
];

const PO_DRAFT_STAGE_DEFAULT_COLUMNS = [
    'index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total'
];

const PO_APPROVAL_STAGE_DEFAULT_COLUMNS = [
    'index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total',
    'rfq_target_price', 'price_variance_pct', 'is_within_budget'
];

const PO_ISSUED_STAGE_DEFAULT_COLUMNS = [
    'index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total',
    'received_quantity', 'remaining_quantity', 'fulfillment_status'
];

let activePoBuilderCols = [...PO_DRAFT_STAGE_DEFAULT_COLUMNS];
let poBuilderColSearchTerm = '';

function getNormalizedPoStage(status) {
    const s = String(status || 'Draft PO').trim().toUpperCase();
    if (s === 'DRAFT' || s === 'DRAFT PO') return 'Draft';
    if (s === 'APPROVAL' || s === 'PENDING APPROVAL' || s === 'PENDING_APPROVAL') return 'Approval';
    if (s === 'ISSUED' || s === 'APPROVED / ISSUED' || s === 'SENT' || s === 'PARTIALLY RECEIVED' || s === 'PARTIALLY_RECEIVED') return 'Issued';
    if (s === 'FULFILLED' || s === 'FULLY RECEIVED' || s === 'FULLY_RECEIVED' || s === 'CLOSED' || s === 'APPROVED') return 'Approved';
    if (s === 'REFUSED' || s === 'CANCELLED' || s === 'SHORT-CLOSED') return 'Refused';
    return 'Draft';
}

function applyPoStageDefaultColumns(stage) {
    const norm = getNormalizedPoStage(stage);
    if (norm === 'Approval') {
        activePoBuilderCols = [...PO_APPROVAL_STAGE_DEFAULT_COLUMNS];
    } else if (norm === 'Issued' || norm === 'Approved') {
        activePoBuilderCols = [...PO_ISSUED_STAGE_DEFAULT_COLUMNS];
    } else {
        activePoBuilderCols = [...PO_DRAFT_STAGE_DEFAULT_COLUMNS];
    }
}

function renderPoBuilderTableHeader() {
    const theadRow = document.getElementById('poItemsTheadRow');
    if (!theadRow) return;

    const visibleCols = PO_ALL_COLUMNS.filter(c => c.lockVisible || activePoBuilderCols.includes(c.id));
    let html = visibleCols.map(col => {
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        const wStyle = col.width ? `width: ${col.width};` : (col.minWidth ? `min-width: ${col.minWidth};` : '');
        return `<th class="${alignClass}" style="${wStyle}">${escapeHtml(col.label)}</th>`;
    }).join('');

    html += `
        <th class="th-center" style="width: 72px; position: relative;">
            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <span>Action</span>
                <button type="button" class="inv-table-filter-btn" id="btnPoBuilderSettings" onclick="togglePoBuilderSettingsDropdown(event)" title="Customize PO Builder Columns" style="width: 22px; height: 22px; font-size: 12px; border-radius: 5px; color: #9333ea; background: #faf5ff; border: 1px solid #e9d5ff;">
                    <i class="ph ph-gear-six"></i>
                </button>
            </div>
            <div id="poBuilderSettingsDropdown" class="po-builder-settings-dropdown rfq-builder-settings-dropdown" style="display: none; text-align: left; right: 0; top: 36px;" onclick="event.stopPropagation()">
                <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 800; font-size: 12px; color: #0f172a;"><i class="ph ph-columns" style="color: #9333ea;"></i> Builder Columns</span>
                    <span style="font-size: 10.5px; font-weight: 700; color: #7e22ce; background: #f3e8ff; padding: 2px 7px; border-radius: 8px;">${visibleCols.length} of ${PO_ALL_COLUMNS.length}</span>
                </div>
                <div style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 6px;">
                    <button type="button" class="inv-col-quick-link" onclick="showAllPoBuilderColumns()" style="font-size: 11px;">Show All</button>
                    <button type="button" class="inv-col-quick-link" onclick="resetPoBuilderColumnsToStage()" style="font-size: 11px;">Stage Default</button>
                </div>
                <div style="max-height: 260px; overflow-y: auto; padding: 6px 10px;">
                    ${PO_ALL_COLUMNS.map(col => {
                        const checked = col.lockVisible || activePoBuilderCols.includes(col.id);
                        return `
                            <label style="display: flex; align-items: center; justify-content: space-between; padding: 4px 6px; font-size: 11.5px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${checked ? '600' : '400'};">
                                    <input type="checkbox" ${checked ? 'checked' : ''} ${col.lockVisible ? 'disabled' : ''} onchange="togglePoBuilderColumn('${col.id}', this.checked)" style="accent-color: #9333ea;">
                                    <span>${escapeHtml(col.label)}</span>
                                </span>
                                <span style="font-size: 9.5px; color: #94a3b8;">${escapeHtml(col.category.split(' ')[0])}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </div>
        </th>
    `;

    theadRow.innerHTML = html;
}

function togglePoBuilderSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('poBuilderSettingsDropdown');
    if (!dd) return;
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

function togglePoBuilderColumn(colId, isVisible) {
    if (isVisible) {
        if (!activePoBuilderCols.includes(colId)) activePoBuilderCols.push(colId);
    } else {
        activePoBuilderCols = activePoBuilderCols.filter(c => c !== colId);
    }
    renderPoItemsTable();
}

function showAllPoBuilderColumns() {
    activePoBuilderCols = PO_ALL_COLUMNS.map(c => c.id);
    renderPoItemsTable();
}

function resetPoBuilderColumnsToStage() {
    applyPoStageDefaultColumns(window.PoStore?.activePo?.status || 'Draft PO');
    renderPoItemsTable();
}

function renderPoItemsTable() {
    renderPoBuilderTableHeader();
    const tbody = document.getElementById('poItemsTbody');
    const items = window.PoStore.activePo.items || [];
    if (!tbody) return;

    const visibleCols = PO_ALL_COLUMNS.filter(c => c.lockVisible || activePoBuilderCols.includes(c.id));
    const stage = getNormalizedPoStage(window.PoStore.activePo.status);
    const isLocked = (stage !== 'Draft');

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleCols.length + 1}" style="text-align: center; padding: 42px; color: var(--po-text-muted);">
                    <i class="ph ph-shopping-bag-open" style="font-size: 38px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--po-text-strong);">No Line Items in Purchase Order</div>
                    <div style="font-size: 0.8rem; margin-top: 4px;">Click <strong>"Import RFQ"</strong>, <strong>"Select Master" (F2)</strong>, or <strong>"Add Custom Row"</strong> to populate items.</div>
                </td>
            </tr>
        `;
        updatePoTotals();
        updatePoLiveDocumentMockup();
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        const qty = parseFloat(it.quantity) || 0;
        const unitPrice = parseFloat(it.unitPrice) || 0;
        const lineTotal = qty * unitPrice;
        const targetPrice = parseFloat(it.rfqTargetPrice !== undefined ? it.rfqTargetPrice : (it.targetUnitPrice || unitPrice)) || 0;
        const priceDiff = unitPrice - targetPrice;
        const variancePct = targetPrice > 0 ? ((priceDiff / targetPrice) * 100) : 0;
        const withinBudget = targetPrice <= 0 || unitPrice <= targetPrice;
        const recQty = parseFloat(it.receivedQuantity || 0);
        const remQty = Math.max(0, qty - recQty);

        const cells = visibleCols.map(col => {
            if (col.id === 'index') {
                return `<td class="td-center" style="font-weight: 700; color: var(--po-text-muted);">${idx + 1}</td>`;
            }
            if (col.id === 'sku') {
                return `
                    <td>
                        <input type="text" value="${escapeHtml(it.sku || 'CAT-ITEM')}" ${isLocked ? 'readonly' : ''} onchange="updatePoItemField(${idx}, 'sku', this.value)" style="font-family: monospace; font-size: 11.5px; font-weight: 700; color: #475569;">
                    </td>
                `;
            }
            if (col.id === 'item_name') {
                return `
                    <td>
                        <input type="text" value="${escapeHtml(it.name || '')}" ${isLocked ? 'readonly' : ''} placeholder="Item Description / Cut / Brand" onchange="updatePoItemField(${idx}, 'name', this.value)" required style="font-weight: 600;">
                    </td>
                `;
            }
            if (col.id === 'uom') {
                return `
                    <td class="td-center" style="min-width: 135px;">
                        <select data-uom-select ${isLocked ? 'disabled' : ''} onchange="updatePoItemField(${idx}, 'unit', this.value)" style="text-align: center;">
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
                `;
            }
            if (col.id === 'quantity') {
                return `
                    <td class="td-num">
                        <input type="number" step="0.01" min="0.01" value="${qty || 1}" ${isLocked ? 'readonly' : ''} onchange="updatePoItemField(${idx}, 'quantity', this.value)" style="text-align: right; font-weight: 700;">
                    </td>
                `;
            }
            if (col.id === 'unit_price') {
                return `
                    <td class="td-num">
                        <input type="number" step="0.01" min="0" value="${unitPrice}" ${isLocked ? 'readonly' : ''} onchange="updatePoItemField(${idx}, 'unitPrice', this.value)" style="text-align: right; font-weight: 700;">
                    </td>
                `;
            }
            if (col.id === 'line_total') {
                return `<td class="td-num" style="font-weight: 800; color: var(--po-text-strong);">₱${formatMoney(lineTotal)}</td>`;
            }
            if (col.id === 'specs') {
                return `
                    <td>
                        <input type="text" value="${escapeHtml(it.specs || '')}" ${isLocked ? 'readonly' : ''} placeholder="Packaging / delivery note" onchange="updatePoItemField(${idx}, 'specs', this.value)">
                    </td>
                `;
            }
            if (col.id === 'rfq_quoted_qty') {
                return `<td class="td-num" style="color: #64748b; font-weight: 600;">${(parseFloat(it.rfqQuotedQty || qty) || 0).toFixed(2)}</td>`;
            }
            if (col.id === 'rfq_target_price') {
                return `<td class="td-num" style="color: #64748b;">₱${formatMoney(targetPrice)}</td>`;
            }
            if (col.id === 'price_variance_amount') {
                const color = priceDiff > 0 ? '#dc2626' : '#059669';
                return `<td class="td-num" style="font-weight: 700; color: ${color};">${priceDiff > 0 ? '+' : ''}₱${formatMoney(priceDiff)}</td>`;
            }
            if (col.id === 'price_variance_pct') {
                const color = variancePct > 0 ? '#dc2626' : '#059669';
                return `<td class="td-num" style="font-weight: 700; color: ${color};">${variancePct > 0 ? '+' : ''}${variancePct.toFixed(1)}%</td>`;
            }
            if (col.id === 'is_within_budget') {
                return `<td class="td-center"><span class="status-pill ${withinBudget ? 'approved' : 'refused'}" style="font-size: 10px; padding: 2px 6px;">${withinBudget ? 'Within Budget' : 'Over Benchmark'}</span></td>`;
            }
            if (col.id === 'received_quantity') {
                const canInputGrn = (stage === 'Issued' || stage === 'Draft');
                if (canInputGrn) {
                    return `
                        <td class="td-num">
                            <input type="number" step="0.01" min="0" value="${recQty}"
                                onchange="updatePoReceivedQty(${idx}, this.value)"
                                placeholder="0.00"
                                title="Input received quantity (GRN) for ${escapeHtml(it.name || it.sku || 'item')}"
                                style="text-align: right; font-weight: 800; color: #059669; background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 6px; padding: 4px 8px; width: 100%; max-width: 115px;">
                        </td>
                    `;
                }
                return `<td class="td-num" style="font-weight: 700; color: #059669;">${recQty.toFixed(2)}</td>`;
            }
            if (col.id === 'remaining_quantity') {
                return `<td class="td-num" style="font-weight: 700; color: ${remQty > 0 ? '#d97706' : '#64748b'};">${remQty.toFixed(2)}</td>`;
            }
            if (col.id === 'fulfillment_status') {
                const fBadge = recQty >= qty && qty > 0 ? 'Fully Received' : (recQty > 0 ? 'Partial' : 'Pending GRN');
                const fColor = recQty >= qty && qty > 0 ? '#059669' : (recQty > 0 ? '#d97706' : '#64748b');
                return `<td class="td-center"><span style="font-size: 10.5px; font-weight: 700; color: ${fColor};">${fBadge}</span></td>`;
            }
            return `<td>--</td>`;
        }).join('');

        return `
            <tr data-po-row-idx="${idx}">
                ${cells}
                <td class="td-center">
                    <button type="button" class="inv-table-filter-btn" style="color: var(--po-danger);" ${isLocked ? 'disabled' : ''} onclick="deletePoItemRow(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    updatePoTotals();
    updatePoLiveDocumentMockup();
    window.UomRegistry?.autoHydrate();
}

function updatePoItemField(idx, field, val) {
    const item = window.PoStore.activePo.items[idx];
    if (!item) return;

    if (field === 'quantity' || field === 'unitPrice' || field === 'receivedQuantity') {
        item[field] = parseFloat(val) || 0;
    } else {
        item[field] = val;
    }

    renderPoItemsTable();
}

async function updatePoReceivedQty(idx, val) {
    const po = window.PoStore?.activePo;
    if (!po || !Array.isArray(po.items) || !po.items[idx]) return;

    const item = po.items[idx];
    const rec = Math.max(0, parseFloat(val) || 0);
    item.receivedQuantity = rec;

    const stage = getNormalizedPoStage(po.status);
    if (stage === 'Issued') {
        const totalOrdered = po.items.reduce((acc, it) => acc + (parseFloat(it.quantity) || 0), 0);
        const totalReceived = po.items.reduce((acc, it) => acc + (parseFloat(it.receivedQuantity) || 0), 0);
        po.status = (totalReceived > 0 && totalOrdered > 0) ? 'Partially Received' : 'Approved / Issued';
    }

    const existingIdx = (window.PoStore.purchaseOrders || []).findIndex(p => p.poNumber === po.poNumber);
    if (existingIdx >= 0) {
        window.PoStore.purchaseOrders[existingIdx] = JSON.parse(JSON.stringify(po));
    } else {
        window.PoStore.purchaseOrders.unshift(JSON.parse(JSON.stringify(po)));
    }
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    renderPoItemsTable();
    renderPoDirectory();
    updatePoLiveDocumentMockup();

    // Persist updated received quantities to database asynchronously
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const route = window.PurchaseOrderConfig?.routes?.transitionStatus || '/purchase/api/orders/transition-status';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                po_number: po.poNumber,
                target_status: po.status || 'Approved / Issued',
                rfq_reference: po.rfqReference || null,
                vendor_id: po.vendorId ? String(po.vendorId) : null,
                vendor_name: po.vendorName || po.vendorTradeName || 'Approved Vendor',
                vendor_trade_name: po.vendorTradeName || po.vendorName || 'Approved Vendor',
                items: po.items
            })
        });
    } catch (e) {
        console.warn('updatePoReceivedQty sync fallback:', e);
    }

    showToast(`✓ Updated GRN Qty for ${item.name || item.sku}: ${rec.toFixed(2)} ${item.unit || 'PCS'}`, 'success');
}

async function receiveAllPoItems() {
    const po = window.PoStore?.activePo;
    if (!po || !Array.isArray(po.items) || po.items.length === 0) return;

    po.items.forEach(it => {
        it.receivedQuantity = parseFloat(it.quantity) || 0;
    });

    await transitionActivePoStage('Approved');
}

function addNewBlankPoItemRow() {
    window.PoStore.activePo.items.push({
        sku: 'CUSTOM-01',
        name: '',
        specs: '',
        category: 'Raw Ingredients',
        unit: 'PCS',
        quantity: 1.00,
        unitPrice: 0.00,
        receivedQuantity: 0.00
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
    const items = window.PoStore.activePo.items || [];
    const totalLines = items.length;
    let totalUnits = 0;
    let totalGross = 0;

    items.forEach(it => {
        const q = parseFloat(it.quantity) || 0;
        const p = parseFloat(it.unitPrice) || 0;
        totalUnits += q;
        totalGross += (q * p);
    });

    const elLines = document.getElementById('summaryPoLines');
    const elUnits = document.getElementById('summaryPoUnits');
    const elGross = document.getElementById('summaryPoGross');
    const elBadge = document.getElementById('poItemCountBadge');

    if (elLines) elLines.textContent = totalLines;
    if (elUnits) elUnits.textContent = totalUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (elGross) elGross.textContent = `₱${formatMoney(totalGross)}`;
    if (elBadge) elBadge.textContent = `${totalLines} Line Item${totalLines === 1 ? '' : 's'}`;

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
async function issuePurchaseOrderSubmit() {
    syncPoFormInputsToState();
    const po = window.PoStore.activePo;
    const isMarket = po.poType === 'wet_market';
    const getVal = (id) => document.getElementById(id)?.value?.trim() || '';

    const sourceName = isMarket
        ? (getVal('poMarketStallName') || po.vendorName || po.vendorTradeName)
        : (po.vendorTradeName || po.vendorName || getVal('poVendorSelect'));

    if (!sourceName) {
        showToast(isMarket ? 'Please enter the wet market stall name' : 'Please select an approved vendor', 'error');
        return;
    }

    if (!po.items || po.items.length === 0) {
        showToast('Please add at least one line item to the purchase order', 'error');
        return;
    }

    // Collect inputs safely
    po.vendorName = sourceName;
    po.vendorTradeName = sourceName;
    po.vendorContactPerson = getVal('poContactPerson') || po.vendorContactPerson || '';
    po.vendorPhone = getVal('poContactPhone') || po.vendorPhone || '';
    po.vendorEmail = getVal('poContactEmail') || po.vendorEmail || '';
    po.vendorAddress = getVal('poFullAddress') || getVal('poSourceLocation') || po.vendorAddress || '';
    po.orderDate = getVal('poOrderDate') || po.orderDate || new Date().toISOString().split('T')[0];
    po.expectedDelivery = getVal('poExpectedDelivery') || po.expectedDelivery || '';
    po.rfqReference = getVal('poRfqReference') || po.rfqReference || '';
    po.deliveryLocation = getVal('poDeliveryLocation') || po.deliveryLocation || 'Central Commissary - Receiving Dock 1';
    po.specialNotes = getVal('poOrderTitle') || getVal('poSpecialNotes') || po.orderTitle || po.specialNotes || '';
    po.orderTitle = po.specialNotes;

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

    // Remove from deleted set if present
    try {
        const deletedPos = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        if (Array.isArray(deletedPos) && deletedPos.includes(po.poNumber)) {
            localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(deletedPos.filter(n => n !== po.poNumber)));
        }
    } catch (e) {}

    // Save to list
    const existingIdx = (window.PoStore.purchaseOrders || []).findIndex(p => p.poNumber === po.poNumber);
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

    // Synchronize issued PO to database backend
    try {
        await fetch(window.PurchaseOrderConfig?.routes?.ordersCreate || '/purchase/api/orders/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                po_number: po.poNumber,
                po_type: po.poType,
                rfq_reference: po.rfqReference || null,
                vendor_id: po.vendorId ? String(po.vendorId) : null,
                vendor_name: po.vendorName,
                vendor_trade_name: po.vendorTradeName,
                vendor_contact_person: po.vendorContactPerson,
                vendor_phone: po.vendorPhone,
                vendor_email: po.vendorEmail,
                vendor_address: po.vendorAddress,
                order_date: po.orderDate || null,
                expected_delivery: po.expectedDelivery || null,
                delivery_location: po.deliveryLocation,
                special_notes: po.specialNotes,
                payment_status: po.paymentStatus,
                payment_method: po.paymentMethod,
                amount_paid: po.amountPaid,
                balance_due: po.balanceDue,
                payment_reference: po.paymentReference,
                payment_date: po.paymentDate || null,
                fund_source: po.fundSource,
                payment_remarks: po.paymentRemarks,
                approval_notes: po.approvalNotes,
                approved_by: po.approvedBy,
                status: po.status,
                items: (po.items || []).map(i => ({
                    sku: i.sku || 'ITEM',
                    name: i.name || 'Ordered Item',
                    specs: i.specs || '',
                    category: i.category || 'Raw Ingredients',
                    unit: i.unit || 'PCS',
                    quantity: Math.max(0.01, parseFloat(i.quantity) || 1),
                    unitPrice: Math.max(0, parseFloat(i.unitPrice) || 0)
                }))
            })
        });
    } catch (e) {
        console.warn('PO DB sync note:', e);
    }

    applyPoStageDefaultColumns(po.status);
    updatePoLifecycleProgressBar(po.status);
    renderPoBuilderTableHeader();
    renderPoItemsTable();
    renderPoDirectory();
    updatePoLiveDocumentMockup();
    showToast(`✓ Purchase Order ${po.poNumber} approved and issued!`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * PO DIRECTORY COLUMN CONFIGURATION & PAGINATION SYSTEM
 * --------------------------------------------------------------------------
 */
const PO_DIRECTORY_COLUMNS = [
    { id: 'ref', label: 'PO Info', subLabel: 'PO # / Source RFQ', default: true, lockVisible: true, defaultWidth: '160px', align: 'left' },
    { id: 'vendor', label: 'Vendor & Contact', subLabel: 'Supplier / Contact Person', default: true, lockVisible: true, defaultWidth: '215px', align: 'left' },
    { id: 'dates', label: 'Order / Delivery Date', subLabel: 'Issued / Expected Arrival', default: true, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'destination', label: 'Branch & Warehouse', subLabel: 'Ship-To / Receiving Dock', default: true, lockVisible: false, defaultWidth: '175px', align: 'left' },
    { id: 'items', label: 'Order Valuation', subLabel: 'Gross Total (₱) / Items', default: true, lockVisible: false, defaultWidth: '145px', align: 'right' },
    { id: 'fulfillment', label: 'Fulfillment (GRN)', subLabel: 'Dock Status / Units', default: true, lockVisible: false, defaultWidth: '145px', align: 'left' },
    { id: 'payment', label: 'Payment / Terms', subLabel: 'Settlement / Credit Terms', default: true, lockVisible: false, defaultWidth: '150px', align: 'left' },
    { id: 'status', label: 'PO Status', subLabel: 'Lifecycle / Approval State', default: true, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'company', label: 'Company', subLabel: 'Buying Legal Entity', default: false, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'currency_incoterms', label: 'Currency & Incoterms', subLabel: 'ISO 4217 / Trade Terms', default: false, lockVisible: false, defaultWidth: '140px', align: 'left' },
    { id: 'amount_paid', label: 'Amount Paid', subLabel: 'Disbursed / Balance (₱)', default: false, lockVisible: false, defaultWidth: '145px', align: 'right' },
    { id: 'payment_reference', label: 'Payment Reference', subLabel: 'Check / Ref # & Date', default: false, lockVisible: false, defaultWidth: '145px', align: 'left' },
    { id: 'po_notes', label: 'PO Notes & Purpose', subLabel: 'Title / Receiving Instructions', default: false, lockVisible: false, defaultWidth: '180px', align: 'left' },
    { id: 'approval_details', label: 'Approval Details', subLabel: 'Approver / Remarks', default: false, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'created_by', label: 'Created By', subLabel: 'Buyer / Created Date', default: false, lockVisible: false, defaultWidth: '145px', align: 'left' },
    { id: 'actions', label: 'Actions', subLabel: 'Edit / Preview / Del', default: true, lockVisible: true, defaultWidth: '165px', align: 'right' }
];

let selectedPoNumbers = new Set();
let poDirectoryColSearchTerm = '';

let activePoColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_cols_v4');
        if (saved) {
            const parsed = JSON.parse(saved);
            const validIds = PO_DIRECTORY_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length >= 6 && parsed.every(id => validIds.includes(id))) {
                return parsed;
            }
        }
    } catch (e) {}
    return PO_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
})();

let savedPoColWidths = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_col_widths_v3');
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

    let thHtml = `
        <th style="width: 38px; min-width: 38px; text-align: center; vertical-align: middle;">
            <input type="checkbox" id="selectAllPoCheckbox" onchange="toggleSelectAllPos(this.checked)" title="Select all POs on this page" aria-label="Select all POs">
        </th>
    `;

    PO_DIRECTORY_COLUMNS.forEach(col => {
        if (!activePoColIds.includes(col.id)) return;
        const w = getPoColWidth(col);
        const isRight = (col.align === 'right');
        const alignClass = isRight ? 'th-num' : 'th-left';

        if (col.id === 'actions') {
            thHtml += `
                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: right; position: relative; user-select: none;">
                    <div class="inv-header-action-wrapper" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                        <div class="rfq-th-content" style="align-items: flex-end; text-align: right;">
                            <div class="rfq-th-title">${escapeHtml(col.label || 'Actions')}</div>
                            <div class="rfq-th-sub">${escapeHtml(col.subLabel || 'Edit / Preview / Del')}</div>
                        </div>
                        <button type="button" class="rfq-th-settings-btn inv-table-filter-btn" id="btnPoActionColFilter" onclick="togglePoTableSettingsDropdown(event)" title="Customize Tracker Columns">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            const hasStatusFilter = (col.id === 'status');
            const hasPaymentFilter = (col.id === 'payment');

            thHtml += `
                <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}" style="width: ${w}; text-align: ${isRight ? 'right' : 'left'}; position: relative; user-select: none;">
                    <div style="display: flex; align-items: center; justify-content: ${isRight ? 'flex-end' : 'space-between'}; gap: 6px; min-width: 0; width: 100%;">
                        <div class="rfq-th-content" style="align-items: ${isRight ? 'flex-end' : 'flex-start'}; text-align: ${isRight ? 'right' : 'left'};">
                            <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                            ${col.subLabel ? `<div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>` : ''}
                        </div>
                        ${hasStatusFilter ? `
                            <button type="button" class="po-th-funnel-btn ${isStatusFiltered ? 'is-active' : ''}" onclick="togglePoHeaderFilter(event, 'status')" title="Click to filter by Approval Status & Order Status">
                                <i class="ph ph-funnel"></i>
                                ${isStatusFiltered ? '<span class="po-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                        ${hasPaymentFilter ? `
                            <button type="button" class="po-th-funnel-btn ${isPaymentFiltered ? 'is-active' : ''}" onclick="togglePoHeaderFilter(event, 'payment')" title="Click to filter by Payment & Terms">
                                <i class="ph ph-funnel"></i>
                                ${isPaymentFiltered ? '<span class="po-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                    </div>
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
    const listEl = document.getElementById('poDirectoryColCheckboxesList') || document.getElementById('poColChecklist');
    if (!listEl) return;

    const q = (poDirectoryColSearchTerm || '').toLowerCase().trim();
    const colsToShow = PO_DIRECTORY_COLUMNS.filter(c => !q || c.label.toLowerCase().includes(q) || (c.subLabel && c.subLabel.toLowerCase().includes(q)));

    listEl.innerHTML = colsToShow.map(col => {
        const isChecked = activePoColIds.includes(col.id);
        const isDisabled = col.lockVisible ? 'disabled' : '';
        return `
            <label class="inv-col-item-row" title="${col.label}" style="display: flex; align-items: center; justify-content: space-between; padding: 5px 8px; font-size: 12px; border-radius: 6px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                <span style="display: flex; align-items: center; gap: 7px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${isChecked ? '600' : '400'};">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="togglePoColumnVisibility('${col.id}', this.checked)" style="accent-color: #9333ea; width: 14px; height: 14px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                    <span>
                        <div>${escapeHtml(col.label)}</div>
                        ${col.subLabel ? `<div style="font-size: 10px; color: #94a3b8; font-weight: 400;">${escapeHtml(col.subLabel)}</div>` : ''}
                    </span>
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

function togglePoTableSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('poTableSettingsDropdown');
    if (!dropdown) return;

    const isVisible = dropdown.style.display !== 'none';
    if (isVisible) {
        dropdown.style.display = 'none';
        return;
    }

    const trigger = event?.currentTarget || document.getElementById('btnPoColConfig');
    if (trigger) {
        const rect = trigger.getBoundingClientRect();
        dropdown.style.top = `${Math.min(window.innerHeight - 360, rect.bottom + 6)}px`;
        dropdown.style.left = `${Math.max(12, rect.right - 310)}px`;
    }
    dropdown.style.display = 'block';
    renderPoColumnChecklist();
}

function handlePoDirectoryColSearch(q) {
    poDirectoryColSearchTerm = q || '';
    renderPoColumnChecklist();
}

function togglePoColumnConfigDropdown(event) {
    togglePoTableSettingsDropdown(event);
}

function togglePoColumnVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activePoColIds.includes(colId)) {
            activePoColIds.push(colId);
        }
    } else {
        activePoColIds = activePoColIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_po_directory_cols_v4', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
}

function showAllPoColumns() {
    activePoColIds = PO_DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_po_directory_cols_v4', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ All tracker columns visible', 'success');
}

function resetPoColumnDefaults() {
    activePoColIds = PO_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    localStorage.setItem('rms_po_directory_cols_v4', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ Reset to default tracker columns', 'success');
}

function resetPoColumns() {
    resetPoColumnDefaults();
}

function resetPoColumnWidths() {
    savedPoColWidths = {};
    localStorage.removeItem('rms_po_directory_col_widths_v3');
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
    localStorage.setItem('rms_po_directory_col_widths_v3', JSON.stringify(savedPoColWidths));
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
    const pos = (window.PoStore.purchaseOrders || []).filter(p => p.status !== 'Archived');
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

    // KPI Counts (5 Cards)
    const total = pos.length;
    const drafts = pos.filter(p => p.status === 'Draft PO' || p.status === 'Draft' || p.status === 'Pending Approval').length;
    const pending = pos.filter(p => p.status === 'Approved / Issued' || p.status === 'Partially Received').length;
    const received = pos.filter(p => p.status === 'Fully Received').length;
    const paid = pos.filter(p => p.paymentStatus === 'Paid in Full / Cash Out').length;

    const elKpiTotal = document.getElementById('kpiPoTotal');
    const elKpiDrafts = document.getElementById('kpiPoDrafts');
    const elKpiPending = document.getElementById('kpiPoPending');
    const elKpiReceived = document.getElementById('kpiPoReceived');
    const elKpiPaid = document.getElementById('kpiPoPaid');
    const elTabCount = document.getElementById('tabPoCount');

    if (elKpiTotal) elKpiTotal.textContent = total;
    if (elKpiDrafts) elKpiDrafts.textContent = drafts;
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
    updatePoBatchToolbarUI();

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${(activePoColIds.length || 8) + 1}" style="text-align: center; padding: 36px; color: var(--po-text-muted);">No Purchase Orders found matching the filter criteria.</td></tr>`;
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
        const totalOrderedUnits = (p.items || []).reduce((acc, it) => acc + (parseFloat(it.quantity) || 0), 0);
        const totalReceivedUnits = (p.items || []).reduce((acc, it) => acc + (parseFloat(it.receivedQuantity || 0)), 0);
        const isMarket = p.poType === 'wet_market';
        let payBadgeClass = 'unpaid';
        if (p.paymentStatus === 'Paid in Full / Cash Out') payBadgeClass = 'paid';
        else if (p.paymentStatus === 'Partial Payment') payBadgeClass = 'partial';

        let poStatusClass = 'draft';
        if (p.status === 'Approved / Issued' || p.status === 'Partially Received') poStatusClass = 'approved';
        else if (p.status === 'Fully Received') poStatusClass = 'received';
        else if (p.status === 'Pending Approval') poStatusClass = 'approval';

        const isChecked = selectedPoNumbers.has(p.poNumber);

        let cellsHtml = `
            <td style="text-align: center;">
                <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleSelectSinglePo('${escapeHtml(p.poNumber)}', this.checked)" aria-label="Select ${escapeHtml(p.poNumber)}">
            </td>
        `;

        PO_DIRECTORY_COLUMNS.forEach(col => {
            if (!activePoColIds.includes(col.id)) return;

            if (col.id === 'ref') {
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title">
                                <a href="javascript:void(0)" class="po-dir-ref-link" onclick="editPoFromDirectory('${escapeHtml(p.poNumber)}')" title="Open ${escapeHtml(p.poNumber)} in Builder">
                                    ${escapeHtml(p.poNumber)}
                                </a>
                            </div>
                            <div class="rms-cell-sub" title="RFQ: ${escapeHtml(p.rfqReference || 'Direct Buy')}">
                                ${p.rfqReference ? `<button type="button" class="po-linked-rfq-sublink" onclick="openLinkedRfqModal('${escapeHtml(p.rfqReference)}')"><i class="ph ph-link"></i> ${escapeHtml(p.rfqReference)} <i class="ph ph-arrow-up-right" style="font-size: 9px;"></i></button>` : `<span style="color: #94a3b8; font-family: monospace;">Direct Order</span>`}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'vendor') {
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" title="${escapeHtml(p.vendorTradeName || p.vendorName || 'Supplier')}">
                                ${escapeHtml(p.vendorTradeName || p.vendorName || 'Supplier')}
                                ${isMarket ? '<span style="font-size: 0.65rem; background: #fef3c7; color: #b45309; padding: 1px 4px; border-radius: 3px; font-weight: 600; margin-left: 4px;">Market</span>' : ''}
                            </div>
                            <div class="rms-cell-sub" title="${escapeHtml((p.vendorContactPerson || 'Purchaser') + ' • ' + (p.vendorPhone || p.vendorEmail || ''))}">
                                <i class="ph ph-user" style="font-size: 11px; color: #94a3b8;"></i> ${escapeHtml(p.vendorContactPerson || 'Purchasing Contact')}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'dates') {
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" title="Order Date: ${formatDateDisplay(p.orderDate)}">
                                ${formatDateDisplay(p.orderDate)}
                            </div>
                            <div class="rms-cell-sub" title="Expected Delivery: ${formatDateDisplay(p.expectedDelivery)}">
                                Arrival: ${formatDateDisplay(p.expectedDelivery)}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'destination') {
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack">
                            <div class="rms-cell-title" title="${escapeHtml(p.deliveryLocation || 'Main Commissary / Outlet')}">
                                ${escapeHtml(p.deliveryLocation || 'Main Commissary')}
                            </div>
                            <div class="rms-cell-sub" title="${escapeHtml(p.shippingNotes || 'Central Warehouse')}">
                                <i class="ph ph-warehouse" style="font-size: 11px; color: #94a3b8;"></i> ${escapeHtml(p.shippingNotes || 'Central Warehouse')}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'items') {
                const lineCount = (p.items || []).length;
                cellsHtml += `
                    <td class="td-num">
                        <div class="rms-cell-stack rms-stack-right">
                            <div class="rms-cell-title" style="color: #0f172a; font-weight: 800; font-variant-numeric: tabular-nums;">₱${formatMoney(gross)}</div>
                            <div class="rms-cell-sub" style="justify-content: flex-end;">${lineCount} line item${lineCount === 1 ? '' : 's'}</div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'fulfillment') {
                const isFull = p.status === 'Fully Received' || (totalOrderedUnits > 0 && totalReceivedUnits >= totalOrderedUnits);
                const isPart = !isFull && (p.status === 'Partially Received' || totalReceivedUnits > 0);
                const fLabel = isFull ? '100% Received' : (isPart ? 'Partial GRN' : 'Awaiting Dock');
                const fClass = isFull ? 'po-appr-approved' : (isPart ? 'po-appr-pending' : 'po-appr-neutral');
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack" style="align-items: flex-start; gap: 3px;">
                            <span class="po-appr-pill ${fClass}">${fLabel}</span>
                            <div class="rms-cell-sub" style="font-variant-numeric: tabular-nums;">${isFull ? totalOrderedUnits.toFixed(1) : totalReceivedUnits.toFixed(1)} / ${totalOrderedUnits.toFixed(1)} units</div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'payment') {
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack" style="align-items: flex-start; gap: 3px;">
                            <span class="pay-badge ${payBadgeClass}" style="cursor: pointer; padding: 2px 8px; font-size: 0.70rem; border-radius: 6px;" onclick="openPaymentSettlementModal('${escapeHtml(p.poNumber)}')" title="View Financial Settlement">
                                ${escapeHtml(p.paymentStatus || 'Unpaid')}
                            </span>
                            <div class="rms-cell-sub" title="${escapeHtml(p.paymentMethod || p.paymentRemarks || 'Standard Terms')}">
                                ${escapeHtml(p.paymentTerms || p.paymentMethod || 'Net 30 Days')}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'status') {
                const isApproved = (p.status === 'Approved / Issued' || p.status === 'Partially Received' || p.status === 'Fully Received' || p.isApproved === true);
                cellsHtml += `
                    <td class="td-left">
                        <div class="rms-cell-stack" style="align-items: flex-start; gap: 3px;">
                            <span class="po-status-badge ${poStatusClass}" style="font-size: 0.70rem; padding: 2px 8px;">
                                <span class="status-pill-dot"></span> ${escapeHtml(p.status)}
                            </span>
                            <div class="rms-cell-sub">
                                ${isApproved ? '<span style="color: #047857; font-weight: 600;"><i class="ph ph-check-circle"></i> Manager Approved</span>' : '<span style="color: #b45309; font-weight: 600;"><i class="ph ph-clock"></i> Pending Sign-off</span>'}
                            </div>
                        </div>
                    </td>
                `;
            } else if (col.id === 'company') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title">Top C Culinary Group</div><div class="rms-cell-sub" style="font-family: monospace;">COMP-01</div></div></td>`;
            } else if (col.id === 'currency_incoterms') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title">PHP (₱)</div><div class="rms-cell-sub">DDP Commissary</div></div></td>`;
            } else if (col.id === 'amount_paid') {
                const paidAmt = parseFloat(p.amountPaid || 0);
                const balAmt = Math.max(0, gross - paidAmt);
                cellsHtml += `<td class="td-num"><div class="rms-cell-stack rms-stack-right"><div class="rms-cell-title" style="color: #059669; font-variant-numeric: tabular-nums;">₱${formatMoney(paidAmt)}</div><div class="rms-cell-sub" style="justify-content: flex-end; font-variant-numeric: tabular-nums;">Bal: ₱${formatMoney(balAmt)}</div></div></td>`;
            } else if (col.id === 'payment_reference') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title" style="font-family: monospace;">${escapeHtml(p.paymentReference || 'N/A')}</div><div class="rms-cell-sub">${formatDateDisplay(p.paymentDate) || '--'}</div></div></td>`;
            } else if (col.id === 'po_notes') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title">${escapeHtml(p.orderTitle || 'Standard PO')}</div><div class="rms-cell-sub">${escapeHtml(p.specialNotes || '--')}</div></div></td>`;
            } else if (col.id === 'approval_details') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title">${escapeHtml(p.approvedBy || 'Procurement Manager')}</div><div class="rms-cell-sub">${escapeHtml(p.approvalNotes || 'Verified')}</div></div></td>`;
            } else if (col.id === 'created_by') {
                cellsHtml += `<td class="td-left"><div class="rms-cell-stack"><div class="rms-cell-title">${escapeHtml(p.createdByUser || 'Procurement Specialist')}</div><div class="rms-cell-sub">${formatDateDisplay(p.orderDate)}</div></div></td>`;
            } else if (col.id === 'actions') {
                cellsHtml += `
                    <td class="td-num td-actions-cell">
                        <div class="po-row-actions">
                            ${(p.status === 'Approved / Issued' || p.status === 'Partially Received') ? `
                                <a href="${window.PurchaseOrderConfig?.routes?.inventoryStockIn || '/inventory/stock-in'}?po=${encodeURIComponent(p.poNumber)}" class="po-act-pill po-act-pill-grn" title="Receive Stock in Inventory (Stock In / GRN)">
                                    <i class="ph ph-tray-arrow-down"></i>
                                    <span>Stock-In</span>
                                </a>
                            ` : ''}
                            <button type="button" class="po-act-icon-btn" onclick="editPoFromDirectory('${escapeHtml(p.poNumber)}')" title="Edit Purchase Order in Builder">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button type="button" class="po-act-icon-btn" onclick="previewPoFromDirectory('${escapeHtml(p.poNumber)}')" title="Preview Printable PO">
                                <i class="ph ph-eye"></i>
                            </button>
                            <button type="button" class="po-act-icon-btn po-act-icon-danger" onclick="deletePo('${escapeHtml(p.poNumber)}')" title="Delete PO">
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
    if (status === 'all') activePoPaymentFilter = 'all';
    poCurrentPage = 1;
    document.querySelectorAll('#pane-tab-po-list .po-kpi-card[data-kpi-filter]').forEach(card => {
        card.classList.toggle('is-active-filter', card.getAttribute('data-kpi-filter') === status);
    });
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
    document.querySelectorAll('#pane-tab-po-list .po-kpi-card[data-kpi-filter]').forEach(card => {
        card.classList.toggle('is-active-filter', card.getAttribute('data-kpi-filter') === payStatus);
    });
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
    window.PoStore._isExplicitlyEditing = true;

    // Update Form
    const numIn = document.getElementById('poNumberInput');
    if (numIn) numIn.value = po.poNumber;
    const refDisp = document.getElementById('poRefDisplay');
    if (refDisp) refDisp.textContent = po.poNumber;
    const titleIn = document.getElementById('poOrderTitle');
    if (titleIn) {
        titleIn.value = po.orderTitle || po.purpose || '';
    }

    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) {
        if (po.rfqReference) {
            rfqBadge.style.display = 'inline-flex';
            rfqBadge.innerHTML = `<i class="ph ph-link"></i> Source RFQ: ${escapeHtml(po.rfqReference)}`;
        } else {
            rfqBadge.style.display = 'none';
        }
    }

    setPoMode(po.poType || 'vendor');

    const stallIn = document.getElementById('poMarketStallName');
    if (po.poType === 'wet_market' && stallIn) {
        stallIn.value = po.vendorName || '';
    } else if (document.getElementById('poVendorSelect')) {
        document.getElementById('poVendorSelect').value = po.vendorId || '';
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === po.vendorId || v.code === po.vendorId || v.vendorCode === po.vendorId)) || (po.vendorName ? {
        id: po.vendorId || 'VND',
        code: po.vendorId || 'VND',
        tradeName: po.vendorTradeName || po.vendorName,
        legalName: po.vendorName,
        category: 'Vendor',
        address: po.vendorAddress
    } : null);
    updatePoVendorSummaryCard(vendor);

    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    setVal('poContactPerson', po.vendorContactPerson || '');
    setVal('poContactPhone', po.vendorPhone || '');
    setVal('poContactEmail', po.vendorEmail || '');
    setVal('poFullAddress', po.vendorAddress || '');
    setVal('poOrderDate', po.orderDate || '');
    setVal('poExpectedDelivery', po.expectedDelivery || '');
    setVal('poRfqReference', po.rfqReference || '');
    setVal('poDeliveryLocation', po.deliveryLocation || 'Central Commissary - Receiving Dock 1');
    setVal('poSpecialNotes', po.specialNotes || '');
    setVal('poPaymentTerms', po.paymentTerms || 'Net 30 Days');

    // Payment fields
    setVal('poPaymentStatus', po.paymentStatus || 'Unpaid / Credit');
    setVal('poPaymentMethod', po.paymentMethod || 'Trade Credit (Net 30/15)');
    setVal('poAmountPaid', parseFloat(po.amountPaid || 0).toFixed(2));
    setVal('poPaymentRef', po.paymentReference || '');
    setVal('poPaymentDate', po.paymentDate || '');
    setVal('poFundSource', po.fundSource || 'Main Commissary Checking Acct');
    setVal('poPaymentRemarks', po.paymentRemarks || '');

    applyPoStageDefaultColumns(po.status || 'Draft PO');
    updatePoLifecycleProgressBar(po.status || 'Draft PO');
    renderPoItemsTable();
    handlePaymentStatusChange(po.paymentStatus || 'Unpaid / Credit');
    updatePoLiveDocumentMockup();

    const badge = document.getElementById('tabPoBuilderBadge');
    if (badge) badge.textContent = `Editing ${po.poNumber}`;
    switchPoTab('tab-po-builder');
    showToast(`Loaded ${po.poNumber} into Purchase Order Builder`, 'success');
}

function previewPoFromDirectory(poNumber) {
    const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    window.PoStore.activePo = JSON.parse(JSON.stringify(po));
    openPoDocumentPreview();
}

async function deletePo(poNumber) {
    if (!confirm(`Are you sure you want to delete Purchase Order ${poNumber}?`)) return;

    const deletedPo = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const route = window.PurchaseOrderConfig?.routes?.batchDelete || '/purchase/api/orders/batch-delete';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({ po_numbers: [poNumber], force: true })
        });
    } catch (e) {
        console.warn('deletePo API fallback:', e);
    }

    // Track in deleted PO numbers so seeded/cached entries do not resurrect
    try {
        const deletedList = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        if (Array.isArray(deletedList) && !deletedList.includes(poNumber)) {
            deletedList.push(poNumber);
            localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(deletedList));
        }
    } catch (e) {}

    // Unlink any RFQ in localStorage rms_rfq_directory that pointed to this PO
    try {
        const rfqList = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
        if (Array.isArray(rfqList)) {
            let changed = false;
            rfqList.forEach(r => {
                if (r && (r.poReference === poNumber || (deletedPo && deletedPo.rfqReference && r.rfqNumber === deletedPo.rfqReference))) {
                    r.poReference = '';
                    changed = true;
                }
            });
            if (changed) localStorage.setItem('rms_rfq_directory', JSON.stringify(rfqList));
        }
    } catch (e) {}

    selectedPoNumbers.delete(poNumber);
    window.PoStore.purchaseOrders = (window.PoStore.purchaseOrders || []).filter(p => p.poNumber !== poNumber);
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    logProcurementAudit(
        'PO',
        poNumber,
        'DELETED',
        'Purchase Order Cancelled / Deleted',
        `Purchase Order ${poNumber} was deleted and removed from registry.`,
        [{ label: 'Supplier / Stall', value: deletedPo ? (deletedPo.vendorTradeName || deletedPo.vendorName) : 'N/A' }]
    );

    updatePoBatchToolbarUI();
    renderPoDirectory();
    showToast(`✓ Purchase Order ${poNumber} deleted`, 'success');
}

function resetPoForm() {
    const poNum = getNextUniquePoNumber();
    window.PoStore._isExplicitlyEditing = false;
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
        deliveryLocation: 'Central Commissary - Receiving Dock 1',
        paymentTerms: 'Net 30 Days',
        specialNotes: '',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 0.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: '',
        approvalNotes: '',
        approvedBy: 'Procurement Specialist',
        status: 'Draft PO',
        attachedFiles: [],
        items: []
    };

    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    setVal('poNumberInput', poNum);
    const refDisp = document.getElementById('poRefDisplay');
    if (refDisp) refDisp.textContent = poNum;
    setVal('poOrderTitle', '');
    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) { rfqBadge.style.display = 'none'; rfqBadge.textContent = ''; }
    setVal('poVendorSelect', '');
    updatePoVendorSummaryCard(null);
    renderPoVendorComboboxList();

    setVal('poMarketStallName', '');
    setVal('poContactPerson', '');
    setVal('poContactPhone', '');
    setVal('poContactEmail', '');
    setVal('poFullAddress', '');
    setVal('poRfqReference', '');
    setVal('poPaymentStatus', 'Unpaid / Credit');
    setVal('poAmountPaid', '0.00');
    setVal('poPaymentRef', '');
    const badge = document.getElementById('tabPoBuilderBadge');
    if (badge) badge.textContent = 'New PO';

    setPoMode('vendor');
    initPoDates();
    applyPoStageDefaultColumns('Draft PO');
    updatePoLifecycleProgressBar('Draft PO');
    renderPoItemsTable();
    updatePoLiveDocumentMockup();
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
    const getVal = id => document.getElementById(id)?.value || '';
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    const paid = parseFloat(getVal('poAmountPaid')) || (po.amountPaid || 0);
    const balance = Math.max(0, gross - paid);

    setTxt('docPoNumber', po.poNumber);
    setTxt('docPoDate', formatDateDisplay(getVal('poOrderDate') || po.orderDate));
    setTxt('docPoDeliveryDue', formatDateDisplay(getVal('poExpectedDelivery') || po.expectedDelivery));

    const vendorTitle = isMarket ? 
        (getVal('poMarketStallName') || po.vendorName || 'Wet Market Stall') : 
        (po.vendorTradeName || po.vendorName || 'Vendor Partner');

    setTxt('docPoVendor', vendorTitle);
    const detailsEl = document.getElementById('docPoVendorDetails');
    if (detailsEl) {
        detailsEl.innerHTML = `
            <strong>Attn:</strong> ${escapeHtml(getVal('poContactPerson') || po.vendorContactPerson || 'Representative')}<br>
            <strong>Phone:</strong> ${escapeHtml(getVal('poContactPhone') || po.vendorPhone || 'N/A')}<br>
            <strong>Address / Market:</strong> ${escapeHtml(getVal('poFullAddress') || po.vendorAddress || 'Metro Manila')}
        `;
    }

    setTxt('docPoDeliveryDest', getVal('poDeliveryLocation') || po.deliveryLocation);
    setTxt('docPoPaymentStatus', getVal('poPaymentStatus') || po.paymentStatus);
    setTxt('docPoPaymentMethod', getVal('poPaymentMethod') || po.paymentMethod);
    setTxt('docPoPaymentRef', getVal('poPaymentRef') || po.paymentReference || 'N/A');

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

    setTxt('docPoGross', `₱${formatMoney(gross)}`);
    setTxt('docPoPaid', `₱${formatMoney(paid)}`);
    setTxt('docPoBalance', `₱${formatMoney(balance)}`);

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

    const approvedRfqs = rfqs.filter(r => (r.status === 'Awarded' || r.rfqStatus === 'APPROVED' || r.isApproved === true));

    if (approvedRfqs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px 16px; color: #64748b;">
                <i class="ph ph-files" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 700; color: #0f172a;">No Approved RFQs Available</div>
                <div style="font-size: 12px; margin-top: 4px;">Only tenders with <strong>Awarded / Approved</strong> status can be converted into Purchase Orders.</div>
            </div>
        `;
    } else {
        const allPos = window.PoStore?.purchaseOrders || [];
        container.innerHTML = approvedRfqs.map(r => {
            const existingPo = allPos.find(p => p.rfqReference === r.rfqNumber || (r.poReference && p.poNumber === r.poReference));
            const linkedPoNum = existingPo ? existingPo.poNumber : (r.poReference || '');
            return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.15s ease;">
                <div style="display: flex; flex-direction: column; gap: 3px; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-family: monospace; font-weight: 800; color: #9333ea; font-size: 13px;">${escapeHtml(r.rfqNumber)}</span>
                        <span class="status-pill approved" style="font-size: 10.5px; padding: 2px 7px;"><i class="ph ph-check-circle"></i> Awarded Tender</span>
                        ${linkedPoNum ? `<span style="font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 99px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="ph ph-file-text"></i> PO Created: ${escapeHtml(linkedPoNum)}</span>` : ''}
                    </div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${escapeHtml(r.vendorName || r.vendorTradeName || 'Contracted Supplier')}
                    </div>
                    <div style="font-size: 11.5px; color: #64748b;">
                        ${escapeHtml(r.projectTitle || r.purpose || 'General Procurement')} • Issued: ${formatDateDisplay(r.dateIssued)} • Items: ${(r.items || []).length} lines
                    </div>
                </div>
                ${linkedPoNum ? `
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeImportRfqModal(); editPo('${escapeHtml(linkedPoNum)}')" style="flex-shrink: 0; padding: 6px 14px; font-size: 12px; background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; font-weight: 700;">
                    <i class="ph ph-arrow-square-out"></i> View PO (${escapeHtml(linkedPoNum)})
                </button>
                ` : `
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="importApprovedRfqToBuilder('${escapeHtml(r.rfqNumber)}')" style="flex-shrink: 0; padding: 6px 14px; font-size: 12px;">
                    <i class="ph ph-arrow-down-left"></i> Import to Builder
                </button>
                `}
            </div>
        `}).join('');
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

    // Prevent duplicate PO if one already exists for this RFQ
    const existingPo = (window.PoStore?.purchaseOrders || []).find(p => p.rfqReference === rfq.rfqNumber || (rfq.poReference && p.poNumber === rfq.poReference));
    if (existingPo) {
        editPo(existingPo.poNumber);
        showToast(`Opened existing Purchase Order ${existingPo.poNumber} for ${rfq.rfqNumber}`, 'info');
        return;
    }

    // Populate Active PO with collision-free PO Number
    const nextPoNum = rfq.poReference || getNextUniquePoNumber();

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
    window.PoStore.activePo.poStatus = 'DRAFT';

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

    // Update DOM inputs safely
    const setElVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
    setElVal('poNumberInput', nextPoNum);
    const refDisp = document.getElementById('poRefDisplay');
    if (refDisp) refDisp.textContent = nextPoNum;
    setElVal('poOrderTitle', window.PoStore.activePo.orderTitle);
    setElVal('poVendorSelect', rfq.vendorId || '');
    setElVal('poContactPerson', window.PoStore.activePo.vendorContactPerson);
    setElVal('poContactPhone', window.PoStore.activePo.vendorPhone);
    setElVal('poContactEmail', window.PoStore.activePo.vendorEmail);
    setElVal('poFullAddress', window.PoStore.activePo.vendorAddress);
    setElVal('poPaymentTerms', window.PoStore.activePo.paymentTerms);
    setElVal('poExpectedDelivery', window.PoStore.activePo.expectedDelivery);
    setElVal('poDeliveryLocation', window.PoStore.activePo.deliveryLocation);
    setElVal('poSpecialNotes', window.PoStore.activePo.specialNotes);

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
    recalculatePoTotals();
    updatePoLiveDocumentMockup();
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

    let rfq = rfqs.find(r => r.rfqNumber === rfqNum);
    if (!rfq) {
        // Synthesize from linked PO in PoStore if RFQ isn't cached in localStorage
        const linkedPo = (window.PoStore?.purchaseOrders || []).find(p => p.rfqReference === rfqNum) ||
            (window.PoStore?.activePo?.rfqReference === rfqNum ? window.PoStore.activePo : null);
        if (linkedPo) {
            rfq = {
                rfqNumber: rfqNum,
                vendorTradeName: linkedPo.vendorTradeName || linkedPo.vendorName || 'Awarded Supplier',
                vendorName: linkedPo.vendorName || linkedPo.vendorTradeName || 'Awarded Supplier',
                vendorAddress: linkedPo.vendorAddress || 'Metro Manila',
                dateIssued: linkedPo.dateCreated || linkedPo.orderDate || '',
                items: (linkedPo.items || []).map(it => ({
                    name: it.name || it.itemDescription || 'Awarded Item',
                    unit: it.unit || it.uom || 'Kg',
                    quantity: it.quantity || it.qtyOrdered || 1,
                    quotedUnitPrice: it.unitPrice || 0
                }))
            };
        }
    }

    const content = document.getElementById('linkedRfqModalBody') || document.getElementById('linkedRfqDetailContent');
    const title = document.getElementById('linkedRfqModalTitle') || document.getElementById('linkedRfqModalRef');

    if (title) {
        title.innerHTML = `<i class="ph ph-link" style="color: #9333ea;"></i> <span>Originating Tender &amp; Quotation Details — ${escapeHtml(rfqNum)}</span>`;
    }

    const footerLink = modal.querySelector('.po-modal-footer a.po-btn-primary');
    if (footerLink) {
        footerLink.href = `/purchase/request-quotations?rfq=${encodeURIComponent(rfqNum)}`;
    }

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

/**
 * --------------------------------------------------------------------------
 * PO LIFECYCLE PROGRESS BAR, STAGE TRANSITIONS & LIVE DOCUMENT MOCKUP ENGINE
 * --------------------------------------------------------------------------
 */
function showPoLockOverlay(msg = 'Processing Purchase Order...') {
    const overlay = document.getElementById('poGlobalLockOverlay');
    const lockMsg = document.getElementById('poLockOverlayMessage');
    if (overlay) overlay.classList.add('is-active');
    if (lockMsg) lockMsg.textContent = msg;
}

function hidePoLockOverlay() {
    const overlay = document.getElementById('poGlobalLockOverlay');
    if (overlay) overlay.classList.remove('is-active');
}

function getPoAttachedFilesCount() {
    return (window.PoStore?.activePo?.attachedFiles || []).length;
}

function applyPoStageFormLocks(stage) {
    const isDraft = (stage === 'Draft');
    const lockIds = ['poOrderTitle', 'poDeliveryLocation', 'poPaymentTerms', 'poOrderDate', 'poExpectedDelivery'];
    lockIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (el.tagName === 'SELECT') el.disabled = !isDraft;
            else el.readOnly = !isDraft;
        }
    });
    const comboTrigger = document.getElementById('poVendorComboTrigger');
    if (comboTrigger) comboTrigger.disabled = !isDraft;
    const btnAddRow = document.getElementById('btnAddCustomPoRow');
    if (btnAddRow) btnAddRow.disabled = !isDraft;
    const btnClearRows = document.getElementById('btnClearAllPoRows');
    if (btnClearRows) btnClearRows.disabled = !isDraft;
}

function updatePoLifecycleProgressBar(status) {
    const effectiveStatus = status || window.PoStore?.activePo?.status || 'Draft PO';
    const stage = getNormalizedPoStage(effectiveStatus);
    applyPoStageFormLocks(stage);
    const wrap = document.getElementById('poLifecycleStepperWrap');
    if (!wrap) return;

    wrap.className = `po-lifecycle-bar-container rfq-lifecycle-bar-container stage-theme-${stage.toLowerCase()}`;

    const stepDraft = document.getElementById('poStepIndicatorDraft');
    const stepApproval = document.getElementById('poStepIndicatorApproval');
    const stepIssued = document.getElementById('poStepIndicatorIssued');
    const stepStatus = document.getElementById('poStepIndicatorStatus');

    const banner = document.getElementById('poStageBanner');
    const bannerIcon = document.getElementById('poStageBannerIcon');
    const bannerTitle = document.getElementById('poStageBannerTitle');
    const bannerSub = document.getElementById('poStageBannerSub');
    const bannerActions = document.getElementById('poStageBannerActions');
    const stageActionsGroup = document.getElementById('poStageActionsGroup');

    const statusTitle = document.getElementById('poStepStatusTitle');
    const statusDesc = document.getElementById('poStepStatusDesc');

    if (stepDraft) stepDraft.className = 'po-chevron-step rfq-chevron-step stage-draft';
    if (stepApproval) stepApproval.className = 'po-chevron-step rfq-chevron-step stage-approval';
    if (stepIssued) stepIssued.className = 'po-chevron-step rfq-chevron-step stage-submitted';
    if (stepStatus) stepStatus.className = 'po-chevron-step rfq-chevron-step stage-status';

    if (banner) {
        banner.className = `po-stage-helper-ribbon rfq-stage-helper-ribbon stage-${stage.toLowerCase()}`;
    }

    const attCount = getPoAttachedFilesCount();
    const poNum = window.PoStore?.activePo?.poNumber || '';

    if (stage === 'Draft') {
        if (stepDraft) stepDraft.classList.add('is-active', 'stage-draft');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'GRN & Settled';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-pencil-simple-line"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Stage 1: Drafting';
        if (bannerSub) bannerSub.textContent = 'Prepare Purchase Order specifications, or import an awarded RFQ package to continue procurement.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill draft"><i class="ph ph-circle-dashed"></i> Drafting Mode</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openPoAttachedFilesModal()" title="View & Attach Supporting Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Attached Files</span>
                    <span class="rfq-stage-badge-count" id="poAttachedCountBadge">${attCount}</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="saveActivePoDraft()" title="Save Purchase Order as Draft">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-submit" onclick="transitionActivePoStage('Approval')" title="Submit PO for Manager Sign-Off">
                    <i class="ph ph-arrow-right"></i>
                    <span>Submit for Approval</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-cancel" onclick="resetPoForm()" title="Reset / Clear Draft">
                    <i class="ph ph-x-circle"></i>
                    <span>Cancel</span>
                </button>
            `;
        }
    } else if (stage === 'Approval') {
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-active', 'stage-approval');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'In Review';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-scales"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Stage 2: Manager Approval';
        if (bannerSub) bannerSub.textContent = 'Verify vendor terms, quantities, and unit prices against RFQ benchmarks before issuing the PO.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill approval" style="color: #d97706;"><i class="ph ph-shield-check"></i> Pending Approval</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="issuePurchaseOrderSubmit()" title="Approve & Issue Official Purchase Order">
                    <i class="ph ph-check-circle"></i>
                    <span>Approve & Issue PO</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-danger" onclick="transitionActivePoStage('Refused')" title="Refuse / Cancel Purchase Order">
                    <i class="ph ph-x-circle"></i>
                    <span>Refuse</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="transitionActivePoStage('Draft')" title="Return to Draft for Revisions">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    <span>Back to Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openPoAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Issued') {
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-completed');
        if (stepIssued) stepIssued.classList.add('is-active', 'stage-submitted');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'Awaiting GRN';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-truck"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Stage 3: Issued & In-Transit';
        if (bannerSub) bannerSub.textContent = 'Official PO dispatched to vendor. Input Received Qty (GRN) directly in the table below or via Stock-In.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill submitted" style="color: #2563eb;"><i class="ph ph-truck"></i> Dispatched to Vendor</span>`;
        }
        const stockInUrl = `${window.PurchaseOrderConfig?.routes?.inventoryStockIn || '/inventory/stock-in'}?po=${encodeURIComponent(poNum)}`;
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="receiveAllPoItems()" title="Receive 100% of Ordered Quantities & Mark Fulfilled">
                    <i class="ph ph-checks"></i>
                    <span>Receive All (100%)</span>
                </button>
                <a href="${stockInUrl}" class="rfq-stage-action-btn rfq-btn-stage-outline" style="text-decoration: none;" title="Open Stock-In Receiving Dock to log Goods Receipt (GRN)">
                    <i class="ph ph-tray-arrow-down"></i>
                    <span>Receive in Stock-In (GRN)</span>
                </a>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-primary" onclick="openPaymentSettlementModal('${escapeHtml(poNum)}')" title="Record Disbursement & Financial Settlement">
                    <i class="ph ph-bank"></i>
                    <span>Payment Settlement</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="transitionActivePoStage('Approved')" title="Mark PO as 100% Fulfilled / Received">
                    <i class="ph ph-seal-check"></i>
                    <span>Mark Fulfilled</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openPoAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Approved') {
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-completed');
        if (stepIssued) stepIssued.classList.add('is-completed');
        if (stepStatus) stepStatus.classList.add('is-active', 'stage-approved');
        if (statusTitle) statusTitle.textContent = 'Fulfilled';
        if (statusDesc) statusDesc.textContent = '100% Received';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-seal-check"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Stage 4: Status (Fully Received)';
        if (bannerSub) bannerSub.textContent = 'All ordered items have been received into inventory. Manage final AP payment settlement below.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill approved" style="color: #059669;"><i class="ph ph-check-circle-fill"></i> Fully Received</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="openPaymentSettlementModal('${escapeHtml(poNum)}')" title="Manage Payment Settlement">
                    <i class="ph ph-bank"></i>
                    <span>Payment Settlement</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="openPoDocumentPreview()" title="Print / Export Completed PO">
                    <i class="ph ph-printer"></i>
                    <span>Print Voucher</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openPoAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Refused') {
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-completed');
        if (stepIssued) stepIssued.classList.add('is-completed');
        if (stepStatus) stepStatus.classList.add('is-active', 'stage-refused');
        if (statusTitle) statusTitle.textContent = 'Cancelled';
        if (statusDesc) statusDesc.textContent = 'PO Voided';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-prohibit"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Stage 4: Status (Cancelled / Refused)';
        if (bannerSub) bannerSub.textContent = 'This Purchase Order was cancelled or refused. Click Re-open as Draft to revise.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill refused" style="color: #dc2626;"><i class="ph ph-x-circle-fill"></i> Cancelled</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="transitionActivePoStage('Draft')" title="Re-open as Draft">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    <span>Back to Draft</span>
                </button>
            `;
        }
    }

    const statusBadge = document.getElementById('poStatusBadge');
    if (statusBadge) {
        statusBadge.className = `status-pill ${stage.toLowerCase()}`;
        statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${escapeHtml(window.PoStore?.activePo?.status || stage)}`;
    }
}

async function saveActivePoDraft() {
    syncPoFormInputsToState();
    const po = window.PoStore.activePo;
    po.status = 'Draft PO';

    try {
        const deletedPos = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        if (Array.isArray(deletedPos) && deletedPos.includes(po.poNumber)) {
            localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(deletedPos.filter(n => n !== po.poNumber)));
        }
    } catch (e) {}

    const existingIdx = (window.PoStore.purchaseOrders || []).findIndex(p => p.poNumber === po.poNumber);
    if (existingIdx >= 0) {
        window.PoStore.purchaseOrders[existingIdx] = JSON.parse(JSON.stringify(po));
    } else {
        window.PoStore.purchaseOrders.unshift(JSON.parse(JSON.stringify(po)));
    }
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const route = window.PurchaseOrderConfig?.routes?.saveDraft || '/purchase/api/orders/save-draft';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({
                po_number: po.poNumber,
                po_type: po.poType || 'vendor',
                rfq_reference: po.rfqReference || null,
                vendor_id: po.vendorId ? String(po.vendorId) : null,
                vendor_name: po.vendorName || po.vendorTradeName || 'Draft Vendor',
                vendor_trade_name: po.vendorTradeName || po.vendorName || 'Draft Vendor',
                vendor_contact_person: po.vendorContactPerson || '',
                vendor_phone: po.vendorPhone || '',
                vendor_email: po.vendorEmail || '',
                vendor_address: po.vendorAddress || '',
                order_date: po.orderDate || null,
                expected_delivery: po.expectedDelivery || null,
                delivery_location: po.deliveryLocation || 'Central Commissary - Receiving Dock 1',
                special_notes: po.orderTitle || po.specialNotes || '',
                items: po.items
            })
        });
    } catch (e) {
        console.warn('saveActivePoDraft network fallback:', e);
    }

    renderPoDirectory();
    updatePoLifecycleProgressBar('Draft PO');
    showToast(`✓ Draft ${po.poNumber} saved!`, 'success');
}

async function transitionActivePoStage(targetStage) {
    syncPoFormInputsToState();
    const po = window.PoStore.activePo;

    if ((targetStage === 'Approval' || targetStage === 'Issued') && (!po.items || po.items.length === 0)) {
        showToast('Please add at least 1 line item before advancing the Purchase Order stage.', 'warning');
        return;
    }

    const statusMap = {
        'Draft': 'Draft PO',
        'Approval': 'Pending Approval',
        'Issued': 'Approved / Issued',
        'Approved': 'Fully Received',
        'Refused': 'Cancelled'
    };
    const nextStatus = statusMap[targetStage] || targetStage;

    if (targetStage === 'Approved' && Array.isArray(po.items)) {
        po.items.forEach(it => {
            const ordered = parseFloat(it.quantity) || 0;
            const currentRec = parseFloat(it.receivedQuantity) || 0;
            if (currentRec < ordered) {
                it.receivedQuantity = ordered;
            }
        });
    }

    try {
        const deletedPos = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        if (Array.isArray(deletedPos) && deletedPos.includes(po.poNumber)) {
            localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(deletedPos.filter(n => n !== po.poNumber)));
        }
    } catch (e) {}

    showPoLockOverlay(`Transitioning ${po.poNumber} to ${nextStatus}...`);
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const route = window.PurchaseOrderConfig?.routes?.transitionStatus || '/purchase/api/orders/transition-status';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({
                po_number: po.poNumber,
                target_status: nextStatus,
                rfq_reference: po.rfqReference || null,
                vendor_id: po.vendorId ? String(po.vendorId) : null,
                vendor_name: po.vendorName || po.vendorTradeName || 'Approved Vendor',
                vendor_trade_name: po.vendorTradeName || po.vendorName || 'Approved Vendor',
                vendor_contact_person: po.vendorContactPerson || '',
                vendor_phone: po.vendorPhone || '',
                vendor_email: po.vendorEmail || '',
                vendor_address: po.vendorAddress || '',
                order_date: po.orderDate || null,
                expected_delivery: po.expectedDelivery || null,
                delivery_location: po.deliveryLocation || 'Central Commissary - Receiving Dock 1',
                special_notes: po.orderTitle || po.specialNotes || '',
                items: po.items
            })
        });
    } catch (e) {
        console.warn('transitionActivePoStage fallback:', e);
    } finally {
        hidePoLockOverlay();
    }

    po.status = nextStatus;
    const existingIdx = (window.PoStore.purchaseOrders || []).findIndex(p => p.poNumber === po.poNumber);
    if (existingIdx >= 0) {
        window.PoStore.purchaseOrders[existingIdx] = JSON.parse(JSON.stringify(po));
    } else {
        window.PoStore.purchaseOrders.unshift(JSON.parse(JSON.stringify(po)));
    }
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    applyPoStageDefaultColumns(nextStatus);
    updatePoLifecycleProgressBar(nextStatus);
    renderPoBuilderTableHeader();
    renderPoItemsTable();
    renderPoDirectory();
    updatePoLiveDocumentMockup();
    showToast(`✓ ${po.poNumber} moved to ${nextStatus}`, 'success');
}

function handlePoStepClick(targetStage) {
    const currentStage = getNormalizedPoStage(window.PoStore?.activePo?.status);
    if (targetStage === currentStage) return;
    if (targetStage === 'Issued') {
        issuePurchaseOrderSubmit();
        return;
    }
    transitionActivePoStage(targetStage);
}

function handlePoBuilderTabClick() {
    switchPoTab('tab-po-builder');
    updatePoLifecycleProgressBar(window.PoStore?.activePo?.status || 'Draft PO');
    updatePoLiveDocumentMockup();
}

function handlePoVendorRedirectClick(event) {
    const vId = window.PoStore?.activePo?.vendorId || document.getElementById('poVendorSelect')?.value;
    const baseUrl = window.PurchaseOrderConfig?.routes?.vendorMasterlist || '/purchase/vendor-masterlist';
    const btn = document.getElementById('poVendorRedirectBtn');
    if (btn) {
        btn.href = vId ? `${baseUrl}?vendor_id=${encodeURIComponent(vId)}` : baseUrl;
    }
    return true;
}

function syncPoFormInputsToState() {
    if (!window.PoStore || !window.PoStore.activePo) return;
    const po = window.PoStore.activePo;
    const getVal = (id) => document.getElementById(id)?.value;

    if (getVal('poOrderTitle') !== undefined) po.orderTitle = getVal('poOrderTitle');
    if (getVal('poDeliveryLocation') !== undefined) po.deliveryLocation = getVal('poDeliveryLocation');
    if (getVal('poPaymentTerms') !== undefined) po.paymentTerms = getVal('poPaymentTerms');
    if (getVal('poOrderDate') !== undefined) po.orderDate = getVal('poOrderDate');
    if (getVal('poExpectedDelivery') !== undefined) po.expectedDelivery = getVal('poExpectedDelivery');
}

function updatePoLiveDocumentMockup() {
    const sheet = document.getElementById('poLiveMockupSheet');
    if (!sheet || !window.PoStore || !window.PoStore.activePo) return;

    const po = window.PoStore.activePo;
    const items = po.items || [];
    const gross = items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
    const paid = parseFloat(po.amountPaid || 0);
    const balance = Math.max(0, gross - paid);

    const directLink = document.getElementById('poVendorDirectLink');
    if (directLink) {
        if (po.vendorName || po.vendorTradeName) {
            directLink.style.display = 'inline-flex';
            const linkText = document.getElementById('poVendorDirectLinkText');
            if (linkText) linkText.textContent = po.vendorTradeName || po.vendorName;
        } else {
            directLink.style.display = 'none';
        }
    }

    const pageBadge = document.getElementById('poMockupPageCountBadge');
    if (pageBadge) {
        const pages = Math.max(1, Math.ceil(items.length / 10));
        pageBadge.textContent = `${pages} Page${pages > 1 ? 's' : ''}`;
    }

    sheet.innerHTML = `
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-weight: 900; font-size: 13px; color: #0f172a; letter-spacing: -0.02em;">TOP C CULINARY GROUP</div>
                <div style="font-size: 10px; color: #64748b;">Central Commissary Procurement Division</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 900; font-size: 12px; color: #7e22ce; text-transform: uppercase;">PURCHASE ORDER</div>
                <div style="font-family: monospace; font-weight: 800; font-size: 11px; color: #0f172a;">${escapeHtml(po.poNumber || 'PO-2026-XXXX')}</div>
                ${po.rfqReference ? `<div style="font-family: monospace; font-size: 9.5px; color: #7e22ce; font-weight: 700;">Ref: ${escapeHtml(po.rfqReference)}</div>` : ''}
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; font-size: 10.5px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 9px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Vendor Partner</div>
                <div style="font-weight: 800; color: #0f172a;">${escapeHtml(po.vendorTradeName || po.vendorName || 'Select Target Vendor')}</div>
                <div style="color: #475569; font-size: 9.5px; margin-top: 2px;">${escapeHtml(po.vendorContactPerson || 'Primary Contact')} ${po.vendorPhone ? '• ' + escapeHtml(po.vendorPhone) : ''}</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 9px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Delivery & Terms</div>
                <div style="font-weight: 700; color: #0f172a;">${escapeHtml(po.deliveryLocation || 'Central Commissary')}</div>
                <div style="color: #475569; font-size: 9.5px; margin-top: 2px;">Due: <strong>${formatDateDisplay(po.expectedDelivery) || 'TBD'}</strong> • ${escapeHtml(po.paymentTerms || 'Net 30 Days')}</div>
            </div>
        </div>

        ${po.orderTitle ? `<div style="font-size: 10px; font-weight: 700; color: #334155; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 5px; padding: 5px 8px; margin-bottom: 10px;">Purpose: ${escapeHtml(po.orderTitle)}</div>` : ''}

        <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 1.5px solid #cbd5e1; text-align: left;">
                    <th style="padding: 4px 6px;">#</th>
                    <th style="padding: 4px 6px;">Item / SKU</th>
                    <th style="padding: 4px 6px; text-align: center;">UOM</th>
                    <th style="padding: 4px 6px; text-align: right;">Qty</th>
                    <th style="padding: 4px 6px; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                ${items.length === 0 ? `
                    <tr><td colspan="5" style="text-align: center; padding: 16px; color: #94a3b8; font-style: italic;">No items added yet</td></tr>
                ` : items.map((it, i) => {
                    const q = parseFloat(it.quantity) || 0;
                    const p = parseFloat(it.unitPrice) || 0;
                    return `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 4px 6px; color: #64748b;">${i + 1}</td>
                            <td style="padding: 4px 6px; font-weight: 700; color: #0f172a;">${escapeHtml(it.name || it.sku || 'Item')}</td>
                            <td style="padding: 4px 6px; text-align: center; font-family: monospace;">${escapeHtml(it.unit || 'PCS')}</td>
                            <td style="padding: 4px 6px; text-align: right; font-variant-numeric: tabular-nums;">${q.toFixed(2)}</td>
                            <td style="padding: 4px 6px; text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">₱${formatMoney(q * p)}</td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>

        <div style="border-top: 1.5px solid #0f172a; padding-top: 8px; display: flex; flex-direction: column; gap: 4px; font-size: 10.5px;">
            <div style="display: flex; justify-content: space-between; font-weight: 900; font-size: 11.5px; color: #0f172a;">
                <span>GROSS ORDER TOTAL:</span>
                <span style="color: #7e22ce; font-variant-numeric: tabular-nums;">₱${formatMoney(gross)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 10px; color: #059669;">
                <span>Amount Paid / Disbursed:</span>
                <span style="font-variant-numeric: tabular-nums;">₱${formatMoney(paid)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700; color: ${balance > 0 ? '#dc2626' : '#64748b'};">
                <span>Balance Due:</span>
                <span style="font-variant-numeric: tabular-nums;">₱${formatMoney(balance)}</span>
            </div>
        </div>
    `;
}

/**
 * --------------------------------------------------------------------------
 * MULTI-SELECTION BATCH ACTION BAR ENGINE (DELETE, ARCHIVE, EXPORT CSV)
 * --------------------------------------------------------------------------
 */
function toggleSelectAllPos(checked) {
    const pos = (window.PoStore.purchaseOrders || []).filter(p => p.status !== 'Archived');
    if (checked) {
        pos.forEach(p => selectedPoNumbers.add(p.poNumber));
    } else {
        selectedPoNumbers.clear();
    }
    renderPoDirectory();
}

function toggleSelectSinglePo(poNumber, checked) {
    if (checked) selectedPoNumbers.add(poNumber);
    else selectedPoNumbers.delete(poNumber);
    updatePoBatchToolbarUI();
}

function updatePoBatchToolbarUI() {
    const bar = document.getElementById('poBatchActionBar');
    const countEl = document.getElementById('poBatchSelectedCount');
    if (!bar || !countEl) return;

    const count = selectedPoNumbers.size;
    countEl.textContent = count;
    bar.style.display = count > 0 ? 'inline-flex' : 'none';
}

function clearPoBatchSelection() {
    selectedPoNumbers.clear();
    const selAll = document.getElementById('selectAllPoCheckbox');
    if (selAll) selAll.checked = false;
    renderPoDirectory();
}

async function executePoBatchDelete() {
    if (selectedPoNumbers.size === 0) return;
    const targets = Array.from(selectedPoNumbers);
    if (!confirm(`Are you sure you want to delete ${targets.length} selected Purchase Order(s)?`)) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const route = window.PurchaseOrderConfig?.routes?.batchDelete || '/purchase/api/orders/batch-delete';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({ po_numbers: targets, force: true })
        });
    } catch (e) {
        console.warn('executePoBatchDelete fallback:', e);
    }

    try {
        const deletedList = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        const merged = Array.from(new Set([...(Array.isArray(deletedList) ? deletedList : []), ...targets]));
        localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(merged));
    } catch (e) {}

    try {
        const targetSet = new Set(targets);
        const rfqList = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
        if (Array.isArray(rfqList)) {
            let changed = false;
            rfqList.forEach(r => {
                if (r && r.poReference && targetSet.has(r.poReference)) {
                    r.poReference = '';
                    changed = true;
                }
            });
            if (changed) localStorage.setItem('rms_rfq_directory', JSON.stringify(rfqList));
        }
    } catch (e) {}

    window.PoStore.purchaseOrders = (window.PoStore.purchaseOrders || []).filter(p => !selectedPoNumbers.has(p.poNumber));
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    clearPoBatchSelection();
    showToast(`✓ Deleted ${targets.length} Purchase Order(s).`, 'success');
}

async function executePoBatchArchive() {
    if (selectedPoNumbers.size === 0) return;
    const targets = Array.from(selectedPoNumbers);
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const route = window.PurchaseOrderConfig?.routes?.batchArchive || '/purchase/api/orders/batch-archive';
        await fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({ po_numbers: targets })
        });
    } catch (e) {
        console.warn('executePoBatchArchive fallback:', e);
    }

    (window.PoStore.purchaseOrders || []).forEach(p => {
        if (selectedPoNumbers.has(p.poNumber)) p.status = 'Archived';
    });
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    clearPoBatchSelection();
    showToast(`✓ Archived ${targets.length} Purchase Order(s).`, 'success');
}

function executePoBatchExport() {
    const targets = selectedPoNumbers.size > 0
        ? (window.PoStore.purchaseOrders || []).filter(p => selectedPoNumbers.has(p.poNumber))
        : (window.PoStore.purchaseOrders || []);
    if (targets.length === 0) {
        showToast('No Purchase Orders to export.', 'warning');
        return;
    }
    const headers = ['PO Number', 'Source RFQ', 'Vendor', 'Order Date', 'Expected Delivery', 'Status', 'Payment Status', 'Gross Total (PHP)'];
    const rows = targets.map(p => {
        const gross = (p.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        return [
            `"${p.poNumber || ''}"`,
            `"${p.rfqReference || 'Direct'}"`,
            `"${(p.vendorTradeName || p.vendorName || '').replace(/"/g, '""')}"`,
            `"${p.orderDate || ''}"`,
            `"${p.expectedDelivery || ''}"`,
            `"${p.status || ''}"`,
            `"${p.paymentStatus || ''}"`,
            gross.toFixed(2)
        ].join(',');
    });
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Purchase_Orders_Export_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    showToast(`✓ Exported ${targets.length} Purchase Order(s) to CSV.`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * ATTACHED FILES MODAL & DEEP-LINKING HANDLER (?po=PO-XXXX / ?rfq=RFQ-XXXX)
 * --------------------------------------------------------------------------
 */
function openPoAttachedFilesModal() {
    const modal = document.getElementById('poAttachedFilesModal');
    if (!modal) return;
    renderPoAttachedFilesList();
    modal.classList.add('is-open');
}

function closePoAttachedFilesModal() {
    const modal = document.getElementById('poAttachedFilesModal');
    if (modal) modal.classList.remove('is-open');
}

function handlePoFileUpload(fileList) {
    if (!fileList || fileList.length === 0) return;
    if (!window.PoStore.activePo.attachedFiles) window.PoStore.activePo.attachedFiles = [];
    Array.from(fileList).forEach(f => {
        window.PoStore.activePo.attachedFiles.push({
            name: f.name,
            size: `${(f.size / 1024).toFixed(1)} KB`,
            uploadedAt: new Date().toLocaleTimeString()
        });
    });
    renderPoAttachedFilesList();
    updatePoLifecycleProgressBar(window.PoStore.activePo.status);
    showToast(`✓ Attached ${fileList.length} document(s) to ${window.PoStore.activePo.poNumber}`, 'success');
}

function removePoAttachedFile(idx) {
    if (!window.PoStore.activePo.attachedFiles) return;
    window.PoStore.activePo.attachedFiles.splice(idx, 1);
    renderPoAttachedFilesList();
    updatePoLifecycleProgressBar(window.PoStore.activePo.status);
}

function renderPoAttachedFilesList() {
    const listEl = document.getElementById('poAttachedFilesList');
    if (!listEl) return;
    const files = window.PoStore?.activePo?.attachedFiles || [];
    if (files.length === 0) {
        listEl.innerHTML = `<div style="text-align: center; padding: 18px; color: #94a3b8; font-size: 12px;">No supporting documents attached yet.</div>`;
        return;
    }
    listEl.innerHTML = files.map((f, i) => `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-file-pdf" style="color: #9333ea; font-size: 16px;"></i>
                <div>
                    <div style="font-weight: 700; color: #0f172a;">${escapeHtml(f.name)}</div>
                    <div style="font-size: 10.5px; color: #64748b;">${escapeHtml(f.size)} • ${escapeHtml(f.uploadedAt)}</div>
                </div>
            </div>
            <button type="button" class="inv-table-filter-btn" style="color: #dc2626;" onclick="removePoAttachedFile(${i})"><i class="ph ph-trash"></i></button>
        </div>
    `).join('');
}

async function handlePoDeepLinkParams() {
    try {
        const params = new URLSearchParams(window.location.search);
        const targetPo = params.get('po');
        const targetRfq = params.get('rfq');
        if (targetPo) {
            let exists = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === targetPo);
            if (!exists) {
                try {
                    const res = await fetch(`/purchase/api/orders/${encodeURIComponent(targetPo)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    if (json?.success && json?.data?.po) {
                        const p = json.data.po;
                        exists = {
                            id: p.id,
                            poNumber: p.po_number,
                            poType: p.po_type || 'vendor',
                            rfqReference: p.rfq_reference || '',
                            orderTitle: p.special_notes || '',
                            vendorId: p.vendor_id || '',
                            vendorName: p.vendor_name,
                            vendorTradeName: p.vendor_trade_name || p.vendor_name,
                            vendorContactPerson: p.vendor_contact_person || '',
                            vendorPhone: p.vendor_phone || '',
                            vendorEmail: p.vendor_email || '',
                            vendorAddress: p.vendor_address || '',
                            orderDate: p.order_date ? String(p.order_date).split('T')[0] : '',
                            expectedDelivery: p.expected_delivery ? String(p.expected_delivery).split('T')[0] : '',
                            deliveryLocation: p.delivery_location || 'Central Commissary - Receiving Dock 1',
                            paymentTerms: p.payment_terms || 'Net 30 Days',
                            specialNotes: p.special_notes || '',
                            paymentStatus: p.payment_status || 'Unpaid / Credit',
                            paymentMethod: p.payment_method || 'Trade Credit (Net 30/15)',
                            amountPaid: parseFloat(p.amount_paid || 0),
                            balanceDue: parseFloat(p.balance_due || 0),
                            approvedBy: p.approved_by || 'Procurement Specialist',
                            status: p.status || 'Approved / Issued',
                            items: (json.data.items || p.items || []).map(it => ({
                                id: it.id,
                                sku: it.sku || 'CAT-01',
                                name: it.item_name || it.name || 'Ordered Item',
                                specs: it.specs || '',
                                category: it.category || 'Raw Ingredients',
                                unit: it.uom || it.unit || 'PCS',
                                quantity: parseFloat(it.quantity || 1),
                                unitPrice: parseFloat(it.unit_price || 0),
                                receivedQuantity: parseFloat(it.received_quantity || 0)
                            }))
                        };
                        window.PoStore.purchaseOrders.unshift(exists);
                        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
                        renderPoDirectory();
                    }
                } catch (e) {}
            }
            if (exists) {
                editPoFromDirectory(targetPo);
            }
        } else if (targetRfq) {
            importApprovedRfqToBuilder(targetRfq);
        }
    } catch (e) {
        console.warn('handlePoDeepLinkParams error:', e);
    }
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
  renderPoBuilderTableHeader,
  togglePoBuilderSettingsDropdown,
  togglePoBuilderColumn,
  showAllPoBuilderColumns,
  resetPoBuilderColumnsToStage,
  renderPoItemsTable,
  updatePoItemField,
  updatePoReceivedQty,
  receiveAllPoItems,
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
  togglePoTableSettingsDropdown,
  handlePoDirectoryColSearch,
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
  deletePaymentDisbursement,
  updatePoLifecycleProgressBar,
  applyPoStageFormLocks,
  saveActivePoDraft,
  transitionActivePoStage,
  handlePoStepClick,
  handlePoBuilderTabClick,
  handlePoVendorRedirectClick,
  syncPoFormInputsToState,
  updatePoLiveDocumentMockup,
  toggleSelectAllPos,
  toggleSelectSinglePo,
  updatePoBatchToolbarUI,
  clearPoBatchSelection,
  executePoBatchDelete,
  executePoBatchArchive,
  executePoBatchExport,
  openPoAttachedFilesModal,
  closePoAttachedFilesModal,
  handlePoFileUpload,
  removePoAttachedFile,
  handlePoDeepLinkParams
});
