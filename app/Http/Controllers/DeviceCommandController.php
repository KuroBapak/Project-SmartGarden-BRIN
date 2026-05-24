<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceSetting;
use App\Models\PlantPreset;

class DeviceCommandController extends Controller
{
    public function settingsView()
    {
        $deviceId = 'esp32_1';
        $setting = DeviceSetting::firstOrCreate(
            ['device_id' => $deviceId],
            [
                'interval_ms'  => 60000,
                'pump_names'   => DeviceSetting::defaultPumpNames(),
                'rules'        => [],
                'calibration'  => DeviceSetting::defaultCalibration(),
            ]
        );

        $presets = PlantPreset::orderBy('name')->get();

        return view('settings', compact('setting', 'presets'));
    }

    public function updateConfig(Request $request)
    {
        // Parse JSON strings from Alpine.js hidden inputs
        if (is_string($request->rules)) {
            $request->merge(['rules' => json_decode($request->rules, true)]);
        }
        if (is_string($request->calibration)) {
            $request->merge(['calibration' => json_decode($request->calibration, true)]);
        }

        $validated = $request->validate([
            'device_id'   => 'required|string',
            'interval_ms' => 'required|integer|min:1000',
            // Pump names
            'pump_names'            => 'required|array',
            'pump_names.pump_1'     => 'required|string|max:50',
            'pump_names.pump_2'     => 'required|string|max:50',
            'pump_names.pump_3'     => 'required|string|max:50',
            'pump_names.pump_4'     => 'required|string|max:50',
            // Rules
            'rules'                 => 'nullable|array',
            'rules.*.sensor'        => 'required|string|in:ph,tds,turbidity,water_temp,air_temp,humidity,light',
            'rules.*.condition'     => 'required|string|in:<,>',
            'rules.*.value'         => 'required|numeric',
            'rules.*.pump'          => 'required|integer|min:1|max:4',
            'rules.*.pulse'         => 'required|integer|min:1|max:60',
            'rules.*.stabilize'     => 'required|integer|min:1|max:120',
            'rules.*.max_pulses'    => 'required|integer|min:1|max:100',
            'rules.*.cooldown'      => 'required|integer|min:0|max:3600',
            // Calibration
            'calibration'           => 'nullable|array',
        ]);

        DeviceSetting::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'interval_ms'  => $validated['interval_ms'],
                'pump_names'   => $validated['pump_names'],
                'rules'        => $validated['rules'] ?? [],
                'calibration'  => $validated['calibration'] ?? DeviceSetting::defaultCalibration(),
            ]
        );

        return redirect()->back()->with('status', 'config-updated');
    }

    public function manualOverride(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'target'    => 'required|in:pump_1,pump_2,pump_3,pump_4',
            'duration'  => 'required|integer|min:1000|max:300000',
        ]);

        // MQTT publish handled by frontend via mqtt.js WebSocket
        return response()->json([
            'status'  => 'success',
            'message' => "Manual override sent for {$validated['target']}",
        ]);
    }
}
