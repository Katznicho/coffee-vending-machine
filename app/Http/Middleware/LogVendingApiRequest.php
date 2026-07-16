<?php

namespace App\Http\Middleware;

use App\Support\IntegrationLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogVendingApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        Log::info('Vending API inbound', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 200),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'payload_keys' => array_keys($request->all()),
        ]);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            Log::error('Vending API exception', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'exception' => $e->getMessage(),
                'payload_keys' => array_keys($request->all()),
            ]);

            $this->persistLog($request, null, $startedAt, $e);

            throw $e;
        }

        $this->persistLog($request, $response, $startedAt);

        return $response;
    }

    protected function persistLog(Request $request, ?Response $response, float $startedAt, ?Throwable $exception = null): void
    {
        $transactionId = $request->input('transactionId')
            ?? $request->input('orderId')
            ?? $request->input('orderid');

        $machineId = $request->input('machineId')
            ?? $request->input('machid');

        $context = IntegrationLogger::resolveOrderContext(
            is_string($transactionId) ? $transactionId : null,
            is_string($machineId) ? $machineId : null,
        );

        $responseData = null;

        if ($response !== null) {
            try {
                $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $responseData = ['raw' => substr((string) $response->getContent(), 0, 2000)];
            }
        } elseif ($exception !== null) {
            $responseData = [
                'exception' => class_basename($exception),
                'message' => $exception->getMessage(),
            ];
        }

        IntegrationLogger::log([
            'direction' => 'inbound',
            'channel' => 'vending_api',
            'event' => $this->eventFromPath($request->path()),
            'order_id' => $context['order_id'],
            'reference' => $context['reference'],
            'machine_id' => $context['machine_id'],
            'http_method' => $request->method(),
            'url' => $request->fullUrl(),
            'http_status' => $response?->getStatusCode() ?? 500,
            'success' => $response?->isSuccessful() ?? false,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'ip_address' => $request->ip(),
            'message' => $exception?->getMessage()
                ?? (is_array($responseData) ? (data_get($responseData, 'message') ?? data_get($responseData, 'status')) : null),
            'request_payload' => $request->all(),
            'response_payload' => $responseData,
        ]);
    }

    protected function eventFromPath(string $path): string
    {
        return match (true) {
            str_contains($path, 'create-order') => 'create_order',
            str_contains($path, 'payment-status') => 'payment_status',
            str_contains($path, 'dispense-result') => 'dispense_result',
            str_contains($path, 'delivery-result') => 'delivery_result',
            default => 'vending_request',
        };
    }
}
