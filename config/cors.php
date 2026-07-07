<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API is consumed by the Vue frontends hosted on Vercel. Auth is
    | Bearer-token based (no cookies), so credentials are not required. Any
    | *.vercel.app origin is allowed out of the box; add custom domains via
    | the comma-separated FRONTEND_URLS environment variable.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('FRONTEND_URLS', '')))
    )),

    'allowed_origins_patterns' => ['#^https://[a-z0-9-]+\.vercel\.app$#i'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
