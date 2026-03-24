<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <!-- Custom Animations for Pump Status -->
    <style>
        @keyframes siren-pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pump-active-card {
            animation: siren-pulse 1.5s infinite;
            background-color: #fef2f2 !important; /* red-50 */
            border-color: #ef4444 !important; /* red-500 */
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Realtime Sensor Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                    <div class="text-sm font-medium text-gray-500">Water Temp</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900" id="val_water_temp">-- °C</div>
                </div>
                <div id="card_ph" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500 transition-all duration-500 relative">
                    <div class="flex justify-between items-start">
                        <div class="text-sm font-medium text-gray-500">pH Level</div>
                        <svg id="icon_pump_ph" class="w-5 h-5 text-red-500 opacity-0 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" id="val_ph">--</div>
                    <div id="status_ph" class="text-xs text-gray-500 mt-2 flex items-center">Target Min: {{ $setting->min_ph }}</div>
                </div>
                
                <div id="card_tds" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500 transition-all duration-500 relative">
                    <div class="flex justify-between items-start">
                        <div class="text-sm font-medium text-gray-500">TDS (Nutrisi)</div>
                        <svg id="icon_pump_tds" class="w-5 h-5 text-red-500 opacity-0 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" id="val_tds">-- ppm</div>
                    <div id="status_tds" class="text-xs text-gray-500 mt-2 flex items-center">Target Min: {{ $setting->min_tds }} p</div>
                </div>
                
                <div id="card_turbidity" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-orange-500 transition-all duration-500 relative">
                    <div class="flex justify-between items-start">
                        <div class="text-sm font-medium text-gray-500">Turbidity</div>
                        <svg id="icon_pump_turbidity" class="w-5 h-5 text-red-500 opacity-0 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" id="val_turbidity">--</div>
                    <div id="status_turbidity" class="text-xs text-gray-500 mt-2 flex items-center">Target Max: {{ $setting->max_turb }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                    <div class="text-sm font-medium text-gray-500">Air Temp</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900" id="val_air_temp">-- °C</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-cyan-500">
                    <div class="text-sm font-medium text-gray-500">Humidity</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900" id="val_humidity">-- %</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                    <div class="text-sm font-medium text-gray-500">Light Level</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900" id="val_light">-- %</div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-500 flex flex-col justify-center items-center">
                    <div class="text-sm font-medium text-gray-500 mb-2">MQTT Status</div>
                    <div id="mqtt_status" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">Connecting...</div>
                </div>
            </div>

            <!-- Historical Data Chart -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historical Data Trends</h3>
                    <div class="relative h-96 w-full">
                        <canvas id="historicalChart"></canvas>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Initialize Chart.js with dummy historical data
            const ctx = document.getElementById('historicalChart').getContext('2d');
            const rawData = @json($historicalData);
            
            const chartData = {
                labels: rawData.labels,
                datasets: [
                    {
                        label: 'Water Temp (°C)',
                        data: rawData.water_temp,
                        borderColor: 'rgb(59, 130, 246)', // blue
                        tension: 0.3
                    },
                    {
                        label: 'Air Temp (°C)',
                        data: rawData.air_temp,
                        borderColor: 'rgb(239, 68, 68)', // red
                        tension: 0.3
                    },
                    {
                        label: 'pH',
                        data: rawData.ph,
                        borderColor: 'rgb(34, 197, 94)', // green
                        tension: 0.3
                    },
                    {
                        label: 'Humidity (%)',
                        data: rawData.humidity,
                        borderColor: 'rgb(6, 182, 212)', // cyan
                        tension: 0.3,
                        hidden: true // hide % scale to keep temp/ph clear
                    },
                    {
                        label: 'Light Level (%)',
                        data: rawData.light,
                        borderColor: 'rgb(168, 85, 247)', // purple
                        tension: 0.3,
                        hidden: true
                    },
                    {
                        label: 'TDS (ppm)',
                        data: rawData.tds,
                        borderColor: 'rgb(234, 179, 8)', // yellow
                        tension: 0.3,
                        hidden: true // hidden by default to keep scale readable
                    },
                    {
                        label: 'Turbidity',
                        data: rawData.turbidity,
                        borderColor: 'rgb(249, 115, 22)', // orange
                        tension: 0.3,
                        hidden: true
                    }
                ]
            };

            const myChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });

            // 2. Initialize MQTT over WebSockets
            const mqttOptions = {
                keepalive: 60,
                clientId: '{{ config("services.mqtt.client_id") }}' + '-' + Math.random().toString(16).substr(2, 6),
                protocolId: 'MQTT',
                protocolVersion: 4,
                clean: true,
                reconnectPeriod: 1000,
                connectTimeout: 30 * 1000,
                username: '{{ config("services.mqtt.username") }}',
                password: '{{ config("services.mqtt.password") }}',
            };

            // Determine connection protocol: wss (Secure) for Cloudflare/HTTPS, ws for local HTTP
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            
            // PENTING: Javascript Frontend BUKAN konek ke Port 1883 (TCP), tapi WAJIB Port 8083 (WebSocket)
            // Pakai standar ENV lu MQTT_HOST
            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttPort = window.location.protocol === 'https:' ? '' : ':{{ config("services.mqtt.ws_port") }}';
            
            const brokerUrl = `${protocol}//${mqttHost}${mqttPort}/mqtt`;
            
            const mqttStatus = document.getElementById('mqtt_status');
            
            console.log('Connecting to MQTT broker...', brokerUrl);
            const client = mqtt.connect(brokerUrl, mqttOptions);

            client.on('connect', function () {
                console.log('Connected to MQTT Broker!');
                mqttStatus.textContent = 'Connected';
                mqttStatus.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold';
                
                // Subscribe to the device topic
                client.subscribe('brin/water/+/up/telemetry', function (err) {
                    if (!err) {
                        console.log('Subscribed to telemetry topic');
                    }
                });
            });

            client.on('error', function (error) {
                console.error('MQTT Error:', error);
                mqttStatus.textContent = 'Error';
                mqttStatus.className = 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold';
            });

            client.on('offline', function () {
                mqttStatus.textContent = 'Offline';
                mqttStatus.className = 'px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-bold';
            });

            client.on('message', function (topic, message) {
                // message is Buffer
                try {
                    const payload = JSON.parse(message.toString());
                    console.log('Received payload:', payload);

                    const targets = {
                        ph: {{ $setting->min_ph }},
                        tds: {{ $setting->min_tds }},
                        turb: {{ $setting->max_turb }}
                    };

                    // Update UI elements dynamically based on JSON payload
                    if (payload.water_temp !== undefined) {
                        document.getElementById('val_water_temp').textContent = parseFloat(payload.water_temp).toFixed(1) + ' °C';
                    }
                    if (payload.ph !== undefined) {
                        let phVal = parseFloat(payload.ph);
                        document.getElementById('val_ph').textContent = phVal.toFixed(2);
                        let card = document.getElementById('card_ph');
                        let status = document.getElementById('status_ph');
                        let icon = document.getElementById('icon_pump_ph');
                        
                        if (phVal < targets.ph) {
                            card.classList.add('pump-active-card');
                            card.classList.remove('border-green-500');
                            icon.classList.remove('opacity-0');
                            icon.classList.add('opacity-100', 'animate-spin');
                            status.innerHTML = `<span class="relative flex h-2 w-2 mr-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span> ⚠️ Pompa pH Aktif`;
                            status.className = 'text-xs text-red-600 font-bold mt-2 flex items-center';
                        } else {
                            card.classList.remove('pump-active-card');
                            card.classList.add('border-green-500');
                            icon.classList.add('opacity-0');
                            icon.classList.remove('opacity-100', 'animate-spin');
                            status.innerHTML = `Target Min: ${targets.ph}`;
                            status.className = 'text-xs text-gray-500 mt-2 flex items-center';
                        }
                    }
                    if (payload.tds !== undefined) {
                        let tdsVal = parseInt(payload.tds);
                        document.getElementById('val_tds').textContent = tdsVal + ' ppm';
                        let card = document.getElementById('card_tds');
                        let status = document.getElementById('status_tds');
                        let icon = document.getElementById('icon_pump_tds');
                        
                        if (tdsVal < targets.tds) {
                            card.classList.add('pump-active-card');
                            card.classList.remove('border-yellow-500');
                            icon.classList.remove('opacity-0');
                            icon.classList.add('opacity-100', 'animate-spin');
                            status.innerHTML = `<span class="relative flex h-2 w-2 mr-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span> ⚠️ Pompa TDS Aktif`;
                            status.className = 'text-xs text-red-600 font-bold mt-2 flex items-center';
                        } else {
                            card.classList.remove('pump-active-card');
                            card.classList.add('border-yellow-500');
                            icon.classList.add('opacity-0');
                            icon.classList.remove('opacity-100', 'animate-spin');
                            status.innerHTML = `Target Min: ${targets.tds} ppm`;
                            status.className = 'text-xs text-gray-500 mt-2 flex items-center';
                        }
                    }
                    if (payload.turbidity !== undefined) {
                        let turbVal = parseFloat(payload.turbidity);
                        document.getElementById('val_turbidity').textContent = turbVal;
                        let card = document.getElementById('card_turbidity');
                        let status = document.getElementById('status_turbidity');
                        let icon = document.getElementById('icon_pump_turbidity');
                        
                        // Using a lightning bolt icon for water pump/turbidity to denote "Flush"
                        if (turbVal > targets.turb) {
                            card.classList.add('pump-active-card');
                            card.classList.remove('border-orange-500');
                            icon.classList.remove('opacity-0');
                            icon.classList.add('opacity-100', 'animate-pulse'); // Pulse instead of spin for lightning
                            status.innerHTML = `<span class="relative flex h-2 w-2 mr-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span> ⚠️ Filter Air Aktif`;
                            status.className = 'text-xs text-red-600 font-bold mt-2 flex items-center';
                        } else {
                            card.classList.remove('pump-active-card');
                            card.classList.add('border-orange-500');
                            icon.classList.add('opacity-0');
                            icon.classList.remove('opacity-100', 'animate-pulse');
                            status.innerHTML = `Target Max: ${targets.turb}`;
                            status.className = 'text-xs text-gray-500 mt-2 flex items-center';
                        }
                    }
                    if (payload.air_temp !== undefined) {
                        document.getElementById('val_air_temp').textContent = parseFloat(payload.air_temp).toFixed(1) + ' °C';
                    }
                    if (payload.humidity !== undefined) {
                        document.getElementById('val_humidity').textContent = parseInt(payload.humidity) + ' %';
                    }
                    if (payload.light !== undefined) {
                        document.getElementById('val_light').textContent = parseInt(payload.light) + ' %';
                    }
                    
                    // Note: Optional to push this real-time data to Chart.js here 
                    // myChart.data.labels.push(new Date().toLocaleTimeString());
                    // myChart.data.datasets[0].data.push(payload.water_temp);
                    // myChart.update();

                } catch (e) {
                    console.error('Failed to parse MQTT message:', e);
                }
            });
        });
    </script>
</x-app-layout>
