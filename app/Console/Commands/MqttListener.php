<?php

namespace App\Console\Commands;

use App\Models\DeviceSetting;
use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Subscribe to MQTT telemetry and write calculated values to InfluxDB (replaces EMQX Rule Engine)';

    /** @var array<string, array{setting: DeviceSetting|null, loaded_at: int}> */
    private array $settingsCache = [];

    private const CACHE_TTL_SECONDS = 60;

    public function handle(MqttService $mqtt): int
    {
        $this->info('[MqttListener] Starting MQTT → InfluxDB bridge...');

        $maxRetryDelay = 30;
        $retryDelay = 1;

        while (true) {
            try {
                $client = $mqtt->createSubscriber();
                $this->info('[MqttListener] Connected to broker');
                $retryDelay = 1; // Reset on successful connect

                // Write heartbeat on connect
                Cache::put('mqtt_listener_heartbeat', now()->toISOString(), 120);

                $client->subscribe('brin/water/+/up/telemetry', function (string $topic, string $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                // Loop forever — processes incoming messages
                $client->loop(true);

            } catch (\Exception $e) {
                $this->error("[MqttListener] Connection lost: {$e->getMessage()}");
                Log::error('[MqttListener] Connection lost', ['error' => $e->getMessage()]);

                Cache::forget('mqtt_listener_heartbeat');

                // Exponential backoff
                $this->warn("[MqttListener] Reconnecting in {$retryDelay}s...");
                sleep($retryDelay);
                $retryDelay = min($retryDelay * 2, $maxRetryDelay);
            }
        }

        return self::SUCCESS; // @phpstan-ignore-line (unreachable but required)
    }

    private function processMessage(string $topic, string $message): void
    {
        try {
            // Update heartbeat
            Cache::put('mqtt_listener_heartbeat', now()->toISOString(), 120);

            // Parse JSON
            $data = json_decode($message, true);
            if (!is_array($data)) {
                Log::warning('[MqttListener] Invalid JSON received', ['topic' => $topic]);
                return;
            }

            // Extract device_id from topic: brin/water/{device_id}/up/telemetry
            $parts = explode('/', $topic);
            $deviceId = $parts[2] ?? null;
            if (!$deviceId) {
                Log::warning('[MqttListener] Could not extract device_id from topic', ['topic' => $topic]);
                return;
            }

            // Validate required raw fields
            $requiredFields = ['raw_ph_mv', 'raw_tds_v', 'raw_turb_v'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    Log::warning("[MqttListener] Missing field: {$field}", ['device_id' => $deviceId]);
                    return;
                }
            }

            // Load DeviceSetting (cached, refreshed every 60s)
            $setting = $this->getCachedSetting($deviceId);

            // Calculate calibrated values
            $calibrated = $this->calculateCalibratedValues($data, $setting);

            // Derive mode from pump states
            $mode = 'normal';
            for ($i = 1; $i <= 4; $i++) {
                if (($data["pump_{$i}"] ?? 0) == 1) {
                    $mode = 'correction';
                    break;
                }
            }

            // Write to InfluxDB
            $this->writeToInfluxDB($deviceId, $data, $calibrated, $mode);

            $this->line("[MqttListener] Written data for {$deviceId} (pH={$calibrated['ph']}, TDS={$calibrated['tds']}, Turb={$calibrated['turbidity']})");

        } catch (\Exception $e) {
            Log::error('[MqttListener] Error processing message', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getCachedSetting(string $deviceId): ?DeviceSetting
    {
        $cached = $this->settingsCache[$deviceId] ?? null;

        if ($cached && (time() - $cached['loaded_at']) < self::CACHE_TTL_SECONDS) {
            return $cached['setting'];
        }

        $setting = DeviceSetting::where('device_id', $deviceId)->first();
        $this->settingsCache[$deviceId] = [
            'setting'   => $setting,
            'loaded_at' => time(),
        ];

        return $setting;
    }

    /**
     * Replicate the Arduino calibration formulas exactly.
     * @see arduino_code.ino lines 625-650
     */
    private function calculateCalibratedValues(array $data, ?DeviceSetting $setting): array
    {
        // Get calibration params (or defaults)
        $cal = $setting?->calibration ?? DeviceSetting::defaultCalibration();

        $rawPhMv    = (float) $data['raw_ph_mv'];
        $rawTdsV    = (float) $data['raw_tds_v'];
        $rawTurbV   = (float) $data['raw_turb_v'];
        $waterTemp  = (float) ($data['water_temp'] ?? 25.0);

        // ── pH: two-point slope + temperature compensation ──
        $p1Ph = (float) ($cal['ph']['p1_ph'] ?? 4.0);
        $p1Mv = (float) ($cal['ph']['p1_mv'] ?? 1500.0);
        $p2Ph = (float) ($cal['ph']['p2_ph'] ?? 6.86);
        $p2Mv = (float) ($cal['ph']['p2_mv'] ?? 1100.0);

        $denominator = $p1Mv - $p2Mv;
        if (abs($denominator) < 0.001) {
            $denominator = 1.0; // Prevent division by zero
        }

        $baseSlope = ($p1Ph - $p2Ph) / $denominator;
        $tempRatio = 298.15 / ($waterTemp + 273.15);
        $compensatedSlope = $baseSlope * $tempRatio;
        $ph = $p1Ph + $compensatedSlope * ($rawPhMv - $p1Mv);
        $ph = max(0.0, min(14.0, round($ph, 2)));

        // ── TDS: polynomial + temperature compensation + cal_k ──
        $calTdsK = (float) ($cal['tds']['k'] ?? 1.0);

        $compensationCoefficient = 1.0 + 0.02 * ($waterTemp - 25.0);
        if ($compensationCoefficient <= 0.0) {
            $compensationCoefficient = 1.0;
        }

        $compensationVoltage = $rawTdsV / $compensationCoefficient;
        $rawTds = (133.42 * pow($compensationVoltage, 3)
                 - 255.86 * pow($compensationVoltage, 2)
                 + 857.39 * $compensationVoltage) * 0.417;
        $tds = max(0.0, round($rawTds * $calTdsK, 2));

        // ── Turbidity: polynomial + zero_v offset ──
        $calTurbZeroV = (float) ($cal['turb']['zero_v'] ?? 2.1);

        $turbVoltage = $rawTurbV + ($calTurbZeroV - 2.1 + 0.928);
        $rawTurb = -1120.4 * pow($turbVoltage, 2) + 5742.3 * $turbVoltage - 4352.9;
        $turbidity = max(0.0, round($rawTurb, 2));

        return [
            'ph'        => $ph,
            'tds'       => $tds,
            'turbidity' => $turbidity,
        ];
    }

    private function writeToInfluxDB(string $deviceId, array $data, array $calibrated, string $mode): void
    {
        $url    = config('services.influxdb.url');
        $token  = config('services.influxdb.token');
        $org    = config('services.influxdb.org');
        $bucket = config('services.influxdb.bucket');
        $bucketSolar = config('services.influxdb.bucket_solar');

        if (empty($token) || empty($bucket)) {
            Log::warning('[MqttListener] InfluxDB not configured, skipping write');
            return;
        }

        $client = new InfluxClient([
            'url'       => $url,
            'token'     => $token,
            'org'       => $org,
            'verifySSL' => false,
        ]);

        $writeApi = $client->createWriteApi();

        // ── Water quality point ──
        $waterPoint = Point::measurement('water_quality')
            ->addTag('device_id', $deviceId)
            ->addField('ph', $calibrated['ph'])
            ->addField('tds', $calibrated['tds'])
            ->addField('turbidity', $calibrated['turbidity'])
            ->addField('water_temp', (float) ($data['water_temp'] ?? 0))
            ->addField('air_temp', (float) ($data['air_temp'] ?? 0))
            ->addField('humidity', (float) ($data['humidity'] ?? 0))
            ->addField('light', (float) ($data['light'] ?? 0))
            ->addField('rssi', (int) ($data['rssi'] ?? 0))
            ->addField('raw_ph_mv', (float) $data['raw_ph_mv'])
            ->addField('raw_tds_v', (float) $data['raw_tds_v'])
            ->addField('raw_turb_v', (float) $data['raw_turb_v'])
            ->addField('pump_1', (int) ($data['pump_1'] ?? 0))
            ->addField('pump_2', (int) ($data['pump_2'] ?? 0))
            ->addField('pump_3', (int) ($data['pump_3'] ?? 0))
            ->addField('pump_4', (int) ($data['pump_4'] ?? 0))
            ->addField('mode', $mode)
            ->time(time(), WritePrecision::S);

        $writeApi->write($waterPoint, WritePrecision::S, $bucket, $org);

        // ── Solar point (if solar data present) ──
        if (!empty($bucketSolar) && isset($data['pv_voltage'])) {
            $solarPoint = Point::measurement('solar_panel')
                ->addTag('device_id', $deviceId)
                ->addField('pv_voltage', (float) ($data['pv_voltage'] ?? 0))
                ->addField('pv_current', (float) ($data['pv_current'] ?? 0))
                ->addField('pv_power', (float) ($data['pv_power'] ?? 0))
                ->addField('battery_voltage', (float) ($data['battery_voltage'] ?? 0))
                ->addField('battery_percentage', (float) ($data['battery_percentage'] ?? 0))
                ->addField('load_power', (float) ($data['load_power'] ?? 0))
                ->addField('net_power', (float) ($data['net_power'] ?? 0))
                ->time(time(), WritePrecision::S);

            $writeApi->write($solarPoint, WritePrecision::S, $bucketSolar, $org);
        }

        $writeApi->close();
        $client->close();
    }
}
