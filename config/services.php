<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('OPENAI_MODEL', 'gpt-5.4'),
        /** Имя ассистента в чате (латиница, чтобы было понятно всем; для русского ответа модель всё равно ответит по-русски). */
        'agent_name' => env('OPENAI_AGENT_NAME', 'Avi'),
        /** Ласковое имя в шутливых сообщениях при сбое API (sprintf, один аргумент — agent_nickname). */
        'agent_nickname' => env('OPENAI_AGENT_NICKNAME', 'Aviosha'),
        /**
         * Сообщения при недоступности OpenAI (sprintf, один аргумент — agent_nickname). По умолчанию EN.
         */
        'unavailable_messages' => [
            '%s dashed off to the hangar to sort something out. Try again in a minute.',
            '%s is on lunch. Even neural nets eat. Try again shortly.',
            '%s got pulled into a shift handover. Retry in a bit — usually it passes.',
            '%s froze for a moment — refresh or send again in a couple of minutes.',
            '%s is temporarily unavailable: the smart reply service is having a moment. Try later.',
            '%s went for coffee. Coffee is basically in the manual. Back soon.',
        ],
        /** HTTP timeout for v1/responses (seconds). */
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT', 120),
        /** Retries on 5xx, 429, and error.type server_error (max 8). */
        'retry_attempts' => (int) env('OPENAI_RETRY_ATTEMPTS', 4),
    ],

];
