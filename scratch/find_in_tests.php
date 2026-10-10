<?php

$oldTables = [
    'procurement_vendors',
    'inventory_categories',
    'inventory_items',
    'bill_of_materials_items',
    'bill_of_materials',
    'goods_receipt_items',
    'goods_receipts',
    'stock_out_items',
    'stock_out_orders',
    'internal_transfer_items',
    'internal_transfers',
    'production_order_items',
    'production_orders',
    'purchase_order_items',
    'purchase_orders',
    'waste_record_items',
    'waste_records',
    'stock_ledger',
];

$results = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('tests'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);
        foreach ($lines as $idx => $line) {
            foreach ($oldTables as $t) {
                if (strpos($line, $t) !== false) {
                    $results[] = [
                        'file' => $file->getPathname(),
                        'line' => $idx + 1,
                        'table' => $t,
                        'snippet' => trim($line),
                    ];
                }
            }
        }
    }
}

echo "Found " . count($results) . " in tests:\n";
foreach ($results as $r) {
    echo "{$r['file']}:{$r['line']} [{$r['table']}] => {$r['snippet']}\n";
}
