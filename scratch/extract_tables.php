<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$schemaRows = $data['SCHEMA'] ?? [];

$tables = [];
$currentTable = null;

foreach ($schemaRows as $rIdx => $cols) {
    $name = trim($cols['A'] ?? '');
    $type = trim($cols['B'] ?? '');
    $purpose = trim($cols['C'] ?? '');
    $changeType = trim($cols['T'] ?? '');
    $prevHeader = trim($cols['U'] ?? '');
    $changeNote = trim($cols['V'] ?? '');

    if ($type === 'TABLE') {
        $currentTable = [
            'row' => $rIdx,
            'table_name' => $name,
            'purpose' => $purpose,
            'change_type' => $changeType,
            'prev_header' => $prevHeader,
            'change_note' => $changeNote,
            'columns' => []
        ];
        $tables[$name] = &$currentTable;
        unset($currentTable);
    } elseif (!empty($type) && $type !== 'Data Type' && $rIdx > 2) {
        // column
        if (!empty($tables)) {
            $lastTableName = array_key_last($tables);
            $tables[$lastTableName]['columns'][] = [
                'row' => $rIdx,
                'col_name' => $name,
                'data_type' => $type,
                'purpose' => $purpose,
                'change_type' => $changeType,
                'prev_header' => $prevHeader,
                'change_note' => $changeNote
            ];
        }
    }
}

echo "Total tables defined in Excel: " . count($tables) . "\n\n";
foreach ($tables as $tName => $tInfo) {
    echo sprintf(
        "Table: %-35s | ChangeType: %-10s | Prev: %-25s | Cols: %2d\n",
        $tName,
        $tInfo['change_type'] ?: 'N/A',
        $tInfo['prev_header'] ?: 'N/A',
        count($tInfo['columns'])
    );
}

file_put_contents('scratch/excel_tables.json', json_encode($tables, JSON_PRETTY_PRINT));
