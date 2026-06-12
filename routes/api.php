<?php

use App\Http\Controllers\CellulantIpnController;
use App\Http\Controllers\Vending\CreateOrderController;
use App\Http\Controllers\Vending\DeliveryResultController;
use App\Http\Controllers\Vending\DispenseResultController;
use App\Http\Controllers\Vending\PaymentStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('vending')->group(function () {
    Route::post('/create-order', CreateOrderController::class)->middleware('machine.verify');
    Route::post('/payment-status', PaymentStatusController::class);
    Route::post('/dispense-result', DispenseResultController::class);
    Route::post('/delivery-result', DeliveryResultController::class);
});

Route::post('/cellulant/ipn', CellulantIpnController::class)->name('cellulant.ipn');
