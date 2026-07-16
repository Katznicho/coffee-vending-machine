<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentStatusController extends Controller
{
    public function __invoke(Request $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $transactionId = $request->input('transactionId')
            ?? $request->input('orderid')
            ?? $request->input('orderId');

        Log::info('Payment status poll received', [
            'ip' => $request->ip(),
            'transactionId' => $transactionId,
            'torderid' => $request->input('torderid'),
            'ver' => $request->input('ver'),
        ]);

        $order = Order::query()
            ->when($request->input('torderid'), fn ($q, $id) => $q->where('third_party_order_id', $id))
            ->where(function ($query) use ($transactionId) {
                $query->where('machine_order_id', $transactionId)
                    ->orWhere('third_party_order_id', $transactionId);
            })
            ->first();

        if (! $order) {
            Log::warning('Payment status order not found', [
                'ip' => $request->ip(),
                'transactionId' => $transactionId,
                'torderid' => $request->input('torderid'),
            ]);

            return response()->json([
                'paid' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $before = $order->payment_status;
        $order->markExpiredIfNeeded();

        if ($order->payment_status === 'pending') {
            $order = $paymentProvider->syncPendingPaymentStatus($order);
        }

        Log::info('Payment status response', [
            'order_id' => $order->id,
            'machine_order_id' => $order->machine_order_id,
            'status_before' => $before,
            'status_after' => $order->payment_status,
            'v1_format' => $request->filled('ver'),
        ]);

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
