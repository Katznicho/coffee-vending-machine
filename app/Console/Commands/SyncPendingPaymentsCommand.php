<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\Console\Command;

class SyncPendingPaymentsCommand extends Command
{
    protected $signature = 'payments:sync-pending
                            {--limit= : Maximum number of orders to check per run}';

    protected $description = 'Sync pending vending orders with Cellulant when IPN or machine polling is delayed';

    public function handle(PaymentProviderInterface $paymentProvider): int
    {
        if (! config('vending.sync_pending_payments', true)) {
            $this->info('Pending payment sync is disabled.');

            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('vending.sync_pending_batch_size', 50));

        $orders = Order::query()
            ->where('payment_status', 'pending')
            ->whereHas('payments')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No pending orders to sync.');

            return self::SUCCESS;
        }

        $synced = 0;
        $paid = 0;
        $failed = 0;
        $expired = 0;

        foreach ($orders as $order) {
            $before = $order->payment_status;

            $paymentProvider->syncPendingPaymentStatus($order);
            $order->refresh()->markExpiredIfNeeded();
            $order->refresh();

            if ($before !== $order->payment_status) {
                $synced++;
            }

            match ($order->payment_status) {
                'paid' => $paid++,
                'failed' => $failed++,
                'expired' => $expired++,
                default => null,
            };
        }

        $this->info(sprintf(
            'Checked %d pending order(s): %d updated, %d paid, %d failed, %d expired.',
            $orders->count(),
            $synced,
            $paid,
            $failed,
            $expired,
        ));

        return self::SUCCESS;
    }
}
