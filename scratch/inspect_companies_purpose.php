<?php

$d = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$rows = $d['SCHEMA'] ?? [];
$isComp = false;
foreach ($rows as $idx => $r) {
    if (($r['B'] ?? '') === 'TABLE') {
        if (($r['A'] ?? '') === 'companies' || ($r['A'] ?? '') === 'branches') {
            $isComp = true;
            echo "=== " . $r['A'] . " ===\n";
        } else {
            $isComp = false;
        }
    } elseif ($isComp && !empty($r['A'])) {
        echo sprintf("%-30s | %-15s | %s\n", $r['A'], $r['B'] ?? '', $r['C'] ?? '');
    }
}
