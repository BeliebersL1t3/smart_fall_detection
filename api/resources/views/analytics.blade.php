@extends('layouts.app')
@section('title', 'Advanced Analytics')

@section('content')
<div class="space-y-8 mt-4">

    {{-- ══════════════════════════════════════════════════════
         HEADER BANNER
    ══════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-700 rounded-2xl p-8 shadow-2xl shadow-indigo-500/30">
        {{-- Decorative circles --}}
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-indigo-200 text-xs font-bold uppercase tracking-widest mb-1">CareGuard · Data Intelligence</p>
                <h1 class="text-3xl font-black text-white">Advanced Analytics</h1>
                <p class="text-indigo-200 text-sm mt-2 font-medium">Analisis pola, tren, dan distribusi risiko berdasarkan data historis perangkat.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center border border-white/20">
                    <p class="text-white/70 text-xs font-semibold">Total Events</p>
                    <p class="text-3xl font-black text-white">{{ $totalEvents }}</p>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-xl px-5 py-3 text-center border border-white/20">
                    <p class="text-white/70 text-xs font-semibold">Device</p>
                    <p class="text-sm font-black text-white mt-1">{{ $device->displayLocation() }}</p>
                    <span class="text-[10px] text-indigo-200">{{ $device->is_online ? '🟢 Online' : '⚪ Offline' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         KPI STAT CARDS
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
        $kpis = [
            ['label' => 'Falls Detected',    'value' => $fallsCount,      'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',                                                                                                           'from' => 'from-red-500',    'to' => 'to-rose-600',    'shadow' => 'shadow-red-500/40'],
            ['label' => 'Manual SOS',        'value' => $sosCount,        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'from' => 'from-blue-500',   'to' => 'to-indigo-600',  'shadow' => 'shadow-blue-500/40'],
            ['label' => 'Confirmed Emerg.',  'value' => $confirmedCount,  'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','from' => 'from-orange-500', 'to' => 'to-amber-600',   'shadow' => 'shadow-orange-500/40'],
            ['label' => 'False Alarms',      'value' => $falseAlarmCount, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                       'from' => 'from-yellow-400', 'to' => 'to-orange-500',  'shadow' => 'shadow-yellow-400/40'],
            ['label' => 'Max Impact (G)',     'value' => $maxImpact ?: '—','icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3','from' => 'from-purple-500', 'to' => 'to-violet-600',  'shadow' => 'shadow-purple-500/40'],
            ['label' => 'Avg Response',      'value' => $avgResponseMinutes ? $avgResponseMinutes.' min' : '—', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                  'from' => 'from-teal-500',   'to' => 'to-emerald-600', 'shadow' => 'shadow-teal-500/40'],
        ];
        @endphp

        @foreach($kpis as $kpi)
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-200 group">
            <div class="flex items-center justify-between mb-3">
                <div class="p-2 rounded-xl bg-gradient-to-br {{ $kpi['from'] }} {{ $kpi['to'] }} shadow-lg {{ $kpi['shadow'] }} group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpi['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black ui-title">{{ $kpi['value'] }}</p>
            <p class="text-xs ui-subtitle font-semibold mt-1">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════
         ROW 1: Weekly Trend + 30-Day Activity
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Weekly Trend (3 cols) --}}
        <div class="lg:col-span-3 ui-card rounded-2xl relative p-4 mt-8">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-purple-700 to-indigo-500 rounded-2xl p-5 shadow-xl shadow-indigo-500/40 h-56 flex items-center justify-center">
                <canvas id="weeklyTrendChart"></canvas>
            </div>
            <div class="pt-56 px-2 pb-2">
                <div class="flex items-start justify-between mt-2">
                    <div>
                        <h4 class="text-base font-bold ui-title">Weekly Activity Trends</h4>
                        <p class="text-xs ui-subtitle mt-1">Perbandingan Fall & SOS dalam 7 hari terakhir.</p>
                    </div>
                    <div class="flex gap-3 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-white inline-block"></span><span class="ui-subtitle font-medium">Falls</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-300 inline-block"></span><span class="ui-subtitle font-medium">SOS</span></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 30-Day Activity (2 cols) --}}
        <div class="lg:col-span-2 ui-card rounded-2xl relative p-4 mt-8">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-sky-600 to-cyan-400 rounded-2xl p-5 shadow-xl shadow-sky-500/40 h-56 flex items-center justify-center">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
            <div class="pt-56 px-2 pb-2">
                <div class="mt-2">
                    <h4 class="text-base font-bold ui-title">30-Day Activity</h4>
                    <p class="text-xs ui-subtitle mt-1">Total event per hari selama sebulan terakhir.</p>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════
         ROW 2: Hourly Risk + Status Breakdown
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Hourly Risk Bar (3 cols) --}}
        <div class="lg:col-span-3 ui-card rounded-2xl relative p-4 mt-8">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-emerald-600 to-teal-400 rounded-2xl p-5 shadow-xl shadow-teal-500/40 h-56 flex items-center justify-center">
                <canvas id="hourlyRiskChart"></canvas>
            </div>
            <div class="pt-56 px-2 pb-2 mt-2">
                <h4 class="text-base font-bold ui-title">Risk Level by Hour of Day</h4>
                <p class="text-xs ui-subtitle mt-1">Jam-jam paling berisiko berdasarkan seluruh riwayat event.</p>
            </div>
        </div>

        {{-- Status Breakdown Donut (2 cols) --}}
        <div class="lg:col-span-2 ui-card rounded-2xl relative p-4 mt-8">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-rose-500 to-orange-400 rounded-2xl p-5 shadow-xl shadow-rose-500/40 h-56 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="pt-56 px-2 pb-2 mt-2">
                <h4 class="text-base font-bold ui-title">Status Breakdown</h4>
                <p class="text-xs ui-subtitle mt-1">Distribusi status penyelesaian seluruh event.</p>
                {{-- Mini legend --}}
                <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                    @php
                    $statusLabels = [
                        'confirmed'             => ['label' => 'Confirmed',  'color' => 'bg-red-500'],
                        'false_alarm'           => ['label' => 'False Alarm','color' => 'bg-yellow-400'],
                        'resolved_by_caregiver' => ['label' => 'Resolved',   'color' => 'bg-green-500'],
                        'pending'               => ['label' => 'Pending',    'color' => 'bg-orange-400'],
                        'cancelled_by_user'     => ['label' => 'Cancelled',  'color' => 'bg-gray-400'],
                    ];
                    @endphp
                    @foreach($statusLabels as $key => $meta)
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full {{ $meta['color'] }} flex-shrink-0"></span>
                        <span class="ui-subtitle">{{ $meta['label'] }}: <strong class="ui-title">{{ $statusBreakdown[$key] ?? 0 }}</strong></span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════
         IMPACT SCATTER (full width)
    ══════════════════════════════════════════════════════ --}}
    <div class="ui-card rounded-2xl relative p-4 mt-10">
        <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-violet-700 via-fuchsia-600 to-pink-500 rounded-2xl p-5 shadow-xl shadow-fuchsia-500/40 h-72 flex items-center justify-center">
            <canvas id="impactScatterChart"></canvas>
        </div>
        <div class="pt-[19rem] px-2 pb-2 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h4 class="text-base font-bold ui-title">Impact Magnitude Over Time (G-Force)</h4>
                <p class="text-xs ui-subtitle mt-1">Intensitas setiap fall event — titik lebih tinggi = benturan lebih keras.</p>
            </div>
            <div class="flex gap-4 text-xs text-right">
                <div class="bg-gray-50 dark:bg-slate-900 rounded-xl px-4 py-2 border border-gray-100 dark:border-slate-700">
                    <p class="ui-muted">Avg Impact</p>
                    <p class="text-lg font-black ui-title">{{ $avgImpact ?: '—' }} <span class="text-xs font-normal ui-subtitle">G</span></p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-900 rounded-xl px-4 py-2 border border-gray-100 dark:border-slate-700">
                    <p class="ui-muted">Max Impact</p>
                    <p class="text-lg font-black text-red-500">{{ $maxImpact ?: '—' }} <span class="text-xs font-normal ui-subtitle">G</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TABLE 1: RECENT EVENTS
    ══════════════════════════════════════════════════════ --}}
    <div id="recent-events-table" class="ui-card rounded-2xl overflow-hidden mt-6 scroll-mt-24">
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h4 class="text-white font-bold text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Recent Events Log
                </h4>
                <p class="text-slate-400 text-xs mt-0.5">
                    Showing <span class="text-white font-semibold">{{ $recentEvents->count() }}</span> event terbaru dari perangkat
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Limit selector --}}
                <form id="analyticsLimitForm" action="{{ route('analytics') }}#recent-events-table" method="GET" class="flex gap-2">
                    {{-- preserve other query params --}}
                    <select name="analytics_limit"
                            onchange="document.getElementById('analyticsLimitForm').submit()"
                            class="bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs rounded-lg py-2 px-3 outline-none cursor-pointer transition">
                        @foreach(['5' => '5 Rows', '10' => '10 Rows', '20' => '20 Rows', '50' => '50 Rows', '100' => '100 Rows', 'all' => 'All Rows'] as $val => $label)
                            <option value="{{ $val }}" class="text-gray-800"
                                {{ $analyticsLimit == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                {{-- Export buttons — reuse dashboard export routes with analytics_limit & filter=all --}}
                <a href="{{ route('export.excel', ['filter' => 'all', 'limit' => $analyticsLimit === 'all' ? 'all' : $analyticsLimit]) }}"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Excel
                </a>
                <a href="{{ route('export.pdf', ['filter' => 'all', 'limit' => $analyticsLimit === 'all' ? 'all' : $analyticsLimit]) }}"
                   class="bg-white/15 hover:bg-white/25 text-white text-xs font-bold py-2 px-3 rounded-lg border border-white/20 shadow transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-slate-700">
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">#</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Impact (G)</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Response Time</th>
                        <th class="px-5 py-3 text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                    @forelse($recentEvents as $i => $event)
                    @php
                        $typeClass  = $event->type === 'auto_fall'
                            ? 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400'
                            : 'bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400';
                        $typeLabel  = $event->type === 'auto_fall' ? 'FALL' : 'SOS';
                        $statusMap  = [
                            'confirmed'             => ['label' => 'CONFIRMED',   'class' => 'text-red-600 dark:text-red-400 font-bold'],
                            'false_alarm'           => ['label' => 'FALSE ALARM', 'class' => 'text-yellow-600 dark:text-yellow-400'],
                            'resolved_by_caregiver' => ['label' => 'RESOLVED',    'class' => 'text-green-600 dark:text-green-400'],
                            'pending'               => ['label' => 'PENDING',     'class' => 'text-orange-500'],
                            'cancelled_by_user'     => ['label' => 'CANCELLED',   'class' => 'text-gray-400'],
                        ];
                        $s = $statusMap[$event->status] ?? ['label' => strtoupper($event->status), 'class' => 'text-gray-500'];
                        $responseTime = $event->resolved_at
                            ? $event->occurred_at->diffInMinutes($event->resolved_at) . ' min'
                            : '—';
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-5 py-3.5 text-gray-400 dark:text-slate-500 font-mono text-xs">{{ $i + 1 }}</td>
                        <td class="px-5 py-3.5">
                            <div class="font-semibold ui-title text-xs">{{ $event->occurred_at->format('M j, Y') }}</div>
                            <div class="text-gray-400 dark:text-slate-500 text-[11px]">{{ $event->occurred_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $typeClass }}">{{ $typeLabel }}</span>
                        </td>
                        <td class="px-5 py-3.5 font-bold ui-title text-sm">
                            {{ $event->acceleration_peak ? number_format($event->acceleration_peak, 2).' G' : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide {{ $s['class'] }}">
                            {{ $s['label'] }}
                        </td>
                        <td class="px-5 py-3.5 text-xs ui-subtitle font-mono">{{ $responseTime }}</td>
                        <td class="px-5 py-3.5 text-xs ui-subtitle max-w-[200px] truncate">
                            {{ $event->notes ?: '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center ui-muted text-sm">Belum ada event tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TABLE 2: TOP IMPACT EVENTS
    ══════════════════════════════════════════════════════ --}}
    @if($topImpacts->count())
    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-red-700 to-rose-600 px-6 py-5 flex items-center justify-between">
            <div>
                <h4 class="text-white font-bold text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Top 10 Highest Impact Falls
                </h4>
                <p class="text-red-200 text-xs mt-0.5">Fall events diurutkan berdasarkan G-Force tertinggi</p>
            </div>
            <div class="bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Max: {{ $maxImpact }} G
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/30">
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">Rank</th>
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">G-Force</th>
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">Severity</th>
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-xs font-bold text-red-400 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                    @foreach($topImpacts as $rank => $event)
                    @php
                        $g = $event->acceleration_peak;
                        $severity = $g >= 4.0 ? ['label' => 'CRITICAL', 'class' => 'bg-red-600 text-white']
                                  : ($g >= 3.0 ? ['label' => 'HIGH',     'class' => 'bg-orange-500 text-white']
                                  : ($g >= 2.0 ? ['label' => 'MODERATE', 'class' => 'bg-yellow-500 text-white']
                                  :              ['label' => 'LOW',      'class' => 'bg-green-500 text-white']));
                        $barPct = $maxImpact > 0 ? round(($g / $maxImpact) * 100) : 0;
                        $statusMap2 = [
                            'confirmed'             => ['label' => 'CONFIRMED',   'class' => 'text-red-600 dark:text-red-400'],
                            'false_alarm'           => ['label' => 'FALSE ALARM', 'class' => 'text-yellow-600'],
                            'resolved_by_caregiver' => ['label' => 'RESOLVED',    'class' => 'text-green-600'],
                            'pending'               => ['label' => 'PENDING',     'class' => 'text-orange-500'],
                            'cancelled_by_user'     => ['label' => 'CANCELLED',   'class' => 'text-gray-400'],
                        ];
                        $s2 = $statusMap2[$event->status] ?? ['label' => strtoupper($event->status), 'class' => 'text-gray-500'];
                    @endphp
                    <tr class="hover:bg-red-50/30 dark:hover:bg-red-950/10 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black
                                {{ $rank === 0 ? 'bg-red-600 text-white' : ($rank === 1 ? 'bg-red-400 text-white' : ($rank === 2 ? 'bg-red-300 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300')) }}">
                                {{ $rank + 1 }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-semibold ui-title text-xs">{{ $event->occurred_at->format('M j, Y') }}</div>
                            <div class="text-gray-400 dark:text-slate-500 text-[11px]">{{ $event->occurred_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-black text-red-600 dark:text-red-400 text-lg leading-none">{{ number_format($g, 2) }}</div>
                            <div class="text-[10px] text-gray-400 dark:text-slate-500">G-Force</div>
                            {{-- mini progress bar --}}
                            <div class="w-24 h-1.5 bg-gray-100 dark:bg-slate-700 rounded-full mt-1.5">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-red-400 to-red-600" style="width: {{ $barPct }}%"></div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $severity['class'] }}">{{ $severity['label'] }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide {{ $s2['class'] }}">
                            {{ $s2['label'] }}
                        </td>
                        <td class="px-5 py-3.5 text-xs ui-subtitle max-w-[200px] truncate">
                            {{ $event->notes ?: '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Severity legend --}}
        <div class="px-6 py-3 bg-gray-50 dark:bg-slate-800/50 border-t border-gray-100 dark:border-slate-700 flex flex-wrap gap-3 text-xs">
            <span class="font-semibold text-gray-500 dark:text-slate-400">Severity scale:</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span> Low (&lt;2G)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span> Moderate (2–3G)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-orange-500 inline-block"></span> High (3–4G)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-600 inline-block"></span> Critical (≥4G)</span>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    const weeklyDays   = {!! json_encode($days->map(fn($d) => \Carbon\Carbon::parse($d)->format('D, M j'))->values()) !!};
    const weeklyFalls  = {!! json_encode($weeklyFalls) !!};
    const weeklySos    = {!! json_encode($weeklySos) !!};
    const hoursData    = {!! json_encode(array_values($hours)) !!};
    const impactData   = {!! json_encode($impactData) !!};
    const monthlyDays  = {!! json_encode($monthlyDays) !!};
    const monthlyCounts= {!! json_encode($monthlyCounts) !!};
    const statusData   = {!! json_encode(array_values($statusBreakdown)) !!};

    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.color = 'rgba(255,255,255,0.85)';
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 11;

        const wgrid = { color: 'rgba(255,255,255,0.1)', borderColor: 'rgba(255,255,255,0.15)' };
        const wtick = { color: 'rgba(255,255,255,0.8)' };

        // ── 1. Weekly Trend ─────────────────────────────────────
        new Chart(document.getElementById('weeklyTrendChart'), {
            type: 'line',
            data: {
                labels: weeklyDays,
                datasets: [
                    {
                        label: 'Falls',
                        data: weeklyFalls,
                        borderColor: '#ffffff',
                        backgroundColor: 'rgba(255,255,255,0.15)',
                        tension: 0.4, fill: true,
                        pointBackgroundColor: '#ffffff',
                        pointRadius: 4, pointHoverRadius: 6
                    },
                    {
                        label: 'SOS',
                        data: weeklySos,
                        borderColor: 'rgba(165,180,252,0.9)',
                        backgroundColor: 'rgba(165,180,252,0.1)',
                        borderDash: [5, 4],
                        tension: 0.4, fill: true,
                        pointBackgroundColor: 'rgba(165,180,252,0.9)',
                        pointRadius: 4, pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: wtick },
                    y: { grid: wgrid, ticks: { ...wtick, stepSize: 1 }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });

        // ── 2. 30-Day Monthly Activity ───────────────────────────
        new Chart(document.getElementById('monthlyTrendChart'), {
            type: 'bar',
            data: {
                labels: monthlyDays,
                datasets: [{
                    label: 'Events',
                    data: monthlyCounts,
                    backgroundColor: 'rgba(255,255,255,0.75)',
                    borderRadius: 3,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { ...wtick, maxTicksLimit: 8 } },
                    y: { grid: wgrid, ticks: { ...wtick, stepSize: 1 }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });

        // ── 3. Hourly Risk ───────────────────────────────────────
        const hourLabels = Array.from({length: 24}, (_, i) => i.toString().padStart(2,'0') + ':00');
        new Chart(document.getElementById('hourlyRiskChart'), {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [{
                    label: 'Events',
                    data: hoursData,
                    backgroundColor: hoursData.map(v => {
                        const max = Math.max(...hoursData);
                        if (max === 0) return 'rgba(255,255,255,0.4)';
                        const intensity = v / max;
                        return `rgba(255,255,255,${0.2 + intensity * 0.75})`;
                    }),
                    borderRadius: 3,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { ...wtick, maxTicksLimit: 12 } },
                    y: { grid: wgrid, ticks: { ...wtick, stepSize: 1 }, beginAtZero: true }
                },
                plugins: { legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: ctx => ctx[0].label + ' — ' + (parseInt(ctx[0].label)+1).toString().padStart(2,'0') + ':00',
                            label: ctx => ' ' + ctx.raw + ' event(s)'
                        }
                    }
                }
            }
        });

        // ── 4. Status Donut ──────────────────────────────────────
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Confirmed','False Alarm','Resolved','Pending','Cancelled'],
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#ef4444','#facc15','#22c55e','#f97316','#9ca3af'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });

        // ── 5. Impact Scatter ────────────────────────────────────
        new Chart(document.getElementById('impactScatterChart'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Fall Impact',
                    data: impactData,
                    backgroundColor: 'rgba(255,255,255,0.85)',
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    pointBorderColor: 'rgba(255,255,255,0.3)',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: {
                        grid: wgrid,
                        title: { display: true, text: 'G-Force', color: 'rgba(255,255,255,0.7)', font: { weight: 'bold' } },
                        ticks: wtick, beginAtZero: true
                    },
                    x: {
                        type: 'category',
                        grid: { display: false },
                        ticks: { ...wtick, maxTicksLimit: 10 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y.toFixed(2)} G  ·  ${ctx.raw.x}`
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
