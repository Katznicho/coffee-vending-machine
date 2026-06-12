<?php

use App\Jobs\RefundOrderJob;
use App\Models\CellulantSetting;
use App\Models\Machine;
use App\Models\Order;
use App\Support\MachineSignature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    CellulantSetting::query()->delete();
    CellulantSetting::current();

    Http::fake([
        'accounts.sandbox.tingg.africa/*' => Http::response([
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'access_token' => 'test-access-token',
        ], 200),
        'accounts.tingg.africa/*' => Http::response([
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'access_token' => 'test-access-token',
        ], 200),
        'payments-instore.sandbox.tingg.africa/*' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => ['merchantTransactionID' => '998877'],
        ], 200),
        'payments.instore.tingg.africa/*' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => ['merchantTransactionID' => '998877'],
        ], 200),
        '*' => Http::response(['status' => 'accepted'], 200),
    ]);

    $this->machine = Machine::create([
        'machine_id' => 'CM001',
        'name' => 'Test Machine',
        'secret_key' => 'test-secret',
        'status' => 'active',
    ]);
});

function machineOrderPayload(Machine $machine, array $overrides = []): array
{
    $timestamp = now()->format('YmdHis');
    $randstr = 'abcd1234';

    return array_merge([
        'orderId' => 'ORD123',
        'machineId' => $machine->machine_id,
        'product' => 'Cappuccino',
        'amount' => 5000,
        'phoneNumber' => '256771234567',
        'randstr' => $randstr,
        'timestamp' => $timestamp,
        'sign' => MachineSignature::generate($machine->secret_key, $timestamp, $randstr),
    ], $overrides);
}

test('create order initiates cellulant payment and returns pending', function () {
    $response = $this->postJson('/api/vending/create-order', machineOrderPayload($this->machine));

    $response->assertOk()
        ->assertJson([
            'status' => 'PENDING',
            'transactionId' => 'ORD123',
        ]);

    $this->assertDatabaseHas('orders', [
        'machine_order_id' => 'ORD123',
        'machine_id' => 'CM001',
        'amount' => 5000,
        'payment_status' => 'pending',
        'customer_phone' => '256771234567',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'initiateMerchantPayment')
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request['msisdn'] === '256771234567'
            && $request['amount'] === 5000
            && $request['reference'] === 'ORD123';
    });
});

test('payment status returns paid false while pending', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD999',
        'third_party_order_id' => 'RF-TEST-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    $this->postJson('/api/vending/payment-status', [
        'transactionId' => 'ORD999',
    ])
        ->assertOk()
        ->assertJson(['paid' => false]);
});

test('cellulant ipn marks order as paid', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD555',
        'third_party_order_id' => 'RF-TEST-002',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    $this->postJson('/api/cellulant/ipn', [
        'merchantTransactionID' => 'ORD555',
        'reference' => 'ORD555',
        'statusCode' => 140,
    ])
        ->assertOk()
        ->assertJson([
            'statusCode' => '188',
            'merchantTransactionID' => 'ORD555',
        ]);

    expect($order->fresh()->payment_status)->toBe('paid');

    $this->postJson('/api/vending/payment-status', [
        'transactionId' => 'ORD555',
    ])->assertJson(['paid' => true]);
});

test('cellulant ipn matches order by reference field', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD777',
        'third_party_order_id' => 'RF-TEST-004',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    $this->postJson('/api/cellulant/ipn', [
        'merchantTransactionID' => '998877',
        'reference' => 'ORD777',
        'statusCode' => 140,
    ])->assertOk();

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('dispense failure dispatches refund for paid order', function () {
    Queue::fake();

    $order = Order::create([
        'machine_order_id' => 'ORD888',
        'third_party_order_id' => 'RF-TEST-003',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'paid',
        'dispense_status' => 'pending',
        'paid_at' => now(),
    ]);

    $this->postJson('/api/vending/dispense-result', [
        'transactionId' => 'ORD888',
        'status' => 'FAILED',
    ])->assertOk()->assertJson(['status' => 'RECEIVED']);

    expect($order->fresh()->dispense_status)->toBe('failed');
    Queue::assertPushed(RefundOrderJob::class);
});
