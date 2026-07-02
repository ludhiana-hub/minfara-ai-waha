<?php

return [
    'base_url'        => env('WAHA_URL', 'http://localhost:3000'),
    'session'         => env('WAHA_SESSION', 'default'),
    'api_key'         => env('WAHA_API_KEY', ''),
    'cms_base_url'    => env('CMS_BASE_URL', 'http://187.77.116.47:8080/cms-minfara'),
    'internal_api_key'=> env('INTERNAL_API_KEY', ''),
    'send_throttle'   => [
        'enabled'    => (bool) env('WAHA_SEND_THROTTLE', true),
        'min_delay'  => (int) env('WAHA_SEND_DELAY_MIN', 5),
        'max_delay'  => (int) env('WAHA_SEND_DELAY_MAX', 10),
        // Volume tiers: pilih tier tertinggi yang memenuhi count >= threshold.
        // 1-10 kirim: tanpa bonus | 11-30: +5-15s | 31-50: +15-30s | 51+: +30-60s
        'volume_tiers' => [
            ['threshold' => 11, 'bonus_min' => 5,  'bonus_max' => 15],
            ['threshold' => 31, 'bonus_min' => 15, 'bonus_max' => 30],
            ['threshold' => 51, 'bonus_min' => 30, 'bonus_max' => 60],
        ],
    ],
];
