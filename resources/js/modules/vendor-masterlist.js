/**
 * ============================================================================
 * VENDOR MASTERLIST & AUTOMATED PROCUREMENT ENGINE
 * ERP-Style Kanban Grid Directory + Real-Time 3-Tab Profile Workspace
 * Direct Integration with Item Master (Pricing & Suppliers)
 * ============================================================================
 */

// Master Item Dictionary from Item Master (resources/views/inventory/product-categories.blade.php)
const ITEM_MASTER_DICTIONARY = [
    { sku: 'RAW-301', name: 'Barista Whole Fresh Milk', unit: 'Liter', defaultCost: 85.00, category: 'Raw Ingredients' },
    { sku: 'RAW-302', name: 'Arabica Espresso Beans (Single Origin)', unit: 'Kg', defaultCost: 550.00, category: 'Raw Ingredients' },
    { sku: 'RAW-303', name: 'French Butter Blocks Unsalted', unit: 'Pc', defaultCost: 120.00, category: 'Raw Ingredients' },
    { sku: 'RAW-304', name: 'Cheddar Melt Shredded Cheese', unit: 'Kg', defaultCost: 320.00, category: 'Raw Ingredients' },
    { sku: 'RAW-305', name: 'Culinary Whipping Cream 35%', unit: 'Liter', defaultCost: 180.00, category: 'Raw Ingredients' },
    { sku: 'RAW-306', name: 'All-Purpose Wheat Flour', unit: 'Kg', defaultCost: 39.20, category: 'Raw Ingredients' },
    { sku: 'RAW-307', name: 'Farm Fresh Table Eggs XL', unit: 'Pc', defaultCost: 7.50, category: 'Raw Ingredients' },
    { sku: 'RAW-308', name: 'US Choice Ribeye Beef Primal', unit: 'Kg', defaultCost: 840.00, category: 'Raw Ingredients' },
    { sku: 'RAW-309', name: 'Pork Belly Skin-On Slab', unit: 'Kg', defaultCost: 340.00, category: 'Raw Ingredients' },
    { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', unit: 'Pc', defaultCost: 4.50, category: 'Packaging' },
    { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', unit: 'Pc', defaultCost: 8.20, category: 'Packaging' }
];

// Seeded Supplier Database populated with full realistic partners matching screenshot
const SEEDED_VENDORS = [
    {
        id: "VND-MER-001",
        code: "VND-MER-001",
        legalName: "Manila Electric Company",
        tradeName: "Meralco Power Systems",
        location: "Ortigas Center, Pasig City, Philippines",
        email: "corporate.accounts@meralco.com.ph",
        tin: "000-101-528-000-VAT",
        address: "Lopez Building, Meralco Center, Ortigas Ave, Pasig City, Metro Manila, 1600",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "ME",
        avatarBg: "#475569",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Engr. Marco Santos",
                title: "Industrial Key Account Manager",
                phone: "+63 917 554 9011",
                email: "marco.santos@meralco.com.ph",
                notes: "Primary liaison for 3-phase commercial kitchen meter installation."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Teresa Cruz",
                title: "Corporate Billing Supervisor",
                phone: "+63 918 442 3901",
                email: "billing.commercial@meralco.com.ph",
                notes: "Automated direct debit reconciliation contact."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Nestor Diaz",
                title: "Emergency Outage Dispatch Chief",
                phone: "+63 920 887 1145",
                email: "dispatch.pasig@meralco.com.ph",
                notes: "24/7 hotline for emergency commissary substation repairs."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 1,
            rushTime: "2 Hours",
            mov: 0,
            moq: "1 Account",
            cutoff: "5:00 PM",
            deliveryDays: "Monday to Sunday (24/7 Grid Supply)",
            earlyDiscount: "1% 10 Net 30",
            scheduleDesc: "Commercial uninterrupted grid feed with automatic backup breaker.",
            bankName: "Metrobank",
            accountName: "Manila Electric Company",
            accountNumber: "007-882-90123",
            bankSwift: "MBTCPHMM",
            bankInstructions: "Direct electronic bank fund transfer with TIN validation"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "VND-UTL-ELEC",
                name: "Commercial Kitchen Power Grid Feed",
                uomPurchased: "Monthly Billing (kWh)",
                ratioQty: 1000,
                ratioUnit: "Pc",
                cost: 12500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-INF-002",
        code: "VND-INF-002",
        legalName: "INFRA TOWER PHILIPPINES INC",
        tradeName: "PhilTower Infrastructure",
        location: "TAGUIG, Philippines",
        email: "barack.odhuno@infratowers.com",
        tin: "009-774-219-000-VAT",
        address: "8th Floor, Menarco Tower, 32nd St, BGC, Taguig City, Philippines",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "IT",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Barack Odhuno",
                title: "Regional Operations Director",
                phone: "+63 917 889 0041",
                email: "barack.odhuno@infratowers.com",
                notes: "Handles rooftop equipment lease agreements."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Aileen Soriano",
                title: "Senior Finance Officer",
                phone: "+63 918 332 9981",
                email: "aileen.soriano@infratowers.com",
                notes: "Monthly lease tax withholding BIR 2307 coordinator."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Gerry Ramos",
                title: "Tower Maintenance Dispatcher",
                phone: "+63 920 112 4478",
                email: "dispatch@infratowers.com",
                notes: "Permit to work coordinator for rooftop technicians."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 5,
            rushTime: "24 Hours",
            mov: 10000,
            moq: "1 Facility",
            cutoff: "3:00 PM",
            deliveryDays: "Monday to Friday (8:00 AM – 5:00 PM)",
            earlyDiscount: "None",
            scheduleDesc: "Strict security check-in at building loading bay with PPE required.",
            bankName: "BDO Unibank, Inc.",
            accountName: "Infra Tower Philippines Inc",
            accountNumber: "0041-5521-9801",
            bankSwift: "BNORPHMM",
            bankInstructions: "Wire transfer reference code: TWR-BGC-RMS"
        },
        catalog: [
            {
                itemMasterSku: "PKG-502",
                sku: "VND-INF-TWR",
                name: "Cloud Server Rooftop Transceiver Link",
                uomPurchased: "Monthly Service Unit",
                ratioQty: 100,
                ratioUnit: "Pc",
                cost: 820.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-PRM-003",
        code: "VND-PRM-003",
        legalName: "PRIMUS@KNOWLEDGE SPECIALISTS INCORPORATED",
        tradeName: "Primus Tech & Cloud POS",
        location: "Mandaluyong City, Philippines",
        email: "jumelendrez@primus.com.ph",
        tin: "007-889-123-000-VAT",
        address: "Pioneer Highlands Tower 2, Pioneer St, Mandaluyong City, Philippines",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "PK",
        avatarBg: "#0d9488",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Jume Lendrez",
                title: "Client Technical Lead",
                phone: "+63 917 773 8920",
                email: "jumelendrez@primus.com.ph",
                notes: "Primary contact for Kitchen Display System (KDS) and POS cloud sync."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Patricia Gomez",
                title: "AR Senior Accountant",
                phone: "+63 918 221 4455",
                email: "ar@primus.com.ph",
                notes: "Reconciliation of software subscription invoices."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Arnel Reyes",
                title: "Field Deployment Engineer",
                phone: "+63 920 665 9912",
                email: "dispatch@primus.com.ph",
                notes: "Dispatches thermal printer replacements within 4 hours."
            }
        ],
        terms: {
            payment: "Net 15",
            leadTimeDays: 2,
            rushTime: "4 Hours",
            mov: 2500,
            moq: "1 License",
            cutoff: "4:00 PM",
            deliveryDays: "Monday to Friday (9:00 AM – 6:00 PM)",
            earlyDiscount: "2% 10 Net 15",
            scheduleDesc: "Remote digital deployment with optional onsite field support.",
            bankName: "UnionBank of the Philippines",
            accountName: "Primus Knowledge Specialists Inc",
            accountNumber: "1094-8832-1145",
            bankSwift: "UBPHPHMM",
            bankInstructions: "Include RMS Client ID in bank memo"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "VND-PRM-POS",
                name: "Kitchen Display POS Terminal Subscription",
                uomPurchased: "Monthly Per Terminal",
                ratioQty: 400,
                ratioUnit: "Pc",
                cost: 1800.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-VECO-004",
        code: "VND-VECO-004",
        legalName: "Visayan Electric Company",
        tradeName: "VECO Cebu Power",
        location: "Cebu, Philippines",
        email: "info@visayanelectric.com",
        tin: "000-334-901-000-VAT",
        address: "J. Panis St, Banilad, Cebu City, Cebu, 6000",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "VE",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Roberto Tan",
                title: "Visayas Key Accounts Head",
                phone: "+63 917 882 1109",
                email: "roberto.tan@visayanelectric.com",
                notes: "Handles power capacity upgrades for provincial branches."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Cecilia Yu",
                title: "Accounting Manager",
                phone: "+63 918 776 2200",
                email: "billing@visayanelectric.com",
                notes: "Monthly provincial SOA reconciliation."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Danilo Go",
                title: "Substation Dispatcher",
                phone: "+63 920 443 8811",
                email: "dispatch.cebu@visayanelectric.com",
                notes: "24/7 Cebu grid dispatch center."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 1,
            rushTime: "2 Hours",
            mov: 0,
            moq: "1 Meter",
            cutoff: "5:00 PM",
            deliveryDays: "Monday to Sunday (24/7 Grid Supply)",
            earlyDiscount: "None",
            scheduleDesc: "Automatic commercial feed via Banilad substation feeder.",
            bankName: "Bank of the Philippine Islands (BPI)",
            accountName: "Visayan Electric Company",
            accountNumber: "0211-9988-34",
            bankSwift: "BOPIPHMM",
            bankInstructions: "Provide VECO Account Reference Number"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "VND-VEC-ELEC",
                name: "Cebu Hub Commercial Power Service",
                uomPurchased: "Monthly Billing (kWh)",
                ratioQty: 1000,
                ratioUnit: "Pc",
                cost: 11800.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-BOT-005",
        code: "VND-BOT-005",
        legalName: "Bote, Elisa Calma",
        tradeName: "Bote Farm Agri-Produce",
        location: "Bulacan Farm Hub, Philippines",
        email: "elisa.bote@philtower.net",
        tin: "192-883-401-000-NON-VAT",
        address: "Sitio Riverside, Barangay Tartaro, San Miguel, Bulacan, Philippines",
        category: "Produce",
        tagClass: "tag-produce",
        status: "Active Preferred",
        avatarType: "initial-box",
        avatarText: "B",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Elisa Calma Bote",
                title: "Farm Owner & Managing Director",
                phone: "+63 917 441 9021",
                email: "elisa.bote@philtower.net",
                notes: "Contact directly before 11:00 AM for same-day farm harvest allocations."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Lito Bote",
                title: "Farm Cashier & Bookkeeper",
                phone: "+63 918 331 4455",
                email: "lito.bote@farms.ph",
                notes: "Handles weekly egg collection receipts and bank deposit confirmations."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Carlos Bote",
                title: "Cold Van Logistics Driver",
                phone: "+63 920 889 1234",
                email: "carlos.dispatch@farms.ph",
                notes: "Plate No: NFD-4821 (Isuzu Refrigerated Delivery Van)."
            }
        ],
        terms: {
            payment: "Net 7",
            leadTimeDays: 2,
            rushTime: "12 Hours",
            mov: 3000,
            moq: "10 Trays",
            cutoff: "11:00 AM",
            deliveryDays: "Tuesday, Thursday, Saturday (5:00 AM – 8:00 AM)",
            earlyDiscount: "3% Cash Upon Unload",
            scheduleDesc: "Eggs must be checked for crack percentage and temperature upon unloading.",
            bankName: "Land Bank of the Philippines",
            accountName: "Elisa Calma Bote",
            accountNumber: "1822-4401-29",
            bankSwift: "TLBPPHMM",
            bankInstructions: "Direct deposit or GCash Enterprise payment accepted"
        },
        catalog: [
            {
                itemMasterSku: "RAW-307",
                sku: "VND-BOT-EGG",
                name: "Farm Fresh Table Eggs XL",
                uomPurchased: "Tray (30 Pcs)",
                ratioQty: 30,
                ratioUnit: "Pc",
                cost: 210.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "RAW-306",
                sku: "VND-BOT-FLR",
                name: "Bulacan Artisan Milled Flour",
                uomPurchased: "Sack (25 Kg)",
                ratioQty: 25,
                ratioUnit: "Kg",
                cost: 900.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-DAV-006",
        code: "VND-DAV-006",
        legalName: "DAVAO LIGHT AND POWER COMPANY, INC.",
        tradeName: "Davao Light (AboitizPower)",
        location: "Davao City",
        email: "davaolight@aboitiz.com",
        tin: "000-441-998-000-VAT",
        address: "C. Bangoy Sr. St, Poblacion District, Davao City, Davao del Sur, 8000",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "DL",
        avatarBg: "#475569",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Manuel Aboitiz",
                title: "Mindanao Regional Accounts Head",
                phone: "+63 917 334 5500",
                email: "manuel.aboitiz@davaolight.com",
                notes: "Coordinates Mindanao hub power load connections."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Grace Lim",
                title: "Commercial Billing Specialist",
                phone: "+63 918 881 2299",
                email: "grace.lim@davaolight.com",
                notes: "Direct debit SOA manager."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Felipe Santos",
                title: "Davao Technical Operations Chief",
                phone: "+63 920 771 0033",
                email: "dispatch@davaolight.com",
                notes: "24/7 hotline for transformer circuit issues."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 1,
            rushTime: "2 Hours",
            mov: 0,
            moq: "1 Account",
            cutoff: "5:00 PM",
            deliveryDays: "Monday to Sunday (24/7 Grid Supply)",
            earlyDiscount: "None",
            scheduleDesc: "Continuous industrial power feed.",
            bankName: "UnionBank of the Philippines",
            accountName: "Davao Light and Power Company",
            accountNumber: "1012-7744-8899",
            bankSwift: "UBPHPHMM",
            bankInstructions: "Provide Customer Account ID in electronic bank transfer"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "VND-DLP-PWR",
                name: "Mindanao Hub Industrial Utility Power",
                uomPurchased: "Monthly Billing (kWh)",
                ratioQty: 1000,
                ratioUnit: "Pc",
                cost: 10950.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-GLD-007",
        code: "VND-GLD-007",
        legalName: "GOLDEN DESIGN KONSTRUKT & TRADING",
        tradeName: "Golden Design Kitchen Stainless",
        location: "Quezon City, Philippines",
        email: "goldendesign@rocketmail.com",
        tin: "201-994-311-000-VAT",
        address: "74 Tandang Sora Avenue, Culiat, Quezon City, Metro Manila, 1128",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "GD",
        avatarBg: "#0d9488",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Arch. Gilbert Cruz",
                title: "Managing Partner",
                phone: "+63 917 881 4455",
                email: "goldendesign@rocketmail.com",
                notes: "On-site kitchen measurements and custom exhaust duct fabrication."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Gina Santos",
                title: "Finance & Progress Billing Officer",
                phone: "+63 918 443 1122",
                email: "gina.goldendesign@gmail.com",
                notes: "50% downpayment upon PO, 50% upon completed commissioning."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Rudy Morales",
                title: "Installation Team Foreman",
                phone: "+63 920 334 8877",
                email: "rudy.foreman@gmail.com",
                notes: "Coordinates after-hours kitchen installation crew gate passes."
            }
        ],
        terms: {
            payment: "Net 15",
            leadTimeDays: 4,
            rushTime: "24 Hours",
            mov: 5000,
            moq: "1 Piece",
            cutoff: "2:00 PM",
            deliveryDays: "Monday to Saturday (8:00 AM – 6:00 PM)",
            earlyDiscount: "None",
            scheduleDesc: "Delivery vehicle requires 2.5m clearance. Security gate pass needed.",
            bankName: "BDO Unibank, Inc.",
            accountName: "Golden Design Konstrukt & Trading",
            accountNumber: "0029-8812-7734",
            bankSwift: "BNORPHMM",
            bankInstructions: "Attach proof of transfer to sales order confirmation"
        },
        catalog: [
            {
                itemMasterSku: "PKG-502",
                sku: "VND-GLD-EXH",
                name: "Kitchen Baffle Grease Filter Stainless SS304",
                uomPurchased: "Box of 2 Filters",
                ratioQty: 2,
                ratioUnit: "Pc",
                cost: 2200.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-STL-008",
        code: "VND-STL-008",
        legalName: "STEELASIA MANUFACTURING CORP.",
        tradeName: "SteelAsia Heavy Industrial",
        location: "Taguig City, Philippines",
        email: "osdelacruz@steelasia.com",
        tin: "000-881-224-000-VAT",
        address: "22nd Floor, The Curve, 32nd St cor 3rd Ave, BGC, Taguig City, 1634",
        category: "Packaging",
        tagClass: "tag-packaging",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "SA",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Oscar Dela Cruz",
                title: "Industrial Accounts Vice President",
                phone: "+63 917 554 9911",
                email: "osdelacruz@steelasia.com",
                notes: "Bulk kitchen structural rebar & stainless storage racking."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Melanie Sotto",
                title: "Credit & Collection Specialist",
                phone: "+63 918 223 4499",
                email: "ar.steelasia@steelasia.com",
                notes: "Net 30 terms subject to corporate credit limit approval."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Joel Valenzuela",
                title: "Calaca Plant Dispatch Officer",
                phone: "+63 920 887 3321",
                email: "dispatch.calaca@steelasia.com",
                notes: "10-wheeler boom truck dispatch requires street unloading permit."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 7,
            rushTime: "48 Hours",
            mov: 20000,
            moq: "1 Metric Ton",
            cutoff: "1:00 PM",
            deliveryDays: "Tuesday & Friday (7:00 AM – 3:00 PM)",
            earlyDiscount: "1.5% 10 Net 30",
            scheduleDesc: "Flatbed boom truck delivery. Forklift required at receiving warehouse.",
            bankName: "Metrobank",
            accountName: "SteelAsia Manufacturing Corp",
            accountNumber: "066-778-9012",
            bankSwift: "MBTCPHMM",
            bankInstructions: "Provide PO Reference and Mill Certificate Number"
        },
        catalog: [
            {
                itemMasterSku: "PKG-502",
                sku: "VND-STL-TBL",
                name: "Heavy-Duty Stainless Steel Commissary Prep Table",
                uomPurchased: "Unit Assembled",
                ratioQty: 1,
                ratioUnit: "Pc",
                cost: 14500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-JGL-009",
        code: "VND-JGL-009",
        legalName: "JAYA GUNA LANCAR (JGL) INC.",
        tradeName: "JGL Cold Storage & Reefer Logistics",
        location: "Manila, Philippines",
        email: "gunawan@jglinc.ph",
        tin: "009-441-230-000-VAT",
        address: "North Harbor Center, Tondo, Manila, Metro Manila, 1012",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "JG",
        avatarBg: "#0d9488",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Gunawan Santoso",
                title: "Logistics Managing Director",
                phone: "+63 917 882 3341",
                email: "gunawan@jglinc.ph",
                notes: "Coordinates inter-island frozen meat container transport."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Lina Wijaya",
                title: "Billing Supervisor",
                phone: "+63 918 332 1188",
                email: "billing@jglinc.ph",
                notes: "Freight invoice reconciliation every 15th."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Budi Hartono",
                title: "Cold Chain Reefer Fleet Manager",
                phone: "+63 920 119 7744",
                email: "dispatch@jglinc.ph",
                notes: "GPS live temperature tracking available 24/7."
            }
        ],
        terms: {
            payment: "Net 15",
            leadTimeDays: 2,
            rushTime: "8 Hours",
            mov: 4000,
            moq: "1 Pallet",
            cutoff: "12:00 PM",
            deliveryDays: "Daily Reefer Route (5:00 AM – 9:00 AM)",
            earlyDiscount: "2% 7 Net 15",
            scheduleDesc: "Cold chain temperature log printout must be signed before dock offload.",
            bankName: "Rizal Commercial Banking Corporation (RCBC)",
            accountName: "Jaya Guna Lancar Inc",
            accountNumber: "9012-3344-55",
            bankSwift: "RCBCPHMM",
            bankInstructions: "Provide Waybill Number on deposit receipt"
        },
        catalog: [
            {
                itemMasterSku: "RAW-308",
                sku: "VND-JGL-REEF",
                name: "Sub-Zero Reefer Pallet Transport (-18°C)",
                uomPurchased: "Per Pallet Run (1000 Kg)",
                ratioQty: 1000,
                ratioUnit: "Kg",
                cost: 3500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-NSS-010",
        code: "VND-NSS-010",
        legalName: "NSS ELECTRICAL SERVICES, INC.",
        tradeName: "NSS Industrial Power & Maintenance",
        location: "Binangonan, Rizal, Philippines",
        email: "nolisulit@yahoo.com",
        tin: "188-442-991-000-VAT",
        address: "Manila East Road, Calumpang, Binangonan, Rizal, 1940",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "NS",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Noli Sulit",
                title: "General Manager & Master Electrician",
                phone: "+63 917 992 4410",
                email: "nolisulit@yahoo.com",
                notes: "On-call 24/7 for walk-in freezer compressor wiring tripped circuit."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Marilou Sulit",
                title: "Office Cashier",
                phone: "+63 918 442 8812",
                email: "marilou.nss@gmail.com",
                notes: "Official receipt issued upon technician sign-off."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Bryan Sulit",
                title: "Lead Field Electrician",
                phone: "+63 920 882 1199",
                email: "bryan.nss@gmail.com",
                notes: "Motorcycle rapid response team."
            }
        ],
        terms: {
            payment: "Net 7",
            leadTimeDays: 1,
            rushTime: "2 Hours",
            mov: 1500,
            moq: "1 Service Call",
            cutoff: "4:00 PM",
            deliveryDays: "Monday to Sunday (24/7 On-Call Emergency)",
            earlyDiscount: "None",
            scheduleDesc: "Technician must present company ID and safety boots at security desk.",
            bankName: "Bank of the Philippine Islands (BPI)",
            accountName: "NSS Electrical Services Inc",
            accountNumber: "3341-8890-12",
            bankSwift: "BOPIPHMM",
            bankInstructions: "Direct electronic transfer with Job Order reference"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "VND-NSS-MAINT",
                name: "Kitchen Walk-in Chiller Preventive Maintenance Inspection",
                uomPurchased: "Monthly Routine Service",
                ratioQty: 1,
                ratioUnit: "Pc",
                cost: 4500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-SUM-011",
        code: "VND-SUM-011",
        legalName: "SUMMIT 8 CONSTRUCTION & TRADING",
        tradeName: "Summit 8 Facilities Engineering",
        location: "Lipa City, Batangas, Philippines",
        email: "melizascairel@summit8construction.com",
        tin: "209-441-882-000-VAT",
        address: "Ayala Highway, Mataas na Lupa, Lipa City, Batangas, 4217",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "person",
        avatarText: "S8",
        avatarBg: "#0d9488",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Meliza Cairel",
                title: "Managing Partner",
                phone: "+63 917 332 8819",
                email: "melizascairel@summit8construction.com",
                notes: "Handles branch commissary waterproofing and grease trap civil works."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Ronaldo Cairel",
                title: "Finance & Purchasing Head",
                phone: "+63 918 882 1144",
                email: "ronaldo@summit8construction.com",
                notes: "Progress billing with 10% retention until 30-day warranty."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Dennis Perez",
                title: "Civil Works Project Manager",
                phone: "+63 920 441 7722",
                email: "dennis.pm@summit8construction.com",
                notes: "Coordinates nightly hot-works permits."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 3,
            rushTime: "12 Hours",
            mov: 8000,
            moq: "1 Project",
            cutoff: "2:00 PM",
            deliveryDays: "Monday to Friday (8:00 AM – 5:00 PM)",
            earlyDiscount: "2% 10 Net 30",
            scheduleDesc: "Safety orientation and fire extinguisher standby required on site.",
            bankName: "Security Bank",
            accountName: "Summit 8 Construction & Trading",
            accountNumber: "0000-8812-3341",
            bankSwift: "SETCPHMM",
            bankInstructions: "Provide project contract billing milestone"
        },
        catalog: [
            {
                itemMasterSku: "PKG-502",
                sku: "VND-SUM-CIV",
                name: "Kitchen Floor Polyurethane Anti-Slip Resurfacing",
                uomPurchased: "Per 10 Sqm Application",
                ratioQty: 10,
                ratioUnit: "Pc",
                cost: 8500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-NAS-012",
        code: "VND-NAS-012",
        legalName: "NASERIA CONSTRUCTION, OPC",
        tradeName: "Nascon Architectural Services",
        location: "PASIG, Philippines",
        email: "mlozanta.nascon@gmail.com",
        tin: "009-883-112-000-VAT",
        address: "Caruncho Ave, Malinao, Pasig City, Metro Manila, 1600",
        category: "Services",
        tagClass: "tag-services",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "NC",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "M. Lozanta",
                title: "Principal Architect & Owner",
                phone: "+63 917 442 1190",
                email: "mlozanta.nascon@gmail.com",
                notes: "Handles food safety sanitary tile layout designs."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Karen Lozanta",
                title: "Administrative Treasurer",
                phone: "+63 918 331 9901",
                email: "karen.nascon@gmail.com",
                notes: "Reconciliation of construction milestone invoices."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Rodel Navarro",
                title: "Field Supervisor",
                phone: "+63 920 882 4433",
                email: "rodel.nascon@gmail.com",
                notes: "Coordinates delivery of quarry tiles and grout materials."
            }
        ],
        terms: {
            payment: "Net 15",
            leadTimeDays: 3,
            rushTime: "24 Hours",
            mov: 5000,
            moq: "1 Job Order",
            cutoff: "3:00 PM",
            deliveryDays: "Monday to Saturday (7:00 AM – 4:00 PM)",
            earlyDiscount: "None",
            scheduleDesc: "All materials unloaded strictly at designated basement storage.",
            bankName: "BDO Unibank, Inc.",
            accountName: "Naseria Construction OPC",
            accountNumber: "0054-9981-2234",
            bankSwift: "BNORPHMM",
            bankInstructions: "Include Job Order number in payment advice"
        },
        catalog: [
            {
                itemMasterSku: "PKG-502",
                sku: "VND-NAS-TILE",
                name: "Heavy-Duty Quarry Kitchen Non-Skid Tiles (30x30cm)",
                uomPurchased: "Box of 11 Tiles (1 Sqm)",
                ratioQty: 1,
                ratioUnit: "Pc",
                cost: 1450.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-MAG-013",
        code: "VND-001",
        legalName: "Magnolia Fresh Dairy Philippines, Inc.",
        tradeName: "Magnolia Fresh",
        location: "Pasig City, Philippines",
        email: "maria@magnolia.ph",
        tin: "123-456-789-000",
        vatType: "VAT Registered (12%)",
        ewtRate: "1% (Purchase of Goods)",
        entityType: "Corporation",
        address: "Building 4, Megabiz Park, C5 Road, Pasig City",
        dispatchAddress: "Building 4, Megabiz Park, C5 Road, Pasig City",
        sameAsBilling: true,
        category: "Dairy & Cold Storage",
        tagClass: "tag-dairy",
        status: "Active",
        avatarType: "initial-box",
        avatarText: "MG",
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Account Mgr",
                roleClass: "role-sales",
                name: "Maria Santos",
                title: "Sales Account Mgr",
                phone: "+63 917 123 4567",
                email: "maria@magnolia.ph",
                isDefault: true,
                notes: "Primary sales account manager for dairy."
            },
            {
                role: "billing",
                roleLabel: "Billing & AP",
                roleClass: "role-billing",
                name: "John Cruz",
                title: "Billing & AP",
                phone: "+63 918 987 6543",
                email: "ap@magnolia.ph",
                isDefault: false,
                notes: "Handles AP invoices and 2307 withholding forms."
            }
        ],
        terms: {
            payment: "Net 30 Days",
            creditLimit: 100000,
            paymentMethod: "Bank Transfer (ACH)",
            bankName: "BDO Unibank",
            accountName: "Magnolia Fresh Dairy Philippines, Inc.",
            accountNumber: "0012-3456-7890",
            bankBranch: "Pasig-Ortigas Branch",
            leadTimeDays: 2,
            rushTime: "24 Hours",
            mov: 5000,
            moq: "5 Cases",
            freeDeliveryThreshold: 8000,
            deliveryDaysList: ["Mon", "Tue", "Thu", "Fri"],
            deliveryDays: "Mon, Tue, Thu, Fri",
            cutoff: "02:00 PM",
            fulfillmentType: "Supplier Delivery",
            returnPolicy: "Credit Memo on next bill",
            earlyDiscount: "2% 10 Net 30",
            scheduleDesc: "Refrigerated van temperature check at loading bay (below 4°C)."
        },
        catalog: [
            {
                itemMasterSku: "RAW-301",
                sku: "MAG-MILK-1L",
                name: "Barista Whole Fresh Milk",
                uomPurchased: "Case (12 Tetra)",
                ratioQty: 12,
                ratioUnit: "Liter",
                cost: 1020.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "RAW-305",
                sku: "MAG-WHP-1L",
                name: "Culinary Whipping Cream 35%",
                uomPurchased: "Case (6 Liters)",
                ratioQty: 6,
                ratioUnit: "Liter",
                cost: 1080.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "RAW-303",
                sku: "MAG-BTR-250",
                name: "French Butter Blocks Unsalted",
                uomPurchased: "Box (20 Pcs)",
                ratioQty: 20,
                ratioUnit: "Pc",
                cost: 2400.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "RAW-304",
                sku: "MAG-CHD-2KG",
                name: "Cheddar Melt Shredded Cheese",
                uomPurchased: "Pack (5 Kg)",
                ratioQty: 5,
                ratioUnit: "Kg",
                cost: 1600.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-SMP-014",
        code: "VND-SMP-014",
        legalName: "San Miguel Pure Foods Company Inc.",
        tradeName: "Monterey Meats Wholesale",
        location: "Pasig City, Metro Manila",
        email: "monterey.wholesale@sanmiguel.com.ph",
        tin: "000-554-321-000-VAT",
        address: "San Miguel Pure Foods Center, 100 E. Rodriguez Jr. Ave, C-5, Ugong, Pasig City, 1604",
        category: "Meat",
        tagClass: "tag-meat",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "SM",
        avatarBg: "#ef4444",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Gerardo 'Gerry' Tan",
                title: "Foodservice Meat Division Manager",
                phone: "+63 917 662 1099",
                email: "gerardo.tan@sanmiguel.com.ph",
                notes: "Handles beef ribeye primal slab cutting specifications and fat-cap trim."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Maria Teresa Flores",
                title: "Senior Credit Controller",
                phone: "+63 918 443 8812",
                email: "mtflores@sanmiguel.com.ph",
                notes: "SOA sent weekly on Fridays. 15-day strict credit line."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Sgt. Nestor Aguilar",
                title: "Cold Storage Dock Chief",
                phone: "+63 920 994 3311",
                email: "dispatch.canlubang@sanmiguel.com.ph",
                notes: "Dispatches reefer trucks directly from Canlubang Meat Plant."
            }
        ],
        terms: {
            payment: "Net 15",
            leadTimeDays: 2,
            rushTime: "12 Hours",
            mov: 12000,
            moq: "20 Kgs",
            cutoff: "1:00 PM",
            deliveryDays: "Monday, Wednesday, Friday (4:00 AM – 7:00 AM)",
            earlyDiscount: "None",
            scheduleDesc: "HACCP meat temp check strictly < -18°C for frozen and < 4°C for chilled primals.",
            bankName: "Metropolitan Bank and Trust Company (Metrobank)",
            accountName: "San Miguel Pure Foods Company Inc",
            accountNumber: "044-789-102938",
            bankSwift: "MBTCPHMM",
            bankInstructions: "Indicate Monterey Customer ID in check deposit / wire memo"
        },
        catalog: [
            {
                itemMasterSku: "RAW-308",
                sku: "SMF-RB-001",
                name: "US Choice Ribeye Beef Primal",
                uomPurchased: "Primal Case (15 Kg)",
                ratioQty: 15,
                ratioUnit: "Kg",
                cost: 12600.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "RAW-309",
                sku: "SMF-PK-002",
                name: "Pork Belly Skin-On Slab",
                uomPurchased: "Box (20 Kg)",
                ratioQty: 20,
                ratioUnit: "Kg",
                cost: 6800.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-BCM-015",
        code: "VND-BCM-015",
        legalName: "BakeCraft Milling & Supplies Co.",
        tradeName: "BakeCraft Artisan Mills",
        location: "Caloocan Industrial Zone, Metro Manila",
        email: "orders@bakecraftmills.ph",
        tin: "009-881-224-000-VAT",
        address: "Plot 14, Caloocan Light Industrial Park, 10th Avenue, Caloocan City, 1400",
        category: "Bakery",
        tagClass: "tag-bakery",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "BC",
        avatarBg: "#f59e0b",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Rowena De Jesus",
                title: "Bakery Key Accounts Head",
                phone: "+63 917 551 2290",
                email: "rowena@bakecraftmills.ph",
                notes: "Handles bulk grain & flour moisture spec certifications."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Francis Ramos",
                title: "Senior Billing Accountant",
                phone: "+63 918 334 1199",
                email: "francis@bakecraftmills.ph",
                notes: "Handles monthly milling SOAs."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Efren Villanueva",
                title: "Dry Freight Dispatcher",
                phone: "+63 920 881 4455",
                email: "dispatch@bakecraftmills.ph",
                notes: "Dry van delivery with moisture-resistant pallet wraps."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 3,
            rushTime: "24 Hours",
            mov: 4000,
            moq: "10 Sacks",
            cutoff: "2:00 PM",
            deliveryDays: "Tuesday & Thursday (8:00 AM – 12:00 PM)",
            earlyDiscount: "2% 10 Net 30",
            scheduleDesc: "Dry goods dock only. Keep dry and protected from rain or damp floors.",
            bankName: "Bank of the Philippine Islands (BPI)",
            accountName: "BakeCraft Milling & Supplies Co",
            accountNumber: "3390-1288-45",
            bankSwift: "BOPIPHMM",
            bankInstructions: "Provide PO number in bank reference"
        },
        catalog: [
            {
                itemMasterSku: "RAW-306",
                sku: "BCM-FLR-025",
                name: "All-Purpose Wheat Flour (Hard Wheat Blend)",
                uomPurchased: "Sack (25 Kg)",
                ratioQty: 25,
                ratioUnit: "Kg",
                cost: 980.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    },
    {
        id: "VND-ECO-016",
        code: "VND-ECO-016",
        legalName: "EcoPack Industrial Solutions Corp.",
        tradeName: "EcoPack Food Packaging",
        location: "Valenzuela City, Metro Manila",
        email: "sales@ecopackindustrial.ph",
        tin: "008-332-901-000-VAT",
        address: "88 Malinta Industrial Road, Paso de Blas, Valenzuela City, Metro Manila, 1442",
        category: "Packaging",
        tagClass: "tag-packaging",
        status: "Active Preferred",
        avatarType: "building",
        avatarText: "EP",
        avatarBg: "#8b5cf6",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Jonathan Sy",
                title: "Foodservice Packaging Director",
                phone: "+63 917 221 4455",
                email: "jonathan.sy@ecopackindustrial.ph",
                notes: "Handles custom logo cup printing and bio-degradable certification."
            },
            {
                role: "billing",
                roleLabel: "Billing / Accounting Rep (Invoice Reconciliation)",
                roleClass: "role-billing",
                name: "Maricel Cheng",
                title: "Credit Supervisor",
                phone: "+63 918 882 3311",
                email: "maricel@ecopackindustrial.ph",
                notes: "Net 30 invoices reconciled on 20th of each month."
            },
            {
                role: "dispatch",
                roleLabel: "Warehouse / Dispatch Rep (Delivery Tracking)",
                roleClass: "role-dispatch",
                name: "Ricky Santos",
                title: "Logistics Warehouse Foreman",
                phone: "+63 920 554 9912",
                email: "dispatch@ecopackindustrial.ph",
                notes: "Dispatches covered box trucks with shrink-wrapped cartons."
            }
        ],
        terms: {
            payment: "Net 30",
            leadTimeDays: 4,
            rushTime: "24 Hours",
            mov: 6000,
            moq: "10 Cartons",
            cutoff: "3:00 PM",
            deliveryDays: "Wednesday & Saturday (9:00 AM – 3:00 PM)",
            earlyDiscount: "1.5% 10 Net 30",
            scheduleDesc: "Dry packaging cartons must be checked for undamaged carton seals.",
            bankName: "China Banking Corporation",
            accountName: "EcoPack Industrial Solutions Corp",
            accountNumber: "188-2940-112",
            bankSwift: "CHBKPHMM",
            bankInstructions: "Email deposit slip to ar@ecopackindustrial.ph"
        },
        catalog: [
            {
                itemMasterSku: "PKG-501",
                sku: "ECO-CUP-12OZ",
                name: "Hot Coffee Paper Cups 12oz (PLA Lined)",
                uomPurchased: "Sleeve (1000 Pcs)",
                ratioQty: 1000,
                ratioUnit: "Pc",
                cost: 4500.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            },
            {
                itemMasterSku: "PKG-502",
                sku: "ECO-BX-KRAFT",
                name: "Kraft Takeout Food Boxes (Leakproof)",
                uomPurchased: "Bundle (500 Pcs)",
                ratioQty: 500,
                ratioUnit: "Pc",
                cost: 4100.00,
                validity: "Valid until Dec 31, 2026",
                syncedWithItemMaster: true
            }
        ]
    }
];

// Active Working Database
let VENDOR_DATABASE = JSON.parse(JSON.stringify(SEEDED_VENDORS));
let currentVendorId = "VND-MAG-013";
let activeFilterCategory = "all";
let activeTabId = "tab-general";

// Base default categories list for taxonomy initialization
const DEFAULT_VENDOR_CATEGORIES = [
    'Dairy & Cold Storage',
    'Dairy',
    'Meat',
    'Bakery',
    'Produce',
    'Packaging',
    'Services'
];

let CUSTOM_VENDOR_CATEGORIES = [];

function loadCustomCategories() {
    try {
        const stored = localStorage.getItem('rms_custom_vendor_categories');
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed)) {
                CUSTOM_VENDOR_CATEGORIES = parsed;
            }
        }
    } catch (e) {
        console.warn('Failed to load custom categories:', e);
    }
}

