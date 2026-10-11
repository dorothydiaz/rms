<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    DB::statement("ALTER TABLE hr_attendance_corrections CHANGE status attendance_correction_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");
    echo "CHANGE WORKED PERFECTLY!\n";
    DB::statement("ALTER TABLE hr_attendance_corrections CHANGE attendance_correction_status status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");
    echo "RESTORED BACK!\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
