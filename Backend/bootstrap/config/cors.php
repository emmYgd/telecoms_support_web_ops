<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [' https://api.naijasub.com/api/v1/* '],

    'allowed_methods' => ['POST', 'GET', 'DELETE', 'PUT', '*'],

    'allowed_origins' => ['https://localhost:8080', 'https://app.naijasub.com'],

    'allowed_origins_patterns' => ['*'],

    'allowed_headers' => ['Access-Control-Allow-Origin'],

    'exposed_headers' => [true],

    'max_age' => 0,

    'supports_credentials' => true, //false,

];
