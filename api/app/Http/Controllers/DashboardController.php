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
use App\Services\IotIngestService;

class DashboardController extends Controller
{
    public function __construct(
        private IotIngestService $iotIngest
    ) {}
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

    public function resolveEvent(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $event = Event::whereHas('device', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $this->iotIngest->resolveEventByCaregiver($event, 'resolved', $request->notes);

        return redirect()->back()->with('success', 'Kejadian telah diselesaikan dengan catatan.');
    }

    public function markFalseAlarm($id)
    {
        $event = Event::whereHas('device', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $this->iotIngest->resolveEventByCaregiver($event, 'false_alarm');

        return redirect()->back()->with('success', 'Alert ditandai sebagai False Alarm.');
    }

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
        $event = Event::findOrFail($id);
        $dismissedAlerts = session()->get('dismissed_alerts', []);
        
        if (!in_array($id, $dismissedAlerts)) {
            $dismissedAlerts[] = $id;
            session()->put('dismissed_alerts', $dismissedAlerts);
        }

        // MATIKAN BUZZER: Update status device ke normal
        $event->device->update(['last_status' => 'normal']);

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
            $device->update(['last_status' => 'alarm']);
        } elseif ($type === 'sos') {
            $event = Event::create([
                'device_id' => $device->id,
                'type' => 'manual_sos',
                'status' => 'confirmed',
                'acceleration_peak' => null,
                'occurred_at' => now(),
            ]);
            $device->update(['last_status' => 'alarm']);

            if (Auth::user()?->email) {
                Mail::to(Auth::user()->email)->send(new EmergencyAlertMail($event, $location));
            }
            
            $this->sendTelegramAlert($event);
        }

        return redirect()->back();
    }

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

        $limit = $request->query('limit', 10);
        if ($limit !== 'all') {
            $query->take((int)$limit);
        }
        $events = $query->get();

        $pdf = Pdf::loadView('pdf.history', ['events' => $events, 'filter' => $filter]);
        $fileName = 'Fall_History_' . strtoupper($filter) . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }

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

        $limit = $request->query('limit', 10);
        if ($limit !== 'all') {
            $query->take((int)$limit);
        }
        $events = $query->get();
        
        $fileName = 'CareGuard_Data_' . strtoupper($filter) . '_' . now()->format('Ymd_His') . '.xls';
        $headers = [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $html = '<table border="1">';
        $html .= '<thead><tr>';
        $html .= '<th>Date & Time<       width="150px"</th>';
        $html .= '<th>Type<       width="100px"</th>';
        $html .= '<th>Impact (G)<       width="100px"</th>';
        $html .= '<th>Status<       width="100px"</th>';
        $html .= '<th>Notes / Action Taken<       width="100px"</th>';
        $html .= '</tr></thead><style>';
        $html .= '.type-fall { background-color: #f8d7da; color: #721c24; }';
        $html .= '.type-sos { background-color: #d4edda; color: #155724; }';
        $html .= '</style>';
        $html .= '<tbody>';
        foreach ($events as $event) {
            $html .= '<tr>';
            $html .= '<td>' . $event->occurred_at->format('M d, Y - H:i:s') . '</td>';
            $html .= '<td class="' . ($event->type == 'auto_fall' ? 'type-fall' : 'type-sos') . '">' . ($event->type == 'auto_fall' ? 'FALL' : 'SOS') . '</td>';
            $html .= '<td>' . ($event->acceleration_peak ? number_format($event->acceleration_peak, 2) . ' G' : '-') . '</td>';
            $html .= '<td>' . strtoupper(str_replace('_', ' ', $event->status)) . '</td>';
            $html .= '<td style="font-size: 10px; font-style: italic;">' . ($event->notes ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return response($html, 200, $headers);
    }

    private function sendTelegramAlert($event)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        if (!$botToken || !$chatId) return;

        $jenisDarurat = $event->type == 'manual_sos' ? '🆘 *MANUAL SOS DITEKAN*' : '🚨 *TERDETEKSI JATUH (CONFIRMED)*';
        $gForce = $event->acceleration_peak ? $event->acceleration_peak . ' G' : 'Tidak Ada Data';
        $waktu = now()->format('d M Y - H:i:s');

        $message = "⚠️ *CAREGUARD EMERGENCY ALERT* ⚠️\n\n{$jenisDarurat}\n⏱ *Waktu:* {$waktu}\n💥 *Benturan:* {$gForce}";

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            \Log::error('Telegram Exception: ' . $e->getMessage());
        }
    }
}