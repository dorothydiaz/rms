<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use Illuminate\Support\Facades\DB;

echo "Seeding Organizational Hierarchy...\n";

// 1. Direct DB updates using exact prefixed column names
// Dorothy Diaz (Summit / GM)
DB::table('hr_employees')->where('employee_id', 1)->update(['employee_supervisor_id' => null]);

// Level 2: Reporting directly to Dorothy Diaz
// Peter Administrator (2), Elena Reyes (3), Carlos Mendoza (4), Rodrigo Santos (5), Beatriz Valdez (11), Eduardo Ramos (14)
DB::table('hr_employees')->whereIn('employee_id', [2, 3, 4, 5, 11, 14])->update(['employee_supervisor_id' => 1]);

// Level 3: Reporting to Carlos Mendoza (Restaurant Manager / Makati Operations)
// Maria Garcia (6 - Cashier Lead / FOH Lead)
DB::table('hr_employees')->where('employee_id', 6)->update(['employee_supervisor_id' => 4]);

// Level 4: Front of House staff reporting to Maria Garcia (Lead)
// John Cashier (7), Sarah Staff (8), Juan Dela Cruz (10), Krizza Navarro (13), Patricia Lim (15)
DB::table('hr_employees')->whereIn('employee_id', [7, 8, 10, 13, 15])->update(['employee_supervisor_id' => 6]);

// Level 4: Back of House kitchen staff reporting to Rodrigo Santos (Head Cook)
// Marco Rossi (9), Gabriel Tan (12), Mark Villanueva (16)
DB::table('hr_employees')->whereIn('employee_id', [9, 12, 16])->update(['employee_supervisor_id' => 5]);

// 2. Department Heads
DB::table('hr_departments')->where('department_name', 'Management')->update(['department_head_employee_id' => 1]);
DB::table('hr_departments')->where('department_name', 'Finance & Admin')->update(['department_head_employee_id' => 3]);
DB::table('hr_departments')->where('department_name', 'Front of House')->update(['department_head_employee_id' => 4]);
DB::table('hr_departments')->where('department_name', 'Back of House')->update(['department_head_employee_id' => 5]);

$supervised = DB::table('hr_employees')->whereNotNull('employee_supervisor_id')->count();
echo "SUCCESS! Supervised employees: {$supervised}/16\n";

$gm = Employee::find(1);
echo "Hierarchy Tree from Dorothy Diaz:\n";
foreach ($gm->subordinates as $sub) {
    echo "  └─ [L2] {$sub->full_name} ({$sub->position?->name}, Dept: " . ($sub->department?->name ?? 'None') . ")\n";
    foreach ($sub->subordinates as $sub2) {
        echo "      └─ [L3] {$sub2->full_name} ({$sub2->position?->name})\n";
        foreach ($sub2->subordinates as $sub3) {
            echo "          └─ [L4] {$sub3->full_name} ({$sub3->position?->name})\n";
        }
    }
}
echo "\nDepartments and Heads:\n";
foreach (Department::with('head')->get() as $d) {
    echo "Dept: {$d->name} -> Head: " . ($d->head?->full_name ?? 'None') . "\n";
}
foreach ($gm->subordinates as $sub) {
    echo "  └─ [Level 2] {$sub->full_name} ({$sub->position?->name}, Dept: {$sub->department?->name})\n";
}
