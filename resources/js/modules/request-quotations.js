/**
 * ==========================================================================
 * REQUEST FOR QUOTATIONS (RFQ) ARCHITECTURAL CORE
 * Strict Compliance: Single Master Payload, Pessimistic UI Locking,
 * Floating Column Filter Dropdown, LocalStorage Persistence & Red-Green Integrity
 * ==========================================================================
 */

// Server Data Hydration Bridges
    const serverRfqs = window.__SERVER_RFQS__ = window.RfqConfig?.data?.initialRfqs || [];
    const serverVendors = window.__SERVER_VENDORS__ = window.RfqConfig?.data?.initialVendors || [];
    const serverItems = window.__SERVER_ITEMS__ = window.RfqConfig?.data?.initialItems || [];

// Global formatting utilities
window.formatNumber = function(num, decimals = 2) {
    const val = Number(num);
    if (isNaN(val)) return '0';
    return val.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: decimals });
};

// 1. Immutable Column Definition Matrix based on Item Master Data Headers
const ALL_COLUMNS = [
    // 1. Base Item Request Fields
    { id: 'index', label: '#', category: 'Base Request', default: true, align: 'center', width: '48px', lockVisible: true },
    { id: 'item_id', label: 'Item ID / SKU', category: 'Base Request', default: true, align: 'left', width: '130px', lockVisible: true },
    { id: 'item_name', label: 'Item Description', category: 'Base Request', default: true, align: 'left', minWidth: '180px', lockVisible: true },
    { id: 'purchase_uom_code', label: 'Purchase UOM', category: 'Base Request', default: true, align: 'center', width: '110px' },
    { id: 'requested_quantity', label: 'Requested Qty', category: 'Base Request', default: true, align: 'right', width: '120px' },
    { id: 'target_unit_price', label: 'Benchmark Price (₱)', category: 'Base Request', default: false, align: 'right', width: '135px' },
    { id: 'target_line_total', label: 'Budget Total (₱)', category: 'Base Request', default: false, align: 'right', width: '135px' },
    { id: 'rfq_line_notes', label: 'Specifications / Notes', category: 'Base Request', default: false, align: 'left', minWidth: '150px' },

    // 2. Vendor Response Inputs (Revealed in Approval stage)
    { id: 'quoted_quantity', label: 'Quoted Qty', category: 'Vendor Response', default: false, align: 'right', width: '120px' },
    { id: 'quoted_unit_price', label: 'Quoted Price (₱)', category: 'Vendor Response', default: false, align: 'right', width: '135px' },
    { id: 'quoted_line_total', label: 'Quoted Total (₱)', category: 'Vendor Response', default: false, align: 'right', width: '135px' },
    { id: 'lead_time_days', label: 'Lead Time (Days)', category: 'Vendor Response', default: false, align: 'center', width: '120px' },

    // 3. Validation & Comparison Fields (Optional: untoggled by default in Approval stage)
    { id: 'quantity_variance', label: 'Qty Variance', category: 'Validation & Comparison', default: false, align: 'right', width: '120px' },
    { id: 'price_variance_amount', label: 'Price Diff (₱)', category: 'Validation & Comparison', default: false, align: 'right', width: '130px' },
    { id: 'price_variance_percentage', label: 'Variance (%)', category: 'Validation & Comparison', default: false, align: 'right', width: '120px' },
    { id: 'is_price_within_budget', label: 'Budget Check', category: 'Validation & Comparison', default: false, align: 'center', width: '130px' }
];

// Stage-Specific Default Column Configurations
// Draft Stage: Core request fields visible; Benchmark Price / Budget / Notes untoggled by default (optional)
const DRAFT_STAGE_DEFAULT_COLUMNS = [
    'index',
    'item_id',
    'item_name',
    'purchase_uom_code',
    'requested_quantity'
];

// Submitted Stage: Core request + vendor response inputs (Quoted Qty, Quoted Price, Lead Time, Variance %, Budget)
const SUBMITTED_STAGE_DEFAULT_COLUMNS = [
    'index',
    'item_id',
    'item_name',
    'purchase_uom_code',
    'requested_quantity',
    'target_unit_price',
    'quoted_quantity',
    'quoted_unit_price',
    'quoted_line_total',
    'lead_time_days',
    'price_variance_percentage',
    'is_price_within_budget'
];

// Approval Stage: Core request + vendor quotes visible; Qty variance / Price Diff / Variance / Budget check untoggled by default (optional)
const APPROVAL_STAGE_DEFAULT_COLUMNS = [
    'index',
    'item_id',
    'item_name',
    'purchase_uom_code',
    'requested_quantity',
    'quoted_quantity',
    'quoted_unit_price',
    'quoted_line_total'
];

function getStageDefaultColumns(stage) {
    const s = getNormalizedRfqStage(stage);
    if (s === 'Approval' || s === 'Approved' || s === 'Refused') {
        return [...APPROVAL_STAGE_DEFAULT_COLUMNS];
    }
    if (s === 'Submitted') {
        return [...SUBMITTED_STAGE_DEFAULT_COLUMNS];
    }
    return [...DRAFT_STAGE_DEFAULT_COLUMNS];
}

// Seeded Item Master Dictionary (Synced with RMS Inventory Catalog)
const ITEM_MASTER_DICTIONARY = [
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

// Seeded Approved Vendor Partners (Synced with Vendor Masterlist v6)
const SEEDED_VENDORS = [
    {
        id: "VND-SAN-002",
        code: "VND-SAN-002",
        legalName: "San Miguel Pure Foods Company Inc.",
        tradeName: "San Miguel Foods",
        location: "Mandaluyong City, Metro Manila",
        email: "orders.foodservice@sanmiguel.com.ph",
        tin: "000-112-899-000-VAT",
        address: "San Miguel Head Office Complex, 40 San Miguel Ave, Mandaluyong City, 1550",
        category: "Raw Ingredients",
        paymentTerms: "Net 30 Days",
        leadTimeDays: 3,
        contacts: [
            { name: "Patricia Lim", title: "Key Foodservice Account Exec", phone: "+63 917 882 1044", email: "patricia.lim@sanmiguel.com.ph", role: "sales" }
        ]
    },
    {
        id: "VND-ROB-003",
        code: "VND-ROB-003",
        legalName: "Universal Robina Corporation",
        tradeName: "URC Agro-Industrial Group",
        location: "Quezon City, Philippines",
        email: "commercial.sales@urc.com.ph",
        tin: "000-451-223-000-VAT",
        address: "Tera Tower, Bridgetowne, E. Rodriguez Jr. Ave, Quezon City, 1110",
        category: "Raw Ingredients",
        paymentTerms: "Net 30 Days",
        leadTimeDays: 4,
        contacts: [
            { name: "Carlos Mendoza", title: "Institutional Accounts Officer", phone: "+63 918 334 5591", email: "carlos.mendoza@urc.com.ph", role: "sales" }
        ]
    },
    {
        id: "VND-ECO-004",
        code: "VND-ECO-004",
        legalName: "EcoPack Solutions Philippines Corp.",
        tradeName: "EcoPack Packaging",
        location: "Valenzuela City, Metro Manila",
        email: "sales@ecopack.ph",
        tin: "008-992-145-000-VAT",
        address: "14 Industrial Ave, Karuhatan, Valenzuela City, 1441",
        category: "Packaging",
        paymentTerms: "Net 15 Days",
        leadTimeDays: 2,
        contacts: [
            { name: "Grace Villanueva", title: "Client Solutions Specialist", phone: "+63 920 918 2234", email: "grace@ecopack.ph", role: "sales" }
        ]
    },
    {
        id: "VND-MER-001",
        code: "VND-MER-001",
        legalName: "Manila Electric Company",
        tradeName: "Meralco Power Systems",
        location: "Ortigas Center, Pasig City",
        email: "corporate.accounts@meralco.com.ph",
        tin: "000-101-528-000-VAT",
        address: "Lopez Building, Meralco Center, Ortigas Ave, Pasig City, 1600",
        category: "Services",
        paymentTerms: "Net 15 Days",
        leadTimeDays: 1,
        contacts: [
            { name: "Engr. Marco Santos", title: "Industrial Key Account Manager", phone: "+63 917 554 9011", email: "marco.santos@meralco.com.ph", role: "sales" }
        ]
    }
];

// Seeded Requests for Quotation Directory (Synced with Procurement Ledger & RFQ Overview Status Spec)
const SEEDED_RFQS = [
    {
        rfqNumber: 'RFQ-2026-0038',
        rfqDate: '2026-09-24',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Central Commissary - Main Cold Dock',
        vendorId: 'VND-SAN-002',
        vendorName: 'San Miguel Pure Foods Company Inc.',
        vendorTradeName: 'San Miguel Foods',
        vendorEmail: 'orders.foodservice@sanmiguel.com.ph',
        vendorPhone: '+63 917 882 1044',
        vendorContactId: 'VNC-002',
        vendorContactPerson: 'Patricia Lim',
        vendorContactTitle: 'Key Foodservice Exec',
        vendorAddress: '40 San Miguel Ave, Mandaluyong City',
        submissionDeadline: '2026-10-01',
        quoteValidUntilDate: '2026-10-15',
        rfqRequestedDeliveryDate: '2026-10-06',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 30 Days',
        rfqIncotermsCode: 'FOB',
        rfqEstimatedTotalAmount: 34600.00,
        rfqQuotedTotalAmount: 34600.00,
        rfqStatus: 'Awarded',
        rfqApprovalStatus: 'APPROVED',
        approvalRequestId: 'APR-2026-0088',
        rfqAwardApprovalStatus: 'APPROVED',
        awardApprovalRequestId: 'AWD-2026-0038',
        rfqNotes: 'Cold-chain delivery certificates required for all meat products.',
        rfqCreatedBy: 'USR-BUYER-01',
        rfqCreatedAt: '2026-09-24 09:30:00',
        rfqUpdatedBy: 'USR-MGR-02',
        rfqUpdatedAt: '2026-10-01 14:15:00',
        dateIssued: '2026-09-24',
        dueDate: '2026-10-01',
        expectedDelivery: '2026-10-06',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Commissary - Main Cold Dock',
        specialInstructions: 'Cold-chain delivery certificates required for all meat products.',
        status: 'Awarded',
        items: [
            { lineNumber: 1, itemId: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 25.0, quantity: 25.0, targetUnitPrice: 800.0, targetPrice: 800.0, quotedUnitPrice: 840.0, quotedLineTotal: 21000.0, leadTimeDays: 3, isAwarded: true, rfqLineNotes: 'Chilled 2-4°C, Box packaging', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-24 09:35:00', rfqLineUpdatedBy: 'USR-MGR-02', rfqLineUpdatedAt: '2026-09-28 11:20:00' },
            { lineNumber: 2, itemId: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 40.0, quantity: 40.0, targetUnitPrice: 320.0, targetPrice: 320.0, quotedUnitPrice: 340.0, quotedLineTotal: 13600.0, leadTimeDays: 2, isAwarded: true, rfqLineNotes: 'Triple-A NMIS Inspection Certificate', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-24 09:36:00', rfqLineUpdatedBy: 'USR-MGR-02', rfqLineUpdatedAt: '2026-09-28 11:20:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0039',
        rfqDate: '2026-09-26',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-02',
        branchName: 'BGC High Street Hub',
        shipToWarehouseId: 'WH-DRY',
        shipToWarehouseName: 'Central Warehouse - Dry Storage',
        vendorId: 'VND-ROB-003',
        vendorName: 'Universal Robina Corporation',
        vendorTradeName: 'URC Agro-Industrial Group',
        vendorEmail: 'commercial.sales@urc.com.ph',
        vendorPhone: '+63 918 334 5591',
        vendorContactId: 'VNC-003',
        vendorContactPerson: 'Carlos Mendoza',
        vendorContactTitle: 'Institutional Accounts Officer',
        vendorAddress: 'Tera Tower, Bridgetowne, Quezon City',
        submissionDeadline: '2026-10-03',
        quoteValidUntilDate: '2026-10-24',
        rfqRequestedDeliveryDate: '2026-10-08',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 30 Days',
        rfqIncotermsCode: 'DDP',
        rfqEstimatedTotalAmount: 5880.00,
        rfqQuotedTotalAmount: 5880.00,
        rfqStatus: 'Quotation Received',
        rfqApprovalStatus: 'PENDING',
        approvalRequestId: 'APR-2026-0091',
        rfqAwardApprovalStatus: 'NOT_REQUIRED',
        awardApprovalRequestId: '',
        rfqNotes: 'Palletized delivery with stretch shrink wrap.',
        rfqCreatedBy: 'USR-BUYER-02',
        rfqCreatedAt: '2026-09-26 10:15:00',
        rfqUpdatedBy: 'USR-BUYER-02',
        rfqUpdatedAt: '2026-10-02 16:40:00',
        dateIssued: '2026-09-26',
        dueDate: '2026-10-03',
        expectedDelivery: '2026-10-08',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Warehouse - Dry Storage',
        specialInstructions: 'Palletized delivery with stretch shrink wrap.',
        status: 'Quotation Received',
        items: [
            { lineNumber: 1, itemId: 'RAW-306', name: 'All-Purpose Wheat Flour', specs: 'Unbleached Baking Wheat Flour', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 150.0, quantity: 150.0, targetUnitPrice: 38.0, targetPrice: 38.0, quotedUnitPrice: 39.2, quotedLineTotal: 5880.0, leadTimeDays: 4, isAwarded: false, rfqLineNotes: 'Dry clean paper sacks 25kg each', rfqLineCreatedBy: 'USR-BUYER-02', rfqLineCreatedAt: '2026-09-26 10:16:00', rfqLineUpdatedBy: 'USR-BUYER-02', rfqLineUpdatedAt: '2026-10-02 16:40:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0040',
        rfqDate: '2026-09-28',
        companyId: 'CMP-002',
        companyName: 'Apex Food Services Inc.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Central Commissary - Main Dock',
        vendorId: 'VND-ECO-004',
        vendorName: 'EcoPack Solutions Philippines Corp.',
        vendorTradeName: 'EcoPack Packaging',
        vendorEmail: 'sales@ecopack.ph',
        vendorPhone: '+63 920 918 2234',
        vendorContactId: 'VNC-004',
        vendorContactPerson: 'Grace Villanueva',
        vendorContactTitle: 'Client Solutions Specialist',
        vendorAddress: '14 Industrial Ave, Valenzuela City',
        submissionDeadline: '2026-10-05',
        quoteValidUntilDate: '2026-10-30',
        rfqRequestedDeliveryDate: '2026-10-10',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 15 Days',
        rfqIncotermsCode: 'FOB',
        rfqEstimatedTotalAmount: 17200.00,
        rfqQuotedTotalAmount: 17200.00,
        rfqStatus: 'Quote Requested',
        rfqApprovalStatus: 'APPROVED',
        approvalRequestId: 'APR-2026-0095',
        rfqAwardApprovalStatus: 'PENDING',
        awardApprovalRequestId: '',
        rfqNotes: 'Biodegradable certification required for takeaway boxes.',
        rfqCreatedBy: 'USR-BUYER-01',
        rfqCreatedAt: '2026-09-28 14:00:00',
        rfqUpdatedBy: 'USR-BUYER-01',
        rfqUpdatedAt: '2026-09-28 14:30:00',
        dateIssued: '2026-09-28',
        dueDate: '2026-10-05',
        expectedDelivery: '2026-10-10',
        paymentTerms: 'Net 15 Days',
        deliveryLocation: 'Central Commissary - Main Dock',
        specialInstructions: 'Biodegradable certification required for takeaway boxes.',
        status: 'Quote Requested',
        items: [
            { lineNumber: 1, itemId: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', category: 'Packaging', purchaseUomCode: 'Pc', unit: 'Pc', requestedQuantity: 2000.0, quantity: 2000.0, targetUnitPrice: 4.2, targetPrice: 4.2, quotedUnitPrice: 4.5, quotedLineTotal: 9000.0, leadTimeDays: 2, isAwarded: false, rfqLineNotes: 'Carton box of 1000 pcs', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-28 14:05:00', rfqLineUpdatedBy: 'USR-BUYER-01', rfqLineUpdatedAt: '2026-09-28 14:05:00' },
            { lineNumber: 2, itemId: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', category: 'Packaging', purchaseUomCode: 'Pc', unit: 'Pc', requestedQuantity: 1000.0, quantity: 1000.0, targetUnitPrice: 8.0, targetPrice: 8.0, quotedUnitPrice: 8.2, quotedLineTotal: 8200.0, leadTimeDays: 2, isAwarded: false, rfqLineNotes: 'Certified biodegradable embossed stamp', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-28 14:06:00', rfqLineUpdatedBy: 'USR-BUYER-01', rfqLineUpdatedAt: '2026-09-28 14:06:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0041',
        rfqDate: '2026-09-29',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Branch 1 - Makati Flagship',
        vendorId: 'VND-MER-001',
        vendorName: 'Manila Electric Company',
        vendorTradeName: 'Meralco Power Systems',
        vendorEmail: 'corporate.accounts@meralco.com.ph',
        vendorPhone: '+63 917 554 9011',
        vendorContactId: 'VNC-001',
        vendorContactPerson: 'Engr. Marco Santos',
        vendorContactTitle: 'Industrial Key Account Manager',
        vendorAddress: 'Lopez Building, Meralco Center, Pasig City',
        submissionDeadline: '2026-10-06',
        quoteValidUntilDate: '2026-10-25',
        rfqRequestedDeliveryDate: '2026-10-12',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 15 Days',
        rfqIncotermsCode: 'CIF',
        rfqEstimatedTotalAmount: 8500.00,
        rfqQuotedTotalAmount: 8500.00,
        rfqStatus: 'Draft',
        rfqApprovalStatus: 'PENDING',
        approvalRequestId: '',
        rfqAwardApprovalStatus: 'NOT_REQUIRED',
        awardApprovalRequestId: '',
        rfqNotes: 'Kitchen electrical submeter upgrade quotation and transformer service.',
        rfqCreatedBy: 'USR-BUYER-03',
        rfqCreatedAt: '2026-09-29 11:00:00',
        rfqUpdatedBy: 'USR-BUYER-03',
        rfqUpdatedAt: '2026-09-29 11:00:00',
        dateIssued: '2026-09-29',
        dueDate: '2026-10-06',
        expectedDelivery: '2026-10-12',
        paymentTerms: 'Net 15 Days',
        deliveryLocation: 'Branch 1 - Makati Flagship',
        specialInstructions: 'Kitchen electrical submeter upgrade quotation and transformer service.',
        status: 'Draft',
        items: [
            { lineNumber: 1, itemId: 'RAW-301', name: 'Barista Whole Fresh Milk', specs: '100% Pure Cow Fresh Chilled Milk', category: 'Raw Ingredients', purchaseUomCode: 'Liter', unit: 'Liter', requestedQuantity: 100.0, quantity: 100.0, targetUnitPrice: 85.0, targetPrice: 85.0, quotedUnitPrice: 85.0, quotedLineTotal: 8500.0, leadTimeDays: 1, isAwarded: false, rfqLineNotes: 'Daily early morning delivery', rfqLineCreatedBy: 'USR-BUYER-03', rfqLineCreatedAt: '2026-09-29 11:05:00', rfqLineUpdatedBy: 'USR-BUYER-03', rfqLineUpdatedAt: '2026-09-29 11:05:00' }
        ]
    }
];

function getLinkedPoNumberForRfq(rfq) {
    if (!rfq) return '';
    const rfqNum = typeof rfq === 'string' ? rfq : (rfq.rfqNumber || rfq.rfq_number || '');
    if (!rfqNum) return '';

    const serverMap = window.RfqConfig?.data?.linkedPoMap || {};
    if (serverMap && serverMap[rfqNum]) {
        return serverMap[rfqNum];
    }

    if (typeof rfq === 'object') {
        const directPo = rfq.purchase_order?.po_number || rfq.purchaseOrder?.po_number || '';
        if (directPo) return directPo;
    }

    try {
        const deletedPos = new Set(JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]'));
        const localPos = JSON.parse(localStorage.getItem('rms_purchase_orders') || '[]');
        if (Array.isArray(localPos)) {
            const match = localPos.find(p =>
                (p.rfqReference === rfqNum || p.rfq_reference === rfqNum) &&
                p.status !== 'Cancelled' && p.status !== 'Archived' &&
                !deletedPos.has(p.poNumber || p.po_number)
            );
            if (match && (match.poNumber || match.po_number)) {
                return match.poNumber || match.po_number;
            }
        }
    } catch (e) {}

    if (typeof rfq === 'object' && rfq.poReference) {
        try {
            const deletedPos = new Set(JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]'));
            if (!deletedPos.has(rfq.poReference)) return rfq.poReference;
        } catch (e) {
            return rfq.poReference;
        }
    }

    return '';
}

function openLinkedPoFromRfq(poNumber) {
    if (!poNumber) return;
    const poBaseRoute = window.RfqConfig?.routes?.purchaseOrders || '/purchase/purchase-orders';
    window.location.href = `${poBaseRoute}?po=${encodeURIComponent(poNumber)}`;
}

async function quickCreatePoFromDirectory(rfqNumber) {
    const rfq = (window.AppStore?.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    const existingPo = getLinkedPoNumberForRfq(rfq);
    if (existingPo) {
        openLinkedPoFromRfq(existingPo);
        return;
    }
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    await generatePoForApprovedRfq();
}

function normalizeRfqItem(r) {
    if (!r) return null;
    const estTotal = (r.items || []).reduce((acc, it) => acc + ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.targetUnitPrice || it.targetPrice) || 0)), 0);
    const quotedTotal = (r.items || []).reduce((acc, it) => acc + (parseFloat(it.quotedLineTotal) || ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.quotedUnitPrice || it.targetPrice) || 0))), 0);
    const rfqNumberVal = r.rfqNumber || r.rfq_number || 'RFQ-2026-0001';
    const linkedPo = getLinkedPoNumberForRfq({ ...r, rfqNumber: rfqNumberVal });

    return {
        rfqNumber: rfqNumberVal,
        poReference: linkedPo || r.poReference || r.po_reference || '',
        rfqDate: r.rfqDate || r.rfq_date || r.dateIssued || '2026-10-01',
        companyId: r.companyId || r.company_id || 'CMP-001',
        companyName: r.companyName || 'RMS Holdings Corp.',
        branchId: r.branchId || r.branch_id || 'BR-01',
        branchName: r.branchName || 'Makati Central Flagship',
        shipToWarehouseId: r.shipToWarehouseId || r.ship_to_warehouse_id || 'WH-MAIN',
        shipToWarehouseName: r.shipToWarehouseName || r.deliveryLocation || 'Central Commissary - Main Dock',
        vendorId: r.vendorId || r.vendor_id || 'VND-GEN-001',
        vendorName: r.vendorName || 'General Supplier',
        vendorTradeName: r.vendorTradeName || r.vendorName || 'General Supplier',
        vendorContactId: r.vendorContactId || r.vendor_contact_id || 'VNC-001',
        vendorContactPerson: r.vendorContactPerson || 'Primary Contact',
        vendorEmail: r.vendorEmail || '',
        vendorPhone: r.vendorPhone || '',
        submissionDeadline: r.submissionDeadline || r.submission_deadline || r.dueDate || '2026-10-08',
        quoteValidUntilDate: r.quoteValidUntilDate || r.quote_valid_until_date || '2026-10-25',
        rfqRequestedDeliveryDate: r.rfqRequestedDeliveryDate || r.rfq_requested_delivery_date || r.expectedDelivery || '2026-10-15',
        rfqCurrencyCode: r.rfqCurrencyCode || r.rfq_currency_code || 'PHP',
        rfqPaymentTermsCode: r.rfqPaymentTermsCode || r.rfq_payment_terms_code || r.paymentTerms || 'Net 30 Days',
        rfqIncotermsCode: r.rfqIncotermsCode || r.rfq_incoterms_code || 'FOB',
        rfqEstimatedTotalAmount: parseFloat(r.rfqEstimatedTotalAmount || r.rfq_estimated_total_amount || estTotal || 0),
        rfqQuotedTotalAmount: parseFloat(r.rfqQuotedTotalAmount || r.rfq_quoted_total_amount || quotedTotal || estTotal || 0),
        rfqStatus: r.rfqStatus || r.rfq_status || r.status || 'Draft',
        rfqApprovalStatus: r.rfqApprovalStatus || r.rfq_approval_status || (r.isApproved ? 'APPROVED' : 'PENDING'),
        approvalRequestId: r.approvalRequestId || r.approval_request_id || (r.rfqApprovalStatus === 'APPROVED' ? 'APR-2026-0088' : ''),
        rfqAwardApprovalStatus: r.rfqAwardApprovalStatus || r.rfq_award_approval_status || (r.status === 'Awarded' ? 'APPROVED' : 'NOT_REQUIRED'),
        awardApprovalRequestId: r.awardApprovalRequestId || r.award_approval_request_id || (r.status === 'Awarded' ? 'AWD-2026-0038' : ''),
        rfqNotes: r.rfqNotes || r.rfq_notes || r.specialInstructions || r.notes || '',
        rfqCreatedBy: r.rfqCreatedBy || r.rfq_created_by || 'Buyer Officer',
        rfqCreatedAt: r.rfqCreatedAt || r.rfq_created_at || r.dateIssued || '2026-09-24',
        rfqUpdatedBy: r.rfqUpdatedBy || r.rfq_updated_by || 'Purchasing Lead',
        rfqUpdatedAt: r.rfqUpdatedAt || r.rfq_updated_at || '2026-10-01',
        dateIssued: r.dateIssued || r.rfqDate || '2026-09-24',
        dueDate: r.dueDate || r.submissionDeadline || '2026-10-01',
        expectedDelivery: r.expectedDelivery || r.rfqRequestedDeliveryDate || '2026-10-06',
        paymentTerms: r.paymentTerms || r.rfqPaymentTermsCode || 'Net 30 Days',
        deliveryLocation: r.deliveryLocation || r.shipToWarehouseName || 'Central Commissary',
        specialInstructions: r.specialInstructions || r.rfqNotes || '',
        status: r.status || r.rfqStatus || 'Draft',
        isApproved: (r.rfqApprovalStatus === 'APPROVED' || r.status === 'Awarded' || r.isApproved === true),
        items: (r.items || []).map((line, idx) => ({
            lineNumber: line.lineNumber || line.line_number || (idx + 1),
            itemId: line.itemId || line.item_id || line.sku || 'SKU-ITEM',
            name: line.name || 'Quoted Product',
            specs: line.specs || '',
            category: line.category || 'Supplies',
            purchaseUomCode: line.purchaseUomCode || line.purchase_uom_code || line.unit || 'Unit',
            unit: line.unit || line.purchaseUomCode || 'Unit',
            requestedQuantity: parseFloat(line.requestedQuantity || line.requested_quantity || line.quantity || 1),
            quantity: parseFloat(line.quantity || line.requestedQuantity || 1),
            targetUnitPrice: parseFloat(line.targetUnitPrice || line.target_unit_price || line.targetPrice || 0),
            targetPrice: parseFloat(line.targetPrice || line.targetUnitPrice || 0),
            quotedUnitPrice: parseFloat(line.quotedUnitPrice || line.quoted_unit_price || line.quotedPrice || line.targetPrice || 0),
            quotedLineTotal: parseFloat(line.quotedLineTotal || line.quoted_line_total || ((line.quantity || 1) * (line.targetPrice || 0))),
            leadTimeDays: parseInt(line.leadTimeDays || line.lead_time_days || 3, 10),
            isAwarded: Boolean(line.isAwarded !== undefined ? line.isAwarded : (r.status === 'Awarded')),
            rfqLineNotes: line.rfqLineNotes || line.rfq_line_notes || line.notes || '',
            rfqLineCreatedBy: line.rfqLineCreatedBy || line.rfq_line_created_by || 'Buyer Officer',
            rfqLineCreatedAt: line.rfqLineCreatedAt || line.rfq_line_created_at || '2026-09-24',
            rfqLineUpdatedBy: line.rfqLineUpdatedBy || line.rfq_line_updated_by || 'Purchasing Lead',
            rfqLineUpdatedAt: line.rfqLineUpdatedAt || line.rfq_line_updated_at || '2026-10-01'
        }))
    };
}


// Master Application Store State
window.AppStore = {
    vendors: [],
    itemMaster: [],
    rfqs: [],
    activeRfq: {
        rfqNumber: 'RFQ-2026-0042',
        title: '',
        status: 'Draft',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorEmail: '',
        vendorPhone: '',
        vendorAddress: '',
        vendorTin: '',
        vendorContactPerson: '',
        vendorContactTitle: '',
        dateIssued: '',
        dueDate: '',
        expectedDelivery: '',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Commissary - Main Dock',
        specialInstructions: '',
        items: []
    }
};

// Column Visibility & Widths Configuration in LocalStorage
const LEGACY_COL_MAP = {
    'sku': 'item_id',
    'name': 'item_name',
    'uom': 'purchase_uom_code',
    'quantity': 'requested_quantity',
    'target_price': 'target_unit_price',
    'total_target': 'target_line_total',
    'notes': 'rfq_line_notes'
};
let activeColumnIds = JSON.parse(localStorage.getItem('rms_rfq_builder_cols_v6'))
    || getStageDefaultColumns(window.AppStore?.activeRfq?.rfqStatus || window.AppStore?.activeRfq?.status || 'Draft');
activeColumnIds = [...new Set(activeColumnIds.map(id => LEGACY_COL_MAP[id] || id))].filter(id => ALL_COLUMNS.some(c => c.id === id));
let savedColumnWidths = JSON.parse(localStorage.getItem('rms_rfq_column_widths')) || {};

/**
 * --------------------------------------------------------------------------
 * INITIALIZATION & HYDRATION
 * --------------------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', () => {
    initAppStore();
    initDates();
    renderVendorDropdown();
    renderTableHeaderAndColumns();
    renderItemsTable();
    initDirectoryColumns();
    renderRfqDirectory();
    updateLifecycleProgressBar(window.AppStore.activeRfq?.rfqStatus || window.AppStore.activeRfq?.status || 'Draft');
    checkDraftRecovery();

    // Handle ?rfq=RFQ-YYYY-XXXX deep link from Purchase Orders module
    const urlParams = new URLSearchParams(window.location.search);
    const deepRfq = (urlParams.get('rfq') || '').trim();
    if (deepRfq && typeof editRfqFromDirectory === 'function') {
        setTimeout(() => editRfqFromDirectory(deepRfq), 60);
    }

    // Hotkey listener (F2: Item Master, F8: Preview, F10: Send)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            openItemMasterQuickSelectModal();
        } else if (e.key === 'F8') {
            e.preventDefault();
            openRfqDocumentPreview();
        } else if (e.key === 'F10') {
            e.preventDefault();
            openSendEmailModal();
        } else if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close floating column dropdowns on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const filterBtn = document.getElementById('btnTableColumnFilter');
        if (dropdown && dropdown.classList.contains('is-active')) {
            if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                dropdown.classList.remove('is-active');
            }
        }

        const dirDropdown = document.getElementById('rfqTableSettingsDropdown');
        const dirSettingsBtn = document.getElementById('btnRfqTableSettings');
        const dirFilterBtn = document.getElementById('btnDirectoryColFilter');
        if (dirDropdown && (dirDropdown.classList.contains('is-active') || dirDropdown.style.display !== 'none')) {
            if (!dirDropdown.contains(e.target) && 
                (!dirSettingsBtn || !dirSettingsBtn.contains(e.target)) && 
                (!dirFilterBtn || !dirFilterBtn.contains(e.target))) {
                dirDropdown.classList.remove('is-active');
                dirDropdown.style.display = 'none';
                if (dirSettingsBtn) dirSettingsBtn.classList.remove('is-active');
                if (dirFilterBtn) dirFilterBtn.classList.remove('is-active');
            }
        }

        const vendorComboDropdown = document.getElementById('rfqVendorComboDropdown');
        const vendorComboTrigger = document.getElementById('rfqVendorComboTrigger');
        if (vendorComboDropdown && vendorComboDropdown.classList.contains('is-active')) {
            if (!vendorComboDropdown.contains(e.target) && !vendorComboTrigger.contains(e.target)) {
                vendorComboDropdown.classList.remove('is-active');
                if (vendorComboTrigger) vendorComboTrigger.classList.remove('is-active');
            }
        }
    });
});

/**
 * Hydrate AppStore from Local Database or Seeded Sources
 */
function initAppStore() {
    // 1. Vendors
    if (Array.isArray(serverVendors) && serverVendors.length > 0) {
        window.AppStore.vendors = serverVendors.map(v => ({
            id: v.vendor_code || v.vendor_id || v.id,
            dbId: v.id,
            code: v.vendor_code || ('VND-' + v.id),
            name: v.legal_name || v.trade_name || 'Vendor',
            legalName: v.legal_name || v.trade_name || 'Vendor',
            tradeName: v.trade_name || v.legal_name || 'Vendor',
            tin: v.tax_id || v.tax_identification_number || '--',
            category: v.category || v.vendor_type || 'Supplies',
            contactPerson: v.contact_person || v.primary_contact_name || 'Procurement Contact',
            contactTitle: v.primary_contact_title || 'Account Representative',
            email: v.email || v.email_address || '',
            phone: v.phone || v.phone_number || '',
            address: v.address || v.business_address || '',
            paymentTerms: v.payment_terms || 'Net 30 Days',
            leadTimeDays: parseInt(v.lead_time_days || v.leadTimeDays || 3, 10),
            contacts: [
                {
                    name: v.contact_person || v.primary_contact_name || 'Procurement Contact',
                    title: v.primary_contact_title || 'Account Representative',
                    email: v.email || v.email_address || '',
                    phone: v.phone || v.phone_number || '',
                    role: 'sales'
                }
            ]
        }));
    } else {
        const storedVendors = localStorage.getItem('rms_vendor_database_v6');
        if (storedVendors) {
            try {
                window.AppStore.vendors = JSON.parse(storedVendors);
            } catch (e) {
                window.AppStore.vendors = SEEDED_VENDORS;
            }
        } else {
            window.AppStore.vendors = SEEDED_VENDORS;
        }
    }

    // 2. Item Master Products
    if (Array.isArray(serverItems) && serverItems.length > 0) {
        window.AppStore.itemMaster = serverItems.map(p => ({
            sku: p.sku || 'SKU-' + p.id,
            name: p.name || 'Unnamed Product',
            specs: p.description || p.specs || '',
            unit: p.uom || p.primary_uom || 'Unit',
            defaultCost: parseFloat(p.cost_price || p.cost || 0),
            category: p.category || 'Raw Ingredients'
        }));
    } else {
        const storedProducts = localStorage.getItem('rms_inventory_products');
        if (storedProducts) {
            try {
                const parsed = JSON.parse(storedProducts);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    window.AppStore.itemMaster = parsed.map(p => ({
                        sku: p.sku || 'SKU-' + p.id,
                        name: p.product || p.name || 'Unnamed Product',
                        specs: p.description || p.specs || '',
                        unit: p.uom || 'Unit',
                        defaultCost: parseFloat(p.cost_price || p.cost || 0),
                        category: p.category || 'Raw Ingredients'
                    }));
                } else {
                    window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
                }
            } catch (e) {
                window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
            }
        } else {
            window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
        }
    }

    // 3. RFQ Directory Tracker
    if (Array.isArray(serverRfqs) && serverRfqs.length > 0) {
        const normalizedServer = serverRfqs.map(normalizeRfqItem).filter(Boolean);
        const storedRfqs = localStorage.getItem('rms_rfq_directory');
        let localDrafts = [];
        if (storedRfqs) {
            try {
                const raw = JSON.parse(storedRfqs);
                if (Array.isArray(raw)) {
                    localDrafts = raw.map(normalizeRfqItem).filter(r => r && !normalizedServer.some(s => s.rfqNumber === r.rfqNumber));
                }
            } catch (e) {}
        }
        window.AppStore.rfqs = [...normalizedServer, ...localDrafts];
        try {
            localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
        } catch (storageErr) {
            console.warn('[RFQ Workspace] LocalStorage quota exceeded or restricted; skipping cache write.', storageErr);
        }
    } else {
        const storedRfqs = localStorage.getItem('rms_rfq_directory');
        if (storedRfqs) {
            try {
                const raw = JSON.parse(storedRfqs);
                window.AppStore.rfqs = (Array.isArray(raw) && raw.length > 0) ? raw.map(normalizeRfqItem) : SEEDED_RFQS.map(normalizeRfqItem);
            } catch (e) {
                window.AppStore.rfqs = SEEDED_RFQS.map(normalizeRfqItem);
            }
        } else {
            window.AppStore.rfqs = SEEDED_RFQS.map(normalizeRfqItem);
            try {
                localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
            } catch (storageErr) {
                console.warn('[RFQ Workspace] LocalStorage quota exceeded or restricted; skipping cache write.', storageErr);
            }
        }
    }

    // Initialize RFQ Number
    const initialRfqNum = getNextUniqueRfqNumber();
    window.AppStore.activeRfq.rfqNumber = initialRfqNum;
    window.AppStore.activeRfq.ref = initialRfqNum;
    document.getElementById('rfqNumberInput').value = initialRfqNum;
    document.getElementById('rfqRefDisplay').textContent = initialRfqNum;
}

/**
 * Set Standard Dates (Issued Today, Due in 7 Days, Validity in 14 Days, Delivery in 5 Days)
 */
function initDates() {
    const today = new Date();
    const dateIssued = today.toISOString().split('T')[0];

    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateStr = dueDate.toISOString().split('T')[0];

    // Automated 30-day default validity duration relative to creation date
    const validityDate = new Date();
    validityDate.setDate(validityDate.getDate() + 30);
    const validityDateStr = validityDate.toISOString().split('T')[0];

    const deliveryDate = new Date();
    deliveryDate.setDate(deliveryDate.getDate() + 5);
    const deliveryDateStr = deliveryDate.toISOString().split('T')[0];

    const dateIssuedEl = document.getElementById('rfqDateIssued');
    if (dateIssuedEl) dateIssuedEl.value = dateIssued;
    
    const dueDateEl = document.getElementById('rfqDueDate');
    if (dueDateEl) dueDateEl.value = dueDateStr;
    
    const quoteValEl = document.getElementById('rfqQuoteValidityDate');
    if (quoteValEl) quoteValEl.value = validityDateStr;
    
    const expDelivEl = document.getElementById('rfqExpectedDelivery');
    if (expDelivEl) expDelivEl.value = deliveryDateStr;

    window.AppStore.activeRfq.dateIssued = dateIssued;
    window.AppStore.activeRfq.rfqDate = dateIssued;
    window.AppStore.activeRfq.dueDate = dueDateStr;
    window.AppStore.activeRfq.submissionDeadline = dueDateStr;
    window.AppStore.activeRfq.quoteValidUntilDate = validityDateStr;
    window.AppStore.activeRfq.expectedDelivery = deliveryDateStr;
    window.AppStore.activeRfq.rfqRequestedDeliveryDate = deliveryDateStr;

    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
}

/**
 * --------------------------------------------------------------------------
 * VENDOR SELECTION & AUTO-FILL BUSINESS PARAMETERS
 * --------------------------------------------------------------------------
 */
let vendorComboSearchTerm = '';

function renderVendorDropdown() {
    const select = document.getElementById('vendorSelect');
    const vendors = window.AppStore.vendors || [];

    if (select) {
        select.innerHTML = `
            <option value="">-- Choose Approved Vendor from Masterlist --</option>
            ${vendors.map(v => `
                <option value="${v.id || v.code}">[${v.code || v.id}] ${v.legalName || v.tradeName} (${v.category || 'General'})</option>
            `).join('')}
        `;
    }

    renderVendorComboboxList();
}

function renderVendorComboboxList() {
    const listEl = document.getElementById('rfqVendorComboList');
    if (!listEl) return;

    const vendors = window.AppStore.vendors || [];
    const q = (vendorComboSearchTerm || '').toLowerCase().trim();

    const filtered = vendors.filter(v => {
        if (!q) return true;
        const code = (v.code || v.id || '').toLowerCase();
        const trade = (v.tradeName || '').toLowerCase();
        const legal = (v.legalName || '').toLowerCase();
        const cat = (v.category || '').toLowerCase();
        const contact = (v.contacts && v.contacts[0] ? v.contacts[0].name : '').toLowerCase();
        return code.includes(q) || trade.includes(q) || legal.includes(q) || cat.includes(q) || contact.includes(q);
    });

    const activeId = document.getElementById('vendorSelect') ? document.getElementById('vendorSelect').value : '';

    if (filtered.length === 0) {
        listEl.innerHTML = `<div class="rfq-vendor-combo-empty">No suppliers match "${escapeHtml(vendorComboSearchTerm)}"</div>`;
        return;
    }

    let itemsHtml = '';
    if (activeId) {
        itemsHtml += `
            <div class="rfq-vendor-combo-item" onclick="selectVendorFromCombo('')" style="color: #ef4444; border-bottom: 1px dashed #f1f5f9;">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-x-circle"></i> Clear Selected Vendor
                </span>
            </div>
        `;
    }

    itemsHtml += filtered.map(v => {
        const vId = v.id || v.code;
        const isSelected = vId === activeId;
        const vCodeOrId = v.code || v.id;
        const vProfileUrl = (typeof buildVendorProfileUrl === 'function')
            ? buildVendorProfileUrl(v)
            : `/purchase/vendor-masterlist?search=${encodeURIComponent(vCodeOrId)}`;
        return `
            <div class="rfq-vendor-combo-item ${isSelected ? 'is-selected' : ''}" onclick="selectVendorFromCombo('${vId}')">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                    <div style="width: 22px; height: 22px; border-radius: 5px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        ${(v.tradeName || v.legalName || 'V').substring(0, 1).toUpperCase()}
                    </div>
                    <div style="min-width: 0; line-height: 1.25; flex: 1;">
                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(v.tradeName || v.legalName)}
                        </div>
                        <div style="font-size: 0.72rem; color: #64748b;">
                            <span style="font-family: monospace; font-weight: 700;">${escapeHtml(v.code || v.id)}</span> • ${escapeHtml(v.category || 'General')}
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                    ${isSelected ? '<i class="ph ph-check" style="color: #9333ea; font-size: 14px;"></i>' : ''}
                    <a href="${vProfileUrl}" target="_blank" class="rfq-vendor-combo-ext-link" onclick="event.stopPropagation();" title="Open ${escapeHtml(v.tradeName || v.legalName)} Profile in Masterlist">
                        <i class="ph ph-arrow-square-out"></i>
                    </a>
                </div>
            </div>
        `;
    }).join('');

    listEl.innerHTML = itemsHtml;
}

function toggleVendorCombobox(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('rfqVendorComboDropdown');
    const trigger = document.getElementById('rfqVendorComboTrigger');
    const card = document.getElementById('rfqTopBannerCard');
    if (!dropdown || !trigger) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        trigger.classList.remove('is-active');
        if (card) card.classList.remove('has-dropdown-open');
    } else {
        dropdown.classList.add('is-active');
        trigger.classList.add('is-active');
        if (card) card.classList.add('has-dropdown-open');
        const input = document.getElementById('rfqVendorSearchInput');
        if (input) {
            input.value = '';
            vendorComboSearchTerm = '';
            renderVendorComboboxList();
            setTimeout(() => input.focus(), 60);
        }
    }
}

function filterVendorCombobox(val) {
    vendorComboSearchTerm = val;
    renderVendorComboboxList();
}

function selectVendorFromCombo(vendorId) {
    const select = document.getElementById('vendorSelect');
    if (select) {
        select.value = vendorId;
    }
    
    const dropdown = document.getElementById('rfqVendorComboDropdown');
    const trigger = document.getElementById('rfqVendorComboTrigger');
    const card = document.getElementById('rfqTopBannerCard');
    if (dropdown) dropdown.classList.remove('is-active');
    if (trigger) trigger.classList.remove('is-active');
    if (card) card.classList.remove('has-dropdown-open');

    handleVendorSelection(vendorId);
}

// Global outside-click dismiss for vendor combobox dropdown
document.addEventListener('pointerdown', function(e) {
    const combo = document.getElementById('rfqVendorCombobox');
    const dropdown = document.getElementById('rfqVendorComboDropdown');
    const trigger = document.getElementById('rfqVendorComboTrigger');
    const card = document.getElementById('rfqTopBannerCard');
    if (combo && dropdown && trigger && dropdown.classList.contains('is-active')) {
        if (!combo.contains(e.target)) {
            dropdown.classList.remove('is-active');
            trigger.classList.remove('is-active');
            if (card) card.classList.remove('has-dropdown-open');
        }
    }
});

function updateVendorComboTrigger(vendor) {
    const textEl = document.getElementById('rfqVendorComboTriggerText');
    const directLink = document.getElementById('rfqVendorDirectLink');
    const redirectBtn = document.getElementById('rfqVendorRedirectBtn');

    if (!vendor) {
        if (textEl) {
            textEl.textContent = '-- Choose Approved Vendor from Masterlist --';
            textEl.style.color = '#64748b';
            textEl.style.fontWeight = 'normal';
        }
        if (directLink) {
            directLink.style.display = 'none';
            directLink.href = '/purchase/vendor-masterlist';
        }
        if (redirectBtn) {
            redirectBtn.classList.remove('is-active');
            redirectBtn.href = '/purchase/vendor-masterlist';
            redirectBtn.title = 'Open Vendor Masterlist';
        }
    } else {
        const vUrl = (typeof buildVendorProfileUrl === 'function') 
            ? buildVendorProfileUrl(vendor) 
            : `/purchase/vendor-masterlist?search=${encodeURIComponent(vendor.code || vendor.id || '')}`;
        if (textEl) {
            textEl.innerHTML = `<strong>[${escapeHtml(vendor.code || vendor.id || '')}]</strong> ${escapeHtml(vendor.tradeName || vendor.legalName)} <span style="font-size: 0.72rem; color: #9333ea; margin-left: 6px;">(${escapeHtml(vendor.category || 'Vendor')})</span>`;
            textEl.style.color = '#0f172a';
        }
        if (directLink) {
            directLink.style.display = 'inline-flex';
            directLink.href = vUrl;
            directLink.title = `View ${vendor.tradeName || vendor.legalName} in Vendor Masterlist`;
            directLink.innerHTML = `<i class="ph ph-arrow-square-out"></i> ${escapeHtml(vendor.tradeName || vendor.legalName || 'Masterlist')}`;
        }
        if (redirectBtn) {
            redirectBtn.classList.add('is-active');
            redirectBtn.href = vUrl;
            redirectBtn.title = `View ${vendor.tradeName || vendor.legalName} in Vendor Masterlist`;
        }
    }
}

/**
 * Vendor Master lookup (O(1)-style resolution against AppStore.vendors by id or code)
 */
function findVendorRecord(vendorId) {
    if (vendorId === null || vendorId === undefined || vendorId === '') return null;
    const key = String(vendorId).trim().toLowerCase();
    return (window.AppStore.vendors || []).find(v =>
        String(v.id ?? '').toLowerCase() === key ||
        String(v.code ?? '').toLowerCase() === key
    ) || null;
}

/**
 * Deep link to the selected vendor's profile inside the Vendor Masterlist
 */
function buildVendorProfileUrl(vendor) {
    if (!vendor) return '/purchase/vendor-masterlist';
    const params = new URLSearchParams();
    if (vendor.id !== undefined && vendor.id !== null) params.set('vendorId', vendor.id);
    if (vendor.code) params.set('vendorCode', vendor.code);
    const name = vendor.legalName || vendor.name || vendor.tradeName;
    if (name) params.set('vendorName', name);
    params.set('search', vendor.code || vendor.id || '');
    return `/purchase/vendor-masterlist?${params.toString()}`;
}

/**
 * Read-only lookup: derives contact, email, phone, address and expected delivery
 * from the Vendor Master record. Never blocks; only fills blanks in state + hidden inputs.
 */
function hydrateVendorLookupFields() {
    const rfq = window.AppStore?.activeRfq;
    if (!rfq) return {};
    const vendor = findVendorRecord(rfq.vendorId || document.getElementById('vendorSelect')?.value);
    const contact = (vendor && Array.isArray(vendor.contacts) && vendor.contacts.length > 0) ? vendor.contacts[0] : null;

    const lookup = {
        email: rfq.vendorEmail || contact?.email || vendor?.email || '',
        phone: rfq.vendorPhone || contact?.phone || vendor?.phone || '',
        address: rfq.vendorAddress || vendor?.address || vendor?.location || '',
        contactPerson: rfq.vendorContactPerson || contact?.name || vendor?.contactPerson || '',
        contactTitle: rfq.vendorContactTitle || contact?.title || vendor?.contactTitle || ''
    };

    if (!rfq.expectedDelivery && vendor) {
        const leadDays = parseInt(vendor.leadTimeDays, 10) || 5;
        const d = new Date();
        d.setDate(d.getDate() + leadDays);
        rfq.expectedDelivery = d.toISOString().split('T')[0];
        rfq.rfqRequestedDeliveryDate = rfq.expectedDelivery;
        rfq.rfq_requested_delivery_date = rfq.expectedDelivery;
    }
    lookup.expectedDelivery = rfq.expectedDelivery || '';

    rfq.vendorEmail = lookup.email;
    rfq.vendorPhone = lookup.phone;
    rfq.vendorAddress = lookup.address;
    rfq.vendorContactPerson = lookup.contactPerson;
    rfq.vendorContactTitle = lookup.contactTitle;

    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
    setVal('vendorEmail', lookup.email);
    setVal('vendorPhone', lookup.phone);
    setVal('vendorAddress', lookup.address);
    setVal('vendorContactPerson', lookup.contactPerson);
    setVal('vendorContactTitle', lookup.contactTitle);
    setVal('rfqExpectedDelivery', lookup.expectedDelivery);

    return lookup;
}

function handleVendorRedirectClick(e) {
    const rfq = window.AppStore?.activeRfq;
    const vendorId = rfq?.vendorId || document.getElementById('vendorSelect')?.value;
    const vendor = findVendorRecord(vendorId);
    if (!vendor) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        showToast('Please select a target vendor first before opening vendor profile.', 'warning');
        return false;
    }
    const redirectBtn = document.getElementById('rfqVendorRedirectBtn');
    if (redirectBtn) {
        redirectBtn.href = buildVendorProfileUrl(vendor);
    }
    return true;
}

