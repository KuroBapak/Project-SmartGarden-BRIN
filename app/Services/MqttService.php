<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the server-side TCP host (MQTT_LISTENER_HOST).
     */
    private function getHost(): string
    {
        return $this->config['listener_host'] ?? $this->config['host'] ?? '127.0.0.1';
    }

    /**
     * Get the TCP port.
     */
    private function getPort(): int
    {
        return (int) ($this->config['tcp_port'] ?? 1883);
    }

    /**
     * Build connection settings with auth credentials.
     */
    private function buildConnectionSettings(): ConnectionSettings
    {
        $settings = (new ConnectionSettings())
            ->setConnectTimeout(5)
            ->setKeepAliveInterval(60);

        if (!empty($this->config['username'])) {
            $settings = $settings->setUsername($this->config['username']);
        }
        if (!empty($this->config['password'])) {
            $settings = $settings->setPassword($this->config['password']);
        }

        if (!empty($this->config['use_tls']) && $this->config['use_tls'] === true) {
            $settings = $settings->setUseTls(true)
                                 ->setTlsVerifyPeer(false)
                                 ->setTlsVerifyPeerName(false);

            if (!empty($this->config['tls_ca_file'])) {
                $settings = $settings->setTlsCertificateAuthorityFile($this->config['tls_ca_file']);
            }
            if (!empty($this->config['tls_client_cert_file'])) {
                $settings = $settings->setTlsClientCertificateFile($this->config['tls_client_cert_file']);
            }
            if (!empty($this->config['tls_client_key_file'])) {
                $settings = $settings->setTlsClientCertificateKeyFile($this->config['tls_client_key_file']);
            }
        }

        return $settings;
    }

    /**
     * Publish a message via a short-lived TCP connection.
     * Opens → authenticates → publishes → disconnects.
     * Never crashes the HTTP request — logs failures and returns false.
     */
    public function publish(string $topic, string $payload, int $qos = 0): bool
    {
        try {
            $clientId = ($this->config['client_id'] ?? 'Laravel') . '-pub-' . substr(md5(uniqid()), 0, 6);

            $client = new MqttClient(
                $this->getHost(),
                $this->getPort(),
                $clientId
            );

            $client->connect($this->buildConnectionSettings(), true);
            $client->publish($topic, $payload, $qos);
            $client->disconnect();

            Log::debug("[MqttService] Published to {$topic}", ['payload_length' => strlen($payload)]);

            return true;
        } catch (\Exception $e) {
            Log::error('[MqttService] Publish failed', [
                'topic' => $topic,
                'host'  => $this->getHost(),
                'port'  => $this->getPort(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a persistent subscriber connection for MqttListener.
     * Uses a fixed client ID to prevent duplicate subscriptions.
     */
    public function createSubscriber(): MqttClient
    {
        $clientId = ($this->config['client_id'] ?? 'Laravel') . '-listener';

        $client = new MqttClient(
            $this->getHost(),
            $this->getPort(),
            $clientId
        );

        $client->connect($this->buildConnectionSettings(), true);

        return $client;
    }
}
