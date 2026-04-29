<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $interval = request('interval', '10m');
        $range = request('range', '-6h');

        $allowedIntervals = ['5m', '10m', '15m', '30m', '1h'];
        $allowedRanges = ['-1h', '-6h', '-12h', '-24h', '-7d'];

        if (!in_array($interval, $allowedIntervals)) $interval = '10m';
        if (!in_array($range, $allowedRanges)) $range = '-6h';

        $historicalData = [
            'labels' => [],
            'ph' => [],
            'tds' => [],
            'water_temp' => [],
            'rssi' => [],
        ];

        $url = config('services.influxdb.url');
        $token = config('services.influxdb.token');
        $org = config('services.influxdb.org');
        $bucket = config('services.influxdb.bucket');

        if (!empty($token) && !empty($bucket)) {
            try {
                $client = new \InfluxDB2\Client([
                    "url" => $url,
                    "token" => $token,
                    "org" => $org,
                ]);

                $queryApi = $client->createQueryApi();

                // Fetch dynamic range, downsampled to dynamic intervals
                $query = "
                  from(bucket: \"{$bucket}\")
                    |> range(start: {$range})
                    |> filter(fn: (r) => r[\"_measurement\"] == \"water_quality\")
                    |> filter(fn: (r) => r[\"_field\"] == \"ph\" or r[\"_field\"] == \"tds\" or r[\"_field\"] == \"water_temp\" or r[\"_field\"] == \"turbidity\" or r[\"_field\"] == \"air_temp\" or r[\"_field\"] == \"humidity\" or r[\"_field\"] == \"light\" or r[\"_field\"] == \"rssi\")
                    |> aggregateWindow(every: {$interval}, fn: mean, createEmpty: false)
                    |> yield(name: \"mean\")
                ";

                $tables = $queryApi->query($query, $org);
                $tempData = [];
                
                foreach ($tables as $table) {
                    foreach ($table->records as $record) {
                        try {
                            $timeStr = $record->getTime();
                            $field = $record->getField();
                            $val = $record->getValue();
                            
                            if ($timeStr && $field) {
                                // Keep full ISO 8601 timestamp for Chart.js time scale
                                $time = \Carbon\Carbon::parse($timeStr)->timezone('Asia/Jakarta')->toIso8601String();
                                $tempData[$time][$field] = $val;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                // Sort by time just in case
                ksort($tempData);

                foreach ($tempData as $time => $fields) {
                    $historicalData['labels'][] = $time;
                    $historicalData['ph'][] = round((float)($fields['ph'] ?? 0), 2);
                    $historicalData['tds'][] = round((float)($fields['tds'] ?? 0), 0);
                    $historicalData['water_temp'][] = round((float)($fields['water_temp'] ?? 0), 1);
                    $historicalData['turbidity'][] = round((float)($fields['turbidity'] ?? 0), 0);
                    $historicalData['air_temp'][] = round((float)($fields['air_temp'] ?? 0), 1);
                    $historicalData['humidity'][] = round((float)($fields['humidity'] ?? 0), 0);
                    $historicalData['light'][] = round((float)($fields['light'] ?? 0), 0);
                    $historicalData['rssi'][] = round((float)($fields['rssi'] ?? 0), 0);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("InfluxDB Query Error: " . $e->getMessage());
                // Silently fail, historicalData remains empty or partial
            }
        }

        $setting = \App\Models\DeviceSetting::firstOrCreate(
            ['device_id' => 'esp32_1'],
            [
                'interval_ms' => 60000,
                'min_ph' => 6.5,
                'min_tds' => 300,
                'max_turb' => 25.0
            ]
        );

        return view('dashboard', compact('historicalData', 'setting', 'interval', 'range'));
    }
}
