<?php

$data = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$rows = $data['SCHEMA'] ?? [];
$isComp = false;
foreach ($rows as $r) {
    if (($r['B'] ?? '') === 'TABLE') {
        if (($r['A'] ?? '') === 'companies' || ($r['A'] ?? '') === 'branches') {
            $isComp = true;
            echo "=== TABLE " . $r['A'] . " ===\n";
        } else {
            $isComp = false;
        }
    } elseif ($isComp && !empty($r['A'])) {
        echo sprintf("Col: %-30s | Type: %-15s | Prev: %-25s | Note: %s\n",
            $r['A'], $r['B'] ?? '', $r['U'] ?? '', $r['V'] ?? '');
    }
}
