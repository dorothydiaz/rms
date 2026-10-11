<?php

$d = json_decode(file_get_contents('scratch/excel_dump.json'), true);
echo "Sheets in excel_dump.json: " . implode(', ', array_keys($d)) . "\n";
foreach ($d as $sheetName => $rows) {
    echo "Sheet: $sheetName (" . count($rows) . " rows)\n";
}
