<?php

namespace App\Services\PaymentProviders;

use App\Models\CellulantSetting;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CellulantProvider implements PaymentProviderInterface
{
    public function __construct(
        protected CellulantApiClient $client,
    ) {}

    public function initiateCollection(Order $order, string $phoneNumber): Payment
    {
        $settings = CellulantSetting::current();
        $msisdn = $this->normalizeMsisdn($phoneNumber);
        $reference = $order->machine_order_id;

        $payment = Payment::create([
            'order_id' => $order->id,
            'phone_number' => $msisdn,
            'amount' => $order->amount,
            'reference' => $reference,
            'provider' => $this->detectPayerClientCode($msisdn, $settings),
            'status' => 'pending',
        ]);

        $payload = [
            'counterCode' => $settings->activeCounterCode(),
            'msisdn' => $msisdn,
            'amount' => (int) $order->amount,
            'payerClientCode' => $payment->provider,
            'reference' => $reference,
        ];

        $response = $this->client->instoreRequest(
            'POST',
            $settings->initiate_payment_path,
            $payload
        );

        $body = $response->json() ?? [];
        $merchantTransactionId = data_get($body, 'data.merchantTransactionID');

        $payment->update([
            'status' => ($response->successful() && data_get($body, 'success')) ? 'processing' : 'failed',
            'transaction_id' => $merchantTransactionId
                ? (string) $merchantTransactionId
                : null,
            'provider_response' => $body,
        ]);

        if ($payment->status === 'failed') {
            Log::warning('Cellulant payment initiation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $order->update(['payment_status' => 'failed']);
        }

        $order->update(['customer_phone' => $msisdn]);

        return $payment->fresh();
    }

    public function refund(Order $order): bool
    {
        Log::info('Cellulant refund requested', [
            'order_id' => $order->id,
            'reference' => $order->machine_order_id,
        ]);

        $order->update(['payment_status' => 'refunded']);
        $order->latestPayment?->update(['status' => 'refunded']);

        return true;
    }

    public function handleWebhook(array $payload): void
    {
        $merchantTransactionId = data_get($payload, 'merchantTransactionID')
            ?? data_get($payload, 'merchant_transaction_id');

        $reference = data_get($payload, 'reference')
            ?? data_get($payload, 'payload.packet.payerTransactionID')
            ?? data_get($payload, 'extraData.reference');

        $statusCode = (string) (
            data_get($payload, 'statusCode')
            ?? data_get($payload, 'status_code')
            ?? data_get($payload, 'payload.packet.statusCode')
            ?? data_get($payload, 'requestStatusCode')
            ?? ''
        );

        $order = null;

        if ($reference) {
            $order = Order::where('machine_order_id', $reference)->first()
                ?? Order::where('third_party_order_id', $reference)->first();
        }

        if (! $order && $merchantTransactionId) {
            $order = Order::where('machine_order_id', $merchantTransactionId)->first()
                ?? Order::whereHas('payments', fn ($query) => $query->where('transaction_id', (string) $merchantTransactionId))
                    ->first();
        }

        if (! $order) {
            return;
        }

        $payment = $order->payments()->latest()->first();

        if ($payment) {
            $payment->update([
                'provider_response' => $payload,
                'transaction_id' => $merchantTransactionId
                    ? (string) $merchantTransactionId
                    : ($payment->transaction_id ?? data_get($payload, 'beepTransactionID')),
            ]);
        }

        if ($this->isPaidStatus($statusCode, $payload)) {
            if ($payment) {
                $payment->update(['status' => 'successful']);
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            return;
        }

        if ($this->isFailedStatus($statusCode, $payload)) {
            if ($payment) {
                $payment->update(['status' => 'failed']);
            }

            $order->update(['payment_status' => 'failed']);
        }
    }

    public function ipnAcknowledgement(array $payload): array
    {
        return [
            'statusCode' => '188',
            'merchantTransactionID' => (string) (
                data_get($payload, 'merchantTransactionID')
                ?? data_get($payload, 'data.merchantTransactionID', '')
            ),
            'statusDescription' => 'transaction acknowledged successfully',
        ];
    }

    protected function isPaidStatus(string $statusCode, array $payload): bool
    {
        if (in_array($statusCode, ['140', '178', '183', '217'], true)) {
            return true;
        }

        $description = strtolower((string) data_get($payload, 'requestStatusDescription', ''));

        return str_contains($description, 'fully paid')
            || str_contains($description, 'payment accepted');
    }

    protected function isFailedStatus(string $statusCode, array $payload): bool
    {
        if (in_array($statusCode, ['180', '216', '99', '101', '102'], true)) {
            return true;
        }

        return ! empty(data_get($payload, 'failedPayments'))
            || ! empty(data_get($payload, 'failed_payments'));
    }

    protected function detectPayerClientCode(string $msisdn, CellulantSetting $settings): string
    {
        if (! $settings->auto_detect_payer) {
            return $settings->default_payer_client_code;
        }

        $local = $this->localNumber($msisdn);

        if (preg_match('/^07(6[0-9]|7[0-8]|8[0-9]|31|39)/', $local)) {
            return $settings->default_payer_client_code;
        }

        if (preg_match('/^07(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])/', $local)) {
            return $settings->airtel_payer_client_code;
        }

        return $settings->default_payer_client_code;
    }

    protected function normalizeMsisdn(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (str_starts_with($phone, '0')) {
            return '256'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '256')) {
            return '256'.$phone;
        }

        return $phone;
    }

    protected function localNumber(string $msisdn): string
    {
        if (str_starts_with($msisdn, '256')) {
            return '0'.substr($msisdn, 3);
        }

        return $msisdn;
    }
}
