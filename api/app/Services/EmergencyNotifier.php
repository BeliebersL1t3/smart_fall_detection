<?php

namespace App\Services;

use App\Jobs\SendEmergencyNotification;
use App\Mail\EmergencyAlertMail;
use App\Models\Device;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmergencyNotifier
{
    /**
     * Antrikan email + Telegram agar request dashboard / ESP32 tidak lag.
     */
    public function notify(Event $event, ?Device $device = null, ?string $location = null): void
    {
        SendEmergencyNotification::dispatch(
            $event->id,
            $device?->id ?? $event->device_id,
            $location
        );
    }

    /**
     * Kirim langsung — dipanggil oleh queue worker.
     */
    public function sendNow(Event $event, ?Device $device = null, ?string $location = null): void
    {
        $device ??= $event->device()->with('user')->first();
        $user = $device?->user;
        $location ??= $device?->displayLocation() ?? 'Perangkat ESP32';

        // Kirim Telegram lebih dulu agar instan dan tidak terblokir oleh SMTP timeout
        $this->sendTelegram($event, $user);

        // Jika device belum di-assign ke user, fallback ke user pertama di database
        $targetEmail = $user?->email ?? User::first()?->email;

        if ($targetEmail) {
            try {
                Log::info('Emergency email sending', [
                    'from' => config('mail.from.address'),
                    'to' => $targetEmail,
                    'event_id' => $event->id,
                ]);

                Mail::to($targetEmail)->send(new EmergencyAlertMail($event, $location));

                Log::info('Emergency email sent', ['to' => $targetEmail]);
            } catch (\Throwable $e) {
                Log::error('Emergency email failed: '.$e->getMessage(), [
                    'to' => $targetEmail,
                ]);
            }
        } else {
            Log::warning('Emergency email skipped: user has no email', [
                'event_id' => $event->id,
            ]);
        }
    }

    private function sendTelegram(Event $event, ?User $user): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = $user?->telegram_chat_id ?: config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::debug('Telegram skipped: bot token or chat ID not configured.');

            return;
        }

        $jenis = $event->type === 'manual_sos'
            ? '🆘 *MANUAL SOS DITEKAN*'
            : '🚨 *TERDETEKSI JATUH*';

        $gForce = $event->acceleration_peak
            ? $event->acceleration_peak.' G'
            : 'Tidak ada data';

        $waktu = now()->format('d M Y - H:i:s');

        $message = "⚠️ *CAREGUARD EMERGENCY* ⚠️\n\n{$jenis}\n⏱ *Waktu:* {$waktu}\n💥 *Benturan:* {$gForce}";

        try {
            $response = Http::timeout(5)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]
            );

            if ($response->successful()) {
                Log::info('Telegram sent', ['chat_id' => $chatId]);
            } else {
                Log::warning('Telegram API error: '.$response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Telegram failed: '.$e->getMessage());
        }
    }
}
