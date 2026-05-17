<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
     x-data="iotConnection({
        userId: {{ auth()->id() }},
        deviceId: {{ $device->id }},
        wsUrl: @js(sprintf('ws://%s:%d?user_id=%s', request()->getHost(), config('iot.ws_port'), auth()->id())),
        apiBase: @js($device->resolvedApiBaseUrl()),
        arduinoRoot: @js($device->arduinoApiRootUrl()),
        deviceToken: @js($device->device_token),
        testUrl: @js(route('iot.test')),
        statusUrl: @js(route('iot.status')),
        saveApiUrl: @js(route('iot.api_base')),
        csrf: @js(csrf_token()),
        initialMagnitude: {{ $device->last_magnitude ?? 'null' }},
        initialBattery: {{ $device->battery_level ?? 0 }}
     })">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="wsConnected ? 'bg-green-500 animate-pulse' : 'bg-gray-300'"></span>
                IoT Connection
            </h3>
            <p class="text-xs text-gray-500 mt-1">Hubungkan ESP32 ke API Laravel. Real-time via WebSocket.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold px-3 py-1 rounded-full"
                  :class="deviceOnline ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  x-text="deviceOnline ? 'Device Online' : 'Device Offline'"></span>
            <button type="button" @click="testApi()" :disabled="testing"
                    class="bg-slate-800 hover:bg-slate-900 disabled:opacity-50 text-white text-xs font-bold py-2 px-4 rounded-lg transition"
                    x-text="testing ? 'Testing...' : 'Test API'"></button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3 text-xs">
            <div>
                <p class="font-bold text-gray-500 uppercase tracking-wider mb-1">API Base URL (untuk ESP32)</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" x-model="apiBase"
                           placeholder="http://192.168.1.5:8000/api"
                           class="flex-1 bg-white px-3 py-2 rounded-lg border border-indigo-200 text-indigo-800 text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none">
                    <button type="button" @click="saveApiBase()" :disabled="savingApi"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold px-4 py-2 rounded-lg whitespace-nowrap"
                            x-text="savingApi ? 'Menyimpan...' : 'Simpan'"></button>
                </div>
                <p class="text-[10px] text-gray-500 mt-1">Arduino <code class="bg-white px-1 rounded">API_BASE_URL</code>: <span class="font-mono text-indigo-600" x-text="arduinoRoot"></span></p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div><p class="font-bold text-gray-400 mb-1">POST</p><code class="text-[10px] block">/sensor-data</code></div>
                <div><p class="font-bold text-gray-400 mb-1">POST</p><code class="text-[10px] block">/fall-detected</code></div>
                <div><p class="font-bold text-gray-400 mb-1">POST</p><code class="text-[10px] block">/sos</code></div>
            </div>
            <div>
                <p class="font-bold text-gray-500 uppercase tracking-wider mb-1">Header ESP32</p>
                <code class="block bg-white px-3 py-2 rounded-lg border text-gray-700 break-all">X-Device-Token: <span x-text="deviceToken"></span></code>
            </div>
            <p x-show="apiMessage" class="text-emerald-600 font-semibold" x-text="apiMessage"></p>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
            <p class="text-xs font-bold opacity-80 uppercase">Live Telemetry</p>
            <p class="text-3xl font-black mt-2" x-text="liveMagnitude !== null ? liveMagnitude.toFixed(2) + ' G' : '—'"></p>
            <p class="text-xs mt-2 opacity-90">Battery: <span x-text="$store.telemetry.battery !== null ? $store.telemetry.battery + '%' : '—'"></span></p>
            <p class="text-[10px] mt-3 opacity-75"
               x-text="lastWsEvent ? 'WS: ' + lastWsEvent : (wsConnected ? 'WebSocket connected' : 'WebSocket off — jalankan: npm run ws')"></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('iotConnection', (config) => ({
        userId: config.userId,
        deviceId: config.deviceId,
        wsUrl: config.wsUrl,
        apiBase: config.apiBase,
        arduinoRoot: config.arduinoRoot,
        deviceToken: config.deviceToken,
        testUrl: config.testUrl,
        statusUrl: config.statusUrl,
        saveApiUrl: config.saveApiUrl,
        csrf: config.csrf,
        wsConnected: false,
        deviceOnline: false,
        testing: false,
        savingApi: false,
        apiMessage: '',
        lastWsEvent: '',
        liveMagnitude: config.initialMagnitude,
        liveBattery: config.initialBattery,
        ws: null,
        lastEventCount: null,
        init() {
            this.connectWebSocket();
            setInterval(() => this.pollStatus(), 5000);
            this.pollStatus();
        },
        connectWebSocket() {
            try {
                this.ws = new WebSocket(this.wsUrl);
                this.ws.onopen = () => { this.wsConnected = true; };
                this.ws.onclose = () => {
                    this.wsConnected = false;
                    setTimeout(() => this.connectWebSocket(), 5000);
                };
                this.ws.onmessage = (event) => {
                    const msg = JSON.parse(event.data);
                    this.lastWsEvent = msg.type;
                    if (msg.type === 'telemetry' && msg.payload) {
                        if (msg.payload.magnitude != null) this.liveMagnitude = parseFloat(msg.payload.magnitude);
                        Alpine.store('telemetry').applyPayload(msg.payload);
                        this.deviceOnline = true;
                    }
                    if (['fall_detected', 'sos_active', 'sos_cancelled'].includes(msg.type)) {
                        window.location.reload();
                    }
                };
            } catch (e) {
                this.wsConnected = false;
            }
        },
        pollStatus() {
            fetch(this.statusUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.deviceOnline = !!data.connected;
                    if (data.last_magnitude != null) this.liveMagnitude = parseFloat(data.last_magnitude);
                    Alpine.store('telemetry').applyPayload(data);
                    if (this.lastEventCount !== null && data.event_count > this.lastEventCount) {
                        window.location.reload();
                    }
                    this.lastEventCount = data.event_count;
                })
                .catch(() => {});
        },
        async saveApiBase() {
            this.savingApi = true;
            this.apiMessage = '';
            try {
                const res = await fetch(this.saveApiUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ api_base_url: this.apiBase }),
                });
                const data = await res.json();
                if (data.success) {
                    this.apiBase = data.api_base;
                    this.arduinoRoot = data.arduino_root;
                    this.apiMessage = data.message || 'URL disimpan.';
                } else {
                    this.apiMessage = data.message || 'Gagal menyimpan URL.';
                }
            } catch (e) {
                this.apiMessage = 'Gagal menyimpan URL.';
            } finally {
                this.savingApi = false;
            }
        },
        async testApi() {
            this.testing = true;
            this.apiMessage = '';
            try {
                const res = await fetch(this.testUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json();
                this.apiMessage = data.message || 'API siap.';
            } catch (e) {
                this.apiMessage = 'Gagal menghubungi API.';
            } finally {
                this.testing = false;
            }
        },
    }));
});
</script>
@endpush
