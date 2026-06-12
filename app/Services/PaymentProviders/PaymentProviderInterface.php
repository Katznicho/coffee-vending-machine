<?php

namespace App\Services\PaymentProviders;

use App\Models\Order;
use App\Models\Payment;

interface PaymentProviderInterface
{
    public function initiateCollection(Order $order, string $phoneNumber): Payment;

    public function refund(Order $order): bool;

    public function handleWebhook(array $payload): void;
}
