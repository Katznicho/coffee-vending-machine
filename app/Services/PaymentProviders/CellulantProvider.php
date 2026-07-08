<?php

namespace App\Services\PaymentProviders;

use App\Models\CellulantSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Support\CellulantIpnPayload;
use App\Support\IntegrationLogger;
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
            'msisdn' => $msisdn,
            'amount' => (int) $order->amount,
            'counterCode' => (string) $settings->activeCounterCode(),
            'payerClientCode' => $payment->provider,
            'reference' => $reference,
        ];

        $response = $this->client->instoreRequest(
            'POST',
            $settings->initiate_payment_path,
            $payload,
            [
                'event' => 'initiate_payment',
                'order_id' => $order->id,
                'reference' => $reference,
            ],
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

    public function findOrderFromPayload(array $payload): ?Order
    {
        $merchantTransactionId = data_get($payload, 'merchantTransactionID')
            ?? data_get($payload, 'merchant_transaction_id');

        $reference = data_get($payload, 'reference')
            ?? data_get($payload, 'payload.packet.payerTransactionID')
            ?? data_get($payload, 'extraData.reference');

        if ($reference) {
            $order = Order::where('machine_order_id', $reference)->first()
                ?? Order::where('third_party_order_id', $reference)->first();

            if ($order) {
                return $order;
            }
        }

        if ($merchantTransactionId) {
            return Order::where('machine_order_id', $merchantTransactionId)->first()
                ?? Order::whereHas('payments', fn ($query) => $query->where('transaction_id', (string) $merchantTransactionId))
                    ->first();
        }

        return null;
    }

    public function syncPendingPaymentStatus(Order $order): Order
    {
        if ($order->payment_status !== 'pending' || ! CellulantSetting::current()->is_active) {
            return $order;
        }

        return $this->refreshPaymentStatus($order);
    }

    public function refreshPaymentStatus(Order $order): Order
    {
        if (! CellulantSetting::current()->is_active) {
            return $order;
        }

        if (! in_array($order->payment_status, ['pending', 'failed'], true)) {
            return $order;
        }

        $payment = $order->payments()->latest()->first();
        $record = $this->lookupPaymentRecord($order, $payment);

        if ($record === null) {
            return $order;
        }

        $this->applyPaymentRecord($order, $payment, $record, 'status_query');

        return $order->fresh();
    }

    public function handleWebhook(array $payload): void
    {
        $merchantTransactionId = data_get($payload, 'merchantTransactionID')
            ?? data_get($payload, 'merchant_transaction_id');

        $statusCode = (string) (CellulantIpnPayload::paymentStatusCode($payload) ?? '');

        $order = $this->findOrderFromPayload($payload);

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

        $this->applyPaymentOutcome($order, $payment, $statusCode, $payload, 'ipn');
    }

    protected function lookupPaymentRecord(Order $order, ?Payment $payment): ?array
    {
        $merchantTransactionId = $payment?->transaction_id;

        if ($merchantTransactionId) {
            $record = $this->fetchSinglePayment((string) $merchantTransactionId, $order);

            if ($record !== null) {
                return $record;
            }
        }

        return $this->fetchPaymentFromHistory($order, $payment);
    }

    protected function fetchSinglePayment(string $merchantTransactionId, Order $order): ?array
    {
        $response = $this->client->instoreRequest(
            'GET',
            '/merchants/payments/'.urlencode($merchantTransactionId),
            [],
            [
                'event' => 'single_payment_lookup',
                'order_id' => $order->id,
                'merchant_transaction_id' => $merchantTransactionId,
                'reference' => $order->machine_order_id,
            ],
        );

        $body = $response->json() ?? [];

        if (! $response->successful() || ! data_get($body, 'success')) {
            Log::info('Cellulant single payment lookup failed', [
                'merchant_transaction_id' => $merchantTransactionId,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            return null;
        }

        $record = data_get($body, 'data');

        return is_array($record) ? $record : null;
    }

    protected function fetchPaymentFromHistory(Order $order, ?Payment $payment): ?array
    {
        $settings = CellulantSetting::current();
        $params = array_filter([
            'payerTransactionID' => $order->machine_order_id,
            'counterCode' => $settings->activeCounterCode(),
            'mobileNumber' => $payment?->phone_number,
            'fromDate' => $order->created_at->copy()->subHour()->format('Y-m-d H:i:s'),
            'toDate' => now()->format('Y-m-d H:i:s'),
            'page' => 1,
            'size' => 100,
        ]);

        $response = $this->client->instoreRequest('GET', '/merchants/payments', $params, [
            'event' => 'payment_history',
            'order_id' => $order->id,
            'reference' => $order->machine_order_id,
            'merchant_transaction_id' => $payment?->transaction_id,
        ]);
        $body = $response->json() ?? [];

        if (! $response->successful() || ! data_get($body, 'success')) {
            Log::info('Cellulant payment history lookup failed', [
                'order_id' => $order->id,
                'reference' => $order->machine_order_id,
                'http_status' => $response->status(),
                'body' => $body,
            ]);

            return null;
        }

        return $this->matchHistoryRecord(data_get($body, 'data', []), $order, $payment);
    }

    protected function matchHistoryRecord(mixed $records, Order $order, ?Payment $payment): ?array
    {
        if (! is_array($records) || $records === []) {
            return null;
        }

        $candidates = array_is_list($records) ? $records : [$records];

        foreach ($candidates as $record) {
            if (! is_array($record)) {
                continue;
            }

            if ($this->historyRecordMatchesOrder($record, $order, $payment)) {
                return $record;
            }
        }

        return null;
    }

    protected function historyRecordMatchesOrder(array $record, Order $order, ?Payment $payment): bool
    {
        $payerTransactionId = (string) data_get($record, 'payerTransactionID', '');
        $reference = (string) data_get($record, 'extraInformation.reference', '');
        $merchantTransactionId = (string) data_get($record, 'merchantTransactionID', '');

        if ($payerTransactionId !== '' && $payerTransactionId === $order->machine_order_id) {
            return true;
        }

        if ($reference !== '' && in_array($reference, [$order->machine_order_id, $order->third_party_order_id], true)) {
            return true;
        }

        if ($merchantTransactionId !== '' && $payment?->transaction_id === $merchantTransactionId) {
            return true;
        }

        return false;
    }

    protected function applyPaymentRecord(Order $order, ?Payment $payment, array $record, string $source): void
    {
        $statusCode = (string) data_get($record, 'paymentStatus', '');

        if ($statusCode === '') {
            return;
        }

        $context = [
            'requestStatusCode' => $statusCode,
            'requestStatusDescription' => data_get($record, 'statusDescription'),
            'statusDescription' => data_get($record, 'statusDescription'),
        ];

        if ($payment) {
            $payment->update([
                'transaction_id' => (string) (
                    data_get($record, 'merchantTransactionID')
                    ?? $payment->transaction_id
                ),
                'provider_response' => array_merge($payment->provider_response ?? [], [
                    $source => $record,
                ]),
            ]);
        }

        $this->applyPaymentOutcome($order, $payment?->fresh(), $statusCode, $context, $source);
    }

    protected function applyPaymentOutcome(
        Order $order,
        ?Payment $payment,
        string $statusCode,
        array $context,
        string $source,
    ): void {
        if ($this->isPaidStatus($statusCode, $context)) {
            if ($payment) {
                $payment->update(['status' => 'successful']);
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('Cellulant payment marked paid', [
                'order_id' => $order->id,
                'source' => $source,
                'status_code' => $statusCode,
            ]);

            IntegrationLogger::log([
                'direction' => 'inbound',
                'channel' => 'payment_sync',
                'event' => $source,
                'order_id' => $order->id,
                'reference' => $order->machine_order_id,
                'merchant_transaction_id' => $payment?->transaction_id,
                'success' => true,
                'message' => 'Order marked paid from '.$source,
                'response_payload' => $context,
            ]);

            return;
        }

        if ($this->isFailedStatus($statusCode, $context)) {
            if ($payment) {
                $payment->update(['status' => 'failed']);
            }

            $order->update(['payment_status' => 'failed']);

            Log::info('Cellulant payment marked failed', [
                'order_id' => $order->id,
                'source' => $source,
                'status_code' => $statusCode,
            ]);

            IntegrationLogger::log([
                'direction' => 'inbound',
                'channel' => 'payment_sync',
                'event' => $source,
                'order_id' => $order->id,
                'reference' => $order->machine_order_id,
                'merchant_transaction_id' => $payment?->transaction_id,
                'success' => false,
                'message' => 'Order marked failed from '.$source,
                'response_payload' => $context,
            ]);
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

        $description = strtolower((string) (
            data_get($payload, 'requestStatusDescription')
            ?? data_get($payload, 'statusDescription', '')
        ));

        return str_contains($description, 'fully paid')
            || str_contains($description, 'payment accepted');
    }

    protected function isFailedStatus(string $statusCode, array $payload): bool
    {
        if (in_array($statusCode, ['138', '141', '180', '216', '99', '101', '102'], true)) {
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
