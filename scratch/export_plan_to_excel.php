<?php

$plan = json_decode(file_get_contents('scratch/full_hr_schema_key_plan.json'), true);

$headers = [
    'Table Name',
    'Module',
    'Sub-Module',
    'Tab / Feature',
    'Column Header (New)',
    'Previous Header',
    'Data Type',
    'Key Type',
    'Source / Origin',
    'Purpose',
];

$rows = [];
foreach ($plan as $tableName => $columns) {
    foreach ($columns as $c) {
        $rows[] = [
            $tableName,
            $c['module'] ?? 'HR Operations',
            $c['submodule'] ?? 'General',
            $c['tab'] ?? 'Overview',
            $c['proposed_column'],
            $c['original_column'],
            $c['data_type'],
            $c['key_type'],
            $c['source'],
            $c['purpose'] ?? '-',
        ];
    }
}

echo "Prepared " . count($rows) . " rows for export.\n";

// Function to generate XLSX
function buildXlsx($outputPath, $headers, $rows) {
    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception("Unable to create zip at $outputPath");
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="HR Column Rename Plan" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

    // Styles: Header (1), Normal (2), PK bold (3), FK italic (4)
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><name val="Segoe UI"/><sz val="10"/></font>
    <font><b/><name val="Segoe UI"/><sz val="11"/><color rgb="FFFFFFFF"/></font>
    <font><b/><name val="Segoe UI"/><sz val="10"/><color rgb="FF0F766E"/></font>
    <font><i/><name val="Segoe UI"/><sz val="10"/><color rgb="FF1D4ED8"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="5">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
  </cellXfs>
</styleSheet>');

    $colLetters = ['A','B','C','D','E','F','G','H','I','J'];
    $totalRows = count($rows) + 1;

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetViews>
    <sheetView tabSelected="1" workbookViewId="0">
      <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
    </sheetView>
  </sheetViews>
  <cols>
    <col min="1" max="1" width="28" customWidth="1"/>
    <col min="2" max="2" width="24" customWidth="1"/>
    <col min="3" max="3" width="28" customWidth="1"/>
    <col min="4" max="4" width="36" customWidth="1"/>
    <col min="5" max="5" width="32" customWidth="1"/>
    <col min="6" max="6" width="24" customWidth="1"/>
    <col min="7" max="7" width="14" customWidth="1"/>
    <col min="8" max="8" width="18" customWidth="1"/>
    <col min="9" max="9" width="32" customWidth="1"/>
    <col min="10" max="10" width="55" customWidth="1"/>
  </cols>
  <sheetData>';

    // Header row
    $sheetXml .= '<row r="1" spans="1:10">';
    foreach ($headers as $idx => $h) {
        $cRef = $colLetters[$idx] . '1';
        $safeH = htmlspecialchars($h, ENT_XML1, 'UTF-8');
        $sheetXml .= '<c r="' . $cRef . '" t="inlineStr" s="1"><is><t>' . $safeH . '</t></is></c>';
    }
    $sheetXml .= '</row>';

    // Rows
    $rNum = 2;
    foreach ($rows as $row) {
        $sheetXml .= '<row r="' . $rNum . '" spans="1:10">';
        foreach ($row as $idx => $val) {
            $cRef = $colLetters[$idx] . $rNum;
            $safeVal = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');

            $styleId = 2; // normal
            if ($idx === 4) { // proposed column
                if (str_contains($row[7], 'PRIMARY KEY')) $styleId = 3;
                elseif (str_contains($row[7], 'FOREIGN KEY')) $styleId = 4;
            }

            $sheetXml .= '<c r="' . $cRef . '" t="inlineStr" s="' . $styleId . '"><is><t>' . $safeVal . '</t></is></c>';
        }
        $sheetXml .= '</row>';
        $rNum++;
    }

    $sheetXml .= '  </sheetData>
  <autoFilter ref="A1:J' . $totalRows . '"/>
</worksheet>';

    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
}

// Function to generate CSV with UTF-8 BOM
function buildCsv($outputPath, $headers, $rows) {
    $fp = fopen($outputPath, 'w');
    // Add UTF-8 BOM so Excel opens with proper encoding
    fputs($fp, "\xEF\xBB\xBF");
    fputcsv($fp, $headers);
    foreach ($rows as $r) {
        fputcsv($fp, $r);
    }
    fclose($fp);
}

// Target paths
$targets = [
    'local_xlsx' => 'hr_database_rename_plan.xlsx',
    'local_csv' => 'hr_database_rename_plan.csv',
    'downloads_xlsx' => 'd:/Downloads/hr_database_rename_plan.xlsx',
    'downloads_csv' => 'd:/Downloads/hr_database_rename_plan.csv',
];

buildXlsx($targets['local_xlsx'], $headers, $rows);
buildCsv($targets['local_csv'], $headers, $rows);

if (is_dir('d:/Downloads')) {
    copy($targets['local_xlsx'], $targets['downloads_xlsx']);
    copy($targets['local_csv'], $targets['downloads_csv']);
}

echo "Successfully exported spreadsheet files:\n";
foreach ($targets as $label => $path) {
    if (file_exists($path)) {
        echo " - [$label]: $path (" . number_format(filesize($path)) . " bytes)\n";
    }
}
