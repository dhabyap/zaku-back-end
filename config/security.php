<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Controls whether CSP header is added and its behavior.
    |
    */
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | Controls HSTS header. Only applied on HTTPS requests.
    |
    */
    'hsts' => [
        'enabled' => env('SECURITY_HSTS_ENABLED', true),
        'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year in seconds
    ],

];