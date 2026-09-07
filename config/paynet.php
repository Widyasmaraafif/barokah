<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayNet Gateway (server-only)
    |--------------------------------------------------------------------------
    |
    | TBC (spec §24 item 1 / §15.6): exact PayNet base URL, auth method,
    | FPX vs DuitNow payload shapes, signature algorithm, redirect vs QR
    | flows, and sandbox/production credentials are all unresolved. Place
    | holders stay until PayNet docs/credentials are provided. Never expose
    | these values to the frontend; they are read server-side only.
    |
    */

    // TBC_PAYNET_API_BASE: real PayNet endpoint unknown; placeholder only.
    'base_url' => env('TBC_PAYNET_API_BASE', env('PAYNET_BASE_URL', '')),

    'merchant_id' => env('TBC_PAYNET_MERCHANT_ID', env('PAYNET_MERCHANT_ID', '')),

    // Shared secret for callback/webhook HMAC verification (TBC algorithm).
    'secret' => env('TBC_PAYNET_SECRET', env('PAYNET_SECRET', '')),

    'sandbox' => env('PAYNET_SANDBOX', true),

    // TBC_PAYNET_TIMEOUT: provider timeout/retry policy unconfirmed.
    'timeout' => (int) env('PAYNET_TIMEOUT', 15),

];
