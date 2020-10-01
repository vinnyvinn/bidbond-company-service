<?php

return [
    'informa' => [
        'url' => env('INFORMA_URL'),
        'key' => env('INFORMA_KEY'),
        'phone_search_active' => env('INFORMA_PHONE_SEARCH_ACTIVE'),
    ],
    'gateway' => [
        'base_uri' => env('GATEWAY_BASE_URL'),
        'secret' => env('GATEWAY_SECRET'),
    ],
    'bidbonds' => [
        'base_uri' => env('BIDBOND_SERVICE_BASE_URL'),
        'secret' => env('BIDBOND_SERVICE_SECRET'),
    ],
    'sidian' => [
        'sms_url' => env('SMS_URL'),
        'sms_apikey' => env('SMS_KEY')
    ],
    'enable_sms' => env('ENABLE_SMS', false),
    'enable_create_account' => env('ENABLE_CREATE_ACCOUNT', false),
];
