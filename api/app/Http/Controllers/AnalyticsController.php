<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $device = Device::where('user_id', Auth::id())->first();
        if (!$device) return redirect('/dashboard');

        $deviceId = $device->id;

        // ── 1. KPI Summary ─────────────────────────────────────────────
        $allEvents       = Event::where('device_id', $deviceId)->get();
        $totalEvents     = $allEvents->count();
        $fallsCount      = $allEvents->where('type', 'auto_fall')->count();
        $sosCount        = $allEvents->where('type', 'manual_sos')->count();
        $confirmedCount  = $allEvents->where('status', 'confirmed')->count();
        $falseAlarmCount = $allEvents->where('status', 'false_alarm')->count();
        $resolvedCount   = $allEvents->where('status', 'resolved_by_caregiver')->count();

        $avgImpact = round($allEvents->whereNotNull('acceleration_peak')->avg('acceleration_peak') ?? 0, 2);
        $maxImpact = round($allEvents->whereNotNull('acceleration_peak')->max('acceleration_peak') ?? 0, 2);

        // Avg response time (occurred_at → resolved_at) in minutes
        $avgResponseMinutes = Event::where('device_id', $deviceId)
            ->whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, occurred_at, resolved_at)) as avg_min'))
            ->value('avg_min');
        $avgResponseMinutes = $avgResponseMinutes ? round($avgResponseMinutes) : null;

        // ── 2. Weekly Trend — last 7 days ──────────────────────────────
        $days = collect(range(6, 0))->map(fn($i) => Carbon::now()->subDays($i)->format('Y-m-d'));

        $weeklyRaw = Event::where('device_id', $deviceId)
            ->where('occurred_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(occurred_at) as date'), 'type', DB::raw('count(*) as count'))
            ->groupBy('date', 'type')
            ->get()
            ->groupBy('date');

        $weeklyFalls = [];
        $weeklySos   = [];
        foreach ($days as $day) {
            $dayData     = $weeklyRaw[$day] ?? collect();
            $weeklyFalls[] = $dayData->where('type', 'auto_fall')->sum('count');
            $weeklySos[]   = $dayData->where('type', 'manual_sos')->sum('count');
        }

        // ── 3. Hourly Distribution ─────────────────────────────────────
        $hourlyRaw = Event::where('device_id', $deviceId)
            ->select(DB::raw('HOUR(occurred_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour')->all();

        $hours = array_fill(0, 24, 0);
        foreach ($hourlyRaw as $h => $c) { $hours[$h] = $c; }

        // ── 4. Impact Magnitude (Scatter) ──────────────────────────────
        $impactData = Event::where('device_id', $deviceId)
            ->whereNotNull('acceleration_peak')
            ->orderBy('occurred_at', 'asc')
            ->get(['occurred_at', 'acceleration_peak'])
            ->map(fn($e) => ['x' => $e->occurred_at->format('Y-m-d H:i'), 'y' => $e->acceleration_peak]);

        // ── 5. 30-Day Monthly Trend ────────────────────────────────────
        $monthlyRaw = Event::where('device_id', $deviceId)
            ->where('occurred_at', '>=', Carbon::now()->subDays(30))
            ->select(DB::raw('DATE(occurred_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date')->all();

        $monthlyDays   = [];
        $monthlyCounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $monthlyDays[]   = Carbon::now()->subDays($i)->format('M d');
            $monthlyCounts[] = $monthlyRaw[$d] ?? 0;
        }

        // ── 6. Status Breakdown ────────────────────────────────────────
        $statusBreakdown = [
            'confirmed'             => $confirmedCount,
            'false_alarm'           => $falseAlarmCount,
            'resolved_by_caregiver' => $resolvedCount,
            'pending'               => $allEvents->where('status', 'pending')->count(),
            'cancelled_by_user'     => $allEvents->where('status', 'cancelled_by_user')->count(),
        ];

        // ── 7. Recent Events Table (configurable limit) ───────────────
        $analyticsLimit = $request->query('analytics_limit', '10');
        $recentEventsQuery = Event::where('device_id', $deviceId)
            ->orderBy('occurred_at', 'desc');
        if ($analyticsLimit !== 'all') {
            $recentEventsQuery->limit((int) $analyticsLimit);
        }
        $recentEvents = $recentEventsQuery->get();

        // ── 8. Top Impact Events ───────────────────────────────────────
        $topImpacts = Event::where('device_id', $deviceId)
            ->whereNotNull('acceleration_peak')
            ->orderBy('acceleration_peak', 'desc')
            ->limit(10)
            ->get();

        return view('analytics', compact(
            'device', 'days', 'weeklyFalls', 'weeklySos', 'hours', 'impactData',
            'totalEvents', 'fallsCount', 'sosCount', 'confirmedCount', 'falseAlarmCount',
            'resolvedCount', 'avgImpact', 'maxImpact', 'avgResponseMinutes',
            'monthlyDays', 'monthlyCounts', 'statusBreakdown',
            'recentEvents', 'topImpacts', 'analyticsLimit'
        ));
    }
}