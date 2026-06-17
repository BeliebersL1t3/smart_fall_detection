<?php

namespace App\Services;

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
     * Titik masuk utama:
     *  1. Telegram dikirim SEKARANG (cepat ~1 detik, tidak memblokir).
     *  2. Email dijadwalkan via app()->terminating() sehingga dikirim
     *     SETELAH response HTML sudah sampai ke browser — dashboard tidak lag sama sekali.
     */
    public function notify(Event $event, ?Device $device = null, ?string $location = null): void
    {
        // Muat relasi sekali di sini agar tersedia untuk Telegram & email
        $device  ??= $event->device()->with('user')->first();
        $user      = $device?->user;
        $location ??= $device?->displayLocation() ?? 'Perangkat ESP32';

        // ── 1. Telegram: langsung sekarang, cepat ────────────────────────
        $this->sendTelegram($event, $user);

        // ── 2. Email: setelah response sudah dikembalikan ke browser ─────
        $targetEmail = $user?->email ?: (env('ALERT_EMAIL') ?: User::first()?->email);

        if (! $targetEmail) {
            Log::warning('Emergency email skipped: no recipient found', ['event_id' => $event->id]);
            return;
        }

        // Capture by value agar closure tidak menahan model Eloquent terlalu lama
        $eventId     = $event->id;
        $eventType   = $event->type;
        $eventPeak   = $event->acceleration_peak;
        $emailAddr   = $targetEmail;
        $emailLoc    = $location;

        app()->terminating(function () use ($eventId, $eventType, $eventPeak, $emailAddr, $emailLoc) {
            try {
                // Reload event dari DB agar fresh (bukan stale instance)
                $ev = Event::find($eventId);
                if (! $ev) {
                    return;
                }
                Log::info('Emergency email sending (after-response)', ['to' => $emailAddr, 'event_id' => $eventId]);
                Mail::to($emailAddr)->send(new EmergencyAlertMail($ev, $emailLoc));
                Log::info('Emergency email sent', ['to' => $emailAddr]);
            } catch (\Throwable $e) {
                Log::error('Emergency email failed: '.$e->getMessage(), ['to' => $emailAddr]);
            }
        });
    }

    /**
     * Dipanggil oleh queue worker (backward compat) — kirim langsung.
     * Telegram sudah dikirim di notify(), jadi di sini cukup email.
     */
    public function sendNow(Event $event, ?Device $device = null, ?string $location = null): void
    {
        $device  ??= $event->device()->with('user')->first();
        $user      = $device?->user;
        $location ??= $device?->displayLocation() ?? 'Perangkat ESP32';

        $targetEmail = $user?->email ?: (env('ALERT_EMAIL') ?: User::first()?->email);

        if ($targetEmail) {
            try {
                Log::info('Emergency email sending (sendNow)', [
                    'from'     => config('mail.from.address'),
                    'to'       => $targetEmail,
                    'event_id' => $event->id,
                ]);
                Mail::to($targetEmail)->send(new EmergencyAlertMail($event, $location));
                Log::info('Emergency email sent', ['to' => $targetEmail]);
            } catch (\Throwable $e) {
                Log::error('Emergency email failed: '.$e->getMessage(), ['to' => $targetEmail]);
            }
        } else {
            Log::warning('Emergency email skipped: no recipient', ['event_id' => $event->id]);
        }
    }

    private function sendTelegram(Event $event, ?User $user): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId   = $user?->telegram_chat_id ?: config('services.telegram.chat_id');

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

        $waktu   = now()->format('d M Y - H:i:s');
        $message = "⚠️ *CAREGUARD EMERGENCY* ⚠️\n\n{$jenis}\n⏱ *Waktu:* {$waktu}\n💥 *Benturan:* {$gForce}";

        try {
            $response = Http::timeout(5)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id'    => $chatId,
                    'text'       => $message,
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
