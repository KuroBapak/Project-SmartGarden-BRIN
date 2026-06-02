<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $interval = request('interval', '10m');
        $range = request('range', '-6h');
        $solar_interval = request('solar_interval', '10m');
        $solar_range = request('solar_range', '-6h');

        $allowedIntervals = ['5m', '10m', '15m', '30m', '1h'];
        $allowedRanges = ['-1h', '-6h', '-12h', '-24h', '-7d'];

        if (!in_array($interval, $allowedIntervals)) $interval = '10m';
        if (!in_array($range, $allowedRanges)) $range = '-6h';
        if (!in_array($solar_interval, $allowedIntervals)) $solar_interval = '10m';
        if (!in_array($solar_range, $allowedRanges)) $solar_range = '-6h';

        $historicalData = [
            'labels' => [],
            'ph' => [],
            'tds' => [],
            'water_temp' => [],
            'turbidity' => [],
            'air_temp' => [],
            'humidity' => [],
            'light' => [],
            'rssi' => [],
        ];

        $historicalSolarData = [
            'labels' => [],
            'pv_power' => [],
            'load_power' => [],
            'battery_percentage' => [],
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
                    "verifySSL" => false,
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
        
        $bucketSolar = config('services.influxdb.bucket_solar', 'solar_data');
        if (!empty($token) && !empty($bucketSolar)) {
            try {
                $client = new \InfluxDB2\Client([
                    "url" => $url,
                    "token" => $token,
                    "org" => $org,
                    "verifySSL" => false,
                ]);

                $queryApi = $client->createQueryApi();

                $querySolar = "
                  from(bucket: \"{$bucketSolar}\")
                    |> range(start: {$solar_range})
                    |> filter(fn: (r) => r[\"_measurement\"] == \"solar_panel\")
                    |> filter(fn: (r) => r[\"_field\"] == \"pv_power\" or r[\"_field\"] == \"load_power\" or r[\"_field\"] == \"battery_percentage\")
                    |> aggregateWindow(every: {$solar_interval}, fn: mean, createEmpty: false)
                    |> yield(name: \"mean\")
                ";

                $tables = $queryApi->query($querySolar, $org);
                $tempSolarData = [];
                
                foreach ($tables as $table) {
                    foreach ($table->records as $record) {
                        try {
                            $timeStr = $record->getTime();
                            $field = $record->getField();
                            $val = $record->getValue();
                            
                            if ($timeStr && $field) {
                                $time = \Carbon\Carbon::parse($timeStr)->timezone('Asia/Jakarta')->toIso8601String();
                                $tempSolarData[$time][$field] = $val;
                            }
                        } catch (\Exception $e) { continue; }
                    }
                }

                ksort($tempSolarData);

                foreach ($tempSolarData as $time => $fields) {
                    $historicalSolarData['labels'][] = $time;
                    $historicalSolarData['pv_power'][] = round((float)($fields['pv_power'] ?? 0), 1);
                    $historicalSolarData['load_power'][] = round((float)($fields['load_power'] ?? 0), 1);
                    $historicalSolarData['battery_percentage'][] = round((float)($fields['battery_percentage'] ?? 0), 0);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("InfluxDB Solar History Query Error: " . $e->getMessage());
            }
        }

        $setting = \App\Models\DeviceSetting::firstOrCreate(
            ['device_id' => 'esp32_1'],
            [
                'interval_ms'  => 60000,
                'pump_names'   => \App\Models\DeviceSetting::defaultPumpNames(),
                'rules'        => [],
                'calibration'  => \App\Models\DeviceSetting::defaultCalibration(),
            ]
        );

        return view('dashboard', compact('historicalData', 'historicalSolarData', 'setting', 'interval', 'range', 'solar_interval', 'solar_range'));
    }

    public function solarData()
    {
        $url = config('services.influxdb.url');
        $token = config('services.influxdb.token');
        $org = config('services.influxdb.org');
        $bucketSolar = config('services.influxdb.bucket_solar', 'solar_data');

        if (empty($token) || empty($bucketSolar)) {
            return response()->json(['error' => 'InfluxDB config missing'], 500);
        }

        try {
            $client = new \InfluxDB2\Client([
                "url" => $url,
                "token" => $token,
                "org" => $org,
                "verifySSL" => false,
            ]);

            $queryApi = $client->createQueryApi();

            // Fetch the last record for solar_panel from bucket_solar
            $query = "
              from(bucket: \"{$bucketSolar}\")
                |> range(start: -30d)
                |> filter(fn: (r) => r[\"_measurement\"] == \"solar_panel\")
                |> last()
            ";

            $tables = $queryApi->query($query, $org);
            $solarData = [
                'pv_voltage' => 0,
                'pv_current' => 0,
                'pv_power' => 0,
                'battery_voltage' => 0,
                'battery_percentage' => 0,
                'load_power' => 0,
                'net_power' => 0,
                'temperature' => 0,
                'updated_at' => null,
            ];

            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $field = $record->getField();
                    $val = $record->getValue();
                    if (array_key_exists($field, $solarData) && $field !== 'updated_at') {
                        $solarData[$field] = round((float)$val, 2);
                        // Take the timestamp of the latest record
                        $timeStr = $record->getTime();
                        if ($timeStr) {
                            $solarData['updated_at'] = \Carbon\Carbon::parse($timeStr)->timezone('Asia/Jakarta')->format('H:i:s');
                        }
                    }
                }
            }

            return response()->json($solarData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("InfluxDB Solar Query Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch solar data'], 500);
        }
    }

    public function sensorData()
    {
        $url = config('services.influxdb.url');
        $token = config('services.influxdb.token');
        $org = config('services.influxdb.org');
        $bucket = config('services.influxdb.bucket');

        if (empty($token) || empty($bucket)) {
            return response()->json(['error' => 'InfluxDB config missing'], 500);
        }

        try {
            $client = new \InfluxDB2\Client([
                "url" => $url,
                "token" => $token,
                "org" => $org,
                "verifySSL" => false,
            ]);

            $queryApi = $client->createQueryApi();

            // Fetch the last record for water_quality
            $query = "
              from(bucket: \"{$bucket}\")
                |> range(start: -30d)
                |> filter(fn: (r) => r[\"_measurement\"] == \"water_quality\")
                |> last()
            ";

            $tables = $queryApi->query($query, $org);
            $sensorData = [
                'ph' => 0,
                'raw_ph_mv' => null,
                'tds' => 0,
                'raw_tds_v' => null,
                'water_temp' => 0,
                'turbidity' => 0,
                'raw_turb_v' => null,
                'air_temp' => 0,
                'humidity' => 0,
                'light' => 0,
                'rssi' => 0,
                'updated_at' => null,
            ];

            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $field = $record->getField();
                    $val = $record->getValue();
                    if (array_key_exists($field, $sensorData) && $field !== 'updated_at') {
                        $sensorData[$field] = round((float)$val, 2);
                        $timeStr = $record->getTime();
                        if ($timeStr) {
                            $sensorData['updated_at'] = \Carbon\Carbon::parse($timeStr)->timezone('Asia/Jakarta')->format('H:i:s');
                        }
                    }
                }
            }

            return response()->json($sensorData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("InfluxDB Sensor Query Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sensor data'], 500);
        }
    }
}
