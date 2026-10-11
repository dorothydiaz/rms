<?php

$hr = json_decode(file_get_contents('scratch/current_hr_columns.json'), true);
foreach ($hr as $tbl => $cols) {
    echo "$tbl (" . count($cols) . " columns):\n";
    echo "  " . implode(', ', $cols) . "\n\n";
}
