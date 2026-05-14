<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceSetting;
use App\Models\PlantPreset;

class DeviceCommandController extends Controller
{


    public function settingsView()
    {
        // For currently, assume single master device view.
        $deviceId = 'esp32_1';
        $setting = DeviceSetting::firstOrCreate(
            ['device_id' => $deviceId],
            [
                'interval_ms' => 60000,
                'min_ph' => 6.5,
                'min_tds' => 300.0,
                'max_turb' => 25.0,
                'max_temp' => 30.0,
            ]
        );

        $presets = PlantPreset::orderBy('name')->get();

        return view('settings', compact('setting', 'presets'));
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'interval_ms' => 'required|integer|min:1000',
            'min_ph' => 'required|numeric',
            'min_tds' => 'required|numeric',
            'max_turb' => 'required|numeric',
            'max_temp' => 'required|numeric',
        ]);

        DeviceSetting::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'interval_ms' => $validated['interval_ms'],
                'min_ph' => $validated['min_ph'],
                'min_tds' => $validated['min_tds'],
                'max_turb' => $validated['max_turb'],
                'max_temp' => $validated['max_temp'],
            ]
        );

        // NOTE: MQTT publish is handled by the frontend via mqtt.js over WebSocket.
        // See resources/views/settings.blade.php for the actual MQTT publish logic.

        return redirect()->back()->with('status', 'config-updated');
    }

    public function manualOverride(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'target' => 'required|in:ph,tds,water',
            'duration' => 'required|integer|min:1000|max:60000'
        ]);

        // NOTE: MQTT publish is handled by the frontend via mqtt.js over WebSocket.
        // See resources/views/dashboard.blade.php for the actual MQTT publish logic.

        return response()->json(['status' => 'success', 'message' => "Manual override sent for {$validated['target']}"]);
    }
}

