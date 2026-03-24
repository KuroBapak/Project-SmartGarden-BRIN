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
            
            // Using standard env config since the PHP worker runs this, not the browser
            $host = config('services.mqtt.host', '192.168.50.100');
            $port = config('services.mqtt.port', 1883); // TCP port
            $clientId = config('services.mqtt.client_id', 'LaravelCmd') . '-' . uniqid();
            $username = config('services.mqtt.username');
            $password = config('services.mqtt.password');
            
            $mqtt = new MqttClient($host, $port, $clientId);
            
            $settings = (new ConnectionSettings)
                ->setUsername($username)
                ->setPassword($password)
                ->setConnectTimeout(5);

            $mqtt->connect($settings, true);
            $mqtt->publish($topic, $message, 0);
            $mqtt->disconnect();
            
            Log::info("MQTT Publish SUCCESS to [$topic]: $message");

        } catch (\Exception $e) {
            Log::error("Failed to publish to MQTT: " . $e->getMessage(), [
                'topic' => $topic,
                'payload' => $payload
            ]);
        }
    }
}
