<?php

return [
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    'ai_text' => [
        'driver' => env('AI_TEXT_DRIVER', 'qwen'),
        'api_key' => env('AI_TEXT_API_KEY'),
        'drivers' => [
            'deepseek' => [
                'base_url' => 'https://api.deepseek.com/v1',
                'model' => 'deepseek-chat',
                'timeout' => 90,
            ],
            'qwen' => [
                'base_url' => 'https://ws-aw3tb2gara5rhvyb.ap-southeast-1.maas.aliyuncs.com/compatible-mode/v1',
                'model' => 'qwen3.6-flash',
                'timeout' => 90,
                'request' => [
                    'response_format' => ['type' => 'json_object'],
                    'enable_thinking' => false,
                ],
            ],
        ],
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'price_id' => env('STRIPE_PRICE_ID'),
        'success_url' => env('STRIPE_SUCCESS_URL', 'http://localhost:3000/billing/success'),
        'cancel_url' => env('STRIPE_CANCEL_URL', 'http://localhost:3000/dashboard'),
        'portal_return_url' => env('STRIPE_PORTAL_RETURN_URL', 'http://localhost:3000/dashboard'),
    ],

    'ai_image' => [
        'driver' => env('AI_IMAGE_DRIVER', 'yandexart'),
        'api_key' => env('AI_IMAGE_API_KEY'),
        'folder_id' => env('AI_IMAGE_FOLDER_ID'),
        'drivers' => [
            'aliceaiart' => [
                'base_url' => 'https://ai.api.cloud.yandex.net/v1',
                'model' => 'aliceai-image-art-3.0',
                'timeout' => 480,
                'max_prompt_length' => 500,
                'size' => '666x832',
            ],
            'yandexart' => [
                'base_url' => 'https://llm.api.cloud.yandex.net/foundationModels/v1',
                'operations_url' => 'https://llm.api.cloud.yandex.net/operations',
                'model' => 'yandex-art/latest',
                'timeout' => 480,
                'max_prompt_length' => 500,
                'poll_interval_seconds' => 2,
                'aspect_ratio' => [
                    'widthRatio' => '4',
                    'heightRatio' => '5',
                ],
            ],
            'openai' => [
                'base_url' => 'https://api.openai.com/v1',
                'model' => 'dall-e-3',
                'timeout' => 480,
                'max_prompt_length' => 500,
                'size' => '1024x1024',
            ],
        ],
    ],

    'book_photo' => [
        'max_kb' => 5120,
        'min_width' => 256,
        'min_height' => 256,
        'max_width' => 4096,
        'max_height' => 4096,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    'privacy' => [
        'signed_url_ttl_minutes' => 60,
        'pending_photo_retention_hours' => 24,
        'failed_generation_retention_days' => 7,
    ],

    'observability' => [
        'notify_on_book_ready' => env('OBSERVABILITY_NOTIFY_ON_BOOK_READY', true),
        'book_reader_url' => env('OBSERVABILITY_BOOK_READER_URL', 'http://localhost:3000/books'),
        'job_backoff_seconds' => [30, 120, 300],
        'job_max_attempts' => 3,
    ],

    'admin' => [
        'emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ADMIN_EMAILS', '')),
        ))),
    ],

    'content' => [
        'prompt_min_quality_score' => 3.0,
        'prompt_min_rating_count' => 1,
    ],

    'scaling' => [
        'catalog_cache_ttl_seconds' => (int) env('CATALOG_CACHE_TTL_SECONDS', 3600),
        'layout_cache_ttl_seconds' => 86400,
        'idempotency_ttl_hours' => 24,
        'ai_text_daily_limit' => 20,
        'ai_image_daily_limit' => 50,
        'text_job_timeout_seconds' => 120,
        'layout_job_timeout_seconds' => 300,
    ],

    'cost' => [
        'text_input_token_usd' => 0.00000025, // qwen 3.6 flash
        'text_output_token_usd' => 0.0000015, // qwen 3.6 flash
        'image_generation_usd' => 0.0285, // yandexArt
        'layout_cpu_second_usd' => 0.0,
        'storage_gb_month_usd' => 0.023, // цена за хранение 1GB данных в месяц
        'bandwidth_gb_usd' => 0.09, // расходы на 1GB исходящего трафика
    ],
];
