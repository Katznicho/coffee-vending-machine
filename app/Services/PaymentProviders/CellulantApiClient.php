<?php

namespace App\Services\PaymentProviders;

use App\Models\CellulantSetting;
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

        return Cache::remember($cacheKey, 3500, function () use ($username, $password, $scope) {
            $response = Http::asForm()->post($this->oauthTokenUrl(), [
                'grant_type' => 'password',
                'client_id' => 'payments',
                'username' => $username,
                'password' => $password,
                'scope' => $scope,
            ]);

            if (! $response->successful()) {
                $message = data_get($response->json(), 'error_description')
                    ?? data_get($response->json(), 'message')
                    ?? $response->body();

                throw new RuntimeException('OAuth failed: '.$message);
            }

            $token = $response->json('access_token');

            if (! $token) {
                throw new RuntimeException('OAuth response did not include an access token.');
            }

            return $token;
        });
    }

    public function instoreRequest(string $method, string $path, array $data = []): Response
    {
        return $this->authorizedRequest(
            $method,
            rtrim($this->settings->activeBaseUrl(), '/').'/'.ltrim($path, '/'),
            $data,
            includePaymentHeaders: true,
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
    ): Response {
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

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->asJson()->post($url, $data),
            'PUT' => $request->asJson()->put($url, $data),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };
    }
}
