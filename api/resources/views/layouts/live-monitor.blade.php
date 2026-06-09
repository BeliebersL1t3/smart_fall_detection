{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  GLOBAL LIVE MONITOR                                            │
    │  Berjalan di semua halaman (diinclude di layouts/app.blade.php) │
    │  - Poll /dashboard/iot/live setiap 2 detik                     │
    │  - Terhubung ke WebSocket jika tersedia                         │
    │  - Tampilkan toast notifikasi saat alarm / fall baru            │
    │  - Update sidebar badge & header status indicator               │
    └─────────────────────────────────────────────────────────────────┘
--}}
@auth
<div
    id="live-monitor"
    x-data="liveMonitor()"
    x-init="init()"
>
    {{-- ══ FLOATING TOAST CONTAINER ══════════════════════════════════ --}}
    <div
        class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"
        style="max-width: 360px;"
        id="toast-container"
    >
        {{-- ALARM TOAST (fall / confirmed) --}}
        <div
            x-show="alarmToast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto bg-red-600 text-white rounded-2xl shadow-2xl shadow-red-600/50 overflow-hidden"
            style="display:none;"
        >
            <div class="flex items-start gap-3 p-4">
                <div class="bg-white/20 rounded-xl p-2 flex-shrink-0 animate-pulse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-black text-sm" x-text="alarmToast.title">Alert</p>
                    <p class="text-red-100 text-xs mt-0.5 leading-relaxed" x-text="alarmToast.body"></p>
                    <div class="flex gap-2 mt-3">
                        <a href="{{ route('dashboard') }}"
                           class="bg-white text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
                            Buka Dashboard →
                        </a>
                        <button @click="dismissAlarmToast()"
                                class="bg-white/20 hover:bg-white/30 text-xs font-bold px-3 py-1.5 rounded-lg transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
            {{-- progress bar auto-close --}}
            <div class="h-1 bg-white/20 w-full">
                <div class="h-1 bg-white/70 transition-all ease-linear" :style="'width:' + alarmToast.progress + '%'"></div>
            </div>
        </div>

        {{-- INFO TOAST (online/offline) --}}
        <div
            x-show="infoToast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto rounded-2xl shadow-xl overflow-hidden"
            :class="infoToast.type === 'online'
                ? 'bg-emerald-600 text-white shadow-emerald-600/40'
                : 'bg-gray-800 text-white shadow-gray-900/40'"
            style="display:none;"
        >
            <div class="flex items-center gap-3 p-4">
                <div class="rounded-xl p-2 flex-shrink-0"
                     :class="infoToast.type === 'online' ? 'bg-white/20' : 'bg-white/10'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold" x-text="infoToast.message"></p>
            </div>
        </div>
    </div>

    {{-- ══ GLOBAL ALARM BANNER (melayang di bawah header) ═══════════ --}}
    <div
        x-show="globalAlarm"
        x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="fixed top-20 left-64 right-0 z-[9990] px-6 pointer-events-none"
        style="display:none;"
    >
        <div class="bg-red-600 text-white rounded-2xl shadow-2xl shadow-red-600/50 px-6 py-3 flex items-center justify-between pointer-events-auto">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-white animate-ping inline-block"></span>
                <span class="font-black text-sm" x-text="globalAlarmMsg">ALARM AKTIF</span>
            </div>
            <a href="{{ route('dashboard') }}"
               class="bg-white text-red-600 text-xs font-bold px-4 py-1.5 rounded-lg hover:bg-red-50 transition ml-4 flex-shrink-0">
                Tangani →
            </a>
        </div>
    </div>
</div>

