<?php
$plan = json_decode(file_get_contents('scratch/full_hr_schema_key_plan.json'), true);
echo sprintf("%-30s | %-25s | %-25s | %-40s\n", "Table", "Module", "Sub-Module", "Tab / Feature");
echo str_repeat("-", 125) . "\n";
foreach ($plan as $t => $cols) {
    $c = $cols[0];
    echo sprintf("%-30s | %-25s | %-25s | %-40s\n", $t, $c['module'], $c['submodule'], $c['tab']);
}
