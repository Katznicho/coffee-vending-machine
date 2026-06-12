<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefundOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(PaymentProviderInterface $paymentProvider): void
    {
        $order = Order::find($this->orderId);

        if (! $order || $order->payment_status !== 'paid') {
            return;
        }

        $paymentProvider->refund($order);
    }
}
