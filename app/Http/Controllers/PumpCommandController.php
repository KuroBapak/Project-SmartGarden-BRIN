<?php

namespace App\Http\Controllers;

use App\Services\MqttService;
use Illuminate\Http\Request;

class PumpCommandController extends Controller
{
    /**
     * Dispatch a manual pump command via server-side MQTT TCP.
     * Used when MQTT_USE_TCP=true — dashboard fetch() hits this route.
     */
    public function dispatch(Request $request, string $deviceId, MqttService $mqtt)
    {
        // Validate device_id format (prevent MQTT topic injection)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $deviceId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid device'], 422);
        }

        $validated = $request->validate([
            'action'   => 'required|in:manual_pump',
            'target'   => 'required|in:pump_1,pump_2,pump_3,pump_4',
            'duration' => 'required|integer|min:0|max:7200000',
        ]);

        $topic = "brin/water/{$deviceId}/down/cmd";
        $success = $mqtt->publish($topic, json_encode($validated));

        return response()->json([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Command sent' : 'MQTT publish failed',
        ], $success ? 200 : 503);
    }
}
