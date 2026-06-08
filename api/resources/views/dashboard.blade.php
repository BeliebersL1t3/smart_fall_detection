@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-10 mt-4">

    @if($latestEvent && $latestEvent->status === 'pending')
    <div x-data="{ 
            timeLeft: {{ $device->immobility_duration ?? 15 }}, 
            show: true,
            init() {
                let timer = setInterval(() => {
                    if (this.timeLeft > 0) this.timeLeft--;
                    else {
                        clearInterval(timer);
                        fetch('{{ route('event.auto_confirm', $latestEvent->id) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                        }).then(r => { if(r.ok) window.location.reload(); });
                    }
                }, 1000);
            }
        }" 
        x-show="show && timeLeft > 0" 
        class="ui-card shadow-lg border-l-4 border-yellow-500 dark:border-yellow-600 p-6 relative overflow-hidden">
        <div class="flex items-center justify-between relative z-10">
            <div class="flex items-center space-x-4">
                <div class="bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 p-3 rounded-full"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                <div>
                    <h2 class="text-xl font-bold ui-title">Fall Detected - Pending Verification</h2>
                    <p class="text-sm ui-subtitle">Location: {{ $deviceLocation }} | Time left: <span class="font-bold text-yellow-600" x-text="timeLeft + 's'"></span></p>
                </div>
            </div>
            
            <button type="button" 
    @click="show = false; fetch('{{ route('event.false_alarm', $latestEvent->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });" 
    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg shadow transition">
    Mark as False Alarm
