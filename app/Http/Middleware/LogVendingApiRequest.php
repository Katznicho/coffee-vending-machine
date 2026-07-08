<?php

namespace App\Http\Middleware;

use App\Support\IntegrationLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVendingApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

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

        try {
            $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $responseData = ['raw' => substr((string) $response->getContent(), 0, 2000)];
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
            'http_status' => $response->getStatusCode(),
            'success' => $response->isSuccessful(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'ip_address' => $request->ip(),
            'message' => is_array($responseData) ? (data_get($responseData, 'message') ?? data_get($responseData, 'status')) : null,
            'request_payload' => $request->all(),
            'response_payload' => $responseData,
        ]);

        return $response;
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
