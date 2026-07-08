<?php

namespace App\Services;

use App\Models\CellulantSetting;
use App\Services\PaymentProviders\CellulantApiClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CellulantSandboxTester
{
    public function runConnectivity(): array
    {
        return $this->buildResult(
            steps: $this->connectivitySteps(),
            paymentTestRan: false,
        );
    }

    public function runPaymentTest(string $testPhone, int $testAmount = 1000): array
    {
        return $this->buildResult(
            steps: [...$this->connectivitySteps(), $this->paymentStep($testPhone, $testAmount)],
            paymentTestRan: true,
        );
    }

    protected function connectivitySteps(): array
    {
        $settings = CellulantSetting::current();

        if (! $settings->is_active) {
            return [[
                'name' => 'Integration',
                'passed' => false,
                'message' => 'Cellulant integration is disabled.',
            ]];
        }

        $client = new CellulantApiClient($settings);
        $counterCode = (string) $settings->activeCounterCode();

        return [
            $this->runStep('OAuth token', function () use ($client) {
                $client->accessToken();

                return 'Authenticated successfully.';
            }),
            $this->runStep('Counter lookup', function () use ($client, $counterCode) {
                if ($counterCode === '') {
                    throw new RuntimeException('Counter code is not configured.');
                }

                $response = $client->counterRequest('GET', "/v2/counterCodes/{$counterCode}/compact");
                $body = $response->json() ?? [];

                if (! $response->successful() || ! data_get($body, 'success')) {
                    throw new RuntimeException($this->apiErrorMessage($response, $body, 'Counter lookup failed.'));
                }

                $store = trim((string) data_get($body, 'data.storeName'));
                $counter = trim((string) data_get($body, 'data.counterName'));

                return "Counter {$counterCode} verified ({$store}).";
            }),
            $this->runStep('Payment methods', function () use ($client, $settings) {
                $countryCode = strtoupper((string) $settings->country_code);

                $response = $client->instoreRequest('GET', '/proxyPaymentMethods', [
                    'countryCode' => $countryCode,
                ]);

                $body = $response->json() ?? [];

                if (! $response->successful()) {
                    throw new RuntimeException($this->apiErrorMessage($response, $body, 'Payment methods request failed.'));
                }

                $codes = collect(data_get($body, 'data', []))
                    ->map(fn ($method) => data_get($method, 'customer.customerCode'))
                    ->filter()
                    ->values()
                    ->all();

                if ($codes === []) {
                    throw new RuntimeException('No payment methods returned for '.$countryCode.'.');
                }

                return implode(', ', $codes).' available.';
            }),
        ];
    }

    protected function paymentStep(string $testPhone, int $testAmount): array
    {
        $settings = CellulantSetting::current();
        $client = new CellulantApiClient($settings);
        $msisdn = $this->normalizeMsisdn($testPhone);
        $payerClientCode = $this->detectPayerClientCode($msisdn, $settings);

        return $this->runStep('Test payment', function () use ($client, $settings, $msisdn, $payerClientCode, $testAmount) {
            $this->assertValidUgandaMsisdn($msisdn);

            $response = $client->instoreRequest('POST', $settings->initiate_payment_path, [
                'msisdn' => $msisdn,
                'amount' => $testAmount,
                'counterCode' => (string) $settings->activeCounterCode(),
                'payerClientCode' => $payerClientCode,
                'reference' => null,
            ]);

            $body = $response->json() ?? [];

            if (! $response->successful() || ! data_get($body, 'success')) {
                throw new CellulantSandboxTestException(
                    $this->apiErrorMessage($response, $body, 'Payment initiation failed.'),
                    [
                        'http_status' => $response->status(),
                        'phone' => $msisdn,
                        'payer' => $payerClientCode,
                        'amount' => number_format($testAmount).' UGX',
                        'response' => $body !== [] ? $body : ['raw' => $response->body()],
                    ]
                );
            }

            $merchantTransactionId = data_get($body, 'data.merchantTransactionID');

            return $this->walletLabel($msisdn).' prompt sent. Approve the PIN on the phone. Ref: '.$merchantTransactionId;
        });
    }

    protected function buildResult(array $steps, bool $paymentTestRan): array
    {
        $settings = CellulantSetting::current();
        $connectivitySteps = $paymentTestRan ? array_slice($steps, 0, -1) : $steps;
        $connectivityPassed = collect($connectivitySteps)->every(fn (array $step) => $step['passed']);
        $paymentStep = $paymentTestRan ? $steps[array_key_last($steps)] : null;
        $paymentPassed = $paymentStep ? $paymentStep['passed'] : null;

        $headline = match (true) {
            ! $connectivityPassed => 'Connection failed.',
            $paymentTestRan && $paymentPassed === false => 'Connection OK. Payment prompt failed.',
            $paymentTestRan && $paymentPassed === true => 'Connection OK. Payment prompt sent.',
            default => 'Connection successful.',
        };

        return [
            'headline' => $headline,
            'passed' => $connectivityPassed && ($paymentPassed ?? true),
            'connectivity_passed' => $connectivityPassed,
            'payment_test_ran' => $paymentTestRan,
            'payment_test_passed' => $paymentPassed,
            'environment' => $settings->environment,
            'steps' => $steps,
        ];
    }

    protected function runStep(string $name, callable $callback): array
    {
        try {
            return [
                'name' => $name,
                'passed' => true,
                'message' => $callback(),
            ];
        } catch (Throwable $exception) {
            return [
                'name' => $name,
                'passed' => false,
                'message' => $exception->getMessage(),
                'details' => $exception instanceof CellulantSandboxTestException ? $exception->details : [],
            ];
        }
    }

    protected function apiErrorMessage(Response $response, array $body, string $fallback): string
    {
        $parts = array_filter([
            data_get($body, 'message'),
            data_get($body, 'statusDescription'),
            data_get($body, 'data.message'),
            data_get($body, 'error'),
            data_get($body, 'error_description'),
        ], fn ($value) => filled($value));

        $errors = data_get($body, 'errors');

        if (is_array($errors) && $errors !== []) {
            $parts[] = collect($errors)->flatten()->filter()->implode('; ');
        }

        $message = $parts !== []
            ? implode(' — ', array_unique(array_map('strval', $parts)))
            : $fallback;

        $statusCode = data_get($body, 'statusCode');

        if ($statusCode) {
            $message = "[{$statusCode}] {$message}";
        } elseif ($response->status() >= 400) {
            $message = "[HTTP {$response->status()}] {$message}";
        }

        if ($message === $fallback && $response->body() !== '' && $body === []) {
            $message = "[HTTP {$response->status()}] ".Str::limit($response->body(), 300);
        }

        return $message;
    }

    protected function assertValidUgandaMsisdn(string $msisdn): void
    {
        if (! preg_match('/^2567\d{8}$/', $msisdn)) {
            throw new RuntimeException('Enter a valid Uganda number (07XXXXXXXX or 2567XXXXXXXX).');
        }
    }

    protected function walletLabel(string $msisdn): string
    {
        $local = $this->localNumber($msisdn);

        if (preg_match('/^07(6[0-9]|7[0-8]|8[0-9]|31|39)/', $local)) {
            return 'MTN MoMo wallet';
        }

        if (preg_match('/^07(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])/', $local)) {
            return 'Airtel Money wallet';
        }

        return 'mobile money wallet';
    }

    protected function detectPayerClientCode(string $msisdn, CellulantSetting $settings): string
    {
        if (! $settings->auto_detect_payer) {
            return $settings->default_payer_client_code;
        }

        $local = $this->localNumber($msisdn);

        if (preg_match('/^07(6[0-9]|7[0-8]|8[0-9]|31|39)/', $local)) {
            return $settings->default_payer_client_code;
        }

        if (preg_match('/^07(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])/', $local)) {
            return $settings->airtel_payer_client_code;
        }

        return $settings->default_payer_client_code;
    }

    protected function normalizeMsisdn(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (str_starts_with($phone, '0')) {
            return '256'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '256')) {
            return '256'.$phone;
        }

        return $phone;
    }

    protected function localNumber(string $msisdn): string
    {
        if (str_starts_with($msisdn, '256')) {
            return '0'.substr($msisdn, 3);
        }

        return $msisdn;
    }
}
