<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dbName = DB::getDatabaseName();

// 1. Get all foreign key constraints from information_schema
$fkRows = DB::select("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = ? 
      AND REFERENCED_TABLE_NAME IS NOT NULL
", [$dbName]);

$foreignKeys = [];
foreach ($fkRows as $row) {
    $foreignKeys[$row->TABLE_NAME][$row->COLUMN_NAME] = [
        'ref_table' => $row->REFERENCED_TABLE_NAME,
        'ref_column' => $row->REFERENCED_COLUMN_NAME,
    ];
}

// 2. Get all column details from information_schema
$colRows = DB::select("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        COLUMN_KEY, 
        DATA_TYPE, 
        IS_NULLABLE 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = ? 
      AND TABLE_NAME LIKE 'hr_%'
    ORDER BY TABLE_NAME, ORDINAL_POSITION
", [$dbName]);

$tableColumns = [];
foreach ($colRows as $row) {
    $tableColumns[$row->TABLE_NAME][$row->COLUMN_NAME] = [
        'key' => $row->COLUMN_KEY,
        'type' => $row->DATA_TYPE,
        'nullable' => $row->IS_NULLABLE,
    ];
}

echo "Found " . count($tableColumns) . " HR tables in information_schema.\n";
echo "Found " . count($fkRows) . " explicit FK constraints.\n";

file_put_contents('scratch/db_fk_dump.json', json_encode([
    'foreign_keys' => $foreignKeys,
    'table_columns' => $tableColumns,
], JSON_PRETTY_PRINT));
