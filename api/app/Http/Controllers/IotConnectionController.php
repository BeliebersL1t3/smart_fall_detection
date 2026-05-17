<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Event;
use App\Support\IotUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IotConnectionController extends Controller
{
    public function status()
    {
        $device = Device::where('user_id', Auth::id())->first();

        if (! $device) {
            return response()->json(['connected' => false, 'message' => 'No device'], 404);
        }

        $eventCount = Event::where('device_id', $device->id)->count();
        $isRecent = $device->last_seen_at && $device->last_seen_at->gt(now()->subSeconds(30));

        return response()->json([
            'connected' => $device->is_online && $isRecent,
            'device_id' => $device->id,
            'is_online' => (bool) $device->is_online,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'last_magnitude' => $device->last_magnitude,
            'battery' => $device->battery_level,
            'battery_level' => $device->battery_level,
            'battery_color' => \App\Support\BatteryHelper::levelColor($device->battery_level),
            'battery_status' => \App\Support\BatteryHelper::statusLabel($device->battery_level),
            'event_count' => $eventCount,
            'api_base' => $device->resolvedApiBaseUrl(),
            'arduino_root' => $device->arduinoApiRootUrl(),
            'ws_url' => $this->wsUrl(),
        ]);
    }

    public function updateApiBase(Request $request)
    {
        $request->validate([
            'api_base_url' => 'required|string|max:255',
        ]);

        $device = Device::where('user_id', Auth::id())->firstOrFail();

        $normalized = IotUrl::normalizeApiBase($request->api_base_url);
        $device->update(['api_base_url' => $normalized]);

        return response()->json([
            'success' => true,
            'message' => 'API Base URL disimpan.',
            'api_base' => $device->resolvedApiBaseUrl(),
            'arduino_root' => $device->arduinoApiRootUrl(),
        ]);
    }

    public function testConnection(Request $request)
    {
        $device = Device::where('user_id', Auth::id())->first();

        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Perangkat tidak ditemukan'], 404);
        }

        $device->update([
            'is_online' => true,
            'last_seen_at' => now(),
            'last_status' => 'normal',
        ]);

        $apiBase = $device->resolvedApiBaseUrl();

        return response()->json([
            'success' => true,
            'message' => 'Koneksi API siap. Gunakan device_token di ESP32.',
            'api_base' => $apiBase,
            'arduino_root' => $device->arduinoApiRootUrl(),
            'endpoints' => [
                'sensor' => $apiBase.'/sensor-data',
                'fall' => $apiBase.'/fall-detected',
                'sos' => $apiBase.'/sos',
            ],
            'device_token' => $device->device_token,
            'ws_url' => $this->wsUrl(),
        ]);
    }

    private function wsUrl(): string
    {
        $host = config('iot.ws_host') === '127.0.0.1'
            ? request()->getHost()
            : config('iot.ws_host');

        return sprintf('ws://%s:%d', $host, config('iot.ws_port'));
    }
}
