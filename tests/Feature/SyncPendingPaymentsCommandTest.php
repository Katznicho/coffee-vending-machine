<?php

use App\Models\CellulantSetting;
use App\Models\Machine;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    CellulantSetting::query()->delete();
    CellulantSetting::clearCache();
    CellulantSetting::current();

    $this->machine = Machine::create([
        'machine_id' => 'CM001',
        'name' => 'Test Machine',
        'secret_key' => 'test-secret',
        'status' => 'active',
    ]);
});

test('sync pending payments command marks order paid from cellulant', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'test-access-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'merchants/payments/20448132')) {
            return Http::response([
                'success' => true,
                'data' => [
                    'merchantTransactionID' => '20448132',
                    'payerTransactionID' => 'ORD-CRON-1',
                    'paymentStatus' => 140,
                    'statusDescription' => 'Payment Accepted',
                ],
            ], 200);
        }

        return Http::response(['success' => false], 404);
    });

    $order = Order::create([
        'machine_order_id' => 'ORD-CRON-1',
        'third_party_order_id' => 'RF-CRON-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 1400,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 1400,
        'reference' => 'ORD-CRON-1',
        'provider' => 'AIRTELUG',
        'status' => 'processing',
        'transaction_id' => '20448132',
    ]);

    $this->artisan('payments:sync-pending')
        ->assertSuccessful()
        ->expectsOutputToContain('1 paid');

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('sync pending payments command can be disabled via config', function () {
    config(['vending.sync_pending_payments' => false]);

    $this->artisan('payments:sync-pending')
        ->assertSuccessful()
        ->expectsOutput('Pending payment sync is disabled.');
});