function saveCustomCategories() {
    try {
        localStorage.setItem('rms_custom_vendor_categories', JSON.stringify(CUSTOM_VENDOR_CATEGORIES));
    } catch (e) {
        console.warn('Failed to save custom categories:', e);
    }
}

/**
 * Normalizes vendor records with backward-compatible defaults for all ERP fields
 */
function sanitizeVendor(v) {
    if (!v) return v;
    if (!v.vatType) v.vatType = 'VAT Registered (12%)';
    if (!v.ewtRate) v.ewtRate = '1% (Purchase of Goods)';
    if (!v.entityType) v.entityType = 'Corporation';
    if (!v.dispatchAddress) v.dispatchAddress = v.address || '';
    if (v.sameAsBilling === undefined) v.sameAsBilling = true;

    // Normalize status to 4 canonical states: Active, Inactive, On Hold, Blacklisted
    if (!v.status) {
        v.status = 'Active';
    } else if (v.status.toLowerCase().includes('inactive')) {
        v.status = 'Inactive';
    } else if (v.status.toLowerCase().includes('hold') || v.status.toLowerCase().includes('probation')) {
        v.status = 'On Hold';
    } else if (v.status.toLowerCase().includes('black')) {
        v.status = 'Blacklisted';
    } else {
        v.status = 'Active';
    }

    if (!v.terms) v.terms = {};
    const t = v.terms;
    if (!t.payment) t.payment = 'Net 30 Days';
    if (t.payment === 'Net 30') t.payment = 'Net 30 Days';
    if (t.payment === 'Net 15') t.payment = 'Net 15 Days';
    if (t.payment === 'Net 7') t.payment = 'Net 7 Days';
    if (t.payment === 'Net 60') t.payment = 'Net 60 Days';
    if (t.payment === 'COD') t.payment = 'COD';

    if (t.creditLimit === undefined || t.creditLimit === null) t.creditLimit = 100000;
    if (!t.paymentMethod) t.paymentMethod = 'Bank Transfer (ACH)';
    if (!t.bankName) t.bankName = 'BDO Unibank';
    if (!t.accountName) t.accountName = v.legalName || 'Magnolia Fresh Dairy Philippines, Inc.';
    if (!t.accountNumber) t.accountNumber = '0012-3456-7890';
    if (!t.bankBranch) t.bankBranch = t.bankSwift || 'Pasig-Ortigas Branch';
    if (t.leadTimeDays === undefined) t.leadTimeDays = 2;
    if (t.mov === undefined) t.mov = 5000;
    if (!t.moq) t.moq = '5 Cases';
    if (t.freeDeliveryThreshold === undefined) t.freeDeliveryThreshold = 8000;
    if (!t.deliveryDaysList || !Array.isArray(t.deliveryDaysList)) {
        t.deliveryDaysList = ['Mon', 'Tue', 'Thu', 'Fri'];
    }
    if (!t.cutoff) t.cutoff = '02:00 PM';
    if (!t.fulfillmentType) t.fulfillmentType = 'Supplier Delivery';
    if (!t.returnPolicy) t.returnPolicy = 'Credit Memo on next bill';

    if (Array.isArray(v.contacts) && v.contacts.length > 0) {
        const hasDefault = v.contacts.some(c => c.isDefault);
        if (!hasDefault) {
            v.contacts[0].isDefault = true;
        }
    }
    return v;
}

