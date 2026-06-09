<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Event;
use App\Support\BatteryHelper;

class IotIngestService
{
    // 1. FIXED: Cleaned up the constructor and removed duplicates/syntax errors
    public function __construct(
        private WebSocketNotifier $notifier,
        private EmergencyNotifier $emergencyNotifier
    ) {}

    public function ingestTelemetry(Device $device, array $data): array
    {
        // Capture the current server-side status BEFORE any update.
        // This is the source-of-truth: if the dashboard dismissed the alarm
        // (set last_status = 'normal'), we must NOT let the ESP32's periodic
        // heartbeat (which still has fallAlarm=true locally) override it back.
        $currentDbStatus = $device->last_status;

        // Only accept an 'alarm' escalation from the ESP32 heartbeat if the
        // server isn't already in a 'normal' (dismissed) state.
        // The dedicated /api/fall-detected endpoint is the correct way to
        // escalate to alarm; this telemetry endpoint must not undo a dismiss.
        $incomingStatus = $data['status'] ?? 'normal';
        if ($incomingStatus === 'alarm' && $currentDbStatus === 'normal') {
            // Dashboard already dismissed — keep 'normal', don't re-escalate.
            $incomingStatus = 'normal';
        }

        $updates = [
            'is_online' => true,
            'last_magnitude' => $data['magnitude'] ?? null,
            'last_ax' => $data['ax'] ?? null,
            'last_ay' => $data['ay'] ?? null,
            'last_az' => $data['az'] ?? null,
            'last_status' => $incomingStatus,
            'last_seen_at' => now(),
        ];

        $battery = BatteryHelper::fromPayload($data);
        if ($battery !== null) {
            $updates['battery_level'] = $battery;
        }

        $device->update($updates);
        $device->refresh();

        $charging = (bool) ($data['charging'] ?? false);

        // Payload yang akan dikirim balik ke ESP32 dan di-broadcast ke Dashboard
        $payload = [
            'device_id' => $device->id,
            'magnitude' => $device->last_magnitude,
            'ax' => $device->last_ax,
            'ay' => $device->last_ay,
            'az' => $device->last_az,
            'status' => $device->last_status,
            'battery' => $device->battery_level,
            'battery_level' => $device->battery_level,
            'battery_color' => BatteryHelper::levelColor($device->battery_level),
            'battery_status' => BatteryHelper::statusLabel($device->battery_level, $charging),
            'charging' => $charging,
            'is_online' => true,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            
            // --- LOGIKA DISMISS ---
            // Jika status di DB sudah 'normal', kirim perintah false ke ESP32 agar buzzer mati
            'command_buzzer' => ($device->last_status === 'alarm'),
        ];

        $this->notifier->broadcast($device->user_id, 'telemetry', $payload);

        return $payload;
    }

    public function ingestFall(Device $device, ?float $magnitude): Event
    {
        $event = Event::create([
            'device_id' => $device->id,
            'type' => 'auto_fall',
            'status' => 'pending',
            'acceleration_peak' => $magnitude,
            'occurred_at' => now(),
        ]);

        $device->update([
            'is_online' => true,
            'last_status' => 'alarm',
            'last_magnitude' => $magnitude,
            'last_seen_at' => now(),
        ]);

        $this->notifier->broadcast($device->user_id, 'fall_detected', [
            'event_id' => $event->id,
            'device_id' => $device->id,
            'type' => 'auto_fall',
            'status' => 'pending',
            'magnitude' => $magnitude,
            'occurred_at' => $event->occurred_at->toIso8601String(),
        ]);

        return $event;
    }

