<?php

namespace Database\Seeders;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctCustomerActivity;
use App\Models\Accounting\AcctCustomerAddress;
use App\Models\Accounting\AcctCustomerContact;
use App\Models\Accounting\AcctCustomerDocument;
use App\Models\Accounting\AcctCustomerPriceList;
use App\Models\Accounting\AcctNumberingSeries;
use App\Models\Inventory\InventoryItem;
use App\Models\Sales\SalesBundle;
use App\Models\Sales\SalesBundleItem;
use App\Models\Sales\SalesDiscount;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\Sales\SalesPromoAuditLog;
use App\Models\Sales\SalesVoucher;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalesModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('sales_orders') || !Schema::hasTable('acct_customers')) {
            return;
        }

        $companyId = '1';
        $branchId = 'BR-01';

        // Ensure Accounting COA (4010/4090), Tax Codes, Payment Terms, Numbering Series & Credit Workflow exist
        app(AccountingSeedService::class)->ensureSeeded($companyId);

        // Fetch existing sellable items from item_master (graceful fallback if running on minimal DB)
        $items = InventoryItem::whereIn('sku', ['MNC-101', 'BEV-001', 'BEV-002', 'PKG-501', 'SYR-601'])
            ->get()
            ->keyBy('sku');

        $pasta = $items->get('MNC-101');
        $latte = $items->get('BEV-001');
        $americano = $items->get('BEV-002');
        $box = $items->get('PKG-501');
        $syrup = $items->get('SYR-601');

        // 1. Seed Customer 360 Master Data (Parent/Subsidiary Hierarchy + Prospect)
        $parentCorp = AcctCustomer::firstOrCreate(
            ['company_id' => $companyId, 'customer_code' => 'CUST-0001'],
            [
                'customer_id' => (string) Str::uuid(),
                'customer_name' => 'Manila Grand Hospitality Group Inc.',
                'trade_name' => 'Manila Grand Hotels & Events',
                'tin' => '008-412-901-000',
                'address' => '28F Ayala Triangle Tower 2, Makati City, Metro Manila',
                'shipping_address' => 'Loading Bay B, Ayala Ave., Makati City',
                'contact_person' => 'Elena Reyes',
                'email' => 'procurement@manilagrand.ph',
                'phone' => '+63 917 555 0101',
                'payment_terms_code' => 'NET30',
                'credit_limit' => 250000.0000,
                'current_balance' => 0.0000,
                'customer_tier' => 'VIP',
                'credit_hold' => false,
                'credit_grace_days' => 15,
                'default_tax_code' => 'VAT12_OUT',
                'sales_rep_name' => 'Corporate Sales Desk',
                'account_stage' => 'ACTIVE',
                'website' => 'https://manilagrand.ph',
                'industry' => 'Hospitality & Banquets',
                'notes' => 'Key national hospitality account with scheduled weekly commissary deliveries.',
                'is_active' => true,
            ]
        );

        $bgcBranch = AcctCustomer::firstOrCreate(
            ['company_id' => $companyId, 'customer_code' => 'CUST-0002'],
            [
                'customer_id' => (string) Str::uuid(),
                'parent_customer_id' => $parentCorp->customer_id,
                'customer_name' => 'Manila Grand — BGC Banquet Pavilion',
                'trade_name' => 'Grand Pavilion BGC',
                'tin' => '008-412-901-001',
                'address' => '5th Ave. cor. 28th St., Bonifacio Global City, Taguig',
                'shipping_address' => 'Service Gate 3, BGC Banquet Pavilion, Taguig',
                'contact_person' => 'Marco Santos',
                'email' => 'bgc.kitchen@manilagrand.ph',
                'phone' => '+63 917 555 0102',
                'payment_terms_code' => 'NET15',
                'credit_limit' => 120000.0000,
                'current_balance' => 0.0000,
                'customer_tier' => 'CORPORATE',
                'credit_hold' => false,
                'credit_grace_days' => 10,
                'default_tax_code' => 'VAT12_OUT',
                'sales_rep_name' => 'Corporate Sales Desk',
                'account_stage' => 'ACTIVE',
                'industry' => 'Events & Catering',
                'notes' => 'Subsidiary property under Manila Grand Hospitality Group.',
                'is_active' => true,
            ]
        );

        $cafePartner = AcctCustomer::firstOrCreate(
            ['company_id' => $companyId, 'customer_code' => 'CUST-0003'],
            [
                'customer_id' => (string) Str::uuid(),
                'customer_name' => 'Ortigas Specialty Coffee Collective Corp.',
                'trade_name' => 'Brew & Co. Ortigas',
                'tin' => '009-773-214-000',
                'address' => 'G/F Emerald Corporate Center, Ortigas Center, Pasig',
                'shipping_address' => 'Rear Receiving Dock, Emerald Ave., Pasig City',
                'contact_person' => 'Camille Mendoza',
                'email' => 'orders@brewandco.ph',
                'phone' => '+63 918 555 0203',
                'payment_terms_code' => 'COD',
                'credit_limit' => 50000.0000,
                'current_balance' => 0.0000,
                'customer_tier' => 'STANDARD',
                'credit_hold' => false,
                'credit_grace_days' => 7,
                'default_tax_code' => 'VAT12_OUT',
                'sales_rep_name' => 'B2B Account Executive',
                'account_stage' => 'ACTIVE',
                'industry' => 'Food & Beverage Retail',
                'notes' => 'COD retail cafe partner ordering syrups and ready-to-serve pastries.',
                'is_active' => true,
            ]
        );

        $prospect = AcctCustomer::firstOrCreate(
            ['company_id' => $companyId, 'customer_code' => 'CUST-0004'],
            [
                'customer_id' => (string) Str::uuid(),
                'customer_name' => 'Alabang Executive Lounge & Suites',
                'trade_name' => 'Alabang Executive Suites',
                'tin' => '010-204-889-000',
                'address' => 'Filinvest Corporate City, Alabang, Muntinlupa',
                'shipping_address' => 'Filinvest Corporate City, Alabang, Muntinlupa',
                'contact_person' => 'Paolo Villanueva',
                'email' => 'fnb@alabangsuites.ph',
                'phone' => '+63 917 555 0304',
                'payment_terms_code' => 'NET30',
                'credit_limit' => 80000.0000,
                'current_balance' => 0.0000,
                'customer_tier' => 'STANDARD',
                'credit_hold' => false,
                'credit_grace_days' => 15,
                'default_tax_code' => 'VAT12_OUT',
                'sales_rep_name' => 'Corporate Sales Desk',
                'account_stage' => 'PROSPECT',
                'industry' => 'Boutique Hotel',
                'notes' => 'Evaluating commissary lunch tray and espresso supply quotation.',
                'is_active' => true,
            ]
        );

        // 2. Customer 360 Contacts, Addresses, Contract Price Lists, Compliance Docs & Activities
        if (Schema::hasTable('acct_customer_contacts')) {
            AcctCustomerContact::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'email' => 'procurement@manilagrand.ph'],
                [
                    'contact_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'full_name' => 'Elena Reyes',
                    'role_title' => 'VP of Procurement',
                    'department' => 'PURCHASING',
                    'phone' => '+63 917 555 0101',
                    'is_primary' => true,
                    'receives_invoices' => false,
                    'created_by' => 'System Seed',
                ]
            );
            AcctCustomerContact::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'email' => 'ap@manilagrand.ph'],
                [
                    'contact_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'full_name' => 'Clarissa Tan',
                    'role_title' => 'Accounts Payable Manager',
                    'department' => 'AP',
                    'phone' => '+63 917 555 0199',
                    'is_primary' => false,
                    'receives_invoices' => true,
                    'created_by' => 'System Seed',
                ]
            );
            AcctCustomerContact::firstOrCreate(
                ['customer_id' => $cafePartner->customer_id, 'email' => 'orders@brewandco.ph'],
                [
                    'contact_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'full_name' => 'Camille Mendoza',
                    'role_title' => 'Operations Lead',
                    'department' => 'RECEIVING',
                    'phone' => '+63 918 555 0203',
                    'is_primary' => true,
                    'receives_invoices' => true,
                    'created_by' => 'System Seed',
                ]
            );
        }

        if (Schema::hasTable('acct_customer_addresses')) {
            AcctCustomerAddress::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'label' => 'Makati Head Office (Billing)'],
                [
                    'address_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'address_type' => 'BILLING',
                    'address_line' => '28F Ayala Triangle Tower 2, Makati City',
                    'city' => 'Makati',
                    'province' => 'Metro Manila',
                    'postal_code' => '1226',
                    'receiving_contact' => 'Clarissa Tan',
                    'receiving_phone' => '+63 917 555 0199',
                    'is_default' => true,
                    'created_by' => 'System Seed',
                ]
            );
            AcctCustomerAddress::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'label' => 'Ayala Loading Bay B (Commissary Drop)'],
                [
                    'address_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'address_type' => 'SHIPPING',
                    'address_line' => 'Loading Bay B, Ayala Ave., Makati City',
                    'city' => 'Makati',
                    'province' => 'Metro Manila',
                    'postal_code' => '1226',
                    'receiving_contact' => 'Chief Steward Office',
                    'receiving_phone' => '+63 917 555 0150',
                    'delivery_window' => '06:00 AM – 10:30 AM',
                    'is_default' => true,
                    'created_by' => 'System Seed',
                ]
            );
        }

        if (Schema::hasTable('acct_customer_price_lists') && $pasta && $latte) {
            AcctCustomerPriceList::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'internal_sku' => $pasta->sku],
                [
                    'price_list_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'inventory_item_id' => $pasta->id,
                    'item_name' => $pasta->name,
                    'customer_sku' => 'MGH-PASTA-TRUF',
                    'uom' => $pasta->uom ?: 'Plate',
                    'standard_price' => (float) ($pasta->selling_price ?: 380),
                    'contract_price' => 340.0000,
                    'discount_pct' => 10.53,
                    'min_qty' => 10.0000,
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'valid_until' => now()->endOfYear()->toDateString(),
                    'is_active' => true,
                    'notes' => 'Annual VIP banquet contract rate.',
                    'created_by' => 'System Seed',
                ]
            );

            AcctCustomerPriceList::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'internal_sku' => $latte->sku],
                [
                    'price_list_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'inventory_item_id' => $latte->id,
                    'item_name' => $latte->name,
                    'customer_sku' => 'MGH-BEV-SLATTE',
                    'uom' => $latte->uom ?: 'Cup',
                    'standard_price' => (float) ($latte->selling_price ?: 150),
                    'contract_price' => 135.0000,
                    'discount_pct' => 10.00,
                    'min_qty' => 20.0000,
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'valid_until' => now()->endOfYear()->toDateString(),
                    'is_active' => true,
                    'created_by' => 'System Seed',
                ]
            );
        }

        if (Schema::hasTable('acct_customer_documents')) {
            AcctCustomerDocument::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'reference_no' => 'COR-047-2026-008412'],
                [
                    'document_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'doc_type' => 'BIR_2303_COR',
                    'document_title' => 'BIR Form 2303 Certificate of Registration',
                    'issue_date' => now()->subMonths(6)->toDateString(),
                    'expiry_date' => now()->addYear()->toDateString(),
                    'notes' => 'Verified VAT-registered corporate taxpayer.',
                    'uploaded_by' => 'System Seed',
                ]
            );
            AcctCustomerDocument::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'reference_no' => 'MSA-2026-MGH-01'],
                [
                    'document_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'doc_type' => 'CONTRACT',
                    'document_title' => 'Master Supply & Credit Terms Agreement (NET30)',
                    'issue_date' => now()->startOfYear()->toDateString(),
                    'expiry_date' => now()->endOfYear()->toDateString(),
                    'notes' => 'Signed PHP 250,000 credit facility.',
                    'uploaded_by' => 'System Seed',
                ]
            );
        }

        if (Schema::hasTable('acct_customer_activities')) {
            AcctCustomerActivity::firstOrCreate(
                ['customer_id' => $parentCorp->customer_id, 'subject' => 'Annual VIP Supply Contract Activated'],
                [
                    'activity_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'activity_type' => 'SYSTEM',
                    'description' => 'Customer 360 profile onboarded with NET30 PHP 250,000 credit facility and Makati/BGC delivery docks.',
                    'acted_by' => 'System Seed',
                    'acted_at' => now()->subDays(5),
                ]
            );
            AcctCustomerActivity::firstOrCreate(
                ['customer_id' => $prospect->customer_id, 'subject' => 'Sample Tasting & Initial Proposal Sent'],
                [
                    'activity_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'activity_type' => 'MEETING',
                    'description' => 'Presented corporate lunch tray and specialty beverage menu pricing for Alabang Suites.',
                    'follow_up_date' => now()->addDays(4)->toDateString(),
                    'acted_by' => 'Corporate Sales Desk',
                    'acted_at' => now()->subDays(1),
                ]
            );
        }

        // 3. Seed Sales Discounts, Vouchers, Bundles & Promo Audit Logs
        if (Schema::hasTable('sales_discounts')) {
            $discountsData = [
                [
                    'discount_code' => 'SC-PWD-20',
                    'discount_name' => 'Senior Citizen / PWD Statutory 20% (BIR VAT-Exempt)',
                    'discount_category' => 'STATUTORY',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 20.0000,
                    'min_order_amount' => 0.0000,
                    'max_discount_cap' => null,
                    'applicable_customer_tier' => 'ALL',
                    'vat_exempt_override' => true,
                    'requires_manager_approval' => false,
                    'stackable' => false,
                    'duration_mode' => 'ALWAYS',
                    'is_active' => true,
                    'notes' => 'RA 9994 / RA 10754 statutory 20% discount with 12% VAT exemption override.',
                ],
                [
                    'discount_code' => 'CORP-VOL-5',
                    'discount_name' => 'Corporate Volume Order 5% Off',
                    'discount_category' => 'COMMERCIAL',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 5.0000,
                    'min_order_amount' => 10000.0000,
                    'max_discount_cap' => 5000.0000,
                    'applicable_customer_tier' => 'CORPORATE',
                    'vat_exempt_override' => false,
                    'requires_manager_approval' => false,
                    'stackable' => false,
                    'duration_mode' => 'ALWAYS',
                    'is_active' => true,
                    'notes' => 'Applies automatically on corporate orders PHP 10,000+.',
                ],
                [
                    'discount_code' => 'VIP-TIER-10',
                    'discount_name' => 'VIP Hospitality Partner 10% Rebate',
                    'discount_category' => 'VIP',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 10.0000,
                    'min_order_amount' => 15000.0000,
                    'max_discount_cap' => 15000.0000,
                    'applicable_customer_tier' => 'VIP',
                    'vat_exempt_override' => false,
                    'requires_manager_approval' => true,
                    'stackable' => false,
                    'duration_mode' => 'ALWAYS',
                    'is_active' => true,
                    'notes' => 'Preferred VIP tier commercial allowance.',
                ],
            ];

            foreach ($discountsData as $d) {
                $disc = SalesDiscount::firstOrCreate(
                    ['company_id' => $companyId, 'discount_code' => $d['discount_code']],
                    array_merge($d, ['discount_id' => (string) Str::uuid(), 'created_by' => 'System Seed'])
                );
                if ($disc->wasRecentlyCreated && Schema::hasTable('sales_promo_audit_logs')) {
                    SalesPromoAuditLog::create([
                        'audit_id' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'promo_module' => 'DISCOUNT',
                        'promo_id' => $disc->discount_id,
                        'promo_code' => $disc->discount_code,
                        'promo_name' => $disc->discount_name,
                        'action' => 'CREATED',
                        'summary' => "Seeded baseline discount {$disc->discount_code}.",
                        'acted_by' => 'System Seed',
                        'acted_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('sales_vouchers')) {
            $vouchersData = [
                [
                    'voucher_code' => 'WELCOME500',
                    'voucher_name' => 'New B2B Account Welcome Credit (PHP 500)',
                    'discount_type' => 'FIXED',
                    'discount_value' => 500.0000,
                    'min_spend_amount' => 3000.0000,
                    'max_discount_amount' => 500.0000,
                    'max_total_uses' => 100,
                    'max_uses_per_customer' => 1,
                    'current_uses_count' => 0,
                    'duration_mode' => 'SCHEDULED',
                    'valid_from' => now()->startOfMonth()->toDateString(),
                    'valid_until' => now()->addMonths(3)->endOfMonth()->toDateString(),
                    'status' => 'ACTIVE',
                    'notes' => 'One-time welcome voucher for newly activated accounts.',
                ],
                [
                    'voucher_code' => 'CATERING10',
                    'voucher_name' => 'Banquet & Catering Seasonal 10% Voucher',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 10.0000,
                    'min_spend_amount' => 8000.0000,
                    'max_discount_amount' => 3000.0000,
                    'max_total_uses' => 50,
                    'max_uses_per_customer' => 3,
                    'current_uses_count' => 0,
                    'duration_mode' => 'SCHEDULED',
                    'valid_from' => now()->startOfMonth()->toDateString(),
                    'valid_until' => now()->addMonths(2)->endOfMonth()->toDateString(),
                    'status' => 'ACTIVE',
                    'notes' => 'Seasonal catering voucher capped at PHP 3,000 per order.',
                ],
            ];

            foreach ($vouchersData as $v) {
                $voucher = SalesVoucher::firstOrCreate(
                    ['company_id' => $companyId, 'voucher_code' => $v['voucher_code']],
                    array_merge($v, ['voucher_id' => (string) Str::uuid(), 'created_by' => 'System Seed'])
                );
                if ($voucher->wasRecentlyCreated && Schema::hasTable('sales_promo_audit_logs')) {
                    SalesPromoAuditLog::create([
                        'audit_id' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'promo_module' => 'VOUCHER',
                        'promo_id' => $voucher->voucher_id,
                        'promo_code' => $voucher->voucher_code,
                        'promo_name' => $voucher->voucher_name,
                        'action' => 'CREATED',
                        'summary' => "Seeded baseline voucher {$voucher->voucher_code}.",
                        'acted_by' => 'System Seed',
                        'acted_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('sales_bundles') && $pasta && $latte) {
            $bundle1 = SalesBundle::firstOrCreate(
                ['company_id' => $companyId, 'bundle_code' => 'BND-EXEC-LUNCH'],
                [
                    'bundle_id' => (string) Str::uuid(),
                    'bundle_name' => 'Executive Truffle Pasta & Spanish Latte Set',
                    'description' => '1x Truffle Mushroom Pasta + 1x Signature Spanish Latte + 1x Kraft Takeout Box.',
                    'bundle_price' => 480.0000,
                    'tax_code' => 'VAT12_OUT',
                    'duration_mode' => 'ALWAYS',
                    'is_active' => true,
                    'created_by' => 'System Seed',
                ]
            );

            if ($bundle1->wasRecentlyCreated) {
                $components = array_filter([
                    ['item' => $pasta, 'qty' => 1],
                    ['item' => $latte, 'qty' => 1],
                    $box ? ['item' => $box, 'qty' => 1] : null,
                ]);
                foreach ($components as $comp) {
                    SalesBundleItem::create([
                        'bundle_item_id' => (string) Str::uuid(),
                        'bundle_id' => $bundle1->bundle_id,
                        'inventory_item_id' => $comp['item']->id,
                        'sku' => $comp['item']->sku,
                        'item_name' => $comp['item']->name,
                        'quantity' => $comp['qty'],
                        'uom' => $comp['item']->uom ?: 'PCS',
                    ]);
                }
                if (Schema::hasTable('sales_promo_audit_logs')) {
                    SalesPromoAuditLog::create([
                        'audit_id' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'promo_module' => 'BUNDLE',
                        'promo_id' => $bundle1->bundle_id,
                        'promo_code' => $bundle1->bundle_code,
                        'promo_name' => $bundle1->bundle_name,
                        'action' => 'CREATED',
                        'summary' => "Seeded bundle promotion {$bundle1->bundle_code}.",
                        'acted_by' => 'System Seed',
                        'acted_at' => now(),
                    ]);
                }
            }

            if ($americano && $syrup) {
                $bundle2 = SalesBundle::firstOrCreate(
                    ['company_id' => $companyId, 'bundle_code' => 'BND-CAFE-STARTER'],
                    [
                        'bundle_id' => (string) Str::uuid(),
                        'bundle_name' => 'Coffee Bar Catering Duo Pack',
                        'description' => '5x Iced Americano Grande + 1x Caramel Macchiato Artisan Syrup.',
                        'bundle_price' => 920.0000,
                        'tax_code' => 'VAT12_OUT',
                        'duration_mode' => 'ALWAYS',
                        'is_active' => true,
                        'created_by' => 'System Seed',
                    ]
                );

                if ($bundle2->wasRecentlyCreated) {
                    foreach ([['item' => $americano, 'qty' => 5], ['item' => $syrup, 'qty' => 1]] as $comp) {
                        SalesBundleItem::create([
                            'bundle_item_id' => (string) Str::uuid(),
                            'bundle_id' => $bundle2->bundle_id,
                            'inventory_item_id' => $comp['item']->id,
                            'sku' => $comp['item']->sku,
                            'item_name' => $comp['item']->name,
                            'quantity' => $comp['qty'],
                            'uom' => $comp['item']->uom ?: 'PCS',
                        ]);
                    }
                }
            }
        }

        // 4. Seed Sample Unified Order-to-Cash Documents (1 Sent Quote + 1 Approved Sales Order)
        $year = date('Y');
        $quoteNo = "SQ-{$year}-0001";
        $sampleQuote = SalesOrder::firstOrCreate(
            ['company_id' => $companyId, 'order_number' => $quoteNo],
            [
                'sales_order_id' => (string) Str::uuid(),
                'quote_number' => $quoteNo,
                'branch_id' => $branchId,
                'source_warehouse_id' => 'WH-MAIN',
                'source_warehouse_name' => 'Main Commissary Warehouse',
                'customer_id' => $prospect->customer_id,
                'customer_name' => $prospect->customer_name,
                'customer_trade_name' => $prospect->trade_name,
                'customer_tin' => $prospect->tin,
                'customer_contact' => $prospect->contact_person,
                'customer_email' => $prospect->email,
                'customer_phone' => $prospect->phone,
                'billing_address' => $prospect->address,
                'shipping_address' => $prospect->shipping_address,
                'customer_po_number' => 'INQ-ALABANG-01',
                'order_date' => now()->toDateString(),
                'requested_delivery_date' => now()->addDays(5)->toDateString(),
                'valid_until_date' => now()->addDays(14)->toDateString(),
                'payment_terms_code' => 'NET30',
                'currency_code' => 'PHP',
                'exchange_rate' => 1.000000,
                'subtotal_amount' => 10600.0000,
                'discount_type' => 'NONE',
                'discount_value' => 0.0000,
                'discount_amount' => 0.0000,
                'tax_amount' => 1272.0000,
                'gross_total' => 11872.0000,
                'total_cost_snapshot' => 3200.0000,
                'balance_due' => 11872.0000,
                'payment_status' => 'UNBILLED',
                'fulfillment_status' => 'UNFULFILLED',
                'credit_review_status' => 'NOT_EVALUATED',
                'document_stage' => 'QUOTE',
                'status' => 'SENT',
                'revision_no' => 1,
                'salesperson_name' => 'Corporate Sales Desk',
                'special_notes' => 'Initial banquet tasting quotation for Alabang Executive Suites.',
                'created_by' => 'System Seed',
            ]
        );

        if ($sampleQuote->wasRecentlyCreated && $pasta && $latte) {
            SalesOrderLine::create([
                'sales_order_line_id' => (string) Str::uuid(),
                'sales_order_id' => $sampleQuote->sales_order_id,
                'line_number' => 1,
                'inventory_item_id' => $pasta->id,
                'sku' => $pasta->sku,
                'item_name' => $pasta->name,
                'uom' => $pasta->uom ?: 'Plate',
                'qty_ordered' => 20,
                'unit_cost_snapshot' => (float) ($pasta->cost_price ?: 115),
                'unit_price' => 380.0000,
                'tax_code' => 'VAT12_OUT',
                'tax_rate_pct' => 12.0000,
                'tax_amount' => 912.0000,
                'line_subtotal' => 7600.0000,
                'line_total' => 8512.0000,
            ]);
            SalesOrderLine::create([
                'sales_order_line_id' => (string) Str::uuid(),
                'sales_order_id' => $sampleQuote->sales_order_id,
                'line_number' => 2,
                'inventory_item_id' => $latte->id,
                'sku' => $latte->sku,
                'item_name' => $latte->name,
                'uom' => $latte->uom ?: 'Cup',
                'qty_ordered' => 20,
                'unit_cost_snapshot' => (float) ($latte->cost_price ?: 45),
                'unit_price' => 150.0000,
                'tax_code' => 'VAT12_OUT',
                'tax_rate_pct' => 12.0000,
                'tax_amount' => 360.0000,
                'line_subtotal' => 3000.0000,
                'line_total' => 3360.0000,
            ]);
        }

        $orderNo = "SLS-{$year}-0001";
        $sampleOrder = SalesOrder::firstOrCreate(
            ['company_id' => $companyId, 'order_number' => $orderNo],
            [
                'sales_order_id' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'source_warehouse_id' => 'WH-MAIN',
                'source_warehouse_name' => 'Main Commissary Warehouse',
                'customer_id' => $cafePartner->customer_id,
                'customer_name' => $cafePartner->customer_name,
                'customer_trade_name' => $cafePartner->trade_name,
                'customer_tin' => $cafePartner->tin,
                'customer_contact' => $cafePartner->contact_person,
                'customer_email' => $cafePartner->email,
                'customer_phone' => $cafePartner->phone,
                'billing_address' => $cafePartner->address,
                'shipping_address' => $cafePartner->shipping_address,
                'customer_po_number' => 'BCO-PO-2026-118',
                'order_date' => now()->toDateString(),
                'requested_delivery_date' => now()->addDays(2)->toDateString(),
                'payment_terms_code' => 'COD',
                'currency_code' => 'PHP',
                'exchange_rate' => 1.000000,
                'subtotal_amount' => 4200.0000,
                'discount_type' => 'NONE',
                'discount_value' => 0.0000,
                'discount_amount' => 0.0000,
                'tax_amount' => 504.0000,
                'gross_total' => 4704.0000,
                'total_cost_snapshot' => 2900.0000,
                'balance_due' => 4704.0000,
                'payment_status' => 'UNBILLED',
                'fulfillment_status' => 'UNFULFILLED',
                'credit_review_status' => 'AUTO_CLEARED',
                'credit_limit_snapshot' => 50000.0000,
                'credit_exposure_snapshot' => 4704.0000,
                'credit_breach_amount' => 0.0000,
                'document_stage' => 'ORDER',
                'status' => 'APPROVED',
                'confirmed_at' => now(),
                'confirmed_by' => 'System Seed',
                'salesperson_name' => 'B2B Account Executive',
                'special_notes' => 'COD barista syrup replenishment order — ready for warehouse dispatch.',
                'created_by' => 'System Seed',
            ]
        );

        if ($sampleOrder->wasRecentlyCreated && $syrup) {
            SalesOrderLine::create([
                'sales_order_line_id' => (string) Str::uuid(),
                'sales_order_id' => $sampleOrder->sales_order_id,
                'line_number' => 1,
                'inventory_item_id' => $syrup->id,
                'sku' => $syrup->sku,
                'item_name' => $syrup->name,
                'uom' => $syrup->uom ?: 'Bottle',
                'qty_ordered' => 10,
                'unit_cost_snapshot' => (float) ($syrup->cost_price ?: 290),
                'unit_price' => 420.0000,
                'tax_code' => 'VAT12_OUT',
                'tax_rate_pct' => 12.0000,
                'tax_amount' => 504.0000,
                'line_subtotal' => 4200.0000,
                'line_total' => 4704.0000,
            ]);
        }

        // Advance SQ and SORD numbering series past seeded 0001 so new documents never collide
        if (Schema::hasTable('acct_numbering_series')) {
            foreach (['SQ' => 2, 'SORD' => 2] as $moduleCode => $minNext) {
                $series = AcctNumberingSeries::where('company_id', $companyId)
                    ->where('module_code', $moduleCode)
                    ->first();
                if ($series && (int) $series->next_number < $minNext) {
                    $series->next_number = $minNext;
                    $series->save();
                }
            }
        }
    }
}

