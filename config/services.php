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

    /*
    |--------------------------------------------------------------------------
    | Groq AI — API pour LLM (Llama 3.1, Mixtral, etc.)
    |--------------------------------------------------------------------------
    | Obtenir une clé : https://console.groq.com/keys
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LeekPay — Paiement Mobile Money (XOF / Togo)
    |--------------------------------------------------------------------------
    | Clés disponibles sur https://leekpay.me/dashboard → Clés API
    */
    'leekpay' => [
        'secret_key'  => env('LEEKPAY_SECRET_KEY', ''),
        'public_key'  => env('LEEKPAY_PUBLIC_KEY', ''),
        'webhook_url' => env('LEEKPAY_WEBHOOK_URL', ''),
        'return_url'  => env('LEEKPAY_RETURN_URL', ''),
        'cancel_url'  => env('LEEKPAY_CANCEL_URL', ''),
    ],

];