/**
 * Extract all active vendor categories dynamically with supplier counts
 */
function getActiveVendorCategories() {
    const categoryMap = new Map();

    // 1. Register active categories from the database
    VENDOR_DATABASE.forEach(v => {
        const cat = (v.category || 'General').trim();
        if (cat) {
            categoryMap.set(cat, (categoryMap.get(cat) || 0) + 1);
        }
    });

    // 2. Register custom categories created by user
    CUSTOM_VENDOR_CATEGORIES.forEach(cat => {
        const trimmed = (cat || '').trim();
        if (trimmed && !categoryMap.has(trimmed)) {
            categoryMap.set(trimmed, 0);
        }
    });

    // 3. Ensure baseline categories exist in taxonomy even if 0 count
    DEFAULT_VENDOR_CATEGORIES.forEach(cat => {
        if (!categoryMap.has(cat)) {
            categoryMap.set(cat, 0);
        }
    });

    return categoryMap;
}

/**
 * Modular Dynamic Renderer for Category Filter Pills & Scalable Dropdown
 * Handles any number of vendor types (5, 20, 50+) with horizontal scroll + dropdown
 */
function renderModularCategoryFilters() {
    const pillsContainer = document.getElementById('vendorFilterPillsContainer');
    const selectEl = document.getElementById('vendorCategorySelect');
    if (!pillsContainer) return;

    const categoryMap = getActiveVendorCategories();
    const totalCount = VENDOR_DATABASE.length;

    // 1. Build Dynamic Filter Pills
    let pillsHtml = `
        <button type="button" class="vendor-pill-btn ${activeFilterCategory === 'all' ? 'active' : ''}" 
                onclick="filterKanbanCategory('all', this)" id="pill-all">
            <span>All Suppliers</span>
            <span class="vendor-pill-badge" id="totalSuppliersBadge">${totalCount}</span>
        </button>
    `;

    categoryMap.forEach((count, cat) => {
        const isActive = activeFilterCategory.toLowerCase() === cat.toLowerCase();
        pillsHtml += `
            <button type="button" class="vendor-pill-btn ${isActive ? 'active' : ''}" 
                    onclick="filterKanbanCategory('${cat}', this)" data-category="${cat}">
                <span>${cat}</span>
                <span class="vendor-pill-badge">${count}</span>
            </button>
        `;
    });

    pillsContainer.innerHTML = pillsHtml;

    // 2. Build Scalable Dropdown (instant selection when many categories exist)
    if (selectEl) {
        let selectHtml = `<option value="all" ${activeFilterCategory === 'all' ? 'selected' : ''}>All Vendor Types (${totalCount})</option>`;
        categoryMap.forEach((count, cat) => {
            const isSelected = activeFilterCategory.toLowerCase() === cat.toLowerCase();
            selectHtml += `<option value="${cat}" ${isSelected ? 'selected' : ''}>${cat} (${count})</option>`;
        });
        selectEl.innerHTML = selectHtml;
    }

    updatePillScrollControls();
}

/**
 * Scroll Filter Pills smoothly
 */
function scrollFilterPills(offset) {
    const container = document.getElementById('vendorFilterPillsContainer');
    if (container) {
        container.scrollBy({ left: offset, behavior: 'smooth' });
        setTimeout(updatePillScrollControls, 250);
    }
}

/**
 * Update scroll indicators for filter pills
 */
function updatePillScrollControls() {
    const container = document.getElementById('vendorFilterPillsContainer');
    const leftBtn = document.getElementById('pillScrollLeft');
    const rightBtn = document.getElementById('pillScrollRight');
    if (!container || !leftBtn || !rightBtn) return;

    const hasOverflow = container.scrollWidth > container.clientWidth + 4;
    if (!hasOverflow) {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
        return;
    }

    leftBtn.style.display = container.scrollLeft > 8 ? 'inline-flex' : 'none';
    rightBtn.style.display = (container.scrollLeft + container.clientWidth < container.scrollWidth - 8) ? 'inline-flex' : 'none';
}

/**
 * Initialize on DOM Load
 */
document.addEventListener('DOMContentLoaded', () => {
    loadFromLocalStorage();
    renderModularCategoryFilters();
    renderKanbanCards();
    populateItemMasterDropdown();
    updateDirectoryMetrics();

    // Auto-sync dispatch address when same-as-billing is checked
    const billingInput = document.getElementById('editBillingAddress');
    if (billingInput) {
        billingInput.addEventListener('input', function() {
            const sameCb = document.getElementById('sameAsBillingCheckbox');
            if (sameCb && sameCb.checked) {
                const dispatchInput = document.getElementById('editDispatchAddress');
                if (dispatchInput) dispatchInput.value = this.value;
            }
        });
    }

    const pillsContainer = document.getElementById('vendorFilterPillsContainer');
    if (pillsContainer) {
        pillsContainer.addEventListener('scroll', updatePillScrollControls);
    }
    window.addEventListener('resize', updatePillScrollControls);
});

