<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Event;

class DeviceController extends Controller
{
    /**
     * Autentikasi sederhana menggunakan device_token
     */
    private function authenticateDevice($token)
    {
        return Device::where('device_token', $token)->first();
    }

    /**
     * Endpoint 1: Menerima event Fall atau Manual SOS dari ESP32
     */
    public function storeEvent(Request $request)
    {
        // Validasi input
        $request->validate([
            'device_token' => 'required|string',
            'type' => 'required|in:Fall,SOS',
            'acceleration_peak' => 'nullable|numeric'
        ]);

        $device = $this->authenticateDevice($request->device_token);

        if (!$device) {
            return response()->json(['message' => 'Unauthorized device'], 401);
        }

        // Tentukan status awal. 
        // Sesuai rule: Fall akan 'pending' dulu (menunggu immobility duration),
        // sedangkan SOS bisa langsung 'Emergency'.
        $initialStatus = ($request->type === 'SOS') ? 'Emergency' : 'pending';

        // Simpan event ke database
        $event = Event::create([
            'device_id' => $device->id,
            'type' => $request->type,
            'status' => $initialStatus,
            'acceleration_peak' => $request->acceleration_peak,
            'occurred_at' => now(),
        ]);

        return response()->json([
            'message' => 'Event recorded successfully',
            'event_id' => $event->id,
            'status' => $initialStatus
        ], 201);
    }

    /**
     * Endpoint 2: Menerima update status baterai dan online/offline dari ESP32
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'battery_level' => 'required|integer|min:0|max:100',
        ]);

        $device = $this->authenticateDevice($request->device_token);

        if (!$device) {
            return response()->json(['message' => 'Unauthorized device'], 401);
        }

        // Update status perangkat
        $device->update([
            'battery_level' => $request->battery_level,
            'is_online' => true,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device status updated',
            'battery_level' => $device->battery_level
        ], 200);
    }

    /**
     * Endpoint 3: Membatalkan event jika lansia bangun atau menekan tombol batal di alat
     */
    public function cancelEvent(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $device = $this->authenticateDevice($request->device_token);

        if (!$device) {
            return response()->json(['message' => 'Unauthorized device'], 401);
        }

        // Cari event 'pending' terakhir milik device ini
        $pendingEvent = Event::where('device_id', $device->id)
                             ->where('status', 'pending')
                             ->latest('occurred_at')
                             ->first();

        if ($pendingEvent) {
            $pendingEvent->update([
                'status' => 'Cancelled',
                'resolved_at' => now()
            ]);

            return response()->json(['message' => 'Event cancelled successfully'], 200);
        }

        return response()->json(['message' => 'No pending events found'], 404);
    }
}