<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceSetting;
use App\Models\PlantPreset;
use App\Services\MqttPublishService;
use Illuminate\Support\Facades\Log;

class DeviceCommandController extends Controller
{
    protected $mqtt;

    public function __construct(MqttPublishService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

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

        $payload = [
            'action' => 'set_config',
            'interval' => (int) $validated['interval_ms'],
            'min_ph' => (float) $validated['min_ph'],
            'min_tds' => (float) $validated['min_tds'],
            'max_turb' => (float) $validated['max_turb'],
            'max_temp' => (float) $validated['max_temp'],
        ];

        $this->mqtt->sendCommand($validated['device_id'], $payload);

        return redirect()->back()->with('status', 'config-updated');
    }

    public function manualOverride(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'target' => 'required|in:ph,tds,water',
            'duration' => 'required|integer|min:1000|max:60000'
        ]);

        $payload = [
            'action' => 'manual_pump',
            'target' => $validated['target'],
            'duration' => (int) $validated['duration']
        ];

        $this->mqtt->sendCommand($validated['device_id'], $payload);

        return response()->json(['status' => 'success', 'message' => "Manual override sent for {$validated['target']}"]);
    }
}