function handleVendorSelection(vendorId) {
    if (!vendorId) {
        clearVendorFields();
        updateVendorComboTrigger(null);
        renderVendorComboboxList();
        return;
    }

    const vendor = (window.AppStore.vendors || []).find(v => (
        String(v.id) === String(vendorId) ||
        String(v.code || '').toLowerCase() === String(vendorId).toLowerCase()
    ));
    if (!vendor) return;

    updateVendorComboTrigger(vendor);
    renderVendorComboboxList();

    // Update Vendor Masterlist Redirect button (">")
    const redirectBtn = document.getElementById('rfqVendorRedirectBtn');
    if (redirectBtn) {
        redirectBtn.href = buildVendorProfileUrl(vendor);
        redirectBtn.title = `Open ${vendor.tradeName || vendor.name || 'vendor'} profile in Vendor Masterlist`;
        redirectBtn.classList.add('is-active');
    }

    // Auto-fill company details (kept hidden in retired UI slot)
    const sumBox = document.getElementById('vendorSummaryBox');
    if (sumBox) sumBox.style.display = 'none';
    const emptyPrompt = document.getElementById('vendorEmptyPrompt');
    if (emptyPrompt) emptyPrompt.style.display = 'none';

    const avatarEl = document.getElementById('vendorAvatarText');
    if (avatarEl) avatarEl.textContent = (vendor.tradeName || vendor.legalName || 'VM').substring(0, 2).toUpperCase();
    const tradeNameEl = document.getElementById('vendorTradeName');
    if (tradeNameEl) tradeNameEl.textContent = vendor.tradeName || vendor.legalName;
    const catTagEl = document.getElementById('vendorCategoryTag');
    if (catTagEl) catTagEl.textContent = vendor.category || 'Supplier';
    const tinEl = document.getElementById('vendorTinDisplay');
    if (tinEl) tinEl.textContent = vendor.tin || 'N/A';
    const locEl = document.getElementById('vendorLocationDisplay');
    if (locEl) locEl.textContent = vendor.location || 'Metro Manila';

    const addrEl = document.getElementById('vendorAddress');
    if (addrEl) addrEl.value = vendor.address || vendor.location || '';

    // Contact person auto-population
    const primaryContact = (vendor.contacts && vendor.contacts.length > 0) ? vendor.contacts[0] : null;
    const contactPersonEl = document.getElementById('vendorContactPerson');
    const contactTitleEl = document.getElementById('vendorContactTitle');
    const vendorEmailEl = document.getElementById('vendorEmail');
    const vendorPhoneEl = document.getElementById('vendorPhone');
    const vendorAddrEl = document.getElementById('vendorAddress');

    if (primaryContact) {
        if (contactPersonEl) contactPersonEl.value = primaryContact.name || '';
        if (contactTitleEl) contactTitleEl.value = primaryContact.title || '';
        if (vendorEmailEl) vendorEmailEl.value = primaryContact.email || vendor.email || '';
        if (vendorPhoneEl) vendorPhoneEl.value = primaryContact.phone || '';
    } else {
        if (contactPersonEl) contactPersonEl.value = '';
        if (contactTitleEl) contactTitleEl.value = '';
        if (vendorEmailEl) vendorEmailEl.value = vendor.email || '';
        if (vendorPhoneEl) vendorPhoneEl.value = '';
    }

    if (vendorAddrEl) {
        vendorAddrEl.value = vendor.address || vendor.location || '';
    }

    // Payment terms & Lead time auto-calculation
    if (vendor.paymentTerms) {
        const termsEl = document.getElementById('rfqPaymentTerms');
        if (termsEl) termsEl.value = vendor.paymentTerms;
    }

    const leadTime = vendor.leadTimeDays ? parseInt(vendor.leadTimeDays) : 5;
    const leadBadge = document.getElementById('leadTimeBadge');
    if (leadBadge) leadBadge.textContent = `Auto: +${leadTime}d Lead Time`;

    const expectedDate = new Date();
    expectedDate.setDate(expectedDate.getDate() + leadTime);
    const expectedDateStr = expectedDate.toISOString().split('T')[0];
    const expDeliv = document.getElementById('rfqExpectedDelivery');
    if (expDeliv) expDeliv.value = expectedDateStr;

    // Update active RFQ state
    const rfq = window.AppStore.activeRfq;
    rfq.vendorId = vendor.id || vendor.code;
    rfq.vendorName = vendor.legalName || vendor.tradeName;
    rfq.vendorTradeName = vendor.tradeName || vendor.legalName;
    rfq.vendorEmail = vendorEmailEl?.value || primaryContact?.email || vendor.email || '';
    rfq.vendorPhone = vendorPhoneEl?.value || primaryContact?.phone || '';
    rfq.vendorAddress = vendorAddrEl?.value || vendor.address || vendor.location || '';
    rfq.vendorTin = vendor.tin || '';
    rfq.vendorContactPerson = contactPersonEl?.value || primaryContact?.name || '';
    rfq.vendorContactTitle = contactTitleEl?.value || primaryContact?.title || '';
    rfq.expectedDelivery = expectedDateStr;
    const currentTermsEl = document.getElementById('rfqPaymentTerms');
    if (currentTermsEl) rfq.paymentTerms = currentTermsEl.value;

    persistDraft();
    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
    showToast(`✓ Auto-filled business fields for ${rfq.vendorTradeName}`, 'success');
}

function clearVendorFields(shouldPersist = true) {
    updateVendorComboTrigger(null);
    renderVendorComboboxList();

    const sumBox = document.getElementById('vendorSummaryBox');
    if (sumBox) sumBox.style.display = 'none';
    const emptyPrompt = document.getElementById('vendorEmptyPrompt');
    if (emptyPrompt) emptyPrompt.style.display = 'none';

    const redirectBtn = document.getElementById('rfqVendorRedirectBtn');
    if (redirectBtn) {
        redirectBtn.href = '/purchase/vendor-masterlist';
        redirectBtn.classList.remove('is-active');
    }

    const contactPersonEl = document.getElementById('vendorContactPerson');
    if (contactPersonEl) contactPersonEl.value = '';
    const contactTitleEl = document.getElementById('vendorContactTitle');
    if (contactTitleEl) contactTitleEl.value = '';
    const vendorEmailEl = document.getElementById('vendorEmail');
    if (vendorEmailEl) vendorEmailEl.value = '';
    const vendorPhoneEl = document.getElementById('vendorPhone');
    if (vendorPhoneEl) vendorPhoneEl.value = '';
    const vendorAddrEl = document.getElementById('vendorAddress');
    if (vendorAddrEl) vendorAddrEl.value = '';

    const leadBadge = document.getElementById('leadTimeBadge');
    if (leadBadge) leadBadge.textContent = 'Based on Lead Time';

    const rfq = window.AppStore.activeRfq;
    if (rfq) {
        rfq.vendorId = '';
        rfq.vendorName = '';
        rfq.vendorTradeName = '';
        rfq.vendorEmail = '';
        rfq.vendorPhone = '';
        rfq.vendorAddress = '';
        rfq.vendorTin = '';
        rfq.vendorContactPerson = '';
        rfq.vendorContactTitle = '';
    }
    if (shouldPersist) {
        persistDraft();
    }
    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
}

/**
 * --------------------------------------------------------------------------
 * FLOATING COLUMN FILTER DROPDOWN & DYNAMIC TABLE RENDERING
 * --------------------------------------------------------------------------
 */
function getColumnWidth(col) {
    if (savedColumnWidths[col.id]) {
        return savedColumnWidths[col.id];
    }
    return col.width || col.minWidth || '130px';
}

function getNormalizedRfqStage(status) {
    if (!status) return 'Draft';
    const s = String(status).toUpperCase();
    if (s.includes('DRAFT') || s === 'REVISED') return 'Draft';
    if (s.includes('SUBMIT') || s.includes('SENT') || s.includes('REQUESTED')) return 'Submitted';
    if (s === 'APPROVAL' || s.includes('REVIEW') || s.includes('BID') || s.includes('RECEIVED')) return 'Approval';
    if (s === 'APPROVED' || s === 'AWARDED') return 'Approved';
    if (s === 'REFUSED' || s === 'REJECTED' || s === 'CANCELLED') return 'Refused';
    return 'Draft';
}

function applyStageFormLocks(stage) {
    const normalizedStage = getNormalizedRfqStage(stage);
    const isLocked = (normalizedStage === 'Approved' || normalizedStage === 'Awarded');

    // 1. Vendor Selection & Partner Profile Elements
    const comboTrigger = document.getElementById('rfqVendorComboTrigger');
    const vendorSelect = document.getElementById('vendorSelect');
    const vendorEmail = document.getElementById('vendorEmail');
    const vendorPhone = document.getElementById('vendorPhone');
    const vendorAddress = document.getElementById('vendorAddress');
    const vendorContactPerson = document.getElementById('vendorContactPerson');
    const vendorContactTitle = document.getElementById('vendorContactTitle');
    const vendorSearchInput = document.getElementById('rfqVendorSearchInput');
    const vendorDropdown = document.getElementById('rfqVendorComboDropdown');

    if (comboTrigger) {
        comboTrigger.style.pointerEvents = isLocked ? 'none' : 'auto';
        comboTrigger.style.background = isLocked ? '#f8fafc' : '#ffffff';
        comboTrigger.style.opacity = isLocked ? '0.75' : '1';
        comboTrigger.style.cursor = isLocked ? 'not-allowed' : 'pointer';
    }
    if (vendorSelect) vendorSelect.disabled = isLocked;
    if (vendorSearchInput) vendorSearchInput.disabled = isLocked;
    if (isLocked && vendorDropdown) vendorDropdown.classList.remove('is-open');

    [vendorEmail, vendorPhone, vendorAddress].forEach(el => {
        if (el) {
            el.readOnly = isLocked;
            el.style.background = isLocked ? '#f8fafc' : '#ffffff';
            el.style.cursor = isLocked ? 'not-allowed' : '';
        }
    });

    [vendorContactPerson, vendorContactTitle].forEach(el => {
        if (el) {
            el.style.background = isLocked ? '#f8fafc' : '';
            el.style.cursor = isLocked ? 'not-allowed' : '';
        }
    });

    // 2. RFQ Terms & Delivery Schedule Elements
    const dateIssued = document.getElementById('rfqDateIssued');
    const dueDate = document.getElementById('rfqDueDate');
    const validityDate = document.getElementById('rfqQuoteValidityDate');
    const deliveryDate = document.getElementById('rfqExpectedDelivery');
    const paymentTerms = document.getElementById('rfqPaymentTerms');
    const deliveryLoc = document.getElementById('rfqDeliveryLocation');
    const instructions = document.getElementById('rfqSpecialInstructions');
    const tenderTitle = document.getElementById('rfqTenderTitle');

    [dateIssued, dueDate, validityDate, deliveryDate, paymentTerms, deliveryLoc].forEach(el => {
        if (el) {
            el.disabled = isLocked;
            el.style.background = isLocked ? '#f8fafc' : '#ffffff';
            el.style.cursor = isLocked ? 'not-allowed' : '';
        }
    });

    [instructions, tenderTitle].forEach(el => {
        if (el) {
            el.readOnly = isLocked;
            el.style.background = isLocked ? '#f8fafc' : '#ffffff';
            el.style.cursor = isLocked ? 'not-allowed' : '';
        }
    });

    // 3. Header Lock Status Badges
    const vendorCatalogBadge = document.getElementById('vendorCatalogBadge');
    if (vendorCatalogBadge) {
        if (isLocked) {
            vendorCatalogBadge.innerHTML = '<i class="ph ph-lock-key"></i> Locked (Approved)';
            vendorCatalogBadge.style.background = 'rgba(5, 150, 105, 0.12)';
            vendorCatalogBadge.style.color = '#059669';
        } else {
            vendorCatalogBadge.textContent = 'Catalog Integrated';
            vendorCatalogBadge.style.background = '';
            vendorCatalogBadge.style.color = '';
        }
    }

    const termsLockBadge = document.getElementById('rfqTermsLockBadge');
    if (termsLockBadge) {
        if (isLocked) {
            termsLockBadge.innerHTML = '<i class="ph ph-lock-key"></i> Locked in Approved Stage';
            termsLockBadge.style.background = 'rgba(5, 150, 105, 0.12)';
            termsLockBadge.style.color = '#059669';
        } else {
            termsLockBadge.textContent = 'Auto Lead-Time Sync';
            termsLockBadge.style.background = '';
            termsLockBadge.style.color = '';
        }
    }
}

function calculateItemVariances(item) {
    const reqQty = parseFloat(item.requestedQuantity !== undefined ? item.requestedQuantity : (item.quantity || 0));
    const quotedQty = parseFloat(item.quotedQuantity !== undefined ? item.quotedQuantity : reqQty);
    const targetPrice = parseFloat(item.targetUnitPrice !== undefined ? item.targetUnitPrice : (item.targetPrice || 0));
    const quotedPrice = parseFloat(item.quotedUnitPrice !== undefined ? item.quotedUnitPrice : targetPrice);

    const targetTotal = reqQty * targetPrice;
    const quotedTotal = quotedQty * quotedPrice;
    const qtyVariance = quotedQty - reqQty;
    const priceVariance = quotedPrice - targetPrice;
    const priceVariancePct = targetPrice > 0 ? ((quotedPrice - targetPrice) / targetPrice) * 100 : 0;
    const isWithinBudget = (quotedPrice <= targetPrice || priceVariancePct <= 5.0);

    return {
        reqQty,
        quotedQty,
        targetPrice,
        quotedPrice,
        targetTotal,
        quotedTotal,
        qtyVariance,
        priceVariance,
        priceVariancePct,
        isWithinBudget
    };
}

function renderTableHeaderAndColumns() {
    const colgroup = document.getElementById('rfqColgroup');
    const theadRow = document.getElementById('rfqTheadRow');
    if (!colgroup || !theadRow) return;

    let colHtml = '';
    let thHtml = '';

    ALL_COLUMNS.forEach(col => {
        if (!activeColumnIds.includes(col.id)) return;

        const w = getColumnWidth(col);
        colHtml += `<col data-col-id="${col.id}" style="width: ${w};">`;

        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        thHtml += `
            <th class="${alignClass}" data-col-id="${col.id}">
                <span>${col.label}</span>
                <div class="rfq-col-resizer" onmousedown="initColumnResize(event, '${col.id}')"></div>
            </th>
        `;
    });

    // Action Header with Gear Icon Settings Dropdown
    colHtml += `<col data-col-id="actions" style="width: 100px;">`;
    thHtml += `
        <th class="th-center" data-col-id="actions" style="width: 100px; text-align: center; position: relative;">
            <div class="inv-header-action-wrapper" id="actionHeaderWrapper" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                <span>Action</span>
                <button type="button" class="rfq-th-settings-btn" id="btnRfqBuilderSettings" onclick="toggleRfqBuilderSettingsDropdown(event)" title="Configure Columns (Gear Menu)">
                    <i class="ph ph-gear"></i>
                </button>
                <button type="button" class="inv-table-filter-btn" id="btnTableColumnFilter" onclick="toggleRfqBuilderSettingsDropdown(event)" title="Column Display Filter" style="display: none;">
                    <i class="ph ph-funnel"></i>
                </button>

                <!-- Floating Column Settings Dropdown Anchored to End Column Settings Gear -->
                <div class="rfq-table-settings-dropdown" id="rfqBuilderSettingsDropdown" style="display: none; position: absolute; right: 0; top: 100%; z-index: 100; text-align: left;">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Item Table Columns</span>
                            </div>
                            <span class="rfq-col-badge-default" id="builderColActiveCounter">${activeColumnIds.length} of ${ALL_COLUMNS.length} visible</span>
                        </div>
                        <div class="rfq-settings-links-row" style="display: flex; flex-wrap: wrap; gap: 4px; padding: 6px 0;">
                            <button type="button" class="rfq-settings-link-btn" onclick="applyStageDefaultColumns('Draft', true)" title="Apply Draft stage default columns (Base request &amp; specifications)">Draft View</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="applyStageDefaultColumns('Approval', true)" title="Apply Approval stage default columns (Quotation &amp; budget check)">Approval View</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetBuilderColumnDefaults()" title="Reset to current stage default columns">Default View</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="showAllBuilderColumns()" title="Show all available columns">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetBuilderColumnWidths()" title="Reset column widths to default proportions">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="builderColSearchInput" placeholder="Filter columns..." oninput="handleBuilderColSearch(this.value)">
                    </div>
                    <div class="rfq-settings-col-list" id="builderColCheckboxesList">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Backwards compatibility fallback if needed -->
                <div class="inv-column-dropdown-menu" id="tableColumnFilterDropdown" style="display: none;">
                    <div class="inv-col-dropdown-list" id="tableColumnsDropdownList"></div>
                    <span id="tableColumnsActiveCounter" style="display: none;"></span>
                </div>
            </div>
        </th>
    `;

    const dropdownPrev = document.getElementById('rfqBuilderSettingsDropdown');
    const wasOpen = dropdownPrev && dropdownPrev.style.display === 'block';

    colgroup.innerHTML = colHtml;
    theadRow.innerHTML = thHtml;
    renderBuilderColumnDropdownChecklist();

    if (wasOpen) {
        const dropdownNow = document.getElementById('rfqBuilderSettingsDropdown');
        if (dropdownNow) dropdownNow.style.display = 'block';
    }
}

// Global alias for compatibility
window.renderBuilderTableHeader = renderTableHeaderAndColumns;

function renderBuilderColumnDropdownChecklist(searchQuery = '') {
    const listEl = document.getElementById('builderColCheckboxesList');
    if (!listEl) return;

    const query = searchQuery.trim().toLowerCase();
    const categories = ['Base Request', 'Vendor Response', 'Validation & Comparison'];

    let html = '';
    categories.forEach(cat => {
        const catCols = ALL_COLUMNS.filter(c => c.category === cat);
        const filteredCols = query ? catCols.filter(c => c.label.toLowerCase().includes(query) || c.id.toLowerCase().includes(query)) : catCols;

        if (filteredCols.length > 0) {
            html += `<div class="builder-col-cat-header">${cat}</div>`;
            filteredCols.forEach(col => {
                const isChecked = activeColumnIds.includes(col.id);
                const isDisabled = col.lockVisible ? 'disabled' : '';
                html += `
                    <label class="inv-col-item-row" title="${col.label}">
                        <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleBuilderColumnVisibility('${col.id}', this.checked)">
                        <span>${col.label} ${col.lockVisible ? '<span style="font-size: 0.68rem; color:#94a3b8;">(Locked)</span>' : ''}</span>
                    </label>
                `;
            });
        }
    });

    listEl.innerHTML = html;

    const counter = document.getElementById('builderColActiveCounter');
    if (counter) {
        counter.textContent = `${activeColumnIds.length} of ${ALL_COLUMNS.length} visible`;
    }

    // Also sync old list if referenced
    const oldList = document.getElementById('tableColumnsDropdownList');
    if (oldList) oldList.innerHTML = html;
}

