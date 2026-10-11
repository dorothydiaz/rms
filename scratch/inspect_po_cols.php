<?php

$d = json_decode(file_get_contents('scratch/excel_dump.json'), true);
$rows = $d['SCHEMA'] ?? [];
$isPO = false;
foreach ($rows as $idx => $r) {
    if (($r['A'] ?? '') === 'purchase_order_header') {
        $isPO = true;
        echo "Found purchase_order_header at row $idx:\n";
        print_r($r);
    } elseif ($isPO && ($r['A'] ?? '') === 'purchase_order_id') {
        echo "purchase_order_id row:\n";
        print_r($r);
        break;
    }
}
