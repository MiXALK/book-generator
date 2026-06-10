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
                'timeout' => 30,
            ],
            'qwen' => [
                'base_url' => 'https://ws-aw3tb2gara5rhvyb.ap-southeast-1.maas.aliyuncs.com/compatible-mode/v1',
                'model' => 'qwen3.6-flash',
                'timeout' => 30,
                'request' => [
                    'response_format' => ['type' => 'json_object'],
                ],
            ],
        ],
    ],
];