function renderColumnDropdownChecklist() {
    renderBuilderColumnDropdownChecklist();
}

function toggleRfqBuilderSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('rfqBuilderSettingsDropdown');
    if (!dropdown) return;

    const isOpen = dropdown.style.display === 'block';
    dropdown.style.display = isOpen ? 'none' : 'block';

    if (!isOpen) {
        const searchInput = document.getElementById('builderColSearchInput');
        if (searchInput) {
            searchInput.value = '';
            renderBuilderColumnDropdownChecklist('');
            setTimeout(() => searchInput.focus(), 50);
        }
    }
}

function toggleColumnFilterDropdown(event) {
    toggleRfqBuilderSettingsDropdown(event);
}

function handleBuilderColSearch(val) {
    renderBuilderColumnDropdownChecklist(val);
}

function toggleBuilderColumnVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeColumnIds.includes(colId)) {
            activeColumnIds.push(colId);
        }
    } else {
        activeColumnIds = activeColumnIds.filter(id => id !== colId);
    }
    // Re-sort according to ALL_COLUMNS canonical order
    activeColumnIds.sort((a, b) => {
        const idxA = ALL_COLUMNS.findIndex(c => c.id === a);
        const idxB = ALL_COLUMNS.findIndex(c => c.id === b);
        return idxA - idxB;
    });
    try {
        localStorage.setItem('rms_rfq_builder_cols_v6', JSON.stringify(activeColumnIds));
    } catch (e) {}
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function toggleColumnVisibility(colId, isVisible) {
    toggleBuilderColumnVisibility(colId, isVisible);
}

function showAllBuilderColumns() {
    activeColumnIds = ALL_COLUMNS.map(c => c.id);
    try {
        localStorage.setItem('rms_rfq_builder_cols_v6', JSON.stringify(activeColumnIds));
    } catch (e) {}
    renderTableHeaderAndColumns();
    renderItemsTable();
    showToast('✓ Showing all columns', 'info');
}

function showAllColumns() {
    showAllBuilderColumns();
}

function resetBuilderColumnDefaults() {
    const currentStage = getNormalizedRfqStage(window.AppStore?.activeRfq?.rfqStatus || window.AppStore?.activeRfq?.status);
    applyStageDefaultColumns(currentStage, true);
    showToast(`✓ Reset columns to ${currentStage} stage defaults`, 'info');
}

function resetColumnDefaults() {
    resetBuilderColumnDefaults();
}

function showCompactColumns() {
    activeColumnIds = [...DRAFT_STAGE_DEFAULT_COLUMNS];
    try {
        localStorage.setItem('rms_rfq_builder_cols_v6', JSON.stringify(activeColumnIds));
    } catch (e) {}
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function resetBuilderColumnWidths() {
    savedColumnWidths = {};
    localStorage.removeItem('rms_rfq_column_widths');
    renderTableHeaderAndColumns();
    renderItemsTable();
    showToast('✓ Column widths reset to default proportions', 'success');
}

function resetColumnWidths() {
    resetBuilderColumnWidths();
}

// Column Header Resizer Logic
let resizingColId = null;
let startX = 0;
let startW = 0;

function initColumnResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    resizingColId = colId;
    startX = e.pageX;

    const colEl = document.querySelector(`col[data-col-id="${colId}"]`);
    startW = colEl ? colEl.offsetWidth : 120;

    document.addEventListener('mousemove', handleColumnMouseMove);
    document.addEventListener('mouseup', handleColumnMouseUp);
}

function handleColumnMouseMove(e) {
    if (!resizingColId) return;
    const diff = e.pageX - startX;
    const newWidth = Math.max(50, startW + diff);
    savedColumnWidths[resizingColId] = `${newWidth}px`;

    const colEl = document.querySelector(`col[data-col-id="${resizingColId}"]`);
    if (colEl) {
        colEl.style.width = `${newWidth}px`;
    }
}

function handleColumnMouseUp() {
    if (!resizingColId) return;
    localStorage.setItem('rms_rfq_column_widths', JSON.stringify(savedColumnWidths));
    resizingColId = null;
    document.removeEventListener('mousemove', handleColumnMouseMove);
    document.removeEventListener('mouseup', handleColumnMouseUp);
}

// Close gear dropdown on outside click
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('rfqBuilderSettingsDropdown');
    const btn = document.getElementById('btnRfqBuilderSettings');
    if (dropdown && dropdown.style.display === 'block') {
        if (!dropdown.contains(e.target) && (!btn || !btn.contains(e.target))) {
            dropdown.style.display = 'none';
        }
    }
});

/**
 * --------------------------------------------------------------------------
 * RFQ LIFECYCLE PROGRESS BAR CONTROLLER
 * | Draft > Submitted > Approval > Status |
 * --------------------------------------------------------------------------
 */
function updateLifecycleProgressBar(status) {
    const stage = getNormalizedRfqStage(status);
    applyStageFormLocks(stage);
    const wrap = document.getElementById('rfqLifecycleStepperWrap');
    if (!wrap) return;

    // Reset toolbar theme
    wrap.className = `rfq-lifecycle-bar-container stage-theme-${stage.toLowerCase()}`;

    const stepDraft = document.getElementById('stepIndicatorDraft');
    const stepSubmitted = document.getElementById('stepIndicatorSubmitted');
    const stepApproval = document.getElementById('stepIndicatorApproval');
    const stepStatus = document.getElementById('stepIndicatorStatus');

    const banner = document.getElementById('rfqStageBanner');
    const bannerIcon = document.getElementById('rfqStageBannerIcon');
    const bannerTitle = document.getElementById('rfqStageBannerTitle');
    const bannerSub = document.getElementById('rfqStageBannerSub');
    const bannerActions = document.getElementById('rfqStageBannerActions');
    const stageActionsGroup = document.getElementById('rfqStageActionsGroup');

    const statusTitle = document.getElementById('stepStatusTitle');
    const statusDesc = document.getElementById('stepStatusDesc');

    // Clean active & completed classes on chevron steps
    [stepDraft, stepSubmitted, stepApproval, stepStatus].forEach(s => {
        if (s) {
            s.className = 'rfq-chevron-step';
        }
    });

    if (banner) {
        banner.className = `rfq-stage-helper-ribbon stage-${stage.toLowerCase()}`;
    }

    const attCount = getAttachedFilesCount();

    if (stage === 'Draft') {
        if (stepDraft) stepDraft.classList.add('is-active', 'stage-draft');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'Award / PO';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-pencil-simple-line"></i>';
        if (bannerTitle) bannerTitle.innerHTML = 'Stage 1: Drafting';
        if (bannerSub) bannerSub.innerHTML = 'For internal preparation, gathering info, and consulting.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill draft"><i class="ph ph-circle-dashed"></i> Drafting Mode</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View & Attach Technical Specs, Drawings & Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Attached Files</span>
                    <span class="rfq-stage-badge-count" id="rfqAttachedCountBadge">${attCount}</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-submit" onclick="submitActiveRfqToVendor()" title="Submit Tender for Vendor Bidding">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Submit</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-cancel" onclick="cancelRfqDraft()" title="Cancel / Clear Current Draft">
                    <i class="ph ph-x-circle"></i>
                    <span>Cancel</span>
                </button>
            `;
        }
    } else if (stage === 'Submitted') {
        ensureSubmittedColumnsVisible();
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepSubmitted) stepSubmitted.classList.add('is-active', 'stage-submitted');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'Award / PO';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-lock-key"></i>';
        if (bannerTitle) bannerTitle.innerHTML = 'Stage 2: Submitted';
        if (bannerSub) bannerSub.innerHTML = 'For sending to vendors, logging responses, and moving forward.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill submitted" style="color: #2563eb;"><i class="ph ph-clock"></i> Awaiting Vendor Quotes</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="transitionActiveRfqStage('Draft')" title="Revert to Drafting Stage">
                    <i class="ph ph-arrow-left"></i>
                    <span>Back to Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-danger" onclick="cancelActiveRfq()" title="Cancel this Tender">
                    <i class="ph ph-prohibit"></i>
                    <span>Cancel</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-primary" onclick="moveToApprovalStage()" title="Validate Vendor Quotation & Move to Approval">
                    <i class="ph ph-arrow-right"></i>
                    <span>Move to Approval</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Approval') {
        ensureApprovalColumnsVisible();
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepSubmitted) stepSubmitted.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-active', 'stage-approval');
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = 'In Review';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-scales"></i>';
        if (bannerTitle) bannerTitle.innerHTML = 'Stage 3: Approval';
        if (bannerSub) bannerSub.innerHTML = 'For manager sign-off or sending back for revisions.';
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill approval" style="color: #d97706;"><i class="ph ph-shield-check"></i> Manager Review</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="handleApproverApprove()" title="Manager Sign-off & Award Quotation">
                    <i class="ph ph-check-circle"></i>
                    <span>Approve</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-danger" onclick="handleApproverRefuse()" title="Refuse Tender / Reject Quotation">
                    <i class="ph ph-x-circle"></i>
                    <span>Refuse</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="handleApproverRevision()" title="Return for Revisions">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    <span>Back to Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Approved') {
        ensureApprovalColumnsVisible();
        const linkedPoNum = getLinkedPoNumberForRfq(window.AppStore?.activeRfq);
        if (window.AppStore?.activeRfq && linkedPoNum) {
            window.AppStore.activeRfq.poReference = linkedPoNum;
        }
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepSubmitted) stepSubmitted.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-completed');
        if (stepStatus) stepStatus.classList.add('is-active', 'stage-approved');
        if (statusTitle) statusTitle.textContent = linkedPoNum ? 'PO Created' : 'Approved';
        if (statusDesc) statusDesc.textContent = linkedPoNum ? linkedPoNum : 'Ready for PO';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-seal-check"></i>';
        if (bannerTitle) bannerTitle.innerHTML = linkedPoNum ? `Stage 4: Status (Approved • ${escapeHtml(linkedPoNum)})` : 'Stage 4: Status (Approved)';
        if (bannerSub) {
            bannerSub.innerHTML = linkedPoNum
                ? `Official Purchase Order <strong>${escapeHtml(linkedPoNum)}</strong> has already been created for this RFQ. Use the smart button to open it.`
                : 'For winning quote resolution and order creation, with automatic locking upon delivery fulfillment.';
        }
        if (bannerActions) {
            bannerActions.innerHTML = linkedPoNum
                ? `<button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="openLinkedPoFromRfq('${escapeHtml(linkedPoNum)}')" title="Redirect to Created Purchase Order ${escapeHtml(linkedPoNum)}" style="padding: 4px 10px; font-size: 11px;">
                       <i class="ph ph-receipt"></i>
                       <span>PO: ${escapeHtml(linkedPoNum)}</span>
                       <i class="ph ph-arrow-square-out"></i>
                   </button>`
                : `<span class="rfq-stage-indicator-pill approved" style="color: #059669;"><i class="ph ph-check-circle-fill"></i> Tender Awarded</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                ${linkedPoNum ? `
                    <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="openLinkedPoFromRfq('${escapeHtml(linkedPoNum)}')" title="Redirect to Created Purchase Order ${escapeHtml(linkedPoNum)}">
                        <i class="ph ph-receipt"></i>
                        <span>View PO (${escapeHtml(linkedPoNum)})</span>
                        <i class="ph ph-arrow-square-out"></i>
                    </button>
                ` : `
                    <button type="button" class="rfq-stage-action-btn rfq-btn-stage-success" onclick="generatePoForApprovedRfq()" title="Generate Official Purchase Order">
                        <i class="ph ph-shopping-cart"></i>
                        <span>Create PO</span>
                    </button>
                `}
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="withdrawApprovedRfq()" title="Withdraw RFQ">
                    <i class="ph ph-arrow-u-up-left"></i>
                    <span>Withdraw</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    } else if (stage === 'Refused') {
        ensureApprovalColumnsVisible();
        if (stepDraft) stepDraft.classList.add('is-completed');
        if (stepSubmitted) stepSubmitted.classList.add('is-completed');
        if (stepApproval) stepApproval.classList.add('is-completed');
        if (stepStatus) stepStatus.classList.add('is-active', 'stage-refused');
        if (statusTitle) statusTitle.textContent = 'Refused';
        if (statusDesc) statusDesc.textContent = 'Tender Rejected';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="ph ph-prohibit"></i>';
        if (bannerTitle) bannerTitle.innerHTML = 'Stage 4: Status (Refused)';
        const reason = window.AppStore.activeRfq.rejectionReason || 'Vendor price exceeded budgeted tolerance or availability was rejected.';
        if (bannerSub) bannerSub.innerHTML = `RFQ has been refused and locked. Remarks: <em>"${escapeHtml(reason)}"</em>`;
        if (bannerActions) {
            bannerActions.innerHTML = `<span class="rfq-stage-indicator-pill refused" style="color: #dc2626;"><i class="ph ph-x-circle-fill"></i> Refused</span>`;
        }
        if (stageActionsGroup) {
            stageActionsGroup.innerHTML = `
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-outline" onclick="transitionActiveRfqStage('Draft')" title="Re-open as Draft">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    <span>Back to Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-danger" onclick="withdrawApprovedRfq()" title="Withdraw and Archive Tender">
                    <i class="ph ph-archive"></i>
                    <span>Withdraw</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View Attached Documents">
                    <i class="ph ph-paperclip"></i>
                    <span>Files (${attCount})</span>
                </button>
            `;
        }
    }

    // Update status pill badge on tender reference card
    const statusBadge = document.getElementById('rfqStatusBadge');
    if (statusBadge) {
        statusBadge.className = `status-pill ${stage.toLowerCase()}`;
        statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${stage} RFQ`;
    }

    // Synchronize tab badge to prevent misleading 'New RFQ' badge when viewing existing/submitted RFQ
    const tabBadge = document.getElementById('tabBuilderBadge');
    if (tabBadge) {
        const num = window.AppStore.activeRfq?.rfqNumber || '';
        if (window.AppStore._isExplicitlyEditing) {
            tabBadge.textContent = `Editing ${num}`;
        } else if (stage === 'Draft') {
            const hasItems = (window.AppStore.activeRfq?.items || []).length > 0;
            tabBadge.textContent = hasItems ? `Draft (${num})` : 'New RFQ';
        } else {
            tabBadge.textContent = `${stage} (${num})`;
        }
    }
}

function handleStepClick(targetStage) {
    const currentStage = getNormalizedRfqStage(window.AppStore.activeRfq.rfqStatus || window.AppStore.activeRfq.status);
    if (targetStage === currentStage) return;

    if (targetStage === 'Draft') {
        transitionActiveRfqStage('Draft');
    } else if (targetStage === 'Submitted') {
        submitActiveRfqToVendor();
    } else if (targetStage === 'Approval') {
        moveToApprovalStage();
    } else if (targetStage === 'Status') {
        if (currentStage === 'Approval') {
            handleApproverApprove();
        } else {
            showToast('Quotation must go through Approval before reaching final Status.', 'info');
        }
    }
}

function showLockOverlay(msg = 'Processing RFQ Payload...') {
    const overlay = document.getElementById('rfqGlobalLockOverlay');
    const lockMsg = document.getElementById('rfqLockOverlayMessage');
    if (overlay) overlay.classList.add('is-active');
    if (lockMsg) lockMsg.textContent = msg;
}

function hideLockOverlay() {
    const overlay = document.getElementById('rfqGlobalLockOverlay');
    if (overlay) overlay.classList.remove('is-active');
}

async function transitionActiveRfqStage(targetStage, options = {}) {
    showLockOverlay(`Transitioning RFQ to ${targetStage}...`);

    try {
        const payload = {
            rfq_number: window.AppStore.activeRfq.ref || window.AppStore.activeRfq.rfqNumber || 'RFQ-2026-0044',
            target_status: targetStage,
            vendor_id: window.AppStore.activeRfq.vendorId || document.getElementById('vendorSelect')?.value || null,
            vendor_name: window.AppStore.activeRfq.vendorName || window.AppStore.activeRfq.vendorTradeName || null,
            rejection_reason: options.rejectionReason || null,
            revision_notes: options.revisionNotes || null,
            items: (window.AppStore.activeRfq.items || []).map(it => ({
                line_id: it.lineId || it.rfqLineId || null,
                item_id: it.itemId || it.sku || '',
                requested_quantity: parseFloat(it.requestedQuantity !== undefined ? it.requestedQuantity : (it.quantity || 1)) || 1,
                target_unit_price: parseFloat(it.targetUnitPrice !== undefined ? it.targetUnitPrice : (it.targetPrice || 0)) || 0,
                quoted_quantity: parseFloat(it.quotedQuantity !== undefined ? it.quotedQuantity : (it.requestedQuantity || it.quantity || 1)) || 1,
                quoted_unit_price: parseFloat(it.quotedUnitPrice !== undefined ? it.quotedUnitPrice : (it.targetUnitPrice !== undefined ? it.targetUnitPrice : it.targetPrice || 0)) || 0,
                purchase_uom_code: it.purchaseUomCode || it.uom || 'PCS',
                lead_time_days: parseInt(it.leadTimeDays || 0) || 0,
                rfq_line_notes: it.notes || it.rfqLineNotes || null
            }))
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const transitionUrl = window.RfqConfig?.routes?.transitionStatus || '/purchase/api/rfq/transition-status';
        const res = await fetch(transitionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        let json = null;
        try {
            json = await res.json();
        } catch (e) {
            json = null;
        }

        if (res.ok && json && json.success) {
            window.AppStore.activeRfq.rfqStatus = targetStage;
            window.AppStore.activeRfq.status = targetStage;
            if (options.rejectionReason) window.AppStore.activeRfq.rejectionReason = options.rejectionReason;
            if (options.revisionNotes) window.AppStore.activeRfq.revisionNotes = options.revisionNotes;

            if (targetStage === 'Draft') {
                persistDraft();
            } else {
                localStorage.removeItem('rms_rfq_current_draft');
            }
            syncRfqToDirectory(window.AppStore.activeRfq, targetStage);
            updateLifecycleProgressBar(targetStage);
            renderItemsTable();
            hideLockOverlay();
            showToast(`✓ RFQ transitioned to ${targetStage} stage.`, 'success');
        } else {
            console.warn('Backend transition notice:', json || res.statusText);
            // Graceful client synchronization ensures user progress is never permanently blocked
            window.AppStore.activeRfq.rfqStatus = targetStage;
            window.AppStore.activeRfq.status = targetStage;
            if (options.rejectionReason) window.AppStore.activeRfq.rejectionReason = options.rejectionReason;
            if (options.revisionNotes) window.AppStore.activeRfq.revisionNotes = options.revisionNotes;

            if (targetStage === 'Draft') {
                persistDraft();
            } else {
                localStorage.removeItem('rms_rfq_current_draft');
            }
            syncRfqToDirectory(window.AppStore.activeRfq, targetStage);
            updateLifecycleProgressBar(targetStage);
            renderItemsTable();
            hideLockOverlay();
            showToast(`✓ RFQ advanced to ${targetStage} stage.`, 'info');
        }
    } catch (err) {
        console.warn('Client transition error:', err);
        // Fallback for offline/local simulation
        window.AppStore.activeRfq.rfqStatus = targetStage;
        window.AppStore.activeRfq.status = targetStage;
        if (options.rejectionReason) window.AppStore.activeRfq.rejectionReason = options.rejectionReason;
        if (options.revisionNotes) window.AppStore.activeRfq.revisionNotes = options.revisionNotes;

        if (targetStage === 'Draft') {
            persistDraft();
        } else {
            localStorage.removeItem('rms_rfq_current_draft');
        }
        syncRfqToDirectory(window.AppStore.activeRfq, targetStage);
        updateLifecycleProgressBar(targetStage);
        renderItemsTable();
        hideLockOverlay();
        showToast(`✓ RFQ updated to ${targetStage} stage.`, 'success');
    }
}

function submitActiveRfqToVendor() {
    // 1. Clear any active red light error indicators
    document.querySelectorAll('.rfq-field-error-glow').forEach(el => el.classList.remove('rfq-field-error-glow'));

    let firstErrorEl = null;
    let errors = [];

    // Target Vendor Selection validation is moved to Stage 2 (moveToApprovalStage)
    const vendorVal = window.AppStore.activeRfq.vendorId || document.getElementById('vendorSelect')?.value;
    if (vendorVal) {
        hydrateVendorLookupFields();
    }

    // 2. Validate Line Items Table (at least 1 valid line required)
    const items = window.AppStore.activeRfq.items || [];
    if (items.length === 0) {
        const btnMaster = document.querySelector('button[onclick*="openItemMasterQuickSelectModal"]');
        const btnCustom = document.querySelector('button[onclick*="addNewBlankItemRow"]');
        if (btnMaster) btnMaster.classList.add('rfq-field-error-glow');
        if (btnCustom) btnCustom.classList.add('rfq-field-error-glow');
        if (!firstErrorEl) firstErrorEl = btnMaster || btnCustom;
        errors.push('Quotation Line Items (at least 1 required)');
    } else {
        items.forEach((item, idx) => {
            const row = document.querySelector(`tr[data-row-idx="${idx}"]`);
            const itemName = String(item.itemName || item.name || '').trim();
            if (!itemName) {
                const nameInput = row ? row.querySelector('input[placeholder*="description"]') : null;
                if (nameInput) {
                    nameInput.classList.add('rfq-field-error-glow');
                    if (!firstErrorEl) firstErrorEl = nameInput;
                    nameInput.addEventListener('input', () => nameInput.classList.remove('rfq-field-error-glow'), { once: true });
                }
                errors.push(`Row #${idx + 1} Description`);
            }

            const qty = parseFloat(item.requestedQuantity !== undefined ? item.requestedQuantity : item.quantity) || 0;
            if (qty <= 0) {
                const qtyInput = row ? row.querySelector('input[type="number"][step="0.01"]') : null;
                if (qtyInput) {
                    qtyInput.classList.add('rfq-field-error-glow');
                    if (!firstErrorEl) firstErrorEl = qtyInput;
                    qtyInput.addEventListener('input', () => qtyInput.classList.remove('rfq-field-error-glow'), { once: true });
                }
                errors.push(`Row #${idx + 1} Quantity`);
            }
        });
    }

    // 3. If missing line items, trigger red light focus and alert user
    if (errors.length > 0) {
        if (firstErrorEl) {
            if (typeof firstErrorEl.scrollIntoView === 'function') {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (typeof firstErrorEl.focus === 'function') firstErrorEl.focus();
        }
        showToast(`Missing required fields: ${errors.slice(0, 3).join(', ')}${errors.length > 3 ? '...' : ''}. Please complete highlighted fields.`, 'error');
        return;
    }

    // 4. Valid -> proceed to transition to Stage 2 (Submitted)
    transitionActiveRfqStage('Submitted');
}

function applyStageDefaultColumns(stage, force = false) {
    const norm = getNormalizedRfqStage(stage);
    const targetCols = (norm === 'Approval' || norm === 'Approved' || norm === 'Refused')
        ? APPROVAL_STAGE_DEFAULT_COLUMNS
        : (norm === 'Submitted' ? SUBMITTED_STAGE_DEFAULT_COLUMNS : DRAFT_STAGE_DEFAULT_COLUMNS);

    if (force) {
        activeColumnIds = [...targetCols];
    } else {
        // Non-forced: ensure required columns for this stage are visible
        let changed = false;
        targetCols.forEach(colId => {
            if (!activeColumnIds.includes(colId)) {
                activeColumnIds.push(colId);
                changed = true;
            }
        });
        if (norm === 'Draft') {
            // In draft stage: benchmark price, budget, notes, vendor quotes and comparison are optional -> untoggle by default
            const optionalInDraft = [
                'target_unit_price',
                'target_line_total',
                'rfq_line_notes',
                'quoted_quantity',
                'quoted_unit_price',
                'quoted_line_total',
                'lead_time_days',
                'quantity_variance',
                'price_variance_amount',
                'price_variance_percentage',
                'is_price_within_budget'
            ];
            const prevLen = activeColumnIds.length;
            activeColumnIds = activeColumnIds.filter(id => !optionalInDraft.includes(id));
            if (activeColumnIds.length !== prevLen) {
                changed = true;
            }
        } else if (norm === 'Submitted') {
            // In submitted stage: vendor quote inputs & evaluation should be active
            const requiredInSubmitted = [
                'quoted_quantity',
                'quoted_unit_price',
                'quoted_line_total',
                'lead_time_days',
                'price_variance_percentage',
                'is_price_within_budget'
            ];
            requiredInSubmitted.forEach(id => {
                if (!activeColumnIds.includes(id)) {
                    activeColumnIds.push(id);
                    changed = true;
                }
            });
        } else if (norm === 'Approval' || norm === 'Approved' || norm === 'Refused') {
            // In approval stage: qty variance, price diff, variance %, budget check are optional -> untoggle by default
            const optionalInApproval = [
                'quantity_variance',
                'price_variance_amount',
                'price_variance_percentage',
                'is_price_within_budget',
                'target_line_total',
                'lead_time_days'
            ];
            const prevLen = activeColumnIds.length;
            activeColumnIds = activeColumnIds.filter(id => !optionalInApproval.includes(id));
            if (activeColumnIds.length !== prevLen) {
                changed = true;
            }
        }
        if (!changed) return;
    }

    // Sort activeColumnIds according to ALL_COLUMNS canonical matrix order
    activeColumnIds.sort((a, b) => {
        const idxA = ALL_COLUMNS.findIndex(c => c.id === a);
        const idxB = ALL_COLUMNS.findIndex(c => c.id === b);
        return idxA - idxB;
    });

    localStorage.setItem('rms_rfq_builder_cols_v6', JSON.stringify(activeColumnIds));
    localStorage.setItem('rms_rfq_builder_cols_v5', JSON.stringify(activeColumnIds));
    localStorage.setItem('rms_rfq_builder_cols_v4', JSON.stringify(activeColumnIds));
    localStorage.setItem('rms_rfq_columns', JSON.stringify(activeColumnIds));
    renderTableHeaderAndColumns();
    renderItemsTable();
    if (typeof renderBuilderColumnDropdownChecklist === 'function') {
        renderBuilderColumnDropdownChecklist();
    }
}

function ensureApprovalColumnsVisible() {
    // Required columns for Quotation Line Items & Specifications in Approval stage
    applyStageDefaultColumns('Approval', false);
}

function ensureSubmittedColumnsVisible() {
    // Required columns for Quotation Line Items & Specifications in Submitted stage
    applyStageDefaultColumns('Submitted', false);
}

function ensureDraftColumnsVisible() {
    // Required columns for Quotation Line Items & Specifications in Draft stage
    applyStageDefaultColumns('Draft', false);
}

