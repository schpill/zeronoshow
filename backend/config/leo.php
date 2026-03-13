<?php

return [
    'default_bot_name' => env('LEO_DEFAULT_BOT_NAME', 'Léo'),
    'message_log_retention_days' => (int) env('LEO_MESSAGE_LOG_RETENTION_DAYS', 90),
    'throttle' => [
        'max_messages_per_hour' => (int) env('LEO_MAX_MESSAGES_PER_HOUR', 20),
    ],
    'stripe' => [
        'price_id' => env('LEO_STRIPE_PRICE_ID'),
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],
];
