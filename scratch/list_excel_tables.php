<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$schemaRows = $data['SCHEMA'] ?? [];
$tables = [];
$currentTable = '';
foreach ($schemaRows as $cols) {
    $type = trim($cols['B'] ?? '');
    $name = trim($cols['A'] ?? '');
    if ($type === 'TABLE') {
        $currentTable = $name;
        $tables[$currentTable] = [];
    } elseif (!empty($currentTable) && !empty($name)) {
        $tables[$currentTable][] = $name;
    }
}
echo "Total tables in Excel: " . count($tables) . PHP_EOL;
foreach ($tables as $t => $cols) {
    echo $t . " (" . count($cols) . " cols)" . PHP_EOL;
}
