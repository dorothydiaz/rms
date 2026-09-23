<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\CreditsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Restaurant Management System (RMS)
|--------------------------------------------------------------------------
*/

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Session Termination
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']); // Fallback GET redirect

    // Main Operations Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // HR Operations
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/dashboard', [HrController::class, 'dashboard'])->name('dashboard');
        Route::get('/employee', [HrController::class, 'employee'])->name('employee');
        Route::get('/users-auth', [HrController::class, 'usersAuth'])->name('users-auth');
        Route::get('/attendance-schedule', [HrController::class, 'attendanceSchedule'])->name('attendance-schedule');
        Route::get('/attendance-checkin', [HrController::class, 'attendanceCheckin'])->name('attendance-checkin');
        Route::get('/employee-leave', [HrController::class, 'employeeLeave'])->name('employee-leave');
    });

    // Sales Operations
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/dashboard', [SalesController::class, 'dashboard'])->name('dashboard');
        Route::get('/daily-sales', [SalesController::class, 'dailySales'])->name('daily-sales');
        Route::get('/payment-report', [SalesController::class, 'paymentReport'])->name('payment-report');
        Route::get('/reconciliations', [SalesController::class, 'reconciliations'])->name('reconciliations');
        Route::get('/discount-config', [SalesController::class, 'discountConfig'])->name('discount-config');
        Route::get('/voucher-config', [SalesController::class, 'voucherConfig'])->name('voucher-config');
        Route::get('/bundle-promotions', [SalesController::class, 'bundlePromotions'])->name('bundle-promotions');
        Route::get('/customer-masterlist', [SalesController::class, 'customerMasterlist'])->name('customer-masterlist');
    });

    // Inventory Operations
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/dashboard', [InventoryController::class, 'dashboard'])->name('dashboard');
        Route::get('/stocks-overview', [InventoryController::class, 'stocksOverview'])->name('stocks-overview');
        Route::get('/beg-balance', [InventoryController::class, 'begBalance'])->name('beg-balance');
        Route::get('/stock-in', [InventoryController::class, 'stockIn'])->name('stock-in');
        Route::get('/stock-out', [InventoryController::class, 'stockOut'])->name('stock-out');
        Route::get('/stock-adjustment', [InventoryController::class, 'stockAdjustment'])->name('stock-adjustment');
        Route::get('/waste-expiry', [InventoryController::class, 'wasteExpiry'])->name('waste-expiry');
        Route::get('/product-categories', [InventoryController::class, 'productCategories'])->name('product-categories');
        Route::get('/recipe-management', [InventoryController::class, 'recipeManagement'])->name('recipe-management');
    });

    // Purchase Operations
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/dashboard', [PurchaseController::class, 'dashboard'])->name('dashboard');
        Route::get('/request-quotations', [PurchaseController::class, 'requestQuotations'])->name('request-quotations');
        Route::get('/purchase-orders', [PurchaseController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/vendor-masterlist', [PurchaseController::class, 'vendorMasterlist'])->name('vendor-masterlist');
        Route::get('/vendor-bills', [PurchaseController::class, 'vendorBills'])->name('vendor-bills');
    });

    // Business Configuration
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/business-settings', [ConfigController::class, 'businessSettings'])->name('business-settings');
        Route::get('/account-settings', [ConfigController::class, 'accountSettings'])->name('account-settings');
    });

    // Credits & Support
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/tickets', [CreditsController::class, 'tickets'])->name('tickets');
        Route::get('/developers', [CreditsController::class, 'developers'])->name('developers');
    });
});
