<?php

namespace App\Http\Controllers;

use App\Services\PaymentProviders\CellulantProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CellulantIpnController extends Controller
{
    public function __invoke(Request $request, CellulantProvider $cellulant): JsonResponse
    {
        $payload = $request->all();

        $cellulant->handleWebhook($payload);

        return response()->json($cellulant->ipnAcknowledgement($payload));
    }
}
