<?php

namespace App\Http\Controllers\Vending;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Support\OrderReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateOrderController extends Controller
{
    public function __invoke(Request $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $payload = $this->normalizePayload($request);

        Log::info('Create order request received', [
            'ip' => $request->ip(),
            'machid' => $payload['machid'] ?? null,
            'orderid' => $payload['orderid'] ?? null,
            'name' => $payload['name'] ?? null,
            'price' => $payload['price'] ?? null,
            'phone_present' => filled($payload['phone_number'] ?? null),
            'ver' => $payload['ver'] ?? null,
            'payload_keys' => array_keys(array_filter($payload)),
        ]);

        try {
            $validated = validator($payload, [
                'orderid' => 'required|string|max:100',
                'machid' => 'required|string|max:20',
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:1',
                'phone_number' => 'nullable|string|max:20',
                'trackno' => 'nullable|string|max:10',
                'channelid' => 'nullable|string|max:10',
                'ver' => 'nullable|string',
                'randstr' => 'nullable|string',
                'timestamp' => 'nullable|string',
                'sign' => 'nullable|string',
            ])->validate();
        } catch (ValidationException $e) {
            Log::warning('Create order validation failed', [
                'ip' => $request->ip(),
                'errors' => $e->errors(),
                'payload_keys' => array_keys(array_filter($payload)),
            ]);

            throw $e;
        }

        $phone = filled($validated['phone_number'] ?? null)
            ? (string) $validated['phone_number']
            : null;
        $machinePriceIsScaled = $request->has('price') && ! $request->has('amount');
        $amount = $this->normalizeAmount($validated['price'], $machinePriceIsScaled);

        Log::info('Create order amount normalized', [
            'orderid' => $validated['orderid'],
            'raw_price' => $validated['price'],
            'machine_price_scaled' => $machinePriceIsScaled,
            'divisor' => $machinePriceIsScaled
                ? config('vending.machine_price_divisor', 100)
                : 1,
            'amount_ugx' => $amount,
        ]);

        $existing = Order::where('machine_order_id', $validated['orderid'])
            ->where('machine_id', $validated['machid'])
            ->first();

        if ($existing) {
            Log::info('Create order duplicate — syncing status', [
                'order_id' => $existing->id,
                'machine_order_id' => $existing->machine_order_id,
                'payment_status' => $existing->payment_status,
                'dispense_status' => $existing->dispense_status,
            ]);

            $existing = $paymentProvider->syncPendingPaymentStatus($existing);

            // Some machines reuse the same orderid until reboot after a completed
            // sale. Recycle that row into a fresh pending order so the next drink
            // does not 500 on payments_reference_unique.
            if ($this->shouldRecycleCompletedOrder($existing)) {
                $existing = $this->recycleCompletedOrder($existing, $validated, $amount, $phone);

                Log::info('Create order recycled completed order for new sale', [
                    'order_id' => $existing->id,
                    'machine_order_id' => $existing->machine_order_id,
                    'third_party_order_id' => $existing->third_party_order_id,
                    'amount' => $existing->amount,
                ]);
            }

            if ($phone && $existing->payment_status === 'pending' && ! $existing->payments()->whereIn('status', ['pending', 'processing', 'successful'])->exists()) {
                $payment = $paymentProvider->initiateCollection($existing, $phone);

                if ($payment->status === 'failed') {
                    Log::error('Create order duplicate payment initiation failed', [
                        'order_id' => $existing->id,
                        'payment_id' => $payment->id,
                    ]);

                    return $this->paymentFailedResponse($existing, $validated);
                }

                $existing->refresh();
            }

            Log::info('Create order duplicate response', [
                'order_id' => $existing->id,
                'payment_status' => $existing->payment_status,
            ]);

            return $this->orderResponse($existing, $validated, $phone);
        }

        $order = Order::create([
            'machine_order_id' => $validated['orderid'],
            'third_party_order_id' => OrderReference::thirdPartyId(),
            'machine_id' => $validated['machid'],
            'track_no' => $validated['trackno'] ?? null,
            'product_name' => $validated['name'],
            'amount' => $amount,
            'channel_id' => $validated['channelid'] ?? config('vending.default_channel_id', '36'),
            'customer_phone' => $phone,
            'payment_status' => 'pending',
            'dispense_status' => 'pending',
            'expires_at' => now()->addMinutes(config('vending.order_expiry_minutes', 15)),
        ]);

        if ($phone) {
            Log::info('Create order created — initiating payment', [
                'order_id' => $order->id,
                'machine_order_id' => $order->machine_order_id,
                'machine_id' => $order->machine_id,
                'amount' => $order->amount,
                'phone' => $phone,
            ]);

            $payment = $paymentProvider->initiateCollection($order, $phone);

            if ($payment->status === 'failed') {
                Log::error('Create order payment initiation failed', [
                    'order_id' => $order->id,
                    'machine_order_id' => $order->machine_order_id,
                    'payment_id' => $payment->id,
                    'provider_response' => $payment->provider_response,
                ]);

                return $this->paymentFailedResponse($order, $validated);
            }

            Log::info('Create order payment initiated', [
                'order_id' => $order->id,
                'machine_order_id' => $order->machine_order_id,
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ]);
        } else {
            Log::info('Create order created — awaiting phone on payment page', [
                'order_id' => $order->id,
                'machine_order_id' => $order->machine_order_id,
                'machine_id' => $order->machine_id,
                'amount' => $order->amount,
                'payment_url' => route('pay.show', $order->third_party_order_id),
            ]);
        }

        return $this->orderResponse($order, $validated, $phone);
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

    protected function normalizeAmount(int|float|string $price, bool $machinePriceIsScaled): int
    {
        if (! $machinePriceIsScaled) {
            return (int) round((float) $price);
        }

        $divisor = max(1, (int) config('vending.machine_price_divisor', 100));

        return max(1, (int) round((float) $price / $divisor));
    }

    protected function shouldRecycleCompletedOrder(Order $order): bool
    {
        if (! in_array($order->payment_status, ['paid', 'failed', 'expired', 'refunded'], true)) {
            return false;
        }

        // Paid and still waiting for dispense — keep this order for the machine.
        if ($order->payment_status === 'paid' && $order->dispense_status === 'pending') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function recycleCompletedOrder(Order $order, array $validated, int $amount, ?string $phone): Order
    {
        foreach ($order->payments as $payment) {
            if ($payment->reference === $order->machine_order_id) {
                $payment->update([
                    'reference' => $payment->reference.'-done-'.$payment->id,
                ]);
            }
        }

        $order->update([
            'third_party_order_id' => OrderReference::thirdPartyId(),
            'track_no' => $validated['trackno'] ?? $order->track_no,
            'product_name' => $validated['name'],
            'amount' => $amount,
            'channel_id' => $validated['channelid'] ?? $order->channel_id ?? config('vending.default_channel_id', '36'),
            'customer_phone' => $phone,
            'payment_status' => 'pending',
            'dispense_status' => 'pending',
            'paid_at' => null,
            'expires_at' => now()->addMinutes(config('vending.order_expiry_minutes', 15)),
        ]);

        return $order->fresh();
    }

    /**
     * Machine PDF (ver=v1) and phone-less flows return QR/payment URL.
     * Phone-present modern clients get status/transactionId.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function orderResponse(Order $order, array $validated, ?string $phone = null): JsonResponse
    {
        $order->markExpiredIfNeeded();

        if ($this->usesMachineProtocol($validated, $phone)) {
            return response()->json([
                'code' => '1',
                'orderid' => $order->machine_order_id,
                'torderid' => $order->third_party_order_id,
                'msg' => 'Success',
                'twocode' => $this->paymentUrl($order),
            ]);
        }

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

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function paymentFailedResponse(Order $order, array $validated): JsonResponse
    {
        $phone = filled($validated['phone_number'] ?? null)
            ? (string) $validated['phone_number']
            : null;

        if ($this->usesMachineProtocol($validated, $phone)) {
            return response()->json([
                'code' => '0',
                'orderid' => $order->machine_order_id,
                'torderid' => $order->third_party_order_id,
                'msg' => 'Unable to initiate payment',
            ]);
        }

        return response()->json([
            'status' => 'FAILED',
            'transactionId' => $order->machine_order_id,
            'message' => 'Unable to initiate payment',
        ], 422);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function usesMachineProtocol(array $validated, ?string $phone = null): bool
    {
        return filled($validated['ver'] ?? null) || blank($phone);
    }

    protected function paymentUrl(Order $order): string
    {
        return route('pay.show', $order->third_party_order_id);
    }
}
