<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$schemaRows = $data['SCHEMA'] ?? [];

$results = [];
$currentTable = '';

foreach ($schemaRows as $rIdx => $cols) {
    $type = trim($cols['B'] ?? '');
    $name = trim($cols['A'] ?? '');
    $changeType = trim($cols['T'] ?? '');
    $prevHeader = trim($cols['U'] ?? '');
    $changeNote = trim($cols['V'] ?? '');

    if ($type === 'TABLE') {
        $currentTable = $name;
    }

    if (!empty($changeType) || !empty($prevHeader) || !empty($changeNote)) {
        $results[] = [
            'row' => $rIdx,
            'table' => $currentTable,
            'field' => $name,
            'type' => $type,
            'change_type' => $changeType,
            'prev_header' => $prevHeader,
            'change_note' => $changeNote,
        ];
    }
}

echo "Total rows with Change metadata: " . count($results) . "\n\n";

$byChangeType = [];
foreach ($results as $r) {
    $ct = $r['change_type'] ?: 'EMPTY';
    $byChangeType[$ct] = ($byChangeType[$ct] ?? 0) + 1;
}
echo "Breakdown by Change Type:\n";
print_r($byChangeType);

echo "\nSample rows with previous headers:\n";
$count = 0;
foreach ($results as $r) {
    if (!empty($r['prev_header']) && $r['prev_header'] !== 'N/A') {
        echo sprintf("Table: %-25s | New: %-30s | Prev: %-30s | Type: %s | Note: %s\n",
            $r['table'], $r['field'], $r['prev_header'], $r['change_type'], substr($r['change_note'], 0, 40));
        $count++;
        if ($count >= 40) break;
    }
}
