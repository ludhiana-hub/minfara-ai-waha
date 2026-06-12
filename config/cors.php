<?php

return [

    /*
     * CORS paths — semua /api/* endpoints
     */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    /*
     * Izinkan Next.js CMS frontend.
     * Di production, ganti '*' dengan domain spesifik.
     */
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * true agar XSRF-TOKEN cookie bisa dikirim dari frontend
     */
    'supports_credentials' => true,

];
