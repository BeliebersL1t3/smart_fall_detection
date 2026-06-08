<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Support\EnvFileWriter;
use App\Support\IotUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $device = Device::where('user_id', $user->id)->first();
        $env = app(EnvFileWriter::class);

        $mail = [
            'username' => $env->get('MAIL_USERNAME'),
            'from_address' => $env->get('MAIL_FROM_ADDRESS'),
            'from_name' => $env->get('MAIL_FROM_NAME', 'Fall Detection System'),
            'host' => $env->get('MAIL_HOST', 'smtp.gmail.com'),
            'port' => $env->get('MAIL_PORT', '465'),
            'scheme' => $env->get('MAIL_SCHEME', 'smtps'),
            'password_set' => $env->get('MAIL_PASSWORD') !== '',
        ];

        $telegram = [
            'bot_token_set' => $env->get('TELEGRAM_BOT_TOKEN') !== '',
            'chat_id' => $env->get('TELEGRAM_CHAT_ID', $user->telegram_chat_id ?? ''),
        ];

        return view('settings', compact('user', 'device', 'mail', 'telegram'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profil diperbarui. Alert darurat akan dikirim ke '.$request->email);
    }

    public function updateDevice(Request $request)
    {
        $request->validate([
            'immobility_duration' => 'required|integer|min:5|max:120',
        ]);

        $device = Device::where('user_id', Auth::id())->first();
        if ($device) {
            $device->update(['immobility_duration' => $request->immobility_duration]);
        }

        return redirect()->back()->with('success', 'Pengaturan perangkat berhasil diperbarui!');
    }

    public function updateApiBase(Request $request)
    {
        $request->validate([
            'api_base_url' => 'required|string|max:255',
        ]);

        $device = Device::where('user_id', Auth::id())->firstOrFail();
        $device->update([
            'api_base_url' => IotUrl::normalizeApiBase($request->api_base_url),
        ]);

        return redirect()->back()->with('success', 'API Base URL disimpan untuk ESP32.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->update(['password' => Hash::make($request->new_password)]);

        Auth::login($user);

        return redirect()->back()->with('success', 'Password berhasil diubah!');
    }

    public function updateTelegram(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:50',
        ]);

        $env = app(EnvFileWriter::class);
        $user = auth()->user();

        if ($request->filled('telegram_bot_token')) {
            $env->set('TELEGRAM_BOT_TOKEN', $request->telegram_bot_token);
        }

        if ($request->has('telegram_chat_id')) {
            $chatId = $request->telegram_chat_id;
            $env->set('TELEGRAM_CHAT_ID', $chatId);
            $user->update(['telegram_chat_id' => $chatId]);
        }

        Artisan::call('config:clear');

        return redirect()->back()->with('success', 'Pengaturan Telegram disimpan ke .env. Notifikasi darurat akan dikirim ke Chat ID tersebut.');
    }
}
