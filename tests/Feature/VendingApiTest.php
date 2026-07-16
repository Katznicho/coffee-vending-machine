<?php

use App\Jobs\RefundOrderJob;
use App\Models\CellulantSetting;
use App\Models\Machine;
use App\Models\CellulantIpnLog;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Models\Payment;
use App\Support\MachineSignature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    CellulantSetting::query()->delete();
    CellulantSetting::clearCache();
    CellulantSetting::current();
    \Illuminate\Support\Facades\Cache::flush();

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
        'payments-instore.sandbox.tingg.africa/initiateMerchantPayment' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => ['merchantTransactionID' => '998877'],
        ], 200),
        'payments.instore.tingg.africa/initiateMerchantPayment' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => ['merchantTransactionID' => '998877'],
        ], 200),
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

test('create order without phone returns payment qr url', function () {
    $payload = machineOrderPayload($this->machine, [
        'ver' => 'v1',
        'orderid' => 'ORD123',
        'machid' => $this->machine->machine_id,
        'name' => 'Cappuccino',
        'price' => 500000,
        'phoneNumber' => null,
    ]);
    unset($payload['orderId'], $payload['machineId'], $payload['product'], $payload['amount'], $payload['phoneNumber']);

    $response = $this->postJson('/api/vending/create-order', $payload);

    $order = Order::where('machine_order_id', 'ORD123')->first();

    $response->assertOk()
        ->assertJson([
            'code' => '1',
            'orderid' => 'ORD123',
            'torderid' => $order->third_party_order_id,
            'msg' => 'Success',
            'twocode' => route('pay.show', $order->third_party_order_id),
        ]);

    $this->assertDatabaseHas('orders', [
        'machine_order_id' => 'ORD123',
        'machine_id' => 'CM001',
        'amount' => 5000,
        'payment_status' => 'pending',
        'customer_phone' => null,
    ]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'initiateMerchantPayment'));
});

test('machine price is converted from protocol units to ugx', function () {
    $payload = machineOrderPayload($this->machine, [
        'ver' => 'v1',
        'orderid' => 'ORD-SCALED-PRICE',
        'machid' => $this->machine->machine_id,
        'name' => 'Hot Chocolate',
        'price' => 1100000,
        'phoneNumber' => null,
    ]);
    unset($payload['orderId'], $payload['machineId'], $payload['product'], $payload['amount'], $payload['phoneNumber']);

    $this->postJson('/api/vending/create-order', $payload)->assertOk();

    $this->assertDatabaseHas('orders', [
        'machine_order_id' => 'ORD-SCALED-PRICE',
        'amount' => 11000,
    ]);
});

test('pay page can retry payment without duplicate reference crash', function () {
    Http::fake([
        'accounts.sandbox.tingg.africa/*' => Http::response([
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'access_token' => 'test-access-token',
        ], 200),
        'payments-instore.sandbox.tingg.africa/initiateMerchantPayment' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => ['merchantTransactionID' => '998877'],
        ], 200),
    ]);

    $order = Order::create([
        'machine_order_id' => 'ORD-RETRY-1',
        'third_party_order_id' => 'RF-RETRY-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Hot Chocolate',
        'amount' => 6000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256706326308',
        'amount' => 6000,
        'reference' => 'ORD-RETRY-1',
        'provider' => 'AIRTELUG',
        'status' => 'failed',
    ]);

    $provider = app(\App\Services\PaymentProviders\PaymentProviderInterface::class);
    $payment = $provider->initiateCollection($order, '0706326308');

    expect($payment->status)->toBe('processing')
        ->and($payment->reference)->toBe('ORD-RETRY-1')
        ->and(Payment::where('reference', 'ORD-RETRY-1')->count())->toBe(1);
});

