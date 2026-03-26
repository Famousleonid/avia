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
        /** Имя ассистента в чате (представление, промпт, приветствие виджета). */
        'agent_name' => env('OPENAI_AGENT_NAME', 'Ави'),
        /** Ласковое имя в шутливых сообщениях при сбое API (подставляется в unavailable_messages как %s). */
        'agent_nickname' => env('OPENAI_AGENT_NICKNAME', 'Авиоша'),
        /**
         * Сообщения при недоступности OpenAI (sprintf, один аргумент — agent_nickname).
         */
        'unavailable_messages' => [
            '%s улетел на базу разобраться с техничкой. Загляните через минуту.',
            '%s на обед. Даже умные нейросети едят. Попробуйте чуть позже.',
            '%s отвлёкся на рейс / смену. Повторите вопрос попозже — обычно всё проходит.',
            '%s зазевался и подвис — так бывает. Обновите или напишите ещё раз через пару минут.',
            '%s временно недоступен: сервис умного ответа капризничает. Зайдите попозже.',
            '%s сбежал на кофе. Кофе обязателен по регламенту. Вернитесь скоро.',
        ],
        /** HTTP timeout for v1/responses (seconds). */
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT', 120),
        /** Retries on 5xx, 429, and error.type server_error (max 8). */
        'retry_attempts' => (int) env('OPENAI_RETRY_ATTEMPTS', 4),
    ],

];
