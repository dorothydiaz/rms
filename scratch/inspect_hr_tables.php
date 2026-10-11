<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$prop = "Tables_in_" . $dbName;

$hrTables = [];
foreach ($tables as $t) {
    $tableName = $t->$prop;
    if (str_starts_with($tableName, 'hr_')) {
        $cols = Schema::getColumnListing($tableName);
        $hrTables[$tableName] = $cols;
    }
}

file_put_contents('scratch/current_hr_columns.json', json_encode($hrTables, JSON_PRETTY_PRINT));
echo "Saved " . count($hrTables) . " hr_ tables to scratch/current_hr_columns.json\n";
