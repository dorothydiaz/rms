<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$views = [
    'procurement_vendors'     => 'vendor_master',
    'inventory_categories'    => 'item_categories',
    'inventory_items'         => 'item_master',
    'bill_of_materials'       => 'bom_header',
    'bill_of_materials_items' => 'bom_lines',
    'goods_receipts'          => 'stock_in_header',
    'goods_receipt_items'     => 'stock_in_lines',
    'stock_out_orders'        => 'stock_out_header',
    'stock_out_items'         => 'stock_out_lines',
    'internal_transfers'      => 'stock_transfer_header',
    'internal_transfer_items' => 'stock_transfer_lines',
    'production_orders'       => 'work_order_header',
    'production_order_items'  => 'work_order_components',
    'purchase_orders'         => 'purchase_order_header',
    'purchase_order_items'    => 'purchase_order_lines',
    'waste_records'           => 'stock_waste_header',
    'waste_record_items'      => 'stock_waste_lines',
    'stock_ledger'            => 'inventory_ledger',
];

echo "=== Creating compatibility views ===\n";
foreach ($views as $old => $new) {
    try {
        DB::statement("DROP VIEW IF EXISTS \"$old\"");
        DB::statement("CREATE VIEW \"$old\" AS SELECT * FROM \"$new\"");
        $count = DB::table($old)->count();
        echo "✓ View $old -> $new works! (Row count: $count)\n";
    } catch (\Throwable $e) {
        echo "✗ Error creating view $old: " . $e->getMessage() . "\n";
    }
}
