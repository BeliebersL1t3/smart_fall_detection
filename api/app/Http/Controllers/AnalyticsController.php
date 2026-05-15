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
    public function index()
    {
        $device = Device::where('user_id', Auth::id())->first();
        if (!$device) return redirect('/dashboard');

        // 1. Data Tren 7 Hari Terakhir
        $days = collect(range(6, 0))->map(fn($i) => Carbon::now()->subDays($i)->format('Y-m-d'));
        
        $weeklyData = Event::where('device_id', $device->id)
            ->where('occurred_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(occurred_at) as date'), 'type', DB::raw('count(*) as count'))
            ->groupBy('date', 'type')
            ->get();

        // 2. Data Distribusi Jam (00:00 - 23:00)
        $hourlyData = Event::where('device_id', $device->id)
            ->select(DB::raw('HOUR(occurred_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour')->all();
        
        $hours = array_fill(0, 24, 0);
        foreach($hourlyData as $hour => $count) { $hours[$hour] = $count; }

        // 3. Data Magnitude G-Force (Scatter)
        $impactData = Event::where('device_id', $device->id)
            ->whereNotNull('acceleration_peak')
            ->orderBy('occurred_at', 'asc')
            ->get(['occurred_at', 'acceleration_peak'])
            ->map(fn($e) => ['x' => $e->occurred_at->format('Y-m-d H:i'), 'y' => $e->acceleration_peak]);

        return view('analytics', compact('days', 'weeklyData', 'hours', 'impactData'));
    }
}