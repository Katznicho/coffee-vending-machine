<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Support\OrderReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateOrderController extends Controller
{
    public function __invoke(Request $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $payload = $this->normalizePayload($request);

        $validated = validator($payload, [
            'orderid' => 'required|string|max:100',
            'machid' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1',
            'phone_number' => 'required|string|max:20',
            'trackno' => 'nullable|string|max:10',
            'channelid' => 'nullable|string|max:10',
            'ver' => 'nullable|string',
            'randstr' => 'nullable|string',
            'timestamp' => 'nullable|string',
            'sign' => 'nullable|string',
        ])->validate();

        $existing = Order::where('machine_order_id', $validated['orderid'])
            ->where('machine_id', $validated['machid'])
            ->first();

        if ($existing) {
            $existing = $paymentProvider->syncPendingPaymentStatus($existing);

            return $this->orderResponse($existing);
        }

        $order = Order::create([
            'machine_order_id' => $validated['orderid'],
            'third_party_order_id' => OrderReference::thirdPartyId(),
            'machine_id' => $validated['machid'],
            'track_no' => $validated['trackno'] ?? null,
            'product_name' => $validated['name'],
            'amount' => (int) $validated['price'],
            'channel_id' => $validated['channelid'] ?? config('vending.default_channel_id', '36'),
            'customer_phone' => $validated['phone_number'],
            'payment_status' => 'pending',
            'dispense_status' => 'pending',
            'expires_at' => now()->addMinutes(config('vending.order_expiry_minutes', 15)),
        ]);

        $payment = $paymentProvider->initiateCollection($order, $validated['phone_number']);

        if ($payment->status === 'failed') {
            return response()->json([
                'status' => 'FAILED',
                'transactionId' => $order->machine_order_id,
                'message' => 'Unable to initiate payment',
            ], 422);
        }

        return $this->orderResponse($order);
    }

    protected function normalizePayload(Request $request): array
    {
        return [
            'orderid' => $request->input('orderId')
                ?? $request->input('orderid')
                ?? $request->input('transactionId'),
            'machid' => $request->input('machineId')
                ?? $request->input('machid'),
            'name' => $request->input('product')
                ?? $request->input('name'),
            'price' => $request->input('amount')
                ?? $request->input('price'),
            'phone_number' => $request->input('phoneNumber')
                ?? $request->input('phone_number')
                ?? $request->input('msisdn'),
            'trackno' => $request->input('trackno'),
            'channelid' => $request->input('channelid'),
            'ver' => $request->input('ver'),
            'randstr' => $request->input('randstr'),
            'timestamp' => $request->input('timestamp'),
            'sign' => $request->input('sign'),
        ];
    }

    protected function orderResponse(Order $order): JsonResponse
    {
        $order->markExpiredIfNeeded();

        return match ($order->payment_status) {
            'paid' => response()->json([
                'status' => 'PAID',
                'transactionId' => $order->machine_order_id,
                'paid' => true,
            ]),
            'failed' => response()->json([
                'status' => 'FAILED',
                'transactionId' => $order->machine_order_id,
                'paid' => false,
            ]),
            'expired' => response()->json([
                'status' => 'EXPIRED',
                'transactionId' => $order->machine_order_id,
                'paid' => false,
            ]),
            default => response()->json([
                'status' => 'PENDING',
                'transactionId' => $order->machine_order_id,
                'paid' => false,
            ]),
        };
    }
}
