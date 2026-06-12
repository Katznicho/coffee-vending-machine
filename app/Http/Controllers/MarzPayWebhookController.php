<?php

namespace App\Http\Controllers;

use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarzPayWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $paymentProvider->handleWebhook($request->all());

        return response()->json(['status' => 'ok']);
    }
}
