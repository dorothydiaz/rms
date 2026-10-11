<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = DB::select('SHOW TABLES LIKE "hr_%"');
$tableNames = [];
foreach ($tables as $t) {
    $tableNames[] = array_values((array)$t)[0];
}

echo "Found " . count($tableNames) . " HR tables.\n";
$cmd = 'C:\\xampp\\mysql\\bin\\mysqldump.exe -u root rms_db ' . implode(' ', $tableNames) . ' > scratch/rms_hr_tables_backup.sql';
exec($cmd, $output, $returnCode);

if ($returnCode === 0) {
    echo "Successfully created backup at scratch/rms_hr_tables_backup.sql (" . filesize('scratch/rms_hr_tables_backup.sql') . " bytes)\n";
} else {
    echo "Backup failed with code: $returnCode\n";
}
