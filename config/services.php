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

    'mqtt' => [
        'host' => env('MQTT_HOST', '192.168.50.100'),
        'ws_port' => env('MQTT_WS_PORT', ''),
        'client_id' => env('MQTT_CLIENT_ID', 'WebClient'),
        'username' => env('MQTT_AUTH_USERNAME', ''),
        'password' => env('MQTT_AUTH_PASSWORD', ''),
    ],

    'influxdb' => [
        'url' => (function() {
            $url = env('INFLUXDB_URL', 'http://192.168.50.100:8086');
            if (!str_starts_with($url, 'http')) {
                $url = str_contains($url, ':') ? "http://{$url}" : "https://{$url}";
            }
            return $url;
        })(),
        'token' => env('INFLUXDB_TOKEN', ''),
        'org' => env('INFLUXDB_ORG', ''),
        'bucket' => env('INFLUXDB_BUCKET', ''),
        'bucket_solar' => env('INFLUXDB_BUCKET_SOLAR', 'solar_data'),
    ],

    'bmkg' => [
        'adm4' => env('BMKG_ADM4', '32.16.09.2001'),
    ],

    'ai_server' => [
        'url'     => env('AI_SERVER_URL', 'http://localhost:8000'),
        'api_key' => env('AI_SERVER_API_KEY', ''),
    ],

];
