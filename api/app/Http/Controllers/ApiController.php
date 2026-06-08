<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Event;
use App\Services\EmergencyNotifier;

class ApiController extends Controller
{
    public function __construct(
        private EmergencyNotifier $emergencyNotifier
    ) {}
    /**
     * Memperbarui data event (Fall/SOS) DAN Baterai
     */
    public function receiveData(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'type' => 'required|in:auto_fall,manual_sos',
            'acceleration_peak' => 'nullable|numeric',
            'battery_level' => 'nullable|numeric|min:0|max:100'
        ]);

        $device = Device::with('user')->where('device_token', $request->device_token)->first();
        if (!$device) return response()->json(['success' => false], 401);

        // Update baterai jika dikirim
        if ($request->has('battery_level')) {
            $device->update(['battery_level' => $request->battery_level, 'is_online' => true]);
        }

        $status = $request->type === 'manual_sos' ? 'confirmed' : 'pending';
        $event = Event::create([
            'device_id' => $device->id,
            'type' => $request->type,
            'status' => $status,
            'acceleration_peak' => $request->acceleration_peak,
            'occurred_at' => now(),
        ]);

        // 🌟 JIKA STATUSNYA DARURAT (MANUAL SOS)
        if ($request->type === 'manual_sos') {
            $this->emergencyNotifier->notify($event, $device);
        }

        return response()->json(['success' => true], 201);
    }

    /**
     * Fungsi: Update Baterai Saja (Heartbeat dari ESP32)
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'battery_level' => 'required|numeric|min:0|max:100'
        ]);

        $device = Device::where('device_token', $request->device_token)->first();
        if ($device) {
            $device->update([
                'battery_level' => $request->battery_level,
                'is_online' => true,
                'updated_at' => now()
            ]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    /**
     * Update Silent Observer: Kirimkan info jumlah event DAN level baterai terbaru
     */
    public function checkNewData($device_id)
    {
        $device = Device::find($device_id);
        $eventCount = Event::where('device_id', $device_id)->count();

        return response()->json([
            'count' => $eventCount,
            'battery' => $device?->battery_level,
            'battery_color' => $device ? \App\Support\BatteryHelper::levelColor($device->battery_level) : null,
            'battery_status' => $device ? \App\Support\BatteryHelper::statusLabel($device->battery_level) : null,
            'magnitude' => $device?->last_magnitude,
            'is_online' => (bool) ($device?->is_online),
            'last_seen_at' => $device?->last_seen_at?->toIso8601String(),
            'last_status' => $device?->last_status,
        ]);
    }
}