<?php

$files = [
    'app/Models/Purchase/ProcurementVendor.php',
    'app/Models/Purchase/PurchaseOrder.php',
    'app/Models/Purchase/PurchaseOrderItem.php',
    'app/Models/Inventory/InventoryCategory.php',
    'app/Models/Inventory/InventoryItem.php',
    'app/Models/Inventory/BillOfMaterials.php',
    'app/Models/Inventory/BillOfMaterialsItem.php',
    'app/Models/Inventory/GoodsReceipt.php',
    'app/Models/Inventory/GoodsReceiptItem.php',
    'app/Models/Inventory/StockOutOrder.php',
    'app/Models/Inventory/StockOutOrderItem.php',
    'app/Models/Inventory/InternalTransfer.php',
    'app/Models/Inventory/InternalTransferItem.php',
    'app/Models/Inventory/ProductionOrder.php',
    'app/Models/Inventory/ProductionOrderItem.php',
    'app/Models/Inventory/WasteRecord.php',
    'app/Models/Inventory/WasteRecordItem.php',
    'app/Models/Inventory/StockLedger.php',
];

foreach ($files as $f) {
    $content = file_get_contents($f);
    if (preg_match("/protected \\\$table = '([^']+)';/", $content, $m)) {
        echo "$f => {$m[1]}\n";
    } else {
        echo "$f => NO TABLE DEFINED\n";
    }
}
