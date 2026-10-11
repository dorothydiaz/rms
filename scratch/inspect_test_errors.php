<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hr\Employee;

$testEmpId = 'EMP-TEST-' . rand(10000, 99999);
$emp = new Employee();
$emp->employee_id = $testEmpId;
echo "emp->employee_code: " . ($emp->employee_code ?? 'NULL') . "\n";
echo "emp->employee_id: " . ($emp->employee_id ?? 'NULL') . "\n";


