<?php

return [
    'username' => env('AFT_USERNAME'),
    'apikey' => env('AFT_APIKEY'),
    'enable_sms' => env('AFT_ENABLE_SMS', false),
    'appKey'=>env('JBB_APP_KEY'),
    'appSecret'=>env('JBB_APP_SECRET'),
    'profileId'=>env('JBB_PROFILE'),
    'AppLink'=>env('JBB_APP_URL')
];