test('create order with phone initiates cellulant payment and returns pending', function () {
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

test('create order recycles completed order when machine reuses order id', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD-REUSE-1',
        'third_party_order_id' => 'RF-OLD-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 5000,
        'payment_status' => 'paid',
        'dispense_status' => 'success',
        'paid_at' => now()->subMinute(),
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 5000,
        'reference' => 'ORD-REUSE-1',
        'provider' => 'AIRTELUG',
        'status' => 'successful',
    ]);

    $payload = machineOrderPayload($this->machine, [
        'ver' => 'v1',
        'orderid' => 'ORD-REUSE-1',
        'machid' => $this->machine->machine_id,
        'name' => 'Hot Chocolate',
        'price' => 600000,
        'phoneNumber' => null,
    ]);
    unset($payload['orderId'], $payload['machineId'], $payload['product'], $payload['amount'], $payload['phoneNumber']);

    $response = $this->postJson('/api/vending/create-order', $payload)->assertOk();

    $order->refresh();

    expect($order->payment_status)->toBe('pending')
        ->and($order->dispense_status)->toBe('pending')
        ->and($order->product_name)->toBe('Hot Chocolate')
        ->and($order->amount)->toBe(6000)
        ->and($order->third_party_order_id)->not->toBe('RF-OLD-001')
        ->and(Payment::where('reference', 'ORD-REUSE-1')->exists())->toBeFalse();

    $response->assertJsonPath('code', '1')
        ->assertJsonPath('orderid', 'ORD-REUSE-1');
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

test('payment status creates integration log entry', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD-LOG-1',
        'third_party_order_id' => 'RF-LOG-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    $this->postJson('/api/vending/payment-status', [
        'transactionId' => 'ORD-LOG-1',
    ])->assertOk();

    $log = IntegrationLog::where('event', 'payment_status')->first();

    expect($log)->not->toBeNull()
        ->and($log->channel)->toBe('vending_api')
        ->and($log->direction)->toBe('inbound')
        ->and($log->order_id)->toBe($order->id);
});

test('create order duplicate checks cellulant status before responding', function () {
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
                    'payerTransactionID' => 'ORD-DUP-1',
                    'paymentStatus' => 140,
                    'statusDescription' => 'Payment Accepted',
                ],
            ], 200);
        }

        return Http::response(['success' => false], 404);
    });

    $order = Order::create([
        'machine_order_id' => 'ORD-DUP-1',
        'third_party_order_id' => 'RF-DUP-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 1400,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 1400,
        'reference' => 'ORD-DUP-1',
        'provider' => 'AIRTELUG',
        'status' => 'processing',
        'transaction_id' => '20448132',
    ]);

    $this->postJson('/api/vending/create-order', machineOrderPayload($this->machine, [
        'orderId' => 'ORD-DUP-1',
        'amount' => 1400,
    ]))
        ->assertOk()
        ->assertJson([
            'status' => 'PAID',
            'transactionId' => 'ORD-DUP-1',
            'paid' => true,
        ]);

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('admin can check order status with cellulant', function () {
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
                    'payerTransactionID' => 'ORD-ADMIN-1',
                    'paymentStatus' => 140,
                    'statusDescription' => 'Payment Accepted',
                ],
            ], 200);
        }

        return Http::response(['success' => false], 404);
    });

    $admin = \App\Models\User::factory()->create(['is_admin' => true]);

    $order = Order::create([
        'machine_order_id' => 'ORD-ADMIN-1',
        'third_party_order_id' => 'RF-ADMIN-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 1400,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 1400,
        'reference' => 'ORD-ADMIN-1',
        'provider' => 'AIRTELUG',
        'status' => 'processing',
        'transaction_id' => '20448132',
    ]);

    $this->actingAs($admin)
        ->post(route('orders.check-status', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHas('success');

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('payment status syncs from cellulant single payment lookup when ipn is missing', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'test-access-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'merchants/payments/20448132')) {
            return Http::response([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Operation successful',
                'data' => [
                    'merchantTransactionID' => '20448132',
                    'payerTransactionID' => 'ORD-SYNC-1',
                    'paymentStatus' => 140,
                    'statusDescription' => 'Payment Accepted',
                    'amountPaid' => 1400,
                    'msisdn' => '256759983853',
                ],
            ], 200);
        }

        return Http::response(['success' => false], 404);
    });

    $order = Order::create([
        'machine_order_id' => 'ORD-SYNC-1',
        'third_party_order_id' => 'RF-SYNC-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 1400,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 1400,
        'reference' => 'ORD-SYNC-1',
        'provider' => 'AIRTELUG',
        'status' => 'processing',
        'transaction_id' => '20448132',
    ]);

    $this->postJson('/api/vending/payment-status', [
        'transactionId' => 'ORD-SYNC-1',
    ])
        ->assertOk()
        ->assertJson([
            'paid' => true,
            'status' => 'PAID',
        ]);

    expect($order->fresh()->payment_status)->toBe('paid');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'merchants/payments/20448132'));
});

