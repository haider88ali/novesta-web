<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Nova plans, quotas and AI gateway settings
|--------------------------------------------------------------------------
|
| Limits are per calendar month. "voice_minutes" is spoken (text-to-speech)
| audio. Prices are what customers pay in USD; the Paddle price ids come
| from the Paddle dashboard (Catalog → Products → Prices).
|
*/

return [

    // Comma-separated emails that may open the admin panel at /admin
    'admin_emails' => env('NOVA_ADMIN_EMAILS', ''),

    'trial_days' => (int) env('NOVA_TRIAL_DAYS', 7),
    'trial_plan' => 'pro',

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'paddle_price_id' => null,
            'description' => 'Try Nova with everyday questions.',
            'limits' => ['messages' => 50, 'searches' => 10, 'designs' => 2, 'voice_minutes' => 5, 'devices' => 1],
            'features' => ['50 messages / month', '10 web searches', '2 UI designs', 'Computer control & files', '1 device'],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 12,
            'paddle_price_id' => env('PADDLE_PRICE_PRO'),
            'description' => 'For people who use Nova every day.',
            'limits' => ['messages' => 2000, 'searches' => 300, 'designs' => 50, 'voice_minutes' => 120, 'devices' => 2],
            'features' => ['2,000 messages / month', '300 web searches', '50 UI designs → Figma', '120 min Urdu/AI voice', '2 devices'],
        ],
        'business' => [
            'name' => 'Business',
            'price' => 29,
            'paddle_price_id' => env('PADDLE_PRICE_BUSINESS'),
            'description' => 'For heavy users and small teams.',
            'limits' => ['messages' => 6000, 'searches' => 1000, 'designs' => 200, 'voice_minutes' => 500, 'devices' => 5],
            'features' => ['6,000 messages / month', '1,000 web searches', '200 UI designs → Figma', '500 min Urdu/AI voice', '5 devices', 'Priority support'],
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
    ],

];
