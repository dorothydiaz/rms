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

echo "=== Testing POST actions ===\n";

// 1. Stock Out confirm ship
try {
    $req = Request::create('/inventory/api/stock-out/confirm-ship', 'POST', ['order_id' => 1]);
    $res = $controller->apiConfirmShipStockOut($req);
    echo "1. apiConfirmShipStockOut: " . json_encode($res->getData(true)) . "\n";
} catch (\Throwable $e) {
    echo "1. apiConfirmShipStockOut FAILED: " . $e->getMessage() . "\n";
}

// 2. Internal Transfer dispatch
try {
    $req = Request::create('/inventory/api/internal-transfer/dispatch', 'POST', ['transfer_id' => 1]);
    $res = $controller->apiDispatchTransfer($req);
    echo "2. apiDispatchTransfer: " . json_encode($res->getData(true)) . "\n";
} catch (\Throwable $e) {
    echo "2. apiDispatchTransfer FAILED: " . $e->getMessage() . "\n";
}

// 3. Production create batch
try {
    $req = Request::create('/inventory/api/production/create-batch', 'POST', ['bill_of_materials_id' => 1, 'planned_quantity' => 1]);
    $res = $controller->apiCreateProductionOrder($req);
    echo "3. apiCreateProductionOrder: " . json_encode($res->getData(true)) . "\n";
} catch (\Throwable $e) {
    echo "3. apiCreateProductionOrder FAILED: " . $e->getMessage() . "\n";
}

// 4. Waste create
try {
    $req = Request::create('/inventory/api/waste/create', 'POST', [
        'items' => [['inventory_item_id' => 1, 'quantity' => 1, 'disposal_reason' => 'Spoilage']]
    ]);
    $res = $controller->apiCreateWasteRecord($req);
    echo "4. apiCreateWasteRecord: " . json_encode($res->getData(true)) . "\n";
} catch (\Throwable $e) {
    echo "4. apiCreateWasteRecord FAILED: " . $e->getMessage() . "\n";
}