    public function ingestSos(Device $device, bool $active, ?string $message = null): ?Event
    {
        // Update status device di database agar dashboard tahu kondisi global device
        $device->update([
            'is_online' => true,
            'last_status' => $active ? 'alarm' : 'normal',
            'last_seen_at' => now(),
        ]);

        if (! $active) {
            // PERBAIKAN: Mencari event yang statusnya 'confirmed' (SOS) atau 'pending' (Jatuh)
            $pending = Event::where('device_id', $device->id)
                ->whereIn('status', ['confirmed', 'pending']) 
                ->latest('occurred_at')
                ->first();

            if ($pending) {
                $pending->update([
                    'status' => 'cancelled_by_user',
                    'resolved_at' => now(),
                ]);
            }

            // Beritahu WebSocket agar UI dashboard langsung berubah
            $this->notifier->broadcast($device->user_id, 'sos_cancelled', [
                'device_id' => $device->id,
                'message' => $message,
                'status' => 'normal',
            ]);

            return null;
        }

        // Jika SOS aktif, buat event baru dengan status 'confirmed'
        $event = Event::create([
            'device_id' => $device->id,
            'type' => 'manual_sos',
            'status' => 'confirmed',
            'acceleration_peak' => null,
            'occurred_at' => now(),
        ]);

        // 2. FIXED: Removed duplicate loadMissing call
        $device->loadMissing('user');

        $this->notifier->broadcast($device->user_id, 'sos_active', [
            'event_id' => $event->id,
            'device_id' => $device->id,
            'type' => 'manual_sos',
            'status' => 'confirmed',
            'message' => $message,
            'occurred_at' => $event->occurred_at->toIso8601String(),
        ]);

        // 3. FIXED: Removed duplicate notify call
        $this->emergencyNotifier->notify($event, $device);

        return $event;
    }

    /**
     * Perawat (dashboard / Flutter) menyelesaikan alarm: false alarm atau resolved + catatan.
     * Mematikan buzzer ESP32 lewat last_status = normal (dibaca saat polling sensor-data).
     */
    public function resolveEventByCaregiver(Event $event, string $status, ?string $notes = null): Event
    {
        $dbStatus = $status === 'resolved' ? 'resolved_by_caregiver' : 'false_alarm';

        $event->update([
            'status' => $dbStatus,
            'notes' => $notes,
            'resolved_at' => now(),
        ]);

        $device = $event->device;
        $device->update(['last_status' => 'normal']);
        $device->refresh();

        $this->notifier->broadcast($device->user_id, 'alarm_dismissed', [
            'event_id' => $event->id,
            'device_id' => $device->id,
            'status' => $dbStatus,
            'command_buzzer' => false,
        ]);

        $this->notifier->broadcast($device->user_id, 'telemetry', [
            'device_id' => $device->id,
            'status' => 'normal',
            'command_buzzer' => false,
            'is_online' => $device->is_online,
            'battery' => $device->battery_level,
            'battery_level' => $device->battery_level,
        ]);

        return $event->fresh();
    }

    private function sendTelegramAlert(Event $event): void
    {
        $dbStatus = $status === 'resolved' ? 'resolved_by_caregiver' : 'false_alarm';

        // Telegram notification logic
        $botToken = env('TELEGRAM_BOT_TOKEN');
        
        // 🌟 AMBIL CHAT ID DARI DATABASE (Bukan dari .env lagi)
        // Kita cari User siapa yang memiliki perangkat pembuat event ini
        $device = $event->device()->with('user')->first();
        $chatId = $device?->user?->telegram_chat_id;

        // Batalkan jika Token Bot tidak ada ATAU jika User belum mengatur Telegram ID di Settings
        if ($botToken && $chatId) {
            // You may want to implement your notification logic here
            // Example: Send Telegram notification about event resolution
            // ...
        }

        $event->update([
            'status' => $dbStatus,
            'notes' => $notes,
            'resolved_at' => now(),
        ]);

        $device = $event->device;
        $device->update(['last_status' => 'normal']);
        $device->refresh();

        $this->notifier->broadcast($device->user_id, 'alarm_dismissed', [
            'event_id' => $event->id,
            'device_id' => $device->id,
            'status' => $dbStatus,
            'command_buzzer' => false,
        ]);

        $this->notifier->broadcast($device->user_id, 'telemetry', [
            'device_id' => $device->id,
            'status' => 'normal',
            'command_buzzer' => false,
            'is_online' => $device->is_online,
            'battery' => $device->battery_level,
            'battery_level' => $device->battery_level,
        ]);
    }
}