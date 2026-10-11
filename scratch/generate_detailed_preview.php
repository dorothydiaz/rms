<?php

$plan = json_decode(file_get_contents('scratch/full_hr_schema_key_plan.json'), true);

$md = "# HR Database Column Rename Plan & Entity Relationship Specification\n\n";
$md .= "This document provides the full structural specification of the proposed column renames across all **46 HR tables**, clearly indicating **Primary Keys (PK)**, **Foreign Keys (FK) and their source tables**, **Standard Attributes**, and **Audit Columns**.\n\n";
$md .= "- **Migration File Created**: [`database/migrations/2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php`](file:///c:/xampp/htdocs/rms/database/migrations/2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php)\n";
$md .= "- **Total Tables Covered**: " . count($plan) . "\n";
$totalCols = 0;
$totalFks = 0;
foreach ($plan as $cols) {
    $totalCols += count($cols);
    foreach ($cols as $c) {
        if (str_contains($c['key_type'], 'FOREIGN KEY')) $totalFks++;
    }
}
$md .= "- **Total Columns Listed**: " . $totalCols . "\n";
$md .= "- **Foreign Key Relationships Identified**: " . $totalFks . "\n";
$md .= "- **Status**: Draft Migration & Specification Ready for Review (Un-run)\n\n";
$md .= "---\n\n";

foreach ($plan as $tableName => $columns) {
    $firstCol = $columns[0] ?? [];
    $moduleName = $firstCol['module'] ?? 'HR Operations';
    $submoduleName = $firstCol['submodule'] ?? 'General';
    $tabName = $firstCol['tab'] ?? 'Overview';

    $md .= "### `" . $tableName . "` (" . count($columns) . " columns)\n\n";
    $md .= "> **Module:** `" . $moduleName . "` &nbsp; | &nbsp; **Sub-Module:** `" . $submoduleName . "` &nbsp; | &nbsp; **Tab / Feature:** `" . $tabName . "`\n\n";
    $md .= "| Column Header (New) | Previous Header | Data Type | Key / Origin | Used In (Module & Tab) | Purpose |\n";
    $md .= "| :--- | :--- | :---: | :--- | :--- | :--- |\n";

    foreach ($columns as $c) {
        $keyBadge = $c['key_type'];
        if ($keyBadge === 'PRIMARY KEY (PK)') {
            $keyBadge = '🔑 **PK**';
        } elseif ($keyBadge === 'FOREIGN KEY (FK)') {
            $keyBadge = '🔗 **FK**';
        } elseif ($keyBadge === 'AUDIT TIMESTAMP') {
            $keyBadge = '🕒 Audit';
        } else {
            $keyBadge = 'Attr';
        }

        $sourceText = $c['source'];
        if ($sourceText !== '-') {
            $sourceText = '`' . $sourceText . '`';
        } else {
            $sourceText = 'Local';
        }

        $originCol = $keyBadge . " (" . $sourceText . ")";

        $purposeText = $c['purpose'] ?? '-';
        $tabLocation = ($c['submodule'] ?? $submoduleName) . ' &rarr; ' . ($c['tab'] ?? $tabName);

        $md .= "| `**" . $c['proposed_column'] . "**` | `" . $c['original_column'] . "` | `" . $c['data_type'] . "` | " . $originCol . " | " . $tabLocation . " | " . $purposeText . " |\n";
    }

    $md .= "\n";
}

file_put_contents('C:/Users/Dorothy/.gemini/antigravity-ide/brain/38139bc9-fa5f-4427-8340-399b192fb9cf/hr_column_renames_preview.md', $md);
echo "Successfully updated hr_column_renames_preview.md with modules and tabs.\n";
