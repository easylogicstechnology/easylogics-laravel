<?php

/*
 * Reseller subscription payments - Razorpay credentials (CakePHP: app/Config/payment.php + payment_local.php).
 *
 * Keys never live in this file: set them in .env. Only the block named by RAZORPAY_MODE ('test' or 'live') is
 * used, and a key that does not match the mode (rzp_live_ key while mode is 'test', or the reverse) is treated
 * as not configured. Leave the mode as 'test' until live go-live has been approved.
 */
return [
    'Razorpay' => [
        'mode' => env('RAZORPAY_MODE', 'test'),
        'test' => [
            'key_id' => env('RAZORPAY_TEST_KEY_ID', ''),
            'key_secret' => env('RAZORPAY_TEST_KEY_SECRET', ''),
            'webhook_secret' => env('RAZORPAY_TEST_WEBHOOK_SECRET', ''),
        ],
        'live' => [
            'key_id' => env('RAZORPAY_LIVE_KEY_ID', ''),
            'key_secret' => env('RAZORPAY_LIVE_KEY_SECRET', ''),
            'webhook_secret' => env('RAZORPAY_LIVE_WEBHOOK_SECRET', ''),
        ],
    ],
];