/**
 * Persist / Load from LocalStorage (Upgraded to v6 schema with deep fallback)
 */
function saveToLocalStorage() {
    try {
        localStorage.setItem('rms_vendor_database_v6', JSON.stringify(VENDOR_DATABASE));
    } catch (e) {
        console.warn('LocalStorage error:', e);
    }
}

function loadFromLocalStorage() {
    try {
        loadCustomCategories();
        const cached = localStorage.getItem('rms_vendor_database_v6');
        if (cached) {
            const parsed = JSON.parse(cached);
            if (Array.isArray(parsed) && parsed.length > 0) {
                parsed.forEach(v => sanitizeVendor(v));
                VENDOR_DATABASE = parsed;
                return;
            }
        }
        // Fallback migration from older schemas
        const oldV5 = localStorage.getItem('rms_vendor_database_v5');
        if (oldV5) {
            const parsed = JSON.parse(oldV5);
            if (Array.isArray(parsed) && parsed.length > 0) {
                parsed.forEach(v => sanitizeVendor(v));
                VENDOR_DATABASE = parsed;
                saveToLocalStorage();
                return;
            }
        }
        VENDOR_DATABASE.forEach(v => sanitizeVendor(v));
    } catch (e) {
        console.warn('LocalStorage parse error:', e);
    }
}

function resetVendorDatabaseToDefaults() {
    if (confirm('Reset vendor directory to factory default seed data? Any unsaved edits will be refreshed.')) {
        localStorage.removeItem('rms_vendor_database_v6');
        localStorage.removeItem('rms_vendor_database_v5');
        localStorage.removeItem('rms_vendor_database_v4');
        localStorage.removeItem('rms_vendor_database_v3');
        VENDOR_DATABASE = JSON.parse(JSON.stringify(SEEDED_VENDORS));
        VENDOR_DATABASE.forEach(v => sanitizeVendor(v));
        saveToLocalStorage();
        activeFilterCategory = 'all';
        renderModularCategoryFilters();
        renderKanbanCards();
        if (document.getElementById('vendorProfileWrapper').style.display !== 'none') {
            hydrateVendorProfile(currentVendorId);
        }
        updateDirectoryMetrics();
        showToast('✓ Vendor directory reset to factory standards!', 'success');
    }
}

function updateDirectoryMetrics() {
    setText('totalSuppliersBadge', VENDOR_DATABASE.length);
    renderModularCategoryFilters();
}

/**
 * Populate Item Master Dropdown for Tab 3
 */
function populateItemMasterDropdown() {
    const select = document.getElementById('catItemMasterSelect');
    if (!select) return;

    select.innerHTML = `
        <option value="">-- Choose an Item Master Product to Link --</option>
        ${ITEM_MASTER_DICTIONARY.map(item => `
            <option value="${item.sku}">[${item.sku}] ${item.name} (${item.unit}) • Benchmark: ₱${formatMoney(item.defaultCost)}</option>
        `).join('')}
    `;
}

/**
 * Handle Item Master Selection in Catalog Modal
 */
function handleItemMasterSelectChange(sku) {
    const item = ITEM_MASTER_DICTIONARY.find(i => i.sku === sku);
    if (item) {
        document.getElementById('catName').value = item.name;
        document.getElementById('catRatioUnit').value = item.unit;
        if (!document.getElementById('catSku').value) {
            document.getElementById('catSku').value = `VND-${item.sku}`;
        }
        updateConversionPreview();
    }
}

/**
 * Render Kanban Grid of Vendors
 * Supports searching by supplier info AND mapped Catalog Item List (name, SKU, Item Master SKU, UOM)
 */
