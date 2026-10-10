<?php

$tableMap = [
    'app/Models/Purchase/ProcurementVendor.php'     => ['procurement_vendors', 'vendor_master'],
    'app/Models/Purchase/PurchaseOrder.php'         => ['purchase_orders', 'purchase_order_header'],
    'app/Models/Purchase/PurchaseOrderItem.php'     => ['purchase_order_items', 'purchase_order_lines'],
    'app/Models/Inventory/InventoryCategory.php'    => ['inventory_categories', 'item_categories'],
    'app/Models/Inventory/InventoryItem.php'        => ['inventory_items', 'item_master'],
    'app/Models/Inventory/BillOfMaterials.php'      => ['bill_of_materials', 'bom_header'],
    'app/Models/Inventory/BillOfMaterialsItem.php'  => ['bill_of_materials_items', 'bom_lines'],
    'app/Models/Inventory/GoodsReceipt.php'         => ['goods_receipts', 'stock_in_header'],
    'app/Models/Inventory/GoodsReceiptItem.php'     => ['goods_receipt_items', 'stock_in_lines'],
    'app/Models/Inventory/StockOutOrder.php'        => ['stock_out_orders', 'stock_out_header'],
    'app/Models/Inventory/StockOutOrderItem.php'    => ['stock_out_items', 'stock_out_lines'],
    'app/Models/Inventory/InternalTransfer.php'     => ['internal_transfers', 'stock_transfer_header'],
    'app/Models/Inventory/InternalTransferItem.php' => ['internal_transfer_items', 'stock_transfer_lines'],
    'app/Models/Inventory/ProductionOrder.php'      => ['production_orders', 'work_order_header'],
    'app/Models/Inventory/ProductionOrderItem.php' => ['production_order_items', 'work_order_components'],
    'app/Models/Inventory/WasteRecord.php'          => ['waste_records', 'stock_waste_header'],
    'app/Models/Inventory/WasteRecordItem.php'      => ['waste_record_items', 'stock_waste_lines'],
    'app/Models/Inventory/StockLedger.php'          => ['stock_ledger', 'inventory_ledger'],
];

foreach ($tableMap as $file => [$oldTable, $newTable]) {
    $content = file_get_contents($file);
    $oldStr = "protected \$table = '$oldTable';";
    $newStr = "protected \$table = '$newTable';";
    if (strpos($content, $oldStr) !== false) {
        $content = str_replace($oldStr, $newStr, $content);
        file_put_contents($file, $content);
        echo "Updated $file to table '$newTable'\n";
    } else {
        echo "Warning: could not find '$oldStr' in $file\n";
    }
}
