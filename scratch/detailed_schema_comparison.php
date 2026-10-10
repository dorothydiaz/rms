<?php

$excelTables = json_decode(file_get_contents('scratch/excel_tables.json'), true);
$db = new PDO('sqlite:database/database.sqlite');

$comparison = [];

// Mapping hypotheses between current tables and excel tables
$currentToExcel = [
    'procurement_vendors' => 'vendor_master',
    'inventory_items' => 'item_master',
    'inventory_categories' => 'item_categories',
    'bill_of_materials' => 'bom_header',
    'bill_of_materials_items' => 'bom_lines',
    'goods_receipts' => 'stock_in_header',
    'goods_receipt_items' => 'stock_in_lines',
    'stock_out_orders' => 'stock_out_header',
    'stock_out_items' => 'stock_out_lines',
    'internal_transfers' => 'stock_transfer_header',
    'internal_transfer_items' => 'stock_transfer_lines',
    'production_orders' => 'work_order_header',
    'production_order_items' => 'work_order_components',
    'purchase_orders' => 'purchase_order_header',
    'purchase_order_items' => 'purchase_order_lines',
    'waste_records' => 'stock_waste_header',
    'waste_record_items' => 'stock_waste_lines',
    'stock_ledger' => 'inventory_ledger',
    'hr_companies' => 'companies',
    'hr_branches' => 'branches',
];

echo "=== MAPPING CURRENT TABLES TO EXCEL REVISED TABLES ===\n";
foreach ($currentToExcel as $current => $excel) {
    $existsInDb = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$current'")->fetchColumn();
    $existsInExcel = isset($excelTables[$excel]);

    // get db columns
    $dbCols = [];
    if ($existsInDb) {
        $colsQuery = $db->query("PRAGMA table_info($current)");
        while ($col = $colsQuery->fetch(PDO::FETCH_ASSOC)) {
            $dbCols[] = $col['name'];
        }
    }

    $excelCols = [];
    if ($existsInExcel) {
        foreach ($excelTables[$excel]['columns'] as $c) {
            $excelCols[] = $c['col_name'];
        }
    }

    echo sprintf("%-28s -> %-28s | DB Cols: %2d | Excel Cols: %2d\n",
        $current, $excel, count($dbCols), count($excelCols));
}

echo "\n=== EXCEL TABLES NOT IN CURRENT DB (NEW MODULES) ===\n";
$mappedExcelTables = array_values($currentToExcel);
foreach ($excelTables as $tName => $tData) {
    if (!in_array($tName, $mappedExcelTables)) {
        echo sprintf(" - %-28s (%2d columns) : %s\n", $tName, count($tData['columns']), substr($tData['purpose'], 0, 70));
    }
}
