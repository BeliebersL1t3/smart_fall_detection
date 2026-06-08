<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Event;
use App\Services\EmergencyNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmergencyNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public int $eventId,
        public ?int $deviceId = null,
        public ?string $location = null,
    ) {}

    public function handle(EmergencyNotifier $notifier): void
    {
        $event = Event::find($this->eventId);
        if (! $event) {
            return;
        }

        $device = $this->deviceId
            ? Device::with('user')->find($this->deviceId)
            : null;

        $notifier->sendNow($event, $device, $this->location);
    }
}