test('payment status syncs from cellulant payment history when merchant id lookup fails', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'test-access-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'merchants/payments/20449999')) {
            return Http::response(['success' => false, 'message' => 'Not found'], 404);
        }

        if (str_contains($url, 'merchants/payments?')) {
            return Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    [
                        'merchantTransactionID' => '20450001',
                        'payerTransactionID' => 'ORD-HIST-1',
                        'paymentStatus' => 140,
                        'statusDescription' => 'Payment Accepted',
                        'extraInformation' => ['reference' => 'ORD-HIST-1'],
                    ],
                ],
            ], 200);
        }

        return Http::response(['success' => false], 404);
    });

    $order = Order::create([
        'machine_order_id' => 'ORD-HIST-1',
        'third_party_order_id' => 'RF-HIST-001',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 1600,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'phone_number' => '256759983853',
        'amount' => 1600,
        'reference' => 'ORD-HIST-1',
        'provider' => 'AIRTELUG',
        'status' => 'processing',
        'transaction_id' => '20449999',
    ]);

    $this->postJson('/api/vending/payment-status', [
        'transactionId' => 'ORD-HIST-1',
    ])->assertJson(['paid' => true]);

    expect($order->fresh()->payment_status)->toBe('paid');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'merchants/payments?')
        && str_contains($request->url(), 'payerTransactionID=ORD-HIST-1'));
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
        'statusCode' => 188,
        'requestStatusCode' => 140,
        'requestStatusDescription' => 'Payment fully paid',
    ])
        ->assertOk()
        ->assertJson([
            'statusCode' => '188',
            'merchantTransactionID' => 'ORD555',
        ]);

    expect($order->fresh()->payment_status)->toBe('paid');

    $log = CellulantIpnLog::first();
    expect($log)->not->toBeNull()
        ->and($log->merchant_transaction_id)->toBe('ORD555')
        ->and($log->status_code)->toBe('140')
        ->and($log->paymentStatusCode())->toBe('140')
        ->and($log->order_matched)->toBeTrue()
        ->and($log->order_id)->toBe($order->id)
        ->and($log->response_payload)->toMatchArray([
            'statusCode' => '188',
            'merchantTransactionID' => 'ORD555',
        ]);

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
        'statusCode' => 188,
        'requestStatusCode' => 140,
    ])->assertOk();

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('cellulant ipn ack status code alone does not mark order paid', function () {
    $order = Order::create([
        'machine_order_id' => 'ORD666',
        'third_party_order_id' => 'RF-TEST-005',
        'machine_id' => $this->machine->machine_id,
        'product_name' => 'Coffee',
        'amount' => 4000,
        'payment_status' => 'pending',
        'dispense_status' => 'pending',
    ]);

    $this->postJson('/api/cellulant/ipn', [
        'merchantTransactionID' => 'ORD666',
        'reference' => 'ORD666',
        'statusCode' => 188,
    ])
        ->assertOk()
        ->assertJson(['statusCode' => '188']);

    expect($order->fresh()->payment_status)->toBe('pending');

    $log = CellulantIpnLog::where('merchant_transaction_id', 'ORD666')->first();
    expect($log->paymentStatusCode())->toBeNull();
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