function renderKanbanCards(query = '') {
    const grid = document.getElementById('vendorKanbanGrid');
    if (!grid) return;

    const q = (query || '').trim().toLowerCase();
    const clearBtn = document.getElementById('kanbanSearchClearBtn');
    if (clearBtn) clearBtn.style.display = q ? 'flex' : 'none';

    const matches = [];

    VENDOR_DATABASE.forEach(v => {
        // 1. Category Filter Check
        const matchesCategory = (activeFilterCategory === 'all' || 
            (v.category && v.category.toLowerCase() === activeFilterCategory.toLowerCase()));
        if (!matchesCategory) return;

        if (!q) {
            matches.push({ vendor: v, matchedItems: [] });
            return;
        }

        // 2. Direct Vendor Fields Match
        const vendorFieldMatch =
            (v.legalName && v.legalName.toLowerCase().includes(q)) ||
            (v.tradeName && v.tradeName.toLowerCase().includes(q)) ||
            (v.location && v.location.toLowerCase().includes(q)) ||
            (v.email && v.email.toLowerCase().includes(q)) ||
            (v.code && v.code.toLowerCase().includes(q)) ||
            (v.tin && v.tin.toLowerCase().includes(q)) ||
            (v.category && v.category.toLowerCase().includes(q));

        // 3. Catalog Item List Match (Product Name, Vendor SKU, Item Master SKU, Purchased UOM)
        const matchedItems = (v.catalog || []).filter(item => {
            return (item.name && item.name.toLowerCase().includes(q)) ||
                   (item.sku && item.sku.toLowerCase().includes(q)) ||
                   (item.itemMasterSku && item.itemMasterSku.toLowerCase().includes(q)) ||
                   (item.uomPurchased && item.uomPurchased.toLowerCase().includes(q));
        });

        if (vendorFieldMatch || matchedItems.length > 0) {
            matches.push({ vendor: v, matchedItems });
        }
    });

    if (matches.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; padding: 48px 16px; text-align: center; color: var(--vendor-text-muted); background: #ffffff; border-radius: 14px; border: 1px dashed #cbd5e1;">
                <i class="ph ph-buildings" style="font-size: 40px; color: #cbd5e1;"></i>
                <h4 style="margin: 10px 0 4px 0; font-size: 0.95rem; color: var(--vendor-text-strong);">No suppliers match your search criteria</h4>
                <p style="font-size: 0.8rem; margin: 0;">Try adjusting your keyword (supplier name or item/SKU) or category filter.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = matches.map(({ vendor: v, matchedItems }) => {
        let leftIconHtml = '';
        if (v.avatarType === 'initial-box') {
            leftIconHtml = `
                <div class="kanban-initial-box" style="background: ${v.avatarBg || '#0284c7'};">
                    ${v.avatarText || v.legalName.charAt(0)}
                </div>
            `;
        } else if (v.avatarType === 'person') {
            leftIconHtml = `<i class="ph ph-user kanban-person-icon"></i>`;
        } else {
            leftIconHtml = `<i class="ph ph-buildings kanban-building-icon"></i>`;
        }

        const catalogCount = v.catalog?.length || 0;
        const leadDays = v.terms?.leadTimeDays ? `${v.terms.leadTimeDays} Days` : '3 Days';

        // Render Item List match highlight if searched term matched items
        let itemMatchSnippet = '';
        if (matchedItems && matchedItems.length > 0) {
            const displayItems = matchedItems.slice(0, 2).map(it => `${it.name} <span class="match-sku">(${it.itemMasterSku || it.sku})</span>`).join(', ');
            const moreBadge = matchedItems.length > 2 ? ` <span class="match-more">+${matchedItems.length - 2} more</span>` : '';
            itemMatchSnippet = `
                <div class="kanban-matched-item-badge" title="Supplies matching items: ${matchedItems.map(it => it.name).join(', ')}">
                    <i class="ph ph-package"></i>
                    <span>Item Match: <strong>${displayItems}</strong>${moreBadge}</span>
                </div>
            `;
        }

        return `
            <div class="vendor-kanban-card" onclick="openVendorProfile('${v.id}')" title="Click to view full vendor profile">
                <!-- Left Icon Column (matching screenshot) -->
                <div class="kanban-card-left">
                    ${leftIconHtml}
                </div>

                <!-- Right Card Details Body -->
                <div class="kanban-card-body">
                    <div>
                        <div class="kanban-vendor-name" title="${v.legalName}">${v.legalName}</div>
                        <div class="kanban-vendor-loc">${v.location || 'Metro Manila, Philippines'}</div>
                        <div class="kanban-vendor-email">${v.email || 'procurement@partner.com'}</div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                            ${v.category ? `<span class="kanban-tag-pill ${v.tagClass || 'tag-services'}">${v.category}</span>` : ''}
                        </div>
                        ${itemMatchSnippet}
                    </div>

                    <div class="kanban-bottom-row">
                        <div class="kanban-metrics-left">
                            <span class="kanban-metric-item" title="Item Master Mappings count">
                                <i class="ph ph-barcode"></i> ${catalogCount} SKUs
                            </span>
                            <span class="kanban-metric-item" title="Delivery Lead Time">
                                <i class="ph ph-clock"></i> ${leadDays}
                            </span>
                        </div>
                        <button type="button" class="kanban-view-action-btn" onclick="event.stopPropagation(); openVendorProfile('${v.id}')">
                            <span>View</span> <i class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Filter Kanban by Category (from Pill or Dropdown)
 */
function filterKanbanCategory(cat, btn) {
    activeFilterCategory = cat;

    // Sync Pills UI
    const container = document.getElementById('vendorFilterPillsContainer');
    if (container) {
        container.querySelectorAll('.vendor-pill-btn').forEach(el => el.classList.remove('active'));
        if (btn) {
            btn.classList.add('active');
            btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
            const target = (cat === 'all')
                ? document.getElementById('pill-all')
                : container.querySelector(`[data-category="${cat}"]`);
            if (target) {
                target.classList.add('active');
                target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }
    }

    // Sync Dropdown Select
    const selectEl = document.getElementById('vendorCategorySelect');
    if (selectEl) {
        selectEl.value = (cat === 'all') ? 'all' : cat;
    }

    const q = document.getElementById('kanbanSearchInput')?.value || '';
    renderKanbanCards(q);
}

/**
 * Handle category dropdown selection
 */
function handleCategorySelectChange(val) {
    filterKanbanCategory(val, null);
}

/**
 * Search Kanban Cards & Item Lists
 */
function handleKanbanSearch(val) {
    renderKanbanCards(val);
}

/**
 * Clear Kanban search input
 */
function clearKanbanSearch() {
    const input = document.getElementById('kanbanSearchInput');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('kanbanSearchClearBtn');
    if (clearBtn) clearBtn.style.display = 'none';
    renderKanbanCards('');
}

/**
 * Live search inside Vendor Profile's Catalog Tab
 */
function handleCatalogSearch(query) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    const q = (query || '').trim().toLowerCase();
    const catalog = vendor.catalog || [];

    if (!q) {
        renderCatalogTable(catalog);
        setText('catalogItemCountBadge', `${catalog.length} items mapped`);
        return;
    }

    const filtered = catalog.filter(item => {
        return (item.name && item.name.toLowerCase().includes(q)) ||
               (item.sku && item.sku.toLowerCase().includes(q)) ||
               (item.itemMasterSku && item.itemMasterSku.toLowerCase().includes(q)) ||
               (item.uomPurchased && item.uomPurchased.toLowerCase().includes(q)) ||
               (item.ratioUnit && item.ratioUnit.toLowerCase().includes(q));
    });

    renderCatalogTable(filtered);
    setText('catalogItemCountBadge', `Found ${filtered.length} of ${catalog.length} items`);
}

/**
 * Open Vendor Profile (Hides Kanban and Vendor Header Bar, Shows Profile Wrapper)
 */
function openVendorProfile(vendorId) {
    const resolvedVendor = VENDOR_DATABASE.find(v => v.id === vendorId || v.code === vendorId) || VENDOR_DATABASE[0];
    currentVendorId = resolvedVendor ? resolvedVendor.id : vendorId;
    hydrateVendorProfile(currentVendorId);

    const headerBar = document.getElementById('vendorHeaderBar') || document.querySelector('.vendor-header-bar');
    const kanbanSection = document.getElementById('vendorKanbanSection');
    const profileWrapper = document.getElementById('vendorProfileWrapper');

    if (headerBar) headerBar.style.display = 'none';
    if (kanbanSection) kanbanSection.style.display = 'none';
    if (profileWrapper) {
        profileWrapper.style.display = 'flex';
        switchVendorTab('tab-general');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

/**
 * Close Vendor Profile (Shows Kanban and Vendor Header Bar, Hides Profile Wrapper)
 */
function closeVendorProfile() {
    const headerBar = document.getElementById('vendorHeaderBar') || document.querySelector('.vendor-header-bar');
    const kanbanSection = document.getElementById('vendorKanbanSection');
    const profileWrapper = document.getElementById('vendorProfileWrapper');

    if (profileWrapper) profileWrapper.style.display = 'none';
    if (kanbanSection) {
        kanbanSection.style.display = 'flex';
        renderKanbanCards(document.getElementById('kanbanSearchInput')?.value || '');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    if (headerBar) headerBar.style.display = 'flex';
}

/**
 * Cycle between vendors (Next / Prev)
 */
function cycleVendor(direction) {
    const currentIndex = VENDOR_DATABASE.findIndex(v => v.id === currentVendorId || v.code === currentVendorId);
    if (currentIndex === -1) return;

    let nextIndex = currentIndex + direction;
    if (nextIndex < 0) nextIndex = VENDOR_DATABASE.length - 1;
    if (nextIndex >= VENDOR_DATABASE.length) nextIndex = 0;

    const nextVendor = VENDOR_DATABASE[nextIndex];
    if (nextVendor) {
        openVendorProfile(nextVendor.id);
    }
}

/**
 * Hydrate All 3 Tabs for the Selected Vendor
 */
function hydrateVendorProfile(vendorId) {
    const vendor = VENDOR_DATABASE.find(v => v.id === vendorId || v.code === vendorId) || VENDOR_DATABASE[0];
    if (!vendor) return;
    currentVendorId = vendor.id;
    sanitizeVendor(vendor);

    // Breadcrumb & Header Controls
    setText('breadcrumbVendorName', vendor.legalName);
    setText('navVendorCategoryBadge', vendor.category || 'General');
    updateVendorNavCounter();

    // Banner Monogram Logo
    const bannerLogo = document.getElementById('vendorBannerLogo');
    if (bannerLogo) {
        bannerLogo.innerHTML = `<span>${vendor.avatarText || vendor.legalName.charAt(0)}</span>`;
        bannerLogo.style.background = vendor.avatarBg || 'var(--vendor-primary-gradient)';
    }

    // Banner Text & Status
    setText('bannerVendorName', vendor.legalName);
    updateBannerStatusDisplay(vendor.status || 'Active');
    setText('bannerVendorDBA', vendor.tradeName || vendor.legalName);
    setText('bannerVendorCode', vendor.code);
    setText('bannerVendorTIN', vendor.tin || '—');
    setText('bannerVendorEntity', vendor.entityType || 'Corporation');

    // Primary Contact Details on Banner
    const primaryContact = (vendor.contacts && vendor.contacts.find(c => c.isDefault)) || (vendor.contacts && vendor.contacts[0]) || null;
    const contactPhone = primaryContact?.phone || vendor.phone || '+63 917 123 4567';
    const contactEmail = primaryContact?.email || vendor.email || 'contact@partner.com';
    setText('bannerVendorPhone', contactPhone);
    setText('bannerVendorEmail', contactEmail);
    setText('bannerVendorLocation', vendor.location || 'Metro Manila, Philippines');

    // Banner KPIs
    setText('bannerLeadTime', `${vendor.terms?.leadTimeDays || 2} Days`);
    setText('bannerPaymentTerms', vendor.terms?.payment || 'Net 30 Days');
    setText('bannerCatalogCount', `${vendor.catalog?.length || 0} SKUs`);

    const creditLim = vendor.terms?.creditLimit !== undefined ? Number(vendor.terms.creditLimit) : 100000;
    setText('bannerCreditLimit', creditLim >= 1000 ? `₱ ${(creditLim / 1000).toLocaleString()}k` : `₱ ${creditLim.toLocaleString()}`);

    // ==========================================
    // TAB 1: GENERAL INFO FORM INPUTS
    // ==========================================
    // 1. Identity
    setVal('editVendorCode', vendor.code);
    setVal('editLegalName', vendor.legalName);
    setVal('editTradeName', vendor.tradeName);

    const editCatSelect = document.getElementById('editCategory');
    if (editCatSelect) {
        const categoryMap = getActiveVendorCategories();
        let opts = '';
        categoryMap.forEach((_, c) => {
            opts += `<option value="${c}">${c}</option>`;
        });
        if (vendor.category && !categoryMap.has(vendor.category)) {
            opts = `<option value="${vendor.category}">${vendor.category}</option>` + opts;
        }
        editCatSelect.innerHTML = opts;
        editCatSelect.value = vendor.category || 'Dairy & Cold Storage';
    }

    const currentStatus = vendor.status || 'Active';
    const statusRadio = document.querySelector(`input[name="vendorStatus"][value="${currentStatus}"]`) || document.querySelector('input[name="vendorStatus"][value="Active"]');
    if (statusRadio) statusRadio.checked = true;

    // 2. Tax & Legal Registration
    setVal('editTIN', vendor.tin);
    setVal('editVatType', vendor.vatType || 'VAT Registered (12%)');
    setVal('editEwtRate', vendor.ewtRate || '1% (Purchase of Goods)');
    setVal('editEntityType', vendor.entityType || 'Corporation');

    // 3. Addresses & Locations
    setVal('editBillingAddress', vendor.address || '');
    setVal('editDispatchAddress', vendor.dispatchAddress || vendor.address || '');
    const sameCheckbox = document.getElementById('sameAsBillingCheckbox');
    if (sameCheckbox) {
        const isSame = vendor.sameAsBilling !== false && (vendor.dispatchAddress === vendor.address || !vendor.dispatchAddress);
        sameCheckbox.checked = isSame;
        toggleSameAsBilling(isSame);
    }

    // 4. Contact Directory
    renderContactsGrid(vendor.contacts || []);

    // ==========================================
    // TAB 2: PURCHASING & LOGISTICS INPUTS
    // ==========================================
    const t = vendor.terms || {};
    // 1. Payment & Credit Terms
    setVal('editPaymentTerms', t.payment || 'Net 30 Days');
    setVal('editCreditLimit', t.creditLimit !== undefined ? t.creditLimit : 100000);
    setVal('editPaymentMethod', t.paymentMethod || 'Bank Transfer (ACH)');

    // 2. Banking & Payout Details
    setVal('editBankName', t.bankName || 'BDO Unibank');
    setVal('editBankAccountName', t.accountName || vendor.legalName);
    setVal('editBankAccountNumber', t.accountNumber || '0012-3456-7890');
    setVal('editBankBranch', t.bankBranch || 'Pasig-Ortigas Branch');

    // 3. Logistics, Lead Time & Constraints
    setVal('editLeadTimeDays', t.leadTimeDays !== undefined ? t.leadTimeDays : 2);
    setVal('editMOV', t.mov !== undefined ? t.mov : 5000);
    setVal('editMOQ', t.moq || '5 Cases');
    setVal('editFreeDeliveryThreshold', t.freeDeliveryThreshold !== undefined ? t.freeDeliveryThreshold : 8000);

    const daysList = t.deliveryDaysList || ['Mon', 'Tue', 'Thu', 'Fri'];
    document.querySelectorAll('input[name="deliveryDays"]').forEach(cb => {
        cb.checked = daysList.includes(cb.value);
    });

    setVal('editOrderCutoffTime', t.cutoff || '02:00 PM');
    setVal('editFulfillmentType', t.fulfillmentType || 'Supplier Delivery');
    setVal('editReturnPolicy', t.returnPolicy || 'Credit Memo on next bill');

    // ==========================================
    // TAB 3: CATALOG & PRICE LIST (CONNECTED TO ITEM MASTER)
    // ==========================================
    const catSearchInput = document.getElementById('catalogSearchInput');
    if (catSearchInput) {
        catSearchInput.value = '';
    }
    renderCatalogTable(vendor.catalog || []);
    setText('tabCatalogCount', vendor.catalog?.length || 0);
    setText('catalogItemCountBadge', `${vendor.catalog?.length || 0} items mapped`);
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val !== undefined && val !== null ? val : '';
}

/**
 * SAVE TAB 1: General Info & Corporate Registration
 */
function handleSaveGeneralInfo(event) {
    if (event) event.preventDefault();
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    vendor.code = document.getElementById('editVendorCode').value;
    vendor.legalName = document.getElementById('editLegalName').value;
    vendor.tradeName = document.getElementById('editTradeName').value;
    vendor.category = document.getElementById('editCategory').value;
    vendor.tagClass = `tag-${vendor.category.toLowerCase().replace(/[^a-z0-9]/g, '-')}`;

    const selectedStatus = document.querySelector('input[name="vendorStatus"]:checked');
    vendor.status = selectedStatus ? selectedStatus.value : 'Active';

    vendor.tin = document.getElementById('editTIN').value;
    vendor.vatType = document.getElementById('editVatType').value;
    vendor.ewtRate = document.getElementById('editEwtRate').value;
    vendor.entityType = document.getElementById('editEntityType').value;

    vendor.address = document.getElementById('editBillingAddress').value;
    vendor.sameAsBilling = document.getElementById('sameAsBillingCheckbox')?.checked ?? false;
    vendor.dispatchAddress = vendor.sameAsBilling ? vendor.address : document.getElementById('editDispatchAddress').value;

    saveToLocalStorage();
    hydrateVendorProfile(currentVendorId);
    renderModularCategoryFilters();
    renderKanbanCards(document.getElementById('kanbanSearchInput')?.value || '');
    updateDirectoryMetrics();

    // Visual delta confirmation
    const formCard = document.querySelector('#pane-tab-general .vendor-content-card');
    if (formCard) {
        formCard.classList.add('flash-updated');
        setTimeout(() => formCard.classList.remove('flash-updated'), 1200);
    }

    showToast(`✓ General Info updated successfully for ${vendor.legalName}!`, 'success');
}

/**
 * SAVE TAB 2: Purchasing & Logistics Terms
 */
function handleSavePurchasingTerms(event) {
    if (event) event.preventDefault();
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    if (!vendor.terms) vendor.terms = {};

    vendor.terms.payment = document.getElementById('editPaymentTerms').value;
    vendor.terms.creditLimit = parseFloat(document.getElementById('editCreditLimit').value) || 0;
    vendor.terms.paymentMethod = document.getElementById('editPaymentMethod').value;

    vendor.terms.bankName = document.getElementById('editBankName').value;
    vendor.terms.accountName = document.getElementById('editBankAccountName').value;
    vendor.terms.accountNumber = document.getElementById('editBankAccountNumber').value;
    vendor.terms.bankBranch = document.getElementById('editBankBranch').value;

    vendor.terms.leadTimeDays = parseInt(document.getElementById('editLeadTimeDays').value, 10) || 2;
    vendor.terms.mov = parseFloat(document.getElementById('editMOV').value) || 0;
    vendor.terms.moq = document.getElementById('editMOQ').value;
    vendor.terms.freeDeliveryThreshold = parseFloat(document.getElementById('editFreeDeliveryThreshold').value) || 0;

    const selectedDays = [];
    document.querySelectorAll('input[name="deliveryDays"]:checked').forEach(cb => {
        selectedDays.push(cb.value);
    });
    vendor.terms.deliveryDaysList = selectedDays;
    vendor.terms.deliveryDays = selectedDays.join(', ');

    vendor.terms.cutoff = document.getElementById('editOrderCutoffTime').value;
    vendor.terms.fulfillmentType = document.getElementById('editFulfillmentType').value;
    vendor.terms.returnPolicy = document.getElementById('editReturnPolicy').value;

    saveToLocalStorage();
    hydrateVendorProfile(currentVendorId);
    renderKanbanCards(document.getElementById('kanbanSearchInput')?.value || '');
    updateDirectoryMetrics();

    // Visual delta confirmation
    const termsCards = document.querySelectorAll('#pane-tab-terms .vendor-content-card');
    termsCards.forEach(card => {
        card.classList.add('flash-updated');
        setTimeout(() => card.classList.remove('flash-updated'), 1200);
    });

    showToast(`✓ Purchasing & Logistics terms saved for ${vendor.legalName}!`, 'success');
}

/**
 * Render Tab 1 Contacts Table with Primary Contact selector, Edit & Delete actions
 */
function renderContactsGrid(contacts) {
    const tbody = document.getElementById('contactsTableBody');
    if (!tbody) return;

    if (!contacts || contacts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 24px; text-align: center; color: var(--vendor-text-muted);">
                    <i class="ph ph-user-plus" style="font-size: 24px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                    No contact persons registered yet. Click "+ Add Another Contact Person" to add sales, AP, or dispatch contacts.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = contacts.map((c, index) => {
        const isDefault = !!c.isDefault;
        const defaultBadge = isDefault
            ? `<button type="button" class="vendor-default-contact-btn is-default" onclick="setDefaultContact(${index})" title="Default Primary Contact"><i class="ph ph-check-square"></i> <span>Default</span></button>`
            : `<button type="button" class="vendor-default-contact-btn" onclick="setDefaultContact(${index})" title="Click to set as Primary Default"><i class="ph ph-square"></i> <span>Set Default</span></button>`;

        return `
            <tr style="border-bottom: 1px solid var(--vendor-border-subtle); transition: background 0.15s ease;">
                <td style="padding: 12px 14px; font-weight: 700; color: var(--vendor-text-strong);">
                    ${c.name}
                    ${isDefault ? '<span style="margin-left: 6px; font-size: 0.68rem; font-weight: 800; background: #ecfdf5; color: #15803d; border: 1px solid #86efac; border-radius: 4px; padding: 2px 6px;">PRIMARY</span>' : ''}
                </td>
                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--vendor-text-medium);">
                    ${c.title || c.roleLabel || c.role || 'Sales Account Mgr'}
                </td>
                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--vendor-text-medium);">
                    ${c.phone ? `
                        <div style="display:inline-flex; align-items:center; gap:6px;">
                            <a href="tel:${c.phone.replace(/[^0-9+]/g, '')}" style="color:var(--vendor-primary); text-decoration:none; font-weight:600;" title="Call ${c.name}">
                                <i class="ph ph-phone"></i> ${c.phone}
                            </a>
                            <button type="button" class="vendor-btn-secondary" style="padding:2px 5px; font-size:0.68rem;" onclick="copyToClipboard('${c.phone}', 'Phone')" title="Copy Phone">
                                <i class="ph ph-copy"></i>
                            </button>
                        </div>
                    ` : '—'}
                </td>
                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--vendor-text-medium);">
                    ${c.email ? `
                        <div style="display:inline-flex; align-items:center; gap:6px;">
                            <a href="mailto:${c.email}" style="color:var(--vendor-teal); text-decoration:none; font-weight:600;" title="Email ${c.name}">
                                <i class="ph ph-envelope-simple"></i> ${c.email}
                            </a>
                            <button type="button" class="vendor-btn-secondary" style="padding:2px 5px; font-size:0.68rem;" onclick="copyToClipboard('${c.email}', 'Email')" title="Copy Email">
                                <i class="ph ph-copy"></i>
                            </button>
                        </div>
                    ` : '—'}
                </td>
                <td style="padding: 12px 14px; text-align: center;">
                    ${defaultBadge}
                </td>
                <td style="padding: 12px 14px; text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 4px;">
                        ${c.phone ? `
                            <a href="tel:${c.phone.replace(/[^0-9+]/g, '')}" class="vendor-btn-secondary" style="padding: 4px 7px; font-size: 0.74rem; text-decoration:none;" title="Direct Call">
                                <i class="ph ph-phone-call" style="color:var(--vendor-primary);"></i>
                            </a>
                        ` : ''}
                        <button type="button" class="vendor-btn-secondary" style="padding: 4px 8px; font-size: 0.74rem;" onclick="event.preventDefault(); event.stopPropagation(); openEditContactModal(${index})" title="Edit Contact">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="vendor-btn-danger-subtle" style="padding: 4px 8px; font-size: 0.74rem;" onclick="event.preventDefault(); event.stopPropagation(); deleteContact(${index})" title="Remove Contact">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Render Tab 3 Catalog Table (Connected to Item Master)
 */
function renderCatalogTable(catalog) {
    const tbody = document.getElementById('catalogTableBody');
    if (!tbody) return;

    if (!catalog || catalog.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: var(--vendor-text-muted);">
                    <i class="ph ph-barcode" style="font-size: 36px; color: #cbd5e1;"></i>
                    <h4 style="margin: 8px 0 4px 0; font-size: 0.95rem; color: var(--vendor-text-strong);">No mapped catalog items yet</h4>
                    <p style="margin: 0; font-size: 0.82rem;">Click "Map Item from Item Master" to connect supplier packaging to kitchen recipe stock units.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = catalog.map((item, index) => {
        const unitCost = (item.ratioQty > 0) ? (item.cost / item.ratioQty) : 0;
        const imItem = ITEM_MASTER_DICTIONARY.find(i => i.sku === item.itemMasterSku);

        return `
            <tr>
                <td><strong style="color: var(--vendor-text-strong); font-family: monospace;">${item.sku}</strong></td>
                <td>
                    <div style="font-weight: 700; color: var(--vendor-text-strong);">${item.name}</div>
                    <span style="font-size: 0.72rem; color: var(--vendor-primary); font-weight: 600;">
                        <i class="ph ph-link-bold"></i> Item Master: [${item.itemMasterSku || 'RAW'}]
                    </span>
                </td>
                <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.78rem;">${item.uomPurchased}</span></td>
                <td>
                    <span class="conversion-pill">
                        <i class="ph ph-arrows-left-right"></i>
                        1 ${extractUomPrefix(item.uomPurchased)} = ${item.ratioQty} ${item.ratioUnit}
                    </span>
                </td>
                <td><strong>₱ ${formatMoney(item.cost)}</strong> <span style="font-size: 0.72rem; color: var(--vendor-text-muted);">/ ${extractUomPrefix(item.uomPurchased)}</span></td>
                <td>
                    <div class="vendor-calc-unit-rate">₱ ${formatMoney(unitCost)}</div>
                    <span class="vendor-calc-unit-sub">per ${item.ratioUnit}</span>
                </td>
                <td>
                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                        <i class="ph ph-check-circle"></i> Synced (₱ ${formatMoney(unitCost)}/${item.ratioUnit})
                    </span>
                </td>
                <td><span style="color: var(--vendor-text-muted); font-size: 0.76rem;">${item.validity || 'Valid'}</span></td>
                <td style="text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                        <button type="button" class="vendor-btn-secondary" style="padding: 4px 8px; font-size: 0.72rem;" onclick="syncSingleItemToItemMaster('${item.itemMasterSku}', ${unitCost}, '${item.name}')" title="Sync this cost to Item Master">
                            <i class="ph ph-arrows-clockwise" style="color: var(--vendor-primary);"></i> Sync
                        </button>
                        <button type="button" class="vendor-btn-secondary" style="padding: 4px 8px; font-size: 0.72rem;" onclick="openEditCatalogModal(${index})" title="Edit Mapping">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="vendor-btn-danger-subtle" style="padding: 4px 8px; font-size: 0.72rem;" onclick="deleteCatalogItem(${index})" title="Remove Mapping">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function extractUomPrefix(uomStr) {
    if (!uomStr) return 'Unit';
    const match = uomStr.match(/^([a-zA-Z]+)/);
    return match ? match[1] : 'Unit';
}

/**
 * Sync Item to Item Master (Pricing & Suppliers)
 * Updates both the in-memory dictionary AND localStorage ('rms_inventory_products')
 */
function syncSingleItemToItemMaster(imSku, unitCost, itemName) {
    const im = ITEM_MASTER_DICTIONARY.find(i => i.sku === imSku);
    if (im) {
        im.defaultCost = unitCost;
    }

    const currentVendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);

    // Deep sync to Item Master local storage if present
    try {
        const rawProducts = localStorage.getItem('rms_inventory_products');
        if (rawProducts) {
            const products = JSON.parse(rawProducts);
            if (Array.isArray(products)) {
                const targetProduct = products.find(p => p.sku === imSku);
                if (targetProduct) {
                    targetProduct.costPrice = unitCost;
                    if (currentVendor) targetProduct.supplier = currentVendor.legalName;
                    localStorage.setItem('rms_inventory_products', JSON.stringify(products));
                }
            }
        }
    } catch (e) {
        console.warn('Item Master sync error:', e);
    }

    showToast(`✓ Item Master Updated: [${imSku}] ${itemName} cost benchmark synced to ₱ ${formatMoney(unitCost)}!`, 'success');
}

function syncAllCatalogPricesToItemMaster() {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor || !vendor.catalog || vendor.catalog.length === 0) {
        showToast('No catalog items available to sync.', 'info');
        return;
    }

    let count = 0;
    try {
        const rawProducts = localStorage.getItem('rms_inventory_products');
        const products = rawProducts ? JSON.parse(rawProducts) : null;

        vendor.catalog.forEach(cat => {
            const unitCost = cat.ratioQty > 0 ? (cat.cost / cat.ratioQty) : 0;
            const im = ITEM_MASTER_DICTIONARY.find(i => i.sku === cat.itemMasterSku);
            if (im) {
                im.defaultCost = unitCost;
                count++;
            }
            if (products && Array.isArray(products)) {
                const targetProduct = products.find(p => p.sku === cat.itemMasterSku);
                if (targetProduct) {
                    targetProduct.costPrice = unitCost;
                    targetProduct.supplier = vendor.legalName;
                }
            }
        });

        if (products && Array.isArray(products)) {
            localStorage.setItem('rms_inventory_products', JSON.stringify(products));
        }
    } catch (e) {
        console.warn('Item Master sync all error:', e);
    }

    renderCatalogTable(vendor.catalog);
    showToast(`✓ Success: ${count} catalog items synchronized to Item Master / Pricing & Suppliers!`, 'success');
}

/**
 * Tab Switching Controller (3 Active Tabs)
 */
function switchVendorTab(tabId) {
    activeTabId = tabId;
    document.querySelectorAll('.vendor-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });
    document.querySelectorAll('.vendor-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === `pane-${tabId}`);
    });
}

/**
 * Modal Handling
 */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('active');
        el.style.display = 'flex';
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('active');
        el.style.display = 'none';
    }
}

// Close on ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.vendor-modal-overlay.active').forEach(m => {
            m.classList.remove('active');
            m.style.display = 'none';
        });
    }
});

/**
 * Add / Edit Vendor Catalog Item Flow
 */
function openAddCatalogModal() {
    document.getElementById('catalogModalTitle').textContent = 'Map Item Master SKU & Conversion';
    document.getElementById('catEditIndex').value = '-1';
    document.getElementById('catItemMasterSelect').value = '';
    document.getElementById('catSku').value = '';
    document.getElementById('catName').value = '';
    const catUomEl = document.getElementById('catUom');
    if (catUomEl) {
        catUomEl.value = 'CS';
        catUomEl.dispatchEvent(new Event('change'));
    }
    document.getElementById('catCost').value = '1020.00';
    document.getElementById('catRatioQty').value = '12';
    document.getElementById('catRatioUnit').value = 'Liter';
    updateConversionPreview();
    openModal('addCatalogModal');
}

function openEditCatalogModal(index) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor || !vendor.catalog || !vendor.catalog[index]) return;

    const item = vendor.catalog[index];
    document.getElementById('catalogModalTitle').textContent = 'Edit Item Master Mapping';
    document.getElementById('catEditIndex').value = index;
    document.getElementById('catItemMasterSelect').value = item.itemMasterSku || '';
    document.getElementById('catSku').value = item.sku;
    document.getElementById('catName').value = item.name;
    const catUomEl = document.getElementById('catUom');
    if (catUomEl) {
        catUomEl.value = item.uomPurchased || 'CS';
        catUomEl.dispatchEvent(new Event('change'));
    }
    document.getElementById('catCost').value = item.cost;
    document.getElementById('catRatioQty').value = item.ratioQty;
    document.getElementById('catRatioUnit').value = item.ratioUnit;
    document.getElementById('catValidity').value = item.validity || 'Valid until Dec 2026';
    updateConversionPreview();
    openModal('addCatalogModal');
}

function updateConversionPreview() {
    const uom = document.getElementById('catUom')?.value || 'Case';
    const cost = parseFloat(document.getElementById('catCost')?.value) || 0;
    const qty = parseFloat(document.getElementById('catRatioQty')?.value) || 1;
    const unit = document.getElementById('catRatioUnit')?.value || 'Liter';

    const formulaEl = document.getElementById('conversionFormulaText');
    const resultEl = document.getElementById('conversionResultText');

    const unitCost = qty > 0 ? (cost / qty) : 0;
    if (formulaEl) formulaEl.textContent = `1 ${extractUomPrefix(uom)} = ${qty} ${unit}`;
    if (resultEl) resultEl.textContent = `Unit: ₱ ${formatMoney(unitCost)} / ${unit}`;
}

function handleSaveCatalogItem(event) {
    if (event) event.preventDefault();
    const editIndex = parseInt(document.getElementById('catEditIndex').value, 10);
    const itemMasterSku = document.getElementById('catItemMasterSelect').value;
    const sku = document.getElementById('catSku').value;
    const name = document.getElementById('catName').value;
    const uom = document.getElementById('catUom').value;
    const cost = parseFloat(document.getElementById('catCost').value) || 0;
    const qty = parseFloat(document.getElementById('catRatioQty').value) || 1;
    const unit = document.getElementById('catRatioUnit').value;
    const validity = document.getElementById('catValidity').value;

    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    if (!vendor.catalog) vendor.catalog = [];

    const catalogData = {
        itemMasterSku: itemMasterSku || 'RAW-301',
        sku,
        name,
        uomPurchased: uom,
        ratioQty: qty,
        ratioUnit: unit,
        cost,
        validity,
        syncedWithItemMaster: true
    };

    if (editIndex >= 0 && editIndex < vendor.catalog.length) {
        vendor.catalog[editIndex] = catalogData;
        showToast(`✓ Mapping updated for SKU ${sku}!`, 'success');
    } else {
        vendor.catalog.unshift(catalogData);
        showToast(`✓ SKU ${sku} mapped to Item Master [${itemMasterSku}]!`, 'success');
    }

    saveToLocalStorage();
    renderCatalogTable(vendor.catalog);
    setText('tabCatalogCount', vendor.catalog.length);
    setText('bannerCatalogCount', `${vendor.catalog.length} SKUs`);
    updateDirectoryMetrics();
    closeModal('addCatalogModal');
}

function deleteCatalogItem(index) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor || !vendor.catalog || !vendor.catalog[index]) return;

    if (confirm(`Remove mapping for SKU ${vendor.catalog[index].sku}?`)) {
        vendor.catalog.splice(index, 1);
        saveToLocalStorage();
        renderCatalogTable(vendor.catalog);
        setText('tabCatalogCount', vendor.catalog.length);
        setText('bannerCatalogCount', `${vendor.catalog.length} SKUs`);
        updateDirectoryMetrics();
        showToast('Mapping removed.', 'info');
    }
}

/**
 * Add / Edit Contact Flow
 */
function openAddContactModal() {
    document.getElementById('contactModalTitle').textContent = 'Add Contact Person';
    document.getElementById('contactEditIndex').value = '-1';
    document.getElementById('contactName').value = '';
    document.getElementById('contactRole').value = 'sales';
    document.getElementById('contactPhone').value = '';
    document.getElementById('contactEmail').value = '';
    document.getElementById('contactTitle').value = '';
    document.getElementById('contactNotes').value = '';
    const defCb = document.getElementById('contactIsDefault');
    if (defCb) defCb.checked = false;
    openModal('addContactModal');
}

function openEditContactModal(index) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId || v.code === currentVendorId);
    if (!vendor || !vendor.contacts || !vendor.contacts[index]) {
        console.warn('openEditContactModal: Contact not found for index', index, 'vendor', currentVendorId);
        showToast('Error: Contact person record not found.', 'warning');
        return;
    }

    const c = vendor.contacts[index];
    document.getElementById('contactModalTitle').textContent = 'Edit Contact Person';
    document.getElementById('contactEditIndex').value = index;
    document.getElementById('contactName').value = c.name || '';
    
    // Set role dropdown properly (match existing option or append custom role)
    const roleSelect = document.getElementById('contactRole');
    if (roleSelect) {
        let matched = false;
        const targetRole = (c.role || '').toLowerCase();
        for (let i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].value.toLowerCase() === targetRole) {
                roleSelect.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched && c.role) {
            const opt = document.createElement('option');
            opt.value = c.role;
            opt.textContent = c.roleLabel || c.title || c.role;
            roleSelect.appendChild(opt);
            roleSelect.value = c.role;
        }
    }

    document.getElementById('contactPhone').value = c.phone || '';
    document.getElementById('contactEmail').value = c.email || '';
    document.getElementById('contactTitle').value = c.title || '';
    document.getElementById('contactNotes').value = c.notes || '';
    const defCb = document.getElementById('contactIsDefault');
    if (defCb) defCb.checked = !!c.isDefault;
    openModal('addContactModal');
}

function handleSaveContact(event) {
    if (event) event.preventDefault();
    const editIndex = parseInt(document.getElementById('contactEditIndex').value, 10);
    const name = document.getElementById('contactName').value;
    const role = document.getElementById('contactRole').value;
    const phone = document.getElementById('contactPhone').value;
    const email = document.getElementById('contactEmail').value;
    const title = document.getElementById('contactTitle').value || 'Sales Account Mgr';
    const notes = document.getElementById('contactNotes').value;
    const isDefault = document.getElementById('contactIsDefault')?.checked || false;

    let roleLabel = "Sales Account Mgr";
    let roleClass = "role-sales";
    if (role === 'billing') {
        roleLabel = "Billing & AP";
        roleClass = "role-billing";
    } else if (role === 'dispatch') {
        roleLabel = "Warehouse & Dispatch";
        roleClass = "role-dispatch";
    }

    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    if (!vendor.contacts) vendor.contacts = [];

    const contactData = {
        role,
        roleLabel,
        roleClass,
        name,
        title,
        phone,
        email,
        notes,
        isDefault
    };

    if (isDefault) {
        vendor.contacts.forEach(c => c.isDefault = false);
    }

    if (editIndex >= 0 && editIndex < vendor.contacts.length) {
        vendor.contacts[editIndex] = contactData;
        showToast(`✓ Contact ${name} updated!`, 'success');
    } else {
        if (vendor.contacts.length === 0) contactData.isDefault = true;
        vendor.contacts.push(contactData);
        showToast(`✓ Contact ${name} registered!`, 'success');
    }

    saveToLocalStorage();
    renderContactsGrid(vendor.contacts);
    closeModal('addContactModal');
}

function deleteContact(index) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor || !vendor.contacts || !vendor.contacts[index]) return;

    if (confirm(`Remove contact ${vendor.contacts[index].name}?`)) {
        vendor.contacts.splice(index, 1);
        if (vendor.contacts.length > 0 && !vendor.contacts.some(c => c.isDefault)) {
            vendor.contacts[0].isDefault = true;
        }
        saveToLocalStorage();
        renderContactsGrid(vendor.contacts);
        showToast('Contact removed.', 'info');
    }
}

/**
 * Set a specific contact as Primary Default
 */
function setDefaultContact(index) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor || !vendor.contacts || !vendor.contacts[index]) return;

    vendor.contacts.forEach((c, idx) => {
        c.isDefault = (idx === index);
    });

    saveToLocalStorage();
    renderContactsGrid(vendor.contacts);
    showToast(`✓ ${vendor.contacts[index].name} set as primary contact!`, 'success');
}

/**
 * Toggle Dispatch / Warehouse address same as billing
 */
function toggleSameAsBilling(isSame) {
    const dispatchEl = document.getElementById('editDispatchAddress');
    const billingEl = document.getElementById('editBillingAddress');
    if (!dispatchEl) return;
    if (isSame) {
        if (billingEl) dispatchEl.value = billingEl.value;
        dispatchEl.setAttribute('readonly', 'readonly');
        dispatchEl.style.backgroundColor = '#f8fafc';
    } else {
        dispatchEl.removeAttribute('readonly');
        dispatchEl.style.backgroundColor = '#ffffff';
    }
}

/**
 * Category Management Modal Operations
 */
function openCategoryManagerModal() {
    renderCategoryManagerList();
    openModal('categoryManagerModal');
    setTimeout(() => {
        const input = document.getElementById('newCategoryInput');
        if (input) input.focus();
    }, 100);
}

function renderCategoryManagerList() {
    const listEl = document.getElementById('categoryManagerList');
    const badgeEl = document.getElementById('categoryCountBadge');
    if (!listEl) return;

    const categoryMap = getActiveVendorCategories();
    if (badgeEl) badgeEl.textContent = categoryMap.size;

    const currentSelected = document.getElementById('editCategory')?.value || '';

    let html = '';
    categoryMap.forEach((count, catName) => {
        const isSelected = catName.toLowerCase() === currentSelected.toLowerCase();
        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: ${isSelected ? '#f0fdf4' : '#ffffff'}; border: 1px solid ${isSelected ? '#86efac' : '#e2e8f0'}; border-radius: 8px; transition: all 0.15s ease;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${isSelected ? '#10b981' : '#0284c7'};"></span>
                    <strong style="font-size: 0.82rem; color: var(--vendor-text-strong);">${catName}</strong>
                    <span style="font-size: 0.7rem; color: var(--vendor-text-muted); background: #f1f5f9; padding: 1px 6px; border-radius: 10px;">${count} vendor${count === 1 ? '' : 's'}</span>
                    ${isSelected ? '<span style="font-size: 0.68rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 1px 5px; border-radius: 4px;">CURRENT</span>' : ''}
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <button type="button" class="vendor-btn-secondary" style="padding: 3px 8px; font-size: 0.72rem;" onclick="selectCategoryFromModal('${catName.replace(/'/g, "\\'")}')" title="Apply this category to current vendor">
                        <i class="ph ph-check"></i> Apply
                    </button>
                    <button type="button" class="vendor-btn-secondary" style="padding: 3px 6px; font-size: 0.72rem;" onclick="renameCategoryFromModal('${catName.replace(/'/g, "\\'")}')" title="Rename category">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                    ${count === 0 ? `
                        <button type="button" class="vendor-btn-danger-subtle" style="padding: 3px 6px; font-size: 0.72rem;" onclick="deleteCategoryFromModal('${catName.replace(/'/g, "\\'")}')" title="Remove unused category">
                            <i class="ph ph-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    });

    listEl.innerHTML = html;
}

function addNewCategoryFromModal() {
    const input = document.getElementById('newCategoryInput');
    if (!input) return;
    const cat = input.value.trim();
    if (!cat) {
        showToast('Please enter a valid category name.', 'warning');
        return;
    }

    const categoryMap = getActiveVendorCategories();
    let exists = false;
    categoryMap.forEach((_, existing) => {
        if (existing.toLowerCase() === cat.toLowerCase()) exists = true;
    });

    if (exists) {
        showToast(`Category "${cat}" already exists in the taxonomy.`, 'info');
    } else {
        if (!CUSTOM_VENDOR_CATEGORIES.includes(cat)) {
            CUSTOM_VENDOR_CATEGORIES.push(cat);
            saveCustomCategories();
        }
        showToast(`✓ Category "${cat}" created!`, 'success');
    }

    input.value = '';
    refreshAllCategoryDropdowns(cat);
    renderCategoryManagerList();
    renderModularCategoryFilters();
}

function selectCategoryFromModal(catName) {
    const select = document.getElementById('editCategory');
    if (select) {
        select.value = catName;
    }
    closeModal('categoryManagerModal');
    showToast(`✓ Category set to "${catName}"`, 'info');
}

function renameCategoryFromModal(oldName) {
    const newName = prompt(`Enter new name for category "${oldName}":`, oldName);
    if (!newName || !newName.trim() || newName.trim() === oldName) return;
    const trimmed = newName.trim();

    // Update in CUSTOM_VENDOR_CATEGORIES
    const customIdx = CUSTOM_VENDOR_CATEGORIES.indexOf(oldName);
    if (customIdx !== -1) {
        CUSTOM_VENDOR_CATEGORIES[customIdx] = trimmed;
    } else {
        CUSTOM_VENDOR_CATEGORIES.push(trimmed);
    }
    saveCustomCategories();

    // Update vendors using oldName
    let updatedVendors = 0;
    VENDOR_DATABASE.forEach(v => {
        if (v.category === oldName) {
            v.category = trimmed;
            updatedVendors++;
        }
    });
    if (updatedVendors > 0) {
        saveToLocalStorage();
    }

    // Update current vendor profile banner badge if currently viewing
    const currentVendor = VENDOR_DATABASE.find(v => v.id === currentVendorId || v.code === currentVendorId);
    if (currentVendor && currentVendor.category === trimmed) {
        setText('navVendorCategoryBadge', trimmed);
    }

    refreshAllCategoryDropdowns(trimmed);
    renderCategoryManagerList();
    renderModularCategoryFilters();
    renderKanbanCards(document.getElementById('kanbanSearchInput')?.value || '');
    showToast(`✓ Category renamed to "${trimmed}" (${updatedVendors} vendors updated)`, 'success');
}

function deleteCategoryFromModal(catName) {
    const categoryMap = getActiveVendorCategories();
    const count = categoryMap.get(catName) || 0;
    if (count > 0) {
        showToast(`Cannot delete category "${catName}" because ${count} vendor(s) are assigned to it.`, 'warning');
        return;
    }

    CUSTOM_VENDOR_CATEGORIES = CUSTOM_VENDOR_CATEGORIES.filter(c => c !== catName);
    saveCustomCategories();
    refreshAllCategoryDropdowns();
    renderCategoryManagerList();
    renderModularCategoryFilters();
    showToast(`Category "${catName}" removed.`, 'info');
}

function refreshAllCategoryDropdowns(selectedCategory = null) {
    const categoryMap = getActiveVendorCategories();
    
    // 1. Tab 1 editCategory
    const editSelect = document.getElementById('editCategory');
    if (editSelect) {
        const curVal = selectedCategory || editSelect.value;
        let opts = '';
        categoryMap.forEach((_, c) => {
            opts += `<option value="${c}">${c}</option>`;
        });
        editSelect.innerHTML = opts;
        if (curVal) editSelect.value = curVal;
    }

    // 2. Add Vendor modal category select
    const newSelect = document.getElementById('newVendorCategory');
    if (newSelect) {
        const curVal = selectedCategory || newSelect.value;
        let opts = '';
        categoryMap.forEach((_, c) => {
            opts += `<option value="${c}">${c}</option>`;
        });
        newSelect.innerHTML = opts;
        if (curVal) newSelect.value = curVal;
    }
}

function promptAddNewCategoryFromProfile() {
    openCategoryManagerModal();
}

/**
 * Update Supplier Position Indicator in Navigation Bar
 */
function updateVendorNavCounter() {
    const el = document.getElementById('vendorNavCounter');
    if (!el) return;
    const currentIndex = VENDOR_DATABASE.findIndex(v => v.id === currentVendorId);
    if (currentIndex !== -1) {
        el.textContent = `Supplier ${currentIndex + 1} of ${VENDOR_DATABASE.length}`;
    }
}

/**
 * Update Banner Status Pill & Dot
 */
function updateBannerStatusDisplay(status) {
    const statusTextEl = document.getElementById('bannerVendorStatus');
    const statusBtnEl = document.getElementById('bannerVendorStatusBtn');
    const statusDotEl = document.getElementById('bannerStatusDot');
    if (statusTextEl) statusTextEl.textContent = status;
    if (statusBtnEl) {
        statusBtnEl.className = 'vendor-status-pill status-' + status.toLowerCase().replace(/[^a-z0-9]/g, '-');
    }
    if (statusDotEl) {
        let color = '#10b981';
        if (status === 'On Hold') color = '#f59e0b';
        else if (status === 'Inactive') color = '#94a3b8';
        else if (status === 'Blacklisted') color = '#ef4444';
        statusDotEl.style.background = color;
    }
}

/**
 * Toggle Quick Status Dropdown
 */
function toggleQuickStatusDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('quickStatusMenu');
    if (menu) menu.classList.toggle('active');
}

/**
 * Close Quick Status Dropdown on outside click
 */
document.addEventListener('click', (e) => {
    const menu = document.getElementById('quickStatusMenu');
    const btn = document.getElementById('bannerVendorStatusBtn');
    if (menu && menu.classList.contains('active')) {
        if (!menu.contains(e.target) && (!btn || !btn.contains(e.target))) {
            menu.classList.remove('active');
        }
    }
});

/**
 * Quick Change Vendor Status from Banner
 */
function quickChangeVendorStatus(newStatus) {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    vendor.status = newStatus;
    saveToLocalStorage();
    updateBannerStatusDisplay(newStatus);

    // Sync Tab 1 status radio button
    const radio = document.querySelector(`input[name="vendorStatus"][value="${newStatus}"]`);
    if (radio) radio.checked = true;

    renderModularCategoryFilters();
    renderKanbanCards(document.getElementById('kanbanSearchInput')?.value || '');
    updateDirectoryMetrics();

    const menu = document.getElementById('quickStatusMenu');
    if (menu) menu.classList.remove('active');

    showToast(`✓ Supplier status changed to ${newStatus}!`, 'success');
}

/**
 * Safe Clipboard Copy with fallback & toast confirmation
 */
function copyToClipboard(text, label = 'Data') {
    if (!text) return;
    const cleanText = text.trim();
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(cleanText).then(() => {
            showToast(`✓ ${label} copied to clipboard: ${cleanText}`, 'info');
        }).catch(() => {
            fallbackCopy(cleanText, label);
        });
    } else {
        fallbackCopy(cleanText, label);
    }
}

function fallbackCopy(text, label) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showToast(`✓ ${label} copied to clipboard: ${text}`, 'info');
    } catch (err) {
        showToast(`Failed to copy ${label}`, 'warning');
    }
    document.body.removeChild(textArea);
}

