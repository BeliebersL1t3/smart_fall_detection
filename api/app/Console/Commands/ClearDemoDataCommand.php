<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Event;
use Illuminate\Console\Command;

class ClearDemoDataCommand extends Command
{
    protected $signature = 'careguard:clear-demo';

    protected $description = 'Hapus event dummy dan reset status device ke kondisi menunggu data ESP32';

    public function handle(): int
    {
        $deleted = Event::query()->delete();

        Device::query()->update([
            'is_online' => false,
            'battery_level' => 0,
            'last_magnitude' => null,
            'last_ax' => null,
            'last_ay' => null,
            'last_az' => null,
            'last_status' => 'normal',
            'last_seen_at' => null,
        ]);

        $this->info("Selesai: {$deleted} event dihapus. Device direset — siap terima data ESP32.");

        return self::SUCCESS;
    }
}
