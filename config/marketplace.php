<?php

use App\Enums\SettingType;

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace Identity
    |--------------------------------------------------------------------------
    |
    | Non-secret marketplace defaults. Values stored in the settings table
    | always take precedence over these defaults.
    |
    */

    'name' => env('MARKETPLACE_NAME', 'Barokah'),

    'currency' => [
        'code' => env('MARKETPLACE_CURRENCY_CODE', 'MYR'),
        'symbol' => env('MARKETPLACE_CURRENCY_SYMBOL', 'RM'),
        'decimals' => 2,
    ],

    'branding' => [
        'site_name' => env('MARKETPLACE_NAME', 'Barokah'),
        'primary_color' => '#ee4d2d',
        'primary_hover_color' => '#d94426',
        'primary_soft_color' => '#fff1ed',
        'secondary_color' => '#113366',
    ],

    'shipping' => [
        // Fixed Rate only in Task 6 (spec §16); external provider lands in Task 9.
        'method' => env('SHIPPING_METHOD', 'fixed'),
        'fixed_rate' => env('SHIPPING_FIXED_RATE', '5.00'),
    ],

    'checkout' => [
        // TBC (spec §24 item 14): expiration default 30 min until confirmed.
        'order_expiration_minutes' => (int) env('CHECKOUT_ORDER_EXPIRATION_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback definitions served by the SettingsService until an admin
    | overrides them in the database. Priority: DB > env > default, because
    | env-backed values below are baked into config while database rows
    | always win in SettingsService::all(). Rows flagged private are never
    | exposed via the public endpoint; sensitive private values are masked
    | in the admin API. Keys whose business behavior is undefined stay TBC
    | (spec §24) and are stored as toggles/defaults only.
    |
    | Groups (11, spec §17): general, branding, currency, marketplace,
    | checkout, payment, shipping, localization, contact, seo, email.
    |
    */

    'settings_defaults' => [
        // General.
        'general.site_tagline' => [
            'value' => env('MARKETPLACE_TAGLINE', 'Barokah Multi-Seller Marketplace'),
            'type' => SettingType::String,
            'group' => 'general',
            'is_public' => true,
        ],
        'general.maintenance_mode' => [
            'value' => false,
            'type' => SettingType::Boolean,
            'group' => 'general',
            'is_public' => true,
        ],
        'general.allow_registration' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'general',
            'is_public' => true,
        ],

        // Branding (spec §18.2: design tokens are defaults, settings win).
        'branding.site_name' => [
            'value' => env('MARKETPLACE_NAME', 'Barokah'),
            'type' => SettingType::String,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.logo_url' => [
            'value' => '',
            'type' => SettingType::Image,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.favicon_url' => [
            'value' => '',
            'type' => SettingType::Image,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.primary_color' => [
            'value' => '#ee4d2d',
            'type' => SettingType::Color,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.primary_hover_color' => [
            'value' => '#d94426',
            'type' => SettingType::Color,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.primary_soft_color' => [
            'value' => '#fff1ed',
            'type' => SettingType::Color,
            'group' => 'branding',
            'is_public' => true,
        ],
        'branding.secondary_color' => [
            'value' => '#113366',
            'type' => SettingType::Color,
            'group' => 'branding',
            'is_public' => true,
        ],

        // Currency (MYR/RM default, spec §4 stack).
        'currency.code' => [
            'value' => env('MARKETPLACE_CURRENCY_CODE', 'MYR'),
            'type' => SettingType::String,
            'group' => 'currency',
            'is_public' => true,
        ],
        'currency.symbol' => [
            'value' => env('MARKETPLACE_CURRENCY_SYMBOL', 'RM'),
            'type' => SettingType::String,
            'group' => 'currency',
            'is_public' => true,
        ],
        'currency.decimals' => [
            'value' => 2,
            'type' => SettingType::Integer,
            'group' => 'currency',
            'is_public' => true,
        ],
        // TBC (spec §24 item 18 + §19): separators exist so a future
        // multi-currency/format change needs no code change.
        'currency.thousands_separator' => [
            'value' => ',',
            'type' => SettingType::String,
            'group' => 'currency',
            'is_public' => true,
        ],
        'currency.decimal_separator' => [
            'value' => '.',
            'type' => SettingType::String,
            'group' => 'currency',
            'is_public' => true,
        ],

        // Marketplace.
        'marketplace.name' => [
            'value' => env('MARKETPLACE_NAME', 'Barokah'),
            'type' => SettingType::String,
            'group' => 'marketplace',
            'is_public' => true,
        ],
        'marketplace.description' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'marketplace',
            'is_public' => true,
        ],
        'marketplace.status' => [
            'value' => 'open',
            'type' => SettingType::String,
            'group' => 'marketplace',
            'is_public' => true,
        ],
        'marketplace.seller_registration_enabled' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'marketplace',
            'is_public' => true,
        ],
        // TBC (spec §24 item 13): toggles only, no review/inventory
        // backend is assumed until confirmed.
        'marketplace.reviews_enabled' => [
            'value' => false,
            'type' => SettingType::Boolean,
            'group' => 'marketplace',
            'is_public' => true,
        ],
        'marketplace.inventory_tracking_enabled' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'marketplace',
            'is_public' => true,
        ],

        // Checkout.
        // TBC (spec §24 item 14): expiration default 30 min until confirmed.
        'checkout.order_expiration_minutes' => [
            'value' => 30,
            'type' => SettingType::Integer,
            'group' => 'checkout',
            'is_public' => true,
        ],
        // TBC (spec §24 item 14): min/max order amounts unconfirmed.
        'checkout.min_order_amount' => [
            'value' => '0.00',
            'type' => SettingType::String,
            'group' => 'checkout',
            'is_public' => true,
        ],
        'checkout.max_order_amount' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'checkout',
            'is_public' => true,
        ],
        'checkout.guest_checkout_enabled' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'checkout',
            'is_public' => true,
        ],

        // Payment (spec §15; secrets stay server-only, spec §20).
        // TBC (spec §24 item 1): no admin payment UI yet; both methods stay
        // enabled by default until toggles are confirmed.
        'payment.fpx_enabled' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'payment',
            'is_public' => true,
        ],
        'payment.duitnow_enabled' => [
            'value' => true,
            'type' => SettingType::Boolean,
            'group' => 'payment',
            'is_public' => true,
        ],
        'payment.gateway' => [
            'value' => 'paynet',
            'type' => SettingType::String,
            'group' => 'payment',
            'is_public' => false,
        ],
        'payment.merchant_id' => [
            'value' => env('TBC_PAYNET_MERCHANT_ID', ''),
            'type' => SettingType::String,
            'group' => 'payment',
            'is_public' => false,
        ],
        'payment.secret_key' => [
            'value' => env('TBC_PAYNET_SECRET', ''),
            'type' => SettingType::String,
            'group' => 'payment',
            'is_public' => false,
        ],
        'payment.sandbox_enabled' => [
            'value' => env('PAYNET_SANDBOX', true),
            'type' => SettingType::Boolean,
            'group' => 'payment',
            'is_public' => false,
        ],
        // TBC_PAYNET_API_BASE (spec §24 item 1): real endpoint unknown.
        'payment.api_base' => [
            'value' => env('TBC_PAYNET_API_BASE', ''),
            'type' => SettingType::String,
            'group' => 'payment',
            'is_public' => false,
        ],

        // Shipping (spec §16.3; secrets stay server-only, spec §20).
        // Fixed Rate only (spec §16.1/§16.3); external provider lands in Task 9.
        'shipping.method' => [
            'value' => 'fixed',
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => true,
        ],
        // TBC (spec §24 item 17): default fixed rate until admin confirms.
        'shipping.fixed_rate' => [
            'value' => '5.00',
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => true,
        ],
        // TBC (spec §24 item 17): free-shipping threshold default until confirmed.
        'shipping.free_shipping_enabled' => [
            'value' => false,
            'type' => SettingType::Boolean,
            'group' => 'shipping',
            'is_public' => true,
        ],
        'shipping.free_shipping_threshold' => [
            'value' => '100.00',
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => true,
        ],
        // TBC (spec §24 item 2): external provider unresolved; public
        // metadata only. API secrets stay server-side in config/shipping.php.
        'shipping.provider_name' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => true,
        ],
        'shipping.api_enabled' => [
            'value' => false,
            'type' => SettingType::Boolean,
            'group' => 'shipping',
            'is_public' => true,
        ],
        'shipping.api_key' => [
            'value' => env('TBC_SHIPPING_API_KEY', ''),
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => false,
        ],
        'shipping.api_secret' => [
            'value' => env('TBC_SHIPPING_API_SECRET', ''),
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => false,
        ],
        'shipping.api_base_url' => [
            'value' => env('TBC_SHIPPING_API_BASE_URL', ''),
            'type' => SettingType::String,
            'group' => 'shipping',
            'is_public' => false,
        ],

        // Localization (English default, Malay via GTranslate, spec §19).
        'localization.default_language' => [
            'value' => 'en',
            'type' => SettingType::String,
            'group' => 'localization',
            'is_public' => true,
        ],
        'localization.available_languages' => [
            'value' => ['en', 'ms'],
            'type' => SettingType::Json,
            'group' => 'localization',
            'is_public' => true,
        ],
        'localization.timezone' => [
            'value' => 'Asia/Kuala_Lumpur',
            'type' => SettingType::String,
            'group' => 'localization',
            'is_public' => true,
        ],
        'localization.date_format' => [
            'value' => 'Y-m-d',
            'type' => SettingType::String,
            'group' => 'localization',
            'is_public' => true,
        ],
        'localization.time_format' => [
            'value' => 'H:i',
            'type' => SettingType::String,
            'group' => 'localization',
            'is_public' => true,
        ],
        // TBC (spec §24 item 15): GTranslate plan unconfirmed; toggle only.
        'localization.gtranslate_enabled' => [
            'value' => false,
            'type' => SettingType::Boolean,
            'group' => 'localization',
            'is_public' => true,
        ],

        // Contact (footer content is dynamic from settings, spec §18.3).
        'contact.email' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ],
        'contact.phone' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ],
        'contact.address' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ],
        'contact.business_hours' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ],
        // TBC (spec §24 item 19): notification channels unconfirmed.
        'contact.whatsapp' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'contact',
            'is_public' => true,
        ],

        // SEO (global settings now, page-level later — TBC spec §24 item 20).
        'seo.meta_title' => [
            'value' => 'Barokah Marketplace',
            'type' => SettingType::String,
            'group' => 'seo',
            'is_public' => true,
        ],
        'seo.meta_description' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'seo',
            'is_public' => true,
        ],
        'seo.keywords' => [
            'value' => '',
            'type' => SettingType::String,
            'group' => 'seo',
            'is_public' => true,
        ],
        'seo.og_image' => [
            'value' => '',
            'type' => SettingType::Image,
            'group' => 'seo',
            'is_public' => true,
        ],

        // Email (behavior TBC, spec §24 item 19; secrets server-only).
        'email.from_name' => [
            'value' => env('MARKETPLACE_NAME', 'Barokah'),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => true,
        ],
        'email.from_address' => [
            'value' => env('MAIL_FROM_ADDRESS', ''),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => false,
        ],
        'email.smtp_host' => [
            'value' => env('MAIL_HOST', ''),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => false,
        ],
        'email.smtp_port' => [
            'value' => (int) env('MAIL_PORT', 587),
            'type' => SettingType::Integer,
            'group' => 'email',
            'is_public' => false,
        ],
        'email.smtp_username' => [
            'value' => env('MAIL_USERNAME', ''),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => false,
        ],
        'email.smtp_password' => [
            'value' => env('MAIL_PASSWORD', ''),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => false,
        ],
        'email.smtp_encryption' => [
            'value' => env('MAIL_SCHEME', 'tls'),
            'type' => SettingType::String,
            'group' => 'email',
            'is_public' => false,
        ],
    ],

];
