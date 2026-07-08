<?php

namespace App\Http\Controllers;

use App\Models\CellulantIpnLog;
use App\Services\PaymentProviders\CellulantProvider;
use App\Support\CellulantIpnPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CellulantIpnController extends Controller
{
    public function __invoke(Request $request, CellulantProvider $cellulant): JsonResponse
    {
        $payload = $request->all();
        $order = $cellulant->findOrderFromPayload($payload);

        $cellulant->handleWebhook($payload);

        $response = $cellulant->ipnAcknowledgement($payload);

        CellulantIpnLog::fromRequest($payload, $response, $order, $request->ip());

        $context = [
            'merchant_transaction_id' => data_get($payload, 'merchantTransactionID') ?? data_get($payload, 'merchant_transaction_id'),
            'reference' => data_get($payload, 'reference') ?? data_get($payload, 'extraData.reference'),
            'status_code' => CellulantIpnPayload::paymentStatusCode($payload),
            'order_matched' => $order !== null,
            'order_id' => $order?->id,
            'ip_address' => $request->ip(),
            'request' => $payload,
            'response' => $response,
        ];

        if ($order === null) {
            Log::warning('Cellulant IPN received with no matching order', $context);
        } else {
            Log::info('Cellulant IPN processed', $context);
        }

        return response()->json($response);
    }
}
