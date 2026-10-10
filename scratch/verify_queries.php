<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\BillOfMaterials;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\StockOutOrder;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\ProductionOrder;
use App\Models\Inventory\WasteRecord;
use App\Models\Inventory\StockLedger;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;

echo "=== Eloquent Query Verifications on Renamed Tables ===\n";

try {
    echo "1. ProcurementVendor (vendor_master): Count = " . ProcurementVendor::count() . "\n";
    echo "2. InventoryCategory (item_categories): Count = " . InventoryCategory::count() . "\n";
    echo "3. InventoryItem (item_master): Count = " . InventoryItem::count() . "\n";
    echo "4. BillOfMaterials (bom_header): Count = " . BillOfMaterials::count() . "\n";
    echo "5. GoodsReceipt (stock_in_header): Count = " . GoodsReceipt::count() . "\n";
    echo "6. StockOutOrder (stock_out_header): Count = " . StockOutOrder::count() . "\n";
    echo "7. InternalTransfer (stock_transfer_header): Count = " . InternalTransfer::count() . "\n";
    echo "8. ProductionOrder (work_order_header): Count = " . ProductionOrder::count() . "\n";
    echo "9. WasteRecord (stock_waste_header): Count = " . WasteRecord::count() . "\n";
    echo "10. StockLedger (inventory_ledger): Count = " . StockLedger::count() . "\n";
    echo "11. PurchaseOrder (purchase_order_header): Count = " . PurchaseOrder::count() . "\n";
    echo "\nAll queries succeeded with ZERO errors!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
