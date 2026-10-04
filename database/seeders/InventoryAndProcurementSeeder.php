<?php

namespace Database\Seeders;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryAndProcurementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Categories
        $categories = [
            ['name' => 'Raw Ingredients', 'slug' => 'raw-ingredients', 'code' => 'RAW', 'badge_color' => 'purple', 'description' => 'Unprocessed meats, produce, grains, and kitchen cooking bases.'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'code' => 'BEV', 'badge_color' => 'sky', 'description' => 'Espresso specialties, cold teas, crafted beverages.'],
            ['name' => 'Main Course', 'slug' => 'main-course', 'code' => 'MNC', 'badge_color' => 'amber', 'description' => 'Plated pasta, entrees, and prepared specialty meals.'],
            ['name' => 'Pastries & Desserts', 'slug' => 'pastries-desserts', 'code' => 'PST', 'badge_color' => 'pink', 'description' => 'Freshly baked viennoiserie, cakes, and treats.'],
            ['name' => 'Packaging & Disposables', 'slug' => 'packaging-disposables', 'code' => 'PKG', 'badge_color' => 'indigo', 'description' => 'Kraft takeout boxes, cups, straws, napkins.'],
            ['name' => 'Syrups & Flavors', 'slug' => 'syrups-flavors', 'code' => 'SYR', 'badge_color' => 'teal', 'description' => 'Flavoring syrups, sauces, and cocktail bases.']
        ];

        foreach ($categories as $cat) {
            InventoryCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }

        // 2. Seed Vendors
        $vendors = [
            [
                'vendor_code' => 'VND-SAN-002',
                'legal_name' => 'San Miguel Pure Foods Company Inc.',
                'trade_name' => 'San Miguel Foods',
                'category' => 'Meats & Poultry Supplier',
                'contact_person' => 'Patricia Lim',
                'email' => 'orders.foodservice@sanmiguel.com.ph',
                'phone' => '+63 917 882 1044',
                'address' => '40 San Miguel Ave, Mandaluyong City',
                'tax_id' => '000-128-492-000',
                'payment_terms' => 'Net 30 Days',
                'sourcing_channel' => 'commercial'
            ],
            [
                'vendor_code' => 'WET-MKT-001',
                'legal_name' => 'Aling Nena Fresh Vegetables & Spices',
                'trade_name' => 'Balintawak Central Wholesale Stall #42',
                'category' => 'Fresh Produce & Wet Market',
                'contact_person' => 'Elena Rodriguez',
                'email' => 'elena.rodriguez.stall42@gmail.com',
                'phone' => '+63 928 441 9923',
                'address' => 'Stall 42, Section C, Balintawak Wholesale Market, QC',
                'tax_id' => '192-837-102-000',
                'payment_terms' => 'Cash on Delivery (COD)',
                'sourcing_channel' => 'wet_market'
            ],
            [
                'vendor_code' => 'VND-BAR-005',
                'legal_name' => 'Barista Essentials Distribution Corp',
                'trade_name' => 'Barista Depot PH',
                'category' => 'Beverage & Coffee Supply',
                'contact_person' => 'Marco Santos',
                'email' => 'sales@baristadepot.ph',
                'phone' => '+63 908 552 3819',
                'address' => '12 Pioneer Street, Pasig City',
                'tax_id' => '241-998-112-000',
                'payment_terms' => 'Net 15 Days',
                'sourcing_channel' => 'commercial'
            ],
            [
                'vendor_code' => 'VND-ECO-009',
                'legal_name' => 'EcoPack Solutions & Paper Products Corp',
                'trade_name' => 'EcoPack Packaging',
                'category' => 'Food Service Disposables',
                'contact_person' => 'Grace Tan',
                'email' => 'gtan@ecopacksolutions.ph',
                'phone' => '+63 917 339 2881',
                'address' => 'Industrial Valley Complex, Cainta, Rizal',
                'tax_id' => '332-109-887-000',
                'payment_terms' => 'Net 30 Days',
                'sourcing_channel' => 'commercial'
            ]
        ];

        foreach ($vendors as $v) {
            ProcurementVendor::updateOrCreate(['vendor_code' => $v['vendor_code']], $v);
        }

        // 3. Seed Products / Inventory Items
        $items = [
            [
                'sku' => 'RAW-308',
                'barcode' => '4800030800012',
                'name' => 'US Choice Ribeye Beef Primal',
                'category' => 'Raw Ingredients',
                'description' => 'Grain-Fed Chilled Steer Cut for Steaks and Roasts',
                'uom' => 'Kg',
                'cost_price' => 840.00,
                'selling_price' => 0.00,
                'current_stock' => 15.00,
                'min_stock' => 10.00,
                'max_stock' => 50.00,
                'storage_location' => 'Meat Chiller Walk-In A'
            ],
            [
                'sku' => 'RAW-309',
                'barcode' => '4800030900019',
                'name' => 'Pork Belly Skin-On Slab',
                'category' => 'Raw Ingredients',
                'description' => 'Fresh Local Triple-A Liempo Cut for Crispy Pork Belly',
                'uom' => 'Kg',
                'cost_price' => 340.00,
                'selling_price' => 0.00,
                'current_stock' => 20.00,
                'min_stock' => 15.00,
                'max_stock' => 80.00,
                'storage_location' => 'Meat Chiller Walk-In A'
            ],
            [
                'sku' => 'RAW-VEG-01',
                'barcode' => '4800091000021',
                'name' => 'Fresh Native Red Onions (Sibuyas Tagalog)',
                'category' => 'Raw Ingredients',
                'description' => 'Class A Firm Native Red Onions',
                'uom' => 'Kg',
                'cost_price' => 140.00,
                'selling_price' => 0.00,
                'current_stock' => 30.00,
                'min_stock' => 20.00,
                'max_stock' => 100.00,
                'storage_location' => 'Produce Cold Room B'
            ],
            [
                'sku' => 'RAW-VEG-02',
                'barcode' => '4800091000038',
                'name' => 'Native Garlic (Bawang)',
                'category' => 'Raw Ingredients',
                'description' => 'Sun-Dried Ilocos Native Garlic Cloves',
                'uom' => 'Kg',
                'cost_price' => 180.00,
                'selling_price' => 0.00,
                'current_stock' => 18.00,
                'min_stock' => 10.00,
                'max_stock' => 50.00,
                'storage_location' => 'Dry Store Room A'
            ],
            [
                'sku' => 'RAW-COFFEE-01',
                'barcode' => '4800058291028',
                'name' => 'Espresso Roast Beans (1kg)',
                'category' => 'Raw Ingredients',
                'description' => '100% Arabica Medium Dark Roast Blend',
                'uom' => 'Kg',
                'cost_price' => 650.00,
                'selling_price' => 0.00,
                'current_stock' => 25.00,
                'min_stock' => 10.00,
                'max_stock' => 60.00,
                'storage_location' => 'Coffee & Bar Dry Storage'
            ],
            [
                'sku' => 'BEV-001',
                'barcode' => '4800019283710',
                'name' => 'Signature Spanish Latte',
                'category' => 'Beverages',
                'description' => 'Espresso Specialty with Condensed Milk Blend',
                'uom' => 'Cup',
                'cost_price' => 45.00,
                'selling_price' => 150.00,
                'current_stock' => 40.00,
                'min_stock' => 20.00,
                'max_stock' => 80.00,
                'storage_location' => 'Main Barista Station'
            ],
            [
                'sku' => 'BEV-002',
                'barcode' => '4800028391028',
                'name' => 'Iced Americano Grande',
                'category' => 'Beverages',
                'description' => 'Double shot espresso over iced purified water',
                'uom' => 'Cup',
                'cost_price' => 28.00,
                'selling_price' => 120.00,
                'current_stock' => 35.00,
                'min_stock' => 15.00,
                'max_stock' => 60.00,
                'storage_location' => 'Main Barista Station'
            ],
            [
                'sku' => 'MNC-101',
                'barcode' => '4800049281048',
                'name' => 'Truffle Mushroom Pasta',
                'category' => 'Main Course',
                'description' => 'Artisan Fettuccine in White Truffle Cream Sauce',
                'uom' => 'Plate',
                'cost_price' => 115.00,
                'selling_price' => 380.00,
                'current_stock' => 15.00,
                'min_stock' => 10.00,
                'max_stock' => 40.00,
                'storage_location' => 'Hot Kitchen Line'
            ],
            [
                'sku' => 'PKG-501',
                'barcode' => '4800067192039',
                'name' => 'Kraft Takeout Box (Medium)',
                'category' => 'Packaging & Disposables',
                'description' => 'Eco-friendly biodegradable food grade kraft box',
                'uom' => 'Piece',
                'cost_price' => 8.50,
                'selling_price' => 15.00,
                'current_stock' => 250.00,
                'min_stock' => 100.00,
                'max_stock' => 500.00,
                'storage_location' => 'Dry Warehouse - Packaging Section'
            ],
            [
                'sku' => 'SYR-601',
                'barcode' => '4800078291039',
                'name' => 'Caramel Macchiato Artisan Syrup',
                'category' => 'Syrups & Flavors',
                'description' => 'Premium 750ml glass bottle syrup',
                'uom' => 'Bottle',
                'cost_price' => 290.00,
                'selling_price' => 420.00,
                'current_stock' => 12.00,
                'min_stock' => 8.00,
                'max_stock' => 25.00,
                'storage_location' => 'Barista Dry Rack B'
            ]
        ];

        foreach ($items as $itemData) {
            $item = InventoryItem::updateOrCreate(['sku' => $itemData['sku']], $itemData);

            // Create initial opening balance ledger entry if not exists
            if (!StockLedger::where('sku', $item->sku)->exists()) {
                StockLedger::create([
                    'transaction_uuid' => (string) Str::uuid(),
                    'sku' => $item->sku,
                    'inventory_item_id' => $item->id,
                    'item_name' => $item->name,
                    'transaction_type' => 'STOCK_IN',
                    'reference_type' => 'opening_balance',
                    'reference_id' => $item->id,
                    'reference_no' => 'BEG-BAL-2026',
                    'before_quantity' => 0.00,
                    'quantity_change' => $item->current_stock,
                    'after_quantity' => $item->current_stock,
                    'unit_cost' => $item->cost_price,
                    'total_value' => round($item->current_stock * $item->cost_price, 2),
                    'batch_lot_no' => 'LOT-INIT-001',
                    'storage_location' => $item->storage_location,
                    'performed_by' => 'system@rms.internal',
                    'notes' => 'Opening Inventory Balance verification',
                    'created_at' => now()->subDays(5)
                ]);
            }
        }

        // 4. Seed Purchase Orders
        $pos = [
            [
                'po_number' => 'PO-2026-0101',
                'po_type' => 'vendor',
                'rfq_reference' => 'RFQ-2026-0038',
                'vendor_id' => 'VND-SAN-002',
                'vendor_name' => 'San Miguel Pure Foods Company Inc.',
                'vendor_trade_name' => 'San Miguel Foods',
                'vendor_contact_person' => 'Patricia Lim',
                'vendor_phone' => '+63 917 882 1044',
                'vendor_email' => 'orders.foodservice@sanmiguel.com.ph',
                'vendor_address' => '40 San Miguel Ave, Mandaluyong City',
                'order_date' => now()->subDays(3)->toDateString(),
                'expected_delivery' => now()->addDays(2)->toDateString(),
                'delivery_location' => 'Central Commissary - Receiving Dock 1',
                'payment_status' => 'Unpaid / Credit',
                'payment_method' => 'Trade Credit (Net 30/15)',
                'amount_paid' => 0.00,
                'balance_due' => 34600.00,
                'status' => 'Approved / Issued',
                'subtotal' => 34600.00,
                'tax_amount' => 0.00,
                'gross_total' => 34600.00,
                'created_by_user' => 'Procurement Officer',
                'items' => [
                    [
                        'sku' => 'RAW-308',
                        'item_name' => 'US Choice Ribeye Beef Primal',
                        'specs' => 'Grain-Fed Chilled Steer Cut',
                        'category' => 'Raw Ingredients',
                        'uom' => 'Kg',
                        'quantity' => 25.00,
                        'unit_price' => 840.00,
                        'total_amount' => 21000.00,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING'
                    ],
                    [
                        'sku' => 'RAW-309',
                        'item_name' => 'Pork Belly Skin-On Slab',
                        'specs' => 'Fresh Local Triple-A Liempo Cut',
                        'category' => 'Raw Ingredients',
                        'uom' => 'Kg',
                        'quantity' => 40.00,
                        'unit_price' => 340.00,
                        'total_amount' => 13600.00,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING'
                    ]
                ]
            ],
            [
                'po_number' => 'PO-2026-0102',
                'po_type' => 'wet_market',
                'rfq_reference' => 'None (Direct Market Purchase)',
                'vendor_id' => 'WET-MKT-001',
                'vendor_name' => 'Aling Nena Fresh Vegetables & Spices',
                'vendor_trade_name' => 'Balintawak Central Wholesale Stall #42',
                'vendor_contact_person' => 'Elena Rodriguez',
                'vendor_phone' => '+63 928 441 9923',
                'vendor_email' => 'elena.rodriguez.stall42@gmail.com',
                'vendor_address' => 'Stall 42, Balintawak Wholesale Market, QC',
                'order_date' => now()->subDays(2)->toDateString(),
                'expected_delivery' => now()->addDays(1)->toDateString(),
                'delivery_location' => 'Central Commissary - Receiving Dock 1',
                'payment_status' => 'Unpaid / Credit',
                'payment_method' => 'Cash on Delivery (COD)',
                'amount_paid' => 0.00,
                'balance_due' => 10600.00,
                'status' => 'Approved / Issued',
                'subtotal' => 10600.00,
                'tax_amount' => 0.00,
                'gross_total' => 10600.00,
                'created_by_user' => 'Kitchen Head Chef',
                'items' => [
                    [
                        'sku' => 'RAW-VEG-01',
                        'item_name' => 'Fresh Native Red Onions (Sibuyas Tagalog)',
                        'specs' => 'Grade A Farm Fresh',
                        'category' => 'Raw Ingredients',
                        'uom' => 'Kg',
                        'quantity' => 50.00,
                        'unit_price' => 140.00,
                        'total_amount' => 7000.00,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING'
                    ],
                    [
                        'sku' => 'RAW-VEG-02',
                        'item_name' => 'Native Garlic (Bawang)',
                        'specs' => 'Ilocos White Garlic',
                        'category' => 'Raw Ingredients',
                        'uom' => 'Kg',
                        'quantity' => 20.00,
                        'unit_price' => 180.00,
                        'total_amount' => 3600.00,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING'
                    ]
                ]
            ],
            [
                'po_number' => 'PO-2026-0103',
                'po_type' => 'vendor',
                'rfq_reference' => 'RFQ-2026-0041',
                'vendor_id' => 'VND-BAR-005',
                'vendor_name' => 'Barista Essentials Distribution Corp',
                'vendor_trade_name' => 'Barista Depot PH',
                'vendor_contact_person' => 'Marco Santos',
                'vendor_phone' => '+63 908 552 3819',
                'vendor_email' => 'sales@baristadepot.ph',
                'vendor_address' => '12 Pioneer Street, Pasig City',
                'order_date' => now()->subDays(1)->toDateString(),
                'expected_delivery' => now()->addDays(3)->toDateString(),
                'delivery_location' => 'Central Commissary - Receiving Dock 1',
                'payment_status' => 'Unpaid / Credit',
                'payment_method' => 'Trade Credit (Net 30/15)',
                'amount_paid' => 0.00,
                'balance_due' => 22400.00,
                'status' => 'Partially Received',
                'subtotal' => 22400.00,
                'tax_amount' => 0.00,
                'gross_total' => 22400.00,
                'created_by_user' => 'Beverage Supervisor',
                'items' => [
                    [
                        'sku' => 'RAW-COFFEE-01',
                        'item_name' => 'Espresso Roast Beans (1kg)',
                        'specs' => '100% Arabica Medium Dark Roast',
                        'category' => 'Raw Ingredients',
                        'uom' => 'Kg',
                        'quantity' => 30.00,
                        'unit_price' => 650.00,
                        'total_amount' => 19500.00,
                        'received_quantity' => 10.00,
                        'status' => 'PARTIAL'
                    ],
                    [
                        'sku' => 'SYR-601',
                        'item_name' => 'Caramel Macchiato Artisan Syrup',
                        'specs' => '750ml Artisan Bottle',
                        'category' => 'Syrups & Flavors',
                        'uom' => 'Bottle',
                        'quantity' => 10.00,
                        'unit_price' => 290.00,
                        'total_amount' => 2900.00,
                        'received_quantity' => 0.00,
                        'status' => 'PENDING'
                    ]
                ]
            ]
        ];

        foreach ($pos as $poData) {
            $itemsData = $poData['items'];
            unset($poData['items']);

            $po = PurchaseOrder::updateOrCreate(['po_number' => $poData['po_number']], $poData);

            foreach ($itemsData as $it) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po->id, 'sku' => $it['sku']],
                    $it
                );
            }
        }
    }
}
