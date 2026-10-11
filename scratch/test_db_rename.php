<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::dropIfExists('test_empty');
Schema::create('test_empty', function (Blueprint $t) {
    $t->id();
});

try {
    Schema::table('test_empty', function (Blueprint $t) {
        // empty closure
    });
    echo "EMPTY CLOSURE SUCCESS!\n";
} catch (\Throwable $e) {
    echo "EMPTY CLOSURE ERROR: " . $e->getMessage() . "\n";
} finally {
    Schema::dropIfExists('test_empty');
}
