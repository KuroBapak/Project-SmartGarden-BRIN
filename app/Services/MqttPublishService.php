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
            
            // NOTE: Backend TCP publishing over port 1883 is disabled for production.
            // Cloudflare Tunnel only natively permits HTTP/WebSockets traffic (WSS).
            // All publishes (commands/config) are now executed via the Frontend using mqtt.js over WebSockets.
            
            Log::info("Backend MQTT Publish skipped intentionally. Payload delegated to frontend WSS.", [
                'topic' => $topic,
                'payload' => $payload
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to parse mqtt payload: " . $e->getMessage());
        }
    }
}
