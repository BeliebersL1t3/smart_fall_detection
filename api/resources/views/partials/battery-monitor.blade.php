@prepend('scripts')
<script>
document.addEventListener('alpine:init', () => {
    if (Alpine.store('telemetry')) return;
    Alpine.store('telemetry', {
        battery: null,
        batteryColor: 'gray',
        batteryStatus: 'No Data',
        charging: false,
        magnitude: null,
        init(battery, color, status, charging) {
            this.battery = battery;
            this.batteryColor = color || 'gray';
            this.batteryStatus = status || 'No Data';
            this.charging = !!charging;
        },
        applyPayload(payload) {
            if (!payload) return;
            const b = payload.battery ?? payload.battery_level;
            if (b !== null && b !== undefined) {
                this.battery = parseInt(b, 10);
                this.batteryColor = payload.battery_color || this.colorFromPercent(this.battery);
                this.batteryStatus = payload.battery_status || this.statusFromPercent(this.battery, !!payload.charging);
                this.charging = !!payload.charging;
            }
            if (payload.magnitude != null) {
                this.magnitude = parseFloat(payload.magnitude);
            }
        },
        colorFromPercent(p) {
            if (p > 70) return 'green';
            if (p >= 30) return 'yellow';
            return 'red';
        },
        statusFromPercent(p, charging) {
            if (charging) return 'Charging';
            if (p < 30) return 'Critical Battery';
            if (p < 70) return 'Low Battery';
            return 'Normal';
        },
    });
});
</script>
@endprepend

@php
    $initialBattery = $device->last_seen_at !== null ? $device->battery_level : null;
    $initialColor = \App\Support\BatteryHelper::levelColor($initialBattery);
    $initialStatus = \App\Support\BatteryHelper::statusLabel($initialBattery);
@endphp

<div class="bg-white rounded-xl shadow-sm relative p-4 border border-gray-100"
     x-data
     x-init="$store.telemetry.init(@js($initialBattery), @js($initialColor), @js($initialStatus), false)">
    <div class="absolute -top-6 left-4 rounded-xl p-4 shadow-lg text-white transition-colors duration-500"
         :class="{
            'bg-green-500 shadow-green-500/40': $store.telemetry.batteryColor === 'green',
            'bg-yellow-500 shadow-yellow-500/40': $store.telemetry.batteryColor === 'yellow',
            'bg-red-500 shadow-red-500/40': $store.telemetry.batteryColor === 'red',
            'bg-gray-400 shadow-gray-400/40': $store.telemetry.batteryColor === 'gray',
         }">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h14a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10v4"/>
        </svg>
    </div>
    <div class="text-right pt-2">
        <p class="text-sm text-gray-500 font-medium">Battery Level</p>
        <h4 class="text-2xl font-bold transition-colors duration-300"
            :class="{
                'text-green-600': $store.telemetry.batteryColor === 'green',
                'text-yellow-600': $store.telemetry.batteryColor === 'yellow',
                'text-red-600': $store.telemetry.batteryColor === 'red',
                'text-gray-400': $store.telemetry.batteryColor === 'gray',
            }"
            x-text="$store.telemetry.battery !== null ? $store.telemetry.battery + '%' : '—'">
        </h4>
        <p class="text-xs font-semibold mt-1"
           :class="{
                'text-green-600': $store.telemetry.batteryColor === 'green' && !$store.telemetry.charging,
                'text-yellow-600': $store.telemetry.batteryColor === 'yellow',
                'text-red-600': $store.telemetry.batteryColor === 'red',
                'text-blue-600': $store.telemetry.charging,
                'text-gray-400': $store.telemetry.batteryColor === 'gray',
           }"
           x-text="$store.telemetry.batteryStatus">
        </p>
    </div>
    <hr class="mt-4 border-gray-100">
    <div class="mt-3 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
        <div class="h-2 rounded-full transition-all duration-500 ease-out"
             :class="{
                'bg-green-500': $store.telemetry.batteryColor === 'green',
                'bg-yellow-500': $store.telemetry.batteryColor === 'yellow',
                'bg-red-500': $store.telemetry.batteryColor === 'red',
                'bg-gray-300': $store.telemetry.batteryColor === 'gray',
             }"
             :style="'width:' + ($store.telemetry.battery !== null ? $store.telemetry.battery + '%' : '0%')">
        </div>
    </div>
</div>
