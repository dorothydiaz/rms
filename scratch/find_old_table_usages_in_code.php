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

$dirs = ['app', 'resources/views', 'routes'];

function scanDirRecursive($dir, &$results, $oldTables) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            scanDirRecursive($path, $results, $oldTables);
        } elseif (preg_match('/\.(php|blade\.php)$/', $path)) {
            $content = file_get_contents($path);
            foreach ($oldTables as $t) {
                if (strpos($content, $t) !== false) {
                    // find line numbers
                    $lines = explode("\n", $content);
                    foreach ($lines as $lineNum => $lineContent) {
                        if (strpos($lineContent, $t) !== false) {
                            $results[] = [
                                'file' => $path,
                                'line' => $lineNum + 1,
                                'table' => $t,
                                'snippet' => trim($lineContent),
                            ];
                        }
                    }
                }
            }
        }
    }
}

$results = [];
foreach ($dirs as $d) {
    scanDirRecursive($d, $results, $oldTables);
}

echo "Found " . count($results) . " occurrences of old table names:\n";
foreach ($results as $r) {
    echo "{$r['file']}:{$r['line']} [{$r['table']}] => {$r['snippet']}\n";
}
