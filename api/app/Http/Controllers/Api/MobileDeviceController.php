<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\IotIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDeviceController extends Controller
{
    public function __construct(
        private IotIngestService $iotIngest
    ) {}

    public function status(Request $request): JsonResponse
    {
        $device = $request->user()->device;

        if (! $device) {
            return response()->json(['message' => 'Tidak ada perangkat terdaftar untuk akun ini.'], 404);
        }

        $device->syncOnlineStatus();
        $device->refresh();

        return response()->json([
            'device_id' => $device->id,
            'is_online' => (bool) $device->is_online,
            'battery' => $device->battery_level,
            'location' => $device->displayLocation(),
            'last_status' => $device->last_status,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $events = Event::query()
            ->whereHas('device', fn ($q) => $q->where('user_id', $userId))
            ->latest('occurred_at')
            ->limit(50)
            ->get()
            ->map(fn (Event $event) => $this->formatEvent($event));

        return response()->json(['events' => $events]);
    }

    public function resolveEvent(Request $request, int $eventId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:false_alarm,resolved'],
            'notes' => ['required_if:status,resolved', 'nullable', 'string', 'max:500'],
        ]);

        $event = Event::query()
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($eventId);

        $activeStatuses = ['pending', 'confirmed'];
        if (! in_array($event->status, $activeStatuses, true)) {
            return response()->json([
                'message' => 'Kejadian ini sudah diselesaikan atau dibatalkan.',
                'event' => $this->formatEvent($event),
            ], 422);
        }

        $updated = $this->iotIngest->resolveEventByCaregiver(
            $event,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Alarm berhasil dimatikan',
            'event' => $this->formatEvent($updated),
        ]);
    }

    private function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'device_id' => $event->device_id,
            'type' => $event->type,
            'type_label' => $event->type === 'manual_sos' ? 'SOS Manual' : 'Jatuh Otomatis',
            'status' => $event->status,
            'acceleration_peak' => $event->acceleration_peak,
            'notes' => $event->notes,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'resolved_at' => $event->resolved_at?->toIso8601String(),
        ];
    }
}
