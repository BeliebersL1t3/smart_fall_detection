@extends('layouts.app')
@section('title', 'Account Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 mt-2">

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-950/40 border-l-4 border-green-500 p-4 rounded-r-xl shadow-sm flex items-center space-x-3 mb-6">
        <div class="text-green-500 dark:text-green-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-green-700 dark:text-green-300 font-semibold text-sm">{{ session('success') }}</p>
    </div>
    @endif

    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">User Profile</h3>
                <p class="text-xs ui-subtitle font-medium">Login dashboard &amp; <strong>alamat penerima</strong> email alert darurat.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.profile') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Caregiver Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" required class="ui-input focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Email Penerima Alert (Login ID)</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" required class="ui-input focus:ring-blue-500">
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">
                        Alert darurat dikirim <strong>ke email ini</strong> dari pengirim sistem
                        <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{{ $mail['from_address'] ?: '—' }}</code>.
                    </p>
                    <p class="text-xs text-orange-500 mt-1 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Warning: Changing this will change your login email.
                    </p>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/30 transition-all">Save Profile</button>
            </form>
        </div>
    </div>

    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">Pengirim Email Sistem</h3>
                <p class="text-xs ui-subtitle font-medium">Akun Gmail resmi untuk mengirim alert — hanya diatur di <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded">.env</code> (tidak bisa diubah dari dashboard).</p>
            </div>
        </div>
        <div class="p-6 space-y-3 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
                    <p class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Dari (From)</p>
                    <p class="font-mono font-semibold text-emerald-700 dark:text-emerald-400">{{ $mail['from_address'] ?: 'Belum dikonfigurasi' }}</p>
                    <p class="text-xs ui-muted mt-1">{{ $mail['from_name'] }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-900/60 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
                    <p class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Ke (To)</p>
                    <p class="font-mono font-semibold text-blue-700 dark:text-blue-400">{{ auth()->user()->email }}</p>
                    <p class="text-xs ui-muted mt-1">Email profil perawat di atas</p>
                </div>
            </div>
            <p class="text-xs ui-muted">
                SMTP: {{ $mail['host'] }}:{{ $mail['port'] }} ({{ $mail['scheme'] }})
                · Kredensial pengirim aman di server.
            </p>
        </div>
    </div>

    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">Notifikasi Telegram</h3>
                <p class="text-xs ui-subtitle font-medium">Disimpan ke <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded">.env</code> — Bot Token & Chat ID penerima alert.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.telegram') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="telegram_bot_token" class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Bot Token</label>
                    <input type="text" name="telegram_bot_token" id="telegram_bot_token"
                           placeholder="{{ $telegram['bot_token_set'] ? '•••••••• (kosongkan jika tidak diubah)' : '123456789:ABCdefGHI...' }}"
                           class="ui-input focus:ring-blue-500 font-mono text-sm">
                </div>
                <div>
                    <label for="telegram_chat_id" class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Chat ID</label>
                    <input type="text" name="telegram_chat_id" id="telegram_chat_id"
                           value="{{ old('telegram_chat_id', $telegram['chat_id']) }}"
                           placeholder="Contoh: 1886921629"
                           class="ui-input focus:ring-blue-500">
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-100 dark:border-blue-800/50 text-xs text-blue-700 dark:text-blue-400 space-y-1">
                    <p><strong>Bot Token:</strong> dari @BotFather di Telegram.</p>
                    <p><strong>Chat ID:</strong> kirim <code>/start</code> ke @userinfobot untuk mendapatkan angka ID Anda.</p>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/30 transition-all">Simpan Pengaturan Telegram</button>
            </form>
        </div>
    </div>

    @if($device)
    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">ESP32 API Credentials</h3>
                <p class="text-xs ui-subtitle font-medium">Salin ke file <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded">smart_fall_detection.ino</code></p>
            </div>
        </div>
        <div class="p-6 space-y-4 text-sm">
            <form action="{{ route('settings.api_base') }}" method="POST" class="space-y-2">
                @csrf
                <p class="font-bold text-gray-500 dark:text-slate-400 text-xs uppercase mb-1">API Base URL (ESP32)</p>
                <input type="text" name="api_base_url" value="{{ $device->resolvedApiBaseUrl() }}"
                       placeholder="http://192.168.1.5:8000/api"
                       class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 py-3 text-sm font-mono text-indigo-700 dark:text-indigo-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                <p class="text-[10px] ui-muted">Arduino API_BASE_URL: <code class="font-mono">{{ $device->arduinoApiRootUrl() }}</code></p>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg">Simpan API URL</button>
            </form>
            <div>
                <p class="font-bold text-gray-500 dark:text-slate-400 text-xs uppercase mb-1">Device Token (X-Device-Token)</p>
                <code class="block bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 rounded-xl px-4 py-3 break-all font-mono dark:text-slate-300">{{ $device->device_token }}</code>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3 border border-gray-200 dark:border-slate-700 dark:text-slate-300"><span class="font-bold ui-muted">POST</span><br>/api/sensor-data</div>
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3 border border-gray-200 dark:border-slate-700 dark:text-slate-300"><span class="font-bold ui-muted">POST</span><br>/api/fall-detected</div>
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3 border border-gray-200 dark:border-slate-700 dark:text-slate-300"><span class="font-bold ui-muted">POST</span><br>/api/sos</div>
            </div>
        </div>
    </div>
    @endif

    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-orange-100 dark:bg-orange-950/50 text-orange-500 dark:text-orange-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">Device Settings</h3>
                <p class="text-xs ui-subtitle font-medium">Configure hardware behavior and alerts.</p>
            </div>
        </div>
        <div class="p-6">
            {{-- Sesuaikan route ini dengan fungsi update timer Anda --}}
            <form action="{{ route('settings.device') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">False Alarm Countdown (Seconds)</label>
                    <p class="text-xs ui-subtitle mb-3">Durasi tunggu (banner kuning) sebelum sistem otomatis mengirim peringatan darurat.</p>
                    <div class="flex items-center space-x-3">
                        <input type="number" name="immobility_duration" value="{{ $device->immobility_duration ?? 15 }}" min="5" max="120" required class="w-32 ui-input focus:ring-orange-500 font-bold text-center">
                        <span class="text-sm font-bold text-gray-600 dark:text-slate-400">Seconds</span>
                    </div>
                </div>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-orange-500/30 transition-all">Update Timer</button>
            </form>
        </div>
    </div>

    <div class="ui-card rounded-2xl overflow-hidden">
        <div class="ui-section-header flex items-center space-x-3">
            <div class="bg-red-100 dark:bg-red-950/50 text-red-500 dark:text-red-400 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold ui-title">Change Password</h3>
                <p class="text-xs ui-subtitle font-medium">Ensure your account is using a long, random password to stay secure.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.password') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="ui-input focus:ring-slate-800">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">New Password (Min 8 characters)</label>
                    <input type="password" name="new_password" required class="ui-input focus:ring-slate-800">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required class="ui-input focus:ring-slate-800">
                </div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection