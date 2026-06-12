<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CellulantSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MachineIntegrationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::get('/pay/{torderid}', [PaymentPageController::class, 'show'])->name('pay.show');
Route::post('/pay/{torderid}', [PaymentPageController::class, 'pay'])->name('pay.submit');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/quick', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/integration', MachineIntegrationController::class)->name('integration.index');

    Route::get('/machines', [MachineController::class, 'index'])->name('machines.index');
    Route::get('/machines/create', [MachineController::class, 'create'])->name('machines.create');
    Route::post('/machines', [MachineController::class, 'store'])->name('machines.store');
    Route::get('/machines/{machine}/edit', [MachineController::class, 'edit'])->name('machines.edit');
    Route::put('/machines/{machine}', [MachineController::class, 'update'])->name('machines.update');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/settings/cellulant', [CellulantSettingsController::class, 'edit'])->name('settings.cellulant.edit');
    Route::put('/settings/cellulant', [CellulantSettingsController::class, 'update'])->name('settings.cellulant.update');
    Route::post('/settings/cellulant/test-connectivity', [CellulantSettingsController::class, 'testConnectivity'])->name('settings.cellulant.test.connectivity');
    Route::post('/settings/cellulant/test-payment', [CellulantSettingsController::class, 'testPayment'])->name('settings.cellulant.test.payment');
});
