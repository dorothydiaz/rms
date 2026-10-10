<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\InventoryController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$controller = app(InventoryController::class);
$request = Request::create('/', 'GET');

$pages = [
    'dashboard' => fn() => $controller->dashboard(),
    'stocksOverview' => fn() => $controller->stocksOverview($request),
    'begBalance' => fn() => $controller->begBalance(),
    'stockIn' => fn() => $controller->stockIn($request),
    'stockOut' => fn() => $controller->stockOut($request),
    'internalTransfer' => fn() => $controller->internalTransfer($request),
    'production' => fn() => $controller->production($request),
    'stockAdjustment' => fn() => $controller->stockAdjustment(),
    'wasteExpiry' => fn() => $controller->wasteExpiry($request),
    'productCategories' => fn() => $controller->productCategories($request),
    'recipeManagement' => fn() => $controller->recipeManagement($request),
    // APIs
    'apiGetStockInData' => fn() => $controller->apiGetStockInData($request),
    'apiGetStockOutData' => fn() => $controller->apiGetStockOutData($request),
    'apiGetInternalTransferData' => fn() => $controller->apiGetInternalTransferData($request),
    'apiGetProductionData' => fn() => $controller->apiGetProductionData($request),
    'apiGetWasteExpiryData' => fn() => $controller->apiGetWasteExpiryData($request),
    'apiGetStocksOverviewData' => fn() => $controller->apiGetStocksOverviewData($request),
];

echo "=== Testing Inventory Controller Pages & APIs ===\n";
foreach ($pages as $name => $fn) {
    try {
        $response = $fn();
        if ($response instanceof \Illuminate\View\View) {
            $html = $response->render();
            echo "✓ $name (View rendered successfully, size: " . strlen($html) . " bytes)\n";
        } elseif ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            echo "✓ $name (API returned 200, success: " . ($data['success'] ?? 'n/a') . ")\n";
        } else {
            echo "✓ $name (Returned response)\n";
        }
    } catch (\Throwable $e) {
        echo "✗ $name FAILED: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
