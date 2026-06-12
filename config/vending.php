<?php

return [
    'verify_signature' => env('VENDING_VERIFY_SIGNATURE', true),
    'default_channel_id' => env('VENDING_DEFAULT_CHANNEL_ID', '36'),
    'order_expiry_minutes' => (int) env('VENDING_ORDER_EXPIRY_MINUTES', 15),
];
