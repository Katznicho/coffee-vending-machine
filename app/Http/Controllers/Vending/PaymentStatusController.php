<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    public function __invoke(Request $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $transactionId = $request->input('transactionId')
            ?? $request->input('orderid')
            ?? $request->input('orderId');

        $order = Order::query()
            ->when($request->input('torderid'), fn ($q, $id) => $q->where('third_party_order_id', $id))
            ->where(function ($query) use ($transactionId) {
                $query->where('machine_order_id', $transactionId)
                    ->orWhere('third_party_order_id', $transactionId);
            })
            ->first();

        if (! $order) {
            return response()->json([
                'paid' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->markExpiredIfNeeded();

        if ($order->payment_status === 'pending') {
            $order = $paymentProvider->syncPendingPaymentStatus($order);
        }

        if ($request->filled('ver')) {
            return response()->json([
                'code' => $order->vendingStatusCode(),
                'msg' => $order->vendingStatusMessage(),
            ]);
        }

        return response()->json([
            'paid' => $order->payment_status === 'paid',
            'transactionId' => $order->machine_order_id,
            'status' => strtoupper($order->payment_status),
        ]);
    }
}
