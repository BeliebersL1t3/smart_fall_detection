<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSocketNotifier
{
    public function broadcast(int $userId, string $type, array $payload): void
    {
        if (! config('iot.ws_enabled')) {
            return;
        }

        $url = sprintf(
            'http://%s:%d/broadcast',
            config('iot.ws_host'),
            config('iot.ws_port')
        );

        try {
            Http::timeout(2)->post($url, [
                'secret' => config('iot.ws_secret'),
                'user_id' => $userId,
                'type' => $type,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::debug('WebSocket broadcast skipped: '.$e->getMessage());
        }
    }
}
