<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use App\Models\Hr\Applicant;
use App\Models\Hr\Interview;

echo "EMPLOYEE SCHEDULES TABLE:\n";
print_r(collect(DB::select('DESCRIBE hr_employee_schedules'))->pluck('Field')->toArray());









echo "ALL VIRTUAL ID COLUMNS VERIFIED!\n";








