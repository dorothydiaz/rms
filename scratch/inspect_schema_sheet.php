<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$schemaRows = $data['SCHEMA'] ?? [];

echo "SCHEMA sheet rows count: " . count($schemaRows) . "\n";
echo "First 15 rows of SCHEMA:\n";
$i = 0;
foreach ($schemaRows as $rIdx => $cols) {
    echo "Row $rIdx:\n";
    foreach ($cols as $col => $val) {
        if (trim($val) !== '') {
            echo "  [$col] => " . substr($val, 0, 100) . "\n";
        }
    }
    $i++;
    if ($i >= 15) break;
}
