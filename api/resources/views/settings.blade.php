@extends('layouts.app')
@section('title', 'Account Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 mt-2">

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl shadow-sm flex items-center space-x-3 mb-6">
        <div class="text-green-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-green-700 font-semibold text-sm">{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex items-center space-x-3">
            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">User Profile</h3>
                <p class="text-xs text-gray-500 font-medium">Manage your personal information and login details.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.profile') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Caregiver Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address (Login ID)</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all text-sm font-medium">
                    <p class="text-xs text-orange-500 mt-2 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Warning: Changing this will change your login email.
                    </p>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/30 transition-all">Save Profile</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex items-center space-x-3">
            <div class="bg-orange-100 text-orange-500 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">Device Settings</h3>
                <p class="text-xs text-gray-500 font-medium">Configure hardware behavior and alerts.</p>
            </div>
        </div>
        <div class="p-6">
            {{-- Sesuaikan route ini dengan fungsi update timer Anda --}}
            <form action="/settings/device" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">False Alarm Countdown (Seconds)</label>
                    <p class="text-xs text-gray-500 mb-3">Durasi tunggu (banner kuning) sebelum sistem otomatis mengirim peringatan darurat.</p>
                    <div class="flex items-center space-x-3">
                        <input type="number" name="immobility_duration" value="{{ $device->immobility_duration ?? 15 }}" min="5" max="120" required class="w-32 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none transition-all text-sm font-bold text-center">
                        <span class="text-sm font-bold text-gray-600">Seconds</span>
                    </div>
                </div>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-orange-500/30 transition-all">Update Timer</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex items-center space-x-3">
            <div class="bg-red-100 text-red-500 p-2 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">Change Password</h3>
                <p class="text-xs text-gray-500 font-medium">Ensure your account is using a long, random password to stay secure.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.password') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:bg-white outline-none transition-all text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">New Password (Min 8 characters)</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:bg-white outline-none transition-all text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-slate-800 focus:bg-white outline-none transition-all text-sm">
                </div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection