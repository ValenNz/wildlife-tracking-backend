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

    'registration' => [
        'captcha' => (bool) env('REGISTRATION_CAPTCHA', true),
        'captcha_min_seconds' => (int) env('REGISTRATION_CAPTCHA_MIN_SECONDS', 2),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
        'production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'finish_url' => env('MIDTRANS_FINISH_URL', 'http://localhost:3000/bookings'),
    ],

    'tourism' => [
        'payment_window_minutes' => (int) env('TOURISM_PAYMENT_WINDOW_MINUTES', 60),
        'bank_name' => env('TOURISM_BANK_NAME', ''),
        'bank_account_number' => env('TOURISM_BANK_ACCOUNT_NUMBER', ''),
        'bank_account_holder' => env('TOURISM_BANK_ACCOUNT_HOLDER', ''),
        'qris_merchant_name' => env('TOURISM_QRIS_MERCHANT_NAME', ''),
        'timezone' => env('TOURISM_TIMEZONE', 'Asia/Jakarta'),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),
        'payment_simulator' => (bool) env('PAYMENT_SIMULATOR_ENABLED', false),
    ],

    'gps_simulator' => [
        'enabled' => (bool) env('GPS_SIMULATOR_ENABLED', false),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY', ''),
        'vision_model' => env('GROQ_VISION_MODEL', 'qwen/qwen3.8-27b'),
        'text_model' => env('GROQ_TEXT_MODEL', 'qwen/qwen3.8-27b'),
        'audio_model' => env('GROQ_AUDIO_MODEL', 'whisper-large-v3-turbo'),
        'timeout' => (int) env('GROQ_TIMEOUT', 60),
    ],

    'ai' => [
        'queue' => (bool) env('AI_QUEUE', false),
    ],

    'ai_vision' => [
        'url' => env('AI_VISION_SERVICE_URL'),
        'token' => env('AI_VISION_SERVICE_TOKEN', env('AI_AUDIO_SERVICE_TOKEN')),
        'timeout' => (int) env('AI_VISION_SERVICE_TIMEOUT', 180),
        'country' => env('AI_COUNTRY', 'IDN'),
        'reid_threshold' => (float) env('AI_REID_THRESHOLD', 0.6),
        'face_threshold' => (float) env('AI_FACE_THRESHOLD', 0.40),
        'face_possible_threshold' => (float) env('AI_FACE_POSSIBLE_THRESHOLD', 0.30),
    ],

    'ai_audio' => [
        'url' => env('AI_AUDIO_SERVICE_URL'),
        'token' => env('AI_AUDIO_SERVICE_TOKEN'),
        'model' => env('AI_AUDIO_SERVICE_MODEL', 'audio-classifier'),
        'timeout' => (int) env('AI_AUDIO_SERVICE_TIMEOUT', 60),
    ],

    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],

];
