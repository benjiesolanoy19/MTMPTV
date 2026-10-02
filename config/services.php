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

    'map' => [
        'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
        'center_latitude' => (float) env('MAP_CENTER_LATITUDE', 14.5995),
        'center_longitude' => (float) env('MAP_CENTER_LONGITUDE', 120.9842),
        'default_zoom' => (int) env('MAP_DEFAULT_ZOOM', 13),
        'location_zoom' => (int) env('MAP_LOCATION_ZOOM', 17),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limits
    |--------------------------------------------------------------------------
    |
    | Failed logins and registrations are throttled per IP address. The defaults
    | are deliberately generous so local development is not interrupted; set the
    | values lower (or override in .env) for an internet-facing deployment.
    |
    */

    'throttle' => [
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 20),
        'login_decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 1),
        'register_max_attempts' => (int) env('REGISTER_MAX_ATTEMPTS', 20),
        'register_decay_minutes' => (int) env('REGISTER_DECAY_MINUTES', 1),
    ],

    'location_online_timeout' => env('LOCATION_ONLINE_TIMEOUT', 60),

];
