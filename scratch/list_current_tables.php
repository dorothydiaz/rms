<?php

$db = new PDO('sqlite:database/database.sqlite');
$query = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
$currentTables = $query->fetchAll(PDO::FETCH_COLUMN);

echo "Current database tables in Laravel (" . count($currentTables) . "):\n";
sort($currentTables);
foreach ($currentTables as $t) {
    echo " - $t\n";
}

file_put_contents('scratch/current_tables.json', json_encode($currentTables, JSON_PRETTY_PRINT));
