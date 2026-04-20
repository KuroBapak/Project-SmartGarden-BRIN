<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Settings & Manual Override') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Validation Errors or Success Messages -->
            @if (session('status') === 'config-updated')
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                    <span class="font-medium">Success!</span> Configuration saved and sent to device.
                </div>
            @endif
            @if ($errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Configuration Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Automation Parameters</h3>
                        <form method="POST" action="{{ route('settings.config') }}" id="configForm">
                            @csrf
                            <input type="hidden" name="device_id" value="{{ $setting->device_id }}">

                            <div class="mb-4">
                                <label for="interval_ms" class="block font-medium text-sm text-gray-700">Telemetry Interval (ms)</label>
                                <input id="interval_ms" type="number" name="interval_ms" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('interval_ms', $setting->interval_ms) }}" required min="1000">
                                <p class="text-xs text-gray-500 mt-1">Example: 60000 = 60 seconds</p>
                            </div>

                            <div class="mb-4">
                                <label for="min_ph" class="block font-medium text-sm text-gray-700">Minimum pH Target</label>
                                <input id="min_ph" type="number" step="0.1" name="min_ph" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('min_ph', $setting->min_ph) }}" required>
                            </div>

                            <div class="mb-4">
                                <label for="min_tds" class="block font-medium text-sm text-gray-700">Minimum TDS Target (ppm)</label>
                                <input id="min_tds" type="number" step="1" name="min_tds" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('min_tds', $setting->min_tds) }}" required>
                            </div>

                            <div class="mb-4">
                                <label for="max_turb" class="block font-medium text-sm text-gray-700">Maximum Turbidity</label>
                                <input id="max_turb" type="number" step="0.1" name="max_turb" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('max_turb', $setting->max_turb) }}" required>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button type="submit" id="saveConfigBtn" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 disabled:opacity-50 transition ease-in-out duration-150">
                                    Save & Sync Config
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Manual Override Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Manual Pump Override</h3>
                        <p class="text-sm text-gray-600 mb-6">Manually trigger a pump for a specific duration. This will temporarily override the edge automation logic on the ESP32.</p>
                        
                        <div class="space-y-4">
                            <!-- pH Pump -->
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">pH Pump (Acid/Base)</h4>
                                    <p class="text-xs text-gray-500">Injects pH buffer</p>
                                </div>
                                <button onclick="triggerPump('ph', 5000)" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-bold shadow transition">
                                    Trigger 5s
                                </button>
                            </div>

                            <!-- TDS Pump -->
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">TDS Pump (Fertilizer)</h4>
                                    <p class="text-xs text-gray-500">Injects Nutrients (AB Mix)</p>
                                </div>
                                <button onclick="triggerPump('tds', 5000)" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm font-bold shadow transition">
                                    Trigger 5s
                                </button>
                            </div>

                            <!-- Water Pump -->
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">Water Pump</h4>
                                    <p class="text-xs text-gray-500">Circulation / Fresh Water</p>
                                </div>
                                <button onclick="triggerPump('water', 10000)" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-bold shadow transition">
                                    Trigger 10s
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mqttOptions = {
                keepalive: 60,
                clientId: '{{ config("services.mqtt.client_id") }}' + '-cmd-' + Math.random().toString(16).substr(2, 6),
                protocolId: 'MQTT',
                protocolVersion: 4,
                clean: true,
                reconnectPeriod: 1000,
                connectTimeout: 30 * 1000,
                username: '{{ config("services.mqtt.username") }}',
                password: '{{ config("services.mqtt.password") }}',
            };

            // MQTT WebSocket broker URL (controlled via .env)
            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttWsPort = '{{ config("services.mqtt.ws_port") }}';
            const brokerUrl = mqttWsPort
                ? `ws://${mqttHost}:${mqttWsPort}/mqtt`
                : `wss://${mqttHost}/mqtt`;
            
            const client = mqtt.connect(brokerUrl, mqttOptions);
            const pubTopic = `brin/water/{{ $setting->device_id }}/down/cmd`;

            client.on('connect', function () {
                console.log('Connected to MQTT via WebSockets for Commands!');
            });

            // Handle Manual Pump Trigger locally via WS
            window.triggerPump = function(targetPump, durationMs) {
                if (!client.connected) {
                    alert("MQTT Not Connected! Menunggu koneksi WebSocket...");
                    return;
                }
                const payload = {
                    action: 'manual_pump',
                    target: targetPump,
                    duration: durationMs
                };
                client.publish(pubTopic, JSON.stringify(payload), {qos: 0}, function(err) {
                    if(!err) {
                        alert(`Command terkirim via WebSockets ke ${targetPump} (${durationMs}ms)`);
                    } else {
                        alert(`Gagal kirim via WebSockets`);
                    }
                });
            };

            // Intercept Config Form to push MQTT before standard POST save
            const configForm = document.getElementById('configForm');
            configForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Stop default submit
                
                if (!client.connected) {
                    alert("MQTT Not Connected! Menunggu koneksi WebSocket...");
                    return;
                }

                document.getElementById('saveConfigBtn').disabled = true;
                document.getElementById('saveConfigBtn').innerText = 'Syncing...';

                const cfgPayload = {
                    action: 'set_config',
                    interval: parseInt(document.getElementById('interval_ms').value),
                    min_ph: parseFloat(document.getElementById('min_ph').value),
                    min_tds: parseFloat(document.getElementById('min_tds').value),
                    max_turb: parseFloat(document.getElementById('max_turb').value),
                };

                client.publish(pubTopic, JSON.stringify(cfgPayload), {qos: 0}, function(err) {
                    if(!err) {
                        console.log('Config synced via WebSockets. Saving to DB...');
                        configForm.submit(); // Continue the standard framework POST
                    } else {
                        alert('Sync gagal via WebSockets!');
                        document.getElementById('saveConfigBtn').disabled = false;
                        document.getElementById('saveConfigBtn').innerText = 'Save & Sync Config';
                    }
                });
            });
        });
    </script>
</x-app-layout>
