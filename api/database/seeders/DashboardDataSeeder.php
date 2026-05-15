<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Device;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // AKUN 1
        // ==========================================
        $user1 = User::create([
            'name' => 'Caregiver Budi',
            'email' => 'pilipbiyik@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $device1 = Device::create([
            'user_id' => $user1->id,
            'device_token' => 'TOKEN_RAHASIA_123',
            'label' => 'Living Room Sensor',
            'battery_level' => 85,
            'is_online' => true,
        ]);

        Event::create([
            'device_id' => $device1->id,
            'type' => 'auto_fall',
            'status' => 'confirmed',
            'acceleration_peak' => 3.20,
            'occurred_at' => Carbon::now()->subMinutes(10),
        ]);

        Event::create([
            'device_id' => $device1->id,
            'type' => 'manual_sos',
            'status' => 'resolved_by_caregiver',
            'acceleration_peak' => null,
            'occurred_at' => Carbon::now()->subHours(2),
        ]);

        // ==========================================
        // AKUN 2
        // ==========================================
        $user2 = User::create([
            'name' => 'Caregiver Siti',
            'email' => 'siti@smartfall.test',
            'password' => Hash::make('password'),
        ]);

        $device2 = Device::create([
            'user_id' => $user2->id,
            'device_token' => 'TOKEN_SITI_456',
            'label' => 'Bedroom Sensor',
            'battery_level' => 40,
            'is_online' => false,
        ]);

        Event::create([
            'device_id' => $device2->id,
            'type' => 'auto_fall',
            'status' => 'pending',
            'acceleration_peak' => 2.50,
            'occurred_at' => Carbon::now(),
        ]);
    }
}