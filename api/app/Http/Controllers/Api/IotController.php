<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\IotIngestService;
use Illuminate\Http\Request;

class IotController extends Controller
{
    public function __construct(
        private IotIngestService $ingest
    ) {}

    public function sensorData(Request $request)
    {
        $request->validate([
            'magnitude' => 'nullable|numeric',
            'ax' => 'nullable|numeric',
            'ay' => 'nullable|numeric',
            'az' => 'nullable|numeric',
            'status' => 'nullable|string|in:normal,alarm',
            'battery' => 'nullable|integer|min:0|max:100',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'charging' => 'nullable|boolean',
        ]);

        /** @var Device $device */
        $device = $request->attributes->get('device');

        $telemetry = $this->ingest->ingestTelemetry($device, $request->only([
            'magnitude', 'ax', 'ay', 'az', 'status', 'battery', 'battery_level', 'charging',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Telemetry received',
            'telemetry' => $telemetry,
        ]);
    }

    public function fallDetected(Request $request)
    {
        $request->validate([
            'magnitude' => 'nullable|numeric',
            'message' => 'nullable|string|max:255',
        ]);

        /** @var Device $device */
        $device = $request->attributes->get('device');

        $event = $this->ingest->ingestFall(
            $device,
            $request->input('magnitude')
        );

        return response()->json([
            'success' => true,
            'message' => 'Fall event recorded',
            'event_id' => $event->id,
            'status' => $event->status,
        ], 201);
    }

    public function sos(Request $request)
    {
        $request->validate([
            'event' => 'nullable|string|in:sos_active,sos_cancelled',
            'message' => 'nullable|string|max:255',
        ]);

        /** @var Device $device */
        $device = $request->attributes->get('device');

        $eventName = $request->input('event', 'sos_active');
        $active = $eventName !== 'sos_cancelled';

        $event = $this->ingest->ingestSos($device, $active, $request->input('message'));

        return response()->json([
            'success' => true,
            'message' => $active ? 'SOS alert recorded' : 'SOS cancelled',
            'event_id' => $event?->id,
            'status' => $event?->status ?? 'cancelled_by_user',
        ], $active ? 201 : 200);
    }
}
