<?php
$files = glob('app/Models/Hr/*.php');
$missing = [];
foreach ($files as $f) {
    $content = file_get_contents($f);
    $hasTrait = str_contains($content, 'HasEntityPrimaryKey');
    if (!$hasTrait) {
        $missing[] = basename($f);
    }
}
echo "Models missing HasEntityPrimaryKey (" . count($missing) . "):\n";
echo implode(", ", $missing) . "\n";
