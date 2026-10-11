<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

function renameColumnDirect($tableName, $oldCol, $newCol) {
    $colInfo = DB::selectOne("
        SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ", [$tableName, $oldCol]);

    if (!$colInfo) {
        return false;
    }

    $type = $colInfo->COLUMN_TYPE;
    $nullable = $colInfo->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    
    $default = '';
    if ($colInfo->COLUMN_DEFAULT !== null) {
        if (strtoupper($colInfo->COLUMN_DEFAULT) === 'CURRENT_TIMESTAMP' || strtoupper($colInfo->COLUMN_DEFAULT) === 'CURRENT_TIMESTAMP()') {
            $default = 'DEFAULT CURRENT_TIMESTAMP';
        } elseif (is_numeric($colInfo->COLUMN_DEFAULT)) {
            $default = "DEFAULT {$colInfo->COLUMN_DEFAULT}";
        } else {
            // Strip any surrounding single quotes if present
            $defVal = trim($colInfo->COLUMN_DEFAULT, "'");
            $default = "DEFAULT " . DB::getPdo()->quote($defVal);
        }
    } elseif ($colInfo->IS_NULLABLE === 'YES') {
        $default = 'DEFAULT NULL';
    }

    $extra = $colInfo->EXTRA;
    if ($extra) {
        $extra = " " . $extra;
    }

    $sql = "ALTER TABLE `{$tableName}` CHANGE COLUMN `{$oldCol}` `{$newCol}` {$type} {$nullable} {$default}{$extra}";
    DB::statement($sql);
    return true;
}

try {
    echo "Testing direct rename on hr_attendance_corrections.status...\n";
    renameColumnDirect('hr_attendance_corrections', 'status', 'attendance_correction_status');
    echo "Direct rename SUCCESS!\n";
    renameColumnDirect('hr_attendance_corrections', 'attendance_correction_status', 'status');
    echo "Restored back SUCCESS!\n";
} catch (\Throwable $e) {
    echo "Direct rename FAILED: " . $e->getMessage() . "\n";
}
