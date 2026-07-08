<?php

namespace App\Services\PaymentProviders;

use App\Models\CellulantSetting;
use App\Support\IntegrationLogger;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CellulantApiClient
{
    public function __construct(
        protected CellulantSetting $settings,
    ) {}

    public static function forCurrent(): self
    {
        return new self(CellulantSetting::current());
    }

    public function oauthTokenUrl(): string
    {
        return $this->settings->isSandbox()
            ? 'https://accounts.sandbox.tingg.africa/api/v1/oauth/token'
            : 'https://accounts.tingg.africa/api/v1/oauth/token';
    }

    public function counterManagementBaseUrl(): string
    {
        return $this->settings->isSandbox()
            ? 'https://instore-management.sandbox.tingg.africa'
            : 'https://instore-management.tingg.africa';
    }

    public function accessToken(): string
    {
        $username = (string) $this->settings->activeUsername();
        $password = (string) $this->settings->activePassword();

        if ($username === '' || $password === '') {
            throw new RuntimeException('Cellulant username and password are required.');
        }

        $scope = (string) ($this->settings->oauth_scope ?: 'read');

        $cacheKey = sprintf(
            'cellulant_oauth_%s_%s_%s',
            $this->settings->id,
            $this->settings->updated_at?->getTimestamp() ?? 0,
            $scope,
        );

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $startedAt = microtime(true);
        $url = $this->oauthTokenUrl();

        $response = Http::asForm()->post($url, [
            'grant_type' => 'password',
            'client_id' => 'payments',
            'username' => $username,
            'password' => $password,
            'scope' => $scope,
        ]);

        $body = $response->json() ?? [];

        IntegrationLogger::log([
            'direction' => 'outbound',
            'channel' => 'cellulant_api',
            'event' => 'oauth_token',
            'http_method' => 'POST',
            'url' => $url,
            'http_status' => $response->status(),
            'success' => $response->successful() && filled(data_get($body, 'access_token')),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'message' => data_get($body, 'error_description') ?? data_get($body, 'message'),
            'request_payload' => [
                'grant_type' => 'password',
                'client_id' => 'payments',
                'username' => $username,
                'scope' => $scope,
            ],
            'response_payload' => $body,
        ]);

        if (! $response->successful()) {
            $message = data_get($body, 'error_description')
                ?? data_get($body, 'message')
                ?? $response->body();

            throw new RuntimeException('OAuth failed: '.$message);
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new RuntimeException('OAuth response did not include an access token.');
        }

        Cache::put($cacheKey, $token, 3500);

        return $token;
    }

    public function instoreRequest(string $method, string $path, array $data = [], ?array $context = []): Response
    {
        return $this->authorizedRequest(
            $method,
            rtrim($this->settings->activeBaseUrl(), '/').'/'.ltrim($path, '/'),
            $data,
            includePaymentHeaders: true,
            context: $context,
        );
    }

    public function counterRequest(string $method, string $path, array $data = []): Response
    {
        return $this->authorizedRequest(
            $method,
            rtrim($this->counterManagementBaseUrl(), '/').'/'.ltrim($path, '/'),
            $data,
            includePaymentHeaders: false,
        );
    }

    protected function authorizedRequest(
        string $method,
        string $url,
        array $data = [],
        bool $includePaymentHeaders = false,
        array $context = [],
    ): Response {
        $startedAt = microtime(true);
        $headers = [
            'X-Country-Code' => strtoupper((string) $this->settings->country_code),
        ];

        if ($includePaymentHeaders) {
            $headers['Currency-Code'] = strtoupper((string) $this->settings->currency_code);
            $headers['Request-Origin'] = (string) ($this->settings->request_origin_code ?: 'TINGG_INSTORE_INTEGRATION');
        }

        $request = Http::withToken($this->accessToken())
            ->acceptJson()
            ->withHeaders($headers);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->asJson()->post($url, $data),
            'PUT' => $request->asJson()->put($url, $data),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };

        $body = $response->json() ?? [];

        IntegrationLogger::log([
            'direction' => 'outbound',
            'channel' => 'cellulant_api',
            'event' => $context['event'] ?? IntegrationLogger::cellulantEventFromUrl($url, $method),
            'order_id' => $context['order_id'] ?? null,
            'merchant_transaction_id' => filled($context['merchant_transaction_id'] ?? null)
                ? (string) $context['merchant_transaction_id']
                : (filled(data_get($body, 'data.merchantTransactionID')) ? (string) data_get($body, 'data.merchantTransactionID') : null),
            'reference' => $context['reference'] ?? data_get($data, 'reference'),
            'http_method' => strtoupper($method),
            'url' => $url,
            'http_status' => $response->status(),
            'success' => $response->successful() && data_get($body, 'success') !== false,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'message' => data_get($body, 'message') ?? data_get($body, 'statusDescription'),
            'request_payload' => strtoupper($method) === 'GET' ? $data : $data,
            'response_payload' => $body !== [] ? $body : ['raw' => substr($response->body(), 0, 2000)],
        ]);

        return $response;
    }
}
