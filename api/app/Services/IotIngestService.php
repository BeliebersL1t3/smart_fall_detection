<?php

namespace App\Services;

use App\Mail\EmergencyAlertMail;
use App\Models\Device;
use App\Models\Event;
use App\Support\BatteryHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class IotIngestService
{
    public function __construct(
        private WebSocketNotifier $notifier
    ) {}

    public function ingestTelemetry(Device $device, array $data): array
{
    $updates = [
        'is_online' => true,
        'last_magnitude' => $data['magnitude'] ?? null,
        'last_ax' => $data['ax'] ?? null,
        'last_ay' => $data['ay'] ?? null,
        'last_az' => $data['az'] ?? null,
        'last_status' => $data['status'] ?? 'normal',
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

        if ($device->user?->email) {
            Mail::to($device->user->email)->send(
                new EmergencyAlertMail($event, $device->label)
            );
        }

        $this->sendTelegramAlert($event);
        
        $this->notifier->broadcast($device->user_id, 'sos_active', [
            'event_id' => $event->id,
            'device_id' => $device->id,
            'type' => 'manual_sos',
            'status' => 'confirmed',
            'message' => $message,
            'occurred_at' => $event->occurred_at->toIso8601String(),
        ]);

        return $event;
    }

    private function sendTelegramAlert(Event $event): void
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (! $botToken || ! $chatId) {
            return;
        }

        $jenis = $event->type === 'manual_sos' ? '🆘 MANUAL SOS' : '🚨 JATUH';
        $gForce = $event->acceleration_peak ? $event->acceleration_peak.' G' : '-';
        $waktu = now()->format('d M Y - H:i:s');

        $message = "⚠️ *CAREGUARD EMERGENCY* ⚠️\n\n{$jenis}\n⏱ {$waktu}\n💥 {$gForce}";

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable) {
            // ignore
        }
    }
}