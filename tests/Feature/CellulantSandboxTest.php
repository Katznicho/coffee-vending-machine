<?php

use App\Models\CellulantSetting;
use App\Services\CellulantSandboxTester;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    CellulantSetting::query()->delete();
    CellulantSetting::current();
    \Illuminate\Support\Facades\Cache::flush();

    Http::fake([
        'accounts.sandbox.tingg.africa/*' => Http::response([
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'access_token' => 'sandbox-token',
        ], 200),
        'instore-management.sandbox.tingg.africa/*' => Http::response([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'storeName' => 'Demo branch',
                'counterName' => 'Counter 1',
                'counterCode' => '1008',
                'countryCode' => 'UGA',
                'serviceCode' => 'PATISSERIEEXPRES_UGA',
            ],
        ], 200),
        'payments-instore.sandbox.tingg.africa/proxyPaymentMethods*' => Http::response([
            'data' => [
                ['customer' => ['customerCode' => 'MTNUG']],
                ['customer' => ['customerCode' => 'AIRTELUG']],
            ],
        ], 200),
    ]);
});

test('connectivity test reports success', function () {
    $results = app(CellulantSandboxTester::class)->runConnectivity();

    expect($results['connectivity_passed'])->toBeTrue()
        ->and($results['payment_test_ran'])->toBeFalse()
        ->and($results['headline'])->toBe('Connection successful.')
        ->and($results['steps'])->toHaveCount(3);
});

test('connectivity test fails when integration is disabled', function () {
    CellulantSetting::current()->update(['is_active' => false]);

    $results = app(CellulantSandboxTester::class)->runConnectivity();

    expect($results['connectivity_passed'])->toBeFalse()
        ->and($results['steps'][0]['name'])->toBe('Integration');
});

test('payment test headline separates connection from payment failure', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'sandbox-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'counterCodes')) {
            return Http::response([
                'success' => true,
                'data' => ['storeName' => 'Demo', 'counterName' => 'Counter 1', 'countryCode' => 'UGA'],
            ], 200);
        }

        if (str_contains($url, 'proxyPaymentMethods')) {
            return Http::response(['data' => [['customer' => ['customerCode' => 'MTNUG']]]], 200);
        }

        if (str_contains($url, 'initiateMerchantPayment')) {
            return Http::response([
                'success' => false,
                'statusCode' => 400,
                'message' => 'We are unable to process your request at this time, please try again later.',
            ], 400);
        }

        return Http::response([], 404);
    });

    $results = app(CellulantSandboxTester::class)->runPaymentTest('0771234567', 1000);

    expect($results['connectivity_passed'])->toBeTrue()
        ->and($results['payment_test_passed'])->toBeFalse()
        ->and($results['headline'])->toBe('Connection OK. Payment prompt failed.')
        ->and($results['steps'][3]['message'])->toBe('[400] We are unable to process your request at this time, please try again later.')
        ->and($results['steps'][3]['details']['response']['message'])->toBe('We are unable to process your request at this time, please try again later.');
});

test('payment test uses airtel money wording for airtel numbers', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'initiateMerchantPayment')) {
            return Http::response([
                'success' => false,
                'statusCode' => 400,
                'message' => 'We are unable to process your request at this time, please try again later.',
            ], 400);
        }

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'sandbox-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'counterCodes')) {
            return Http::response(['success' => true, 'data' => ['storeName' => 'Demo', 'counterName' => 'Counter 1']], 200);
        }

        if (str_contains($url, 'proxyPaymentMethods')) {
            return Http::response(['data' => [['customer' => ['customerCode' => 'AIRTELUG']]]], 200);
        }

        return Http::response([], 404);
    });

    $results = app(CellulantSandboxTester::class)->runPaymentTest('0759983853', 982);

    expect($results['steps'][3]['message'])->toBe('[400] We are unable to process your request at this time, please try again later.');
});

test('payment test succeeds when initiate payment succeeds', function () {
    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'oauth/token')) {
            return Http::response(['access_token' => 'sandbox-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($url, 'counterCodes')) {
            return Http::response([
                'success' => true,
                'data' => ['storeName' => 'Demo branch', 'counterName' => 'Counter 1', 'countryCode' => 'UGA'],
            ], 200);
        }

        if (str_contains($url, 'proxyPaymentMethods')) {
            return Http::response(['data' => [['customer' => ['customerCode' => 'MTNUG']]]], 200);
        }

        if (str_contains($url, 'initiateMerchantPayment')) {
            return Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => ['merchantTransactionID' => '556677'],
            ], 200);
        }

        return Http::response([], 404);
    });

    $results = app(CellulantSandboxTester::class)->runPaymentTest('0771234567', 1000);

    expect($results['passed'])->toBeTrue()
        ->and($results['payment_test_passed'])->toBeTrue()
        ->and($results['steps'])->toHaveCount(4);
});