</button>

        </div>
    </div>
    @endif

    @php
        $isDismissed = $latestEvent ? in_array($latestEvent->id, session('dismissed_alerts', [])) : false;
    @endphp

    @if($latestEvent && $latestEvent->status === 'confirmed' && !$isDismissed)
    <div x-data="{ 
            showEmergency: true,
            alarmSound: new Audio('https://assets.mixkit.co/active_storage/sfx/995/995-preview.mp3'),
            init() {
                this.alarmSound.loop = true;
                this.alarmSound.play().catch(error => {
                    console.log('Autoplay dicegah browser. Klik halaman.');
                });
            },
            stopAlarm() {
                this.alarmSound.pause();
                this.alarmSound.currentTime = 0;
            }
        }" 
        x-show="showEmergency" 
        class="bg-red-500 rounded-xl shadow-lg shadow-red-500/40 p-6 text-white relative">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white text-red-500 p-3 rounded-full animate-pulse">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">EMERGENCY: Immediate Attention Required</h2>
                    <p class="text-red-100">Location: {{ $deviceLocation }} @if($latestEvent->acceleration_peak) | Impact: <strong class="text-white">{{ number_format($latestEvent->acceleration_peak, 2) }} G</strong> @endif</p>
                </div>
            </div>
            <button type="button" @click="stopAlarm(); showEmergency = false; fetch('{{ route('event.dismiss', $latestEvent->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }});" class="bg-white text-red-500 hover:bg-red-50 font-bold py-2 px-6 rounded-lg shadow transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path></svg>
                Dismiss Alert
            </button>
        </div>
    </div>
    @endif

    @include('partials.iot-connection')

    @if(config('app.debug'))
    <div class="bg-indigo-50 dark:bg-indigo-950/50 border border-dashed border-indigo-300 dark:border-indigo-700 rounded-xl p-5 relative">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    Developer Sandbox
                </h3>
                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">Gunakan tombol ini untuk simulasi masuknya data dari ESP32.</p>
            </div>
            <div class="flex space-x-3">
                <form action="{{ route('simulate', 'fall') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition">Test Fall Sensor</button>
                </form>
                <form action="{{ route('simulate', 'sos') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition">Test Manual SOS</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-6">
        <div class="ui-card-stat">
            <div class="absolute -top-6 left-4 bg-orange-500 rounded-xl p-4 shadow-lg shadow-orange-500/40 text-white">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
            </div>
            <div class="text-right pt-2">
                <p class="text-sm ui-subtitle font-medium">Device Status</p>
                @if($device->is_online)
                    <h4 class="text-2xl font-bold ui-title flex items-center justify-end"><span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span> Online</h4>
                @else
                    <h4 class="text-2xl font-bold ui-title flex items-center justify-end"><span class="w-3 h-3 bg-gray-400 rounded-full mr-2"></span> Offline</h4>
                @endif
            </div>
            <hr class="mt-4 ui-divider">
            <div class="mt-3 text-xs ui-muted flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                @if($device->last_seen_at)
                    Terakhir kirim {{ $device->last_seen_at->diffForHumans() }}
                @else
                    Belum ada data dari ESP32
                @endif
            </div>
        </div>

        @include('partials.battery-monitor')

        <a href="{{ route('dashboard', ['filter' => 'emergencies']) }}#history-table" class="block ui-card-stat hover:shadow-md hover:-translate-y-1 transition-all duration-200 group cursor-pointer">
            <div class="absolute -top-6 left-4 bg-red-500 rounded-xl p-4 shadow-lg shadow-red-500/40 text-white group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div class="text-right pt-2">
                <p class="text-sm ui-subtitle font-medium">Emergencies</p>
                <h4 class="text-2xl font-bold ui-title">{{ $emergencyCount }}</h4>
            </div>
            <hr class="mt-4 ui-divider">
            <div class="mt-3 text-xs text-red-500 font-semibold flex items-center justify-between">
                <span>Requires action</span>
                <span>View &rarr;</span>
            </div>
        </a>

        <a href="{{ route('dashboard', ['filter' => 'all']) }}#history-table" class="block ui-card-stat hover:shadow-md hover:-translate-y-1 transition-all duration-200 group cursor-pointer">
            <div class="absolute -top-6 left-4 bg-blue-500 rounded-xl p-4 shadow-lg shadow-blue-500/40 text-white group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div class="text-right pt-2">
                <p class="text-sm ui-subtitle font-medium">Total Records</p>
                <h4 class="text-2xl font-bold ui-title">{{ $totalEvents }}</h4>
            </div>
            <hr class="mt-4 ui-divider">
            <div class="mt-3 text-xs ui-muted font-medium flex items-center justify-between">
                <span>All historical events</span>
                <span class="text-blue-500 group-hover:underline">View All &rarr;</span>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-8">
        <div class="ui-card relative p-4 mt-6">
            <div class="absolute -top-8 left-4 right-4 bg-gradient-to-tr from-green-600 to-green-400 rounded-xl p-4 shadow-lg shadow-green-500/40 h-48 flex items-center justify-center">
                <canvas id="eventTypesChart"></canvas>
            </div>
            <div class="pt-44">
                <h4 class="text-lg font-bold ui-title">Event Distribution</h4>
                <p class="text-sm ui-subtitle">Falls vs Manual SOS triggers.</p>
            </div>
        </div>

        <div class="ui-card relative p-4 mt-6">
            <div class="absolute -top-8 left-4 right-4 bg-gradient-to-tr from-orange-600 to-orange-400 rounded-xl p-4 shadow-lg shadow-orange-500/40 h-48 flex items-center justify-center">
                <canvas id="alertStatusChart"></canvas>
            </div>
            <div class="pt-44">
                <h4 class="text-lg font-bold ui-title">Resolution Status</h4>
                <p class="text-sm ui-subtitle">Breakdown of how events are handled.</p>
            </div>
        </div>
    </div>

    <div id="history-table" class="ui-card relative p-4 mt-12 mb-8 scroll-mt-24">
        
        <div class="absolute -top-8 left-4 right-4 bg-gradient-to-tr from-gray-800 to-gray-700 rounded-xl p-6 shadow-lg shadow-gray-500/40 text-white flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div>
                <h4 class="text-lg font-bold">Event History Ledger</h4>
                <p class="text-sm text-gray-300 font-light">
                    Showing {{ request('limit', 10) === 'all' ? 'All' : request('limit', 10) }} recent events
                </p>
            </div>
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <form id="filterForm" action="{{ route('dashboard') }}#history-table" method="GET" class="flex space-x-2">
                    <select name="limit" onchange="document.getElementById('filterForm').submit()" class="bg-white/10 text-white border border-white/20 text-sm rounded-lg py-2.5 px-3 outline-none cursor-pointer">
                        <option value="10" class="text-gray-800" {{ request('limit', 10) == 10 ? 'selected' : '' }}>10 Rows</option>
                        <option value="25" class="text-gray-800" {{ request('limit') == 25 ? 'selected' : '' }}>25 Rows</option>
                        <option value="50" class="text-gray-800" {{ request('limit') == 50 ? 'selected' : '' }}>50 Rows</option>
                        <option value="all" class="text-gray-800" {{ request('limit') == 'all' ? 'selected' : '' }}>All Rows</option>
                    </select>

                    <select name="filter" onchange="document.getElementById('filterForm').submit()" class="bg-white/10 text-white border border-white/20 text-sm rounded-lg py-2.5 px-3 outline-none cursor-pointer">
                        <option value="all" class="text-gray-800" {{ $currentFilter == 'all' ? 'selected' : '' }}>All Events</option>
                        <option value="pending" class="text-gray-800" {{ $currentFilter == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="falls" class="text-gray-800" {{ $currentFilter == 'falls' ? 'selected' : '' }}>Falls Only</option>
                        <option value="sos" class="text-gray-800" {{ $currentFilter == 'sos' ? 'selected' : '' }}>SOS Only</option>
                        <option value="emergencies" class="text-gray-800" {{ $currentFilter == 'emergencies' ? 'selected' : '' }}>Emergencies</option>
                        <option value="false_alarms" class="text-gray-800" {{ $currentFilter == 'false_alarms' ? 'selected' : '' }}>False Alarms</option>
                        <option value="resolved" class="text-gray-800" {{ $currentFilter == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="cancelled" class="text-gray-800" {{ $currentFilter == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>

                <div class="flex space-x-2">
    <a href="{{ route('export.excel', ['filter' => $currentFilter, 'limit' => request('limit', 10)]) }}" class="bg-emerald-600 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow transition hover:bg-emerald-700 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> 
        Excel
    </a>

    <a href="{{ route('export.pdf', ['filter' => $currentFilter, 'limit' => request('limit', 10)]) }}" class="bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-100 hover:bg-gray-100 dark:hover:bg-slate-600 text-sm font-bold py-2.5 px-4 rounded-lg shadow transition flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> 
        PDF
    </a>
</div>
            </div>
        </div> <div class="pt-20 overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="ui-table-head">
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Trigger Type</th>
                        <th class="px-4 py-3">Impact (G)</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-50 dark:divide-slate-700">
                    @php
                        $limit = request('limit', 10);
                        $displayedEvents = $limit === 'all' ? $recentEvents : $recentEvents->take((int)$limit);
                    @endphp

                    @forelse($displayedEvents as $event)
                    <tr class="ui-table-row" x-data="{ isResolved: false }">
                            <td class="px-4 py-4">
                            <div class="font-bold ui-title">{{ $event->occurred_at->format('M j, Y') }}</div>
                            <div class="text-xs ui-subtitle">{{ $event->occurred_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $event->type == 'auto_fall' ? 'text-red-500 bg-red-50 dark:bg-red-950/50' : 'text-blue-500 bg-blue-50 dark:bg-blue-950/50' }}">
                                {{ $event->type == 'auto_fall' ? 'SENSOR FALL' : 'MANUAL SOS' }}
                            </span>
                        </td>
                        <td class="px-4 py-4 font-bold text-gray-700 dark:text-slate-300">
                            {{ $event->acceleration_peak ? number_format($event->acceleration_peak, 2).' G' : '-' }}
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $statusColor = match($event->status) {
                                    'confirmed' => 'text-red-600 font-bold',
                                    'resolved_by_caregiver' => 'text-green-600',
                                    'false_alarm' => 'text-orange-500',
                                    default => 'text-gray-500'
                                };
                            @endphp
                            
                            <span x-show="!isResolved" class="{{ $statusColor }} uppercase text-xs font-semibold tracking-wider">
                                {{ str_replace('_', ' ', $event->status) }}
                            </span>

                            <span x-show="isResolved" style="display: none;" class="text-green-600 font-bold uppercase text-xs font-semibold tracking-wider">
                                RESOLVED
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            @if($event->status == 'confirmed')
                            
                            <div x-data="{ open: false }" x-show="!isResolved">
                                <button @click="open = true" class="text-emerald-500 hover:text-emerald-700 font-bold text-xs border border-emerald-500 dark:border-emerald-600 px-3 py-1.5 rounded-md hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition">
                                    Resolve
                                </button>

                                <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto text-left" x-cloak>
                                    <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
                                    <div class="relative min-h-screen flex items-center justify-center p-4">
                                        <div @click.away="open = false" class="ui-card rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
                                            <h3 class="text-lg font-bold ui-title mb-2">Resolution Notes</h3>
                                            <p class="text-sm ui-subtitle mb-4 font-medium">Apa tindakan yang telah diambil untuk kejadian ini?</p>
                                            
                                            <form @submit.prevent="open = false; isResolved = true; fetch('{{ route('event.resolve', $event->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ notes: $event.target.notes.value }) });">
                                            @csrf
                                                <textarea 
                                                    name="notes" rows="4" required
                                                    placeholder="Contoh: Pasien terpeleset karpet..."
                                                    class="ui-input mb-4 focus:ring-emerald-500"
                                                ></textarea>

                                                <div class="flex space-x-3 justify-end">
                                                    <button type="button" @click="open = false" class="px-4 py-2 text-sm font-bold ui-subtitle hover:text-gray-700 dark:hover:text-slate-200">Cancel</button>
                                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-6 rounded-lg shadow-lg shadow-emerald-200 transition">Save & Resolve</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <span x-show="isResolved" style="display: none;" class="text-gray-300 font-bold">-</span>
                            
                            @else
                                @if($event->notes)
                                    <div class="group relative inline-block">
                                        <svg class="w-5 h-5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block w-48 p-2 bg-gray-800 text-white text-[10px] rounded shadow-lg z-50 whitespace-normal text-left">
                                            {{ $event->notes }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-300 font-bold">-</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center ui-muted">No events found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.documentElement.style.scrollBehavior = 'smooth';

        Chart.defaults.color = 'rgba(255, 255, 255, 0.9)';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

        const ctxTypes = document.getElementById('eventTypesChart').getContext('2d');
        const eventTypesChart = new Chart(ctxTypes, {
            type: 'doughnut',
            data: {
                labels: ['Falls', 'SOS'],
                datasets: [{
                    data: [{{ $fallsCount }}, {{ $sosCount }}],
                    backgroundColor: ['rgba(255,255,255,0.9)', 'rgba(255,255,255,0.3)'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } },
                onHover: (event, chartElement) => { event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default'; },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const label = eventTypesChart.data.labels[elements[0].index];
                        let filterParam = label === 'Falls' ? 'falls' : (label === 'SOS' ? 'sos' : 'all');
                        window.location.href = "{{ route('dashboard') }}?filter=" + filterParam + "#history-table";
                    }
                }
            }
        });

        const ctxStatus = document.getElementById('alertStatusChart').getContext('2d');
        const alertStatusChart = new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Confirmed', 'False Alarm', 'Resolved', 'Cancelled'],
                datasets: [{
                    data: [{{ $confirmedCount }}, {{ $falseAlarmCount }}, {{ $resolvedCount }}, {{ $cancelledCount }}],
                    backgroundColor: ['#ff4d4d', '#ffcc00', '#33cc33', '#a6a6a6'],
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } },
                onHover: (event, chartElement) => { event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default'; },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const label = alertStatusChart.data.labels[elements[0].index];
                        let filterParam = 'all';
                        if (label === 'Confirmed') filterParam = 'emergencies';
                        else if (label === 'False Alarm') filterParam = 'false_alarms';
                        else if (label === 'Resolved') filterParam = 'resolved';
                        else if (label === 'Cancelled') filterParam = 'cancelled';
                        window.location.href = "{{ route('dashboard') }}?filter=" + filterParam + "#history-table";
                    }
                }
            }
        });
    });
</script>

@endpush