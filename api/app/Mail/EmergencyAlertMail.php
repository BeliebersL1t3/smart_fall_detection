<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Wajib ditambahkan
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Tambahkan "implements ShouldQueue" di sini untuk menghilangkan lag
class EmergencyAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $event;
    public $location;

    public function __construct(Event $event, $location)
    {
        $this->event = $event;
        $this->location = $location;
    }

    public function build()
    {
        // Pisahkan logika antara SOS dan Deteksi Jatuh
        if ($this->event->type === 'manual_sos') {
            return $this->subject('🚨 TOMBOL SOS DITEKAN: Bantuan Segera Dibutuhkan!')
                        ->view('emails.sos_alert'); // Mengarah ke template khusus SOS
        }

        return $this->subject('⚠️ DETEKSI JATUH OTOMATIS: Darurat Terdeteksi!')
                    ->view('emails.emergency_alert'); // Template asli
    }
}