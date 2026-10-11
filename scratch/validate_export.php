<?php
$f = 'd:/Downloads/hr_database_rename_plan.xlsx';
$z = new ZipArchive();
if ($z->open($f) === true) {
    $xml = $z->getFromName('xl/worksheets/sheet1.xml');
    $s = simplexml_load_string($xml);
    echo "XLSX VALID: " . count($s->sheetData->row) . " rows parsed successfully in d:/Downloads/hr_database_rename_plan.xlsx\n";
    $z->close();
} else {
    echo "FAILED TO OPEN\n";
}
