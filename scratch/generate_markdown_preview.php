<?php

$renames = json_decode(file_get_contents('scratch/proposed_hr_column_renames.json'), true);

$md = "# HR Database Column Rename Mapping Proposal\n\n";
$md .= "This document outlines the proposed entity-prefixed column renames for all **46 HR tables** in alignment with the enterprise schema naming standard.\n\n";
$md .= "- **Migration File Created**: [`2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php`](file:///c:/xampp/htdocs/rms/database/migrations/2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php)\n";
$md .= "- **Total Tables Affected**: " . count($renames) . "\n";
$totalCols = array_sum(array_map('count', $renames));
$md .= "- **Total Columns to Rename**: " . $totalCols . "\n";
$md .= "- **Status**: Draft migration generated, pending user verification before running.\n\n";
$md .= "---\n\n";

foreach ($renames as $table => $cols) {
    $md .= "### `" . $table . "` (" . count($cols) . " columns)\n\n";
    $md .= "| Current Column | Proposed New Column |\n";
    $md .= "| :--- | :--- |\n";
    foreach ($cols as $old => $new) {
        $md .= "| `" . $old . "` | `**" . $new . "**` |\n";
    }
    $md .= "\n";
}

file_put_contents('C:/Users/Dorothy/.gemini/antigravity-ide/brain/38139bc9-fa5f-4427-8340-399b192fb9cf/hr_column_renames_preview.md', $md);
echo "Generated C:/Users/Dorothy/.gemini/antigravity-ide/brain/38139bc9-fa5f-4427-8340-399b192fb9cf/hr_column_renames_preview.md\n";
