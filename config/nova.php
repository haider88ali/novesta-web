<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Novesta plans, quotas and AI gateway settings
|--------------------------------------------------------------------------
|
| Limits are per calendar month. "voice_minutes" is spoken (text-to-speech)
| audio. Prices are what customers pay in USD; the Stripe price ids come
| from the Stripe dashboard (Product catalog → a product → its monthly price, "price_…").
|
*/

return [

    // Comma-separated emails that may open the admin panel at /admin
    'admin_emails' => env('NOVA_ADMIN_EMAILS', ''),

    // Who runs Novesta, for the Terms, Privacy and Refund pages and the site footer
    'legal' => [
        'operator' => env('NOVA_LEGAL_NAME', 'Haider Ali'),
        'country' => env('NOVA_LEGAL_COUNTRY', 'Pakistan'),
        'support_email' => env('NOVA_SUPPORT_EMAIL', 'support@example.com'),
        'updated' => '28 October 2026',
    ],

    'trial_days' => (int) env('NOVA_TRIAL_DAYS', 7),
    'trial_plan' => 'pro',

    // Stripe Tax: work out sales tax/VAT at checkout (turn on Stripe Tax in the Stripe dashboard first)
    'stripe_tax' => (bool) env('STRIPE_TAX', false),

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'stripe_price_id' => null,
            'description' => 'Try Novesta with everyday questions.',
            'limits' => ['messages' => 50, 'searches' => 10, 'designs' => 2, 'voice_minutes' => 5, 'devices' => 1],
            'features' => ['50 messages / month', '10 web searches', '2 UI designs', 'Computer control & files', '1 device'],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 12,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'description' => 'For people who use Novesta every day.',
            'limits' => ['messages' => 2000, 'searches' => 300, 'designs' => 50, 'voice_minutes' => 120, 'devices' => 2],
            'features' => ['2,000 messages / month', '300 web searches', '50 UI designs → Figma', '120 min AI voice', '2 devices'],
        ],
        'business' => [
            'name' => 'Business',
            'price' => 29,
            'stripe_price_id' => env('STRIPE_PRICE_BUSINESS'),
            'description' => 'For heavy users and small teams.',
            'limits' => ['messages' => 6000, 'searches' => 1000, 'designs' => 200, 'voice_minutes' => 500, 'devices' => 5],
            'features' => ['6,000 messages / month', '1,000 web searches', '200 UI designs → Figma', '500 min AI voice', '5 devices', 'Priority support'],
        ],
    ],

    'downloads' => [
        'mac' => env('NOVA_DOWNLOAD_MAC'),
        'windows' => env('NOVA_DOWNLOAD_WINDOWS'),
    ],

    /*
    | AI gateway: the desktop app sends OpenAI-format requests here with its
    | device token; the server adds the real key, enforces quotas and logs cost.
    */
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'chat_models' => ['gpt-4o-mini', 'gpt-4.1-mini', 'gpt-4.1'],
        'tts_models' => ['gpt-4o-mini-tts'],
        'stt_models' => ['gpt-4o-mini-transcribe'],
        // Language hints the desktop app may send with a recording (ISO 639-1; Roman Urdu is sent as ur)
        'stt_languages' => ['en', 'ur', 'hi', 'ar', 'bn', 'fa', 'tr', 'es', 'fr', 'de', 'pt', 'it', 'ru', 'zh', 'ja', 'ko', 'id'],
        'tts_voices' => ['nova', 'alloy', 'shimmer', 'echo', 'onyx', 'fable', 'coral', 'sage'],
        'max_output_tokens' => 16000,
        'allowed_tools' => ['web_search', 'function'],
    ],

    // USD per 1M tokens, used for the cost shown in the admin panel. Check current OpenAI pricing.
    'pricing' => [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
        'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
        'web_search_call' => 0.01,
        'tts_minute' => 0.015,
        'stt_minute' => 0.003,
    ],

];