function handleQuickPhoneCall(event) {
    if (event) event.preventDefault();
    const phone = document.getElementById('bannerVendorPhone')?.textContent?.trim();
    if (!phone || phone === '—') return;
    copyToClipboard(phone, 'Phone number');
    window.location.href = `tel:${phone.replace(/[^0-9+]/g, '')}`;
}

function handleQuickEmail(event) {
    if (event) event.preventDefault();
    const email = document.getElementById('bannerVendorEmail')?.textContent?.trim();
    const vendorName = document.getElementById('bannerVendorName')?.textContent?.trim() || 'Supplier';
    if (!email || email === '—') return;
    copyToClipboard(email, 'Email address');
    window.location.href = `mailto:${email}?subject=Procurement Inquiry - ${encodeURIComponent(vendorName)}`;
}

/**
 * Save Current Active Tab Form from Hero Banner Button
 */
function saveCurrentVendorProfileForm() {
    if (activeTabId === 'tab-general') {
        const form = document.getElementById('formGeneralInfo');
        if (form && form.checkValidity()) {
            handleSaveGeneralInfo();
        } else if (form) {
            form.reportValidity();
        }
    } else if (activeTabId === 'tab-terms') {
        const form = document.getElementById('formPurchasingTerms');
        if (form && form.checkValidity()) {
            handleSavePurchasingTerms();
        } else if (form) {
            form.reportValidity();
        }
    } else {
        syncAllCatalogPricesToItemMaster();
    }
}

