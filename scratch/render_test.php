<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
if ($user) {
    Auth::login($user);
    $controller = app(\App\Http\Controllers\Hr\PeopleController::class);
    $request = Illuminate\Http\Request::create('/hr/people/employees', 'GET');
    $response = $controller->employeesIndex($request);
    $html = $response->render();
    $idx = strpos($html, 'empSearchInput');
    echo "Preceding 1000 chars:\n" . substr($html, $idx - 1000, 1000);
}
