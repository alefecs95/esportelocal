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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp' => [
        'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'http://whatsapp:3000'),
        'gateway_token' => env('WHATSAPP_GATEWAY_TOKEN'),
    ],

    'consultas' => [
        'max_consultas_diarias_por_tenant' => env('CONSULTAS_MAX_DIARIAS_POR_TENANT', 200),
        'max_consultas_diarias_cpf' => env('CONSULTAS_MAX_DIARIAS_CPF', 200),
        'max_consultas_diarias_tse' => env('CONSULTAS_MAX_DIARIAS_TSE', 200),
    ],

];
