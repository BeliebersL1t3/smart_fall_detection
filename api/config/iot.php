<?php

return [
    // Default API untuk ESP32 jika belum diset di Settings (contoh: http://192.168.1.5:8000/api)
    'api_base' => env('IOT_API_BASE'),

    'ws_host' => env('IOT_WS_HOST', '127.0.0.1'),
    'ws_port' => (int) env('IOT_WS_PORT', 6001),
    'ws_secret' => env('IOT_WS_SECRET', 'careguard-ws-secret-change-me'),
    'ws_enabled' => env('IOT_WS_ENABLED', true),
];
