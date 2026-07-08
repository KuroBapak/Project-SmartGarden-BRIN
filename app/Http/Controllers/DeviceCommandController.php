<?php

namespace App\Http\Controllers;

use App\Models\DeviceSetting;
use App\Models\PlantPreset;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function updateConfig(Request $request, MqttService $mqtt)
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

        // If TCP mode, publish set_config to ESP32 via server-side MQTT
        if (config('services.mqtt.use_tcp')) {
            try {
                $cfgPayload = [
                    'action'     => 'set_config',
                    'interval'   => $validated['interval_ms'],
                    'preset'     => $request->input('preset_name', 'default'),
                    'pump_names' => [
                        'p1' => $validated['pump_names']['pump_1'],
                        'p2' => $validated['pump_names']['pump_2'],
                        'p3' => $validated['pump_names']['pump_3'],
                        'p4' => $validated['pump_names']['pump_4'],
                    ],
                    'cal' => [
                        'ph'   => [
                            'p1_ph' => (float) ($validated['calibration']['ph']['p1_ph'] ?? 4.0),
                            'p1_mv' => (float) ($validated['calibration']['ph']['p1_mv'] ?? 1500.0),
                            'p2_ph' => (float) ($validated['calibration']['ph']['p2_ph'] ?? 6.86),
                            'p2_mv' => (float) ($validated['calibration']['ph']['p2_mv'] ?? 1100.0),
                        ],
                        'tds'  => ['k' => (float) ($validated['calibration']['tds']['k'] ?? 1.0)],
                        'turb' => ['zero_v' => (float) ($validated['calibration']['turb']['zero_v'] ?? 2.1)],
                    ],
                    'rules' => collect($validated['rules'] ?? [])->map(fn($r) => [
                        's' => $r['sensor'], 'c' => $r['condition'], 'v' => (float) $r['value'],
                        'p' => (int) $r['pump'], 'pulse' => (int) $r['pulse'],
                        'stab' => (int) $r['stabilize'], 'max' => (int) $r['max_pulses'],
                        'cd' => (int) $r['cooldown'],
                    ])->toArray(),
                ];

                $topic = "brin/water/{$validated['device_id']}/down/cmd";
                $success = $mqtt->publish($topic, json_encode($cfgPayload));

                if (!$success) {
                    return redirect()->back()
                        ->with('status', 'config-saved-mqtt-failed')
                        ->with('warning', 'Config saved to DB but MQTT sync failed.');
                }
            } catch (\Exception $e) {
                Log::error('[DeviceCommand] Config publish failed', ['error' => $e->getMessage()]);
                return redirect()->back()->with('status', 'config-saved-mqtt-failed');
            }
        }

        return redirect()->back()->with('status', 'config-updated');
    }

    public function manualOverride(Request $request, MqttService $mqtt)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'target'    => 'required|in:pump_1,pump_2,pump_3,pump_4',
            'duration'  => 'required|integer|min:1000|max:300000',
        ]);

        // TCP mode: publish via server-side MQTT
        if (config('services.mqtt.use_tcp')) {
            $payload = [
                'action'   => 'manual_pump',
                'target'   => $validated['target'],
                'duration' => $validated['duration'],
            ];
            $topic = "brin/water/{$validated['device_id']}/down/cmd";
            $mqtt->publish($topic, json_encode($payload));
        }
        // WS mode: frontend handles publish via mqtt.js (unchanged)

        return response()->json([
            'status'  => 'success',
            'message' => "Manual override sent for {$validated['target']}",
        ]);
    }
}
