<?php

namespace App\Services\PaymentProviders;

use App\Models\Order;
use App\Models\Payment;
use App\Support\OrderReference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarzPayProvider implements PaymentProviderInterface
{
    public function initiateCollection(Order $order, string $phoneNumber): Payment
    {
        $reference = OrderReference::marzPayReference();
        $phone = $this->normalizePhone($phoneNumber);

        $payment = Payment::create([
            'order_id' => $order->id,
            'phone_number' => $phone,
            'amount' => $order->amount,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$this->credentials(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl().'/collect-money', [
            'amount' => (int) $order->amount,
            'phone_number' => $phone,
            'country' => config('marzpay.country', 'UG'),
            'reference' => $reference,
            'description' => $order->product_name.' - '.$order->third_party_order_id,
            'callback_url' => route('payments.marzpay.webhook'),
        ]);

        $body = $response->json() ?? [];

        $payment->update([
            'status' => $response->successful() ? 'processing' : 'failed',
            'transaction_id' => data_get($body, 'data.transaction.uuid')
                ?? data_get($body, 'transaction.uuid'),
            'provider' => data_get($body, 'data.transaction.provider')
                ?? data_get($body, 'transaction.provider'),
            'provider_response' => $body,
        ]);

        if (! $response->successful()) {
            Log::warning('MarzPay collection failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $body,
            ]);
        }

        $order->update(['customer_phone' => $phone]);

        return $payment->fresh();
    }

    public function refund(Order $order): bool
    {
        $payment = $order->latestPayment;

        if (! $payment || ! $order->customer_phone) {
            return false;
        }

        $reference = OrderReference::marzPayReference();

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$this->credentials(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl().'/send-money', [
            'amount' => (int) $order->amount,
            'phone_number' => $payment->phone_number,
            'country' => config('marzpay.country', 'UG'),
            'reference' => $reference,
            'description' => 'Refund for '.$order->third_party_order_id,
            'callback_url' => route('payments.marzpay.webhook'),
        ]);

        if ($response->successful()) {
            $order->update(['payment_status' => 'refunded']);
            $payment->update(['status' => 'refunded']);

            return true;
        }

        Log::error('MarzPay refund failed', [
            'order_id' => $order->id,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return false;
    }

    public function handleWebhook(array $payload): void
    {
        $reference = data_get($payload, 'transaction.reference');
        $status = strtolower((string) data_get($payload, 'transaction.status', ''));
        $eventType = (string) data_get($payload, 'event_type', '');

        if (! $reference) {
            return;
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'transaction_id' => data_get($payload, 'transaction.uuid', $payment->transaction_id),
            'provider' => data_get($payload, 'transaction.provider', $payment->provider),
            'provider_response' => $payload,
        ]);

        $order = $payment->order;

        if (in_array($status, ['completed', 'successful'], true)
            || str_contains($eventType, 'completed')
            || str_contains($eventType, 'successful')) {
            $payment->update(['status' => 'successful']);
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            return;
        }

        if (in_array($status, ['failed', 'cancelled'], true)
            || str_contains($eventType, 'failed')
            || str_contains($eventType, 'cancelled')) {
            $payment->update(['status' => 'failed']);
            $order->update(['payment_status' => 'failed']);
        }
    }

    public function syncPendingPaymentStatus(Order $order): Order
    {
        return $order;
    }

    public function refreshPaymentStatus(Order $order): Order
    {
        return $order;
    }

    protected function credentials(): string
    {
        return base64_encode(
            config('marzpay.api_user').':'.config('marzpay.api_key')
        );
    }

    protected function baseUrl(): string
    {
        return rtrim(config('marzpay.base_url'), '/');
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '+256'.substr($phone, 1);
        }

        if (str_starts_with($phone, '256')) {
            return '+'.$phone;
        }

        if (! str_starts_with($phone, '+')) {
            return '+'.$phone;
        }

        return $phone;
    }
}
