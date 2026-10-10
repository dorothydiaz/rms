<?php
$file = file_get_contents('resources/views/hr/reports/index.blade.php');
echo "Open <div: " . substr_count($file, '<div') . "\n";
echo "Close </div: " . substr_count($file, '</div') . "\n";
