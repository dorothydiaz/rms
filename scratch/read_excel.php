<?php

$file = 'Software_Logics_Revised.xlsx';
$zip = new ZipArchive();
if ($zip->open($file) !== true) {
    die("Failed to open $file\n");
}

// 1. Get relationships
$relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
$rels = simplexml_load_string($relsXml);
$rIdToTarget = [];
foreach ($rels->Relationship as $rel) {
    $rIdToTarget[(string)$rel['Id']] = (string)$rel['Target'];
}

// 2. Get sheets
$workbookXml = $zip->getFromName('xl/workbook.xml');
$workbook = simplexml_load_string($workbookXml);

// 3. Get shared strings
$sharedStrings = [];
$ssXml = $zip->getFromName('xl/sharedStrings.xml');
if ($ssXml) {
    $ssObj = simplexml_load_string($ssXml);
    foreach ($ssObj->si as $si) {
        $text = '';
        if (isset($si->t)) {
            $text = (string)$si->t;
        } elseif (isset($si->r)) {
            foreach ($si->r as $r) {
                $text .= (string)$r->t;
            }
        }
        $sharedStrings[] = $text;
    }
}

function parseSheetXml($xmlStr, $sharedStrings) {
    $sheet = simplexml_load_string($xmlStr);
    $rows = [];
    foreach ($sheet->sheetData->row as $r) {
        $rowIndex = (int)$r['r'];
        $rowData = [];
        foreach ($r->c as $c) {
            $cellRef = (string)$c['r'];
            $colLetter = preg_replace('/[0-9]/', '', $cellRef);
            $type = (string)$c['t'];
            $val = '';
            if (isset($c->v)) {
                $rawVal = (string)$c->v;
                if ($type === 's') {
                    $val = $sharedStrings[(int)$rawVal] ?? '';
                } else {
                    $val = $rawVal;
                }
            } elseif (isset($c->is->t)) {
                $val = (string)$c->is->t;
            }
            $rowData[$colLetter] = $val;
        }
        $rows[$rowIndex] = $rowData;
    }
    return $rows;
}

$allSheetData = [];
foreach ($workbook->sheets->sheet as $s) {
    $name = (string)$s['name'];
    $rId = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
    $target = $rIdToTarget[$rId] ?? '';
    if (strpos($target, 'xl/') === false) {
        $target = 'xl/' . $target;
    }
    $target = ltrim($target, '/');
    $sheetXml = $zip->getFromName($target);
    $allSheetData[$name] = parseSheetXml($sheetXml, $sharedStrings);
}

$zip->close();

file_put_contents('scratch/excel_dump.json', json_encode($allSheetData, JSON_PRETTY_PRINT));
echo "Successfully dumped sheets to scratch/excel_dump.json\n";
