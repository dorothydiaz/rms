/**
 * Product Management Module Interactive Controller
 * Column Filter Triggered by Filter Icon in Action Header, Horizontal Scroll, Full Category Listing
 */
(function() {
    'use strict';

    // 1. Dynamic Column Definition Matrix (Prefix + Dynamic Cat 1, Cat 2, ... + Suffix)
    const BASE_PREFIX_COLUMNS = [
        { id: 'sku', label: 'SKU', default: true, align: 'left', width: '120px' },
        { id: 'product', label: 'Barcode / Product Name', default: true, align: 'left', minWidth: '220px' },
        { id: 'description', label: 'Product Description', default: true, align: 'left', minWidth: '180px' }
    ];

    const BASE_SUFFIX_COLUMNS = [
        { id: 'variants', label: 'Variants & Prices', default: true, align: 'left', minWidth: '175px' },
        { id: 'tracking_by', label: 'Track in Inventory', default: true, align: 'left', width: '140px' },
        { id: 'uom', label: 'Unit of Measure (UOM) / Pack Size', default: true, align: 'left', width: '160px' },
        { id: 'cost_price', label: 'Cost Price', default: true, align: 'left', width: '120px' },
        { id: 'selling_price', label: 'Selling Price', default: true, align: 'left', width: '120px' },
        { id: 'margin', label: 'Margin / Markup %', default: true, align: 'left', width: '130px' },
        { id: 'tax_rate', label: 'Tax Rate', default: true, align: 'left', width: '110px' },
        { id: 'supplier', label: 'Primary Supplier', default: true, align: 'left', minWidth: '160px' },
        { id: 'allergens', label: 'Allergen / Dietary Tags', default: true, align: 'left', minWidth: '160px' },
        { id: 'status', label: 'Status (Active/Inactive)', default: true, align: 'left', width: '140px' }
    ];

    function getMaxCategoryDepth() {
        let maxLvl = 2; // Baseline default for standard ERP (Cat 1 & Cat 2)
        if (window.AppStore) {
            let detectedMax = 1;
            if (Array.isArray(window.AppStore.categoryNodes)) {
                window.AppStore.categoryNodes.forEach(n => {
                    if (n.level && n.level > detectedMax) detectedMax = n.level;
                });
            }
            if (window.AppStore.categoryLevelNames) {
                Object.keys(window.AppStore.categoryLevelNames).forEach(k => {
                    const num = Number(k);
                    if (num > detectedMax) detectedMax = num;
                });
            }
            if (Array.isArray(window.AppStore.products)) {
                window.AppStore.products.forEach(p => {
                    if (Array.isArray(p.categoryPath) && p.categoryPath.length > detectedMax) {
                        detectedMax = p.categoryPath.length;
                    }
                });
            }
            maxLvl = Math.max(1, detectedMax);
        }
        return maxLvl;
    }

    function getCategoryLevelLabel(level) {
        if (window.AppStore && window.AppStore.categoryLevelNames && window.AppStore.categoryLevelNames[level]) {
            return window.AppStore.categoryLevelNames[level];
        }
        return `Cat ${level}`;
    }

    function getDynamicCategoryColumns() {
        const depth = getMaxCategoryDepth();
        const cols = [];
        for (let lvl = 1; lvl <= depth; lvl++) {
            cols.push({
                id: `cat_level_${lvl}`,
                label: getCategoryLevelLabel(lvl),
                catLevel: lvl,
                default: true,
                align: 'left',
                width: '145px',
                minWidth: '120px'
            });
        }
        return cols;
    }

    function getAllColumns() {
        return [
            ...BASE_PREFIX_COLUMNS,
            ...getDynamicCategoryColumns(),
            ...BASE_SUFFIX_COLUMNS
        ];
    }

    // Saved Column Visibility Settings
    let activeColumnIds = JSON.parse(localStorage.getItem('rms_product_columns')) || null;

    function syncActiveColumnIdsWithDynamicLevels() {
        const allCols = getAllColumns();
        if (!Array.isArray(activeColumnIds) || activeColumnIds.length === 0) {
            activeColumnIds = allCols.map(c => c.id);
        } else {
            // Migrate legacy 'category' / 'subcategory' IDs and auto-include newly created cat_level_N columns
            if (activeColumnIds.includes('category') && !activeColumnIds.includes('cat_level_1')) {
                activeColumnIds.push('cat_level_1');
            }
            if (activeColumnIds.includes('subcategory') && !activeColumnIds.includes('cat_level_2')) {
                activeColumnIds.push('cat_level_2');
            }
            activeColumnIds = activeColumnIds.filter(id => id !== 'category' && id !== 'subcategory');
            let knownCatCols = JSON.parse(localStorage.getItem('rms_known_cat_cols')) || [];
            getDynamicCategoryColumns().forEach(catCol => {
                if (!knownCatCols.includes(catCol.id)) {
                    knownCatCols.push(catCol.id);
                    if (!activeColumnIds.includes(catCol.id)) {
                        activeColumnIds.push(catCol.id);
                    }
                }
            });
            ['variants', 'tracking_by'].forEach(newColId => {
                if (!knownCatCols.includes(newColId)) {
                    knownCatCols.push(newColId);
                    if (!activeColumnIds.includes(newColId)) {
                        activeColumnIds.push(newColId);
                    }
                }
            });
            // Prune any deleted cat_level_N from knownCatCols
            const currentMax = getMaxCategoryDepth();
            knownCatCols = knownCatCols.filter(id => {
                if (id.startsWith('cat_level_')) {
                    const l = Number(id.replace('cat_level_', ''));
                    return l <= currentMax;
                }
                return true;
            });
            localStorage.setItem('rms_known_cat_cols', JSON.stringify(knownCatCols));
        }
        // Ensure any cat_level_N columns from deleted levels are pruned from activeColumnIds
        const activeMax = getMaxCategoryDepth();
        activeColumnIds = activeColumnIds.filter(id => {
            if (id.startsWith('cat_level_')) {
                const l = Number(id.replace('cat_level_', ''));
                return l <= activeMax;
            }
            return true;
        });
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
    }

    // Saved Modular Column Widths Settings (Persisted in localStorage)
    let savedColumnWidths = JSON.parse(localStorage.getItem('rms_product_column_widths')) || {};

    // Helper: Retrieve active or saved width for any column
    function getColumnWidth(col) {
        if (savedColumnWidths[col.id]) {
            return savedColumnWidths[col.id];
        }
        return col.width || col.minWidth || '140px';
    }

    // Modular Visual Category Palette Dictionary
    const CATEGORY_PALETTES = {
        purple:  { label: 'Purple / Violet', bg: '#faf5ff', text: '#7e22ce', border: '#e9d5ff', dot: '#a855f7' },
        amber:   { label: 'Amber / Gold',    bg: '#fffbeb', text: '#b45309', border: '#fde68a', dot: '#f59e0b' },
        rose:    { label: 'Pink / Rose',     bg: '#fdf2f8', text: '#be185d', border: '#fbcfe8', dot: '#ec4899' },
        emerald: { label: 'Emerald / Green', bg: '#f0fdf4', text: '#15803d', border: '#bbf7d0', dot: '#10b981' },
        blue:    { label: 'Blue / Sky',      bg: '#eff6ff', text: '#1d4ed8', border: '#bfdbfe', dot: '#3b82f6' },
        orange:  { label: 'Orange / Coral',  bg: '#fff7ed', text: '#c2410c', border: '#fed7aa', dot: '#f97316' },
        teal:    { label: 'Teal / Cyan',     bg: '#f0fdfa', text: '#0f766e', border: '#99f6e4', dot: '#14b8a6' },
        indigo:  { label: 'Indigo / Navy',   bg: '#eef2ff', text: '#4338ca', border: '#c7d2fe', dot: '#6366f1' },
        slate:   { label: 'Slate / Neutral', bg: '#f8fafc', text: '#475569', border: '#cbd5e1', dot: '#64748b' }
    };

    const CATEGORY_ICONS = [
        { id: 'ph-coffee', label: 'Coffee / Drink' },
        { id: 'ph-fork-knife', label: 'Fork & Knife' },
        { id: 'ph-cookie', label: 'Cookie / Pastry' },
        { id: 'ph-plant', label: 'Plant / Raw' },
        { id: 'ph-box', label: 'Box / Packaging' },
        { id: 'ph-drop', label: 'Drop / Syrup' },
        { id: 'ph-wine', label: 'Wine / Bar' },
        { id: 'ph-pizza', label: 'Pizza / Snack' },
        { id: 'ph-package', label: 'Package / Goods' },
        { id: 'ph-t-shirt', label: 'Merchandise' },
        { id: 'ph-wrench', label: 'Tools / Hardware' },
        { id: 'ph-tag', label: 'Tag / General' }
    ];

    const DEFAULT_CATEGORY_META = {
        'Beverages': { icon: 'ph-coffee', color: 'purple' },
        'Main Course': { icon: 'ph-fork-knife', color: 'amber' },
        'Pastries & Desserts': { icon: 'ph-cookie', color: 'rose' },
        'Raw Ingredients': { icon: 'ph-plant', color: 'emerald' },
        'Appetizers & Starters': { icon: 'ph-pizza', color: 'orange' },
        'Side Dishes': { icon: 'ph-fork-knife', color: 'amber' },
        'Packaging & Disposables': { icon: 'ph-box', color: 'blue' },
        'Syrups & Flavors': { icon: 'ph-drop', color: 'orange' },
        'Uncategorized': { icon: 'ph-tag', color: 'slate' }
    };

    function getCategoryMeta(catName) {
        if (window.AppStore && window.AppStore.categoryMeta && window.AppStore.categoryMeta[catName]) {
            return window.AppStore.categoryMeta[catName];
        }
        if (DEFAULT_CATEGORY_META[catName]) {
            return DEFAULT_CATEGORY_META[catName];
        }
        return { icon: 'ph-tag', color: 'purple' };
    }

    function getCategoryPalette(colorKey) {
        return CATEGORY_PALETTES[colorKey] || CATEGORY_PALETTES.purple;
    }

    // Initial Mock Catalog Data - Includes multiple categories to showcase horizontal scrolling
    const INITIAL_CATEGORIES = [
        'Beverages', 
        'Main Course', 
        'Pastries & Desserts', 
        'Raw Ingredients', 
        'Appetizers & Starters', 
        'Side Dishes', 
        'Packaging & Disposables', 
        'Syrups & Flavors'
    ];

    const INITIAL_PRODUCTS = [
        {
            id: 1,
            sku: 'BEV-001',
            name: 'Signature Spanish Latte',
            barcode: '4800019283711',
            description: 'Espresso with sweetened condensed milk and silky textured steamed milk.',
            category: 'Beverages',
            subcategory: 'Espresso Based',
            unit: 'Cup',
            packSize: '16 oz Regular',
            costPrice: 45.00,
            sellingPrice: 150.00,
            taxRate: '12% VAT',
            supplier: 'Power Mac Roasters',
            allergens: ['Dairy'],
            isActive: true,
            trackStock: true,
            reorderPoint: 20,
            targetStock: 80,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 42, reserved: 2, reorder: 20 },
                { name: 'Makati Greenbelt Kiosk', onHand: 18, reserved: 0, reorder: 15 },
                { name: 'BGC High Street Branch', onHand: 35, reserved: 4, reorder: 20 }
            ],
            ledger: [
                { ref: 'PO-2026-0041', type: 'Stock In', qty: '+50', balance: 95 },
                { ref: 'POS-TX-9902', type: 'Sale', qty: '-2', balance: 93 }
            ]
        },
        {
            id: 2,
            sku: 'BEV-002',
            name: 'Iced Americano Grande',
            barcode: '4800019283728',
            description: 'Rich double espresso shot over cold filtered water and ice cubes.',
            category: 'Beverages',
            subcategory: 'Iced Coffee',
            unit: 'Cup',
            packSize: '20 oz Grande',
            costPrice: 28.00,
            sellingPrice: 120.00,
            taxRate: '12% VAT',
            supplier: 'Power Mac Roasters',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 60,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 55, reserved: 0, reorder: 15 },
                { name: 'Makati Greenbelt Kiosk', onHand: 24, reserved: 1, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0038', type: 'Stock In', qty: '+40', balance: 119 }
            ]
        },
        {
            id: 3,
            sku: 'BEV-003',
            name: 'Matcha Green Tea Fusion',
            barcode: '4800019283735',
            description: 'Ceremonial grade Uji matcha whisked with fresh oat milk.',
            category: 'Beverages',
            subcategory: 'Tea & Non-Coffee',
            unit: 'Cup',
            packSize: '16 oz',
            costPrice: 52.00,
            sellingPrice: 175.00,
            taxRate: '12% VAT',
            supplier: 'Kyoto Tea Imports',
            allergens: ['Oat'],
            isActive: true,
            trackStock: true,
            reorderPoint: 12,
            targetStock: 45,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 11, reserved: 0, reorder: 12 }
            ],
            ledger: [
                { ref: 'PO-2026-0030', type: 'Stock In', qty: '+25', balance: 34 }
            ]
        },
        {
            id: 4,
            sku: 'MNC-101',
            name: 'Truffle Mushroom Pasta',
            barcode: '4800028471920',
            description: 'Fettuccine in white truffle cream, shiitake and cremini mushrooms, Parmigiano Reggiano.',
            category: 'Main Course',
            subcategory: 'Pasta & Noodles',
            unit: 'Plate',
            packSize: '320g Serving',
            costPrice: 115.00,
            sellingPrice: 380.00,
            taxRate: '12% VAT',
            supplier: 'Gourmet Italia Supply',
            allergens: ['Dairy', 'Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 40,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 28, reserved: 3, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0028', type: 'Stock In', qty: '+30', balance: 50 }
            ]
        },
        {
            id: 5,
            sku: 'MNC-102',
            name: 'Crispy Pork Belly Bowl',
            barcode: '4800028471937',
            description: 'Slow-roasted crispy pork belly with garlic scallion rice and spiced vinegar glaze.',
            category: 'Main Course',
            subcategory: 'Rice Bowls',
            unit: 'Plate',
            packSize: '400g Bowl',
            costPrice: 95.00,
            sellingPrice: 295.00,
            taxRate: '12% VAT',
            supplier: 'Prime Meat Traders',
            allergens: ['Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 50,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 34, reserved: 0, reorder: 15 }
            ],
            ledger: [
                { ref: 'PO-2026-0021', type: 'Stock In', qty: '+40', balance: 53 }
            ]
        },
        {
            id: 6,
            sku: 'PST-201',
            name: 'Burnt Basque Cheesecake',
            barcode: '4800037190012',
            description: 'Creamy caramelized custard interior with deeply browned exterior crust.',
            category: 'Pastries & Desserts',
            subcategory: 'Cakes & Slices',
            unit: 'Slice',
            packSize: '150g Slice',
            costPrice: 55.00,
            sellingPrice: 195.00,
            taxRate: '12% VAT',
            supplier: 'Artisan Bakehouse Co.',
            allergens: ['Dairy', 'Eggs'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 35,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 14, reserved: 1, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0035', type: 'Stock In', qty: '+15', balance: 33 }
            ]
        },
        {
            id: 7,
            sku: 'PST-202',
            name: 'French Butter Croissant',
            barcode: '4800037190029',
            description: 'Traditional laminated dough baked fresh daily with Lescure French butter.',
            category: 'Pastries & Desserts',
            subcategory: 'Viennoiserie',
            unit: 'Piece',
            packSize: '85g Piece',
            costPrice: 32.00,
            sellingPrice: 110.00,
            taxRate: '12% VAT',
            supplier: 'Artisan Bakehouse Co.',
            allergens: ['Dairy', 'Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 20,
            targetStock: 60,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 38, reserved: 0, reorder: 20 }
            ],
            ledger: [
                { ref: 'PO-2026-0040', type: 'Stock In', qty: '+50', balance: 85 }
            ]
        },
        {
            id: 8,
            sku: 'RAW-301',
            name: 'Barista Whole Fresh Milk',
            barcode: '4800049102911',
            description: 'High-protein fresh cow milk formulated for microfoam and latte art.',
            category: 'Raw Ingredients',
            subcategory: 'Dairy & Liquids',
            unit: 'Liter',
            packSize: '1 Liter Tetra',
            costPrice: 85.00,
            sellingPrice: 120.00,
            taxRate: '0% Exempt',
            supplier: 'Fresh Dairy Corp',
            allergens: ['Dairy'],
            isActive: true,
            trackStock: true,
            reorderPoint: 40,
            targetStock: 150,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 88, reserved: 0, reorder: 40 }
            ],
            ledger: [
                { ref: 'PO-2026-0043', type: 'Stock In', qty: '+100', balance: 185 }
            ]
        },
        {
            id: 9,
            sku: 'RAW-302',
            name: 'Arabica Espresso Beans (Single Origin)',
            barcode: '4800049102928',
            description: 'Benguet & Mt. Apo arabica blend, tasting notes of dark cocoa and molasses.',
            category: 'Raw Ingredients',
            subcategory: 'Coffee Beans',
            unit: 'Kg',
            packSize: '1 Kg Bag',
            costPrice: 550.00,
            sellingPrice: 750.00,
            taxRate: '0% Exempt',
            supplier: 'Highland Growers Collective',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 50,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 24, reserved: 0, reorder: 15 }
            ],
            ledger: [
                { ref: 'PO-2026-0039', type: 'Stock In', qty: '+30', balance: 51 }
            ]
        },
        {
            id: 10,
            sku: 'APP-401',
            name: 'Crispy Calamari Fritti',
            barcode: '4800051283720',
            description: 'Tender squid rings lightly dusted in seasoned flour with caper garlic aioli.',
            category: 'Appetizers & Starters',
            subcategory: 'Deep Fried',
            unit: 'Plate',
            packSize: '250g Serving',
            costPrice: 120.00,
            sellingPrice: 280.00,
            taxRate: '12% VAT',
            supplier: 'Pacific Seafood Logistics',
            allergens: ['Gluten', 'Seafood'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 30,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 16, reserved: 0, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0014', type: 'Stock In', qty: '+20', balance: 20 }
            ]
        },
        {
            id: 11,
            sku: 'PKG-501',
            name: 'Kraft Takeout Box (Medium)',
            barcode: '4800067192834',
            description: 'Eco-friendly biodegradable paperboard container with grease-resistant lining.',
            category: 'Packaging & Disposables',
            subcategory: 'Boxes & Containers',
            unit: 'Piece',
            packSize: 'Pack of 50',
            costPrice: 8.50,
            sellingPrice: 15.00,
            taxRate: '12% VAT',
            supplier: 'GreenPack Solutions',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 100,
            targetStock: 500,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 350, reserved: 0, reorder: 100 }
            ],
            ledger: [
                { ref: 'PO-2026-0044', type: 'Stock In', qty: '+300', balance: 350 }
            ]
        },
        {
            id: 12,
            sku: 'SYR-601',
            name: 'Caramel Macchiato Artisan Syrup',
            barcode: '4800078291039',
            description: 'Slow-cooked golden butter caramel syrup with roasted sugar undertones (750ml).',
            category: 'Syrups & Flavors',
            subcategory: 'Flavor Syrups',
            unit: 'Bottle',
            packSize: '750ml Bottle',
            costPrice: 290.00,
            sellingPrice: 420.00,
            taxRate: '12% VAT',
            supplier: 'Monin Specialty Distributors',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 8,
            targetStock: 25,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 14, reserved: 0, reorder: 8 }
            ],
            ledger: [
                { ref: 'PO-2026-0037', type: 'Stock In', qty: '+15', balance: 22 }
            ]
        }
    ];

    // Reactive State Store
    const DEFAULT_LEVEL_NAMES = {
        1: 'Cat 1',
        2: 'Cat 2'
    };

    window.AppStore = {
        categories: JSON.parse(localStorage.getItem('rms_product_categories')) || INITIAL_CATEGORIES,
        categoryInfo: JSON.parse(localStorage.getItem('rms_category_info')) || {},
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        categoryNodes: JSON.parse(localStorage.getItem('rms_category_nodes')) || null,
        categoryLevelNames: Object.assign({}, DEFAULT_LEVEL_NAMES, JSON.parse(localStorage.getItem('rms_category_level_names')) || {}),
        products: JSON.parse(localStorage.getItem('rms_inventory_products')) || INITIAL_PRODUCTS,
        activeWorkspaceTab: 'catalog',
        activeCategory: 'ALL',
        searchQuery: '',
        selectedProductId: null,
        collapsedCategories: {},
        branchPage: 1,
        branchPageSize: 4,
        ledgerPage: 1,
        ledgerPageSize: 4,
        activePriceChart: null
    };

    // Helper: Find category node by name (case-insensitive)
    function getCategoryNode(name) {
        if (!name || !Array.isArray(window.AppStore.categoryNodes)) return null;
        const target = String(name).trim().toLowerCase();
        return window.AppStore.categoryNodes.find(n => n.name.toLowerCase() === target) || null;
    }

    // Helper: Build full ancestor path ['Beverages', 'Espresso Based', ...] for any category name
    function getCategoryAncestryPath(catName) {
        if (!catName) return [];
        const path = [];
        let curr = getCategoryNode(catName);
        const visited = new Set();
        while (curr && !visited.has(curr.name.toLowerCase())) {
            visited.add(curr.name.toLowerCase());
            path.unshift(curr.name);
            curr = curr.parentName ? getCategoryNode(curr.parentName) : null;
        }
        if (path.length === 0) path.push(catName);
        return path;
    }

    // Helper: Return array of category names [Cat1, Cat2, Cat3, ...] for a product
    function getProductCategoryPath(product) {
        if (!product) return [];
        if (Array.isArray(product.categoryPath) && product.categoryPath.filter(Boolean).length > 0) {
            return product.categoryPath.filter(Boolean);
        }
        const path = [];
        if (product.category) path.push(product.category);
        if (product.subcategory && product.subcategory !== 'Standard') path.push(product.subcategory);
        return path;
    }

    function getProductCategoryAtLevel(product, level) {
        const path = getProductCategoryPath(product);
        return path[level - 1] || '';
    }

    // Initialize / Migrate Hierarchical Category Nodes from existing Categories & Product Subcategories
    function initializeCategoryHierarchy() {
        if (!Array.isArray(window.AppStore.categoryNodes) || window.AppStore.categoryNodes.length === 0) {
            const nodes = [];
            const seen = new Set();

            // 1. Seed root categories (Level 1)
            (window.AppStore.categories || INITIAL_CATEGORIES).forEach(catName => {
                const clean = String(catName || '').trim();
                if (!clean || seen.has(clean.toLowerCase())) return;
                seen.add(clean.toLowerCase());
                const meta = getCategoryMeta(clean);
                nodes.push({
                    name: clean,
                    parentName: null,
                    level: 1,
                    icon: meta.icon || 'ph-tag',
                    color: meta.color || 'purple',
                    description: window.AppStore.categoryInfo[clean] || ''
                });
            });

            // 2. Seed child categories (Level 2+) from products
            (window.AppStore.products || []).forEach(p => {
                const path = getProductCategoryPath(p);
                p.categoryPath = path;
                for (let i = 0; i < path.length; i++) {
                    const cName = String(path[i] || '').trim();
                    if (!cName) continue;
                    const parentName = i > 0 ? String(path[i - 1] || '').trim() : null;
                    if (!seen.has(cName.toLowerCase())) {
                        seen.add(cName.toLowerCase());
                        const parentMeta = parentName ? getCategoryMeta(parentName) : getCategoryMeta(cName);
                        nodes.push({
                            name: cName,
                            parentName: parentName,
                            level: i + 1,
                            icon: parentMeta.icon || 'ph-tag',
                            color: parentMeta.color || 'purple',
                            description: window.AppStore.categoryInfo[cName] || ''
                        });
                    }
                }
            });

            window.AppStore.categoryNodes = nodes;
        }

        // Ensure all products have normalized categoryPath
        (window.AppStore.products || []).forEach(p => {
            p.categoryPath = getProductCategoryPath(p);
            if (p.categoryPath[0]) p.category = p.categoryPath[0];
            p.subcategory = p.categoryPath[1] || 'Standard';
        });

        // Ensure level names exist for every depth
        const maxDepth = getMaxCategoryDepth();
        for (let lvl = 1; lvl <= maxDepth; lvl++) {
            if (!window.AppStore.categoryLevelNames[lvl]) {
                window.AppStore.categoryLevelNames[lvl] = `Cat ${lvl}`;
            }
        }

        persistCategoryHierarchy();
        syncActiveColumnIdsWithDynamicLevels();
    }

    function persistCategoryHierarchy() {
        // Keep root categories & meta synced for cross-module Stocks Overview compatibility
        const allNames = window.AppStore.categoryNodes.map(n => n.name);
        window.AppStore.categories = allNames;
        window.AppStore.categoryNodes.forEach(n => {
            window.AppStore.categoryMeta[n.name] = { icon: n.icon || 'ph-tag', color: n.color || 'purple' };
            if (n.description) window.AppStore.categoryInfo[n.name] = n.description;
        });

        localStorage.setItem('rms_category_nodes', JSON.stringify(window.AppStore.categoryNodes));
        localStorage.setItem('rms_category_level_names', JSON.stringify(window.AppStore.categoryLevelNames));
        localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));
        localStorage.setItem('rms_category_info', JSON.stringify(window.AppStore.categoryInfo));
        localStorage.setItem('rms_category_meta', JSON.stringify(window.AppStore.categoryMeta));
    }

    initializeCategoryHierarchy();

    // Auto-seed baseline audit logs, trackingBy (lots/quantity/serial), and variants for existing products
    window.AppStore.products.forEach(p => {
        if (!p.trackingBy) {
            const catLower = (p.category || '').toLowerCase();
            if (catLower.includes('packaging') || (p.sku && p.sku.startsWith('PKG-'))) {
                p.trackingBy = 'quantity';
            } else if (catLower.includes('equipment') || (p.sku && p.sku.startsWith('EQP-'))) {
                p.trackingBy = 'serial';
            } else {
                p.trackingBy = 'lots';
            }
        }
        if (!Array.isArray(p.variants)) {
            if (p.sku === 'BEV-101') {
                p.variants = [
                    { id: 'v-101-1', group: 'Size', name: '12oz Regular (Hot)', sku: 'BEV-101-12H', costPrice: 52.00, sellingPrice: 135.00 },
                    { id: 'v-101-2', group: 'Size', name: '16oz Large (Iced)', sku: 'BEV-101-16I', costPrice: 58.50, sellingPrice: 145.00 },
                    { id: 'v-101-3', group: 'Size', name: '22oz Venti (Iced)', sku: 'BEV-101-22I', costPrice: 68.00, sellingPrice: 170.00 }
                ];
            } else if (p.sku === 'BEV-102') {
                p.variants = [
                    { id: 'v-102-1', group: 'Size', name: '16oz Regular Frappe', sku: 'BEV-102-16R', costPrice: 68.00, sellingPrice: 165.00 },
                    { id: 'v-102-2', group: 'Size', name: '22oz Upsize Frappe', sku: 'BEV-102-22U', costPrice: 79.00, sellingPrice: 195.00 }
                ];
            } else {
                p.variants = [];
            }
        }
        if (p.imageUrl === undefined) {
            p.imageUrl = '';
        }
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
                        { field: 'Category', oldValue: '—', newValue: p.category },
                        { field: 'Cost Price', oldValue: '—', newValue: formatPHP(p.costPrice) },
                        { field: 'Selling Price', oldValue: '—', newValue: formatPHP(p.sellingPrice) },
                        { field: 'Track in Inventory', oldValue: '—', newValue: `Tracked by ${p.trackingBy}` }
                    ]
                }
            ];
        }
    });
    localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));

    // DOM Caches
    const masterColgroup = document.getElementById('masterTableColgroup');
    const masterTheadRow = document.getElementById('masterTableTheadRow');
    const masterTbody = document.getElementById('masterProductTbody');
    const searchInput = document.getElementById('invMasterSearch');
    const filteredCountEl = document.getElementById('filteredItemCount');
    const newCategoryModal = document.getElementById('newCategoryModal');

    // Drawer Elements
    const drawerBackdrop = document.getElementById('drawerBackdrop');
    const drawerContainer = document.getElementById('productDetailDrawer');
    const drawerTitle = document.getElementById('drawerTitle');
    const drawerSubtitle = document.getElementById('drawerSubtitle');
    const drawerIcon = document.getElementById('drawerIcon');
    const btnSaveText = document.getElementById('btnSaveText');
    const toastEl = document.getElementById('invToast');
    const toastMsgEl = document.getElementById('invToastMsg');

    // Drawer Inputs
    const formId = document.getElementById('formProductId');
    const formSku = document.getElementById('formSku');
    const formBarcode = document.getElementById('formBarcode');
    const formName = document.getElementById('formName');
    const formCategory = document.getElementById('formCategory');
    const formSubcategory = document.getElementById('formSubcategory');
    const formSubcategorySelect = document.getElementById('formSubcategorySelect');
    const formUnit = document.getElementById('formUnit');
    const formPackSize = document.getElementById('formPackSize');
    const formDescription = document.getElementById('formDescription');
    const formAllergens = document.getElementById('formAllergens');
    const formCanBeSold = document.getElementById('formCanBeSold');
    const formCanBePurchased = document.getElementById('formCanBePurchased');
    const formHasBom = document.getElementById('formHasBom');
    const formIsActive = document.getElementById('formIsActive');
    const formTrackStock = document.getElementById('formTrackStock');
    const formReorderPoint = document.getElementById('formReorderPoint');
    const formTargetStock = document.getElementById('formTargetStock');
    const formCostPrice = document.getElementById('formCostPrice');
    const formSellingPrice = document.getElementById('formSellingPrice');
    const formProfitMargin = document.getElementById('formProfitMargin');
    const formTaxRate = document.getElementById('formTaxRate');
    const formSupplier = document.getElementById('formSupplier');
    const branchSubtableBody = document.getElementById('branchStockSubtableBody');
    const ledgerSubtableBody = document.getElementById('ledgerLogSubtableBody');
    const supplierPricesListBody = document.getElementById('supplierPricesListBody');

    // Helper: Currency Formatter
    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    // Helper: Toast
    function showToast(message) {
        if (!toastEl) return;
        toastMsgEl.textContent = message;
        toastEl.classList.add('is-visible');
        setTimeout(() => toastEl.classList.remove('is-visible'), 3200);
    }

    // Calculation: Profit Margin & Markup
    function updateMarginCalculation() {
        const cost = parseFloat(formCostPrice.value) || 0;
        const selling = parseFloat(formSellingPrice.value) || 0;
        if (selling <= 0) {
            formProfitMargin.value = '0.0%';
            return;
        }
        const profit = selling - cost;
        const marginPct = (profit / selling) * 100;
        const markupPct = cost > 0 ? ((profit / cost) * 100) : 0;
        formProfitMargin.value = `${marginPct.toFixed(1)}% margin (${markupPct.toFixed(1)}% markup)`;
    }

    formCostPrice.addEventListener('input', updateMarginCalculation);
    formSellingPrice.addEventListener('input', updateMarginCalculation);

    // =========================================================================
    // SKU UNIQUENESS VALIDATION (REAL-TIME & PREVENTION)
    // =========================================================================
    function validateSkuUniqueness() {
        if (!formSku) return true;
        const skuVal = (formSku.value || '').trim();
        const currentId = formId && formId.value ? Number(formId.value) : null;
        const errEl = document.getElementById('formSkuError');
        const badgeEl = document.getElementById('formSkuStatusBadge');

        if (!skuVal) {
            formSku.classList.remove('inv-input-error');
            if (errEl) errEl.style.display = 'none';
            if (badgeEl) badgeEl.style.display = 'none';
            return true;
        }

        const normalized = skuVal.toLowerCase();
        const duplicate = (window.AppStore.products || []).find(p =>
            p.id !== currentId &&
            p.sku &&
            p.sku.trim().toLowerCase() === normalized
        );

        if (duplicate) {
            formSku.classList.add('inv-input-error');
            if (errEl) {
                errEl.style.display = 'flex';
                const span = errEl.querySelector('span');
                if (span) span.textContent = `SKU "${skuVal}" is already used by "${duplicate.name}"`;
            }
            if (badgeEl) {
                badgeEl.style.display = 'inline-block';
                badgeEl.style.color = '#ef4444';
                badgeEl.textContent = 'Duplicate SKU';
            }
            return false;
        } else {
            formSku.classList.remove('inv-input-error');
            if (errEl) errEl.style.display = 'none';
            if (badgeEl) {
                badgeEl.style.display = 'inline-block';
                badgeEl.style.color = '#10b981';
                badgeEl.textContent = 'Unique SKU ✓';
            }
            return true;
        }
    }

    if (formSku) {
        formSku.addEventListener('input', validateSkuUniqueness);
        formSku.addEventListener('blur', validateSkuUniqueness);
    }

    // =========================================================================
    // HIERARCHICAL CATEGORY & CASCADING SELECTORS SYNCHRONIZATION
    // =========================================================================
    let currentFormCategoryPath = [];

    function getOrderedCategoryTreeNodes() {
        const nodes = window.AppStore.categoryNodes || [];
        const result = [];
        const visited = new Set();

        function visitChildren(parentName, depth) {
            nodes
                .filter(n => {
                    const pName = n.parentName ? n.parentName.toLowerCase() : '';
                    const targetP = parentName ? parentName.toLowerCase() : '';
                    return pName === targetP;
                })
                .forEach(n => {
                    if (visited.has(n.name.toLowerCase())) return;
                    visited.add(n.name.toLowerCase());
                    n.level = depth;
                    result.push(n);
                    visitChildren(n.name, depth + 1);
                });
        }

        visitChildren(null, 1);

        // Append any orphan nodes whose parent wasn't found
        nodes.forEach(n => {
            if (!visited.has(n.name.toLowerCase())) {
                visited.add(n.name.toLowerCase());
                result.push(n);
            }
        });

        return result;
    }

    function updateWorkspaceBadges() {
        const products = window.AppStore.products || [];
        const prodCountBadge = document.getElementById('tabBadgeProductCount');
        if (prodCountBadge) {
            prodCountBadge.textContent = products.length;
        }

        const maxDepth = getMaxCategoryDepth();
        const labels = [];
        for (let i = 1; i <= Math.min(maxDepth, 3); i++) {
            labels.push(getCategoryLevelLabel(i));
        }
        const levelSummaryText = maxDepth > 3 ? `${labels.join(' • ')} +${maxDepth - 3}` : labels.join(' • ');

        const catCountBadge = document.getElementById('tabBadgeCategoryCount');
        if (catCountBadge) {
            catCountBadge.textContent = levelSummaryText;
        }

        // Hydrate 4-Card KPI Summary Bar in Product Catalog
        const activeCount = products.filter(p => p.isActive !== false).length;
        const sellableList = products.filter(p => p.canBeSold !== false);
        const bomList = products.filter(p => (p.explodeBomOnSale !== undefined ? p.explodeBomOnSale : p.hasBom) === true);

        let avgMargin = 0;
        if (sellableList.length > 0) {
            const sumMargin = sellableList.reduce((acc, p) => {
                const s = Number(p.sellingPrice) || 0;
                const c = Number(p.costPrice) || 0;
                return acc + (s > 0 ? ((s - c) / s) * 100 : 0);
            }, 0);
            avgMargin = sumMargin / sellableList.length;
        }

        const elTotal = document.getElementById('kpiTotalItems');
        const elTotalSub = document.getElementById('kpiTotalItemsSub');
        const elSellable = document.getElementById('kpiSellableItems');
        const elSellableSub = document.getElementById('kpiSellableSub');
        const elBom = document.getElementById('kpiBomItems');
        const elBomSub = document.getElementById('kpiBomSub');
        const elCatLevels = document.getElementById('kpiCategoryLevels');
        const elCatSub = document.getElementById('kpiCategorySub');

        if (elTotal) elTotal.textContent = products.length;
        if (elTotalSub) elTotalSub.textContent = `${activeCount} Active SKUs in Masterlist`;
        if (elSellable) elSellable.textContent = sellableList.length;
        if (elSellableSub) elSellableSub.textContent = `Avg Gross Margin: ${avgMargin.toFixed(1)}%`;
        if (elBom) elBom.textContent = bomList.length;
        if (elBomSub) elBomSub.textContent = `${(window.AppStore.categoryNodes || []).length} total parent/child categories`;
        if (elCatLevels) elCatLevels.textContent = `${maxDepth} ${maxDepth === 1 ? 'Level' : 'Levels'}`;
        if (elCatSub) elCatSub.textContent = `${levelSummaryText} active columns`;
    }

    window.handleCatalogKpiFilter = function(segmentMode) {
        window.AppStore.catalogSegment = segmentMode || 'ALL';

        document.querySelectorAll('#workspace-catalog .rfq-kpi-card[data-kpi-mode]').forEach(card => {
            card.classList.toggle('is-active-filter', card.getAttribute('data-kpi-mode') === window.AppStore.catalogSegment);
        });

        document.querySelectorAll('#catalogQuickSegmentPills .rfq-popover-chip').forEach(chip => {
            chip.classList.toggle('is-active', chip.getAttribute('data-seg') === window.AppStore.catalogSegment);
        });

        renderMasterList();
    };

    function syncCategoryControls() {
        syncActiveColumnIdsWithDynamicLevels();
        updateWorkspaceBadges();

        // 1. Sync Catalog Filter Select (#invCategoryFilterSelect)
        const filterSelect = document.getElementById('invCategoryFilterSelect');
        if (filterSelect) {
            const ordered = getOrderedCategoryTreeNodes();
            const prevVal = window.AppStore.activeCategory || 'ALL';
            let filterHtml = `<option value="ALL">All Categories (${ordered.length})</option>`;
            ordered.forEach(node => {
                const indent = node.level > 1 ? '— '.repeat(node.level - 1) : '';
                const lvlLabel = getCategoryLevelLabel(node.level);
                filterHtml += `<option value="${node.name}">${indent}${node.name} [${lvlLabel}]</option>`;
            });
            filterSelect.innerHTML = filterHtml;
            if (prevVal === 'ALL' || ordered.some(n => n.name === prevVal)) {
                filterSelect.value = prevVal;
            } else {
                window.AppStore.activeCategory = 'ALL';
                filterSelect.value = 'ALL';
            }
        }

        // 2. Sync legacy hidden #formCategory
        if (formCategory) {
            const rootNodes = (window.AppStore.categoryNodes || []).filter(n => !n.parentName || n.level === 1);
            formCategory.innerHTML = rootNodes.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
        }

        // 3. Render cascading category selectors in Product Details tab
        renderProductCategorySelectors(currentFormCategoryPath);

        // 4. Refresh Category Settings Tab if present
        if (typeof renderCategorySettingsTab === 'function') {
            renderCategorySettingsTab();
        }
    }

    window.handleCatalogCategoryFilter = function(val) {
        window.AppStore.activeCategory = val || 'ALL';
        renderMasterList();
    };

    function renderProductCategorySelectors(selectedPath) {
        const container = document.getElementById('dynamicCategorySelectorsContainer');
        if (!container) return;

        const maxDepth = getMaxCategoryDepth();
        currentFormCategoryPath = Array.isArray(selectedPath) ? [...selectedPath] : [];

        let html = '';
        for (let lvl = 1; lvl <= maxDepth; lvl++) {
            const lvlLabel = getCategoryLevelLabel(lvl);
            const parentSelected = lvl === 1 ? null : (currentFormCategoryPath[lvl - 2] || '');
            const currentVal = currentFormCategoryPath[lvl - 1] || '';

            // Candidate categories at this level
            let optionsNodes = (window.AppStore.categoryNodes || []).filter(n => {
                if (lvl === 1) return !n.parentName || n.level === 1;
                if (!parentSelected) return false;
                return n.parentName && n.parentName.toLowerCase() === parentSelected.toLowerCase();
            });

            // If user had a custom value saved at this level that isn't in optionsNodes yet, include it
            const optionNames = optionsNodes.map(n => n.name);
            if (currentVal && !optionNames.some(name => name.toLowerCase() === currentVal.toLowerCase())) {
                optionNames.unshift(currentVal);
            }

            const isDisabled = (lvl > 1 && !parentSelected);
            const placeholder = lvl === 1
                ? `— Select ${lvlLabel} —`
                : (parentSelected ? `— Optional ${lvlLabel} —` : `Select ${getCategoryLevelLabel(lvl - 1)} first`);

            html += `
                <div class="inv-form-group" style="margin: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <label for="formCatLevel_${lvl}" style="margin: 0; font-size: 0.76rem; font-weight: 700;">
                            ${lvlLabel} ${lvl === 1 ? '*' : ''}
                            <span style="font-weight: 500; color: var(--inv-text-muted); font-size: 0.68rem;">(Level ${lvl})</span>
                        </label>
                        ${(lvl === 1 || parentSelected) ? `
                            <button type="button" class="inv-col-quick-link" onclick="openNewCategoryModal('${parentSelected ? parentSelected.replace(/'/g, "\\'") : ''}')" style="font-size: 0.68rem; padding: 1px 6px;" title="Add category under ${parentSelected || 'Root'}">
                                <i class="ph ph-plus"></i> Add
                            </button>
                        ` : ''}
                    </div>
                    <select id="formCatLevel_${lvl}" class="inv-form-select" data-cat-level="${lvl}" ${lvl === 1 ? 'required' : ''} ${isDisabled ? 'disabled' : ''} onchange="handleCascadingCategoryChange(${lvl}, this.value)">
                        <option value="">${placeholder}</option>
                        ${optionNames.map(name => `<option value="${name}" ${name.toLowerCase() === currentVal.toLowerCase() ? 'selected' : ''}>${name}</option>`).join('')}
                    </select>
                </div>
            `;
        }

        container.innerHTML = html;

        // Keep legacy hidden fields synced
        if (formCategory) formCategory.value = currentFormCategoryPath[0] || '';
        if (formSubcategory) formSubcategory.value = currentFormCategoryPath[1] || 'Standard';
    }

    window.handleCascadingCategoryChange = function(level, value) {
        const maxDepth = getMaxCategoryDepth();
        currentFormCategoryPath[level - 1] = value || '';
        // Clear child selections deeper than the changed level
        for (let i = level; i < maxDepth; i++) {
            currentFormCategoryPath[i] = '';
        }
        // Auto-select first child if available? Keep optional so user can choose or leave blank
        renderProductCategorySelectors(currentFormCategoryPath);
    };

    function syncSubcategoryDropdown(categoryFilter) {
        renderProductCategorySelectors(currentFormCategoryPath);
    }

    window.handleDrawerCategoryChange = function(selectedCategory) {
        currentFormCategoryPath = [selectedCategory];
        renderProductCategorySelectors(currentFormCategoryPath);
    };

    window.handleSubcategorySelectChange = function(val) {
        currentFormCategoryPath[1] = val;
        renderProductCategorySelectors(currentFormCategoryPath);
    };

    window.toggleCustomSubcategoryInput = function() {
        openNewCategoryModal(currentFormCategoryPath[0] || '');
    };

    // =========================================================================
    // COLUMN FILTER DROPDOWN CONTROLLER (TRIGGERED BY FILTER ICON IN ACTION HEADER)
    // =========================================================================
    window.toggleColumnFilterDropdown = function(e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const triggerBtn = document.getElementById('btnTableColumnFilter');
        if (!dropdown) return;

        const isOpen = dropdown.classList.contains('is-active');
        if (isOpen) {
            dropdown.classList.remove('is-active');
            if (triggerBtn) triggerBtn.classList.remove('is-active');
        } else {
            dropdown.classList.add('is-active');
            if (triggerBtn) triggerBtn.classList.add('is-active');
        }
    };

    function renderColumnDropdownList() {
        const listEl = document.getElementById('tableColumnsDropdownList');
        const counterEl = document.getElementById('tableColumnsActiveCounter');
        if (!listEl) return;

        const allCols = getAllColumns();
        let listHtml = '';
        allCols.forEach(col => {
            const isChecked = activeColumnIds.includes(col.id);
            listHtml += `
                <div class="inv-col-dropdown-item ${isChecked ? 'is-checked' : ''}" onclick="window.handleColumnToggle('${col.id}', ${!isChecked})">
                    <label class="inv-col-checkbox-label">
                        <span class="inv-col-checkbox-custom">
                            <i class="ph ph-check" style="${isChecked ? '' : 'display:none;'}"></i>
                        </span>
                        <span title="${col.label}">${col.label}</span>
                    </label>
                    <span class="inv-col-status-badge">
                        ${isChecked ? 'Shown' : 'Hidden'}
                    </span>
                </div>
            `;
        });

        listEl.innerHTML = listHtml;
        if (counterEl) {
            counterEl.textContent = `${activeColumnIds.length} of ${allCols.length} visible`;
        }
    }

    window.handleColumnToggle = function(colId, isChecked) {
        if (isChecked) {
            if (!activeColumnIds.includes(colId)) activeColumnIds.push(colId);
        } else {
            if (activeColumnIds.length <= 2) {
                showToast('At least two columns must remain visible.');
                return;
            }
            activeColumnIds = activeColumnIds.filter(id => id !== colId);
        }

        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
    };

    window.showAllColumns = function() {
        activeColumnIds = getAllColumns().map(c => c.id);
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('All columns visible');
    };

    window.resetColumnDefaults = function() {
        activeColumnIds = getAllColumns().map(c => c.id);
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('Columns reset to default');
    };

    window.showCompactColumns = function() {
        const catColIds = getDynamicCategoryColumns().map(c => c.id);
        activeColumnIds = ['sku', 'product', ...catColIds, 'selling_price', 'status'];
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('Compact View applied');
    };

    // Reset Modular Column Widths to system defaults
    window.resetColumnWidths = function() {
        savedColumnWidths = {};
        localStorage.removeItem('rms_product_column_widths');
        renderTableHeaders();
        renderMasterList();
        showToast('Column widths restored to defaults');
    };

    // =========================================================================
    // INTERACTIVE MODULAR COLUMN RESIZING CONTROLLER (DRAG & PERSIST)
    // =========================================================================
    window.initColumnResize = function(e, colId) {
        if (e.button !== 0) return; // Only primary mouse button
        e.preventDefault();
        e.stopPropagation();

        const resizer = e.currentTarget;
        const th = resizer.closest('th');
        if (!th) return;

        const startX = e.pageX;
        const startWidth = th.offsetWidth;
        const colDef = getAllColumns().find(c => c.id === colId);
        const minW = parseInt(colDef?.minWidth || '80', 10);
        const maxW = 600;

        resizer.classList.add('is-resizing');
        document.body.classList.add('is-column-resizing');

        const colEl = document.getElementById(`col-${colId}`);

        function onMouseMove(moveEvent) {
            moveEvent.preventDefault();
            const deltaX = moveEvent.pageX - startX;
            const newWidth = Math.max(minW, Math.min(maxW, startWidth + deltaX));
            const widthPx = `${newWidth}px`;

            // Live instant update on <col> tag and <th> element
            if (colEl) {
                colEl.style.width = widthPx;
            }
            th.style.width = widthPx;
            th.style.minWidth = widthPx;
        }

        function onMouseUp(upEvent) {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);

            resizer.classList.remove('is-resizing');
            document.body.classList.remove('is-column-resizing');

            const deltaX = upEvent.pageX - startX;
            const finalWidth = Math.max(minW, Math.min(maxW, startWidth + deltaX));
            const finalWidthPx = `${finalWidth}px`;

            // Save to state and localStorage
            savedColumnWidths[colId] = finalWidthPx;
            localStorage.setItem('rms_product_column_widths', JSON.stringify(savedColumnWidths));

            // Re-render so all <td> in tbody get updated synchronously
            renderTableHeaders();
            renderMasterList();

            showToast(`Column "${colDef ? colDef.label : colId}" width saved: ${finalWidthPx}`);
        }

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };

    // Close Dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const triggerBtn = document.getElementById('btnTableColumnFilter');
        if (dropdown && dropdown.classList.contains('is-active')) {
            const wrapper = document.getElementById('actionHeaderWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                dropdown.classList.remove('is-active');
                if (triggerBtn) triggerBtn.classList.remove('is-active');
            }
        }
    });

    // =========================================================================
    // DYNAMIC TABLE HEADERS & FLAT ROWS RENDERING (WITH CAT 1, CAT 2... COLUMNS)
    // =========================================================================
    function renderTableHeaders() {
        let thHtml = '';
        let colHtml = '';
        const allCols = getAllColumns();

        allCols.forEach(col => {
            if (!activeColumnIds.includes(col.id)) return;
            const alignClass = col.align === 'right' ? 'th-right' : (col.align === 'center' ? 'th-center' : '');
            const colWidth = getColumnWidth(col);
            const style = `width: ${colWidth}; min-width: ${col.minWidth || '80px'};`;
            
            colHtml += `<col id="col-${col.id}" style="width: ${colWidth};">`;
            thHtml += `
                <th class="${alignClass}" data-col-id="${col.id}" style="${style}">
                    <span class="th-content-label">${col.label}</span>
                    <div class="inv-col-resizer" onmousedown="initColumnResize(event, '${col.id}')" title="Drag to resize column"></div>
                </th>
            `;
        });

        // Action column with Column Filter Icon trigger positioned directly after Action text
        const actionColWidth = savedColumnWidths['action'] || '130px';
        colHtml += `<col id="col-action" style="width: ${actionColWidth};">`;
        thHtml += `
            <th class="th-right th-action" data-col-id="action" style="width: ${actionColWidth};">
                <div class="inv-header-action-wrapper" id="actionHeaderWrapper">
                    <span>Action</span>
                    <button type="button" class="inv-table-filter-btn" id="btnTableColumnFilter" onclick="toggleColumnFilterDropdown(event)" title="Column Display Filter">
                        <i class="ph ph-funnel"></i>
                    </button>

                    <!-- Floating Column Filter Dropdown anchored after Action Header -->
                    <div class="inv-column-dropdown-menu" id="tableColumnFilterDropdown">
                        <div class="inv-col-dropdown-header">
                            <div class="inv-col-dropdown-title-row">
                                <div class="inv-col-dropdown-title">
                                    <i class="ph ph-funnel"></i>
                                    <span>Column Visibility & Widths</span>
                                </div>
                                <button type="button" class="inv-col-quick-link is-reset" onclick="resetColumnWidths()" title="Reset all column widths to defaults">
                                    <i class="ph ph-arrow-counter-clockwise"></i>
                                    <span>Reset Widths</span>
                                </button>
                            </div>
                            <div class="inv-col-dropdown-quick-links">
                                <button type="button" class="inv-col-quick-link" onclick="showAllColumns()">
                                    <i class="ph ph-check-all"></i>
                                    <span>Show All</span>
                                </button>
                                <button type="button" class="inv-col-quick-link" onclick="resetColumnDefaults()">
                                    <i class="ph ph-columns"></i>
                                    <span>Default View</span>
                                </button>
                                <button type="button" class="inv-col-quick-link" onclick="showCompactColumns()">
                                    <i class="ph ph-rows"></i>
                                    <span>Compact View</span>
                                </button>
                            </div>
                        </div>

                        <div class="inv-col-dropdown-list" id="tableColumnsDropdownList">
                            <!-- Populated dynamically -->
                        </div>

                        <div class="inv-col-dropdown-footer">
                            <span id="tableColumnsActiveCounter">${activeColumnIds.length} of ${allCols.length} visible</span>
                            <span>Drag headers to resize</span>
                        </div>
                    </div>
                </div>
            </th>
        `;

        const dropdownPrev = document.getElementById('tableColumnFilterDropdown');
        const wasOpen = dropdownPrev && dropdownPrev.classList.contains('is-active');

        if (masterColgroup) {
            masterColgroup.innerHTML = colHtml;
        }
        masterTheadRow.innerHTML = thHtml;
        renderColumnDropdownList();

        if (wasOpen) {
            const dropdownNow = document.getElementById('tableColumnFilterDropdown');
            const triggerBtn = document.getElementById('btnTableColumnFilter');
            if (dropdownNow) dropdownNow.classList.add('is-active');
            if (triggerBtn) triggerBtn.classList.add('is-active');
        }
    }

    function renderMasterList() {
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const activeCat = window.AppStore.activeCategory || 'ALL';
        const segment = window.AppStore.catalogSegment || 'ALL';
        const allCols = getAllColumns();

        const filtered = window.AppStore.products.filter(item => {
            const catPath = getProductCategoryPath(item);
            const matchesCat = (activeCat === 'ALL') || catPath.some(c => c.toLowerCase() === activeCat.toLowerCase());
            if (!matchesCat) return false;

            if (segment === 'ACTIVE' && item.isActive === false) return false;
            if (segment === 'SELLABLE' && item.canBeSold === false) return false;
            if (segment === 'BOM' && !(item.explodeBomOnSale !== undefined ? item.explodeBomOnSale : item.hasBom)) return false;

            if (!query) return true;
            return (
                item.name.toLowerCase().includes(query) ||
                item.sku.toLowerCase().includes(query) ||
                (item.barcode && item.barcode.toLowerCase().includes(query)) ||
                catPath.some(c => c.toLowerCase().includes(query)) ||
                (item.supplier && item.supplier.toLowerCase().includes(query)) ||
                (item.unit && item.unit.toLowerCase().includes(query)) ||
                (item.allergens && item.allergens.some(a => a.toLowerCase().includes(query)))
            );
        });

        if (filteredCountEl) {
            filteredCountEl.textContent = `${filtered.length} Product${filtered.length === 1 ? '' : 's'}`;
        }
        updateWorkspaceBadges();

        const visibleCols = allCols.filter(c => activeColumnIds.includes(c.id));
        const totalVisibleCols = visibleCols.length + 1; // + 1 for action column

        if (filtered.length === 0) {
            masterTbody.innerHTML = `
                <tr>
                    <td colspan="${totalVisibleCols}">
                        <div class="inv-empty-state">
                            <i class="ph ph-package-x inv-empty-icon"></i>
                            <div style="font-weight: 600; font-size: 0.95rem; color: var(--inv-text-strong); margin-bottom: 4px;">No products match your criteria</div>
                            <div>Try adjusting your search terms or category filter.</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';

        // Flat product rows (Category Group Header rows removed in favor of dynamic Cat 1, Cat 2... columns)
        filtered.forEach(item => {
            const isSelected = (window.AppStore.selectedProductId === item.id);
            const cost = Number(item.costPrice) || 0;
            const selling = Number(item.sellingPrice) || 0;
            const profit = selling - cost;
            const marginPct = selling > 0 ? ((profit / selling) * 100).toFixed(1) : '0.0';
            const catPath = getProductCategoryPath(item);

            html += `
                <tr class="inv-item-row ${isSelected ? 'is-selected' : ''}" onclick="openProductDrawer(${item.id})">
            `;

            allCols.forEach(col => {
                if (!activeColumnIds.includes(col.id)) return;
                const colId = col.id;
                const colWidth = getColumnWidth(col);
                const style = `width: ${colWidth}; min-width: ${col.minWidth || '80px'};`;

                if (col.catLevel) {
                    const catVal = catPath[col.catLevel - 1] || '';
                    if (!catVal) {
                        html += `<td style="${style}"><span style="color: var(--inv-text-subtle);">—</span></td>`;
                    } else if (col.catLevel === 1) {
                        const itemCatMeta = getCategoryMeta(catVal);
                        const itemCatPal = getCategoryPalette(itemCatMeta.color);
                        html += `
                            <td style="${style}">
                                <span class="inv-category-pill" style="background: ${itemCatPal.bg}; color: ${itemCatPal.text}; border: 1px solid ${itemCatPal.border};">
                                    <i class="ph ${itemCatMeta.icon}"></i>
                                    <span>${catVal}</span>
                                </span>
                            </td>
                        `;
                    } else {
                        const node = getCategoryNode(catVal);
                        if (node && node.color) {
                            const pal = getCategoryPalette(node.color);
                            html += `
                                <td style="${style}">
                                    <span class="inv-category-pill" style="background: ${pal.bg}; color: ${pal.text}; border: 1px solid ${pal.border}; font-weight: 600;">
                                        <i class="ph ${node.icon || 'ph-tag'}"></i>
                                        <span>${catVal}</span>
                                    </span>
                                </td>
                            `;
                        } else {
                            html += `<td style="${style}"><span class="inv-badge-subcat">${catVal}</span></td>`;
                        }
                    }
                    return;
                }

                switch (colId) {
                    case 'sku':
                        html += `<td style="${style}"><span class="inv-sku-badge">${item.sku}</span></td>`;
                        break;
                    case 'product': {
                        const vCount = Array.isArray(item.variants) ? item.variants.length : 0;
                        const thumbHtml = item.imageUrl
                            ? `<img src="${item.imageUrl}" alt="" class="inv-prod-thumb-avatar">`
                            : '';
                        html += `
                            <td style="${style}">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    ${thumbHtml}
                                    <div class="inv-prod-title-group">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="inv-product-name">${item.name}</span>
                                            ${vCount > 0 ? `<span class="inv-variant-pill" title="${vCount} product variant(s)"><i class="ph ph-stack"></i> ${vCount}</span>` : ''}
                                        </div>
                                        <span class="inv-barcode-tag"><i class="ph ph-barcode"></i> ${item.barcode || '—'}</span>
                                    </div>
                                </div>
                            </td>
                        `;
                        break;
                    }
                    case 'description':
                        html += `
                            <td style="${style}">
                                <div class="inv-desc-cell" title="${item.description || ''}">
                                    ${item.description || '—'}
                                </div>
                            </td>
                        `;
                        break;
                    case 'variants': {
                        const vars = Array.isArray(item.variants) ? item.variants : [];
                        if (vars.length === 0) {
                            html += `<td style="${style}"><span style="color: var(--inv-text-subtle); font-size: 0.74rem;">Standard (Single)</span></td>`;
                        } else {
                            const prices = vars.map(v => Number(v.sellingPrice) || Number(item.sellingPrice) || 0);
                            const minP = Math.min(...prices);
                            const maxP = Math.max(...prices);
                            const priceRange = minP === maxP ? formatPHP(minP) : `${formatPHP(minP)} – ${formatPHP(maxP)}`;
                            const namesPreview = vars.slice(0, 2).map(v => v.name).join(', ') + (vars.length > 2 ? ` +${vars.length - 2}` : '');
                            html += `
                                <td style="${style}">
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <span class="inv-variant-pill" style="width: fit-content;"><i class="ph ph-stack"></i> ${vars.length} Variant${vars.length > 1 ? 's' : ''} (${priceRange})</span>
                                        <span style="font-size: 0.69rem; color: var(--inv-text-muted); max-width: 170px; overflow: hidden; text-overflow: ellipsis;">${namesPreview}</span>
                                    </div>
                                </td>
                            `;
                        }
                        break;
                    }
                    case 'tracking_by': {
                        const tracksStock = item.trackPhysicalStock !== undefined ? item.trackPhysicalStock : (item.trackStock !== false);
                        const tMode = tracksStock ? (item.trackingBy || 'lots') : 'none';
                        if (tMode === 'lots') {
                            html += `<td style="${style}"><span class="inv-track-badge lots" title="Lot & Expiry enabled in Stock-In"><i class="ph ph-calendar-check"></i> By Lots</span></td>`;
                        } else if (tMode === 'serial') {
                            html += `<td style="${style}"><span class="inv-track-badge serial" title="Tracked by Serial Number"><i class="ph ph-qr-code"></i> Serial #</span></td>`;
                        } else if (tMode === 'quantity') {
                            html += `<td style="${style}"><span class="inv-track-badge quantity" title="Tracked by Quantity Only"><i class="ph ph-hash"></i> By Quantity</span></td>`;
                        } else {
                            html += `<td style="${style}"><span class="inv-track-badge none"><i class="ph ph-prohibit"></i> Untracked</span></td>`;
                        }
                        break;
                    }
                    case 'uom':
                        html += `
                            <td style="${style}">
                                <span style="font-weight: 600;">${item.unit}</span>
                                <span style="color: var(--inv-text-muted); font-size: 0.72rem;">(${item.packSize || '1 pc'})</span>
                            </td>
                        `;
                        break;
                    case 'cost_price':
                        html += `<td style="${style}"><span style="color: var(--inv-text-muted); font-variant-numeric: tabular-nums;">${formatPHP(item.costPrice)}</span></td>`;
                        break;
                    case 'selling_price':
                        html += `<td style="${style}"><span style="font-weight: 700; color: var(--inv-text-strong); font-variant-numeric: tabular-nums;">${formatPHP(item.sellingPrice)}</span></td>`;
                        break;
                    case 'margin':
                        html += `<td style="${style}"><span style="font-weight: 600; color: var(--inv-success); font-variant-numeric: tabular-nums;">${marginPct}%</span></td>`;
                        break;
                    case 'tax_rate':
                        html += `<td style="${style}"><span style="font-size: 0.76rem;">${item.taxRate || '12% VAT'}</span></td>`;
                        break;
                    case 'supplier':
                        html += `<td style="${style}"><span style="font-size: 0.77rem; color: var(--inv-text-medium);">${item.supplier || '—'}</span></td>`;
                        break;
                    case 'allergens':
                        html += `
                            <td style="${style}">
                                ${(item.allergens && item.allergens.length > 0) ? item.allergens.map(a => `<span class="inv-badge-allergen">${a}</span>`).join('') : '<span style="color:var(--inv-text-subtle);">None</span>'}
                            </td>
                        `;
                        break;
                    case 'status':
                        html += `
                            <td style="${style}; text-align: left;">
                                <span class="inv-status-pill ${item.isActive ? 'active' : 'inactive'}">
                                    <span class="inv-status-dot"></span>
                                    ${item.isActive ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                        `;
                        break;
                }
            });

            html += `
                    <td class="td-right td-action">
                        <button type="button" class="inv-view-btn" onclick="event.stopPropagation(); openProductDrawer(${item.id});">
                            <i class="ph ph-sliders-horizontal"></i>
                            <span>Details</span>
                        </button>
                    </td>
                </tr>
            `;
        });

        masterTbody.innerHTML = html;
    }

    // =========================================================================
    // NEW / EDIT CATEGORY MODAL LOGIC & VISUAL CUSTOMIZATION
    // =========================================================================
    function renderCategoryIconGrid(selectedIcon) {
        const grid = document.getElementById('catIconGrid');
        if (!grid) return;
        grid.innerHTML = CATEGORY_ICONS.map(item => {
            const isActive = (item.id === selectedIcon);
            return `
                <button type="button" 
                    class="inv-cat-icon-btn ${isActive ? 'is-active' : ''}" 
                    title="${item.label}" 
                    onclick="handleSelectCategoryIcon('${item.id}')">
                    <i class="ph ${item.id}"></i>
                </button>
            `;
        }).join('');
        
        const iconObj = CATEGORY_ICONS.find(i => i.id === selectedIcon) || { label: 'General / Tag' };
        const labelEl = document.getElementById('selectedIconLabel');
        if (labelEl) labelEl.textContent = iconObj.label;
    }

    function renderCategoryColorGrid(selectedColor) {
        const grid = document.getElementById('catColorGrid');
        if (!grid) return;
        grid.innerHTML = Object.entries(CATEGORY_PALETTES).map(([key, pal]) => {
            const isActive = (key === selectedColor);
            return `
                <div class="inv-cat-color-chip ${isActive ? 'is-active' : ''}" onclick="handleSelectCategoryColor('${key}')">
                    <span class="inv-cat-color-dot" style="background: ${pal.dot};"></span>
                    <span>${pal.label.split(' / ')[0]}</span>
                </div>
            `;
        }).join('');

        const palObj = CATEGORY_PALETTES[selectedColor] || CATEGORY_PALETTES.purple;
        const labelEl = document.getElementById('selectedColorLabel');
        if (labelEl) labelEl.textContent = palObj.label;
    }

    function updateCategoryLivePreview() {
        const iconInput = document.getElementById('modalCategoryIcon');
        const colorInput = document.getElementById('modalCategoryColor');
        const nameInput = document.getElementById('modalCategoryName');
        const previewBadge = document.getElementById('catLivePreviewBadge');
        const previewIcon = document.getElementById('previewBadgeIcon');
        const previewText = document.getElementById('previewBadgeText');

        if (!previewBadge || !iconInput || !colorInput) return;

        const icon = iconInput.value || 'ph-tag';
        const colorKey = colorInput.value || 'purple';
        const pal = getCategoryPalette(colorKey);
        const catName = (nameInput && nameInput.value.trim()) ? nameInput.value.trim() : 'New Category';

        previewBadge.style.background = pal.bg;
        previewBadge.style.color = pal.text;
        previewBadge.style.borderColor = pal.border;

        if (previewIcon) previewIcon.className = `ph ${icon}`;
        if (previewText) previewText.textContent = catName;
    }

    window.handleSelectCategoryIcon = function(iconId) {
        const input = document.getElementById('modalCategoryIcon');
        if (input) input.value = iconId;
        renderCategoryIconGrid(iconId);
        updateCategoryLivePreview();
    };

    window.handleSelectCategoryColor = function(colorKey) {
        const input = document.getElementById('modalCategoryColor');
        if (input) input.value = colorKey;
        renderCategoryColorGrid(colorKey);
        updateCategoryLivePreview();
    };

    function populateModalParentSelect(selectedParentName, excludeCategoryName) {
        const parentSelect = document.getElementById('modalCategoryParent');
        if (!parentSelect) return;

        const ordered = getOrderedCategoryTreeNodes();
        const excludeLower = excludeCategoryName ? excludeCategoryName.toLowerCase() : '';

        let html = `<option value="">— Top-Level Parent (${getCategoryLevelLabel(1)}) —</option>`;
        ordered.forEach(node => {
            if (excludeLower && node.name.toLowerCase() === excludeLower) return;
            const indent = node.level > 1 ? '— '.repeat(node.level - 1) : '';
            const lvlLabel = getCategoryLevelLabel(node.level);
            html += `<option value="${node.name}">${indent}${node.name} (${lvlLabel})</option>`;
        });

        parentSelect.innerHTML = html;
        parentSelect.value = selectedParentName || '';
        handleModalParentCategoryChange(parentSelect.value);
    }

    window.handleModalParentCategoryChange = function(parentName) {
        const hintEl = document.getElementById('modalCategoryLevelHint');
        if (!hintEl) return;

        if (!parentName) {
            hintEl.textContent = `Column: ${getCategoryLevelLabel(1)} (Level 1)`;
            return;
        }
        const pNode = getCategoryNode(parentName);
        const childLevel = pNode ? (pNode.level + 1) : 2;
        const currentMax = getMaxCategoryDepth();
        const colLabel = getCategoryLevelLabel(childLevel);
        if (childLevel > currentMax) {
            hintEl.textContent = `Auto-creates new column: ${colLabel} (Level ${childLevel})`;
        } else {
            hintEl.textContent = `Column: ${colLabel} (Level ${childLevel} under ${parentName})`;
        }
    };

    window.openNewCategoryModal = function(defaultParentName) {
        document.getElementById('modalCategoryOriginalName').value = '';
        document.getElementById('modalCategoryTitle').textContent = defaultParentName
            ? `Add Child Category under "${defaultParentName}"`
            : 'Add Product Category';
        document.getElementById('btnSaveCategoryText').textContent = 'Create Category';
        document.getElementById('modalCategoryHeaderIcon').className = 'ph ph-folder-plus';
        document.getElementById('modalCategoryName').value = '';
        document.getElementById('modalCategoryDesc').value = '';

        populateModalParentSelect(defaultParentName || '', '');

        // Initialize default icon and color (inherit parent's color/icon if adding child)
        const pNode = defaultParentName ? getCategoryNode(defaultParentName) : null;
        const defaultIcon = pNode ? (pNode.icon || 'ph-tag') : 'ph-tag';
        const defaultColor = pNode ? (pNode.color || 'purple') : 'purple';
        document.getElementById('modalCategoryIcon').value = defaultIcon;
        document.getElementById('modalCategoryColor').value = defaultColor;
        renderCategoryIconGrid(defaultIcon);
        renderCategoryColorGrid(defaultColor);
        updateCategoryLivePreview();

        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
        newCategoryModal.classList.add('is-open');
        setTimeout(() => document.getElementById('modalCategoryName').focus(), 150);
    };

    window.openEditCategoryModal = function(catName) {
        if (!catName) {
            catName = window.AppStore.categories[0] || 'Beverages';
        }
        const existingNode = getCategoryNode(catName);
        document.getElementById('modalCategoryOriginalName').value = catName;
        document.getElementById('modalCategoryTitle').textContent = `Edit Category: ${catName}`;
        document.getElementById('btnSaveCategoryText').textContent = 'Update Category';
        document.getElementById('modalCategoryHeaderIcon').className = 'ph ph-pencil-simple';
        document.getElementById('modalCategoryName').value = catName;
        document.getElementById('modalCategoryDesc').value = (existingNode && existingNode.description) || window.AppStore.categoryInfo[catName] || '';

        populateModalParentSelect(existingNode ? (existingNode.parentName || '') : '', catName);

        // Hydrate existing metadata
        const meta = getCategoryMeta(catName);
        document.getElementById('modalCategoryIcon').value = meta.icon;
        document.getElementById('modalCategoryColor').value = meta.color;
        renderCategoryIconGrid(meta.icon);
        renderCategoryColorGrid(meta.color);
        updateCategoryLivePreview();

        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
        newCategoryModal.classList.add('is-open');
        setTimeout(() => document.getElementById('modalCategoryName').focus(), 150);
    };

    window.closeNewCategoryModal = function() {
        newCategoryModal.classList.remove('is-open');
        document.getElementById('modalCategoryOriginalName').value = '';
        document.getElementById('modalCategoryName').value = '';
        document.getElementById('modalCategoryDesc').value = '';
        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
    };

    function hideCategoryDuplicateWarning() {
        const warn = document.getElementById('catDuplicateWarning');
        if (warn) warn.classList.remove('is-visible');
    }

    function hideCategoryAutocomplete() {
        const drop = document.getElementById('catAutocompleteDropdown');
        if (drop) drop.classList.remove('is-open');
    }

    window.handleCategoryInputCheck = function(typedVal) {
        updateCategoryLivePreview();
        const originalName = document.getElementById('modalCategoryOriginalName').value;
        const val = typedVal.trim().toLowerCase();
        const warn = document.getElementById('catDuplicateWarning');
        const msg = document.getElementById('catDuplicateMsg');
        const drop = document.getElementById('catAutocompleteDropdown');
        const list = document.getElementById('catAutocompleteList');

        if (!val) {
            hideCategoryDuplicateWarning();
            hideCategoryAutocomplete();
            return;
        }

        // Search matching categories
        const matches = window.AppStore.categories.filter(c => c.toLowerCase().includes(val));
        const exactMatch = window.AppStore.categories.find(c => c.toLowerCase() === val);

        if (exactMatch && exactMatch.toLowerCase() !== originalName.toLowerCase()) {
            warn.classList.add('is-visible');
            msg.textContent = `A category named "${exactMatch}" already exists!`;
        } else {
            hideCategoryDuplicateWarning();
        }

        if (matches.length > 0) {
            list.innerHTML = matches.map(c => `
                <div class="inv-cat-dropdown-item" onclick="selectExistingCategory('${c}')">
                    <span>${c}</span>
                    <i class="ph ph-arrow-up-left" style="font-size: 13px; color: var(--inv-text-muted);"></i>
                </div>
            `).join('');
            drop.classList.add('is-open');
        } else {
            hideCategoryAutocomplete();
        }
    };

    window.selectExistingCategory = function(name) {
        document.getElementById('modalCategoryName').value = name;
        hideCategoryAutocomplete();
        handleCategoryInputCheck(name);
    };

    window.handleCreateCategory = function(e) {
        e.preventDefault();
        const catName = document.getElementById('modalCategoryName').value.trim();
        const catDesc = document.getElementById('modalCategoryDesc').value.trim();
        const catIcon = document.getElementById('modalCategoryIcon').value.trim() || 'ph-tag';
        const catColor = document.getElementById('modalCategoryColor').value.trim() || 'purple';
        const parentSelect = document.getElementById('modalCategoryParent');
        const parentName = (parentSelect && parentSelect.value.trim()) ? parentSelect.value.trim() : null;
        const originalName = document.getElementById('modalCategoryOriginalName').value;

        if (!catName) return;

        // Check duplicate
        const isDuplicate = (window.AppStore.categoryNodes || []).some(n =>
            n.name.toLowerCase() === catName.toLowerCase() && n.name.toLowerCase() !== originalName.toLowerCase()
        );

        if (isDuplicate) {
            showToast(`Category "${catName}" already exists!`);
            const warn = document.getElementById('catDuplicateWarning');
            if (warn) warn.classList.add('is-visible');
            return;
        }

        const parentNode = parentName ? getCategoryNode(parentName) : null;
        const newLevel = parentNode ? (parentNode.level + 1) : 1;

        if (!window.AppStore.categoryLevelNames[newLevel]) {
            window.AppStore.categoryLevelNames[newLevel] = `Cat ${newLevel}`;
        }

        if (originalName) {
            // Edit Mode
            const existingNode = getCategoryNode(originalName);
            if (existingNode) {
                existingNode.name = catName;
                existingNode.parentName = parentName;
                existingNode.level = newLevel;
                existingNode.icon = catIcon;
                existingNode.color = catColor;
                existingNode.description = catDesc;
            }
            // Update children pointing to originalName
            (window.AppStore.categoryNodes || []).forEach(n => {
                if (n.parentName && n.parentName.toLowerCase() === originalName.toLowerCase()) {
                    n.parentName = catName;
                }
            });
            // Migrate products containing originalName in categoryPath / category / subcategory
            window.AppStore.products.forEach(p => {
                const path = getProductCategoryPath(p);
                const updatedPath = path.map(c => (c.toLowerCase() === originalName.toLowerCase() ? catName : c));
                p.categoryPath = updatedPath;
                p.category = updatedPath[0] || 'Uncategorized';
                p.subcategory = updatedPath[1] || 'Standard';
            });

            if (originalName !== catName) {
                delete window.AppStore.categoryInfo[originalName];
                delete window.AppStore.categoryMeta[originalName];
            }
            showToast(`Category "${catName}" (${getCategoryLevelLabel(newLevel)}) updated!`);
        } else {
            // Add Mode
            window.AppStore.categoryNodes.push({
                name: catName,
                parentName: parentName,
                level: newLevel,
                icon: catIcon,
                color: catColor,
                description: catDesc
            });

            // Ensure new level column is visible in table
            const colId = `cat_level_${newLevel}`;
            if (!activeColumnIds.includes(colId)) {
                activeColumnIds.push(colId);
                localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
            }

            // If we are inside the Product Details tab, auto-select the newly created category at its level
            if (parentName && currentFormCategoryPath[newLevel - 2] && currentFormCategoryPath[newLevel - 2].toLowerCase() === parentName.toLowerCase()) {
                currentFormCategoryPath[newLevel - 1] = catName;
            } else if (!parentName && !currentFormCategoryPath[0]) {
                currentFormCategoryPath[0] = catName;
            }

            showToast(`Category "${catName}" created in ${getCategoryLevelLabel(newLevel)}!`);
        }

        // Recompute tree levels cleanly
        getOrderedCategoryTreeNodes();
        window.AppStore.categoryInfo[catName] = catDesc;
        window.AppStore.categoryMeta[catName] = { icon: catIcon, color: catColor };

        persistCategoryHierarchy();
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.removeItem('rms_stocks_ledger');

        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        closeNewCategoryModal();
    };

    window.confirmDeleteCategory = function(catName, itemCount) {
        if (catName === 'Uncategorized') {
            showToast('The "Uncategorized" section cannot be deleted.');
            return;
        }

        const affectedCount = typeof itemCount === 'number'
            ? itemCount
            : window.AppStore.products.filter(p => getProductCategoryPath(p).some(c => c.toLowerCase() === catName.toLowerCase())).length;

        const confirmText = affectedCount > 0 
            ? `Are you sure you want to delete category "${catName}"? ${affectedCount} ${affectedCount === 1 ? 'product' : 'products'} using it will be updated.`
            : `Are you sure you want to delete category "${catName}"?`;

        if (!confirm(confirmText)) return;

        const nodeToDelete = getCategoryNode(catName);
        const parentOfDeleted = nodeToDelete ? nodeToDelete.parentName : null;

        // Re-parent any child categories up one level
        (window.AppStore.categoryNodes || []).forEach(n => {
            if (n.parentName && n.parentName.toLowerCase() === catName.toLowerCase()) {
                n.parentName = parentOfDeleted || null;
            }
        });

        // Remove node
        window.AppStore.categoryNodes = (window.AppStore.categoryNodes || []).filter(
            n => n.name.toLowerCase() !== catName.toLowerCase()
        );

        // Update products
        window.AppStore.products.forEach(p => {
            const path = getProductCategoryPath(p).filter(c => c.toLowerCase() !== catName.toLowerCase());
            if (path.length === 0) {
                path.push('Uncategorized');
                if (!getCategoryNode('Uncategorized')) {
                    window.AppStore.categoryNodes.push({
                        name: 'Uncategorized',
                        parentName: null,
                        level: 1,
                        icon: 'ph-tag',
                        color: 'slate',
                        description: 'Default fallback category'
                    });
                }
            }
            p.categoryPath = path;
            p.category = path[0];
            p.subcategory = path[1] || 'Standard';
        });

        delete window.AppStore.categoryInfo[catName];
        delete window.AppStore.categoryMeta[catName];

        getOrderedCategoryTreeNodes();
        persistCategoryHierarchy();
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.removeItem('rms_stocks_ledger');

        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        showToast(`Category "${catName}" removed.`);
    };

    // =========================================================================
    // CATEGORY SETTINGS TAB CONTROLLER (COLUMN RENAMER & HIERARCHY TREE)
    // =========================================================================
    function renderCategorySettingsTab() {
        const levelListEl = document.getElementById('categoryLevelNamesList');
        const treeBodyEl = document.getElementById('categoryHierarchyTreeBody');
        const depthBadgeEl = document.getElementById('catLevelDepthBadge');
        const maxDepth = getMaxCategoryDepth();

        if (depthBadgeEl) {
            depthBadgeEl.textContent = `${maxDepth} ${maxDepth === 1 ? 'Level' : 'Levels'}`;
        }

        // 1. Render Category Level Column Cards (Spacious Industrial Redesign)
        if (levelListEl) {
            let lvlHtml = '';
            for (let lvl = 1; lvl <= maxDepth; lvl++) {
                const currentLabel = getCategoryLevelLabel(lvl);
                const countAtLevel = (window.AppStore.categoryNodes || []).filter(n => n.level === lvl).length;
                const prodCountAtLevel = (window.AppStore.products || []).filter(p => {
                    const path = getProductCategoryPath(p);
                    return path && path.length >= lvl && Boolean(path[lvl - 1]);
                }).length;

                // Deletion rule:
                // Can delete if:
                // 1. It is higher than Level 1 (Level 1 is the mandatory root category column)
                // 2. 0 categories exist at this level (countAtLevel === 0)
                // 3. 0 products have this level in their path (prodCountAtLevel === 0)
                // 4. It is the outermost level (lvl === maxDepth)
                const isTopLevel = (lvl === maxDepth && maxDepth > 1);
                const canDelete = (isTopLevel && countAtLevel === 0 && prodCountAtLevel === 0);
                const lvlRole = lvl === 1 ? 'Root Category' : (lvl === 2 ? 'Subcategory' : `Level ${lvl} Child`);
                const lvlPillClass = lvl <= 5 ? `lvl-${lvl}` : 'lvl-5';

                lvlHtml += `
                    <div class="inv-level-card ${canDelete ? 'is-deletable' : ''}" data-level="${lvl}">
                        <div class="inv-level-card-header">
                            <div class="inv-level-card-left">
                                <span class="inv-level-pill ${lvlPillClass}">
                                    <i class="ph ${lvl === 1 ? 'ph-tree-view' : (lvl === 2 ? 'ph-git-fork' : 'ph-folder-notch-open')}"></i>
                                    <span>Level ${lvl}</span>
                                </span>
                                <span class="inv-level-type-label">${lvlRole}</span>
                            </div>
                            <div class="inv-level-card-actions">
                                <span class="inv-level-usage-pill ${countAtLevel > 0 ? 'is-active' : 'is-empty'}" title="${countAtLevel} categories, ${prodCountAtLevel} products at Level ${lvl}">
                                    <i class="ph ${countAtLevel > 0 ? 'ph-check-circle' : 'ph-circle'}"></i>
                                    <span>${countAtLevel} ${countAtLevel === 1 ? 'cat' : 'cats'}</span>
                                    <span class="inv-level-usage-dot">&bull;</span>
                                    <span>${prodCountAtLevel} ${prodCountAtLevel === 1 ? 'item' : 'items'}</span>
                                </span>
                                ${canDelete ? `
                                    <button type="button" class="inv-level-del-btn" onclick="deleteCategoryLevelColumn(${lvl})" title="Delete Level ${lvl} Column (Unused by any categories or items)">
                                        <i class="ph ph-trash"></i>
                                        <span>Delete Level</span>
                                    </button>
                                ` : (lvl === 1 ? `
                                    <span class="inv-level-lock-pill" title="Level 1 is the mandatory primary category column">
                                        <i class="ph ph-lock-key"></i>
                                        <span>Base</span>
                                    </span>
                                ` : `
                                    <span class="inv-level-lock-pill" title="${countAtLevel > 0 ? `In use by ${countAtLevel} categories` : `Delete Level ${maxDepth} first`}">
                                        <i class="ph ph-lock-key"></i>
                                        <span>In Use</span>
                                    </span>
                                `)}
                            </div>
                        </div>
                        <div class="inv-level-card-body">
                            <label class="inv-level-input-label" for="catLevelInput_${lvl}">
                                <span>Catalog Table Header</span>
                                <span class="inv-level-col-target">Column ID: <code>cat_level_${lvl}</code></span>
                            </label>
                            <div class="inv-level-input-wrap">
                                <i class="ph ph-text-t inv-level-input-icon"></i>
                                <input type="text"
                                    id="catLevelInput_${lvl}"
                                    class="inv-form-input cat-level-rename-input"
                                    data-level="${lvl}"
                                    value="${currentLabel.replace(/"/g, '&quot;')}"
                                    placeholder="e.g. Cat ${lvl}, Department, Type..."
                                    onchange="handleInlineLevelRename(${lvl}, this.value)"
                                    autocomplete="off">
                                <span class="inv-level-save-indicator" id="levelSaveInd_${lvl}" title="Saved">
                                    <i class="ph ph-check"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }
            levelListEl.innerHTML = lvlHtml;
        }

        // 2. Render Parent & Child Category Tree Table
        if (treeBodyEl) {
            const ordered = getOrderedCategoryTreeNodes();
            const searchInputEl = document.getElementById('catTreeSearchInput');
            const filterText = (searchInputEl && searchInputEl.value || '').trim().toLowerCase();

            const filteredNodes = ordered.filter(node => {
                if (!filterText) return true;
                return (
                    node.name.toLowerCase().includes(filterText) ||
                    (node.parentName && node.parentName.toLowerCase().includes(filterText)) ||
                    getCategoryLevelLabel(node.level).toLowerCase().includes(filterText)
                );
            });

            if (filteredNodes.length === 0) {
                treeBodyEl.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px; color: var(--inv-text-muted);">
                            No categories match your filter. Click <strong>Add Root Category</strong> to create one.
                        </td>
                    </tr>
                `;
                return;
            }

            treeBodyEl.innerHTML = filteredNodes.map(node => {
                const pal = getCategoryPalette(node.color || 'purple');
                const lvlLabel = getCategoryLevelLabel(node.level);
                const lvlClass = node.level <= 3 ? `lvl-${node.level}` : '';
                const indentPx = (node.level - 1) * 24;
                const connector = node.level > 1 ? `<span class="inv-cat-tree-connector">└─</span>` : '';
                const prodCount = (window.AppStore.products || []).filter(p =>
                    getProductCategoryPath(p).some(c => c.toLowerCase() === node.name.toLowerCase())
                ).length;
                const safeName = node.name.replace(/'/g, "\\'");
                const isUncat = node.name === 'Uncategorized';

                return `
                    <tr class="inv-cat-tree-row">
                        <td>
                            <div class="inv-cat-tree-branch" style="padding-left: ${indentPx}px;">
                                ${connector}
                                <span class="inv-category-pill" style="background: ${pal.bg}; color: ${pal.text}; border: 1px solid ${pal.border};">
                                    <i class="ph ${node.icon || 'ph-tag'}"></i>
                                    <span>${node.name}</span>
                                </span>
                                ${node.description ? `<span style="font-size: 0.72rem; color: var(--inv-text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${node.description}">${node.description}</span>` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="inv-cat-level-pill ${lvlClass}">
                                ${lvlLabel} &bull; Level ${node.level}
                            </span>
                        </td>
                        <td>
                            ${node.parentName
                                ? `<span class="inv-badge-subcat">${node.parentName}</span>`
                                : `<span style="font-size: 0.73rem; color: var(--inv-text-muted); font-weight: 600;">Root (Top-Level)</span>`
                            }
                        </td>
                        <td>
                            <span style="font-weight: 700; font-variant-numeric: tabular-nums;">${prodCount}</span>
                            <span style="font-size: 0.72rem; color: var(--inv-text-muted);">${prodCount === 1 ? 'item' : 'items'}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <button type="button" class="inv-btn-quick-create" onclick="openNewCategoryModal('${safeName}')" title="Add Child Category under ${node.name} (Level ${node.level + 1})">
                                    <i class="ph ph-plus"></i>
                                    <span>Add Child</span>
                                </button>
                                <button type="button" class="inv-group-action-btn" onclick="openEditCategoryModal('${safeName}')" title="Edit ${node.name}">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                ${!isUncat ? `
                                    <button type="button" class="inv-group-action-btn is-delete" onclick="confirmDeleteCategory('${safeName}', ${prodCount})" title="Delete ${node.name}">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    }

    window.handleInlineLevelRename = function(level, newLabel) {
        const cleaned = String(newLabel || '').trim() || `Cat ${level}`;
        window.AppStore.categoryLevelNames[level] = cleaned;
        persistCategoryHierarchy();
        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();

        const ind = document.getElementById(`levelSaveInd_${level}`);
        if (ind) {
            ind.classList.add('is-saved');
            setTimeout(() => ind.classList.remove('is-saved'), 1500);
        }

        showToast(`Level ${level} column renamed to "${cleaned}"`);
    };

    window.deleteCategoryLevelColumn = function(lvl) {
        lvl = Number(lvl);
        if (lvl <= 1) {
            showToast('Level 1 is the primary category column and cannot be deleted.', 'error');
            return;
        }

        const nodesAtLevel = (window.AppStore.categoryNodes || []).filter(n => n.level === lvl);
        if (nodesAtLevel.length > 0) {
            showToast(`Cannot delete Level ${lvl}: ${nodesAtLevel.length} categories exist at this level. Delete them first in the hierarchy tree.`, 'error');
            return;
        }

        const productsAtLevel = (window.AppStore.products || []).filter(p => {
            const path = getProductCategoryPath(p);
            return path && path.length >= lvl && Boolean(path[lvl - 1]);
        });
        if (productsAtLevel.length > 0) {
            showToast(`Cannot delete Level ${lvl}: ${productsAtLevel.length} products currently belong to this level.`, 'error');
            return;
        }

        const colName = getCategoryLevelLabel(lvl);
        if (!confirm(`Are you sure you want to remove the Level ${lvl} ("${colName}") column? This will remove the column from the catalog table.`)) {
            return;
        }

        // 1. Remove from categoryLevelNames
        delete window.AppStore.categoryLevelNames[lvl];

        // 2. Remove from activeColumnIds
        const colId = `cat_level_${lvl}`;
        if (Array.isArray(activeColumnIds)) {
            activeColumnIds = activeColumnIds.filter(id => id !== colId);
            localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        }

        // 3. Remove from knownCatCols
        let knownCatCols = JSON.parse(localStorage.getItem('rms_known_cat_cols')) || [];
        knownCatCols = knownCatCols.filter(id => id !== colId);
        localStorage.setItem('rms_known_cat_cols', JSON.stringify(knownCatCols));

        // 4. Persist and refresh all UI surfaces
        persistCategoryHierarchy();
        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        renderCategorySettingsTab();

        showToast(`Level ${lvl} column ("${colName}") removed successfully.`, 'success');
    };

    window.saveCategoryLevelNamesFromUI = function() {
        const inputs = document.querySelectorAll('.cat-level-rename-input');
        inputs.forEach(input => {
            const lvl = Number(input.getAttribute('data-level'));
            if (lvl) {
                window.AppStore.categoryLevelNames[lvl] = input.value.trim() || `Cat ${lvl}`;
            }
        });
        persistCategoryHierarchy();
        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        renderCategorySettingsTab();
        showToast('Category column names saved & updated across table!');
    };

    window.addCategoryLevelColumn = function() {
        const nextLevel = getMaxCategoryDepth() + 1;
        window.AppStore.categoryLevelNames[nextLevel] = `Cat ${nextLevel}`;
        const colId = `cat_level_${nextLevel}`;
        if (!activeColumnIds.includes(colId)) {
            activeColumnIds.push(colId);
            localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        }
        persistCategoryHierarchy();
        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        renderCategorySettingsTab();
        showToast(`Added new column "${getCategoryLevelLabel(nextLevel)}" (Level ${nextLevel})`);
    };

    window.resetCategoryLevelNames = function() {
        // Prune empty unused trailing levels down to actual deepest category node or product path (min 2)
        let actualMax = 2;
        if (window.AppStore) {
            if (Array.isArray(window.AppStore.categoryNodes)) {
                window.AppStore.categoryNodes.forEach(n => {
                    if (n.level && n.level > actualMax) actualMax = n.level;
                });
            }
            if (Array.isArray(window.AppStore.products)) {
                window.AppStore.products.forEach(p => {
                    const path = getProductCategoryPath(p);
                    if (path && path.length > actualMax) actualMax = path.length;
                });
            }
        }
        window.AppStore.categoryLevelNames = {};
        for (let lvl = 1; lvl <= actualMax; lvl++) {
            window.AppStore.categoryLevelNames[lvl] = `Cat ${lvl}`;
        }
        // Clean up any orphan cat_level_N from activeColumnIds
        if (Array.isArray(activeColumnIds)) {
            activeColumnIds = activeColumnIds.filter(id => {
                if (id.startsWith('cat_level_')) {
                    const num = Number(id.replace('cat_level_', ''));
                    return num <= actualMax;
                }
                return true;
            });
            localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        }
        persistCategoryHierarchy();
        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        renderCategorySettingsTab();
        showToast('Category columns reset to default Cat 1, Cat 2...');
    };

    searchInput.addEventListener('input', function(e) {
        window.AppStore.searchQuery = e.target.value;
        renderMasterList();
    });

    // =========================================================================
    // SLIDE-OUT DETAIL DRAWER: PAGINATION & SUBTABLE CONTROLLERS
    // =========================================================================
    let currentDrawerBranches = [];
    let currentDrawerLocations = [];
    let currentDrawerLedgers = [];

    const locationSubtableBody = document.getElementById('locationStockSubtableBody');

    function renderBranchSubtablePage() {
        const page = window.AppStore.branchPage || 1;
        const size = window.AppStore.branchPageSize || 4;
        const total = currentDrawerBranches.length;
        const totalPages = Math.max(1, Math.ceil(total / size));
        const start = (page - 1) * size;
        const pageItems = currentDrawerBranches.slice(start, start + size);

        const pageInfoEl = document.getElementById('branchStockPageInfo');
        const prevBtn = document.getElementById('btnBranchPrev');
        const nextBtn = document.getElementById('btnBranchNext');
        if (pageInfoEl) pageInfoEl.textContent = `Page ${page} of ${totalPages} (${total} total)`;
        if (prevBtn) prevBtn.disabled = (page <= 1);
        if (nextBtn) nextBtn.disabled = (page >= totalPages);

        if (!branchSubtableBody) return;
        if (pageItems.length > 0) {
            branchSubtableBody.innerHTML = pageItems.map(b => {
                const isLow = b.onHand <= b.reorder;
                return `
                    <tr>
                        <td><strong>${b.name}</strong></td>
                        <td class="td-right" style="font-weight: 600;">${b.onHand}</td>
                        <td class="td-right" style="color: var(--inv-text-muted);">${b.reserved || 0}</td>
                        <td class="td-right">
                            <span class="inv-stock-status-pill ${isLow ? 'low-stock' : 'in-stock'}">
                                ${isLow ? 'Low Stock' : 'Optimal'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            branchSubtableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 16px; color: var(--inv-text-muted);">No branch inventory mapped.</td></tr>`;
        }
    }

    window.navigateBranchPage = function(delta) {
        const totalPages = Math.max(1, Math.ceil(currentDrawerBranches.length / (window.AppStore.branchPageSize || 4)));
        const newPage = (window.AppStore.branchPage || 1) + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            window.AppStore.branchPage = newPage;
            renderBranchSubtablePage();
        }
    };

    function renderLocationSubtablePage() {
        const page = window.AppStore.locationPage || 1;
        const size = window.AppStore.locationPageSize || 4;
        const total = currentDrawerLocations.length;
        const totalPages = Math.max(1, Math.ceil(total / size));
        const start = (page - 1) * size;
        const pageItems = currentDrawerLocations.slice(start, start + size);

        const pageInfoEl = document.getElementById('locationStockPageInfo');
        const prevBtn = document.getElementById('btnLocationPrev');
        const nextBtn = document.getElementById('btnLocationNext');
        if (pageInfoEl) pageInfoEl.textContent = `Page ${page} of ${totalPages} (${total} total)`;
        if (prevBtn) prevBtn.disabled = (page <= 1);
        if (nextBtn) nextBtn.disabled = (page >= totalPages);

        if (!locationSubtableBody) return;
        if (pageItems.length > 0) {
            locationSubtableBody.innerHTML = pageItems.map(loc => `
                <tr>
                    <td><strong>${loc.facility}</strong></td>
                    <td>
                        <span style="font-weight: 600; color: var(--inv-text-strong);">${loc.zone}</span>
                    </td>
                    <td>${loc.rack}</td>
                    <td>
                        <span class="inv-bin-chip" title="Physical Storage Barcode">
                            <i class="ph ph-barcode"></i>
                            <span>${loc.bin}</span>
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 0.70rem; font-weight: 600; padding: 2px 7px; border-radius: 4px; background: #f1f5f9; color: #475569;">
                            ${loc.behavior || 'Storage'}
                        </span>
                    </td>
                    <td class="td-right" style="font-weight: 700; color: var(--inv-text-strong);">${loc.onHand}</td>
                    <td class="td-right" style="color: var(--inv-text-muted);">${loc.reserved || 0}</td>
                    <td class="td-right" style="font-weight: 700; color: var(--inv-success);">${loc.available || (loc.onHand - (loc.reserved || 0))}</td>
                </tr>
            `).join('');
        } else {
            locationSubtableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 16px; color: var(--inv-text-muted);">No specific storage zones or bin locations allocated.</td></tr>`;
        }
    }

    window.navigateLocationPage = function(delta) {
        const totalPages = Math.max(1, Math.ceil(currentDrawerLocations.length / (window.AppStore.locationPageSize || 4)));
        const newPage = (window.AppStore.locationPage || 1) + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            window.AppStore.locationPage = newPage;
            renderLocationSubtablePage();
        }
    };

    function renderLedgerSubtablePage() {
        const page = window.AppStore.ledgerPage || 1;
        const size = window.AppStore.ledgerPageSize || 4;
        const total = currentDrawerLedgers.length;
        const totalPages = Math.max(1, Math.ceil(total / size));
        const start = (page - 1) * size;
        const pageItems = currentDrawerLedgers.slice(start, start + size);

        const pageInfoEl = document.getElementById('ledgerLogPageInfo');
        const prevBtn = document.getElementById('btnLedgerPrev');
        const nextBtn = document.getElementById('btnLedgerNext');
        if (pageInfoEl) pageInfoEl.textContent = `Page ${page} of ${totalPages} (${total} total)`;
        if (prevBtn) prevBtn.disabled = (page <= 1);
        if (nextBtn) nextBtn.disabled = (page >= totalPages);

        if (!ledgerSubtableBody) return;
        if (pageItems.length > 0) {
            ledgerSubtableBody.innerHTML = pageItems.map(l => {
                const isPositive = String(l.qty).startsWith('+');
                let badgeClass = 'adj';
                const typeLower = (l.type || '').toLowerCase();
                if (typeLower.includes('stock in') || typeLower.includes('grn') || typeLower.includes('receive')) badgeClass = 'in';
                else if (typeLower.includes('transfer') || typeLower.includes('trf')) badgeClass = 'trf';
                else if (typeLower.includes('sale') || typeLower.includes('pos')) badgeClass = 'pos';
                else if (typeLower.includes('bom') || typeLower.includes('recipe') || typeLower.includes('production')) badgeClass = 'bom';

                return `
                    <tr>
                        <td>
                            <strong>${l.ref}</strong>
                            <div style="font-size: 0.69rem; color: var(--inv-text-muted);">${l.date || 'Today'}</div>
                        </td>
                        <td>
                            <span class="inv-ledger-type-badge ${badgeClass}">${l.type}</span>
                        </td>
                        <td>
                            <span class="inv-ledger-route-pill src" title="Origin Source: ${l.source || 'Vendor'}">
                                <i class="ph ph-arrow-up-right"></i>
                                <span>${l.source || 'Vendor Supply'}</span>
                            </span>
                        </td>
                        <td>
                            <span class="inv-ledger-route-pill dst" title="Target Destination: ${l.destination || 'Storage'}">
                                <i class="ph ph-arrow-down-right"></i>
                                <span>${l.destination || 'Main Warehouse'}</span>
                            </span>
                        </td>
                        <td class="td-right" style="font-weight: 700; color: ${isPositive ? 'var(--inv-success)' : 'var(--inv-danger)'};">${l.qty}</td>
                        <td class="td-right" style="font-weight: 800; color: var(--inv-text-strong);">${l.balance}</td>
                    </tr>
                `;
            }).join('');
        } else {
            ledgerSubtableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 16px; color: var(--inv-text-muted);">No recent ledger activity recorded.</td></tr>`;
        }
    }

    window.navigateLedgerPage = function(delta) {
        const totalPages = Math.max(1, Math.ceil(currentDrawerLedgers.length / (window.AppStore.ledgerPageSize || 4)));
        const newPage = (window.AppStore.ledgerPage || 1) + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            window.AppStore.ledgerPage = newPage;
            renderLedgerSubtablePage();
        }
    };

    // =========================================================================
    // PRICING TAB: COLUMN CHART & SUPPLIER BENCHMARKS (LAST 3 MONTHS)
    // =========================================================================
    function getMonthLabels3Mos() {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const d = new Date();
        const res = [];
        for (let i = 2; i >= 0; i--) {
            const pastDate = new Date(d.getFullYear(), d.getMonth() - i, 1);
            res.push(`${monthNames[pastDate.getMonth()]} ${pastDate.getFullYear()}`);
        }
        return res;
    }

    function renderPriceHistoryChart(baseCost, baseSelling) {
        const canvas = document.getElementById('priceHistoryChart');
        if (!canvas) return;

        if (window.AppStore.activePriceChart) {
            window.AppStore.activePriceChart.destroy();
            window.AppStore.activePriceChart = null;
        }

        const months = getMonthLabels3Mos();
        const badgeEl = document.getElementById('chartMonthsBadge');
        if (badgeEl) badgeEl.textContent = `${months[0]} - ${months[2]}`;

        if (typeof Chart === 'undefined') return;

        // Compute realistic monthly trend points for past 3 months
        const cVal = Number(baseCost) || 50;
        const sVal = Number(baseSelling) || (cVal * 1.6);

        const costData = [
            Math.round(cVal * 0.94 * 100) / 100,
            Math.round(cVal * 0.97 * 100) / 100,
            Math.round(cVal * 1.00 * 100) / 100
        ];
        const sellingData = [
            Math.round(sVal * 0.98 * 100) / 100,
            Math.round(sVal * 1.00 * 100) / 100,
            Math.round(sVal * 1.00 * 100) / 100
        ];

        const ctx = canvas.getContext('2d');
        window.AppStore.activePriceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Cost Price (₱)',
                        data: costData,
                        backgroundColor: 'rgba(168, 85, 247, 0.75)',
                        borderColor: '#a855f7',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Selling Price (₱)',
                        data: sellingData,
                        backgroundColor: 'rgba(59, 130, 246, 0.65)',
                        borderColor: '#3b82f6',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Poppins', size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${Number(context.raw).toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₱' + value; },
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 11, weight: '500' } }
                    }
                }
            }
        });
    }

    function renderSupplierPriceSection(product, isNew) {
        let suppliers = [];
        const baseCost = Number(product?.costPrice) || 50;

        if (product && product.supplierBenchmarks && product.supplierBenchmarks.length > 0) {
            suppliers = product.supplierBenchmarks;
        } else {
            // Generate standard mock supplier quotes based on product context
            const primarySup = (product && product.supplier) ? product.supplier : 'Direct Import Supply Corp';
            suppliers = [
                { name: primarySup, price: Math.round(baseCost * 100) / 100, updated: '2 days ago' },
                { name: 'Apex Premier Distro', price: Math.round(baseCost * 1.08 * 100) / 100, updated: '1 week ago' },
                { name: 'Global Goods Wholesale Hub', price: Math.round(baseCost * 0.95 * 100) / 100, updated: 'Yesterday' }
            ];
        }

        // Find lowest affordable price
        const minPrice = Math.min(...suppliers.map(s => Number(s.price)));

        // Automatically fill Cost Price = Lowest Price + 5% Margin if new or user requests
        const recommendedCost = (minPrice * 1.05).toFixed(2);
        if (isNew || !formCostPrice.value || Number(formCostPrice.value) === 0) {
            formCostPrice.value = recommendedCost;
            updateMarginCalculation();
        }

        supplierPricesListBody.innerHTML = suppliers.map(s => {
            const isLowest = (Number(s.price) === minPrice);
            return `
                <div class="inv-supplier-item ${isLowest ? 'is-lowest' : ''}">
                    <div>
                        <div class="inv-supplier-name">
                            ${s.name}
                            ${isLowest ? '<span class="inv-supplier-best-badge">Most Affordable</span>' : ''}
                        </div>
                        <div class="inv-supplier-updated">Updated: ${s.updated}</div>
                    </div>
                    <div class="inv-supplier-price-box">
                        <div class="inv-supplier-price">${formatPHP(s.price)}</div>
                        ${isLowest ? '<div style="font-size: 0.64rem; color: var(--inv-success); font-weight: 600;">+5% = ₱' + (s.price * 1.05).toFixed(2) + '</div>' : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // =========================================================================
    // TOP-LEVEL WORKSPACE TABS & INLINE PRODUCT DETAILS TAB LOGIC
    // =========================================================================
    window.syncDetailStatusPill = function(isActive) {
        const pill = document.getElementById('detailHeaderStatusPill');
        const text = document.getElementById('detailHeaderStatusText');
        if (!pill || !text) return;
        pill.className = `inv-status-pill ${isActive ? 'active' : 'inactive'}`;
        text.textContent = isActive ? 'Active' : 'Inactive';
    };

    window.switchMasterWorkspaceTab = function(workspaceKey, skipDefaultHydration) {
        window.AppStore.activeWorkspaceTab = workspaceKey;

        document.querySelectorAll('#itemMasterWorkspaceTabs .rfq-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-workspace') === workspaceKey);
        });

        const paneCatalog = document.getElementById('workspace-catalog');
        const paneDetails = document.getElementById('workspace-details');
        const paneCategories = document.getElementById('workspace-categories');

        if (paneCatalog) paneCatalog.classList.toggle('active', workspaceKey === 'catalog');
        if (paneDetails) paneDetails.classList.toggle('active', workspaceKey === 'details');
        if (paneCategories) paneCategories.classList.toggle('active', workspaceKey === 'categories');

        if (workspaceKey === 'details' && !skipDefaultHydration && (!formSku || !formSku.value)) {
            const firstProd = (window.AppStore.products || [])[0];
            if (firstProd) {
                window.openProductDrawer(firstProd.id);
            } else {
                window.openProductDrawer('new');
            }
            return;
        }

        if (workspaceKey === 'categories') {
            renderCategorySettingsTab();
        }
    };

    // =========================================================================
    // PRODUCT IMAGE UPLOAD, TRACKING METHOD (LOTS/QTY/SERIAL) & VARIANTS ENGINE
    // =========================================================================
    let currentDrawerVariants = [];

    function renderProductImageState(dataUrl) {
        const hiddenInput = document.getElementById('formProductImageData');
        const previewImg = document.getElementById('productImagePreview');
        const placeholder = document.getElementById('productImagePlaceholder');
        const removeBtn = document.getElementById('btnRemoveProductImage');
        const fileInput = document.getElementById('formProductImageInput');

        const cleanUrl = dataUrl || '';
        if (hiddenInput) hiddenInput.value = cleanUrl;
        if (! cleanUrl && fileInput) fileInput.value = '';

        if (cleanUrl) {
            if (previewImg) {
                previewImg.src = cleanUrl;
                previewImg.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
            if (removeBtn) removeBtn.style.display = 'inline-flex';
        } else {
            if (previewImg) {
                previewImg.src = '';
                previewImg.style.display = 'none';
            }
            if (placeholder) placeholder.style.display = 'flex';
            if (removeBtn) removeBtn.style.display = 'none';
        }
    }

    window.handleProductImageUpload = function(event) {
        const file = event && event.target && event.target.files ? event.target.files[0] : null;
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            showToast('Please select a valid image file (PNG, JPG, WEBP).');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(evt) {
            renderProductImageState(evt.target.result);
            showToast('Product image uploaded and staged.');
        };
        reader.readAsDataURL(file);
    };

    window.clearProductImage = function(event) {
        if (event) event.stopPropagation();
        renderProductImageState('');
        showToast('Product image removed.');
    };

    window.selectTrackingByMethod = function(mode) {
        const validMode = ['lots', 'quantity', 'serial'].includes(mode) ? mode : 'lots';
        const hiddenInput = document.getElementById('formTrackingBy');
        if (hiddenInput) hiddenInput.value = validMode;

        document.querySelectorAll('#trackByOptionsGroup .inv-track-mode-card').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-track-mode') === validMode);
        });

        const invBadge = document.getElementById('inventoryTabTrackingBadge');
        const footerHint = document.getElementById('detailFooterTrackingHint');
        const isTrackChecked = formTrackStock ? formTrackStock.checked : true;

        const labelMap = {
            lots: 'By Lots (Shows Lot & Expiry in Stock-In)',
            quantity: 'By Quantity (Unit count only)',
            serial: 'By Serial Number (Unique unit ID)'
        };
        if (invBadge) {
            invBadge.textContent = isTrackChecked ? `Tracked: ${labelMap[validMode]}` : 'Untracked Item';
        }
        if (footerHint) {
            footerHint.textContent = isTrackChecked
                ? `Inventory Tracking: ${labelMap[validMode]}`
                : 'Non-physical / untracked item';
        }
    };

    window.handleTrackStockToggle = function(checked) {
        const panel = document.getElementById('trackByOptionsGroup');
        if (panel) {
            panel.classList.toggle('is-disabled', !checked);
        }
        const currentMode = (document.getElementById('formTrackingBy') || {}).value || 'lots';
        window.selectTrackingByMethod(currentMode);
    };

    window.selectProductItemType = function(type) {
        const validType = ['goods', 'service', 'both'].includes(type) ? type : 'goods';
        const hiddenInput = document.getElementById('formItemType');
        if (hiddenInput) hiddenInput.value = validType;

        document.querySelectorAll('.inv-item-type-card').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-item-type') === validType);
        });

        const badge = document.getElementById('itemTypeBadge');
        const typeLabels = {
            goods: 'Physical Goods',
            service: 'Service / Labor',
            both: 'Both (Hybrid Bundle)'
        };
        if (badge) badge.textContent = typeLabels[validType] || 'Physical Goods';

        if (validType === 'service') {
            const trackStockCheckbox = document.getElementById('formTrackStock');
            if (trackStockCheckbox && trackStockCheckbox.checked) {
                showToast('Service item selected: physical warehouse receiving is bypassed.');
            }
        }
    };

    window.openReorderRulesModal = function() {
        const modal = document.getElementById('reorderRulesModal');
        if (!modal) return;
        const sku = (formSku && formSku.value) ? formSku.value.trim() : 'PRD';
        const name = (formName && formName.value) ? formName.value.trim() : 'Product';
        const sub = document.getElementById('reorderModalSubtitle');
        if (sub) sub.textContent = `Item: ${sku} • ${name}`;

        const reorderVal = document.getElementById('formReorderPoint')?.value || 15;
        const targetVal = document.getElementById('formTargetStock')?.value || 50;
        const minStockInput = document.getElementById('ruleMinStock');
        const maxStockInput = document.getElementById('ruleMaxStock');
        if (minStockInput) minStockInput.value = reorderVal;
        if (maxStockInput) maxStockInput.value = targetVal;

        modal.style.display = 'flex';
    };

    window.closeReorderRulesModal = function() {
        const modal = document.getElementById('reorderRulesModal');
        if (modal) modal.style.display = 'none';
    };

    window.selectReorderStrategy = function(strat) {
        ['btnRuleMinMax', 'btnRuleCommissary', 'btnRuleJit'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        if (strat === 'min_max') document.getElementById('btnRuleMinMax')?.classList.add('active');
        else if (strat === 'commissary_push') document.getElementById('btnRuleCommissary')?.classList.add('active');
        else if (strat === 'jit_order') document.getElementById('btnRuleJit')?.classList.add('active');
    };

    window.saveReorderRulesModal = function() {
        const minVal = document.getElementById('ruleMinStock')?.value || 15;
        const maxVal = document.getElementById('ruleMaxStock')?.value || 50;
        if (formReorderPoint) formReorderPoint.value = minVal;
        if (formTargetStock) formTargetStock.value = maxVal;

        const ruleDisp = document.getElementById('ruleDisplayReorderPoint');
        if (ruleDisp) ruleDisp.textContent = `${minVal} units`;

        window.closeReorderRulesModal();
        showToast('Replenishment rule policy updated and saved to item.');
    };

    function getMockLocationsForProduct(product) {
        const cat = (product && product.category) || 'General';
        const isCold = cat.toLowerCase().includes('bev') || cat.toLowerCase().includes('dairy') || cat.toLowerCase().includes('meat') || cat.toLowerCase().includes('syrup');
        const zoneName = isCold ? 'Zone A: Cold Storage / Chiller' : 'Zone B: Dry Goods & Grains';
        const zoneCode = isCold ? 'ZA' : 'ZB';

        return [
            {
                facility: 'Power Mac Center - Main HQ',
                zone: zoneName,
                rack: 'Aisle 1 • Rack 02',
                bin: `WH-MAIN-${zoneCode}-R02-B01`,
                behavior: 'Storage',
                onHand: 24,
                reserved: 2,
                available: 22
            },
            {
                facility: 'Power Mac Center - Main HQ',
                zone: 'Zone S: Inbound Staging & QA',
                rack: 'QA Bench 01',
                bin: 'WH-MAIN-ZS-QA-B01',
                behavior: 'Staging',
                onHand: 6,
                reserved: 0,
                available: 6
            },
            {
                facility: 'Makati Greenbelt Kiosk',
                zone: 'Front Bar / Station B',
                rack: 'Under-counter Shelf 1',
                bin: 'WH-MKT-BAR-S01-B03',
                behavior: 'Storage',
                onHand: 18,
                reserved: 1,
                available: 17
            },
            {
                facility: 'BGC High Street Branch',
                zone: 'Pantry Stockroom',
                rack: 'Shelving Unit 04',
                bin: 'WH-BGC-PN-S04-B02',
                behavior: 'Storage',
                onHand: 35,
                reserved: 4,
                available: 31
            }
        ];
    }

    function getMockLedgerForProduct(product) {
        const supplierName = (product && product.supplier) ? product.supplier : 'Accredited Vendor Supply';
        return [
            {
                date: 'Sep 21, 2026',
                ref: 'PO-2026-0041',
                type: 'Stock In (GRN)',
                source: supplierName,
                destination: 'Central Commissary • Dock Receiving',
                qty: '+50',
                balance: 95
            },
            {
                date: 'Sep 20, 2026',
                ref: 'TRF-2026-0018',
                type: 'Internal Transfer',
                source: 'Central Commissary (WH-MAIN)',
                destination: 'Makati Greenbelt Kiosk (WH-MKT)',
                qty: '-12',
                balance: 45
            },
            {
                date: 'Sep 19, 2026',
                ref: 'POS-2026-9182',
                type: 'POS Sale (Deduction)',
                source: 'Makati Kiosk • Front Bar',
                destination: 'Customer Order #9182 (Walk-in)',
                qty: '-2',
                balance: 57
            },
            {
                date: 'Sep 18, 2026',
                ref: 'BOM-2026-005',
                type: 'Recipe Batch Production',
                source: 'Central Commissary Cold Storage',
                destination: 'Kitchen Prep Batch Kettle #2',
                qty: '-8',
                balance: 59
            },
            {
                date: 'Sep 15, 2026',
                ref: 'ADJ-2026-002',
                type: 'Cycle Count Audit',
                source: 'Physical Warehouse Audit',
                destination: 'Inventory Variance Reconciliation',
                qty: '+2',
                balance: 67
            }
        ];
    }

    window.renderProductVariantsEditor = function() {
        const container = document.getElementById('productVariantsListContainer');
        const badge = document.getElementById('productVariantsCountBadge');
        const tabBadge = document.getElementById('tabVariantsCountBadge');
        const vKpiCount = document.getElementById('variantKpiCount');
        const vKpiPrice = document.getElementById('variantKpiPriceRange');
        const vKpiCost = document.getElementById('variantKpiCostRange');
        const vKpiMargin = document.getElementById('variantKpiAvgMargin');

        if (badge) {
            badge.textContent = `${currentDrawerVariants.length} Variant${currentDrawerVariants.length === 1 ? '' : 's'}`;
        }
        if (tabBadge) {
            tabBadge.textContent = String(currentDrawerVariants.length);
        }

        if (currentDrawerVariants.length > 0) {
            const prices = currentDrawerVariants.map(v => Number(v.sellingPrice) || 0);
            const costs = currentDrawerVariants.map(v => Number(v.costPrice) || 0);
            const minP = Math.min(...prices);
            const maxP = Math.max(...prices);
            const minC = Math.min(...costs);
            const maxC = Math.max(...costs);

            const totalSell = prices.reduce((a, b) => a + b, 0);
            const totalCost = costs.reduce((a, b) => a + b, 0);
            const avgMargin = totalSell > 0 ? (((totalSell - totalCost) / totalSell) * 100).toFixed(1) : '0.0';

            if (vKpiCount) vKpiCount.textContent = `${currentDrawerVariants.length} Option${currentDrawerVariants.length === 1 ? '' : 's'}`;
            if (vKpiPrice) vKpiPrice.textContent = minP === maxP ? formatPHP(minP) : `${formatPHP(minP)} – ${formatPHP(maxP)}`;
            if (vKpiCost) vKpiCost.textContent = minC === maxC ? formatPHP(minC) : `${formatPHP(minC)} – ${formatPHP(maxC)}`;
            if (vKpiMargin) vKpiMargin.textContent = `${avgMargin}%`;
        } else {
            const baseC = parseFloat(formCostPrice?.value) || 0;
            const baseS = parseFloat(formSellingPrice?.value) || 0;
            const singleMargin = baseS > 0 ? (((baseS - baseC) / baseS) * 100).toFixed(1) : '0.0';
            if (vKpiCount) vKpiCount.textContent = '0 Options (Single)';
            if (vKpiPrice) vKpiPrice.textContent = formatPHP(baseS);
            if (vKpiCost) vKpiCost.textContent = formatPHP(baseC);
            if (vKpiMargin) vKpiMargin.textContent = `${singleMargin}%`;
        }

        if (!container) return;

        if (currentDrawerVariants.length === 0) {
            container.innerHTML = `
                <div class="inv-variants-empty">
                    <i class="ph ph-stack" style="font-size: 26px; color: var(--inv-primary-dark);"></i>
                    <div style="font-weight: 700; color: var(--inv-text-strong); font-size: 0.88rem;">No Product Variants Configured Yet</div>
                    <div style="font-size: 0.76rem; max-width: 520px; line-height: 1.4;">
                        Add product variations like different beverage sizes (12oz, 16oz, 22oz), temperatures (Hot, Iced), or custom bundles with individual selling prices, SKUs, and margin tracking.
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
                        <button type="button" class="inv-btn-quick-create" onclick="addVariantPreset('size')">
                            <i class="ph ph-magic-wand"></i> + Quick Add Size Set
                        </button>
                        <button type="button" class="inv-action-btn-primary" onclick="addProductVariantRow()" style="padding: 5px 12px; font-size: 0.74rem;">
                            <i class="ph ph-plus-bold"></i> + Custom Option
                        </button>
                    </div>
                </div>
            `;
            return;
        }

        let rowsHtml = currentDrawerVariants.map((v, idx) => {
            const cost = parseFloat(v.costPrice) || 0;
            const selling = parseFloat(v.sellingPrice) || 0;
            const profit = selling - cost;
            const margin = selling > 0 ? ((profit / selling) * 100).toFixed(1) : '0.0';

            return `
                <tr>
                    <td style="width: 130px;">
                        <input type="text" class="inv-form-input" value="${(v.group || 'Option').replace(/"/g, '&quot;')}"
                            placeholder="e.g. Size / Flavor"
                            oninput="updateProductVariantField(${idx}, 'group', this.value)">
                    </td>
                    <td>
                        <input type="text" class="inv-form-input" value="${(v.name || '').replace(/"/g, '&quot;')}"
                            placeholder="e.g. 16oz Large (Iced)" style="font-weight: 600;"
                            oninput="updateProductVariantField(${idx}, 'name', this.value)">
                    </td>
                    <td style="width: 145px;">
                        <input type="text" class="inv-form-input" value="${(v.sku || '').replace(/"/g, '&quot;')}"
                            placeholder="Variant SKU" style="font-family: monospace;"
                            oninput="updateProductVariantField(${idx}, 'sku', this.value)">
                    </td>
                    <td style="width: 125px;">
                        <input type="number" step="0.01" min="0" class="inv-form-input" value="${cost.toFixed(2)}"
                            placeholder="0.00" style="text-align: right; font-variant-numeric: tabular-nums;"
                            onchange="updateProductVariantField(${idx}, 'costPrice', this.value)">
                    </td>
                    <td style="width: 135px;">
                        <input type="number" step="0.01" min="0" class="inv-form-input" value="${selling.toFixed(2)}"
                            placeholder="0.00" style="text-align: right; font-weight: 700; color: var(--inv-primary-dark); font-variant-numeric: tabular-nums;"
                            onchange="updateProductVariantField(${idx}, 'sellingPrice', this.value)">
                    </td>
                    <td style="width: 105px; text-align: right;">
                        <span style="font-weight: 700; font-size: 0.74rem; color: ${profit >= 0 ? 'var(--inv-success)' : 'var(--inv-danger)'}; font-variant-numeric: tabular-nums;">
                            ${margin}%
                        </span>
                    </td>
                    <td style="width: 44px; text-align: center;">
                        <button type="button" class="inv-group-action-btn is-delete" onclick="removeProductVariantRow(${idx})" title="Remove variant">
                            <i class="ph ph-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <div style="overflow-x: auto; border: 1px solid var(--inv-border-subtle); border-radius: 10px;">
                <table class="inv-variants-table">
                    <thead>
                        <tr>
                            <th>Attribute / Group</th>
                            <th>Variant Option Name *</th>
                            <th>Variant SKU</th>
                            <th style="text-align: right;">Cost (₱)</th>
                            <th style="text-align: right;">Selling Price (₱) *</th>
                            <th style="text-align: right;">Margin %</th>
                            <th style="text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
        `;
    };

    window.addProductVariantRow = function(customVariant) {
        const baseSku = (formSku && formSku.value ? formSku.value.trim() : 'PRD') || 'PRD';
        const baseCost = parseFloat(formCostPrice && formCostPrice.value ? formCostPrice.value : 0) || 0;
        const baseSell = parseFloat(formSellingPrice && formSellingPrice.value ? formSellingPrice.value : 0) || 0;
        const nextNum = currentDrawerVariants.length + 1;

        currentDrawerVariants.push(customVariant || {
            id: 'var-' + Date.now() + '-' + nextNum,
            group: 'Size / Option',
            name: `Variant ${nextNum}`,
            sku: `${baseSku}-V${nextNum}`,
            costPrice: baseCost,
            sellingPrice: baseSell
        });
        window.renderProductVariantsEditor();
    };

    window.addVariantPreset = function(presetType) {
        const baseSku = (formSku && formSku.value ? formSku.value.trim() : 'PRD') || 'PRD';
        const baseCost = parseFloat(formCostPrice && formCostPrice.value ? formCostPrice.value : 45) || 45;
        const baseSell = parseFloat(formSellingPrice && formSellingPrice.value ? formSellingPrice.value : 120) || 120;

        if (presetType === 'size') {
            currentDrawerVariants.push(
                { id: 'var-sz-1-' + Date.now(), group: 'Size', name: '12oz Regular', sku: `${baseSku}-12OZ`, costPrice: baseCost, sellingPrice: baseSell },
                { id: 'var-sz-2-' + Date.now(), group: 'Size', name: '16oz Large', sku: `${baseSku}-16OZ`, costPrice: +(baseCost * 1.15).toFixed(2), sellingPrice: +(baseSell + 20).toFixed(2) },
                { id: 'var-sz-3-' + Date.now(), group: 'Size', name: '22oz Venti', sku: `${baseSku}-22OZ`, costPrice: +(baseCost * 1.30).toFixed(2), sellingPrice: +(baseSell + 40).toFixed(2) }
            );
        } else if (presetType === 'temp') {
            currentDrawerVariants.push(
                { id: 'var-tp-1-' + Date.now(), group: 'Temperature', name: 'Hot Serve', sku: `${baseSku}-HOT`, costPrice: baseCost, sellingPrice: baseSell },
                { id: 'var-tp-2-' + Date.now(), group: 'Temperature', name: 'Over Ice (Iced)', sku: `${baseSku}-ICED`, costPrice: +(baseCost + 5).toFixed(2), sellingPrice: +(baseSell + 15).toFixed(2) }
            );
        } else if (presetType === 'flavor') {
            currentDrawerVariants.push(
                { id: 'var-fl-1-' + Date.now(), group: 'Flavor', name: 'French Vanilla Syrup', sku: `${baseSku}-VAN`, costPrice: +(baseCost + 8).toFixed(2), sellingPrice: +(baseSell + 20).toFixed(2) },
                { id: 'var-fl-2-' + Date.now(), group: 'Flavor', name: 'Salted Caramel Drizzle', sku: `${baseSku}-CAR`, costPrice: +(baseCost + 10).toFixed(2), sellingPrice: +(baseSell + 25).toFixed(2) },
                { id: 'var-fl-3-' + Date.now(), group: 'Flavor', name: 'Roasted Hazelnut', sku: `${baseSku}-HAZ`, costPrice: +(baseCost + 10).toFixed(2), sellingPrice: +(baseSell + 25).toFixed(2) }
            );
        }
        window.renderProductVariantsEditor();
        showToast('Variant preset options added — customize prices below.');
    };

    window.updateProductVariantField = function(idx, field, val) {
        if (!currentDrawerVariants[idx]) return;
        if (field === 'costPrice' || field === 'sellingPrice') {
            currentDrawerVariants[idx][field] = parseFloat(val) || 0;
        } else {
            currentDrawerVariants[idx][field] = val;
        }
        window.renderProductVariantsEditor();
    };

    window.removeProductVariantRow = function(idx) {
        currentDrawerVariants.splice(idx, 1);
        window.renderProductVariantsEditor();
    };

    window.openProductDrawer = function(idOrMode, defaultCategory) {
        switchPrimaryTab('tab-general');
        switchSubTab('sub-branch');

        window.AppStore.branchPage = 1;
        window.AppStore.locationPage = 1;
        window.AppStore.ledgerPage = 1;

        const errEl = document.getElementById('formSkuError');
        const badgeEl = document.getElementById('formSkuStatusBadge');
        if (errEl) errEl.style.display = 'none';
        if (badgeEl) badgeEl.style.display = 'none';
        if (formSku) formSku.classList.remove('inv-input-error');

        const btnSaveTextTop = document.getElementById('btnSaveTextTop');
        const tabBadgeDetailMode = document.getElementById('tabBadgeDetailMode');
        const tabLabelProductDetails = document.getElementById('tabLabelProductDetails');

        if (idOrMode === 'new') {
            window.AppStore.selectedProductId = null;
            drawerTitle.textContent = 'Add New Product';
            drawerSubtitle.textContent = 'Create master catalog record with barcodes, hierarchy & pricing';
            drawerIcon.innerHTML = '<i class="ph ph-plus-circle"></i>';
            btnSaveText.textContent = 'Create Product';
            if (btnSaveTextTop) btnSaveTextTop.textContent = 'Create Product';
            if (tabBadgeDetailMode) tabBadgeDetailMode.textContent = 'New Item';
            if (tabLabelProductDetails) tabLabelProductDetails.textContent = 'New Product';

            formId.value = '';
            let newSku = 'PRD-' + Math.floor(1000 + Math.random() * 9000);
            while ((window.AppStore.products || []).some(p => p.sku && p.sku.toLowerCase() === newSku.toLowerCase())) {
                newSku = 'PRD-' + Math.floor(1000 + Math.random() * 9000);
            }
            formSku.value = newSku;
            formBarcode.value = '4800' + Math.floor(100000000 + Math.random() * 900000000);
            formName.value = '';
            renderProductImageState('');
            window.selectProductItemType('goods');
            
            const rootNodes = (window.AppStore.categoryNodes || []).filter(n => !n.parentName || n.level === 1);
            const defCat = defaultCategory || (rootNodes[0] ? rootNodes[0].name : 'Beverages');
            currentFormCategoryPath = [defCat];
            renderProductCategorySelectors(currentFormCategoryPath);

            if (window.UomRegistry && window.UomRegistry.isLoaded) {
                window.UomRegistry.populateSelect(formUnit, 'PCS');
            } else {
                formUnit.value = 'Piece';
            }
            formUnit.dispatchEvent(new Event('change'));
            formPackSize.value = '1';
            formDescription.value = '';
            formAllergens.value = '';
            formCanBeSold.checked = true;
            formCanBePurchased.checked = true;
            formHasBom.checked = false;
            formIsActive.checked = true;
            formTrackStock.checked = true;
            window.selectTrackingByMethod('lots');
            window.handleTrackStockToggle(true);
            currentDrawerVariants = [];
            window.renderProductVariantsEditor();

            formReorderPoint.value = 15;
            formTargetStock.value = 50;
            const ruleDisp = document.getElementById('ruleDisplayReorderPoint');
            if (ruleDisp) ruleDisp.textContent = '15 units';

            formCostPrice.value = '45.00';
            formSellingPrice.value = '';
            formTaxRate.value = '12% VAT';
            formSupplier.value = '';
            formProfitMargin.value = '0.0%';

            currentDrawerBranches = [
                { name: 'Power Mac Center - Main HQ', onHand: 0, reserved: 0, reorder: 15 }
            ];
            currentDrawerLocations = getMockLocationsForProduct(null);
            currentDrawerLedgers = [];

            renderBranchSubtablePage();
            renderLocationSubtablePage();
            renderLedgerSubtablePage();
            renderSupplierPriceSection(null, true);
            renderPriceHistoryChart(parseFloat(formCostPrice.value) || 45, 120);

        } else {
            const product = window.AppStore.products.find(p => p.id === Number(idOrMode));
            if (!product) return;

            const prodPath = getProductCategoryPath(product);
            window.AppStore.selectedProductId = product.id;
            drawerTitle.textContent = product.name;
            drawerSubtitle.textContent = `SKU: ${product.sku} • ${prodPath.join(' › ')}`;
            drawerIcon.innerHTML = '<i class="ph ph-tag"></i>';
            btnSaveText.textContent = 'Save Changes';
            if (btnSaveTextTop) btnSaveTextTop.textContent = 'Save Changes';
            if (tabBadgeDetailMode) tabBadgeDetailMode.textContent = product.sku;
            if (tabLabelProductDetails) tabLabelProductDetails.textContent = 'Product Details';

            formId.value = product.id;
            formSku.value = product.sku;
            formBarcode.value = product.barcode || '';
            formName.value = product.name;
            renderProductImageState(product.imageUrl || '');
            window.selectProductItemType(product.itemType || 'goods');
            
            currentFormCategoryPath = [...prodPath];
            renderProductCategorySelectors(currentFormCategoryPath);

            if (window.UomRegistry && window.UomRegistry.isLoaded) {
                window.UomRegistry.populateSelect(formUnit, product.unit);
            } else {
                formUnit.value = product.unit;
            }
            formUnit.dispatchEvent(new Event('change'));
            const numericPackSize = parseFloat(String(product.packSize || '1').replace(/[^0-9.]/g, '')) || 1;
            formPackSize.value = numericPackSize;

            formDescription.value = product.description || '';
            formAllergens.value = (product.allergens || []).join(', ');
            formCanBeSold.checked = product.canBeSold !== false;
            formCanBePurchased.checked = product.canBePurchased !== false;
            formHasBom.checked = product.explodeBomOnSale !== undefined ? product.explodeBomOnSale : (product.hasBom === true);
            formIsActive.checked = product.isActive !== false;
            formTrackStock.checked = product.trackPhysicalStock !== undefined ? product.trackPhysicalStock : (product.trackStock !== false);
            window.selectTrackingByMethod(product.trackingBy || 'lots');
            window.handleTrackStockToggle(formTrackStock.checked);
            currentDrawerVariants = Array.isArray(product.variants)
                ? product.variants.map(v => Object.assign({}, v))
                : [];
            window.renderProductVariantsEditor();

            formReorderPoint.value = product.reorderPoint || 10;
            formTargetStock.value = product.targetStock || 50;
            const ruleDisp = document.getElementById('ruleDisplayReorderPoint');
            if (ruleDisp) ruleDisp.textContent = `${formReorderPoint.value} units`;

            formCostPrice.value = Number(product.costPrice).toFixed(2);
            formSellingPrice.value = Number(product.sellingPrice).toFixed(2);
            formTaxRate.value = product.taxRate || '12% VAT';
            formSupplier.value = product.supplier || '';
            updateMarginCalculation();

            currentDrawerBranches = product.branches || [
                { name: 'Power Mac Center - Main HQ', onHand: 24, reserved: 0, reorder: product.reorderPoint || 15 }
            ];
            currentDrawerLocations = product.locations || getMockLocationsForProduct(product);
            currentDrawerLedgers = (product.ledger && product.ledger[0]?.source) ? product.ledger : getMockLedgerForProduct(product);

            renderBranchSubtablePage();
            renderLocationSubtablePage();
            renderLedgerSubtablePage();
            renderSupplierPriceSection(product, false);
            renderPriceHistoryChart(product.costPrice, product.sellingPrice);
        }

        // Switch to the inline Product Details workspace tab instead of opening a slide-out popup drawer
        window.syncDetailStatusPill(formIsActive ? formIsActive.checked : true);
        window.switchMasterWorkspaceTab('details', true);

        setTimeout(() => {
            if (formName) {
                formName.focus();
                formName.select();
            }
        }, 120);

        renderMasterList();
    };

    window.closeProductDrawer = function() {
        window.AppStore.selectedProductId = null;
        if (window.AppStore.activePriceChart) {
            window.AppStore.activePriceChart.destroy();
            window.AppStore.activePriceChart = null;
        }
        window.switchMasterWorkspaceTab('catalog', true);
        renderMasterList();
        setTimeout(() => {
            if (searchInput) searchInput.focus();
        }, 120);
    };

    function switchPrimaryTab(tabId) {
        document.querySelectorAll('.inv-drawer-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.drawer-tab-pane').forEach(pane => {
            pane.style.display = (pane.id === 'pane-' + tabId) ? 'block' : 'none';
        });

        // Trigger chart render if pricing tab opened
        if (tabId === 'tab-pricing') {
            setTimeout(() => {
                const cost = parseFloat(formCostPrice.value) || 50;
                const selling = parseFloat(formSellingPrice.value) || (cost * 1.5);
                renderPriceHistoryChart(cost, selling);
            }, 50);
        }

        if (tabId === 'tab-audit') {
            const prod = window.AppStore.products.find(p => p.id === window.AppStore.selectedProductId);
            renderProductAuditLogs(prod);
        }
    }

    document.querySelectorAll('.inv-drawer-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchPrimaryTab(this.getAttribute('data-tab'));
        });
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
            summary: summary || 'Product record updated',
            changes: changes || []
        };
    }

    function renderProductAuditLogs(product) {
        const container = document.getElementById('productAuditLogsContainer');
        if (!container) return;

        if (!product) {
            container.innerHTML = `
                <div class="audit-overview-card" style="grid-template-columns: 1fr; text-align: center; padding: 28px 16px;">
                    <div style="color: var(--inv-text-muted); display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <i class="ph ph-sparkle" style="font-size: 2rem; color: var(--inv-primary);"></i>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--inv-text-strong);">New Item Draft</h4>
                        <p style="margin: 0; font-size: 0.8rem; max-width: 320px; line-height: 1.4;">
                            Audit trail will automatically initialize with registration timestamp and user identity upon clicking "Create Product".
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
                <div style="text-align: center; padding: 24px; color: var(--inv-text-muted); font-size: 0.82rem;">
                    No revision history recorded yet for this item.
                </div>
            `;
            return;
        }

        let timelineHtml = '<div class="audit-timeline-container">';

        logs.forEach((log) => {
            let iconClass = '';
            let iconGlyph = 'ph-clock-counter-clockwise';
            let badgeClass = 'badge-updated';
            let badgeText = log.actionType || 'UPDATED';

            if (log.actionType === 'CREATED') {
                iconClass = 'is-created';
                iconGlyph = 'ph-plus-circle';
                badgeClass = 'badge-created';
                badgeText = 'CREATED';
            } else if (log.actionType === 'PRICE_CHANGE') {
                iconClass = 'is-price';
                iconGlyph = 'ph-currency-circle-dollar';
                badgeClass = 'badge-price';
                badgeText = 'PRICE ADJUSTED';
            } else if (log.actionType === 'BOM_CHANGE') {
                iconClass = 'is-bom';
                iconGlyph = 'ph-tree-structure';
                badgeClass = 'badge-bom';
                badgeText = 'BOM UPDATED';
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
                                    <td style="font-weight: 600; color: var(--inv-text-strong);">${ch.field}</td>
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
                            <span style="font-size: 0.72rem; color: var(--inv-text-muted); font-weight: 500;">
                                <i class="ph ph-clock" style="vertical-align: middle;"></i> ${log.formattedDate}
                            </span>
                        </div>
                    </div>
                    <div class="audit-summary-text">${log.summary || 'Item modified'}</div>
                    ${diffRows}
                </div>
            `;
        });

        timelineHtml += '</div>';
        container.innerHTML = overviewHtml + timelineHtml;
    }

    function switchSubTab(subtabId) {
        document.querySelectorAll('.inv-subtab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-subtab') === subtabId);
        });
        document.querySelectorAll('.inv-subtable-wrapper').forEach(wrapper => {
            wrapper.style.display = (wrapper.id === 'subpane-' + subtabId) ? 'block' : 'none';
        });

        const branchPagination = document.getElementById('branchStockPagination');
        const locationPagination = document.getElementById('locationStockPagination');
        const ledgerPagination = document.getElementById('ledgerLogPagination');
        if (branchPagination) branchPagination.style.display = (subtabId === 'sub-branch') ? 'flex' : 'none';
        if (locationPagination) locationPagination.style.display = (subtabId === 'sub-locations') ? 'flex' : 'none';
        if (ledgerPagination) ledgerPagination.style.display = (subtabId === 'sub-movements') ? 'flex' : 'none';
    }

    document.querySelectorAll('.inv-subtab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchSubTab(this.getAttribute('data-subtab'));
        });
    });

    window.triggerSaveForm = function() {
        const form = document.getElementById('productForm');
        if (form.reportValidity()) {
            saveProductChanges();
        }
    };

    function saveProductChanges(e) {
        if (e) e.preventDefault();

        const idVal = formId.value;
        const isNew = !idVal;
        const newId = isNew ? Date.now() : Number(idVal);

        const allergenArray = formAllergens.value.split(',').map(s => s.trim()).filter(Boolean);

        // Collect selected multi-level category path from cascading selectors
        const maxDepth = getMaxCategoryDepth();
        const selectedPath = [];
        for (let lvl = 1; lvl <= maxDepth; lvl++) {
            const selEl = document.getElementById(`formCatLevel_${lvl}`);
            const val = selEl ? selEl.value.trim() : (currentFormCategoryPath[lvl - 1] || '');
            if (val) {
                selectedPath.push(val);
            } else {
                break;
            }
        }
        if (selectedPath.length === 0) {
            selectedPath.push(formCategory && formCategory.value ? formCategory.value : 'Uncategorized');
        }

        const trackingByEl = document.getElementById('formTrackingBy');
        const imageDataEl = document.getElementById('formProductImageData');
        const itemTypeEl = document.getElementById('formItemType');
        const itemType = itemTypeEl ? itemTypeEl.value : 'goods';

        const skuVal = (formSku.value || '').trim();
        if (!skuVal) {
            showToast('SKU Code is required.', 'error');
            switchPrimaryTab('tab-general');
            formSku.focus();
            return;
        }

        const normalizedSku = skuVal.toLowerCase();

        // 1. Strict SKU Uniqueness Check across all other products
        const duplicateProduct = (window.AppStore.products || []).find(p => 
            p.id !== newId && 
            p.sku && 
            p.sku.trim().toLowerCase() === normalizedSku
        );

        if (duplicateProduct) {
            showToast(`SKU "${skuVal}" is already in use by "${duplicateProduct.name}". Each SKU must be unique across all products.`, 'error');
            switchPrimaryTab('tab-general');
            formSku.classList.add('inv-input-error');
            const errEl = document.getElementById('formSkuError');
            if (errEl) {
                errEl.style.display = 'flex';
                const span = errEl.querySelector('span');
                if (span) span.textContent = `SKU "${skuVal}" is already used by "${duplicateProduct.name}"`;
            }
            formSku.focus();
            return;
        }

        // 2. Strict SKU Uniqueness Check against all existing variant SKUs in other products
        const duplicateFromOtherVariant = (window.AppStore.products || []).find(p => 
            p.id !== newId && 
            Array.isArray(p.variants) && 
            p.variants.some(v => v.sku && v.sku.trim().toLowerCase() === normalizedSku)
        );
        if (duplicateFromOtherVariant) {
            showToast(`SKU "${skuVal}" is already in use as a variant SKU in "${duplicateFromOtherVariant.name}".`, 'error');
            switchPrimaryTab('tab-general');
            formSku.classList.add('inv-input-error');
            formSku.focus();
            return;
        }

        const cleanedVariants = (currentDrawerVariants || [])
            .filter(v => v && String(v.name || '').trim() !== '')
            .map((v, i) => ({
                id: v.id || `var-${newId}-${i + 1}`,
                group: String(v.group || 'Option').trim(),
                name: String(v.name || '').trim(),
                sku: String(v.sku || `${skuVal}-V${i + 1}`).trim(),
                costPrice: parseFloat(v.costPrice) || 0,
                sellingPrice: parseFloat(v.sellingPrice) || 0
            }));

        // 3. Variant SKU Uniqueness Checks
        for (let i = 0; i < cleanedVariants.length; i++) {
            const v = cleanedVariants[i];
            const vSkuNorm = (v.sku || '').trim().toLowerCase();
            if (!vSkuNorm) continue;

            if (vSkuNorm === normalizedSku) {
                showToast(`Variant "${v.name}" cannot have the same SKU "${v.sku}" as the parent product.`, 'error');
                switchPrimaryTab('tab-variants');
                return;
            }

            const duplicateVariantSelf = cleanedVariants.find((ov, oidx) => oidx !== i && (ov.sku || '').trim().toLowerCase() === vSkuNorm);
            if (duplicateVariantSelf) {
                showToast(`Duplicate SKU "${v.sku}" detected between variants "${v.name}" and "${duplicateVariantSelf.name}". Each variant must have a unique SKU.`, 'error');
                switchPrimaryTab('tab-variants');
                return;
            }

            const duplicateVariantExternal = (window.AppStore.products || []).find(p => 
                p.id !== newId && (
                    (p.sku && p.sku.trim().toLowerCase() === vSkuNorm) ||
                    (Array.isArray(p.variants) && p.variants.some(ov => ov.sku && ov.sku.trim().toLowerCase() === vSkuNorm))
                )
            );
            if (duplicateVariantExternal) {
                showToast(`Variant SKU "${v.sku}" is already in use by product "${duplicateVariantExternal.name}".`, 'error');
                switchPrimaryTab('tab-variants');
                return;
            }
        }

        const updatedRecord = {
            id: newId,
            sku: formSku.value.trim(),
            barcode: formBarcode.value.trim(),
            name: formName.value.trim(),
            imageUrl: imageDataEl ? imageDataEl.value : '',
            itemType: itemType,
            categoryPath: selectedPath,
            category: selectedPath[0] || 'Uncategorized',
            subcategory: selectedPath[1] || 'Standard',
            unit: formUnit.value,
            packSize: formPackSize.value.trim() || '1',
            description: formDescription.value.trim(),
            allergens: allergenArray,
            canBeSold: formCanBeSold.checked,
            canBePurchased: formCanBePurchased.checked,
            trackPhysicalStock: formTrackStock.checked,
            trackStock: formTrackStock.checked,
            trackingBy: trackingByEl ? trackingByEl.value : 'lots',
            variants: cleanedVariants,
            explodeBomOnSale: formHasBom.checked,
            hasBom: formHasBom.checked,
            isActive: formIsActive.checked,
            reorderPoint: Number(formReorderPoint.value) || 0,
            targetStock: Number(formTargetStock.value) || 0,
            costPrice: parseFloat(formCostPrice.value) || 0,
            sellingPrice: parseFloat(formSellingPrice.value) || 0,
            taxRate: formTaxRate.value,
            supplier: formSupplier.value.trim() || 'Direct Supply',
            locations: getMockLocationsForProduct(newId, formSku.value.trim()),
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 42, reserved: 2, reorder: Number(formReorderPoint.value) || 20 },
                { name: 'Makati Greenbelt Kiosk', onHand: 18, reserved: 0, reorder: 15 },
                { name: 'BGC High Street Branch', onHand: 35, reserved: 4, reorder: 20 }
            ],
            ledger: [
                { ref: 'ADJ-' + Math.floor(1000 + Math.random() * 9000), type: 'Catalog Update', qty: '+0', balance: 95 }
            ]
        };

        const currentUser = {
            name: 'John Abiguero',
            email: 'jabiguero@dorothydiaz.internal',
            role: 'Lead Inventory Admin'
        };

        if (isNew) {
            const initialLog = createAuditEntry(currentUser, 'CREATED', `Initial catalog registration for "${updatedRecord.name}"`, [
                { field: 'SKU Code', oldValue: '—', newValue: updatedRecord.sku },
                { field: 'Product Name', oldValue: '—', newValue: updatedRecord.name },
                { field: 'Offering Type', oldValue: '—', newValue: updatedRecord.itemType === 'service' ? 'Service / Labor' : (updatedRecord.itemType === 'both' ? 'Both (Hybrid Bundle)' : 'Physical Goods') },
                { field: 'Category', oldValue: '—', newValue: updatedRecord.category },
                { field: 'Unit of Measure', oldValue: '—', newValue: `${updatedRecord.unit} (${updatedRecord.packSize})` },
                { field: 'Cost Price', oldValue: '—', newValue: formatPHP(updatedRecord.costPrice) },
                { field: 'Selling Price', oldValue: '—', newValue: formatPHP(updatedRecord.sellingPrice) },
                { field: 'Track Physical Stock', oldValue: '—', newValue: updatedRecord.trackPhysicalStock ? 'Yes (Counted in Stocks Overview)' : 'No (Non-Physical / Service)' },
                { field: 'Explode Recipe on Sale', oldValue: '—', newValue: updatedRecord.explodeBomOnSale ? 'Yes (Deducts Sub-ingredients)' : 'No (Deducts Item Directly)' }
            ]);
            updatedRecord.createdAt = initialLog.formattedDate;
            updatedRecord.createdBy = `${currentUser.name} (${currentUser.role})`;
            updatedRecord.auditLogs = [initialLog];
            window.AppStore.products.unshift(updatedRecord);
            showToast(`Product "${updatedRecord.name}" successfully created!`);
        } else {
            const index = window.AppStore.products.findIndex(p => p.id === newId);
            if (index !== -1) {
                const existingProd = window.AppStore.products[index];
                updatedRecord.branches = existingProd.branches || updatedRecord.branches;
                updatedRecord.locations = existingProd.locations || updatedRecord.locations;
                updatedRecord.ledger = existingProd.ledger || updatedRecord.ledger;
                updatedRecord.createdAt = existingProd.createdAt || 'Sep 15, 2026 • 09:30 AM';
                updatedRecord.createdBy = existingProd.createdBy || 'Dorothy Diaz (System Admin)';

                const changes = [];
                if (existingProd.name !== updatedRecord.name) changes.push({ field: 'Product Name', oldValue: existingProd.name, newValue: updatedRecord.name });
                if (existingProd.sku !== updatedRecord.sku) changes.push({ field: 'SKU Code', oldValue: existingProd.sku, newValue: updatedRecord.sku });
                if ((existingProd.itemType || 'goods') !== updatedRecord.itemType) {
                    changes.push({
                        field: 'Offering Type',
                        oldValue: existingProd.itemType || 'goods',
                        newValue: updatedRecord.itemType
                    });
                }
                if (existingProd.category !== updatedRecord.category) changes.push({ field: 'Category', oldValue: existingProd.category, newValue: updatedRecord.category });
                if (existingProd.subcategory !== updatedRecord.subcategory) changes.push({ field: 'Subcategory', oldValue: existingProd.subcategory || 'Standard', newValue: updatedRecord.subcategory || 'Standard' });
                if (existingProd.unit !== updatedRecord.unit) changes.push({ field: 'Unit of Measure', oldValue: existingProd.unit, newValue: updatedRecord.unit });
                if (String(existingProd.packSize) !== String(updatedRecord.packSize)) changes.push({ field: 'Pack Size', oldValue: String(existingProd.packSize), newValue: String(updatedRecord.packSize) });
                if (Number(existingProd.costPrice) !== Number(updatedRecord.costPrice)) changes.push({ field: 'Cost Price', oldValue: formatPHP(existingProd.costPrice), newValue: formatPHP(updatedRecord.costPrice) });
                if (Number(existingProd.sellingPrice) !== Number(updatedRecord.sellingPrice)) changes.push({ field: 'Selling Price', oldValue: formatPHP(existingProd.sellingPrice), newValue: formatPHP(updatedRecord.sellingPrice) });
                if (existingProd.canBeSold !== updatedRecord.canBeSold) changes.push({ field: 'Can be Sold', oldValue: existingProd.canBeSold ? 'Yes' : 'No', newValue: updatedRecord.canBeSold ? 'Yes' : 'No' });
                if (existingProd.canBePurchased !== updatedRecord.canBePurchased) changes.push({ field: 'Can be Purchased', oldValue: existingProd.canBePurchased ? 'Yes' : 'No', newValue: updatedRecord.canBePurchased ? 'Yes' : 'No' });
                if ((existingProd.trackPhysicalStock ?? existingProd.trackStock) !== updatedRecord.trackPhysicalStock) changes.push({ field: 'Track Physical Stock', oldValue: (existingProd.trackPhysicalStock ?? existingProd.trackStock) ? 'Yes' : 'No', newValue: updatedRecord.trackPhysicalStock ? 'Yes' : 'No' });
                if ((existingProd.trackingBy || 'lots') !== updatedRecord.trackingBy) changes.push({ field: 'Track Inventory By', oldValue: existingProd.trackingBy || 'lots', newValue: updatedRecord.trackingBy });
                if ((existingProd.variants || []).length !== (updatedRecord.variants || []).length) changes.push({ field: 'Product Variants', oldValue: `${(existingProd.variants || []).length} variant(s)`, newValue: `${(updatedRecord.variants || []).length} variant(s)` });
                if ((existingProd.explodeBomOnSale ?? existingProd.hasBom) !== updatedRecord.explodeBomOnSale) changes.push({ field: 'Explode Recipe on Sale', oldValue: (existingProd.explodeBomOnSale ?? existingProd.hasBom) ? 'Yes' : 'No', newValue: updatedRecord.explodeBomOnSale ? 'Yes' : 'No' });
                if (existingProd.isActive !== updatedRecord.isActive) changes.push({ field: 'Status', oldValue: existingProd.isActive ? 'Active' : 'Inactive', newValue: updatedRecord.isActive ? 'Active' : 'Inactive' });

                if (changes.length > 0) {
                    let actionType = 'UPDATED';
                    if (changes.some(c => c.field === 'Selling Price' || c.field === 'Cost Price')) actionType = 'PRICE_CHANGE';
                    if (changes.some(c => c.field === 'Explode Recipe on Sale' || c.field === 'Has BOM')) actionType = 'BOM_CHANGE';

                    const log = createAuditEntry(currentUser, actionType, `Updated ${changes.length} product propert${changes.length === 1 ? 'y' : 'ies'}`, changes);
                    updatedRecord.auditLogs = [log, ...(existingProd.auditLogs || [])];
                } else {
                    updatedRecord.auditLogs = existingProd.auditLogs || [];
                }

                window.AppStore.products[index] = updatedRecord;
            }
            showToast(`Product "${updatedRecord.name}" successfully updated!`);
        }

        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.removeItem('rms_stocks_ledger');

        // Auto-sync BOM catalog entry with rms_boms
        try {
            let boms = JSON.parse(localStorage.getItem('rms_boms')) || [];
            const existingBomIdx = boms.findIndex(b => b.productId === newId);
            if (updatedRecord.hasBom) {
                if (existingBomIdx === -1) {
                    boms.unshift({
                        productId: newId,
                        yield: 1,
                        ingredients: []
                    });
                    localStorage.setItem('rms_boms', JSON.stringify(boms));
                }
            } else {
                if (existingBomIdx !== -1 && (!boms[existingBomIdx].ingredients || boms[existingBomIdx].ingredients.length === 0)) {
                    boms.splice(existingBomIdx, 1);
                    localStorage.setItem('rms_boms', JSON.stringify(boms));
                }
            }
        } catch (e) {
            console.warn('BOM storage sync:', e);
        }

        closeProductDrawer();
    }

    // =========================================================================
    // IMPORT & EXPORT CSV CAPABILITIES
    // =========================================================================
    let importedParsedProducts = [];

    // Helper: Escape CSV fields properly
    function escapeCSV(val) {
        if (val === null || val === undefined) return '""';
        let str = String(val).replace(/"/g, '""');
        return `"${str}"`;
    }

    // Export current products to CSV
    window.exportProductsCSV = function() {
        const products = window.AppStore.products;
        if (!products || products.length === 0) {
            showToast('No products available to export.');
            return;
        }

        const headers = [
            'SKU', 'Product Name', 'Barcode', 'Product Description',
            'Category', 'Sub-category', 'UOM / Pack Size', 'Cost Price',
            'Selling Price', 'Tax Rate', 'Primary Supplier', 'Allergen Tags', 'Status'
        ];

        const rows = products.map(p => [
            escapeCSV(p.sku),
            escapeCSV(p.name),
            escapeCSV(p.barcode || ''),
            escapeCSV(p.description || ''),
            escapeCSV(p.category || ''),
            escapeCSV(p.subcategory || ''),
            escapeCSV(p.unit || ''),
            escapeCSV(p.costPrice || 0),
            escapeCSV(p.sellingPrice || 0),
            escapeCSV(p.taxRate || 0),
            escapeCSV(p.supplier || ''),
            escapeCSV((p.allergens || []).join('; ')),
            escapeCSV(p.status || 'Active')
        ]);

        const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const now = new Date();
        const dateStr = now.toISOString().slice(0, 10);
        link.setAttribute('href', url);
        link.setAttribute('download', `RMS_Product_Catalog_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        showToast(`Successfully exported ${products.length} products to CSV`);
    };

    const importModal = document.getElementById('importProductsModal');
    const importFileInput = document.getElementById('importFileInput');
    const importDropzone = document.getElementById('importDropzone');
    const importPreviewWrapper = document.getElementById('importPreviewWrapper');
    const importPreviewCount = document.getElementById('importPreviewCount');
    const importPreviewTbody = document.getElementById('importPreviewTbody');
    const btnConfirmImport = document.getElementById('btnConfirmImport');

    window.openImportModal = function() {
        importedParsedProducts = [];
        if (importFileInput) importFileInput.value = '';
        if (importPreviewWrapper) importPreviewWrapper.style.display = 'none';
        if (importPreviewTbody) importPreviewTbody.innerHTML = '';
        if (btnConfirmImport) btnConfirmImport.disabled = true;
        if (importModal) importModal.classList.add('is-open');
    };

    window.closeImportModal = function() {
        if (importModal) importModal.classList.remove('is-open');
    };

    // Drag and drop event listeners for dropzone
    if (importDropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            importDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                importDropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            importDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                importDropzone.classList.remove('is-dragover');
            });
        });

        importDropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                parseCSVFile(files[0]);
            }
        });
    }

    window.handleImportFileSelect = function(e) {
        const file = e.target.files && e.target.files[0];
        if (file) {
            parseCSVFile(file);
        }
    };

    function parseCSVLine(line) {
        const result = [];
        let cur = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            const nextChar = line[i + 1];
            if (char === '"') {
                if (inQuotes && nextChar === '"') {
                    cur += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                result.push(cur.trim());
                cur = '';
            } else {
                cur += char;
            }
        }
        result.push(cur.trim());
        return result;
    }

    function parseCSVFile(file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const text = evt.target.result;
            const lines = text.split(/\r?\n/).filter(line => line.trim().length > 0);
            if (lines.length < 2) {
                showToast('The selected CSV file does not contain header and row data.');
                return;
            }

            const headerRow = parseCSVLine(lines[0]).map(h => h.toLowerCase().replace(/[^a-z0-9]/g, ''));
            const skuIdx = headerRow.findIndex(h => h.includes('sku'));
            const nameIdx = headerRow.findIndex(h => h.includes('name') || h.includes('product'));
            const barcodeIdx = headerRow.findIndex(h => h.includes('barcode'));
            const descIdx = headerRow.findIndex(h => h.includes('desc'));
            const catIdx = headerRow.findIndex(h => h.includes('cat') && !h.includes('sub'));
            const subcatIdx = headerRow.findIndex(h => h.includes('subcat'));
            const uomIdx = headerRow.findIndex(h => h.includes('uom') || h.includes('unit') || h.includes('pack'));
            const costIdx = headerRow.findIndex(h => h.includes('cost'));
            const priceIdx = headerRow.findIndex(h => h.includes('sell') || h.includes('price'));
            const taxIdx = headerRow.findIndex(h => h.includes('tax'));
            const supIdx = headerRow.findIndex(h => h.includes('supp'));
            const allergenIdx = headerRow.findIndex(h => h.includes('allergen'));
            const statusIdx = headerRow.findIndex(h => h.includes('stat'));

            importedParsedProducts = [];

            for (let i = 1; i < lines.length; i++) {
                const cols = parseCSVLine(lines[i]);
                if (cols.length === 0 || cols.every(c => c === '')) continue;

                const name = nameIdx !== -1 && cols[nameIdx] ? cols[nameIdx] : `Imported Item ${i}`;
                const sku = skuIdx !== -1 && cols[skuIdx] ? cols[skuIdx] : `IMP-${1000 + i}`;
                const category = catIdx !== -1 && cols[catIdx] ? cols[catIdx] : (window.AppStore.categories[0] || 'Beverages');
                const costPrice = costIdx !== -1 ? (parseFloat(cols[costIdx].replace(/[^0-9.-]+/g, '')) || 0) : 0;
                const sellingPrice = priceIdx !== -1 ? (parseFloat(cols[priceIdx].replace(/[^0-9.-]+/g, '')) || 0) : 0;
                const barcode = barcodeIdx !== -1 ? cols[barcodeIdx] : '';
                const description = descIdx !== -1 ? cols[descIdx] : '';
                const subcategory = subcatIdx !== -1 ? cols[subcatIdx] : '';
                const unit = uomIdx !== -1 ? cols[uomIdx] : 'Piece';
                const taxRate = taxIdx !== -1 ? (parseFloat(cols[taxIdx].replace(/[^0-9.-]+/g, '')) || 12) : 12;
                const supplier = supIdx !== -1 ? cols[supIdx] : 'Standard Supplier';
                const allergens = allergenIdx !== -1 && cols[allergenIdx] ? cols[allergenIdx].split(/;|,\s*/).map(a => a.trim()).filter(Boolean) : [];
                const status = (statusIdx !== -1 && cols[statusIdx]) ? cols[statusIdx] : 'Active';

                importedParsedProducts.push({
                    id: Date.now() + i,
                    sku: sku,
                    name: name,
                    barcode: barcode,
                    description: description,
                    category: category,
                    subcategory: subcategory,
                    unit: unit,
                    costPrice: costPrice,
                    sellingPrice: sellingPrice,
                    taxRate: taxRate,
                    supplier: supplier,
                    allergens: allergens,
                    status: (status.toLowerCase().includes('inact') ? 'Inactive' : 'Active'),
                    stock: 0
                });
            }

            if (importedParsedProducts.length === 0) {
                showToast('No valid product rows found in the CSV file.');
                return;
            }

            // Populate preview
            importPreviewCount.textContent = `${importedParsedProducts.length} product(s) ready to import`;
            let previewRows = '';
            importedParsedProducts.slice(0, 10).forEach(p => {
                previewRows += `
                    <tr>
                        <td><strong>${p.sku}</strong></td>
                        <td>${p.name}</td>
                        <td><span class="inv-category-pill" style="font-size: 0.68rem; padding: 2px 7px;">${p.category}</span></td>
                        <td>$${p.sellingPrice.toFixed(2)}</td>
                        <td><span class="inv-status-pill ${p.status === 'Active' ? 'is-active' : 'is-inactive'}" style="font-size:0.68rem;">${p.status}</span></td>
                    </tr>
                `;
            });
            if (importedParsedProducts.length > 10) {
                previewRows += `<tr><td colspan="5" style="text-align:center; color: var(--inv-text-muted); font-style:italic;">...and ${importedParsedProducts.length - 10} more rows</td></tr>`;
            }
            importPreviewTbody.innerHTML = previewRows;
            importPreviewWrapper.style.display = 'block';
            btnConfirmImport.disabled = false;

            showToast(`${importedParsedProducts.length} products parsed from CSV.`);
        };
        reader.readAsText(file);
    }

    window.confirmImportProducts = function() {
        if (!importedParsedProducts || importedParsedProducts.length === 0) {
            showToast('No products ready for import.');
            return;
        }

        // Upsert into categories if new categories were discovered
        importedParsedProducts.forEach(p => {
            if (p.category && !window.AppStore.categories.includes(p.category)) {
                window.AppStore.categories.push(p.category);
            }
        });
        localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));

        // Merge or replace by SKU
        const existingProducts = [...window.AppStore.products];
        let addedCount = 0;
        let updatedCount = 0;

        importedParsedProducts.forEach(item => {
            const existingIdx = existingProducts.findIndex(p => p.sku.toLowerCase() === item.sku.toLowerCase());
            if (existingIdx !== -1) {
                existingProducts[existingIdx] = { ...existingProducts[existingIdx], ...item, id: existingProducts[existingIdx].id };
                updatedCount++;
            } else {
                existingProducts.push(item);
                addedCount++;
            }
        });

        window.AppStore.products = existingProducts;
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));

        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        closeImportModal();

        showToast(`Import finished: ${addedCount} added, ${updatedCount} updated.`);
    };

    window.downloadSampleCSV = function() {
        const sampleHeaders = 'SKU,Product Name,Barcode,Product Description,Category,Sub-category,UOM / Pack Size,Cost Price,Selling Price,Tax Rate,Primary Supplier,Allergen Tags,Status\r\n';
        const sampleRows = 'SAMPLE-001,"Signature Roasted Coffee","4800019283999","Premium dark roasted whole bean coffee blend","Beverages","Hot Beverages","Bag 1kg",12.50,24.00,12,"Bean Roasters Inc.","None","Active"\r\nSAMPLE-002,"Matcha Crepe Slice","4800019283888","Multilayer green tea crepe cake with mascarpone","Pastries & Desserts","Cakes","Slice",3.20,7.50,12,"Artisan Bakery Co.","Dairy; Gluten","Active"\r\n';
        const blob = new Blob(['\uFEFF' + sampleHeaders + sampleRows], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'RMS_Product_Catalog_Sample.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    // Close Category Autocomplete on outside click
    document.addEventListener('click', function(e) {
        const drop = document.getElementById('catAutocompleteDropdown');
        const inputWrap = document.querySelector('.inv-cat-input-wrapper');
        if (drop && drop.classList.contains('is-open')) {
            if (inputWrap && !inputWrap.contains(e.target)) {
                drop.classList.remove('is-open');
            }
        }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const filterDropdown = document.getElementById('tableColumnFilterDropdown');
            if (filterDropdown && filterDropdown.classList.contains('is-active')) {
                filterDropdown.classList.remove('is-active');
                const triggerBtn = document.getElementById('btnTableColumnFilter');
                if (triggerBtn) triggerBtn.classList.remove('is-active');
                return;
            }
            if (importModal && importModal.classList.contains('is-open')) {
                closeImportModal();
                return;
            }
            if (newCategoryModal.classList.contains('is-open')) {
                closeNewCategoryModal();
                return;
            }
            if (window.AppStore.activeWorkspaceTab === 'details') {
                e.preventDefault();
                closeProductDrawer();
                return;
            }
        }

        if (e.key === 'F2') {
            e.preventDefault();
            window.openProductDrawer('new');
            return;
        }

        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            if (window.AppStore.activeWorkspaceTab !== 'catalog') {
                window.switchMasterWorkspaceTab('catalog');
            }
            searchInput.focus();
            searchInput.select();
        }
    });

    // Initialize View
    syncCategoryControls();
    renderTableHeaders();
    renderMasterList();
    renderCategorySettingsTab();


    // Expose internal functions to window for inline HTML event handlers & ERP cross-module compatibility
    Object.assign(window, {
        saveProductChanges,
        switchPrimaryTab,
        switchSubTab,
        updateMarginCalculation,
        syncCategoryControls,
        syncSubcategoryDropdown,
        renderTableHeaders,
        renderMasterList,
        renderCategorySettingsTab,
        renderProductAuditLogs,
        renderCategoryIconGrid,
        renderCategoryColorGrid,
        updateCategoryLivePreview,
        hideCategoryDuplicateWarning,
        hideCategoryAutocomplete,
        selectProductItemType,
        openReorderRulesModal,
        closeReorderRulesModal,
        selectReorderStrategy,
        saveReorderRulesModal,
        navigateLocationPage,
    });

})();