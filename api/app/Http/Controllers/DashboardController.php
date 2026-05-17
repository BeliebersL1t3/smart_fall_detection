<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmergencyAlertMail;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /**
     * Menampilkan Dashboard utama berdasarkan user yang login
     */
    public function index(Request $request)
    {
        $device = Device::with('user')->where('user_id', Auth::id())->first();
        
        if (!$device) {
            Auth::logout();
            return redirect('/login')->withErrors(['email' => 'Akun Anda belum memiliki perangkat terdaftar.']);
        }

        $device->syncOnlineStatus();

        $allEvents = Event::where('device_id', $device->id)->orderBy('occurred_at', 'desc')->get();
        
        $totalEvents = $allEvents->count();
        $emergencyCount = $allEvents->where('status', 'confirmed')->count();
        $latestEvent = $allEvents->first();

        $fallsCount = $allEvents->where('type', 'auto_fall')->count();
        $sosCount = $allEvents->where('type', 'manual_sos')->count();

        $confirmedCount = $allEvents->where('status', 'confirmed')->count();
        $falseAlarmCount = $allEvents->where('status', 'false_alarm')->count();
        $resolvedCount = $allEvents->where('status', 'resolved_by_caregiver')->count();
        $cancelledCount = $allEvents->where('status', 'cancelled_by_user')->count();

        $filter = $request->query('filter', 'all');
        $query = Event::where('device_id', $device->id)->orderBy('occurred_at', 'desc');

        match($filter) {
            'pending' => $query->where('status', 'pending'),
            'falls' => $query->where('type', 'auto_fall'),
            'sos' => $query->where('type', 'manual_sos'),
            'emergencies' => $query->where('status', 'confirmed'),
            'false_alarms' => $query->where('status', 'false_alarm'),
            'cancelled' => $query->where('status', 'cancelled_by_user'),
            'resolved' => $query->where('status', 'resolved_by_caregiver'),
            default => $query
        };

        $recentEvents = $query->get();

        return view('dashboard', [
            'device' => $device,
            'recentEvents' => $recentEvents,
            'totalEvents' => $totalEvents,
            'emergencyCount' => $emergencyCount,
            'latestEvent' => $latestEvent,
            'fallsCount' => $fallsCount,
            'sosCount' => $sosCount,
            'confirmedCount' => $confirmedCount,
            'falseAlarmCount' => $falseAlarmCount,
            'resolvedCount' => $resolvedCount,
            'cancelledCount' => $cancelledCount,
            'deviceLocation' => $device->displayLocation(),
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Menyelesaikan kejadian DENGAN CATATAN PENANGANAN
     */
    public function resolveEvent(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $event = Event::findOrFail($id);
        $event->update([
            'status' => 'resolved_by_caregiver',
            'notes' => $request->notes,
            'resolved_at' => now()
        ]);

        return redirect()->back()->with('success', 'Kejadian telah diselesaikan dengan catatan.');
    }

    public function markFalseAlarm($id)
    {
        $event = Event::findOrFail($id);
        $event->update([
            'status' => 'false_alarm',
            'resolved_at' => now()
        ]);

        return redirect()->back()->with('success', 'Alert ditandai sebagai False Alarm.');
    }

    /**
     * Auto Confirm: Dipanggil oleh Timer Alpine.js di Frontend
     */
    public function autoConfirm($id)
    {
        $event = Event::findOrFail($id);
        
        if ($event->status === 'pending') {
            $event->update([
                'status' => 'confirmed',
                'resolved_at' => now(),
            ]);

            $device = Device::with('user')->find($event->device_id);
            $user = $device?->user;
            $location = $device?->displayLocation() ?? 'Perangkat ESP32';

            if ($user?->email) {
                Mail::to($user->email)->send(new EmergencyAlertMail($event, $location));
            }
            
            $this->sendTelegramAlert($event);
        }

        return response()->json(['success' => true]);
    }

    public function dismissAlert($id)
    {
        $dismissedAlerts = session()->get('dismissed_alerts', []);
        if (!in_array($id, $dismissedAlerts)) {
            $dismissedAlerts[] = $id;
            session()->put('dismissed_alerts', $dismissedAlerts);
        }
        return response()->json(['success' => true]);
    }

    public function simulateEvent($type)
    {
        $device = Device::where('user_id', Auth::id())->first();
        $location = $device->displayLocation();

        if ($type === 'fall') {
            Event::create([
                'device_id' => $device->id,
                'type' => 'auto_fall',
                'status' => 'pending', 
                'acceleration_peak' => mt_rand(210, 350) / 100,
                'occurred_at' => now(),
            ]);
        } elseif ($type === 'sos') {
            $event = Event::create([
                'device_id' => $device->id,
                'type' => 'manual_sos',
                'status' => 'confirmed',
                'acceleration_peak' => null,
                'occurred_at' => now(),
            ]);

            if (Auth::user()?->email) {
                Mail::to(Auth::user()->email)->send(new EmergencyAlertMail($event, $location));
            }
            
            $this->sendTelegramAlert($event);
        }

        return redirect()->back();
    }

    /**
     * Ekspor Laporan PDF
     */
    public function exportPdf(Request $request)
    {
        $device = Device::where('user_id', Auth::id())->first();
        if (!$device) return redirect()->back(); 

        $filter = $request->query('filter', 'all');
        $query = Event::where('device_id', $device->id)->orderBy('occurred_at', 'desc');

        match($filter) {
            'pending' => $query->where('status', 'pending'),
            'falls' => $query->where('type', 'auto_fall'),
            'sos' => $query->where('type', 'manual_sos'),
            'emergencies' => $query->where('status', 'confirmed'),
            'false_alarms' => $query->where('status', 'false_alarm'),
            'cancelled' => $query->where('status', 'cancelled_by_user'),
            'resolved' => $query->where('status', 'resolved_by_caregiver'),
            default => $query
        };

        $events = $query->take(50)->get();

        $pdf = Pdf::loadView('pdf.history', ['events' => $events, 'filter' => $filter]);
        return $pdf->download('Fall_History_' . strtoupper($filter) . '.pdf');
    }

    /**
     * 🌟 EKSPOR EXCEL (.XLS) BERWARNA
     */
    public function exportExcel(Request $request)
    {
        $device = Device::where('user_id', Auth::id())->first();
        if (!$device) return redirect()->back();

        $filter = $request->query('filter', 'all');
        $query = Event::where('device_id', $device->id)->orderBy('occurred_at', 'desc');

        match($filter) {
            'emergencies' => $query->where('status', 'confirmed'),
            'resolved' => $query->where('status', 'resolved_by_caregiver'),
            'falls' => $query->where('type', 'auto_fall'),
            'sos' => $query->where('type', 'manual_sos'),
            default => $query
        };

        $events = $query->get();
        
        $fileName = 'CareGuard_Data_' . now()->format('Ymd_His') . '.xls';
        
        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Membangun Tabel HTML untuk dibaca Excel
        $html = '<table border="1" style="font-family: Arial, sans-serif; border-collapse: collapse;">';
        $html .= '<tr style="background-color: #1f2937; color: #ffffff; font-weight: bold; text-align: center;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Time</th>
                    <th style="padding: 10px;">Trigger Type</th>
                    <th style="padding: 10px;">Impact (G)</th>
                    <th style="padding: 10px;">Status</th>
                    <th style="padding: 10px;">Caregiver Notes</th>
                  </tr>';

        foreach ($events as $event) {
            $typeColor = $event->type == 'auto_fall' ? 'color: #dc2626; font-weight: bold; background-color: #fef2f2;' : 'color: #2563eb; font-weight: bold; background-color: #eff6ff;';
            $typeLabel = $event->type == 'auto_fall' ? 'SENSOR FALL' : 'MANUAL SOS';

            $statusColor = match($event->status) {
                'confirmed' => 'color: #dc2626; font-weight: bold;',
                'resolved_by_caregiver' => 'color: #16a34a; font-weight: bold;',
                'false_alarm' => 'color: #d97706; font-weight: bold;',
                default => 'color: #6b7280;'
            };
            $statusLabel = strtoupper(str_replace('_', ' ', $event->status));

            $html .= '<tr style="text-align: center;">';
            $html .= '<td style="padding: 5px;">' . $event->id . '</td>';
            $html .= '<td style="padding: 5px;">' . $event->occurred_at->format('M d, Y') . '</td>';
            $html .= '<td style="padding: 5px;">' . $event->occurred_at->format('H:i:s') . '</td>';
            $html .= '<td style="padding: 5px; ' . $typeColor . '">' . $typeLabel . '</td>';
            $html .= '<td style="padding: 5px;">' . ($event->acceleration_peak ? $event->acceleration_peak . ' G' : '-') . '</td>';
            $html .= '<td style="padding: 5px; ' . $statusColor . '">' . $statusLabel . '</td>';
            $html .= '<td style="padding: 5px; text-align: left; font-style: italic;">' . ($event->notes ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return response($html, 200, $headers);
    }

    /**
     * Notifikasi Telegram
     */
    private function sendTelegramAlert($event)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) return;

        $jenisDarurat = $event->type == 'manual_sos' ? '🆘 *MANUAL SOS DITEKAN*' : '🚨 *TERDETEKSI JATUH (CONFIRMED)*';
        $gForce = $event->acceleration_peak ? $event->acceleration_peak . ' G' : 'Tidak Ada Data';
        $waktu = now()->format('d M Y - H:i:s');

        $message = "⚠️ *CAREGUARD EMERGENCY ALERT* ⚠️\n\n";
        $message .= "{$jenisDarurat}\n\n";
        $message .= "⏱ *Waktu:* {$waktu}\n";
        $message .= "💥 *Benturan:* {$gForce}\n";
        $message .= "📍 *Status:* Membutuhkan Perhatian Segera!\n\n";
        $message .= "Silakan cek Dashboard untuk detail.";

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if (!$response->successful()) {
                \Log::error('Telegram Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error('Telegram Exception: ' . $e->getMessage());
        }
    }
}