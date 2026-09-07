<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Non-secret shipping defaults. Database settings (via SettingsService)
    | always take precedence over these defaults. Secrets stay in env vars
    | and are never exposed to the frontend (spec §16.3/§20).
    |
    | External provider details are TBC (spec §24 item 2) until the client
    | confirms provider name, base URL, auth, and request/response shapes.
    | Placeholders below are prefixed TBC_SHIPPING_* and must not be
    | treated as real credentials or endpoints.
    |
    */

    'method' => env('SHIPPING_METHOD', 'fixed'),

    'fixed_rate' => env('SHIPPING_FIXED_RATE', '5.00'),

    'free_shipping_enabled' => env('SHIPPING_FREE_ENABLED', false),

    // TBC (spec §24 item 17): free-shipping threshold default until confirmed.
    'free_shipping_threshold' => env('SHIPPING_FREE_THRESHOLD', '100.00'),

    'provider_name' => env('TBC_SHIPPING_PROVIDER_NAME', null),

    'api_enabled' => env('TBC_SHIPPING_API_ENABLED', false),

    'external' => [
        'base_url' => env('TBC_SHIPPING_API_BASE_URL'),
        'api_key' => env('TBC_SHIPPING_API_KEY'),
        'api_secret' => env('TBC_SHIPPING_API_SECRET'),
        'timeout_seconds' => (int) env('TBC_SHIPPING_TIMEOUT_SECONDS', 10),
    ],

];
