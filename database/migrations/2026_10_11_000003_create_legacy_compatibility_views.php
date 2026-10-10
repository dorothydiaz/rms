<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy tables mapped to revised enterprise tables.
     */
    protected array $views = [
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

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->views as $old => $new) {
            if (!Schema::hasTable($old) && Schema::hasTable($new)) {
                DB::statement("DROP VIEW IF EXISTS \"$old\"");
                DB::statement("CREATE VIEW \"$old\" AS SELECT * FROM \"$new\"");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_keys($this->views) as $old) {
            DB::statement("DROP VIEW IF EXISTS \"$old\"");
        }
    }
};
