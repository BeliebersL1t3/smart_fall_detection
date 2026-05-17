<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::updateOrCreate(
            ['email' => 'pilipbiyik@gmail.com'],
            [
                'name' => 'Caregiver Budi',
                'password' => Hash::make('password'),
            ]
        );

        Device::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'device_token' => 'TOKEN_RAHASIA_123',
                'label' => 'ESP32 Fall Sensor',
                'battery_level' => 0,
                'is_online' => false,
                'api_base_url' => null,
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'siti@smartfall.test'],
            [
                'name' => 'Caregiver Siti',
                'password' => Hash::make('password'),
            ]
        );

        Device::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'device_token' => 'TOKEN_SITI_456',
                'label' => 'ESP32 Fall Sensor',
                'battery_level' => 0,
                'is_online' => false,
            ]
        );
    }
}
