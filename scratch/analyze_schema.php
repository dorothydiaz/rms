<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);

echo "=== SCHEMA SHEET ===\n";
$schemaRows = $data['SCHEMA'] ?? [];
$header = [];
$rowCount = 0;
foreach ($schemaRows as $rIdx => $cols) {
    if ($rowCount < 50) {
        echo "Row $rIdx: " . json_encode($cols) . "\n";
    }
    $rowCount++;
}

echo "\nTotal rows in SCHEMA: " . count($schemaRows) . "\n";

echo "\n=== APPROVAL MATRIX SHEET ===\n";
$matrixRows = $data['Approval Matrix'] ?? [];
$rowCount = 0;
foreach ($matrixRows as $rIdx => $cols) {
    if ($rowCount < 30) {
        echo "Row $rIdx: " . json_encode($cols) . "\n";
    }
    $rowCount++;
}
echo "\nTotal rows in Approval Matrix: " . count($matrixRows) . "\n";
