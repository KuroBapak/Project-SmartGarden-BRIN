<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class MqttPublishService
{
    /**
     * Publishes a JSON payload to a specific device's command topic.
     * Direct connection mimicking the proven old architecture pattern.
     */
    public function sendCommand($deviceId, array $payload)
    {
        $topic = "brin/water/{$deviceId}/down/cmd";
        
        try {
            $message = json_encode($payload);
            
            $host = config('services.mqtt.host');
            $port = config('services.mqtt.port', 1883); // TCP port

            $clientId = config('services.mqtt.client_id', 'LaravelCmd') . '-' . uniqid();
            $username = config('services.mqtt.username');
            $password = config('services.mqtt.password');
            
            $settings = (new ConnectionSettings)
                ->setUsername($username)
                ->setPassword($password)
                ->setConnectTimeout(3); // Short timeout for faster fallback

            try {
                // 1. Try Primary Host Connection
                $mqtt = new MqttClient($host, $port, $clientId);
                $mqtt->connect($settings, true);
            } catch (\Exception $e) {
                Log::warning("Primary MQTT Connection Failed ({$host}). Attempting fallback to mqtt.kurobapak.site.", ['error' => $e->getMessage()]);
                
                // 2. Try Fallback Public Tunnel
                if ($host !== 'mqtt.kurobapak.site') {
                    $fallbackHost = 'mqtt.kurobapak.site';
                    $fallbackPort = 1883;
                    $mqtt = new MqttClient($fallbackHost, $fallbackPort, $clientId);
                    $mqtt->connect($settings, true);
                    $host = $fallbackHost; // Update host variable for success log
                } else {
                    throw $e; // Re-throw if the primary WAS the fallback
                }
            }
            
            $mqtt->publish($topic, $message, 0);
            $mqtt->disconnect();
            
            Log::info("MQTT Publish SUCCESS via [{$host}] to [$topic]: $message");

        } catch (\Exception $e) {
            Log::error("Failed to publish to MQTT: " . $e->getMessage(), [
                'topic' => $topic,
                'payload' => $payload
            ]);
        }
    }
}