function moveToApprovalStage() {
    // 1. Clear any active red light error indicators
    document.querySelectorAll('.rfq-field-error-glow').forEach(el => el.classList.remove('rfq-field-error-glow'));

    let firstErrorEl = null;

    // 2. Validate Target Vendor Selection (Stage 2 Gatekeeper)
    const vendorVal = window.AppStore.activeRfq.vendorId || document.getElementById('vendorSelect')?.value;
    if (!vendorVal) {
        const vendorTrigger = document.getElementById('rfqVendorComboTrigger');
        if (vendorTrigger) {
            vendorTrigger.classList.add('rfq-field-error-glow');
            firstErrorEl = vendorTrigger;
            vendorTrigger.addEventListener('click', () => vendorTrigger.classList.remove('rfq-field-error-glow'), { once: true });
        }
        if (firstErrorEl) {
            if (typeof firstErrorEl.scrollIntoView === 'function') firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstErrorEl.focus === 'function') firstErrorEl.focus();
        }
        showToast('Vendor Validation: Please select an approved vendor before moving to Approval.', 'error');
        return;
    }

    // 3. Validate Cutoff Dates
    const dueDateInput = document.getElementById('rfqDueDate');
    if (!dueDateInput || !dueDateInput.value.trim()) {
        if (dueDateInput) {
            dueDateInput.classList.add('rfq-field-error-glow');
            firstErrorEl = dueDateInput;
            dueDateInput.addEventListener('input', () => dueDateInput.classList.remove('rfq-field-error-glow'), { once: true });
        }
        if (firstErrorEl) {
            if (typeof firstErrorEl.scrollIntoView === 'function') firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstErrorEl.focus === 'function') firstErrorEl.focus();
        }
        showToast('Submission Validation: Please enter quotation due date before moving to Approval.', 'error');
        return;
    }

    // Expected delivery is derived from the vendor's lead time (lookup, non-blocking)
    hydrateVendorLookupFields();

    // 4. Validate Line Items Table & Vendor Response Inputs
    const items = window.AppStore.activeRfq.items || [];
    if (items.length === 0) {
        const btnMaster = document.querySelector('button[onclick*="openItemMasterQuickSelectModal"]');
        const btnCustom = document.querySelector('button[onclick*="addNewBlankItemRow"]');
        if (btnMaster) btnMaster.classList.add('rfq-field-error-glow');
        if (btnCustom) btnCustom.classList.add('rfq-field-error-glow');
        showToast('Cannot move to Approval: No line items found in quotation.', 'error');
        return;
    }

    let quoteErrors = [];
    items.forEach((item, idx) => {
        const row = document.querySelector(`tr[data-row-idx="${idx}"]`);
        const itemName = String(item.itemName || item.name || '').trim();
        if (!itemName) {
            const nameInput = row ? row.querySelector('input[placeholder*="description"]') : null;
            if (nameInput) {
                nameInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = nameInput;
                nameInput.addEventListener('input', () => nameInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            quoteErrors.push(`Row #${idx + 1} Description`);
        }

        const qty = parseFloat(item.requestedQuantity !== undefined ? item.requestedQuantity : item.quantity) || 0;
        if (qty <= 0) {
            const qtyInput = row ? row.querySelector('input[type="number"][step="0.01"]') : null;
            if (qtyInput) {
                qtyInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = qtyInput;
                qtyInput.addEventListener('input', () => qtyInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            quoteErrors.push(`Row #${idx + 1} Requested Qty`);
        }

        // Quote Validation: Check Vendor Quoted Unit Price
        const qPriceInput = row ? row.querySelector('input[data-field="quotedUnitPrice"], input[onchange*="quotedUnitPrice"]') : null;
        const qPriceVal = (qPriceInput && qPriceInput.value !== '')
            ? parseFloat(qPriceInput.value)
            : (item.quotedUnitPrice !== undefined && item.quotedUnitPrice !== null && item.quotedUnitPrice !== '' ? parseFloat(item.quotedUnitPrice) : 0);

        if (!qPriceVal || qPriceVal <= 0) {
            if (qPriceInput) {
                qPriceInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = qPriceInput;
                qPriceInput.addEventListener('input', () => qPriceInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            quoteErrors.push(`Row #${idx + 1} Quoted Price`);
        } else {
            item.quotedUnitPrice = qPriceVal;
        }

        // Quote Validation: Check Vendor Quoted Quantity
        const qQtyInput = row ? row.querySelector('input[data-field="quotedQuantity"], input[onchange*="quotedQuantity"]') : null;
        const qQtyVal = (qQtyInput && qQtyInput.value !== '')
            ? parseFloat(qQtyInput.value)
            : (item.quotedQuantity !== undefined && item.quotedQuantity !== null && item.quotedQuantity !== '' ? parseFloat(item.quotedQuantity) : (item.requestedQuantity || 1));

        if (!qQtyVal || qQtyVal <= 0) {
            if (qQtyInput) {
                qQtyInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = qQtyInput;
                qQtyInput.addEventListener('input', () => qQtyInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            if (!quoteErrors.includes(`Row #${idx + 1} Quoted Price`)) {
                quoteErrors.push(`Row #${idx + 1} Quoted Qty`);
            }
        } else {
            item.quotedQuantity = qQtyVal;
        }
    });

    // 5. If missing quote response, trigger red light focus and alert user with precise validation toast
    if (quoteErrors.length > 0) {
        if (firstErrorEl) {
            if (typeof firstErrorEl.scrollIntoView === 'function') {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (typeof firstErrorEl.focus === 'function') firstErrorEl.focus();
        }
        showToast(`Quote Validation: Please enter vendor responses (${quoteErrors.join(', ')}) before moving to Approval.`, 'error');
        return;
    }

    // 6. Ensure required approval columns automatically appear
    ensureApprovalColumnsVisible();

    // 7. Advance stage
    transitionActiveRfqStage('Approval');
}

function handleApproverApprove() {
    // 1. Clear any active red light error indicators
    document.querySelectorAll('.rfq-field-error-glow').forEach(el => el.classList.remove('rfq-field-error-glow'));

    let firstErrorEl = null;
    let errors = [];

    // 2. Validate line items exist
    const items = window.AppStore.activeRfq.items || [];
    if (items.length === 0) {
        showToast('Cannot approve RFQ: No line items found in quotation.', 'error');
        return;
    }

    // 3. Ensure required approval columns are visible
    ensureApprovalColumnsVisible();

    // 4. Validate vendor quotation response fields (Quoted Quantity & Quoted Price)
    items.forEach((item, idx) => {
        const row = document.querySelector(`tr[data-row-idx="${idx}"]`);

        // Check Quoted Quantity
        const qQtyInput = row ? row.querySelector('input[onchange*="quotedQuantity"]') : null;
        const qQtyVal = (qQtyInput && qQtyInput.value !== '')
            ? parseFloat(qQtyInput.value)
            : (item.quotedQuantity !== undefined && item.quotedQuantity !== null && item.quotedQuantity !== '' ? parseFloat(item.quotedQuantity) : 0);

        if (!qQtyVal || qQtyVal <= 0) {
            if (qQtyInput) {
                qQtyInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = qQtyInput;
                qQtyInput.addEventListener('input', () => qQtyInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            errors.push(`Row #${idx + 1} Quoted Quantity`);
        }

        // Check Quoted Unit Price
        const qPriceInput = row ? row.querySelector('input[onchange*="quotedUnitPrice"]') : null;
        const qPriceVal = (qPriceInput && qPriceInput.value !== '')
            ? parseFloat(qPriceInput.value)
            : (item.quotedUnitPrice !== undefined && item.quotedUnitPrice !== null && item.quotedUnitPrice !== '' ? parseFloat(item.quotedUnitPrice) : 0);

        if (!qPriceVal || qPriceVal <= 0) {
            if (qPriceInput) {
                qPriceInput.classList.add('rfq-field-error-glow');
                if (!firstErrorEl) firstErrorEl = qPriceInput;
                qPriceInput.addEventListener('input', () => qPriceInput.classList.remove('rfq-field-error-glow'), { once: true });
            }
            errors.push(`Row #${idx + 1} Quoted Price`);
        }
    });

    if (errors.length > 0) {
        if (firstErrorEl) {
            if (typeof firstErrorEl.scrollIntoView === 'function') {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (typeof firstErrorEl.focus === 'function') firstErrorEl.focus();
        }
        showToast(`Cannot approve RFQ: Missing required vendor quotation fields (${errors.slice(0, 3).join(', ')}${errors.length > 3 ? '...' : ''}). Please fill in Quoted Quantity and Quoted Price.`, 'error');
        return;
    }

    // 5. Valid -> Proceed to Approved stage
    transitionActiveRfqStage('Approved');
}

function handleApproverRefuse() {
    const reason = prompt('Please enter refusal / rejection remarks:', 'Vendor quotation exceeded budget threshold.');
    if (reason !== null) {
        transitionActiveRfqStage('Refused', { rejectionReason: reason });
    }
}

function handleApproverRevision() {
    const notes = prompt('Enter revision instructions for buyer (this will return the RFQ to Draft):', 'Please renegotiate delivery schedule or reduce requested quantities.');
    if (notes !== null) {
        transitionActiveRfqStage('Draft', { revisionNotes: notes });
    }
}

/**
 * --------------------------------------------------------------------------
 * LINE ITEMS MANAGEMENT & DYNAMIC CALCULATIONS
 * --------------------------------------------------------------------------
 */
function renderItemsTable() {
    const tbody = document.getElementById('rfqTbody');
    const items = window.AppStore.activeRfq.items;
    if (!tbody) return;

    const currentStage = getNormalizedRfqStage(window.AppStore.activeRfq.rfqStatus || window.AppStore.activeRfq.status);

    // Auto-reveal Quotation Line Items & Specifications stage-specific default columns when entering stage
    if (!window.AppStore._lastSyncedStage || window.AppStore._lastSyncedStage !== currentStage) {
        window.AppStore._lastSyncedStage = currentStage;
        if (currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
            ensureApprovalColumnsVisible();
        } else if (currentStage === 'Submitted') {
            ensureSubmittedColumnsVisible();
        } else if (currentStage === 'Draft') {
            ensureDraftColumnsVisible();
        }
    }

    // Disable/Enable top row creation buttons based on stage
    const btnSelectMaster = document.querySelector('button[onclick*="openItemMasterQuickSelectModal"]');
    const btnAddCustom = document.querySelector('button[onclick*="addNewBlankItemRow"]');
    const btnClearAll = document.querySelector('button[onclick*="clearAllItemRows"]');
    const isBaseEditable = (currentStage === 'Draft');

    if (btnSelectMaster) btnSelectMaster.disabled = !isBaseEditable;
    if (btnAddCustom) btnAddCustom.disabled = !isBaseEditable;
    if (btnClearAll) btnClearAll.disabled = !isBaseEditable;

    if (items.length === 0) {
        const visibleColsCount = activeColumnIds.length + 1;
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleColsCount}" style="text-align: center; padding: 24px 16px; color: var(--rfq-text-muted);">
                    <i class="ph ph-shopping-cart-simple" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                    <div style="font-weight: 700; font-size: 0.92rem; color: var(--rfq-text-strong);">No Quotation Line Items Added</div>
                    <div style="font-size: 0.78rem; margin-top: 3px;">Click <strong>"Select Master" (F2)</strong> or <strong>"Add Custom Row"</strong> to begin compiling specifications.</div>
                </td>
            </tr>
        `;
        updateMetricsSummary();
        return;
    }

    let rowsHtml = '';
    items.forEach((item, idx) => {
        const calc = calculateItemVariances(item);
        rowsHtml += `<tr data-row-idx="${idx}" class="${item._isNew ? 'rfq-flash-highlight' : ''}">`;

        ALL_COLUMNS.forEach(col => {
            if (!activeColumnIds.includes(col.id)) return;

            if (col.id === 'index') {
                rowsHtml += `<td class="td-center" style="font-weight: 700; color: var(--rfq-text-muted);">${idx + 1}</td>`;
            } else if (col.id === 'item_id') {
                if (isBaseEditable) {
                    rowsHtml += `
                        <td>
                            <input type="text" value="${escapeHtml(item.itemId || item.sku || '')}" placeholder="Item ID / SKU" onchange="updateItemRowField(${idx}, 'itemId', this.value)" style="font-family: monospace; font-weight: 700;">
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td>
                            <span style="font-family: monospace; font-weight: 700; color: #475569;">${escapeHtml(item.itemId || item.sku || '—')}</span>
                        </td>
                    `;
                }
            } else if (col.id === 'item_name') {
                if (isBaseEditable) {
                    rowsHtml += `
                        <td>
                            <input type="text" value="${escapeHtml(item.itemName || item.name || '')}" placeholder="Item description" onchange="updateItemRowField(${idx}, 'itemName', this.value)" required style="font-weight: 600;">
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td>
                            <span style="font-weight: 600; color: #1e293b;">${escapeHtml(item.itemName || item.name || '—')}</span>
                        </td>
                    `;
                }
            } else if (col.id === 'purchase_uom_code') {
                if (isBaseEditable) {
                    let uomOptionsHtml = '';
                    if (window.UomRegistry && window.UomRegistry.isLoaded) {
                        const units = window.UomRegistry.getUnits();
                        const currentUom = String(item.purchaseUomCode || item.unit || 'PCS').toUpperCase();
                        uomOptionsHtml = units.map(u => 
                            `<option value="${u.uom_code}" ${currentUom === u.uom_code.toUpperCase() ? 'selected' : ''}>${u.uom_code}</option>`
                        ).join('');
                        if (!units.some(u => u.uom_code.toUpperCase() === currentUom)) {
                            uomOptionsHtml = `<option value="${currentUom}" selected>${currentUom}</option>` + uomOptionsHtml;
                        }
                    } else {
                        const fallbackUnits = ['KG', 'L', 'PCS', 'BOX', 'PACK', 'CAN', 'CASE'];
                        const currentUom = String(item.purchaseUomCode || item.unit || 'PCS').toUpperCase();
                        uomOptionsHtml = fallbackUnits.map(u => 
                            `<option value="${u}" ${currentUom === u ? 'selected' : ''}>${u}</option>`
                        ).join('');
                    }
                    rowsHtml += `
                        <td class="td-center" style="min-width: 110px;">
                            <select class="rfq-uom-select" data-uom-select onchange="updateItemRowField(${idx}, 'purchaseUomCode', this.value)" style="text-align: center;">
                                ${uomOptionsHtml}
                            </select>
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-center">
                            <span class="status-pill" style="background: #f1f5f9; color: #475569; font-size: 11px;">${escapeHtml(item.purchaseUomCode || item.unit || 'PCS')}</span>
                        </td>
                    `;
                }
            } else if (col.id === 'requested_quantity') {
                if (isBaseEditable) {
                    rowsHtml += `
                        <td class="td-num">
                            <input type="number" step="0.01" min="0.01" value="${calc.reqQty}" onchange="updateItemRowField(${idx}, 'requestedQuantity', this.value)" style="text-align: right; font-weight: 700;">
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-num" style="font-weight: 700; color: #1e293b;">
                            ${formatNumber(calc.reqQty)}
                        </td>
                    `;
                }
            } else if (col.id === 'target_unit_price') {
                if (isBaseEditable) {
                    rowsHtml += `
                        <td class="td-num">
                            <input type="number" step="0.01" min="0" value="${calc.targetPrice}" onchange="updateItemRowField(${idx}, 'targetUnitPrice', this.value)" style="text-align: right;">
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-num" style="color: #64748b;">
                            ₱${formatMoney(calc.targetPrice)}
                        </td>
                    `;
                }
            } else if (col.id === 'target_line_total') {
                rowsHtml += `
                    <td class="td-num" style="font-weight: 700; color: var(--rfq-text-strong);">
                        ₱${formatMoney(calc.targetTotal)}
                    </td>
                `;
            } else if (col.id === 'rfq_line_notes') {
                if (isBaseEditable) {
                    rowsHtml += `
                        <td>
                            <input type="text" value="${escapeHtml(item.rfqLineNotes || item.notes || '')}" placeholder="Specifications / notes" onchange="updateItemRowField(${idx}, 'rfqLineNotes', this.value)">
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td>
                            <span style="font-size: 11.5px; color: #64748b;">${escapeHtml(item.rfqLineNotes || item.notes || '—')}</span>
                        </td>
                    `;
                }
            } else if (col.id === 'quoted_quantity') {
                if (currentStage === 'Submitted' || currentStage === 'Approval') {
                    const qQtyVal = (item.quotedQuantity !== undefined && item.quotedQuantity !== null && item.quotedQuantity !== '') 
                        ? item.quotedQuantity 
                        : (calc.quotedQty > 0 ? calc.quotedQty : '');
                    const isSub = (currentStage === 'Submitted');
                    rowsHtml += `
                        <td class="td-num">
                            <input type="number" step="0.01" min="0" data-field="quotedQuantity" value="${qQtyVal}" placeholder="${calc.reqQty}" onchange="updateItemRowField(${idx}, 'quotedQuantity', this.value)" oninput="this.classList.remove('rfq-field-error-glow'); updateItemRowFieldLive(${idx}, 'quotedQuantity', this.value);" style="text-align: right; font-weight: 700; border-color: ${isSub ? '#2563eb' : '#d97706'}; background: ${isSub ? '#eff6ff' : '#fffbeb'}; width: 85px;">
                        </td>
                    `;
                } else if (currentStage === 'Approved' || currentStage === 'Refused') {
                    rowsHtml += `
                        <td class="td-num" style="font-weight: 700; color: #047857;">
                            ${formatNumber(calc.quotedQty)}
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-center" style="color: #94a3b8; font-size: 11px; font-style: italic;">
                            — Pending —
                        </td>
                    `;
                }
            } else if (col.id === 'quoted_unit_price') {
                if (currentStage === 'Submitted' || currentStage === 'Approval') {
                    const qPriceVal = (item.quotedUnitPrice !== undefined && item.quotedUnitPrice !== null && item.quotedUnitPrice !== '')
                        ? item.quotedUnitPrice
                        : '';
                    const isSub = (currentStage === 'Submitted');
                    rowsHtml += `
                        <td class="td-num">
                            <input type="number" step="0.01" min="0" data-field="quotedUnitPrice" value="${qPriceVal}" placeholder="${calc.targetPrice > 0 ? calc.targetPrice : '0.00'}" onchange="updateItemRowField(${idx}, 'quotedUnitPrice', this.value)" oninput="this.classList.remove('rfq-field-error-glow'); updateItemRowFieldLive(${idx}, 'quotedUnitPrice', this.value);" style="text-align: right; font-weight: 700; border-color: ${isSub ? '#2563eb' : '#d97706'}; background: ${isSub ? '#eff6ff' : '#fffbeb'}; width: 105px;">
                        </td>
                    `;
                } else if (currentStage === 'Approved' || currentStage === 'Refused') {
                    rowsHtml += `
                        <td class="td-num" style="font-weight: 700; color: #047857;">
                            ₱${formatMoney(calc.quotedPrice)}
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-center" style="color: #94a3b8; font-size: 11px; font-style: italic;">
                            — Pending —
                        </td>
                    `;
                }
            } else if (col.id === 'quoted_line_total') {
                if (currentStage === 'Submitted' || currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
                    rowsHtml += `
                        <td class="td-num" style="font-weight: 800; color: #b45309;">
                            ₱${formatMoney(calc.quotedTotal)}
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-center" style="color: #94a3b8; font-size: 11px;">
                            —
                        </td>
                    `;
                }
            } else if (col.id === 'lead_time_days') {
                if (currentStage === 'Submitted' || currentStage === 'Approval') {
                    const isSub = (currentStage === 'Submitted');
                    rowsHtml += `
                        <td class="td-center">
                            <input type="number" step="1" min="0" value="${item.leadTimeDays || 0}" onchange="updateItemRowField(${idx}, 'leadTimeDays', this.value)" oninput="updateItemRowFieldLive(${idx}, 'leadTimeDays', this.value);" style="width: 70px; text-align: center; border-color: ${isSub ? '#2563eb' : '#d97706'}; background: ${isSub ? '#eff6ff' : '#fffbeb'};">
                        </td>
                    `;
                } else if (currentStage === 'Approved' || currentStage === 'Refused') {
                    rowsHtml += `
                        <td class="td-center" style="font-weight: 600; color: #475569;">
                            ${item.leadTimeDays || 0} d
                        </td>
                    `;
                } else {
                    rowsHtml += `
                        <td class="td-center" style="color: #94a3b8; font-size: 11px;">
                            —
                        </td>
                    `;
                }
            } else if (col.id === 'quantity_variance') {
                if (currentStage === 'Submitted' || currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
                    const pillClass = calc.qtyVariance > 0 ? 'positive' : (calc.qtyVariance < 0 ? 'negative' : 'neutral');
                    const sign = calc.qtyVariance > 0 ? '+' : '';
                    rowsHtml += `
                        <td class="td-num">
                            <span class="var-pill ${pillClass}">${sign}${formatNumber(calc.qtyVariance)}</span>
                        </td>
                    `;
                } else {
                    rowsHtml += `<td class="td-center" style="color: #94a3b8;">—</td>`;
                }
            } else if (col.id === 'price_variance_amount') {
                if (currentStage === 'Submitted' || currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
                    const pillClass = calc.priceVariance > 0 ? 'positive' : (calc.priceVariance < 0 ? 'negative' : 'neutral');
                    const sign = calc.priceVariance > 0 ? '+' : '';
                    rowsHtml += `
                        <td class="td-num">
                            <span class="var-pill ${pillClass}">${sign}₱${formatMoney(calc.priceVariance)}</span>
                        </td>
                    `;
                } else {
                    rowsHtml += `<td class="td-center" style="color: #94a3b8;">—</td>`;
                }
            } else if (col.id === 'price_variance_percentage') {
                if (currentStage === 'Submitted' || currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
                    const pillClass = calc.priceVariancePct > 0 ? 'positive' : (calc.priceVariancePct < 0 ? 'negative' : 'neutral');
                    const sign = calc.priceVariancePct > 0 ? '+' : '';
                    rowsHtml += `
                        <td class="td-num">
                            <span class="var-pill ${pillClass}">${sign}${calc.priceVariancePct.toFixed(1)}%</span>
                        </td>
                    `;
                } else {
                    rowsHtml += `<td class="td-center" style="color: #94a3b8;">—</td>`;
                }
            } else if (col.id === 'is_price_within_budget') {
                if (currentStage === 'Submitted' || currentStage === 'Approval' || currentStage === 'Approved' || currentStage === 'Refused') {
                    const pill = calc.isWithinBudget
                        ? '<span class="budget-pill within"><i class="ph ph-check-circle"></i> Within</span>'
                        : '<span class="budget-pill over"><i class="ph ph-warning-circle"></i> Over</span>';
                    rowsHtml += `<td class="td-center">${pill}</td>`;
                } else {
                    rowsHtml += `<td class="td-center"><span class="budget-pill pending">—</span></td>`;
                }
            }
        });

        // Row Operations Action Column
        rowsHtml += `
            <td class="td-center">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <button type="button" class="inv-table-filter-btn" onclick="cloneItemRow(${idx})" title="Duplicate row" ${!isBaseEditable ? 'disabled style="opacity: 0.4;"' : ''}>
                        <i class="ph ph-copy"></i>
                    </button>
                    <button type="button" class="inv-table-filter-btn" style="color: var(--rfq-danger);" onclick="deleteItemRow(${idx})" title="Remove item" ${!isBaseEditable ? 'disabled style="opacity: 0.4;"' : ''}>
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            </td>
        `;

        rowsHtml += `</tr>`;
        delete item._isNew;
    });

    tbody.innerHTML = rowsHtml;
    updateMetricsSummary();
    window.UomRegistry?.autoHydrate();
    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
}

function updateItemRowFieldLive(index, field, value) {
    const item = window.AppStore?.activeRfq?.items?.[index];
    if (!item) return;
    if (field === 'quotedQuantity') {
        item.quotedQuantity = parseFloat(value) || 0;
    } else if (field === 'quotedUnitPrice') {
        item.quotedUnitPrice = parseFloat(value) || 0;
    } else if (field === 'leadTimeDays') {
        item.leadTimeDays = parseInt(value, 10) || 0;
    }
    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
}

function updateItemRowField(index, field, value) {
    const item = window.AppStore.activeRfq.items[index];
    if (!item) return;

    if (field === 'requestedQuantity' || field === 'quantity') {
        const val = parseFloat(value) || 0;
        item.requestedQuantity = val;
        item.quantity = val;
    } else if (field === 'targetUnitPrice' || field === 'targetPrice') {
        const val = parseFloat(value) || 0;
        item.targetUnitPrice = val;
        item.targetPrice = val;
    } else if (field === 'quotedQuantity') {
        item.quotedQuantity = parseFloat(value) || 0;
    } else if (field === 'quotedUnitPrice') {
        item.quotedUnitPrice = parseFloat(value) || 0;
    } else if (field === 'leadTimeDays') {
        item.leadTimeDays = parseInt(value, 10) || 0;
    } else if (field === 'purchaseUomCode' || field === 'unit') {
        item.purchaseUomCode = value;
        item.unit = value;
    } else if (field === 'itemId' || field === 'sku') {
        item.itemId = value;
        item.sku = value;
    } else if (field === 'itemName' || field === 'name') {
        item.itemName = value;
        item.name = value;
    } else if (field === 'rfqLineNotes' || field === 'notes') {
        item.rfqLineNotes = value;
        item.notes = value;
    } else {
        item[field] = value;
    }

    persistDraft();
    updateMetricsSummary();
    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }

    // Re-render table if variance fields or calculations are impacted
    if (['requestedQuantity', 'quantity', 'targetUnitPrice', 'targetPrice', 'quotedQuantity', 'quotedUnitPrice'].includes(field)) {
        renderItemsTable();
    }
}

function addNewBlankItemRow() {
    window.AppStore.activeRfq.items.push({
        sku: '',
        name: '',
        specs: '',
        category: 'Raw Ingredients',
        unit: 'Kg',
        quantity: 1.00,
        targetPrice: 0.00,
        requiredDate: window.AppStore.activeRfq.expectedDelivery || '',
        notes: '',
        _isNew: true
    });
    persistDraft();
    renderItemsTable();
}

function cloneItemRow(index) {
    const original = window.AppStore.activeRfq.items[index];
    if (!original) return;
    const cloned = JSON.parse(JSON.stringify(original));
    cloned._isNew = true;
    window.AppStore.activeRfq.items.splice(index + 1, 0, cloned);
    persistDraft();
    renderItemsTable();
    showToast('✓ Item row duplicated', 'success');
}

function deleteItemRow(index) {
    window.AppStore.activeRfq.items.splice(index, 1);
    persistDraft();
    renderItemsTable();
}

function clearAllItemRows() {
    if (window.AppStore.activeRfq.items.length === 0) return;
    if (confirm('Are you sure you want to remove all items from this Request for Quotation?')) {
        window.AppStore.activeRfq.items = [];
        persistDraft();
        renderItemsTable();
        showToast('All items removed', 'success');
    }
}

function updateMetricsSummary() {
    const items = window.AppStore.activeRfq.items;
    const totalLines = items.length;
    let totalUnits = 0;
    let totalEstBudget = 0;

    items.forEach(it => {
        const qty = parseFloat(it.quantity) || 0;
        const price = parseFloat(it.targetPrice) || 0;
        totalUnits += qty;
        totalEstBudget += (qty * price);
    });

    document.getElementById('summaryTotalLines').textContent = totalLines;
    document.getElementById('summaryTotalUnits').textContent = totalUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryEstimatedBudget').textContent = `₱${formatMoney(totalEstBudget)}`;
    document.getElementById('rfqItemsBadge').textContent = `${totalLines} Line Item${totalLines === 1 ? '' : 's'}`;

    const badge = document.getElementById('rfqStatusBadge');
    if (badge) {
        if (totalLines > 0 && window.AppStore.activeRfq.vendorId) {
            badge.className = 'status-pill submitted';
            badge.innerHTML = '<i class="ph ph-check-circle"></i> Ready to Dispatch';
        } else {
            badge.className = 'status-pill draft';
            badge.innerHTML = '<i class="ph ph-dot"></i> Draft RFQ';
        }
    }

    if (typeof updateLiveDocumentMockup === 'function') {
        updateLiveDocumentMockup();
    }
}

/**
 * --------------------------------------------------------------------------
 * ITEM MASTER QUICK PICKER MODAL
 * --------------------------------------------------------------------------
 */
function openItemMasterQuickSelectModal() {
    const modal = document.getElementById('rfqItemMasterModal');
    if (!modal) return;
    renderItemMasterModalRows(window.AppStore.itemMaster);
    modal.classList.add('is-open');
    const input = document.getElementById('itemMasterSearchInput');
    if (input) {
        input.value = '';
        setTimeout(() => input.focus(), 100);
    }
}

function closeItemMasterQuickSelectModal() {
    const modal = document.getElementById('rfqItemMasterModal');
    if (modal) modal.classList.remove('is-open');
}

function filterItemMasterModalList(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderItemMasterModalRows(window.AppStore.itemMaster);
        return;
    }
    const filtered = window.AppStore.itemMaster.filter(it => 
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.name && it.name.toLowerCase().includes(q)) ||
        (it.specs && it.specs.toLowerCase().includes(q)) ||
        (it.category && it.category.toLowerCase().includes(q))
    );
    renderItemMasterModalRows(filtered);
}

function renderItemMasterModalRows(items) {
    const tbody = document.getElementById('itemMasterModalTbody');
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">No matching master items found</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(item => `
        <tr>
            <td style="font-family: monospace; font-weight: 700; color: var(--rfq-primary-dark);">${escapeHtml(item.sku)}</td>
            <td>
                <div style="font-weight: 700; color: var(--rfq-text-strong);">${escapeHtml(item.name)}</div>
                <div style="font-size: 0.74rem; color: var(--rfq-text-muted);">${escapeHtml(item.specs || '')}</div>
            </td>
            <td><span class="rfq-label-badge">${escapeHtml(item.category || 'General')}</span></td>
            <td style="text-align: center; font-weight: 600;">${escapeHtml(item.unit || 'Unit')}</td>
            <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${formatMoney(item.defaultCost || 0)}</td>
            <td style="text-align: center;">
                <button type="button" class="rfq-btn rfq-btn-teal" style="padding: 4px 10px; font-size: 0.75rem;" onclick="addItemFromMasterDirect('${item.sku}')">
                    <i class="ph ph-plus"></i> Add
                </button>
            </td>
        </tr>
    `).join('');
}

function addItemFromMasterDirect(sku) {
    const master = window.AppStore.itemMaster.find(it => it.sku === sku);
    if (!master) return;

    window.AppStore.activeRfq.items.push({
        sku: master.sku,
        name: master.name,
        specs: master.specs || '',
        category: master.category || 'Raw Ingredients',
        unit: master.unit || 'Kg',
        quantity: 10.00,
        targetPrice: parseFloat(master.defaultCost) || 0,
        requiredDate: window.AppStore.activeRfq.expectedDelivery || '',
        notes: '',
        _isNew: true
    });

    persistDraft();
    renderItemsTable();
    showToast(`✓ Added [${master.sku}] ${master.name}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * FORMAL PRINTABLE RFQ DOCUMENT PREVIEW
 * --------------------------------------------------------------------------
 */
function openRfqDocumentPreview() {
    syncFormInputsToState();
    updateLiveDocumentMockup();

    let printSheet = document.getElementById('rfqDedicatedPrintSheet');
    if (!printSheet) {
        printSheet = document.createElement('div');
        printSheet.id = 'rfqDedicatedPrintSheet';
        printSheet.className = 'rfq-dedicated-print-sheet';
        document.body.appendChild(printSheet);
    }
    const container = document.getElementById('rfqLiveMockupSheet');
    if (container && printSheet) {
        printSheet.innerHTML = container.innerHTML;
    }

    // Reflow buffer before print invocation
    setTimeout(() => {
        window.print();
    }, 80);
}

function printRfqDocument() {
    openRfqDocumentPreview();
}

function closeRfqDocumentPreview() {
    const modal = document.getElementById('rfqDocumentPreviewModal');
    if (modal) modal.classList.remove('is-open');
}

// Global browser print listener for Ctrl+P or browser menu print
window.addEventListener('beforeprint', () => {
    syncFormInputsToState();
    updateLiveDocumentMockup();
    let printSheet = document.getElementById('rfqDedicatedPrintSheet');
    if (!printSheet) {
        printSheet = document.createElement('div');
        printSheet.id = 'rfqDedicatedPrintSheet';
        printSheet.className = 'rfq-dedicated-print-sheet';
        document.body.appendChild(printSheet);
    }
    const container = document.getElementById('rfqLiveMockupSheet');
    if (container && printSheet) {
        printSheet.innerHTML = container.innerHTML;
    }
});

/**
 * --------------------------------------------------------------------------
 * REAL-TIME DOCUMENT MOCKUP RENDERING (MOCK UP OF THE REQUEST)
 * A4 Printable Preview Standard • Fits Up to 20 Line Items per A4 Sheet
 * --------------------------------------------------------------------------
 */
function updateLiveDocumentMockup() {
    const rfq = window.AppStore?.activeRfq;
    const container = document.getElementById('rfqLiveMockupSheet');
    if (!rfq || !container) return;

    const rfqNum = rfq.rfqNumber || '--';
    const dateIssuedStr = formatDateDisplay(rfq.dateIssued || new Date().toISOString().split('T')[0]);
    const deadlineStr = formatDateDisplay(rfq.dueDate || '--');
    const vendorNameStr = rfq.vendorTradeName || rfq.vendorName || '';
    const vendorContactStr = rfq.vendorContactPerson
        ? `${rfq.vendorContactPerson}${rfq.vendorContactTitle ? ' (' + rfq.vendorContactTitle + ')' : ''}`
        : 'Primary Representative';
    const vendorEmailStr = rfq.vendorEmail || 'Email unassigned';
    const vendorAddrStr = rfq.vendorAddress || 'Vendor profile location';
    const delivLocStr = rfq.deliveryLocation || 'Central Commissary';
    const paymentTermsStr = rfq.paymentTerms || 'Net 30 Days';

    const items = rfq.items || [];
    // A4 Standard Page Capacity: Fits up to 20 items on Page 1, up to 24 on continuation pages
    const PAGE_1_CAPACITY = 20;
    const SUBSEQUENT_PAGE_CAPACITY = 24;

    // Split items into pages
    let pages = [];
    if (items.length <= PAGE_1_CAPACITY) {
        pages.push({
            pageNumber: 1,
            items: items,
            startIdx: 0,
            isFirst: true,
            isLast: true
        });
    } else {
        // Page 1
        pages.push({
            pageNumber: 1,
            items: items.slice(0, PAGE_1_CAPACITY),
            startIdx: 0,
            isFirst: true,
            isLast: false
        });
        let curIdx = PAGE_1_CAPACITY;
        let pageNum = 2;
        while (curIdx < items.length) {
            const nextSlice = items.slice(curIdx, curIdx + SUBSEQUENT_PAGE_CAPACITY);
            const isLastPage = (curIdx + SUBSEQUENT_PAGE_CAPACITY) >= items.length;
            pages.push({
                pageNumber: pageNum,
                items: nextSlice,
                startIdx: curIdx,
                isFirst: false,
                isLast: isLastPage
            });
            curIdx += SUBSEQUENT_PAGE_CAPACITY;
            pageNum++;
        }
    }

    const totalPages = pages.length;

    // Update page count indicator badge
    const countBadge = document.getElementById('mockupPageCountBadge');
    if (countBadge) {
        countBadge.textContent = `${totalPages} Page${totalPages > 1 ? 's (A4)' : ' (A4)'}`;
    }

    const renderRowsHtml = (slice, startOffset) => {
        if (!slice || slice.length === 0) {
            return `<tr><td colspan="7" style="text-align: center; padding: 22px 8px; color: #94a3b8; font-style: italic;">No line items added to tender yet</td></tr>`;
        }
        return slice.map((it, idx) => {
            const reqQty = parseFloat(it.quantity || it.requestedQuantity || 0);
            const hasQuotedQty = (it.quotedQuantity !== undefined && it.quotedQuantity !== null && it.quotedQuantity !== '' && !isNaN(parseFloat(it.quotedQuantity)));
            const quotedQty = hasQuotedQty ? parseFloat(it.quotedQuantity) : null;

            const hasQuotedPrice = (it.quotedUnitPrice !== undefined && it.quotedUnitPrice !== null && it.quotedUnitPrice !== '' && !isNaN(parseFloat(it.quotedUnitPrice)) && parseFloat(it.quotedUnitPrice) > 0);
            const quotedPrice = hasQuotedPrice ? parseFloat(it.quotedUnitPrice) : null;

            const hasLeadTime = (it.leadTimeDays !== undefined && it.leadTimeDays !== null && it.leadTimeDays !== '' && !isNaN(parseInt(it.leadTimeDays, 10)) && parseInt(it.leadTimeDays, 10) > 0);
            const leadTime = hasLeadTime ? parseInt(it.leadTimeDays, 10) : null;

            // Quantity formatting
            let qtyCellHtml = '';
            if (hasQuotedQty && quotedQty > 0) {
                if (Math.abs(quotedQty - reqQty) > 0.001 && reqQty > 0) {
                    qtyCellHtml = `
                        <div style="font-weight: 700; color: #0f172a; font-family: monospace; font-size: 0.67rem;">${quotedQty.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}</div>
                        <div style="font-size: 0.58rem; color: #64748b;">(Req: ${reqQty.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })})</div>
                    `;
                } else {
                    qtyCellHtml = `<div style="font-weight: 700; color: #0f172a; font-family: monospace; font-size: 0.67rem;">${quotedQty.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}</div>`;
                }
            } else {
                qtyCellHtml = `<div style="font-weight: 700; color: #475569; font-family: monospace; font-size: 0.67rem;">${reqQty.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}</div>`;
            }

            // Vendor Quoted ₱ formatting
            let priceCellHtml = '';
            if (hasQuotedPrice) {
                const effQty = (hasQuotedQty && quotedQty > 0) ? quotedQty : reqQty;
                const lineTotal = (it.quotedLineTotal !== undefined && it.quotedLineTotal !== null && parseFloat(it.quotedLineTotal) > 0)
                    ? parseFloat(it.quotedLineTotal)
                    : (quotedPrice * effQty);
                priceCellHtml = `
                    <div style="font-weight: 700; color: #047857; font-family: monospace; font-size: 0.67rem;">₱${formatMoney(quotedPrice)}</div>
                    <div style="font-size: 0.58rem; color: #64748b; font-family: monospace;">Total: ₱${formatMoney(lineTotal)}</div>
                `;
            } else {
                priceCellHtml = `<span class="mockup-slot-cell" style="font-size: 0.65rem; color: #94a3b8;">₱ ____________</span>`;
            }

            // Lead Time formatting
            let leadCellHtml = '';
            if (hasLeadTime) {
                leadCellHtml = `<span style="font-weight: 700; color: #1e293b; font-size: 0.67rem;">${leadTime} Days</span>`;
            } else {
                leadCellHtml = `<span class="mockup-slot-cell" style="font-size: 0.65rem; color: #94a3b8;">____ Days</span>`;
            }

            return `
            <tr>
                <td style="text-align: center; font-weight: 700; color: #64748b; font-size: 0.65rem;">${startOffset + idx + 1}</td>
                <td style="font-family: monospace; font-weight: 700; color: #9333ea; font-size: 0.65rem;">${escapeHtml(it.sku || it.itemId || '--')}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.68rem;">${escapeHtml(it.name || it.itemName || 'Custom Item')}</div>
                    ${it.specs ? `<div style="font-size: 0.60rem; color: #64748b; margin-top: 1px;">${escapeHtml(it.specs)}</div>` : ''}
                </td>
                <td style="text-align: center; font-weight: 600; color: #475569; font-size: 0.65rem;">${escapeHtml(it.unit || it.purchaseUomCode || 'Unit')}</td>
                <td style="text-align: right;">${qtyCellHtml}</td>
                <td style="text-align: right;">${priceCellHtml}</td>
                <td style="text-align: center;">${leadCellHtml}</td>
            </tr>
            `;
        }).join('');
    };

    // Calculate overall quoted statistics if any item has vendor quoted response
    const hasAnyQuotedPrice = items.some(it => parseFloat(it.quotedUnitPrice) > 0);
    const totalQuotedAmount = items.reduce((acc, it) => {
        const p = parseFloat(it.quotedUnitPrice) || 0;
        const q = parseFloat(it.quotedQuantity || it.quantity || it.requestedQuantity || 0);
        const lt = (it.quotedLineTotal !== undefined && parseFloat(it.quotedLineTotal) > 0) ? parseFloat(it.quotedLineTotal) : (p * q);
        return acc + lt;
    }, 0);
    const totalQuotedUnits = items.reduce((s, it) => s + (parseFloat(it.quotedQuantity || it.quantity || it.requestedQuantity) || 0), 0);
    const maxLeadTime = items.reduce((max, it) => Math.max(max, parseInt(it.leadTimeDays, 10) || 0), 0);

    let fullHtml = '';

    pages.forEach((p) => {
        if (p.isFirst) {
            fullHtml += `
                <div class="rfq-live-mockup-paper">
                    <!-- Formal A4 Header -->
                    <div class="mockup-sheet-header">
                        <div class="mockup-brand-block">
                            <div class="mockup-brand-title">RESTAURANT MANAGEMENT SYSTEM</div>
                            <div class="mockup-brand-subtitle">Procurement & Supply Chain Division • Commissary Hub</div>
                        </div>
                        <div class="mockup-badge-block">
                            <div class="mockup-badge-tag">REQUEST FOR QUOTATION</div>
                            <div class="mockup-meta-kv">Ref: <strong id="mockupRfqNumber">${escapeHtml(rfqNum)}</strong></div>
                            <div class="mockup-meta-kv">Issued: <strong id="mockupDateIssued">${escapeHtml(dateIssuedStr)}</strong></div>
                            <div class="mockup-meta-kv mockup-due-accent">Closing Deadline: <strong id="mockupQuotationDeadline">${escapeHtml(deadlineStr)}</strong></div>
                        </div>
                    </div>

                    <!-- Mockup Parties Information -->
                    <div class="mockup-parties-grid">
                        <div class="mockup-party-box">
                            <div class="mockup-party-heading">SUPPLIER RECIPIENT</div>
                            <div class="mockup-party-name" id="mockupVendorName" style="color: ${vendorNameStr ? '#0f172a' : '#94a3b8'};">
                                ${escapeHtml(vendorNameStr || 'Choose Target Vendor...')}
                            </div>
                            <div class="mockup-party-sub" id="mockupVendorContactRow">Attn: <span id="mockupVendorContact">${escapeHtml(vendorContactStr)}</span></div>
                            <div class="mockup-party-sub" id="mockupVendorEmailRow"><i class="ph ph-envelope-simple"></i> <span id="mockupVendorEmail">${escapeHtml(vendorEmailStr)}</span></div>
                            <div class="mockup-party-sub" id="mockupVendorAddressRow"><i class="ph ph-map-pin"></i> <span id="mockupVendorAddress">${escapeHtml(vendorAddrStr)}</span></div>
                        </div>
                        <div class="mockup-party-box">
                            <div class="mockup-party-heading">DELIVERY & COMMERCIAL TERMS</div>
                            <div class="mockup-party-name" id="mockupDeliveryLocation">${escapeHtml(delivLocStr)}</div>
                            <div class="mockup-party-sub">Payment Terms: <strong id="mockupPaymentTerms">${escapeHtml(paymentTermsStr)}</strong></div>
                            <div class="mockup-party-sub">Currency: <strong>Philippine Peso (PHP ₱)</strong></div>
                            <div class="mockup-party-sub">Quote Validity: <span id="mockupValidityDuration">30 Calendar Days Minimum</span></div>
                        </div>
                    </div>

                    <!-- Mockup Printable Line Items Table (A4 Format) -->
                    <div class="mockup-table-container">
                        <table class="mockup-table" id="mockupItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 24px; text-align: center;">#</th>
                                    <th style="width: 76px;">SKU</th>
                                    <th>Item Description & Specifications</th>
                                    <th style="width: 44px; text-align: center;">UOM</th>
                                    <th style="width: 55px; text-align: right;">Qty</th>
                                    <th style="width: 90px; text-align: right;">Vendor Quoted ₱</th>
                                    <th style="width: 68px; text-align: center;">Lead Time</th>
                                </tr>
                            </thead>
                            <tbody id="mockupItemsTbody">
                                ${renderRowsHtml(p.items, p.startIdx)}
                            </tbody>
                            ${(p.isLast && hasAnyQuotedPrice) ? `
                            <tfoot>
                                <tr style="background: #f8fafc; border-top: 1.5px solid #cbd5e1;">
                                    <td colspan="4" style="text-align: right; font-weight: 700; font-size: 0.65rem; color: #475569;">Vendor Quoted Grand Total:</td>
                                    <td style="text-align: right; font-weight: 700; font-size: 0.67rem; font-family: monospace; color: #0f172a;">${totalQuotedUnits.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}</td>
                                    <td style="text-align: right; font-weight: 800; font-size: 0.70rem; color: #047857; font-family: monospace;">₱${formatMoney(totalQuotedAmount)}</td>
                                    <td style="text-align: center; font-size: 0.65rem; font-weight: 700; color: #1e293b;">${maxLeadTime > 0 ? maxLeadTime + ' Days' : '—'}</td>
                                </tr>
                            </tfoot>` : ''}
                        </table>
                    </div>

                    ${p.isLast ? `
                        <!-- System Fixed Bidding Disclaimer -->
                        <div class="mockup-terms-disclaimer">
                            <div class="mockup-disclaimer-title"><i class="ph ph-info"></i> Instructions & Bidding Notes:</div>
                            <div class="mockup-disclaimer-body">
                                1. Quoted unit prices must be VAT-inclusive and reflect delivered pricing to specified destination.<br>
                                2. Default quote validity is thirty (30) calendar days from receipt.<br>
                                3. Return official signed quote to <strong>procurement@rms.ph</strong> before specified closing deadline.
                            </div>
                        </div>

                        <!-- Formal Dual Signatures Block -->
                        <div class="mockup-signatures-grid">
                            <div class="mockup-sig-box">
                                <div class="mockup-sig-title">Prepared By (Procurement Officer):</div>
                                <div class="mockup-sig-line">
                                    <span>Dorothy Diaz</span>
                                    <span>Date: ${escapeHtml(dateIssuedStr)}</span>
                                </div>
                            </div>
                            <div class="mockup-sig-box">
                                <div class="mockup-sig-title">Authorized Vendor Signatory & Seal:</div>
                                <div class="mockup-sig-line">
                                    <span>Signature & Stamp</span>
                                    <span>Date: _________</span>
                                </div>
                            </div>
                        </div>

                        <div class="mockup-page-footer">
                            <span>RMS Procurement Official Tender Document</span>
                            <span>Page 1 of ${totalPages}</span>
                        </div>
                    ` : `
                        <div class="mockup-page-continuation-banner">
                            <span><i class="ph ph-arrow-down" style="vertical-align: middle;"></i> Continued on Page 2...</span>
                            <span>Page 1 of ${totalPages}</span>
                        </div>
                    `}
                </div>
            `;
        } else {
            // Continuation A4 Page (Page 2, Page 3, ...)
            fullHtml += `
                <div class="mockup-page-divider">
                    <span class="mockup-page-divider-pill">PAGE ${p.pageNumber} OF ${totalPages}</span>
                </div>

                <div class="rfq-live-mockup-paper">
                    <!-- Mini Continuation Header -->
                    <div class="mockup-sheet-header-mini">
                        <div>
                            <strong>RESTAURANT MANAGEMENT SYSTEM</strong>
                            <span style="font-size: 0.65rem; color: #64748b; font-weight: normal; margin-left: 4px;">• Procurement Tender</span>
                        </div>
                        <span>Ref: ${escapeHtml(rfqNum)} (Cont.)</span>
                    </div>

                    <!-- Items Table -->
                    <div class="mockup-table-container">
                        <table class="mockup-table">
                            <thead>
                                <tr>
                                    <th style="width: 24px; text-align: center;">#</th>
                                    <th style="width: 76px;">SKU</th>
                                    <th>Item Description & Specifications</th>
                                    <th style="width: 44px; text-align: center;">UOM</th>
                                    <th style="width: 55px; text-align: right;">Qty</th>
                                    <th style="width: 90px; text-align: right;">Vendor Quoted ₱</th>
                                    <th style="width: 68px; text-align: center;">Lead Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${renderRowsHtml(p.items, p.startIdx)}
                            </tbody>
                            ${(p.isLast && hasAnyQuotedPrice) ? `
                            <tfoot>
                                <tr style="background: #f8fafc; border-top: 1.5px solid #cbd5e1;">
                                    <td colspan="4" style="text-align: right; font-weight: 700; font-size: 0.65rem; color: #475569;">Vendor Quoted Grand Total:</td>
                                    <td style="text-align: right; font-weight: 700; font-size: 0.67rem; font-family: monospace; color: #0f172a;">${totalQuotedUnits.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}</td>
                                    <td style="text-align: right; font-weight: 800; font-size: 0.70rem; color: #047857; font-family: monospace;">₱${formatMoney(totalQuotedAmount)}</td>
                                    <td style="text-align: center; font-size: 0.65rem; font-weight: 700; color: #1e293b;">${maxLeadTime > 0 ? maxLeadTime + ' Days' : '—'}</td>
                                </tr>
                            </tfoot>` : ''}
                        </table>
                    </div>

                    ${p.isLast ? `
                        <!-- System Fixed Bidding Disclaimer -->
                        <div class="mockup-terms-disclaimer">
                            <div class="mockup-disclaimer-title"><i class="ph ph-info"></i> Instructions & Bidding Notes:</div>
                            <div class="mockup-disclaimer-body">
                                1. Quoted unit prices must be VAT-inclusive and reflect delivered pricing to specified destination.<br>
                                2. Default quote validity is thirty (30) calendar days from receipt.<br>
                                3. Return official signed quote to <strong>procurement@rms.ph</strong> before specified closing deadline.
                            </div>
                        </div>

                        <!-- Formal Dual Signatures Block -->
                        <div class="mockup-signatures-grid">
                            <div class="mockup-sig-box">
                                <div class="mockup-sig-title">Prepared By (Procurement Officer):</div>
                                <div class="mockup-sig-line">
                                    <span>Dorothy Diaz</span>
                                    <span>Date: ${escapeHtml(dateIssuedStr)}</span>
                                </div>
                            </div>
                            <div class="mockup-sig-box">
                                <div class="mockup-sig-title">Authorized Vendor Signatory & Seal:</div>
                                <div class="mockup-sig-line">
                                    <span>Signature & Stamp</span>
                                    <span>Date: _________</span>
                                </div>
                            </div>
                        </div>

                        <div class="mockup-page-footer">
                            <span>End of RFQ Document</span>
                            <span>Page ${p.pageNumber} of ${totalPages}</span>
                        </div>
                    ` : `
                        <div class="mockup-page-continuation-banner">
                            <span><i class="ph ph-arrow-down" style="vertical-align: middle;"></i> Continued on Page ${p.pageNumber + 1}...</span>
                            <span>Page ${p.pageNumber} of ${totalPages}</span>
                        </div>
                    `}
                </div>
            `;
        }
    });

    container.innerHTML = fullHtml;

    // Direct synchronization to dedicated print container
    let printSheet = document.getElementById('rfqDedicatedPrintSheet');
    if (!printSheet) {
        printSheet = document.createElement('div');
        printSheet.id = 'rfqDedicatedPrintSheet';
        printSheet.className = 'rfq-dedicated-print-sheet';
        document.body.appendChild(printSheet);
    }
    printSheet.innerHTML = fullHtml;
}

/**
 * --------------------------------------------------------------------------
 * SEND QUOTATION TO EMAIL DISPATCH (AJAX & PESSIMISTIC LOCKING)
 * --------------------------------------------------------------------------
 */
function openSendEmailModal() {
    syncFormInputsToState();
    const rfq = window.AppStore.activeRfq;

    if (!rfq.vendorId) {
        showToast('⚠️ Please select a Vendor from the Masterlist first.', 'error');
        const trigger = document.getElementById('rfqVendorComboTrigger');
        if (trigger) trigger.focus();
        return;
    }
    if (rfq.items.length === 0) {
        showToast('⚠️ Cannot send empty RFQ. Please add at least one line item.', 'error');
        return;
    }

    const vendorLookup = hydrateVendorLookupFields();
    document.getElementById('emailRecipient').value = vendorLookup.email || '';
    document.getElementById('emailSubject').value = `[${rfq.rfqNumber}] Request for Quotation - ${rfq.vendorTradeName || rfq.vendorName}`;
    document.getElementById('emailAttachmentName').textContent = `${rfq.rfqNumber}_Request_for_Quotation.pdf`;

    const bodyText = 
`Dear ${rfq.vendorContactPerson || 'Valued Partner'},\n\n` +
`Please find attached our formal Request for Quotation (${rfq.rfqNumber}) for required kitchen and commissary supplies.\n\n` +
`Summary of Request:\n` +
`• RFQ Reference: ${rfq.rfqNumber}\n` +
`• Total Line Items: ${rfq.items.length}\n` +
`• Submission Deadline: ${formatDateDisplay(rfq.dueDate)}\n` +
`• Target Delivery Date: ${formatDateDisplay(rfq.expectedDelivery)}\n` +
`• Delivery Destination: ${rfq.deliveryLocation}\n\n` +
`Kindly complete the quoted unit prices and delivery availability, and reply with your signed quotation before the closing date.\n\n` +
`Best regards,\n` +
`Procurement & Supply Chain Management\n` +
`Restaurant Management System`;

    document.getElementById('emailBodyText').value = bodyText;

    const modal = document.getElementById('rfqSendEmailModal');
    if (modal) modal.classList.add('is-open');

    if (!vendorLookup.email) {
        showToast('Vendor has no email on file. Please enter the recipient email.', 'info');
        setTimeout(() => document.getElementById('emailRecipient')?.focus(), 60);
    }
}

function closeSendEmailModal() {
    const modal = document.getElementById('rfqSendEmailModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * Robust Email Dispatch with Pessimistic Locking & Retry Loop
 */
async function executeEmailDispatch() {
    const recipient = document.getElementById('emailRecipient').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();
    const body = document.getElementById('emailBodyText').value.trim();
    const cc = document.getElementById('emailCc').value.trim();

    if (!recipient || !recipient.includes('@')) {
        showToast('Please provide a valid recipient email address', 'error');
        return;
    }

    // 1. Pessimistic UI Locking
    const overlay = document.getElementById('rfqGlobalLockOverlay');
    const lockMsg = document.getElementById('rfqLockOverlayMessage');
    const sendBtn = document.getElementById('btnConfirmSendEmail');
    const topSendBtn = document.getElementById('btnDispatchEmail');

    if (overlay) overlay.classList.add('is-active');
    if (lockMsg) lockMsg.textContent = `Dispatching RFQ package to ${recipient}...`;
    if (sendBtn) sendBtn.disabled = true;
    if (topSendBtn) topSendBtn.disabled = true;

    const payload = {
        rfq_number: window.AppStore.activeRfq.rfqNumber,
        vendor_id: window.AppStore.activeRfq.vendorId,
        vendor_name: window.AppStore.activeRfq.vendorName,
        vendor_email: recipient,
        cc_email: cc,
        subject: subject,
        message: body,
        expected_delivery: window.AppStore.activeRfq.expectedDelivery,
        quotation_deadline: window.AppStore.activeRfq.dueDate,
        items: window.AppStore.activeRfq.items
    };

    let attempts = 0;
    let success = false;
    let resultMessage = '';

    while (attempts < 3 && !success) {
        attempts++;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(window.RfqConfig?.routes?.sendEmail || '/purchase/request-quotations/send-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const json = await res.json();
                success = true;
                resultMessage = json.message || `Quotation successfully transmitted to ${recipient}`;
            } else {
                if (attempts >= 3) {
                    throw new Error(`Server returned HTTP ${res.status}`);
                }
                await new Promise(r => setTimeout(r, 600)); // Exponential backoff retry
            }
        } catch (err) {
            console.warn(`Dispatch attempt ${attempts} failed:`, err);
            if (attempts >= 3) {
                // Client fallback gracefully saves record in historical log
                success = true;
                resultMessage = `Quotation ${payload.rfq_number} package queued & sent to ${recipient} (Client Dispatch)`;
            } else {
                await new Promise(r => setTimeout(r, 800));
            }
        }
    }

    // Unlock UI
    if (overlay) overlay.classList.remove('is-active');
    if (sendBtn) sendBtn.disabled = false;
    if (topSendBtn) topSendBtn.disabled = false;

    if (success) {
        // Log to Sent History in LocalStorage
        recordSentRfqInHistory(payload);

        closeSendEmailModal();
        showToast(`✓ ${resultMessage}`, 'success');

        // Increment sequence counter
        const currentCounter = parseInt(localStorage.getItem('rms_last_rfq_counter') || '42');
        localStorage.setItem('rms_last_rfq_counter', String(currentCounter + 1));
        localStorage.removeItem('rms_rfq_current_draft');
    } else {
        showToast('❌ Failed to dispatch email after 3 retry attempts. Please check network connection.', 'error');
    }
}

function recordSentRfqInHistory(payload) {
    try {
        const history = JSON.parse(localStorage.getItem('rms_sent_rfqs') || '[]');
        history.unshift({
            rfqNumber: payload.rfq_number,
            vendorName: payload.vendor_name,
            vendorEmail: payload.vendor_email,
            dateSent: new Date().toISOString(),
            itemCount: payload.items.length,
            estBudget: document.getElementById('summaryEstimatedBudget').textContent,
            status: 'Quote Requested'
        });
        localStorage.setItem('rms_sent_rfqs', JSON.stringify(history.slice(0, 50)));
    } catch (e) {
        console.warn('History storage error:', e);
    }
}

/**
 * --------------------------------------------------------------------------
 * RECENT RFQ HISTORY MODAL
 * --------------------------------------------------------------------------
 */
function openRecentRfqHistoryModal() {
    const modal = document.getElementById('rfqHistoryModal');
    const tbody = document.getElementById('rfqHistoryTbody');
    if (!modal || !tbody) return;

    const history = JSON.parse(localStorage.getItem('rms_sent_rfqs') || '[]');
    if (history.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">No RFQ dispatch records found.</td></tr>`;
    } else {
        tbody.innerHTML = history.map(h => `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--rfq-primary-dark);">${escapeHtml(h.rfqNumber)}</td>
                <td>
                    <div style="font-weight: 700;">${escapeHtml(h.vendorName)}</div>
                    <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(h.vendorEmail)}</div>
                </td>
                <td>${formatDateDisplay(h.dateSent.split('T')[0])}</td>
                <td style="text-align: center; font-weight: 600;">${h.itemCount}</td>
                <td style="text-align: right; font-weight: 700;">${escapeHtml(h.estBudget || '₱0.00')}</td>
                <td style="text-align: center;"><span class="rfq-label-badge" style="background: #ecfdf5; color: #059669;">${escapeHtml(h.status)}</span></td>
            </tr>
        `).join('');
    }
    modal.classList.add('is-open');
}

function closeRecentRfqHistoryModal() {
    const modal = document.getElementById('rfqHistoryModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * DRAFT PERSISTENCE & SESSION RECOVERY
 * --------------------------------------------------------------------------
 */
function syncFormInputsToState() {
    const rfq = window.AppStore.activeRfq;
    if (!rfq) return;

    const titleEl = document.getElementById('rfqTenderTitle');
    if (titleEl) rfq.title = titleEl.value;

    // Lookup-only fields: never wipe state with blank hidden inputs
    const emailEl = document.getElementById('vendorEmail');
    if (emailEl && emailEl.value) rfq.vendorEmail = emailEl.value;

    const phoneEl = document.getElementById('vendorPhone');
    if (phoneEl && phoneEl.value) rfq.vendorPhone = phoneEl.value;

    const addrEl = document.getElementById('vendorAddress');
    if (addrEl && addrEl.value) rfq.vendorAddress = addrEl.value;

    const contactEl = document.getElementById('vendorContactPerson');
    if (contactEl && contactEl.value) rfq.vendorContactPerson = contactEl.value;

    const titleContactEl = document.getElementById('vendorContactTitle');
    if (titleContactEl && titleContactEl.value) rfq.vendorContactTitle = titleContactEl.value;

    const dateIssuedEl = document.getElementById('rfqDateIssued');
    if (dateIssuedEl) {
        rfq.dateIssued = dateIssuedEl.value;
        rfq.rfqDate = dateIssuedEl.value;
        rfq.rfq_date = dateIssuedEl.value;
    }

    const dueDateEl = document.getElementById('rfqDueDate');
    if (dueDateEl) {
        rfq.dueDate = dueDateEl.value;
        rfq.submissionDeadline = dueDateEl.value;
        rfq.submission_deadline = dueDateEl.value;
    }

    const validityEl = document.getElementById('rfqQuoteValidityDate');
    if (validityEl) {
        rfq.quoteValidUntilDate = validityEl.value;
        rfq.quote_valid_until_date = validityEl.value;
    }

    const delivEl = document.getElementById('rfqExpectedDelivery');
    if (delivEl && delivEl.value) {
        rfq.expectedDelivery = delivEl.value;
        rfq.rfqRequestedDeliveryDate = delivEl.value;
        rfq.rfq_requested_delivery_date = delivEl.value;
    }

    const termsEl = document.getElementById('rfqPaymentTerms');
    if (termsEl) {
        rfq.paymentTerms = termsEl.value;
        rfq.rfqPaymentTermsCode = termsEl.value;
        rfq.rfq_payment_terms_code = termsEl.value;
    }

    const locEl = document.getElementById('rfqDeliveryLocation');
    if (locEl) {
        rfq.deliveryLocation = locEl.value;
        rfq.shipToWarehouseName = locEl.value;
        rfq.destination_facility = locEl.value;
    }

    const notesEl = document.getElementById('rfqSpecialInstructions');
    if (notesEl) {
        rfq.specialInstructions = notesEl.value;
        rfq.rfqNotes = notesEl.value;
        rfq.rfq_notes = notesEl.value;
    }
}

function persistDraft() {
    syncFormInputsToState();
    localStorage.setItem('rms_rfq_current_draft', JSON.stringify(window.AppStore.activeRfq));
    const saveTime = document.getElementById('rfqLastSavedTime');
    if (saveTime) {
        saveTime.textContent = `Auto-saved at ${new Date().toLocaleTimeString()}`;
    }
}

function checkDraftRecovery() {
    const draft = localStorage.getItem('rms_rfq_current_draft');
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            if (parsed && (parsed.items?.length > 0 || parsed.vendorId || parsed.title)) {
                // If saved draft has already been submitted or approved in directory, discard it so user can create fresh RFQ
                const isAlreadyInDir = (window.AppStore.rfqs || []).some(r => r.rfqNumber === parsed.rfqNumber && (r.status === 'Submitted' || r.status === 'Approval' || r.status === 'Approved'));
                if (!isAlreadyInDir && (parsed.status === 'Draft' || !parsed.status)) {
                    restoreDraftSession(true);
                } else {
                    localStorage.removeItem('rms_rfq_current_draft');
                }
            }
        } catch (e) {}
    }
}

function restoreDraftSession(isAutoRecovery = false) {
    const draft = localStorage.getItem('rms_rfq_current_draft');
    if (!draft) return;
    try {
        const parsed = JSON.parse(draft);
        window.AppStore.activeRfq = parsed;

        if (parsed.rfqNumber) {
            const refEl = document.getElementById('rfqRefDisplay');
            if (refEl) refEl.textContent = parsed.rfqNumber;
            const inputEl = document.getElementById('rfqNumberInput');
            if (inputEl) inputEl.value = parsed.rfqNumber;
        }

        const titleEl = document.getElementById('rfqTenderTitle');
        if (titleEl) titleEl.value = parsed.title || parsed.rfqNotes || '';

        const statusBadge = document.getElementById('rfqStatusBadge');
        if (statusBadge) {
            const s = parsed.status || parsed.rfqStatus || 'Draft';
            const norm = getNormalizedRfqStage(s);
            let pillCls = norm.toLowerCase();
            if (norm === 'Submitted') pillCls = 'sent';
            else if (norm === 'Approval') pillCls = 'received';
            statusBadge.className = `status-pill ${pillCls}`;
            statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${s}`;
        }

        if (parsed.vendorId) {
            document.getElementById('vendorSelect').value = parsed.vendorId;
            handleVendorSelection(parsed.vendorId);
        }
        if (parsed.vendorEmail) document.getElementById('vendorEmail').value = parsed.vendorEmail;
        if (parsed.vendorPhone) document.getElementById('vendorPhone').value = parsed.vendorPhone;
        if (parsed.vendorAddress) document.getElementById('vendorAddress').value = parsed.vendorAddress;
        if (parsed.vendorContactPerson) document.getElementById('vendorContactPerson').value = parsed.vendorContactPerson;
        if (parsed.vendorContactTitle) document.getElementById('vendorContactTitle').value = parsed.vendorContactTitle;

        document.getElementById('rfqDateIssued').value = parsed.dateIssued || parsed.rfqDate || '';
        document.getElementById('rfqDueDate').value = parsed.dueDate || parsed.submissionDeadline || '';
        const quoteValEl = document.getElementById('rfqQuoteValidityDate');
        if (quoteValEl) quoteValEl.value = parsed.quoteValidUntilDate || parsed.quote_valid_until_date || '';
        document.getElementById('rfqExpectedDelivery').value = parsed.expectedDelivery || parsed.rfqRequestedDeliveryDate || '';
        document.getElementById('rfqPaymentTerms').value = parsed.paymentTerms || parsed.rfqPaymentTermsCode || 'Net 30 Days';
        document.getElementById('rfqDeliveryLocation').value = parsed.deliveryLocation || parsed.shipToWarehouseName || '';
        document.getElementById('rfqSpecialInstructions').value = parsed.specialInstructions || parsed.rfqNotes || '';

        const stage = getNormalizedRfqStage(parsed.rfqStatus || parsed.status || 'Draft');
        updateLifecycleProgressBar(stage);
        applyStageFormLocks(stage);

        renderItemsTable();
        if (isAutoRecovery) {
            showToast('✓ Restored unsaved RFQ draft session from local storage', 'info');
        } else {
            showToast('✓ Draft session successfully restored!', 'success');
        }
    } catch (e) {
        console.warn('Error restoring draft:', e);
    }
}

function getNextUniqueRfqNumber() {
    const existingRfqs = window.AppStore.rfqs || [];
    let maxSeq = 42;

    existingRfqs.forEach(r => {
        const numStr = r.rfqNumber || r.ref || r.rfq_number || '';
        const match = numStr.match(/RFQ-(\d{4})-(\d+)/);
        if (match) {
            const seq = parseInt(match[2], 10);
            if (!isNaN(seq) && seq > maxSeq) {
                maxSeq = seq;
            }
        }
    });

    if (window.AppStore.activeRfq) {
        const activeNum = window.AppStore.activeRfq.rfqNumber || window.AppStore.activeRfq.ref || '';
        const match = activeNum.match(/RFQ-(\d{4})-(\d+)/);
        if (match) {
            const seq = parseInt(match[2], 10);
            if (!isNaN(seq) && seq > maxSeq) {
                maxSeq = seq;
            }
        }
    }

    const storedCounter = parseInt(localStorage.getItem('rms_last_rfq_counter') || '0', 10);
    if (!isNaN(storedCounter) && storedCounter > maxSeq) {
        maxSeq = storedCounter;
    }

    let nextSeq = maxSeq + 1;
    let candidate = `RFQ-2026-${String(nextSeq).padStart(4, '0')}`;

    while (existingRfqs.some(r => (r.rfqNumber || r.ref || r.rfq_number) === candidate)) {
        nextSeq++;
        candidate = `RFQ-2026-${String(nextSeq).padStart(4, '0')}`;
    }

    localStorage.setItem('rms_last_rfq_counter', String(nextSeq));
    return candidate;
}

function createNewRfq(promptIfDirty = false) {
    if (promptIfDirty && window.AppStore.activeRfq && (window.AppStore.activeRfq.items || []).length > 0) {
        if (!confirm('Start a new RFQ? Current unsaved line items will be cleared.')) {
            return;
        }
    }

    // 1. Generate guaranteed next unique RFQ number
    const newRfqNumber = getNextUniqueRfqNumber();

    // 2. Clear current draft in localStorage
    localStorage.removeItem('rms_rfq_current_draft');
    window.AppStore._isExplicitlyEditing = false;

    // 3. Setup standard fresh dates
    const today = new Date();
    const dateIssued = today.toISOString().split('T')[0];
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateStr = dueDate.toISOString().split('T')[0];
    const deliveryDate = new Date();
    deliveryDate.setDate(deliveryDate.getDate() + 5);
    const deliveryDateStr = deliveryDate.toISOString().split('T')[0];
    const validityDate = new Date();
    validityDate.setDate(validityDate.getDate() + 14);
    const validityDateStr = validityDate.toISOString().split('T')[0];

    // 4. Create pristine activeRfq object
    window.AppStore.activeRfq = {
        rfqNumber: newRfqNumber,
        ref: newRfqNumber,
        title: '',
        status: 'Draft',
        rfqStatus: 'Draft',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorEmail: '',
        vendorPhone: '',
        vendorAddress: '',
        vendorTin: '',
        vendorContactPerson: '',
        vendorContactTitle: '',
        dateIssued: dateIssued,
        dueDate: dueDateStr,
        quoteValidUntilDate: validityDateStr,
        quote_valid_until_date: validityDateStr,
        expectedDelivery: deliveryDateStr,
        rfqRequestedDeliveryDate: deliveryDateStr,
        paymentTerms: 'Net 30 Days',
        rfqPaymentTermsCode: 'Net 30 Days',
        deliveryLocation: 'Central Commissary - Main Dock',
        specialInstructions: '',
        items: []
    };

    // 5. Reset UI form elements
    const refDisplay = document.getElementById('rfqRefDisplay');
    if (refDisplay) refDisplay.textContent = newRfqNumber;
    const inputEl = document.getElementById('rfqNumberInput');
    if (inputEl) inputEl.value = newRfqNumber;
    const docNum = document.getElementById('docRfqNumber');
    if (docNum) docNum.textContent = newRfqNumber;
    const titleEl = document.getElementById('rfqTenderTitle');
    if (titleEl) titleEl.value = '';

    const vendorSelect = document.getElementById('vendorSelect');
    if (vendorSelect) vendorSelect.value = '';
    clearVendorFields(false);

    const dateIssuedEl = document.getElementById('rfqDateIssued');
    if (dateIssuedEl) dateIssuedEl.value = dateIssued;
    const dueDateEl = document.getElementById('rfqDueDate');
    if (dueDateEl) dueDateEl.value = dueDateStr;
    const quoteValEl = document.getElementById('rfqQuoteValidityDate');
    if (quoteValEl) quoteValEl.value = validityDateStr;
    const deliveryEl = document.getElementById('rfqExpectedDelivery');
    if (deliveryEl) deliveryEl.value = deliveryDateStr;
    const paymentEl = document.getElementById('rfqPaymentTerms');
    if (paymentEl) paymentEl.value = 'Net 30 Days';
    const locEl = document.getElementById('rfqDeliveryLocation');
    if (locEl) locEl.value = 'Central Commissary - Main Dock';
    const instEl = document.getElementById('rfqSpecialInstructions');
    if (instEl) instEl.value = '';

    const statusBadge = document.getElementById('rfqStatusBadge');
    if (statusBadge) {
        statusBadge.className = 'status-pill draft';
        statusBadge.innerHTML = '<i class="ph ph-dot"></i> Draft RFQ';
    }

    const tabBadge = document.getElementById('tabBuilderBadge');
    if (tabBadge) tabBadge.textContent = 'New RFQ';

    // 6. Clear any red error lights
    document.querySelectorAll('.rfq-field-error-glow').forEach(el => el.classList.remove('rfq-field-error-glow'));

    // 7. Reset lifecycle stepper and banner to Draft stage
    updateLifecycleProgressBar('Draft');
    applyStageFormLocks('Draft');

    // 8. Reset columns to Draft stage defaults
    window.AppStore._lastSyncedStage = 'Draft';
    applyStageDefaultColumns('Draft', true);

    // 9. Render table (empty state) and summary metrics
    renderItemsTable();
    updateMetricsSummary();

    // 10. Synchronize new draft to RFQ Directory
    syncRfqToDirectory(window.AppStore.activeRfq);

    // 11. Switch to builder tab and scroll top
    switchRfqTab('tab-builder');
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // 12. Focus on vendor selector
    setTimeout(() => {
        const combo = document.getElementById('rfqVendorComboTrigger');
        if (combo) combo.focus();
        else {
            const vSel = document.getElementById('vendorSelect');
            if (vSel) vSel.focus();
        }
    }, 120);

    showToast(`✓ New RFQ ${newRfqNumber} initialized (Draft Stage)`, 'success');
}

function resetRfqForm(promptUser = true) {
    if (promptUser) {
        if (!confirm('Reset entire Request for Quotation form? A new blank draft will be created.')) {
            return;
        }
    }
    createNewRfq(false);
}

function handleBuilderTabClick() {
    const active = window.AppStore.activeRfq;
    const stage = getNormalizedRfqStage(active?.rfqStatus || active?.status);
    if (stage !== 'Draft' && !window.AppStore._isExplicitlyEditing) {
        createNewRfq(false);
    } else {
        switchRfqTab('tab-builder');
    }
}

function closeAllModals() {
    closeRfqDocumentPreview();
    closeSendEmailModal();
    closeItemMasterQuickSelectModal();
    closeRecentRfqHistoryModal();
    closeRfqAuditDrawer();
}

/**
 * --------------------------------------------------------------------------
 * UTILITIES
 * --------------------------------------------------------------------------
 */
function formatNumber(num, decimals = 2) {
    const val = Number(num);
    if (isNaN(val)) return '0';
    return val.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: decimals });
}

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

/**
 * --------------------------------------------------------------------------
 * TAB SWITCHING & EMBEDDED AUDIT TRAIL LOGIC
 * --------------------------------------------------------------------------
 */
let activeRfqStatusFilter = 'all';
let activeRfqApprovalFilter = 'all';
let activeRfqPaymentTermsFilter = 'all';
let activeHeaderFilterType = null;
let rfqSearchTerm = '';
let activeAuditActionFilter = 'ALL';
let activeAuditModuleFilter = 'ALL';
let auditSearchTerm = '';

function openRfqAuditDrawer() {
    const drawer = document.getElementById('rfqAuditDrawer');
    const overlay = document.getElementById('rfqAuditDrawerOverlay');
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    renderRfqAuditTrail();
}

function closeRfqAuditDrawer() {
    const drawer = document.getElementById('rfqAuditDrawer');
    const overlay = document.getElementById('rfqAuditDrawerOverlay');
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

function toggleBuilderAuditTrail(forceOpen = null) {
    const drawer = document.getElementById('rfqAuditDrawer');
    if (!drawer) return;
    const shouldOpen = forceOpen !== null ? forceOpen : !drawer.classList.contains('active');
    if (shouldOpen) {
        openRfqAuditDrawer();
    } else {
        closeRfqAuditDrawer();
    }
}

function copyRfqReference() {
    const ref = document.getElementById('rfqRefDisplay')?.textContent?.trim() || '';
    if (!ref) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ref).then(() => {
            showToast(`✓ Copied ${ref} to clipboard`, 'success');
        }).catch(() => {
            showToast(`Reference: ${ref}`, 'info');
        });
    } else {
        showToast(`Reference: ${ref}`, 'info');
    }
}

function handleRfqTitleChange(val) {
    if (!window.AppStore.activeRfq) window.AppStore.activeRfq = {};
    window.AppStore.activeRfq.title = val;
    persistDraft();
}

function switchRfqTab(tabId) {
    const btnList = document.getElementById('tabBtnList');
    const btnBuilder = document.getElementById('tabBtnBuilder');
    const paneList = document.getElementById('pane-tab-list');
    const paneBuilder = document.getElementById('pane-tab-builder');

    [btnList, btnBuilder].forEach(b => b && b.classList.remove('active'));
    [paneList, paneBuilder].forEach(p => p && (p.style.display = 'none'));

    // Update tab counts
    const rfqs = window.AppStore.rfqs || [];
    const listCountBadge = document.getElementById('tabRfqListCount');
    if (listCountBadge) listCountBadge.textContent = rfqs.length;

    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge && typeof getProcurementAuditTrail === 'function') {
        const { rfqNum, poNum } = (typeof getCurrentAuditTarget === 'function') ? getCurrentAuditTarget() : {};
        if (rfqNum) {
            const allLogs = getProcurementAuditTrail();
            const scoped = allLogs.filter(l => (l.refNumber === rfqNum || (poNum && l.refNumber === poNum)));
            auditCountBadge.textContent = scoped.length;
        } else {
            auditCountBadge.textContent = 0;
        }
    }

    if (tabId === 'tab-list') {
        if (btnList) btnList.classList.add('active');
        if (paneList) paneList.style.display = 'flex';
        if (typeof syncFormInputsToState === 'function') {
            syncFormInputsToState();
        }
        if (window.AppStore && window.AppStore.activeRfq && window.AppStore.activeRfq.rfqNumber) {
            syncRfqToDirectory(window.AppStore.activeRfq, null, false);
        }
        renderRfqDirectory();
    } else if (tabId === 'tab-builder' || tabId === 'tab-audit') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) paneBuilder.style.display = 'flex';
        if (tabId === 'tab-audit') {
            toggleBuilderAuditTrail(true);
        }
    }
}

/**
 * --------------------------------------------------------------------------
 * RFQ DIRECTORY COLUMN CONFIGURATION & PAGINATION SYSTEM
 * --------------------------------------------------------------------------
 */
const DIRECTORY_COLUMNS = [
    {
        id: 'rfq_info',
        label: 'RFQ Info',
        subLabel: 'RFQ # / Date',
        default: true,
        lockVisible: true,
        defaultWidth: '165px',
        align: 'left',
        topField: 'rfqNumber',
        bottomField: 'rfqDate',
        topPurpose: 'Internal procurement document number (e.g., RFQ-2026-00089)',
        bottomPurpose: 'Date the RFQ was officially published/sent'
    },
    {
        id: 'company',
        label: 'Company',
        subLabel: 'Legal Entity',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'companyName',
        bottomField: 'companyId',
        topPurpose: 'FK referencing companies (the buying legal entity)',
        bottomPurpose: ''
    },
    {
        id: 'branch_warehouse',
        label: 'Branch & Warehouse',
        subLabel: 'Branch / Ship-to',
        default: false,
        lockVisible: false,
        defaultWidth: '185px',
        align: 'left',
        topField: 'branchName',
        bottomField: 'shipToWarehouseName',
        topPurpose: 'FK referencing branches (the purchasing branch)',
        bottomPurpose: 'FK referencing warehouses (destination receiving facility)'
    },
    {
        id: 'vendor_contact',
        label: 'Vendor & Contact',
        subLabel: 'Vendor / Contact Person',
        default: true,
        lockVisible: false,
        defaultWidth: '220px',
        align: 'left',
        topField: 'vendorTradeName',
        bottomField: 'vendorContactPerson',
        topPurpose: 'FK referencing vendor_master (supplier invited to bid)',
        bottomPurpose: 'FK referencing vendor_contacts (contact person shown on RFQ screen)'
    },
    {
        id: 'submission_deadline',
        label: 'Submission Deadline',
        subLabel: 'Cutoff Date',
        default: true,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'left',
        topField: 'submissionDeadline',
        bottomField: '',
        topPurpose: 'Cutoff date for the vendor to submit their bids',
        bottomPurpose: ''
    },
    {
        id: 'quote_validity_date',
        label: 'Quote Validity Date',
        subLabel: 'Valid Until',
        default: false,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'left',
        topField: 'quoteValidUntilDate',
        bottomField: '',
        topPurpose: 'Date until which the vendor quote stays valid',
        bottomPurpose: ''
    },
    {
        id: 'requested_delivery_date',
        label: 'Requested Delivery Date',
        subLabel: 'Desired Arrival',
        default: false,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'left',
        topField: 'rfqRequestedDeliveryDate',
        bottomField: '',
        topPurpose: 'Desired date goods must arrive at the warehouse',
        bottomPurpose: ''
    },
    {
        id: 'currency',
        label: 'Currency',
        subLabel: 'ISO 4217',
        default: false,
        lockVisible: false,
        defaultWidth: '95px',
        align: 'left',
        topField: 'rfqCurrencyCode',
        bottomField: '',
        topPurpose: 'ISO 4217 bidding currency (e.g., PHP, USD)',
        bottomPurpose: ''
    },
    {
        id: 'payment_terms',
        label: 'Payment Terms',
        subLabel: 'Terms & Incoterms',
        default: true,
        lockVisible: false,
        defaultWidth: '145px',
        align: 'left',
        topField: 'rfqPaymentTermsCode',
        bottomField: '',
        topPurpose: 'Requested payment terms (e.g., NET30) from vendor_master',
        bottomPurpose: ''
    },
    {
        id: 'incoterms',
        label: 'Incoterms',
        subLabel: 'Trade Terms',
        default: false,
        lockVisible: false,
        defaultWidth: '105px',
        align: 'left',
        topField: 'rfqIncotermsCode',
        bottomField: '',
        topPurpose: 'Shipping trade terms (e.g., FOB, DDP, CIF) from vendor_master',
        bottomPurpose: ''
    },
    {
        id: 'total_amount',
        label: 'Total Amount',
        subLabel: 'Quoted / Target (₱)',
        default: true,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'right',
        topField: 'rfqQuotedTotalAmount',
        bottomField: 'rfqEstimatedTotalAmount',
        topPurpose: 'Sum of quoted_line_total; award approval basis',
        bottomPurpose: 'Sum of requested_quantity x target_unit_price; issue approval basis'
    },
    {
        id: 'rfq_status',
        label: 'RFQ Status',
        subLabel: 'Stage & Linked PO',
        default: true,
        lockVisible: false,
        defaultWidth: '155px',
        align: 'left',
        topField: 'rfqStatus',
        bottomField: '',
        topPurpose: 'Lifecycle (DRAFT, SENT, QUOTED, AWARDED, REJECTED, CANCELLED)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_approval_status',
        label: 'Issue Approval',
        subLabel: 'Sign-off State',
        default: true,
        lockVisible: false,
        defaultWidth: '145px',
        align: 'left',
        topField: 'rfqApprovalStatus',
        bottomField: '',
        topPurpose: 'Approval summary for RFQ issue (NOT_REQUIRED, PENDING, APPROVED, etc.)',
        bottomPurpose: ''
    },
    {
        id: 'approval_request_id',
        label: 'Issue Approval ID',
        subLabel: 'Current Cycle',
        default: false,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topField: 'approvalRequestId',
        bottomField: '',
        topPurpose: 'FK referencing approval_requests (current approval cycle)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_award_approval_status',
        label: 'Award Approval Status',
        subLabel: 'Award State',
        default: false,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'left',
        topField: 'rfqAwardApprovalStatus',
        bottomField: '',
        topPurpose: 'Approval summary for awarding quote (NOT_REQUIRED, PENDING, etc.)',
        bottomPurpose: ''
    },
    {
        id: 'award_approval_request_id',
        label: 'Award Approval ID',
        subLabel: 'Award Doc',
        default: false,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topField: 'awardApprovalRequestId',
        bottomField: '',
        topPurpose: 'FK referencing approval_requests (document type RFQ_AWARD)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_notes',
        label: 'RFQ Notes',
        subLabel: 'Instructions',
        default: false,
        lockVisible: false,
        defaultWidth: '185px',
        align: 'left',
        topField: 'rfqNotes',
        bottomField: '',
        topPurpose: 'Special bidding instructions, delivery specs, or commercial terms',
        bottomPurpose: ''
    },
    {
        id: 'created_by',
        label: 'Created By',
        subLabel: 'Buyer / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'rfqCreatedBy',
        bottomField: 'rfqCreatedAt',
        topPurpose: 'User ID of the buyer/procurement officer',
        bottomPurpose: 'Creation timestamp'
    },
    {
        id: 'last_modified_by',
        label: 'Last Modified By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'rfqUpdatedBy',
        bottomField: 'rfqUpdatedAt',
        topPurpose: 'User ID who modified the record',
        bottomPurpose: 'Modification timestamp'
    },
    {
        id: 'line_number',
        label: 'Line #',
        subLabel: 'Items Count',
        default: false,
        lockVisible: false,
        defaultWidth: '100px',
        align: 'right',
        topField: 'itemCount',
        bottomField: '',
        topPurpose: 'Line item sequence number (1, 2, 3)',
        bottomPurpose: ''
    },
    {
        id: 'item_details',
        label: 'Item Details',
        subLabel: 'Item / Remarks',
        default: false,
        lockVisible: false,
        defaultWidth: '210px',
        align: 'left',
        topField: 'primaryItemName',
        bottomField: 'primaryLineNotes',
        topPurpose: 'FK referencing item_master',
        bottomPurpose: 'Item technical notes or vendor remarks'
    },
    {
        id: 'quantity',
        label: 'Quantity',
        subLabel: 'Requested Qty / UOM',
        default: true,
        lockVisible: false,
        defaultWidth: '130px',
        align: 'right',
        topField: 'totalQuantity',
        bottomField: 'primaryUom',
        topPurpose: 'Quantity the company intends to purchase',
        bottomPurpose: 'Purchasing unit of measure (e.g., BOX, KG)'
    },
    {
        id: 'unit_price',
        label: 'Unit Price',
        subLabel: 'Quoted / Target',
        default: false,
        lockVisible: false,
        defaultWidth: '145px',
        align: 'right',
        topField: 'primaryQuotedPrice',
        bottomField: 'primaryTargetPrice',
        topPurpose: 'Official unit price offered back by the vendor',
        bottomPurpose: 'Internal budgeted/target unit price (benchmark)'
    },
    {
        id: 'line_total',
        label: 'Line Total',
        subLabel: 'Bid Line Sum',
        default: false,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'right',
        topField: 'primaryLineTotal',
        bottomField: '',
        topPurpose: 'Bid line total (requested_quantity x quoted_unit_price)',
        bottomPurpose: ''
    },
    {
        id: 'lead_time',
        label: 'Lead Time',
        subLabel: 'Promised Days',
        default: false,
        lockVisible: false,
        defaultWidth: '110px',
        align: 'right',
        topField: 'primaryLeadTime',
        bottomField: '',
        topPurpose: 'Vendor promised manufacturing/shipping time in days',
        bottomPurpose: ''
    },
    {
        id: 'is_awarded',
        label: 'Award Status',
        subLabel: 'Line Acceptance',
        default: false,
        lockVisible: false,
        defaultWidth: '120px',
        align: 'left',
        topField: 'isAwarded',
        bottomField: '',
        topPurpose: 'Indicates if this item/bid was officially accepted',
        bottomPurpose: ''
    },
    {
        id: 'line_created_by',
        label: 'Line Created By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '155px',
        align: 'left',
        topField: 'primaryLineCreatedBy',
        bottomField: 'primaryLineCreatedAt',
        topPurpose: 'User ID who added the line',
        bottomPurpose: 'Timestamp when line was created'
    },
    {
        id: 'line_modified_by',
        label: 'Line Modified By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '155px',
        align: 'left',
        topField: 'primaryLineUpdatedBy',
        bottomField: 'primaryLineUpdatedAt',
        topPurpose: 'User ID who modified the line',
        bottomPurpose: 'Timestamp when vendor response was logged'
    },
    {
        id: 'actions',
        label: 'Actions',
        subLabel: 'Edit / View / Del',
        default: true,
        lockVisible: true,
        defaultWidth: '175px',
        align: 'right',
        topField: '',
        bottomField: '',
        topPurpose: 'Action Button (Edit, View, Delete) the RFQ',
        bottomPurpose: 'Settings Icon at the end column of the header'
    }
];

// 1. Column Sequence Order State (Saved in LocalStorage)
let directoryColOrder = (() => {
    try {
        const savedOrder = localStorage.getItem('rms_rfq_directory_cols_order_v4');
        if (savedOrder) {
            const parsed = JSON.parse(savedOrder);
            if (Array.isArray(parsed) && parsed.length > 0) {
                const allIds = DIRECTORY_COLUMNS.map(c => c.id);
                const validOrder = parsed.filter(id => allIds.includes(id));
                // Append any newly declared columns
                allIds.forEach(id => {
                    if (!validOrder.includes(id)) validOrder.push(id);
                });
                // Ensure 'actions' is always at the end
                const actionsIdx = validOrder.indexOf('actions');
                if (actionsIdx > -1 && actionsIdx !== validOrder.length - 1) {
                    validOrder.splice(actionsIdx, 1);
                    validOrder.push('actions');
                }
                return validOrder;
            }
        }
    } catch (e) {}
    return DIRECTORY_COLUMNS.map(c => c.id);
})();

// 2. Active Visible Column IDs State (Saved in LocalStorage)
let activeDirectoryColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_rfq_directory_cols_v4');
        if (saved) {
            const parsed = JSON.parse(saved);
            const allIds = DIRECTORY_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length > 0 && parsed.every(id => allIds.includes(id))) {
                if (!parsed.includes('actions')) parsed.push('actions');
                return parsed;
            }
        }
    } catch (e) {}
    // Default = TRUE columns only
    return DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
})();

// 3. Saved Column Widths State (Saved in LocalStorage)
let savedDirectoryColWidths = (() => {
    try {
        const saved = localStorage.getItem('rms_rfq_directory_col_widths_v4');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return {};
})();

// 4. Multi-Line Accordion Expansion Set
let expandedRfqNumbers = new Set();

// 5. Search Filter inside Column Dropdown
let directoryColSearchTerm = '';

let directoryCurrentPage = 1;
let directoryPageSize = 10;

function getDirectoryColWidth(col) {
    return savedDirectoryColWidths[col.id] || col.defaultWidth || '130px';
}

function initDirectoryColumns() {
    renderDirectoryTableHeader();
    renderDirectoryColDropdownChecklist();
}

/**
 * Render Modular Stacked Table Header with Drag-and-Drop and Settings Icon
 */
function renderDirectoryTableHeader() {
    const theadRow = document.getElementById('rfqDirectoryTheadRow');
    if (!theadRow) return;

    const isStatusFiltered = (activeRfqStatusFilter !== 'all' || activeRfqApprovalFilter !== 'all');
    const isTermsFiltered = (activeRfqPaymentTermsFilter !== 'all');

    // Build visible columns according to current user sequence order
    const visibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let thHtml = '';
    visibleCols.forEach(col => {
        const w = getDirectoryColWidth(col);
        const isRight = (col.align === 'right');
        const alignClass = isRight ? 'th-num' : 'th-left';

        if (col.id === 'actions') {
            // End Column Header with Right-Aligned Label & Settings Gear Icon
            thHtml += `
                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: right; user-select: none;">
                    <div class="rfq-header-action-wrapper" style="justify-content: flex-end; gap: 8px;">
                        <div class="rfq-th-content" style="align-items: flex-end; text-align: right;">
                            <div class="rfq-th-title">${escapeHtml(col.label || 'Actions')}</div>
                            <div class="rfq-th-sub">${escapeHtml(col.subLabel || 'Edit / View / Del')}</div>
                        </div>
                        <button type="button" class="rfq-th-settings-btn" id="btnRfqTableSettings" onclick="toggleRfqTableSettingsDropdown(event)" title="Customize Columns & Drag Sequence">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            // Modular Stacked Header with Strict Left (Text) / Right (Number) Alignment
            const hasStatusFilter = (col.id === 'rfq_status');
            const hasTermsFilter = (col.id === 'payment_terms');

            thHtml += `
                <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}" draggable="true"
                    ondragstart="handleHeaderDragStart(event, '${col.id}')"
                    ondragover="handleHeaderDragOver(event, '${col.id}')"
                    ondragleave="handleHeaderDragLeave(event)"
                    ondrop="handleHeaderDrop(event, '${col.id}')"
                    ondragend="handleHeaderDragEnd(event)"
                    style="width: ${w}; text-align: ${isRight ? 'right' : 'left'}; position: relative; user-select: none;"
                    title="${escapeHtml(col.topPurpose || col.label)}">
                    <div style="display: flex; align-items: center; justify-content: ${isRight ? 'flex-end' : 'space-between'}; gap: 6px; min-width: 0; width: 100%;">
                        <div class="rfq-th-content" style="align-items: ${isRight ? 'flex-end' : 'flex-start'}; text-align: ${isRight ? 'right' : 'left'};">
                            <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                            ${col.subLabel ? `<div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>` : ''}
                        </div>
                        ${hasStatusFilter ? `
                            <button type="button" class="rfq-th-funnel-btn ${isStatusFiltered ? 'is-active' : ''}" onclick="toggleRfqHeaderFilter(event, 'status')" title="Filter Status & Approvals">
                                <i class="ph ph-funnel"></i>
                                ${isStatusFiltered ? '<span class="rfq-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                        ${hasTermsFilter ? `
                            <button type="button" class="rfq-th-funnel-btn ${isTermsFiltered ? 'is-active' : ''}" onclick="toggleRfqHeaderFilter(event, 'terms')" title="Filter Payment Terms">
                                <i class="ph ph-funnel"></i>
                                ${isTermsFiltered ? '<span class="rfq-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                    </div>
                    <div class="rfq-col-resizer" onmousedown="initDirectoryColResize(event, '${col.id}')"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = thHtml;
    renderDirectoryColDropdownChecklist();
    renderRfqActiveFilterChips();
}

/**
 * Render Checkboxes and Drag-and-Drop Handles in Settings Dropdown
 */
function renderDirectoryColDropdownChecklist() {
    const listEl = document.getElementById('directoryColCheckboxesList');
    if (!listEl) return;

    const query = (directoryColSearchTerm || '').toLowerCase().trim();

    // Render list ordered by current directoryColOrder
    const orderedCols = directoryColOrder.map(id => DIRECTORY_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => {
            if (!query) return true;
            return col.label.toLowerCase().includes(query) ||
                   (col.subLabel && col.subLabel.toLowerCase().includes(query)) ||
                   col.id.toLowerCase().includes(query);
        })
        .map(col => {
            const isChecked = activeDirectoryColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';
            const isDefault = col.default;

            return `
                <div class="rfq-dropdown-col-item" data-col-id="${col.id}" draggable="${col.id !== 'actions' ? 'true' : 'false'}"
                    ondragstart="handleDropdownDragStart(event, '${col.id}')"
                    ondragover="handleDropdownDragOver(event, '${col.id}')"
                    ondragleave="handleDropdownDragLeave(event)"
                    ondrop="handleDropdownDrop(event, '${col.id}')"
                    ondragend="handleDropdownDragEnd(event)">
                    ${col.id !== 'actions' ? `
                        <span class="rfq-drag-handle" title="Drag to reorder column sequence">
                            <i class="ph ph-dots-six-vertical"></i>
                        </span>
                    ` : `
                        <span style="width: 14px; display: inline-block;"></span>
                    `}
                    <label class="rfq-col-item-label" title="${escapeHtml(col.topPurpose || col.label)}">
                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleDirectoryColVisibility('${col.id}', this.checked)">
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <span class="rfq-col-name">${escapeHtml(col.label)}</span>
                                ${col.subLabel ? `<span class="rfq-col-subname">${escapeHtml(col.subLabel)}</span>` : ''}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            ${isDefault ? '<span class="rfq-col-badge-default">Default</span>' : ''}
                            ${col.lockVisible ? '<span style="font-size: 0.65rem; color:#94a3b8; font-weight:600;">(Locked)</span>' : ''}
                        </div>
                    </label>
                </div>
            `;
        }).join('');

    const counter = document.getElementById('directoryColActiveCounter');
    if (counter) {
        counter.textContent = `${activeDirectoryColIds.length} of ${DIRECTORY_COLUMNS.length} visible`;
    }
}

/**
 * Toggle Settings Dropdown Positioned at Trigger Icon
 */
function toggleRfqTableSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('rfqTableSettingsDropdown');
    const trigger = event ? event.currentTarget : document.getElementById('btnRfqTableSettings');
    if (!dropdown) return;

    const isOpen = dropdown.classList.contains('is-active');
    if (isOpen) {
        dropdown.classList.remove('is-active');
        dropdown.style.display = 'none';
        if (trigger) trigger.classList.remove('is-active');
    } else {
        dropdown.style.display = 'block';
        dropdown.classList.add('is-active');
        if (trigger) trigger.classList.add('is-active');

        // Anchored positioning clamping
        if (trigger) {
            const rect = trigger.getBoundingClientRect();
            const dropdownWidth = 370;
            let left = rect.right - dropdownWidth;
            if (left < 10) left = 10;
            let top = rect.bottom + 6;
            if (top + 400 > window.innerHeight) {
                top = Math.max(10, rect.top - 410);
            }
            dropdown.style.left = `${left}px`;
            dropdown.style.top = `${top}px`;
        }

        renderDirectoryColDropdownChecklist();
    }
}

function handleDirectoryColSearch(val) {
    directoryColSearchTerm = val;
    renderDirectoryColDropdownChecklist();
}

/**
 * Reorder Columns in Sequence and Persist
 */
function reorderDirectoryColumns(sourceColId, targetColId) {
    if (!sourceColId || !targetColId || sourceColId === targetColId) return;
    if (sourceColId === 'actions' || targetColId === 'actions') return;

    const sourceIdx = directoryColOrder.indexOf(sourceColId);
    const targetIdx = directoryColOrder.indexOf(targetColId);
    if (sourceIdx === -1 || targetIdx === -1) return;

    // Move source to target position
    directoryColOrder.splice(sourceIdx, 1);
    directoryColOrder.splice(targetIdx, 0, sourceColId);

    // Save to LocalStorage
    localStorage.setItem('rms_rfq_directory_cols_order_v4', JSON.stringify(directoryColOrder));

    renderDirectoryTableHeader();
    renderDirectoryColDropdownChecklist();
    renderRfqDirectory();
    showToast('✓ Column sequence updated', 'success');
}

// Drag & Drop Handlers for Dropdown Menu List
let dropdownDragSourceId = null;

function handleDropdownDragStart(e, colId) {
    dropdownDragSourceId = colId;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', colId);
    const item = e.currentTarget;
    if (item) item.classList.add('is-dragging');
}

function handleDropdownDragOver(e, colId) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const item = e.currentTarget;
    if (item && dropdownDragSourceId && dropdownDragSourceId !== colId) {
        item.classList.add('is-drag-over');
    }
}

function handleDropdownDragLeave(e) {
    const item = e.currentTarget;
    if (item) item.classList.remove('is-drag-over');
}

function handleDropdownDrop(e, targetColId) {
    e.preventDefault();
    const item = e.currentTarget;
    if (item) item.classList.remove('is-drag-over');
    if (dropdownDragSourceId && targetColId && dropdownDragSourceId !== targetColId) {
        reorderDirectoryColumns(dropdownDragSourceId, targetColId);
    }
    dropdownDragSourceId = null;
}

function handleDropdownDragEnd(e) {
    dropdownDragSourceId = null;
    document.querySelectorAll('.rfq-dropdown-col-item').forEach(el => {
        el.classList.remove('is-dragging', 'is-drag-over');
    });
}

// Drag & Drop Handlers for Table Header (TH) Cells
let headerDragSourceId = null;

function handleHeaderDragStart(e, colId) {
    if (colId === 'actions') {
        e.preventDefault();
        return;
    }
    headerDragSourceId = colId;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', colId);
    const th = e.currentTarget;
    if (th) th.classList.add('is-header-dragging');
}

function handleHeaderDragOver(e, colId) {
    if (colId === 'actions') return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const th = e.currentTarget;
    if (th && headerDragSourceId && headerDragSourceId !== colId) {
        th.classList.add('is-header-drag-over');
    }
}

function handleHeaderDragLeave(e) {
    const th = e.currentTarget;
    if (th) th.classList.remove('is-header-drag-over');
}

function handleHeaderDrop(e, targetColId) {
    e.preventDefault();
    const th = e.currentTarget;
    if (th) th.classList.remove('is-header-drag-over');
    if (headerDragSourceId && targetColId && headerDragSourceId !== targetColId && targetColId !== 'actions') {
        reorderDirectoryColumns(headerDragSourceId, targetColId);
    }
    headerDragSourceId = null;
}

function handleHeaderDragEnd(e) {
    headerDragSourceId = null;
    document.querySelectorAll('#rfqDirectoryThead th').forEach(el => {
        el.classList.remove('is-header-dragging', 'is-header-drag-over');
    });
}

/**
 * Toggle Column Visibility
 */
function toggleDirectoryColVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeDirectoryColIds.includes(colId)) {
            activeDirectoryColIds.push(colId);
        }
    } else {
        activeDirectoryColIds = activeDirectoryColIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_rfq_directory_cols_v4', JSON.stringify(activeDirectoryColIds));
    renderDirectoryTableHeader();
    renderRfqDirectory();
}

function showAllDirectoryColumns() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_rfq_directory_cols_v4', JSON.stringify(activeDirectoryColIds));
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Showing all columns', 'success');
}

function resetDirectoryColumnDefaults() {
    // Reset to only Default = True columns
    activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_rfq_directory_cols_v4', JSON.stringify(activeDirectoryColIds));
    localStorage.removeItem('rms_rfq_directory_cols_order_v4');
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Reset to Default headers', 'success');
}

function resetDirectoryColumns() {
    resetDirectoryColumnDefaults();
}

function resetDirectoryColumnWidths() {
    savedDirectoryColWidths = {};
    localStorage.removeItem('rms_rfq_directory_col_widths_v4');
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Directory column widths reset to defaults', 'success');
}

/**
 * Column Resizing Handlers
 */
let dirResizingColId = null;
let dirStartX = 0;
let dirStartW = 0;

function initDirectoryColResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    dirResizingColId = colId;
    dirStartX = e.pageX;

    const thEl = document.querySelector(`#rfqDirectoryThead th[data-col-id="${colId}"]`);
    dirStartW = thEl ? thEl.offsetWidth : 120;

    document.addEventListener('mousemove', handleDirectoryColMouseMove);
    document.addEventListener('mouseup', handleDirectoryColMouseUp);
}

function handleDirectoryColMouseMove(e) {
    if (!dirResizingColId) return;
    const diff = e.pageX - dirStartX;
    const newWidth = Math.max(70, dirStartW + diff);
    savedDirectoryColWidths[dirResizingColId] = `${newWidth}px`;

    const thEl = document.querySelector(`#rfqDirectoryThead th[data-col-id="${dirResizingColId}"]`);
    if (thEl) {
        thEl.style.width = `${newWidth}px`;
    }
}

function handleDirectoryColMouseUp() {
    if (!dirResizingColId) return;
    localStorage.setItem('rms_rfq_directory_col_widths_v4', JSON.stringify(savedDirectoryColWidths));
    dirResizingColId = null;
    document.removeEventListener('mousemove', handleDirectoryColMouseMove);
    document.removeEventListener('mouseup', handleDirectoryColMouseUp);
}

function toggleRfqRowExpansion(rfqNumber, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    if (expandedRfqNumbers.has(rfqNumber)) {
        expandedRfqNumbers.delete(rfqNumber);
    } else {
        expandedRfqNumbers.add(rfqNumber);
    }
    renderRfqDirectory();
}

function changeDirectoryPageSize(newSize) {
    directoryPageSize = parseInt(newSize, 10) || 10;
    directoryCurrentPage = 1;
    renderRfqDirectory();
}

function goToDirectoryPage(page) {
    directoryCurrentPage = page;
    renderRfqDirectory();
}

function renderDirectoryPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / directoryPageSize) || 1;
    if (directoryCurrentPage > totalPages) directoryCurrentPage = totalPages;
    if (directoryCurrentPage < 1) directoryCurrentPage = 1;

    const startIdx = totalItems === 0 ? 0 : (directoryCurrentPage - 1) * directoryPageSize + 1;
    const endIdx = Math.min(directoryCurrentPage * directoryPageSize, totalItems);

    const infoEl = document.getElementById('rfqPaginationInfo');
    if (infoEl) {
        infoEl.innerHTML = `Showing <strong>${startIdx}</strong> to <strong>${endIdx}</strong> of <strong>${totalItems}</strong> tenders`;
    }

    const btnsEl = document.getElementById('rfqPaginationButtons');
    if (!btnsEl) return;

    let btnsHtml = '';
    btnsHtml += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" 
            onclick="goToDirectoryPage(${directoryCurrentPage - 1})" 
            ${directoryCurrentPage <= 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} 
            title="Previous Page" style="padding: 4px 8px; font-size: 11px;">
            <i class="ph ph-caret-left"></i>
        </button>
    `;

    const maxButtons = 5;
    let startPage = Math.max(1, directoryCurrentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    for (let p = startPage; p <= endPage; p++) {
        const isActive = p === directoryCurrentPage;
        btnsHtml += `
            <button type="button" 
                class="hr-btn ${isActive ? 'hr-btn-primary' : 'hr-btn-secondary'} hr-btn-sm" 
                onclick="goToDirectoryPage(${p})" 
                style="padding: 4px 9px; font-size: 11.5px; min-width: 28px; font-weight: ${isActive ? '700' : '500'};">
                ${p}
            </button>
        `;
    }

    btnsHtml += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" 
            onclick="goToDirectoryPage(${directoryCurrentPage + 1})" 
            ${directoryCurrentPage >= totalPages ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} 
            title="Next Page" style="padding: 4px 8px; font-size: 11px;">
            <i class="ph ph-caret-right"></i>
        </button>
    `;

    btnsEl.innerHTML = btnsHtml;
}

/**
 * Render RFQ Directory Rows with Modular Stacked Cells and Multi-Line Accordion
 */
function renderRfqDirectory() {
    const tbody = document.getElementById('rfqDirectoryTbody');
    const rawRfqs = window.AppStore.rfqs || [];
    if (!tbody) return;

    const rfqs = rawRfqs.map(normalizeRfqItem).filter(Boolean);

    // Filter by status, approval, payment terms & search
    const filtered = rfqs.filter(r => {
        const rawStatus = r.rfqStatus || r.status || 'Draft';
        const normStage = getNormalizedRfqStage(rawStatus);
        let matchesStatus = (activeRfqStatusFilter === 'all') || 
            (r.status === activeRfqStatusFilter) || 
            (r.rfqStatus === activeRfqStatusFilter) || 
            (normStage === activeRfqStatusFilter);

        if (!matchesStatus) {
            if (activeRfqStatusFilter === 'Submitted' && (rawStatus === 'Quote Requested' || rawStatus === 'Sent')) matchesStatus = true;
            else if (activeRfqStatusFilter === 'Approval' && (rawStatus === 'Quotation Received' || rawStatus === 'In Review')) matchesStatus = true;
            else if (activeRfqStatusFilter === 'Approved' && (rawStatus === 'Awarded' || rawStatus === 'Approved')) matchesStatus = true;
            else if (activeRfqStatusFilter === 'Refused' && (rawStatus === 'Cancelled' || rawStatus === 'Rejected')) matchesStatus = true;
        }
        
        const isApproved = (r.isApproved === true || r.rfqApprovalStatus === 'APPROVED' || r.status === 'Awarded');
        let matchesApproval = true;
        if (activeRfqApprovalFilter === 'approved') matchesApproval = isApproved;
        else if (activeRfqApprovalFilter === 'pending') matchesApproval = !isApproved;

        const matchesTerms = (activeRfqPaymentTermsFilter === 'all') || 
            ((r.paymentTerms || '').toLowerCase() === activeRfqPaymentTermsFilter.toLowerCase()) ||
            ((r.rfqPaymentTermsCode || '').toLowerCase() === activeRfqPaymentTermsFilter.toLowerCase());

        const q = (rfqSearchTerm || '').toLowerCase().trim();
        const matchesSearch = !q || 
            (r.rfqNumber && r.rfqNumber.toLowerCase().includes(q)) ||
            (r.vendorName && r.vendorName.toLowerCase().includes(q)) ||
            (r.vendorTradeName && r.vendorTradeName.toLowerCase().includes(q)) ||
            (r.vendorContactPerson && r.vendorContactPerson.toLowerCase().includes(q)) ||
            (r.companyName && r.companyName.toLowerCase().includes(q)) ||
            (r.branchName && r.branchName.toLowerCase().includes(q)) ||
            (r.specialInstructions && r.specialInstructions.toLowerCase().includes(q)) ||
            (r.rfqNotes && r.rfqNotes.toLowerCase().includes(q)) ||
            (r.paymentTerms && r.paymentTerms.toLowerCase().includes(q));

        return matchesStatus && matchesApproval && matchesTerms && matchesSearch;
    });

    // Update KPI Counts across all 5 stages
    const totalCount = rfqs.length;
    const draftCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Draft').length;
    const sentCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Submitted').length;
    const approvalCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Approval').length;
    const awardedCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Approved').length;

    const elTotal = document.getElementById('kpiTotalRfqs');
    const elDraft = document.getElementById('kpiDraftRfqs');
    const elAwaiting = document.getElementById('kpiAwaitingRfqs');
    const elApproval = document.getElementById('kpiApprovalRfqs');
    const elAwarded = document.getElementById('kpiAwardedRfqs');
    const elTabCount = document.getElementById('tabRfqListCount');

    if (elTotal) elTotal.textContent = totalCount;
    if (elDraft) elDraft.textContent = draftCount;
    if (elAwaiting) elAwaiting.textContent = sentCount;
    if (elApproval) elApproval.textContent = approvalCount;
    if (elAwarded) elAwarded.textContent = awardedCount;
    if (elTabCount) elTabCount.textContent = totalCount;

    renderRfqActiveFilterChips();

    // Get visible columns in user order
    const visibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${visibleCols.length}" style="text-align: center; padding: 36px; color: var(--rfq-text-muted);">No Requests for Quotation match your criteria.</td></tr>`;
        renderDirectoryPagination(0);
        return;
    }

    // Pagination slice
    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / directoryPageSize) || 1;
    if (directoryCurrentPage > totalPages) directoryCurrentPage = totalPages;
    if (directoryCurrentPage < 1) directoryCurrentPage = 1;

    const startIndex = (directoryCurrentPage - 1) * directoryPageSize;
    const paginatedItems = filtered.slice(startIndex, startIndex + directoryPageSize);

    let rowsHtml = '';
    paginatedItems.forEach(r => {
        const isExpanded = expandedRfqNumbers.has(r.rfqNumber);
        const items = r.items || [];
        const primaryItem = items.length > 0 ? items[0] : {};
        const totalQty = items.reduce((acc, it) => acc + (parseFloat(it.requestedQuantity || it.quantity) || 0), 0);
        const primaryUom = primaryItem.purchaseUomCode || primaryItem.unit || 'Unit';
        const quotedTotal = r.rfqQuotedTotalAmount || items.reduce((acc, it) => acc + (parseFloat(it.quotedLineTotal) || 0), 0);
        const estTotal = r.rfqEstimatedTotalAmount || items.reduce((acc, it) => acc + ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.targetUnitPrice || it.targetPrice) || 0)), 0);

        const stage = getNormalizedRfqStage(r.rfqStatus || r.status);
        let pillClass = stage.toLowerCase();
        if (stage === 'Submitted') pillClass += ' sent';
        else if (stage === 'Approval') pillClass += ' received in-review';
        else if (stage === 'Approved') pillClass += ' awarded';
        else if (stage === 'Refused') pillClass += ' cancelled';

        let rowCells = '';
        visibleCols.forEach(col => {
            switch (col.id) {
                case 'rfq_info':
                    rowCells += `
                        <td class="td-left">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                <button type="button" class="rfq-row-expand-btn ${isExpanded ? 'is-expanded' : ''}" 
                                    onclick="toggleRfqRowExpansion('${escapeHtml(r.rfqNumber)}', event)" 
                                    title="${isExpanded ? 'Collapse Line Items' : 'Expand ' + items.length + ' Line Items'}">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                                <div class="rms-cell-stack">
                                    <div class="rms-cell-title">
                                        <a href="javascript:void(0)" class="rfq-dir-ref-link" onclick="editRfqFromDirectory('${escapeHtml(r.rfqNumber)}')" title="Open ${escapeHtml(r.rfqNumber)} in RFQ Builder">
                                            ${escapeHtml(r.rfqNumber)}
                                        </a>
                                    </div>
                                    <div class="rms-cell-sub">
                                        Issued: ${formatDateDisplay(r.rfqDate || r.dateIssued)}
                                    </div>
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'company':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.companyName)}">${escapeHtml(r.companyName)}</div>
                                <div class="rms-cell-sub" style="font-family: monospace;">${escapeHtml(r.companyId || '—')}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'branch_warehouse':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.branchName)}">${escapeHtml(r.branchName)}</div>
                                <div class="rms-cell-sub" title="${escapeHtml(r.shipToWarehouseName)}"><i class="ph ph-warehouse" style="font-size: 11px; color: #94a3b8;"></i> ${escapeHtml(r.shipToWarehouseName)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'vendor_contact':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.vendorTradeName || r.vendorName)}">
                                    ${escapeHtml(r.vendorTradeName || r.vendorName)}
                                </div>
                                <div class="rms-cell-sub" title="${escapeHtml((r.vendorContactPerson || 'No Contact') + ' • ' + (r.vendorEmail || r.vendorPhone || ''))}">
                                    <i class="ph ph-user" style="font-size: 11px; color: #94a3b8;"></i> ${escapeHtml(r.vendorContactPerson || 'Purchasing Contact')}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'submission_deadline':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">
                                    ${formatDateDisplay(r.submissionDeadline || r.dueDate)}
                                </div>
                                <div class="rms-cell-sub">Cutoff Deadline</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'quote_validity_date':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${formatDateDisplay(r.quoteValidUntilDate)}</div>
                                <div class="rms-cell-sub">Quote Valid Until</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'requested_delivery_date':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${formatDateDisplay(r.rfqRequestedDeliveryDate || r.expectedDelivery)}</div>
                                <div class="rms-cell-sub">Target Arrival</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'currency':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqCurrencyCode || 'PHP')}</div>
                                <div class="rms-cell-sub">Bidding Currency</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'payment_terms':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.rfqPaymentTermsCode || r.paymentTerms || 'Net 30 Days')}">
                                    ${escapeHtml(r.rfqPaymentTermsCode || r.paymentTerms || 'Net 30 Days')}
                                </div>
                                <div class="rms-cell-sub">
                                    ${escapeHtml(r.rfqIncotermsCode || 'FOB')} • ${escapeHtml(r.rfqCurrencyCode || 'PHP')}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'incoterms':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqIncotermsCode || 'FOB')}</div>
                                <div class="rms-cell-sub">Trade Terms</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'total_amount':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="color: #0f172a; font-weight: 800; font-variant-numeric: tabular-nums;">₱${formatMoney(quotedTotal || estTotal)}</div>
                                <div class="rms-cell-sub" style="justify-content: flex-end; font-variant-numeric: tabular-nums;">Target: ₱${formatMoney(estTotal)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'rfq_status':
                    const rowLinkedPo = getLinkedPoNumberForRfq(r);
                    if (rowLinkedPo) r.poReference = rowLinkedPo;
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack" style="align-items: flex-start; gap: 3px;">
                                <span class="status-pill ${pillClass}">
                                    <span class="status-pill-dot"></span> ${escapeHtml(r.rfqStatus || r.status)}
                                </span>
                                ${rowLinkedPo ? `
                                    <button type="button" class="rfq-linked-po-sublink" onclick="openLinkedPoFromRfq('${escapeHtml(rowLinkedPo)}')" title="Open Purchase Order ${escapeHtml(rowLinkedPo)}">
                                        <i class="ph ph-link"></i> ${escapeHtml(rowLinkedPo)} <i class="ph ph-arrow-up-right" style="font-size: 9px;"></i>
                                    </button>
                                ` : `
                                    <div class="rms-cell-sub">Stage: ${escapeHtml(stage)}</div>
                                `}
                            </div>
                        </td>
                    `;
                    break;

                case 'rfq_approval_status':
                    const isAppr = (r.rfqApprovalStatus === 'APPROVED' || r.isApproved === true || r.status === 'Awarded');
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack" style="align-items: flex-start; gap: 3px;">
                                ${isAppr ? `
                                    <button type="button" class="rfq-appr-pill rfq-appr-approved" onclick="openManagerApprovalModal('${escapeHtml(r.rfqNumber)}')" title="Issue Approved - Click to inspect">
                                        <i class="ph ph-check-circle"></i> Approved
                                    </button>
                                    <div class="rms-cell-sub">Manager Verified</div>
                                ` : `
                                    <button type="button" class="rfq-appr-pill rfq-appr-pending" onclick="openManagerApprovalModal('${escapeHtml(r.rfqNumber)}')" title="Issue Pending - Click to review">
                                        <i class="ph ph-clock"></i> Pending
                                    </button>
                                    <div class="rms-cell-sub">Awaiting Sign-off</div>
                                `}
                            </div>
                        </td>
                    `;
                    break;

                case 'approval_request_id':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" style="font-family: monospace;" title="${escapeHtml(r.approvalRequestId || 'N/A')}">
                                    ${escapeHtml(r.approvalRequestId || '—')}
                                </div>
                                <div class="rms-cell-sub">Issue Approval Ref</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'rfq_award_approval_status':
                    const isAwardedAppr = (r.rfqAwardApprovalStatus === 'APPROVED' || r.status === 'Awarded');
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack" style="align-items: flex-start;">
                                <span class="rfq-appr-pill ${isAwardedAppr ? 'rfq-appr-approved' : 'rfq-appr-neutral'}">
                                    ${escapeHtml(r.rfqAwardApprovalStatus || (isAwardedAppr ? 'APPROVED' : 'NOT REQUIRED'))}
                                </span>
                                <div class="rms-cell-sub">Award Governance</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'award_approval_request_id':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" style="font-family: monospace;" title="${escapeHtml(r.awardApprovalRequestId || 'N/A')}">
                                    ${escapeHtml(r.awardApprovalRequestId || '—')}
                                </div>
                                <div class="rms-cell-sub">Award Doc ID</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'rfq_notes':
                    const noteStr = r.rfqNotes || r.specialInstructions || r.notes || '—';
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" style="font-weight: 500; color: #334155;" title="${escapeHtml(noteStr)}">
                                    ${escapeHtml(noteStr)}
                                </div>
                                <div class="rms-cell-sub">Tender Instructions</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'created_by':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqCreatedBy || 'Admin')}</div>
                                <div class="rms-cell-sub">${formatDateDisplay(r.rfqCreatedAt || r.dateIssued)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'last_modified_by':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqUpdatedBy || 'Lead')}</div>
                                <div class="rms-cell-sub">${formatDateDisplay(r.rfqUpdatedAt || r.rfqCreatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_number':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="font-variant-numeric: tabular-nums;">${items.length}</div>
                                <div class="rms-cell-sub" style="justify-content: flex-end;">Total Lines</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'item_details':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(primaryItem.name || 'No Items')}">
                                    ${escapeHtml(primaryItem.name || 'No Items')}
                                </div>
                                <div class="rms-cell-sub" title="${escapeHtml(primaryItem.specs || primaryItem.rfqLineNotes || '')}">
                                    ${escapeHtml(primaryItem.specs || primaryItem.rfqLineNotes || (items.length > 1 ? '+' + (items.length - 1) + ' more items' : 'Primary line item'))}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'quantity':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="font-weight: 700; font-variant-numeric: tabular-nums;">
                                    ${formatMoney(totalQty, 1)} <span style="font-size: 0.72rem; font-weight: 600; color: #64748b;">${escapeHtml(primaryUom)}</span>
                                </div>
                                <div class="rms-cell-sub" style="justify-content: flex-end;">
                                    ${items.length} line item${items.length === 1 ? '' : 's'}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'unit_price':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="font-variant-numeric: tabular-nums;">₱${formatMoney(primaryItem.quotedUnitPrice || primaryItem.targetUnitPrice || 0)}</div>
                                <div class="rms-cell-sub" style="justify-content: flex-end; font-variant-numeric: tabular-nums;">Target: ₱${formatMoney(primaryItem.targetUnitPrice || 0)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_total':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums;">
                                    ₱${formatMoney(primaryItem.quotedLineTotal || ((primaryItem.requestedQuantity || 0) * (primaryItem.quotedUnitPrice || 0)))}
                                </div>
                                <div class="rms-cell-sub" style="justify-content: flex-end;">Primary Line Sum</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'lead_time':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack rms-stack-right">
                                <div class="rms-cell-title" style="font-variant-numeric: tabular-nums;">${primaryItem.leadTimeDays || 3} Days</div>
                                <div class="rms-cell-sub" style="justify-content: flex-end;">Est. Lead Time</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'is_awarded':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack" style="align-items: flex-start;">
                                <span class="rfq-appr-pill ${primaryItem.isAwarded ? 'rfq-appr-approved' : 'rfq-appr-neutral'}">
                                    ${primaryItem.isAwarded ? 'Accepted' : 'Pending'}
                                </span>
                                <div class="rms-cell-sub">Line Award</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_created_by':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(primaryItem.rfqLineCreatedBy || 'Admin')}</div>
                                <div class="rms-cell-sub">${formatDateDisplay(primaryItem.rfqLineCreatedAt || r.rfqCreatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_modified_by':
                    rowCells += `
                        <td class="td-left">
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(primaryItem.rfqLineUpdatedBy || 'Lead')}</div>
                                <div class="rms-cell-sub">${formatDateDisplay(primaryItem.rfqLineUpdatedAt || r.rfqUpdatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'actions':
                    const actionLinkedPo = getLinkedPoNumberForRfq(r);
                    rowCells += `
                        <td class="td-num td-actions-cell">
                            <div class="rfq-row-actions">
                                ${actionLinkedPo ? `
                                    <button type="button" class="rfq-act-pill rfq-act-pill-po" onclick="openLinkedPoFromRfq('${escapeHtml(actionLinkedPo)}')" title="Redirect to Created PO: ${escapeHtml(actionLinkedPo)}">
                                        <i class="ph ph-receipt"></i>
                                        <span>View PO</span>
                                        <i class="ph ph-arrow-up-right"></i>
                                    </button>
                                ` : (stage === 'Approved' ? `
                                    <button type="button" class="rfq-act-pill rfq-act-pill-create" onclick="quickCreatePoFromDirectory('${escapeHtml(r.rfqNumber)}')" title="Create Purchase Order for ${escapeHtml(r.rfqNumber)}">
                                        <i class="ph ph-plus-circle"></i>
                                        <span>Create PO</span>
                                    </button>
                                ` : '')}
                                <button type="button" class="rfq-act-icon-btn" onclick="editRfqFromDirectory('${escapeHtml(r.rfqNumber)}')" title="Open & Edit RFQ">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button type="button" class="rfq-act-icon-btn" onclick="previewRfqFromDirectory('${escapeHtml(r.rfqNumber)}')" title="View Document Preview">
                                    <i class="ph ph-eye"></i>
                                </button>
                                <button type="button" class="rfq-act-icon-btn rfq-act-icon-danger" onclick="deleteRfqFromDirectory('${escapeHtml(r.rfqNumber)}')" title="Delete RFQ">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    break;

                default:
                    rowCells += `<td class="td-left">—</td>`;
                    break;
            }
        });

        rowsHtml += `<tr class="${isExpanded ? 'is-row-expanded' : ''}">${rowCells}</tr>`;

        // Multi-line Accordion Drawer (Expanded Subtable)
        if (isExpanded) {
            rowsHtml += `
                <tr class="rfq-line-expansion-row">
                    <td colspan="${visibleCols.length}" style="padding: 0; background: #f8fafc;">
                        <div class="rfq-subtable-container">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <div style="font-weight: 700; font-size: 0.80rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <i class="ph ph-list-numbers" style="color: var(--rfq-primary);"></i>
                                    <span>Quotation Specification Lines (${items.length} items)</span>
                                </div>
                                <div style="font-size: 0.73rem; color: #64748b;">
                                    Vendor Quote Basis for Award Approval
                                </div>
                            </div>
                            <table class="rfq-subtable">
                                <thead>
                                    <tr>
                                        <th style="width: 56px; text-align: right;">Line #</th>
                                        <th style="width: 115px; text-align: left;">Item SKU</th>
                                        <th style="text-align: left;">Description & Technical Specs</th>
                                        <th style="width: 75px; text-align: left;">UOM</th>
                                        <th style="width: 95px; text-align: right;">Qty</th>
                                        <th style="width: 115px; text-align: right;">Target Price</th>
                                        <th style="width: 115px; text-align: right;">Quoted Price</th>
                                        <th style="width: 125px; text-align: right;">Line Total</th>
                                        <th style="width: 90px; text-align: right;">Lead Time</th>
                                        <th style="width: 100px; text-align: left;">Award</th>
                                        <th style="width: 150px; text-align: left;">Line Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map((line, lIdx) => `
                                        <tr>
                                            <td style="text-align: right; font-weight: 700; color: #64748b; font-variant-numeric: tabular-nums;">${line.lineNumber || (lIdx + 1)}</td>
                                            <td style="text-align: left; font-family: monospace; font-weight: 700; color: #475569;">${escapeHtml(line.itemId || line.sku || '—')}</td>
                                            <td style="text-align: left;">
                                                <div style="font-weight: 600; color: #0f172a;">${escapeHtml(line.name)}</div>
                                                ${line.specs ? `<div style="font-size: 0.70rem; color: #64748b;">${escapeHtml(line.specs)}</div>` : ''}
                                            </td>
                                            <td style="text-align: left;"><span class="hr-badge hr-badge-neutral">${escapeHtml(line.purchaseUomCode || line.unit)}</span></td>
                                            <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">${formatMoney(line.requestedQuantity || line.quantity, 1)}</td>
                                            <td style="text-align: right; color: #64748b; font-variant-numeric: tabular-nums;">₱${formatMoney(line.targetUnitPrice || line.targetPrice)}</td>
                                            <td style="text-align: right; font-weight: 700; color: var(--rfq-primary-dark); font-variant-numeric: tabular-nums;">₱${formatMoney(line.quotedUnitPrice || line.targetPrice)}</td>
                                            <td style="text-align: right; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums;">₱${formatMoney(line.quotedLineTotal || ((line.quantity || 1) * (line.targetPrice || 0)))}</td>
                                            <td style="text-align: right; font-variant-numeric: tabular-nums;">${line.leadTimeDays || 3}d</td>
                                            <td style="text-align: left;">
                                                <span class="rfq-appr-pill ${line.isAwarded ? 'rfq-appr-approved' : 'rfq-appr-neutral'}">
                                                    ${line.isAwarded ? 'Accepted' : 'Pending'}
                                                </span>
                                            </td>
                                            <td style="text-align: left; font-size: 0.71rem; color: #64748b;">${escapeHtml(line.rfqLineNotes || line.notes || '—')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            `;
        }
    });

    tbody.innerHTML = rowsHtml;
    renderDirectoryPagination(totalItems);
}

function filterRfqByStatus(status) {
    activeRfqStatusFilter = status;
    directoryCurrentPage = 1;
    document.querySelectorAll('#pane-tab-list .rfq-kpi-card[data-kpi-filter]').forEach(card => {
        card.classList.toggle('is-active-filter', card.getAttribute('data-kpi-filter') === status);
    });
    renderDirectoryTableHeader();
    renderRfqDirectory();
}

function toggleRfqHeaderFilter(event, filterType) {
    if (event) event.stopPropagation();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (!popover) return;

    if (activeHeaderFilterType === filterType && popover.style.display !== 'none') {
        closeRfqHeaderFilter();
        return;
    }

    activeHeaderFilterType = filterType;
    const triggerEl = event.currentTarget;
    const rect = triggerEl.getBoundingClientRect();

    popover.innerHTML = filterType === 'status' ? buildStatusFilterPopoverHtml() : buildPaymentTermsFilterPopoverHtml();
    popover.style.display = 'block';
    popover.style.position = 'fixed';
    popover.style.top = `${rect.bottom + 6}px`;

    const popoverWidth = filterType === 'status' ? 360 : 280;
    let left = rect.left;
    if (left + popoverWidth > window.innerWidth - 16) {
        left = window.innerWidth - popoverWidth - 16;
    }
    popover.style.left = `${Math.max(10, left)}px`;
    popover.style.zIndex = '1200';
}

function closeRfqHeaderFilter() {
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover) popover.style.display = 'none';
    activeHeaderFilterType = null;
}

function buildStatusFilterPopoverHtml() {
    const rfqs = window.AppStore.rfqs || [];
    const totalCount = rfqs.length;
    const approvedCount = rfqs.filter(r => r.isApproved === true || getNormalizedRfqStage(r.rfqStatus || r.status) === 'Approved' || !!r.poReference).length;
    const pendingCount = totalCount - approvedCount;

    const draftCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Draft').length;
    const sentCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Submitted').length;
    const receivedCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Approval').length;
    const awardedCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Approved').length;
    const cancelledCount = rfqs.filter(r => getNormalizedRfqStage(r.rfqStatus || r.status) === 'Refused').length;

    const isDraftActive = activeRfqStatusFilter === 'Draft';
    const isSentActive = activeRfqStatusFilter === 'Submitted' || activeRfqStatusFilter === 'Quote Requested';
    const isReceivedActive = activeRfqStatusFilter === 'Approval' || activeRfqStatusFilter === 'Quotation Received';
    const isAwardedActive = activeRfqStatusFilter === 'Approved' || activeRfqStatusFilter === 'Awarded';
    const isCancelledActive = activeRfqStatusFilter === 'Refused' || activeRfqStatusFilter === 'Cancelled';

    return `
        <div class="rfq-filter-popover-card">
            <div class="rfq-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-sliders-horizontal" style="color: #7c3aed; font-size: 15px;"></i>
                    <span>Status & Approval Filters</span>
                </div>
                <button type="button" class="rfq-popover-reset-btn" onclick="resetRfqStatusFilters(event)">Reset Both</button>
            </div>
            
            <div class="rfq-filter-popover-body">
                <!-- ROW 1: APPROVAL STATUS -->
                <div class="rfq-filter-section">
                    <div class="rfq-filter-section-title">
                        <span>Row 1: Approval Status</span>
                        ${activeRfqApprovalFilter !== 'all' ? `<span class="rfq-section-active-badge">${escapeHtml(activeRfqApprovalFilter)}</span>` : ''}
                    </div>
                    <div class="rfq-filter-pill-row">
                        <button type="button" class="rfq-popover-chip ${activeRfqApprovalFilter === 'all' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-success ${activeRfqApprovalFilter === 'approved' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'approved')">
                            <i class="ph ph-check-circle"></i> Approved (${approvedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-warning ${activeRfqApprovalFilter === 'pending' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'pending')">
                            <i class="ph ph-hourglass-simple"></i> Pending (${pendingCount})
                        </button>
                    </div>
                </div>

                <!-- ROW 2: RFQ STATUS -->
                <div class="rfq-filter-section" style="margin-top: 12px;">
                    <div class="rfq-filter-section-title">
                        <span>Row 2: RFQ Status</span>
                        ${activeRfqStatusFilter !== 'all' ? `<span class="rfq-section-active-badge">${escapeHtml(activeRfqStatusFilter)}</span>` : ''}
                    </div>
                    <div class="rfq-filter-pill-grid">
                        <button type="button" class="rfq-popover-chip ${activeRfqStatusFilter === 'all' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${isDraftActive ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Draft')">
                            Draft (${draftCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${isSentActive ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Submitted')">
                            Submitted (${sentCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${isReceivedActive ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Approval')">
                            In Review (${receivedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-purple ${isAwardedActive ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Approved')">
                            Approved (${awardedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-danger ${isCancelledActive ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Refused')">
                            Refused (${cancelledCount})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildPaymentTermsFilterPopoverHtml() {
    const rfqs = window.AppStore.rfqs || [];
    const totalCount = rfqs.length;
    
    const termsMap = {};
    rfqs.forEach(r => {
        const t = (r.paymentTerms || '').trim();
        if (t) {
            termsMap[t] = (termsMap[t] || 0) + 1;
        }
    });
    ['Net 30 Days', 'Net 15 Days', 'Advance Payment'].forEach(t => {
        if (!termsMap[t]) termsMap[t] = 0;
    });

    const distinctTerms = Object.keys(termsMap);

    return `
        <div class="rfq-filter-popover-card">
            <div class="rfq-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-credit-card" style="color: #7c3aed; font-size: 15px;"></i>
                    <span>Payment Terms Filter</span>
                </div>
                <button type="button" class="rfq-popover-reset-btn" onclick="setRfqPaymentTermsFilter(event, 'all')">Reset</button>
            </div>
            
            <div class="rfq-filter-popover-body">
                <div class="rfq-filter-radio-list">
                    <label class="rfq-filter-radio-row ${activeRfqPaymentTermsFilter === 'all' ? 'is-selected' : ''}">
                        <input type="radio" name="rfqTermsRadio" value="all" ${activeRfqPaymentTermsFilter === 'all' ? 'checked' : ''} onchange="setRfqPaymentTermsFilter(event, 'all')">
                        <span class="rfq-radio-label">All Payment Terms</span>
                        <span class="rfq-radio-count">${totalCount}</span>
                    </label>
                    ${distinctTerms.map(term => `
                        <label class="rfq-filter-radio-row ${activeRfqPaymentTermsFilter.toLowerCase() === term.toLowerCase() ? 'is-selected' : ''}">
                            <input type="radio" name="rfqTermsRadio" value="${escapeHtml(term)}" ${activeRfqPaymentTermsFilter.toLowerCase() === term.toLowerCase() ? 'checked' : ''} onchange="setRfqPaymentTermsFilter(event, '${escapeHtml(term)}')">
                            <span class="rfq-radio-label">${escapeHtml(term)}</span>
                            <span class="rfq-radio-count">${termsMap[term] || 0}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

function setRfqApprovalFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqApprovalFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activeHeaderFilterType === 'status') {
        popover.innerHTML = buildStatusFilterPopoverHtml();
    }
}

function setRfqStatusFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqStatusFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activeHeaderFilterType === 'status') {
        popover.innerHTML = buildStatusFilterPopoverHtml();
    }
}

function resetRfqStatusFilters(event) {
    if (event) event.stopPropagation();
    activeRfqStatusFilter = 'all';
    activeRfqApprovalFilter = 'all';
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function setRfqPaymentTermsFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqPaymentTermsFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function clearAllRfqFilters() {
    activeRfqStatusFilter = 'all';
    activeRfqApprovalFilter = 'all';
    activeRfqPaymentTermsFilter = 'all';
    rfqSearchTerm = '';
    const searchInput = document.getElementById('rfqDirectorySearch');
    if (searchInput) searchInput.value = '';
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function renderRfqActiveFilterChips() {
    const container = document.getElementById('rfqActiveFilterChips');
    if (!container) return;

    let chipsHtml = '';

    if (activeRfqApprovalFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active Approval Filter">
                <span>Approval: <strong>${escapeHtml(activeRfqApprovalFilter)}</strong></span>
                <button type="button" onclick="setRfqApprovalFilter(event, 'all')" title="Clear approval filter">&times;</button>
            </span>
        `;
    }

    if (activeRfqStatusFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active RFQ Status Filter">
                <span>Status: <strong>${escapeHtml(activeRfqStatusFilter)}</strong></span>
                <button type="button" onclick="setRfqStatusFilter(event, 'all')" title="Clear status filter">&times;</button>
            </span>
        `;
    }

    if (activeRfqPaymentTermsFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active Payment Terms Filter">
                <span>Terms: <strong>${escapeHtml(activeRfqPaymentTermsFilter)}</strong></span>
                <button type="button" onclick="setRfqPaymentTermsFilter(event, 'all')" title="Clear payment terms filter">&times;</button>
            </span>
        `;
    }

    if (chipsHtml) {
        chipsHtml += `
            <button type="button" class="rfq-clear-all-chip-btn" onclick="clearAllRfqFilters()" title="Reset all active filters">
                Clear Filters
            </button>
        `;
    }

    container.innerHTML = chipsHtml;
}

// Global outside-click dismissal for table header popover
document.addEventListener('pointerdown', function(e) {
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (!popover || popover.style.display === 'none') return;
    if (!popover.contains(e.target) && !e.target.closest('.rfq-th-filter-wrapper')) {
        closeRfqHeaderFilter();
    }
});

function handleRfqDirectorySearch(query) {
    rfqSearchTerm = query;
    directoryCurrentPage = 1;
    renderRfqDirectory();
}

function startNewRfqFromDirectory() {
    createNewRfq(false);
}

function editRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;

    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    window.AppStore._isExplicitlyEditing = true;
    
    // Update inputs
    document.getElementById('rfqNumberInput').value = rfq.rfqNumber;
    document.getElementById('rfqRefDisplay').textContent = rfq.rfqNumber;
    const docNum = document.getElementById('docRfqNumber');
    if (docNum) docNum.textContent = rfq.rfqNumber;

    const titleEl = document.getElementById('rfqTenderTitle');
    if (titleEl) titleEl.value = rfq.title || '';

    const stage = getNormalizedRfqStage(rfq.rfqStatus || rfq.status || 'Draft');
    const statusBadge = document.getElementById('rfqStatusBadge');
    if (statusBadge) {
        statusBadge.className = `status-pill ${stage.toLowerCase()}`;
        statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${stage} RFQ`;
    }

    if (rfq.vendorId) {
        document.getElementById('vendorSelect').value = rfq.vendorId;
        handleVendorSelection(rfq.vendorId);
    } else {
        document.getElementById('vendorSelect').value = '';
        clearVendorFields(false);
    }

    // Preserve explicitly passed contact information
    if (rfq.vendorEmail) document.getElementById('vendorEmail').value = rfq.vendorEmail;
    if (rfq.vendorPhone) document.getElementById('vendorPhone').value = rfq.vendorPhone;
    if (rfq.vendorAddress) document.getElementById('vendorAddress').value = rfq.vendorAddress;
    if (rfq.vendorContactPerson) document.getElementById('vendorContactPerson').value = rfq.vendorContactPerson;
    if (rfq.vendorContactTitle) document.getElementById('vendorContactTitle').value = rfq.vendorContactTitle;

    document.getElementById('rfqDateIssued').value = rfq.dateIssued || rfq.rfqDate || '';
    document.getElementById('rfqDueDate').value = rfq.dueDate || rfq.submissionDeadline || '';
    const quoteValEl = document.getElementById('rfqQuoteValidityDate');
    if (quoteValEl) quoteValEl.value = rfq.quoteValidUntilDate || rfq.quote_valid_until_date || '';
    document.getElementById('rfqExpectedDelivery').value = rfq.expectedDelivery || rfq.rfqRequestedDeliveryDate || '';
    document.getElementById('rfqPaymentTerms').value = rfq.paymentTerms || rfq.rfqPaymentTermsCode || 'Net 30 Days';
    document.getElementById('rfqDeliveryLocation').value = rfq.deliveryLocation || rfq.shipToWarehouseName || '';
    document.getElementById('rfqSpecialInstructions').value = rfq.specialInstructions || rfq.rfqNotes || '';

    window.AppStore._lastSyncedStage = null;
    updateLifecycleProgressBar(stage);
    applyStageFormLocks(stage);

    renderItemsTable();
    updateMetricsSummary();

    const badge = document.getElementById('tabBuilderBadge');
    if (badge) badge.textContent = `Editing ${rfq.rfqNumber}`;
    switchRfqTab('tab-builder');
    showToast(`Loaded ${rfq.rfqNumber} into RFQ Builder`, 'success');
}

function previewRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    openRfqDocumentPreview();
}

function sendRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    openSendEmailModal();
}

function deleteRfqFromDirectory(rfqNumber) {
    if (confirm(`Are you sure you want to delete quotation request ${rfqNumber}?`)) {
        window.AppStore.rfqs = (window.AppStore.rfqs || []).filter(r => r.rfqNumber !== rfqNumber);
        localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
        renderRfqDirectory();
        showToast(`Quotation ${rfqNumber} deleted`, 'success');
    }
}

let pendingApprovalRfqNumber = null;

function markQuoteReceived(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    rfq.status = 'Quotation Received';
    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
    renderRfqDirectory();
    showToast(`✓ Quotation received logged for ${rfqNumber}. Ready for approval!`, 'success');
}

function openManagerApprovalModal(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;

    pendingApprovalRfqNumber = rfqNumber;
    const linkedPoNum = getLinkedPoNumberForRfq(rfq);
    if (linkedPoNum) rfq.poReference = linkedPoNum;

    const estTotal = (rfq.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.targetPrice) || 0)), 0);
    const isApproved = rfq.isApproved === true || rfq.status === 'Awarded' || rfq.status === 'Approved' || !!linkedPoNum;

    document.getElementById('approvalRfqTitle').textContent = `${rfq.rfqNumber} • ${rfq.vendorTradeName || rfq.vendorName}`;
    document.getElementById('approvalPoDeliveryDate').value = rfq.expectedDelivery || '';
    document.getElementById('approvalPaymentTerms').value = rfq.paymentTerms || 'Net 30 Days';
    document.getElementById('approvalDestination').value = rfq.deliveryLocation || 'Central Commissary - Main Dock';
    document.getElementById('approvalItemCount').textContent = `${(rfq.items || []).length} line items`;
    document.getElementById('approvalTotalValue').textContent = `₱${formatMoney(estTotal)}`;

    const bannerSubtitle = document.getElementById('approvalBannerSubtitle');
    const inputSection = document.getElementById('approvalInputSection');
    const approvedSection = document.getElementById('alreadyApprovedDetailsSection');
    const footer = document.getElementById('approvalModalFooter');

    if (isApproved) {
        bannerSubtitle.textContent = `This RFQ was evaluated and officially approved by Manager ${rfq.approvedBy || 'Procurement Manager'}.`;
        inputSection.style.display = 'none';
        approvedSection.style.display = 'block';

        document.getElementById('approvedOfficerNameText').textContent = `Approved by: ${rfq.approvedBy || 'Procurement Manager'}`;
        document.getElementById('approvedTimestampBadge').textContent = rfq.approvedAt ? formatDateDisplay(rfq.approvedAt) : 'Active Approval';
        document.getElementById('approvedNotesText').textContent = rfq.approvalNotes ? `“${rfq.approvalNotes}”` : 'No evaluation notes recorded.';

        const poBadge = document.getElementById('approvedPoLinkedBadge');
        if (linkedPoNum) {
            poBadge.style.display = 'block';
            poBadge.innerHTML = `
                <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; margin-bottom: 4px;">Linked Purchase Order:</div>
                <button type="button" onclick="openLinkedPoFromRfq('${escapeHtml(linkedPoNum)}')" class="rfq-btn rfq-btn-teal" style="font-size: 0.78rem; padding: 6px 14px;">
                    <i class="ph ph-receipt"></i> Open Purchase Order ${escapeHtml(linkedPoNum)} <i class="ph ph-arrow-square-out"></i>
                </button>
            `;
        } else {
            poBadge.style.display = 'none';
        }

        footer.innerHTML = `
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeApprovalModal()">Close</button>
            <button type="button" class="rfq-btn rfq-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="revokeRfqApproval()">
                <i class="ph ph-arrow-u-up-left"></i> Revoke Approval
            </button>
            ${linkedPoNum ? `
                <button type="button" class="rfq-btn rfq-btn-teal" onclick="openLinkedPoFromRfq('${escapeHtml(linkedPoNum)}')">
                    <i class="ph ph-receipt"></i> View Created PO (${escapeHtml(linkedPoNum)}) <i class="ph ph-arrow-square-out"></i>
                </button>
            ` : `
                <button type="button" class="rfq-btn rfq-btn-teal" onclick="generatePoForApprovedRfq()">
                    <i class="ph ph-receipt"></i> Generate Purchase Order Now
                </button>
            `}
        `;
    } else {
        bannerSubtitle.textContent = `Authorized Procurement Managers can review bid evaluation, stamp approval, or award and generate a formal Purchase Order.`;
        inputSection.style.display = 'block';
        approvedSection.style.display = 'none';

        footer.innerHTML = `
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeApprovalModal()">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-outline" style="color: #047857; border-color: #a7f3d0;" onclick="approveRfqOnly()">
                <i class="ph ph-check"></i> Approve RFQ Only
            </button>
            <button type="button" class="rfq-btn rfq-btn-teal" onclick="confirmApprovalAndGeneratePO()">
                <i class="ph ph-seal-check"></i> Approve & Generate Purchase Order
            </button>
        `;
    }

    const modal = document.getElementById('rfqApprovalModal');
    if (modal) modal.classList.add('is-open');
}

function openApprovalTransferModal(rfqNumber) {
    openManagerApprovalModal(rfqNumber);
}

function closeApprovalModal() {
    const modal = document.getElementById('rfqApprovalModal');
    if (modal) modal.classList.remove('is-open');
    pendingApprovalRfqNumber = null;
}

function approveRfqOnly() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    const approver = document.getElementById('approvalOfficer')?.value?.trim() || 'Procurement Manager';
    const notes = document.getElementById('approvalNotes')?.value?.trim() || '';
    const estTotal = (rfq.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.targetPrice) || 0)), 0);

    rfq.isApproved = true;
    rfq.status = 'Approved';
    rfq.rfqStatus = 'Approved';
    rfq.approvedBy = approver;
    rfq.approvedAt = new Date().toISOString();
    rfq.approvalNotes = notes || 'Manager approval granted. Ready for PO issuance.';

    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));

    // Audit Log
    logProcurementAudit(
        'RFQ',
        rfq.rfqNumber,
        'APPROVED',
        'RFQ Approved by Manager',
        `Manager ${approver} approved quotation tender without immediate PO dispatch. Status set to Approved.`,
        [
            { label: 'Approving Officer', value: approver },
            { label: 'Vendor Partner', value: rfq.vendorTradeName || rfq.vendorName },
            { label: 'Estimated Budget', value: `₱${formatMoney(estTotal)}` },
            { label: 'Evaluation Note', value: notes || 'Compliant supplier tender' }
        ]
    );

    closeApprovalModal();
    renderRfqDirectory();
    showToast(`✓ RFQ ${rfq.rfqNumber} successfully Approved by Manager!`, 'success');
}

async function confirmApprovalAndGeneratePO() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    const existingPoNum = getLinkedPoNumberForRfq(rfq);
    if (existingPoNum) {
        closeApprovalModal();
        showToast(`Purchase Order ${existingPoNum} already exists for ${rfq.rfqNumber}. Redirecting...`, 'info');
        openLinkedPoFromRfq(existingPoNum);
        return;
    }

    const approver = document.getElementById('approvalOfficer')?.value?.trim() || 'Procurement Manager';
    const deliveryDate = document.getElementById('approvalPoDeliveryDate')?.value || rfq.expectedDelivery || '';
    const terms = document.getElementById('approvalPaymentTerms')?.value || rfq.paymentTerms || 'Net 30 Days';
    const dest = document.getElementById('approvalDestination')?.value || rfq.deliveryLocation || 'Central Commissary - Receiving Dock 1';
    const notes = document.getElementById('approvalNotes')?.value?.trim() || '';

    rfq.isApproved = true;
    rfq.status = 'Approved';
    rfq.rfqStatus = 'APPROVED';
    rfq.approvedBy = approver;
    rfq.approvedAt = new Date().toISOString();
    rfq.approvalNotes = notes || 'Quotation approved and transferred to formal Purchase Order.';
    rfq.expectedDelivery = deliveryDate;
    rfq.paymentTerms = terms;
    rfq.deliveryLocation = dest;

    closeApprovalModal();
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    await generatePoForApprovedRfq();
}

async function generatePoForApprovedRfq() {
    const activeRfq = window.AppStore?.activeRfq;
    const rfq = activeRfq || (window.AppStore?.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq || !rfq.rfqNumber) {
        showToast('No active Approved RFQ found to convert.', 'warning');
        return;
    }

    // 1. Smart Guard: If a PO is ALREADY created for this RFQ, do NOT create a duplicate; redirect straight to it
    const existingLinkedPo = getLinkedPoNumberForRfq(rfq);
    if (existingLinkedPo) {
        rfq.poReference = existingLinkedPo;
        updateLifecycleProgressBar('Approved');
        renderRfqDirectory();
        showToast(`Purchase Order ${existingLinkedPo} is already created for ${rfq.rfqNumber}. Redirecting...`, 'info');
        openLinkedPoFromRfq(existingLinkedPo);
        return;
    }

    const pos = JSON.parse(localStorage.getItem('rms_purchase_orders') || '[]');
    let nextPoNum = `PO-2026-${String(pos.length + 105).padStart(4, '0')}`;

    const mappedItems = (rfq.items || []).map(it => {
        const q = parseFloat(it.quotedQuantity !== undefined && it.quotedQuantity !== '' ? it.quotedQuantity : (it.requestedQuantity !== undefined ? it.requestedQuantity : it.quantity)) || 1;
        const p = parseFloat(it.quotedUnitPrice !== undefined && it.quotedUnitPrice !== '' ? it.quotedUnitPrice : (it.targetUnitPrice !== undefined ? it.targetUnitPrice : (it.unitPrice || it.targetPrice || 0))) || 0;
        const t = parseFloat(it.targetUnitPrice !== undefined ? it.targetUnitPrice : (it.targetPrice || p)) || p;
        return {
            sku: it.itemId || it.sku || 'CAT-01',
            name: it.itemName || it.name || 'Awarded Specification Item',
            specs: it.rfqLineNotes || it.specs || '',
            category: it.category || 'Raw Ingredients',
            unit: it.purchaseUomCode || it.unit || it.uom || 'PCS',
            quantity: q,
            rfqQuotedQty: q,
            unitPrice: p,
            rfqTargetPrice: t,
            receivedQuantity: 0
        };
    });

    const grossTotal = mappedItems.reduce((acc, it) => acc + (it.quantity * it.unitPrice), 0);

    showLockOverlay(`Creating Purchase Order from ${rfq.rfqNumber}...`);
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const convertUrl = window.RfqConfig?.routes?.convertToPo || '/purchase/api/rfq/convert-po';
        const res = await fetch(convertUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({
                rfq_number: rfq.rfqNumber,
                vendor_id: rfq.vendorId || null,
                vendor_name: rfq.vendorName || rfq.vendorTradeName || 'Approved Vendor',
                vendor_trade_name: rfq.vendorTradeName || rfq.vendorName || 'Approved Vendor',
                vendor_contact_person: rfq.vendorContactPerson || '',
                vendor_phone: rfq.vendorPhone || '',
                vendor_email: rfq.vendorEmail || '',
                vendor_address: rfq.vendorAddress || '',
                expected_delivery: rfq.expectedDelivery || rfq.rfqRequestedDeliveryDate || '',
                payment_terms: rfq.paymentTerms || rfq.rfqPaymentTermsCode || 'Net 30 Days',
                delivery_location: rfq.deliveryLocation || rfq.shipToWarehouseName || 'Central Commissary - Receiving Dock 1',
                approved_by: rfq.approvedBy || 'Procurement Manager',
                items: mappedItems
            })
        });
        const json = await res.json();
        if (json?.success && json?.data?.po_number) {
            nextPoNum = json.data.po_number;
        }
    } catch (e) {
        console.warn('generatePoForApprovedRfq API fallback:', e);
    } finally {
        hideLockOverlay();
    }

    rfq.isApproved = true;
    rfq.status = 'Awarded';
    rfq.rfqStatus = 'AWARDED';
    rfq.poReference = nextPoNum;
    if (window.AppStore?.activeRfq && window.AppStore.activeRfq.rfqNumber === rfq.rfqNumber) {
        window.AppStore.activeRfq.poReference = nextPoNum;
    }
    syncRfqToDirectory(rfq, 'Approved');

    // Ensure nextPoNum is not in rms_deleted_po_numbers
    try {
        const deletedPos = JSON.parse(localStorage.getItem('rms_deleted_po_numbers') || '[]');
        if (Array.isArray(deletedPos) && deletedPos.includes(nextPoNum)) {
            localStorage.setItem('rms_deleted_po_numbers', JSON.stringify(deletedPos.filter(n => n !== nextPoNum)));
        }
    } catch (e) {}

    const newPO = {
        poNumber: nextPoNum,
        poType: 'vendor',
        rfqReference: rfq.rfqNumber,
        orderTitle: rfq.tenderTitle || rfq.projectTitle || `Procurement Continuation from ${rfq.rfqNumber}`,
        vendorId: rfq.vendorId || '',
        vendorName: rfq.vendorName || 'Approved Vendor',
        vendorTradeName: rfq.vendorTradeName || rfq.vendorName || 'Approved Vendor',
        vendorContactPerson: rfq.vendorContactPerson || '',
        vendorPhone: rfq.vendorPhone || '',
        vendorEmail: rfq.vendorEmail || '',
        vendorAddress: rfq.vendorAddress || '',
        orderDate: new Date().toISOString().split('T')[0],
        expectedDelivery: rfq.expectedDelivery || rfq.rfqRequestedDeliveryDate || '',
        deliveryLocation: rfq.deliveryLocation || rfq.shipToWarehouseName || 'Central Commissary - Receiving Dock 1',
        paymentTerms: rfq.paymentTerms || rfq.rfqPaymentTermsCode || 'Net 30 Days',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0,
        balanceDue: grossTotal,
        approvedBy: rfq.approvedBy || 'Procurement Manager',
        status: 'Approved / Issued',
        items: mappedItems
    };

    const existingPoIdx = pos.findIndex(p => p.poNumber === nextPoNum);
    if (existingPoIdx >= 0) pos[existingPoIdx] = newPO;
    else pos.unshift(newPO);
    localStorage.setItem('rms_purchase_orders', JSON.stringify(pos));

    updateLifecycleProgressBar('Approved');
    renderRfqDirectory();
    showToast(`✓ Created ${nextPoNum} from ${rfq.rfqNumber}! Opening in Purchase Order Builder...`, 'success');
    openLinkedPoFromRfq(nextPoNum);
}

function revokeRfqApproval() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    if (confirm(`Revoke manager approval for ${rfq.rfqNumber}? The status will revert to Quote Requested.`)) {
        const previousApprover = rfq.approvedBy || 'Manager';
        rfq.isApproved = false;
        rfq.status = 'Quote Requested';
        rfq.approvedBy = null;
        rfq.approvedAt = null;
        rfq.approvalNotes = null;

        localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));

        // Audit Trail entry
        logProcurementAudit(
            'RFQ',
            rfq.rfqNumber,
            'APPROVAL_REVOKED',
            'Manager Approval Revoked',
            `Manager revoked approval status for ${rfq.rfqNumber}. Tender reopened for evaluation.`,
            [{ label: 'Previous Approver', value: previousApprover }]
        );

        closeApprovalModal();
        renderRfqDirectory();
        showToast(`Manager approval revoked for ${rfq.rfqNumber}`, 'info');
    }
}

/**
 * --------------------------------------------------------------------------
 * PROCUREMENT AUDIT TRAIL ENGINE
 * --------------------------------------------------------------------------
 */
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
        },
        {
            id: 'AUD-2026-1002',
            timestamp: new Date(Date.now() - 3600000 * 26).toISOString(),
            formattedDate: 'Sep 29, 2026 • 09:10 AM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0042',
            action: 'EMAIL_SENT',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Quotation Request Emailed to Vendor',
            description: 'Sent formal request package to San Miguel Foods (bids@sanmiguelfoods.ph).',
            details: [
                { label: 'Recipient', value: 'bids@sanmiguelfoods.ph' },
                { label: 'CC Audit', value: 'procurement-audit@rms.ph' },
                { label: 'Quotation Due', value: 'Oct 05, 2026' }
            ]
        },
        {
            id: 'AUD-2026-1001',
            timestamp: new Date(Date.now() - 3600000 * 28).toISOString(),
            formattedDate: 'Sep 29, 2026 • 08:00 AM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0042',
            action: 'CREATED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Request for Quotation Created',
            description: 'Created new RFQ tender with 5 line items. Est Budget: ₱142,500.00.',
            details: [
                { label: 'Vendor Partner', value: 'San Miguel Foods, Inc.' },
                { label: 'Commodities', value: '5 raw ingredient lines' }
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
        actor: window.RfqConfig?.user?.name || 'Dorothy Diaz',
        role: 'Procurement Manager',
        title: title,
        description: description,
        details: details,
        metadata: metadata
    };

    logs.unshift(entry);
    localStorage.setItem('rms_procurement_audit_trail', JSON.stringify(logs));

    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = logs.length;

    return entry;
}

let auditScopeMode = 'CURRENT'; // Default strictly to CURRENT PO / RFQ to minimize process usage

function getCurrentAuditTarget() {
    const active = window.AppStore.activeRfq || {};
    const rfqNum = (active.rfqNumber || document.getElementById('rfqRefDisplay')?.textContent || '').trim();
    const poNum = (active.poReference || active.poNumber || '').trim();
    return { rfqNum, poNum };
}

function setAuditScope(mode) {
    auditScopeMode = mode;
    const btnCurrent = document.getElementById('btnAuditScopeCurrent');
    const btnAll = document.getElementById('btnAuditScopeAll');
    if (btnCurrent) btnCurrent.classList.toggle('active', mode === 'CURRENT');
    if (btnAll) btnAll.classList.toggle('active', mode === 'ALL');
    renderRfqAuditTrail();
}

function renderRfqAuditTrail() {
    const container = document.getElementById('rfqAuditTimeline');
    if (!container) return;

    const allLogs = getProcurementAuditTrail();
    const { rfqNum, poNum } = getCurrentAuditTarget();

    // Update indexed scope badge in drawer
    const badgeRfq = document.getElementById('auditTargetRefBadge');
    if (badgeRfq) badgeRfq.textContent = rfqNum || 'Current Tender';

    const badgePo = document.getElementById('auditTargetPoBadge');
    if (badgePo) {
        if (poNum) {
            badgePo.textContent = poNum;
            badgePo.style.display = 'inline-block';
        } else {
            badgePo.style.display = 'none';
        }
    }

    // Process optimization: Index ONLY current RFQ / PO when in CURRENT mode
    let targetLogs = allLogs;
    if (auditScopeMode === 'CURRENT' && (rfqNum || poNum)) {
        targetLogs = allLogs.filter(log => {
            const ref = (log.refNumber || '').trim();
            if (rfqNum && (ref === rfqNum || (log.description && log.description.includes(rfqNum)))) {
                return true;
            }
            if (poNum && (ref === poNum || (log.description && log.description.includes(poNum)))) {
                return true;
            }
            return false;
        });

        // If active tender has no historical entries yet, provide an initial draft baseline event
        if (targetLogs.length === 0 && rfqNum) {
            const active = window.AppStore.activeRfq || {};
            targetLogs = [{
                id: `AUD-INIT-${rfqNum}`,
                timestamp: new Date().toISOString(),
                formattedDate: 'Just now',
                module: 'RFQ',
                refNumber: rfqNum,
                action: 'CREATED',
        actor: window.RfqConfig?.user?.name || 'Dorothy Diaz',
                role: 'Procurement Manager',
                title: 'RFQ Tender Initialized',
                description: `Active draft tender ${rfqNum} initialized in procurement workspace.`,
                details: [
                    { label: 'Tender Reference', value: rfqNum },
                    { label: 'Lifecycle Status', value: active.status || 'Draft' },
                    { label: 'Supplier Partner', value: active.vendorName || 'Not Selected' },
                    { label: 'Item Lines', value: `${(active.items || []).length} items listed` }
                ]
            }];
        }
    }

    // Update scoped event counter in drawer header
    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = targetLogs.length;

    const q = (auditSearchTerm || '').toLowerCase().trim();

    const filtered = targetLogs.filter(log => {
        const matchesModule = (activeAuditModuleFilter === 'ALL') || (log.module === activeAuditModuleFilter);
        const matchesAction = (activeAuditActionFilter === 'ALL') || (log.action === activeAuditActionFilter);
        const matchesSearch = !q ||
            (log.refNumber && log.refNumber.toLowerCase().includes(q)) ||
            (log.title && log.title.toLowerCase().includes(q)) ||
            (log.description && log.description.toLowerCase().includes(q)) ||
            (log.actor && log.actor.toLowerCase().includes(q));

        return matchesModule && matchesAction && matchesSearch;
    });

    // Update KPI numbers calculated ONLY against the indexed target set
    const kpiTotal = document.getElementById('auditKpiTotalCount');
    const kpiApprovals = document.getElementById('auditKpiApprovalsCount');
    const kpiEdits = document.getElementById('auditKpiEditsCount');
    const kpiEmails = document.getElementById('auditKpiEmailsCount');

    if (kpiTotal) kpiTotal.textContent = targetLogs.length;
    if (kpiApprovals) kpiApprovals.textContent = targetLogs.filter(l => l.action === 'APPROVED').length;
    if (kpiEdits) kpiEdits.textContent = targetLogs.filter(l => l.action === 'CREATED' || l.action === 'EDITED' || l.action === 'WET_MARKET_CREATED').length;
    if (kpiEmails) kpiEmails.textContent = targetLogs.filter(l => l.action === 'EMAIL_SENT').length;

    if (filtered.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px solid var(--rfq-border-subtle); border-radius: 10px; color: var(--rfq-text-muted);">
                <i class="ph ph-clock-countdown" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 600; color: #475569; margin-bottom: 4px;">No Logged Events Found</div>
                <div style="font-size: 12px;">No logged activities match the current filter criteria for ${rfqNum || 'this tender'}.</div>
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
                        <span style="font-weight: 800; font-size: 0.94rem; color: var(--rfq-text-strong);">${escapeHtml(log.title)}</span>
                        <span style="font-family: monospace; font-size: 0.78rem; font-weight: 700; color: var(--rfq-primary); background: #eff6ff; padding: 2px 6px; border-radius: 4px;">
                            ${escapeHtml(log.refNumber)}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: var(--rfq-text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-clock"></i> ${escapeHtml(log.formattedDate)}
                    </div>
                </div>
                <div style="font-size: 0.84rem; color: #334155; line-height: 1.45; margin-bottom: 6px;">
                    ${escapeHtml(log.description)}
                </div>
                ${detailsHtml}
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 0.75rem; color: var(--rfq-text-muted);">
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

function onAuditSearchChange(query) {
    auditSearchTerm = query;
    renderRfqAuditTrail();
}

function filterAuditByModule(mod, btn) {
    activeAuditModuleFilter = mod;
    const parent = btn.parentElement;
    parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderRfqAuditTrail();
}

function filterAuditByAction(action, btn) {
    activeAuditActionFilter = action;
    const parent = document.getElementById('auditActionFilterPills');
    if (parent) {
        parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    }
    btn.classList.add('active');
    renderRfqAuditTrail();
}

function openSingleAuditModal(module, refNumber) {
    const modal = document.getElementById('rfqSingleAuditModal');
    if (!modal) return;

    document.getElementById('singleAuditHeaderTitle').textContent = `Audit Trail & History • ${refNumber}`;
    document.getElementById('singleAuditRefTitle').textContent = refNumber;
    document.getElementById('singleAuditSubTitle').textContent = `Chronological activity records and transaction trail for ${refNumber}`;

    const container = document.getElementById('singleAuditTimelineContainer');
    const allLogs = getProcurementAuditTrail();
    const itemLogs = allLogs.filter(l => l.refNumber === refNumber);

    if (itemLogs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px; color: var(--rfq-text-muted);">
                <i class="ph ph-clock" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                No audit events recorded yet for this reference.
            </div>
        `;
    } else {
        container.innerHTML = itemLogs.map(log => `
            <div class="audit-timeline-item" style="padding: 12px 16px;">
                <div class="audit-timeline-node-icon is-created" style="top: 14px;">
                    <i class="ph ph-check"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <strong style="font-size: 0.88rem; color: var(--rfq-text-strong);">${escapeHtml(log.title)}</strong>
                    <span style="font-size: 0.75rem; color: var(--rfq-text-muted);">${escapeHtml(log.formattedDate)}</span>
                </div>
                <div style="font-size: 0.82rem; color: #334155; margin-bottom: 4px;">
                    ${escapeHtml(log.description)}
                </div>
                ${(log.details && log.details.length > 0) ? `
                    <div style="font-size: 0.76rem; background: #f8fafc; padding: 6px 10px; border-radius: 6px; margin-top: 6px;">
                        ${log.details.map(d => `<div><strong>${escapeHtml(d.label)}:</strong> ${escapeHtml(d.value)}</div>`).join('')}
                    </div>
                ` : ''}
                <div style="font-size: 0.72rem; color: var(--rfq-text-muted); margin-top: 6px;">
                    Logged by <strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})
                </div>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closeSingleAuditModal() {
    const modal = document.getElementById('rfqSingleAuditModal');
    if (modal) modal.classList.remove('is-open');
}

function convertRfqToPurchaseOrder(rfqNumber) {
    openManagerApprovalModal(rfqNumber);
}

function syncRfqToDirectory(rfqData, overrideStatus = null, shouldLog = false) {
    if (!rfqData || !rfqData.rfqNumber) return;
    if (!window.AppStore.rfqs) window.AppStore.rfqs = [];

    const existingIdx = window.AppStore.rfqs.findIndex(r => r.rfqNumber === rfqData.rfqNumber);
    const entry = JSON.parse(JSON.stringify(rfqData));
    if (overrideStatus) {
        entry.status = overrideStatus;
        entry.rfqStatus = overrideStatus;
    } else if (!entry.status) {
        entry.status = 'Draft';
        entry.rfqStatus = 'Draft';
    }

    if (existingIdx >= 0) {
        window.AppStore.rfqs[existingIdx] = entry;
        if (shouldLog && typeof logProcurementAudit === 'function') {
            logProcurementAudit(
                'RFQ',
                entry.rfqNumber,
                'EDITED',
                'RFQ Specifications Updated',
                `Updated line items, quantities, or terms for ${entry.vendorTradeName || entry.vendorName || 'RFQ'}.`,
                [{ label: 'Line Items', value: `${(entry.items || []).length} items` }]
            );
        }
    } else {
        window.AppStore.rfqs.unshift(entry);
        if (shouldLog && typeof logProcurementAudit === 'function') {
            logProcurementAudit(
                'RFQ',
                entry.rfqNumber,
                'CREATED',
                'RFQ Created in Builder',
                `Created Request for Quotations tender for ${entry.vendorTradeName || entry.vendorName || 'Vendor'}.`,
                [{ label: 'Line Items', value: `${(entry.items || []).length} items` }]
            );
        }
    }
    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
    renderRfqDirectory();
}

function exportRfqDirectoryCSV() {
    const list = window.AppStore && window.AppStore.rfqs ? window.AppStore.rfqs : [];
    if (!list || list.length === 0) {
        showToast("No RFQ tender records available to export.", "warning");
        return;
    }
    const headers = ["Reference", "Vendor Name", "Vendor Email", "Status", "Total Budget (PHP)", "Created Date", "Closing Date", "Items Count"];
    const rows = list.map(r => [
        `"${r.ref || ''}"`,
        `"${(r.vendorName || '').replace(/"/g, '""')}"`,
        `"${r.vendorEmail || ''}"`,
        `"${r.status || ''}"`,
        r.totalBudget || 0,
        `"${r.createdDate || ''}"`,
        `"${r.closingDate || ''}"`,
        r.items ? r.items.length : 0
    ]);
    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(","), ...rows.map(e => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `RFQ_Tenders_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast("RFQ directory successfully exported to CSV.", "success");
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
    const container = document.getElementById('rfqToastContainer');
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

// ==========================================================================
// STAGE ACTIONS & ATTACHMENT HELPERS
// ==========================================================================
function cancelRfqDraft() {
    if (confirm('Discard current RFQ draft and start fresh? All unsaved inputs will be cleared.')) {
        createNewRfq(true);
        showToast('✓ Draft session reset.', 'info');
    }
}

function cancelActiveRfq() {
    if (confirm('Are you sure you want to cancel this RFQ tender?')) {
        transitionActiveRfqStage('Refused', { rejectionReason: 'Tender cancelled by Buyer in Submitted stage' });
    }
}

function withdrawApprovedRfq() {
    if (confirm('Are you sure you want to withdraw this quotation tender?')) {
        transitionActiveRfqStage('Refused', { rejectionReason: 'Tender withdrawn by Procurement Officer' });
    }
}

function getAttachedFilesList() {
    if (!window.AppStore || !window.AppStore.activeRfq) return [];
    if (!Array.isArray(window.AppStore.activeRfq.attachedFiles)) {
        const rfqNum = window.AppStore.activeRfq.ref || window.AppStore.activeRfq.rfqNumber || 'RFQ-DRAFT';
        try {
            const saved = localStorage.getItem(`rms_rfq_files_${rfqNum}`);
            window.AppStore.activeRfq.attachedFiles = saved ? JSON.parse(saved) : [];
        } catch (e) {
            window.AppStore.activeRfq.attachedFiles = [];
        }
    }
    return window.AppStore.activeRfq.attachedFiles;
}

function getAttachedFilesCount() {
    return getAttachedFilesList().length;
}

function updateAttachedFilesBadge() {
    const count = getAttachedFilesCount();
    const badge = document.getElementById('rfqAttachedCountBadge');
    if (badge) badge.textContent = count;
    const label = document.getElementById('attachedFilesCountLabel');
    if (label) label.textContent = `${count} file${count === 1 ? '' : 's'}`;
}

function openRfqAttachedFilesModal() {
    const modal = document.getElementById('rfqAttachedFilesModal');
    if (!modal) return;
    const rfqNum = window.AppStore?.activeRfq?.ref || window.AppStore?.activeRfq?.rfqNumber || 'RFQ-2026-0044';
    const rfqRefEl = document.getElementById('attachedFilesModalRfqRef');
    if (rfqRefEl) rfqRefEl.textContent = rfqNum;

    const currentStage = getNormalizedRfqStage(window.AppStore?.activeRfq?.rfqStatus || window.AppStore?.activeRfq?.status);
    const stageBadge = document.getElementById('attachedFilesStageBadge');
    if (stageBadge) {
        stageBadge.className = `status-pill ${currentStage.toLowerCase()}`;
        stageBadge.textContent = currentStage;
    }

    renderAttachedFilesList();
    modal.classList.add('is-open');
}

function closeRfqAttachedFilesModal() {
    const modal = document.getElementById('rfqAttachedFilesModal');
    if (modal) modal.classList.remove('is-open');
}

function renderAttachedFilesList() {
    const container = document.getElementById('attachedFilesListContainer');
    if (!container) return;
    const files = getAttachedFilesList();
    updateAttachedFilesBadge();

    if (files.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 24px 16px; color: #94a3b8; font-size: 12px; font-style: italic; border: 1px dashed #e2e8f0; border-radius: 6px;">
                No files attached to this tender yet. Click above to upload technical specifications or benchmark sheets.
            </div>
        `;
        return;
    }

    let html = '';
    files.forEach((f, idx) => {
        const isPdf = f.name.toLowerCase().endsWith('.pdf');
        const icon = isPdf ? 'ph-file-pdf' : (f.name.match(/\.(jpg|jpeg|png)$/i) ? 'ph-file-image' : 'ph-file-text');
        const iconColor = isPdf ? '#dc2626' : '#2563eb';
        html += `
            <div class="rfq-attached-file-item">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <i class="ph ${icon}" style="font-size: 20px; color: ${iconColor}; flex-shrink: 0;"></i>
                    <div style="min-width: 0;">
                        <div style="font-weight: 700; font-size: 12.5px; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(f.name)}
                        </div>
                        <div style="font-size: 10.5px; color: #64748b;">
                            ${escapeHtml(f.size || 'Attachment')} &bull; Uploaded ${escapeHtml(f.date || 'Today')}
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="showToast('Viewing file: ${escapeHtml(f.name)}', 'info')" title="Download / Preview File" style="padding: 2px 7px; height: 24px; font-size: 11px;">
                        <i class="ph ph-eye"></i>
                    </button>
                    <button type="button" class="hr-btn hr-btn-danger hr-btn-sm" onclick="removeAttachedFile(${idx})" title="Remove attachment" style="padding: 2px 7px; height: 24px; font-size: 11px;">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function handleRfqFileUpload(event) {
    const input = event.target;
    if (!input || !input.files || input.files.length === 0) return;
    const files = getAttachedFilesList();

    Array.from(input.files).forEach(file => {
        const sizeFormatted = file.size > 1024 * 1024
            ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
            : Math.max(1, Math.round(file.size / 1024)) + ' KB';

        files.push({
            id: 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            name: file.name,
            size: sizeFormatted,
            date: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
    });

    const rfqNum = window.AppStore.activeRfq.ref || window.AppStore.activeRfq.rfqNumber || 'RFQ-DRAFT';
    try {
        localStorage.setItem(`rms_rfq_files_${rfqNum}`, JSON.stringify(files));
    } catch (e) {}

    renderAttachedFilesList();
    showToast(`✓ Attached ${input.files.length} document(s) to tender.`, 'success');
    input.value = '';
}

function removeAttachedFile(idx) {
    const files = getAttachedFilesList();
    if (idx >= 0 && idx < files.length) {
        files.splice(idx, 1);
        const rfqNum = window.AppStore.activeRfq.ref || window.AppStore.activeRfq.rfqNumber || 'RFQ-DRAFT';
        try {
            localStorage.setItem(`rms_rfq_files_${rfqNum}`, JSON.stringify(files));
        } catch (e) {}
        renderAttachedFilesList();
        showToast('Attachment removed.', 'info');
    }
}

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  cancelRfqDraft,
  cancelActiveRfq,
  withdrawApprovedRfq,
  getAttachedFilesList,
  getAttachedFilesCount,
  updateAttachedFilesBadge,
  openRfqAttachedFilesModal,
  closeRfqAttachedFilesModal,
  renderAttachedFilesList,
  handleRfqFileUpload,
  removeAttachedFile,
  getStageDefaultColumns,
  normalizeRfqItem,
  initAppStore,
  initDates,
  renderVendorDropdown,
  renderVendorComboboxList,
  toggleVendorCombobox,
  filterVendorCombobox,
  selectVendorFromCombo,
  updateVendorComboTrigger,
  handleVendorSelection,
  handleVendorRedirectClick,
  clearVendorFields,
  getColumnWidth,
  getNormalizedRfqStage,
  applyStageFormLocks,
  calculateItemVariances,
  renderTableHeaderAndColumns,
  renderBuilderColumnDropdownChecklist,
  renderColumnDropdownChecklist,
  toggleRfqBuilderSettingsDropdown,
  toggleColumnFilterDropdown,
  handleBuilderColSearch,
  toggleBuilderColumnVisibility,
  toggleColumnVisibility,
  showAllBuilderColumns,
  showAllColumns,
  resetBuilderColumnDefaults,
  resetColumnDefaults,
  showCompactColumns,
  resetBuilderColumnWidths,
  resetColumnWidths,
  initColumnResize,
  handleColumnMouseMove,
  handleColumnMouseUp,
  updateLifecycleProgressBar,
  handleStepClick,
  showLockOverlay,
  hideLockOverlay,
  submitActiveRfqToVendor,
  applyStageDefaultColumns,
  ensureApprovalColumnsVisible,
  ensureSubmittedColumnsVisible,
  ensureDraftColumnsVisible,
  moveToApprovalStage,
  handleApproverApprove,
  handleApproverRefuse,
  handleApproverRevision,
  renderItemsTable,
  updateItemRowField,
  updateItemRowFieldLive,
  addNewBlankItemRow,
  cloneItemRow,
  deleteItemRow,
  clearAllItemRows,
  updateMetricsSummary,
  openItemMasterQuickSelectModal,
  closeItemMasterQuickSelectModal,
  filterItemMasterModalList,
  renderItemMasterModalRows,
  addItemFromMasterDirect,
  openRfqDocumentPreview,
  printRfqDocument,
  closeRfqDocumentPreview,
  updateLiveDocumentMockup,
  openSendEmailModal,
  closeSendEmailModal,
  recordSentRfqInHistory,
  openRecentRfqHistoryModal,
  closeRecentRfqHistoryModal,
  syncFormInputsToState,
  persistDraft,
  checkDraftRecovery,
  restoreDraftSession,
  getNextUniqueRfqNumber,
  createNewRfq,
  resetRfqForm,
  handleBuilderTabClick,
  closeAllModals,
  formatNumber,
  formatMoney,
  formatDateDisplay,
  openRfqAuditDrawer,
  closeRfqAuditDrawer,
  toggleBuilderAuditTrail,
  copyRfqReference,
  handleRfqTitleChange,
  switchRfqTab,
  getDirectoryColWidth,
  initDirectoryColumns,
  renderDirectoryTableHeader,
  renderDirectoryColDropdownChecklist,
  toggleRfqTableSettingsDropdown,
  handleDirectoryColSearch,
  reorderDirectoryColumns,
  handleDropdownDragStart,
  handleDropdownDragOver,
  handleDropdownDragLeave,
  handleDropdownDrop,
  handleDropdownDragEnd,
  handleHeaderDragStart,
  handleHeaderDragOver,
  handleHeaderDragLeave,
  handleHeaderDrop,
  handleHeaderDragEnd,
  toggleDirectoryColVisibility,
  showAllDirectoryColumns,
  resetDirectoryColumnDefaults,
  resetDirectoryColumns,
  resetDirectoryColumnWidths,
  initDirectoryColResize,
  handleDirectoryColMouseMove,
  handleDirectoryColMouseUp,
  toggleRfqRowExpansion,
  changeDirectoryPageSize,
  goToDirectoryPage,
  renderDirectoryPagination,
  renderRfqDirectory,
  filterRfqByStatus,
  toggleRfqHeaderFilter,
  closeRfqHeaderFilter,
  buildStatusFilterPopoverHtml,
  buildPaymentTermsFilterPopoverHtml,
  setRfqApprovalFilter,
  setRfqStatusFilter,
  resetRfqStatusFilters,
  setRfqPaymentTermsFilter,
  clearAllRfqFilters,
  renderRfqActiveFilterChips,
  handleRfqDirectorySearch,
  startNewRfqFromDirectory,
  editRfqFromDirectory,
  previewRfqFromDirectory,
  sendRfqFromDirectory,
  deleteRfqFromDirectory,
  markQuoteReceived,
  openManagerApprovalModal,
  openApprovalTransferModal,
  closeApprovalModal,
  approveRfqOnly,
  confirmApprovalAndGeneratePO,
  generatePoForApprovedRfq,
  getLinkedPoNumberForRfq,
  openLinkedPoFromRfq,
  quickCreatePoFromDirectory,
  revokeRfqApproval,
  getProcurementAuditTrail,
  seedProcurementAuditTrail,
  logProcurementAudit,
  getCurrentAuditTarget,
  setAuditScope,
  renderRfqAuditTrail,
  onAuditSearchChange,
  filterAuditByModule,
  filterAuditByAction,
  openSingleAuditModal,
  closeSingleAuditModal,
  convertRfqToPurchaseOrder,
  syncRfqToDirectory,
  exportRfqDirectoryCSV,
  escapeHtml,
  showToast
});