{{-- ══ SIDEBAR LIVE INDICATOR (disisipkan via JS) ══════════════════ --}}
<script>
(function () {
    // Inject live dot into sidebar Dashboard link
    document.addEventListener('DOMContentLoaded', function () {
        const dashLink = document.querySelector('a[href="{{ route("dashboard") }}"]');
        if (dashLink) {
            const dot = document.createElement('span');
            dot.id = 'sidebar-alarm-dot';
            dot.className = 'ml-auto w-2.5 h-2.5 rounded-full bg-red-500 animate-ping hidden';
            dashLink.appendChild(dot);
        }
    });
})();
</script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('liveMonitor', () => ({
        // ── state ──────────────────────────────────────────────────────
        liveUrl:         '{{ route("iot.live") }}',
        wsBaseUrl:       '{{ sprintf("ws://%s:%d", request()->getHost(), config("iot.ws_port")) }}',
        userId:          {{ auth()->id() }},

        // Suppress all popups when user is already on the dashboard
        // (dashboard has its own inline alert UI)
        onDashboard:     {{ request()->routeIs('dashboard') ? 'true' : 'false' }},

        prevOnline:      null,   // track online↔offline flips
        prevAlarmState:  null,   // track normal↔alarm flips
        prevEventCount:  null,   // track new events
        ws:              null,
        pollTimer:       null,

        alarmToast: { visible: false, title: '', body: '', progress: 100, timer: null },
        infoToast:  { visible: false, type: 'online', message: '', timer: null },
        globalAlarm: false,
        globalAlarmMsg: '🚨 ALARM AKTIF — Ada fall/SOS yang belum ditangani!',

        // ── lifecycle ──────────────────────────────────────────────────
        init() {
            this.connectWs();
            this.poll();           // immediate first poll
            this.pollTimer = setInterval(() => this.poll(), 2000);
        },

        // ── WebSocket ──────────────────────────────────────────────────
        connectWs() {
            try {
                const url = this.wsBaseUrl + '?user_id=' + this.userId;
                this.ws = new WebSocket(url);

                this.ws.onmessage = (ev) => {
                    try {
                        const msg = JSON.parse(ev.data);
                        this.handleWsMessage(msg);
                    } catch (_) {}
                };

                this.ws.onclose = () => {
                    // retry after 5 s
                    setTimeout(() => this.connectWs(), 5000);
                };
            } catch (_) {}
        },

        handleWsMessage(msg) {
            if (msg.type === 'fall_detected') {
                if (this.onDashboard) {
                    window.location.reload();
                    return;
                }
                const impact = msg.payload?.magnitude
                    ? parseFloat(msg.payload.magnitude).toFixed(2) + ' G'
                    : '—';
                this.showAlarmToast(
                    '🆘 JATUH TERDETEKSI!',
                    'Benturan: ' + impact + '  ·  Menunggu verifikasi Anda.'
                );
                this.setGlobalAlarm(true, '🆘 Fall detected — ' + impact + '!');
                this.blinkSidebarDot(true);
            }

            if (msg.type === 'sos_active') {
                if (this.onDashboard) {
                    window.location.reload();
                    return;
                }
                this.showAlarmToast('🔴 SOS AKTIF!', 'Tombol darurat ditekan. Segera tangani!');
                this.setGlobalAlarm(true, '🔴 SOS Aktif — segera tangani!');
                this.blinkSidebarDot(true);
            }

            if (msg.type === 'sos_cancelled' || msg.type === 'alarm_dismissed') {
                this.setGlobalAlarm(false);
                this.blinkSidebarDot(false);
            }

            if (msg.type === 'telemetry' && msg.payload) {
                const status = msg.payload.status ?? msg.payload.last_status;
                if (status === 'normal') {
                    this.setGlobalAlarm(false);
                    this.blinkSidebarDot(false);
                }
            }
        },

        // ── Polling (fallback / complement to WS) ─────────────────────
        async poll() {
            try {
                const res  = await fetch(this.liveUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.ok) return;

                // ─ online / offline flip
                if (this.prevOnline !== null && this.prevOnline !== data.is_online) {
                    if (data.is_online) {
                        this.showInfoToast('online', '✅ Perangkat terhubung kembali');
                    } else {
                        this.showInfoToast('offline', '⚠️ Perangkat tidak merespon');
                    }
                }
                this.prevOnline = data.is_online;

                // ─ alarm state flip (normal → alarm)
                if (data.last_status === 'alarm' && this.prevAlarmState !== 'alarm') {
                    if (this.prevAlarmState !== null && this.onDashboard) {
                        window.location.reload();
                        return;
                    }
                    if (data.active_event) {
                        const ev = data.active_event;
                        const impact = ev.impact ? parseFloat(ev.impact).toFixed(2) + ' G' : '—';
                        const label  = ev.type === 'auto_fall' ? 'Fall Detected' : 'SOS Active';
                        this.showAlarmToast('🚨 ' + label + '!', 'Benturan: ' + impact);
                    }
                    this.setGlobalAlarm(true);
                    this.blinkSidebarDot(true);
                }

                if (data.last_status === 'normal' && this.prevAlarmState === 'alarm') {
                    this.setGlobalAlarm(false);
                    this.blinkSidebarDot(false);
                    // dismiss alarm toast automatically
                    this.dismissAlarmToast();
                }
                this.prevAlarmState = data.last_status;

                // ─ new event arrived → show badge pulse but no toast (WS handles that)
                if (this.prevEventCount !== null && data.total_count > this.prevEventCount) {
                    this.blinkSidebarDot(true);
                }
                this.prevEventCount = data.total_count;

                // ─ update Alpine global store so widgets on any page can read values
                if (Alpine.store && Alpine.store('live')) {
                    Alpine.store('live').apply(data);
                }

            } catch (_) {}
        },

        // ── helpers ───────────────────────────────────────────────────
        setGlobalAlarm(on, msg) {
            if (this.onDashboard) return;
            this.globalAlarm = on;
            if (msg) this.globalAlarmMsg = msg;
            if (!on) this.globalAlarmMsg = '';
        },

        blinkSidebarDot(on) {
            const dot = document.getElementById('sidebar-alarm-dot');
            if (!dot) return;
            if (on) dot.classList.remove('hidden');
            else    dot.classList.add('hidden');
        },

        showAlarmToast(title, body) {
            if (this.onDashboard) return;
            clearTimeout(this.alarmToast.timer);
            clearInterval(this.alarmToast._progTimer);

            this.alarmToast.title   = title;
            this.alarmToast.body    = body;
            this.alarmToast.progress = 100;
            this.alarmToast.visible = true;

            // Animate progress bar over 12 s then auto-close
            const dur = 12000;
            const step = 200;
            this.alarmToast._progTimer = setInterval(() => {
                this.alarmToast.progress = Math.max(0, this.alarmToast.progress - (step / dur * 100));
            }, step);

            this.alarmToast.timer = setTimeout(() => {
                clearInterval(this.alarmToast._progTimer);
                this.alarmToast.visible = false;
            }, dur);
        },

        dismissAlarmToast() {
            clearTimeout(this.alarmToast.timer);
            clearInterval(this.alarmToast._progTimer);
            this.alarmToast.visible = false;
        },

        showInfoToast(type, message) {
            if (this.onDashboard) return;
            clearTimeout(this.infoToast.timer);
            this.infoToast.type    = type;
            this.infoToast.message = message;
            this.infoToast.visible = true;
            this.infoToast.timer   = setTimeout(() => {
                this.infoToast.visible = false;
            }, 3500);
        },
    }));

    // Global live store — lets any page widget subscribe to live data
    Alpine.store('live', {
        isOnline:     false,
        lastStatus:   'normal',
        magnitude:    null,
        battery:      null,
        pendingCount: 0,
        activeEvent:  null,
        apply(data) {
            this.isOnline     = data.is_online     ?? this.isOnline;
            this.lastStatus   = data.last_status   ?? this.lastStatus;
            this.magnitude    = data.last_magnitude ?? this.magnitude;
            this.battery      = data.battery        ?? this.battery;
            this.pendingCount = data.pending_count  ?? this.pendingCount;
            this.activeEvent  = data.active_event   ?? null;
        }
    });
});
</script>
@endauth
