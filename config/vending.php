<?php

return [
    'verify_signature' => env('VENDING_VERIFY_SIGNATURE', true),
    'default_channel_id' => env('VENDING_DEFAULT_CHANNEL_ID', '36'),
    'order_expiry_minutes' => (int) env('VENDING_ORDER_EXPIRY_MINUTES', 15),
    // LE machine protocol sends price as the configured UGX price multiplied by 100.
    'machine_price_divisor' => (int) env('VENDING_MACHINE_PRICE_DIVISOR', 100),
    'sync_pending_payments' => env('VENDING_SYNC_PENDING_PAYMENTS', true),
    'sync_pending_batch_size' => (int) env('VENDING_SYNC_PENDING_BATCH_SIZE', 50),
    'sync_pending_schedule' => env('VENDING_SYNC_PENDING_SCHEDULE', 'everyMinute'),
];
