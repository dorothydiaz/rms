<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $r1 = view('inventory.recipe-management', ['initialBoms' => collect(), 'products' => collect()])->render();
    echo 'Recipe Management rendered OK (len ' . strlen($r1) . ')' . PHP_EOL;
} catch (\Throwable $e) {
    echo 'Recipe Management Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}

try {
    $r2 = view('inventory.stocks-overview', ['initialProducts' => collect(), 'initialCategories' => collect(), 'initialLedger' => collect()])->render();
    echo 'Stocks Overview rendered OK (len ' . strlen($r2) . ')' . PHP_EOL;
} catch (\Throwable $e) {
    echo 'Stocks Overview Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}

try {
    $r3 = view('inventory.production', [
        'initialOrders' => collect(),
        'initialBoms' => collect(),
        'initialProducts' => collect(),
        'initialCategories' => collect(),
        'completedBatches' => 0,
        'totalUnitsProduced' => 0,
        'totalProductionValuation' => 0,
        'avgYieldEfficiency' => 100
    ])->render();
    echo 'Production rendered OK (len ' . strlen($r3) . ')' . PHP_EOL;
} catch (\Throwable $e) {
    echo 'Production Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