/**
 * Quick Delivery Days Preset Helper in Tab 2
 */
function setDeliveryDaysPreset(presetType) {
    const checkboxes = document.querySelectorAll('input[name="deliveryDays"]');
    checkboxes.forEach(cb => {
        if (presetType === 'weekdays') {
            cb.checked = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].includes(cb.value);
        } else if (presetType === 'all') {
            cb.checked = true;
        } else if (presetType === 'clear') {
            cb.checked = false;
        }
    });
    showToast(`✓ Delivery schedule set to: ${presetType === 'weekdays' ? 'Weekdays (Mon-Fri)' : presetType === 'all' ? 'All 7 Days' : 'Cleared'}`, 'info');
}

/**
 * Export Official Printable Procurement Dossier
 */
function exportVendorDossier() {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    const printWin = window.open('', '_blank', 'width=900,height=750');
    if (!printWin) {
        showToast('Please allow popups in your browser to export the vendor dossier.', 'warning');
        return;
    }

    const t = vendor.terms || {};
    const contactsHtml = (vendor.contacts || []).map(c => `
        <tr>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">${c.name} ${c.isDefault ? '<span style="color:#047857; font-size:11px;">(PRIMARY)</span>' : ''}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">${c.title || c.roleLabel || c.role}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">${c.phone || '—'}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">${c.email || '—'}</td>
        </tr>
    `).join('') || '<tr><td colspan="4" style="padding:10px; text-align:center;">No contacts listed</td></tr>';

    const catalogHtml = (vendor.catalog || []).map(cat => `
        <tr>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">${cat.itemMasterSku || '—'}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">${cat.name}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">${cat.uomPurchased}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align:right;">₱ ${Number(cat.cost).toFixed(2)}</td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align:right;">₱ ${(cat.ratioQty > 0 ? (cat.cost / cat.ratioQty) : cat.cost).toFixed(2)} / ${cat.ratioUnit}</td>
        </tr>
    `).join('') || '<tr><td colspan="5" style="padding:10px; text-align:center;">No catalog items mapped</td></tr>';

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Vendor Partner Dossier - ${vendor.legalName}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; margin: 30px; font-size: 13px; line-height: 1.5; }
                h1, h2, h3 { margin: 0 0 6px 0; color: #0f172a; }
                .dossier-header { display: flex; justify-content: space-between; border-bottom: 2px solid #0284c7; padding-bottom: 16px; margin-bottom: 20px; }
                .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
                .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px; }
                .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
                .lbl { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #64748b; margin-bottom: 2px; }
                .val { font-size: 13px; font-weight: 600; color: #0f172a; }
                table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; }
                @media print { body { margin: 0; } button { display: none; } }
            </style>
        </head>
        <body>
            <div class="dossier-header">
                <div>
                    <h1>${vendor.legalName}</h1>
                    <div style="font-size: 14px; color: #64748b;">DBA: ${vendor.tradeName || vendor.legalName} • Code: <strong>${vendor.code}</strong> • Status: <strong>${vendor.status}</strong></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: bold; color: #0284c7;">RESTAURANT PROCUREMENT DOSSIER</div>
                    <div style="font-size: 11px; color: #64748b;">Printed: ${new Date().toLocaleString()}</div>
                    <button onclick="window.print()" style="margin-top:8px; padding:6px 12px; background:#0284c7; color:#fff; border:none; border-radius:6px; cursor:pointer;">Print Dossier</button>
                </div>
            </div>

            <div class="grid-3">
                <div class="box">
                    <div class="lbl">Tax ID Number (TIN)</div>
                    <div class="val">${vendor.tin || '—'}</div>
                    <div class="lbl" style="margin-top:8px;">VAT Registration</div>
                    <div class="val">${vendor.vatType || 'VAT Registered (12%)'}</div>
                </div>
                <div class="box">
                    <div class="lbl">Withholding Tax (EWT)</div>
                    <div class="val">${vendor.ewtRate || '1% (Purchase of Goods)'}</div>
                    <div class="lbl" style="margin-top:8px;">Business Entity</div>
                    <div class="val">${vendor.entityType || 'Corporation'}</div>
                </div>
                <div class="box">
                    <div class="lbl">Category</div>
                    <div class="val">${vendor.category || 'General'}</div>
                    <div class="lbl" style="margin-top:8px;">Operating Hub / City</div>
                    <div class="val">${vendor.location || 'Metro Manila'}</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="box">
                    <h3>Billing & Registered Address</h3>
                    <div class="val">${vendor.address || '—'}</div>
                    <h3 style="margin-top:12px;">Dispatch / Warehouse Address</h3>
                    <div class="val">${vendor.dispatchAddress || vendor.address || '—'}</div>
                </div>
                <div class="box">
                    <h3>Purchasing, Logistics & Payment Terms</h3>
                    <div class="grid-2" style="margin-bottom:0;">
                        <div>
                            <div class="lbl">Payment Terms</div>
                            <div class="val">${t.payment || 'Net 30 Days'}</div>
                            <div class="lbl" style="margin-top:8px;">Credit Limit</div>
                            <div class="val">₱ ${(Number(t.creditLimit) || 0).toLocaleString()}</div>
                        </div>
                        <div>
                            <div class="lbl">Avg Lead Time</div>
                            <div class="val">${t.leadTimeDays || 2} Days</div>
                            <div class="lbl" style="margin-top:8px;">Min Order Value (MOV)</div>
                            <div class="val">₱ ${(Number(t.mov) || 0).toLocaleString()}</div>
                        </div>
                    </div>
                    <div style="margin-top:8px;">
                        <div class="lbl">Delivery Days Schedule</div>
                        <div class="val">${t.deliveryDays || 'Mon, Tue, Wed, Thu, Fri'}</div>
                    </div>
                </div>
            </div>

            <div class="box" style="margin-bottom: 20px;">
                <h3>Banking & AP Remittance Details</h3>
                <div class="grid-3" style="margin-bottom:0;">
                    <div>
                        <div class="lbl">Bank Name</div>
                        <div class="val">${t.bankName || 'BDO Unibank'}</div>
                    </div>
                    <div>
                        <div class="lbl">Account Name</div>
                        <div class="val">${t.accountName || vendor.legalName}</div>
                    </div>
                    <div>
                        <div class="lbl">Account Number</div>
                        <div class="val">${t.accountNumber || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="box" style="margin-bottom: 20px;">
                <h3>Designated Contact Persons</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Contact Name</th>
                            <th>Role / Title</th>
                            <th>Phone</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>${contactsHtml}</tbody>
                </table>
            </div>

            <div class="box">
                <h3>Mapped Catalog Items & Pricing</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Master SKU</th>
                            <th>Item Description</th>
                            <th>Packaging UOM</th>
                            <th style="text-align:right;">Purchase Cost</th>
                            <th style="text-align:right;">Recipe Base Cost</th>
                        </tr>
                    </thead>
                    <tbody>${catalogHtml}</tbody>
                </table>
            </div>
        </body>
        </html>
    `);
    printWin.document.close();
    showToast('✓ Generated official Vendor Dossier for printing!', 'success');
}

/**
 * Open Quick Purchase Order Creation Modal
 */
function openCreatePOModalForCurrentVendor() {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    const randomSuffix = Math.floor(1000 + Math.random() * 9000);
    const poRef = `PO-${new Date().getFullYear()}-${randomSuffix}`;
    const refDisplay = document.getElementById('poRefDisplay');
    const refHidden = document.getElementById('poRefHidden');
    if (refDisplay) refDisplay.textContent = poRef;
    if (refHidden) refHidden.value = poRef;

    setText('poVendorNameDisplay', vendor.legalName);
    setText('poVendorCodeDisplay', vendor.code);
    setText('poPaymentTermsDisplay', vendor.terms?.payment || 'Net 30 Days');

    const leadDays = Number(vendor.terms?.leadTimeDays) || 2;
    const targetDate = new Date();
    targetDate.setDate(targetDate.getDate() + leadDays);
    const yyyy = targetDate.getFullYear();
    const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
    const dd = String(targetDate.getDate()).padStart(2, '0');
    const dateInput = document.getElementById('poDeliveryDateInput');
    if (dateInput) {
        dateInput.value = `${yyyy}-${mm}-${dd}`;
        dateInput.min = new Date().toISOString().split('T')[0];
    }

    const tbody = document.getElementById('poItemsTableBody');
    if (tbody) {
        if (!vendor.catalog || vendor.catalog.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px; color:var(--vendor-text-muted);">
                        No mapped catalog items found. Map items from Item Master in Tab 3 to place purchase orders.
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = vendor.catalog.map((item, idx) => {
                const cost = Number(item.cost) || 0;
                return `
                    <tr style="border-bottom: 1px solid var(--vendor-border-subtle);">
                        <td style="padding: 10px 12px; font-weight:700; color:var(--vendor-text-strong);">
                            ${item.name}
                            <div style="font-size:0.72rem; color:var(--vendor-text-muted); font-weight:normal;">SKU: ${item.itemMasterSku || item.sku}</div>
                        </td>
                        <td style="padding: 10px 12px; font-size:0.8rem; color:var(--vendor-text-medium);">${item.uomPurchased}</td>
                        <td style="padding: 10px 12px; font-size:0.82rem; font-weight:700;">₱ ${formatMoney(cost)}</td>
                        <td style="padding: 10px 12px; width: 110px;">
                            <input type="number" class="vendor-form-input po-item-qty" data-index="${idx}" data-cost="${cost}" min="0" value="0" step="1" oninput="calculatePOTotals()" style="padding: 5px 8px; font-size: 0.82rem; text-align: center;">
                        </td>
                        <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: var(--vendor-primary);" id="poLineTotal_${idx}">
                            ₱ 0.00
                        </td>
                    </tr>
                `;
            }).join('');
        }
    }

    calculatePOTotals();
    openModal('quickPOModal');
}

/**
 * Calculate PO Line and Total values live
 */
function calculatePOTotals() {
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    let total = 0;
    const qtyInputs = document.querySelectorAll('.po-item-qty');
    qtyInputs.forEach(input => {
        const idx = input.getAttribute('data-index');
        const cost = parseFloat(input.getAttribute('data-cost')) || 0;
        const qty = parseInt(input.value, 10) || 0;
        const lineTotal = cost * qty;
        total += lineTotal;
        const lineEl = document.getElementById(`poLineTotal_${idx}`);
        if (lineEl) lineEl.textContent = `₱ ${formatMoney(lineTotal)}`;
    });

    setText('poTotalAmountDisplay', `₱ ${formatMoney(total)}`);

    const mov = vendor?.terms?.mov || 5000;
    const freeThresh = vendor?.terms?.freeDeliveryThreshold || 8000;

    const movEl = document.getElementById('poMovComplianceBadge');
    if (movEl) {
        if (total === 0) {
            movEl.innerHTML = `<span style="color:var(--vendor-text-muted);">Minimum Order Value: ₱ ${formatMoney(mov)}</span>`;
        } else if (total >= mov) {
            movEl.innerHTML = `<span style="color:#047857; font-weight:bold;"><i class="ph ph-check-circle"></i> Meets MOV Constraint (₱ ${formatMoney(mov)})</span>`;
        } else {
            movEl.innerHTML = `<span style="color:#b45309; font-weight:bold;"><i class="ph ph-warning-circle"></i> Below Supplier MOV (₱ ${formatMoney(mov)})</span>`;
        }
    }

    const freeEl = document.getElementById('poFreeDeliveryBadge');
    if (freeEl) {
        if (total >= freeThresh) {
            freeEl.innerHTML = `<span style="color:#047857; font-weight:bold;"><i class="ph ph-truck"></i> Free Delivery Eligible!</span>`;
        } else {
            const diff = freeThresh - total;
            freeEl.innerHTML = `<span style="color:var(--vendor-text-muted);">Add ₱ ${formatMoney(diff > 0 ? diff : 0)} more for Free Shipping</span>`;
        }
    }
}

/**
 * Handle PO Generation Submission
 */
function handleCreatePOSubmit(event) {
    if (event) event.preventDefault();
    const vendor = VENDOR_DATABASE.find(v => v.id === currentVendorId);
    if (!vendor) return;

    const poRef = document.getElementById('poRefHidden')?.value || `PO-${Date.now()}`;
    const deliveryDate = document.getElementById('poDeliveryDateInput')?.value || '';
    const priority = document.getElementById('poPrioritySelect')?.value || 'Standard Replenishment';
    const notes = document.getElementById('poNotesTextarea')?.value || '';

    const items = [];
    let totalAmount = 0;
    document.querySelectorAll('.po-item-qty').forEach(input => {
        const qty = parseInt(input.value, 10) || 0;
        if (qty > 0) {
            const idx = parseInt(input.getAttribute('data-index'), 10);
            const cat = vendor.catalog[idx];
            if (cat) {
                const cost = Number(cat.cost) || 0;
                const lineTotal = cost * qty;
                totalAmount += lineTotal;
                items.push({
                    sku: cat.sku,
                    itemMasterSku: cat.itemMasterSku,
                    name: cat.name,
                    uomPurchased: cat.uomPurchased,
                    unitCost: cost,
                    quantity: qty,
                    lineTotal: lineTotal
                });
            }
        }
    });

    if (items.length === 0) {
        alert('Please specify at least 1 item with quantity greater than 0.');
        return;
    }

    const poRecord = {
        poNumber: poRef,
        vendorId: vendor.id,
        vendorName: vendor.legalName,
        vendorCode: vendor.code,
        paymentTerms: vendor.terms?.payment || 'Net 30 Days',
        orderDate: new Date().toISOString().split('T')[0],
        deliveryDate: deliveryDate,
        priority: priority,
        notes: notes,
        items: items,
        totalAmount: totalAmount,
        status: 'Draft Issued',
        createdAt: new Date().toISOString()
    };

    try {
        const existing = JSON.parse(localStorage.getItem('rms_purchase_orders') || '[]');
        existing.unshift(poRecord);
        localStorage.setItem('rms_purchase_orders', JSON.stringify(existing));
    } catch (e) {
        console.warn('PO save error:', e);
    }

    closeModal('quickPOModal');
    showToast(`✓ Purchase Order ${poRef} issued to ${vendor.legalName} (₱ ${formatMoney(totalAmount)})!`, 'success');
}

/**
 * Global Keyboard Shortcuts for Vendor Profile View
 */
document.addEventListener('keydown', (e) => {
    const profileWrapper = document.getElementById('vendorProfileWrapper');
    if (!profileWrapper || profileWrapper.style.display === 'none') return;

    // Esc closes profile if no modal is active
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.vendor-modal-overlay.active');
        if (!activeModal) {
            closeVendorProfile();
        }
    }

    // Ctrl+S or Cmd+S saves current tab form
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        saveCurrentVendorProfileForm();
    }
});

/**
 * Register New Vendor Modal Action (with dynamically populated category taxonomy)
 */
function openAddVendorModal() {
    populateAddVendorCategorySelect();
    openModal('addVendorModal');
}

/**
 * Populate Category Options for Modal from current taxonomy
 */
function populateAddVendorCategorySelect() {
    const select = document.getElementById('newCategory');
    if (!select) return;

    const categoryMap = getActiveVendorCategories();
    let html = '';
    categoryMap.forEach((_, cat) => {
        html += `<option value="${cat}">${cat}</option>`;
    });
    html += `<option value="__custom__">+ Add New Vendor Type...</option>`;
    select.innerHTML = html;

    const customInput = document.getElementById('newCustomCategory');
    if (customInput) {
        customInput.style.display = 'none';
        customInput.value = '';
    }
}

/**
 * Toggle custom category text field in Add Vendor Modal
 */
function handleNewVendorCategoryChange(val) {
    const customInput = document.getElementById('newCustomCategory');
    if (!customInput) return;
    if (val === '__custom__') {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
    }
}

function handleSaveNewVendor(event) {
    if (event) event.preventDefault();
    const legalName = document.getElementById('newLegalName').value;
    const tradeName = document.getElementById('newTradeName').value;
    const code = document.getElementById('newVendorCode').value;
    const tin = document.getElementById('newTIN').value;
    
    let category = document.getElementById('newCategory').value;
    if (category === '__custom__') {
        category = (document.getElementById('newCustomCategory')?.value || '').trim() || 'General';
    }

    const payment = document.getElementById('newTerms').value;
    const leadTimeDays = parseInt(document.getElementById('newLeadTime').value, 10) || 3;
    const mov = parseFloat(document.getElementById('newMOV').value) || 5000;
    const location = document.getElementById('newLocation').value;
    const email = document.getElementById('newEmail').value;
    const address = document.getElementById('newAddress').value;

    const newVendor = {
        id: code || `VND-${Date.now()}`,
        code,
        legalName,
        tradeName,
        location,
        email,
        tin,
        address,
        category,
        tagClass: `tag-${category.toLowerCase().replace(/[^a-z0-9]/g, '-')}`,
        status: "Active Preferred",
        avatarType: "initial-box",
        avatarText: legalName.charAt(0).toUpperCase(),
        avatarBg: "#0284c7",
        contacts: [
            {
                role: "sales",
                roleLabel: "Sales Rep (For Placing Orders)",
                roleClass: "role-sales",
                name: "Primary Sales Officer",
                title: "Key Account Lead",
                phone: "+63 900 000 0000",
                email: email,
                notes: "Primary account representative"
            }
        ],
        terms: {
            payment,
            leadTimeDays,
            rushTime: "24 Hours",
            mov,
            moq: "5 Cases",
            cutoff: "2:00 PM",
            deliveryDays: "Tuesday & Friday (6:00 AM – 10:00 AM)",
            earlyDiscount: "None",
            scheduleDesc: "Receiving dock protocol standard check.",
            bankName: "BDO Unibank, Inc.",
            accountName: legalName,
            accountNumber: "0000-0000-0000",
            bankSwift: "",
            bankInstructions: ""
        },
        catalog: []
    };

    VENDOR_DATABASE.unshift(newVendor);
    saveToLocalStorage();
    renderModularCategoryFilters();
    renderKanbanCards();
    updateDirectoryMetrics();
    closeModal('addVendorModal');
    showToast(`✓ Supplier ${legalName} registered successfully under ${category}!`, 'success');
}

/**
 * Export Vendor Directory to CSV
 */
function exportVendorDirectory() {
    const headers = ["Vendor Code", "Legal Name", "Trade Name", "TIN", "Category", "Payment Terms", "Lead Time", "Contact Email"];
    const rows = VENDOR_DATABASE.map(v => [
        `"${v.code || ''}"`,
        `"${v.legalName || ''}"`,
        `"${v.tradeName || ''}"`,
        `"${v.tin || ''}"`,
        `"${v.category || ''}"`,
        `"${v.terms?.payment || 'Net 30'}"`,
        `"${v.terms?.leadTimeDays || 3} Days"`,
        `"${v.email || ''}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(","), ...rows.map(e => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `RMS_Vendor_Directory_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('✓ Vendor Directory CSV downloaded!', 'success');
}

function formatMoney(amount) {
    if (isNaN(amount)) return '0.00';
    return Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Soft Toast Notification Helper
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('vendorToast');
    const toastText = document.getElementById('vendorToastText');
    if (!toast || !toastText) return;

    toastText.textContent = message;
    toast.className = `vendor-toast ${type} active`;

    setTimeout(() => {
        toast.classList.remove('active');
    }, 3800);
}

// Expose functions and databases to window for inline HTML event handlers & ERP cross-module calls
Object.assign(window, {
    ITEM_MASTER_DICTIONARY,
    SEEDED_VENDORS,
    VENDOR_DATABASE,
    currentVendorId,
    activeFilterCategory,
    activeTabId,
    DEFAULT_VENDOR_CATEGORIES,
    CUSTOM_VENDOR_CATEGORIES,
    loadCustomCategories,
    saveCustomCategories,
    sanitizeVendor,
    getActiveVendorCategories,
    renderModularCategoryFilters,
    scrollFilterPills,
    updatePillScrollControls,
    saveToLocalStorage,
    loadFromLocalStorage,
    resetVendorDatabaseToDefaults,
    updateDirectoryMetrics,
    populateItemMasterDropdown,
    handleItemMasterSelectChange,
    renderKanbanCards,
    filterKanbanCategory,
    handleCategorySelectChange,
    handleKanbanSearch,
    clearKanbanSearch,
    handleCatalogSearch,
    openVendorProfile,
    closeVendorProfile,
    cycleVendor,
    hydrateVendorProfile,
    setText,
    setVal,
    handleSaveGeneralInfo,
    handleSavePurchasingTerms,
    renderContactsGrid,
    renderCatalogTable,
    extractUomPrefix,
    syncSingleItemToItemMaster,
    syncAllCatalogPricesToItemMaster,
    switchVendorTab,
    openModal,
    closeModal,
    openAddCatalogModal,
    openEditCatalogModal,
    updateConversionPreview,
    handleSaveCatalogItem,
    deleteCatalogItem,
    openAddContactModal,
    openEditContactModal,
    handleSaveContact,
    deleteContact,
    setDefaultContact,
    toggleSameAsBilling,
    openCategoryManagerModal,
    renderCategoryManagerList,
    addNewCategoryFromModal,
    selectCategoryFromModal,
    renameCategoryFromModal,
    deleteCategoryFromModal,
    refreshAllCategoryDropdowns,
    promptAddNewCategoryFromProfile,
    updateVendorNavCounter,
    updateBannerStatusDisplay,
    toggleQuickStatusDropdown,
    quickChangeVendorStatus,
    copyToClipboard,
    fallbackCopy,
    handleQuickPhoneCall,
    handleQuickEmail,
    saveCurrentVendorProfileForm,
    setDeliveryDaysPreset,
    exportVendorDossier,
    openCreatePOModalForCurrentVendor,
    calculatePOTotals,
    handleCreatePOSubmit,
    openAddVendorModal,
    populateAddVendorCategorySelect,
    handleNewVendorCategoryChange,
    handleSaveNewVendor,
    exportVendorDirectory,
    formatMoney,
    showToast
});
