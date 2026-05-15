<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;

class SettingsController extends Controller
{
    /**
     * Menampilkan halaman settings
     */
    public function index()
    {
        $user = Auth::user();
        $device = Device::where('user_id', $user->id)->first();

        return view('settings', compact('user', 'device'));
    }
/**
     * Memperbarui Nama dan Email User
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validasi input, pastikan email unik kecuali untuk milik user itu sendiri
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        // Simpan perubahan ke database
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Memperbarui Durasi False Alarm (Immobility Duration)
     */
    public function updateDevice(Request $request)
    {
        $request->validate([
            'immobility_duration' => 'required|integer|min:5|max:120', // Batasi 5 detik sampai 2 menit
        ]);

        $device = Device::where('user_id', Auth::id())->first();
        if ($device) {
            $device->update(['immobility_duration' => $request->immobility_duration]);
        }

        return redirect()->back()->with('success', 'Pengaturan perangkat berhasil diperbarui!');
    }

    /**
     * Memperbarui Password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->update(['password' => Hash::make($request->new_password)]);

        // 🌟 Tambahkan baris ini agar user TIDAK ter-logout setelah ganti password
        Auth::login($user);

        return redirect()->back()->with('success', 'Password berhasil diubah!');
    }
}