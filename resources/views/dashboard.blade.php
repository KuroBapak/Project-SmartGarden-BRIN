<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
            <div id="mqtt_status_badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-amber-50 text-amber-700 border-amber-200">
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span>
                Connecting...
            </div>
        </div>
    </x-slot>

    <style>
        @keyframes siren-pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.35); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pump-active-row {
            animation: siren-pulse 1.5s infinite;
            background-color: #fef2f2 !important;
            border-color: #fca5a5 !important;
        }
        .pump-btn {
            position: relative; overflow: hidden;
            transition: all 0.2s ease;
        }
        .pump-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .pump-btn:active { transform: translateY(0); }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════ ROW 1: ENERGY MANAGEMENT ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        Energy Management
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Solar --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="w-11 h-11 rounded-full bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wide">Solar Panel</p>
                                <p class="text-2xl font-bold text-gray-900 leading-tight" id="val_solar_w">-- <span class="text-sm font-medium text-gray-400">W</span></p>
                            </div>
                        </div>
                        {{-- Load --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                            <div class="w-11 h-11 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-wide">System Load</p>
                                <p class="text-2xl font-bold text-gray-900 leading-tight" id="val_load_w">-- <span class="text-sm font-medium text-gray-400">W</span></p>
                            </div>
                        </div>
                        {{-- Battery --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                            <div class="w-11 h-11 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-500 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wide">Battery</p>
                                    <p class="text-lg font-bold text-gray-900" id="val_battery_pct">--%</p>
                                </div>
                                <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden mt-1">
                                    <div id="battery_bar_fill" class="h-full bg-emerald-500 rounded-full transition-all duration-700" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 2: SENSOR CARDS (classic style, improved) ═══════ --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 gap-4">
                {{-- Water Temp --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-blue-500">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Water Temp</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" id="val_water_temp">-- °C</div>
                </div>
                {{-- Air Temp --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-red-500">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Air Temp</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" id="val_air_temp">-- °C</div>
                </div>
                {{-- Humidity --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-cyan-500">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Humidity</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" id="val_humidity">-- %</div>
                </div>
                {{-- Light Level --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-purple-500">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Light Level</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" id="val_light">-- %</div>
                </div>
            </div>

            {{-- ═══════ ROW 3: WATER QUALITY + PUMP CONTROL ═══════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Water Quality Panel (2 col) --}}
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                Water Quality & Automation Status
                            </h3>
                            <span class="flex h-2.5 w-2.5 relative" title="System Health">
                                <span id="water_health_ping" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span id="water_health_dot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            {{-- pH --}}
                            <div id="card_ph" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">pH Level</span>
                                    <svg id="icon_pump_ph" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_ph">--</p>
                                <p id="status_ph" class="text-[11px] text-gray-400 mt-1">Target Min: {{ $setting->min_ph }}</p>
                            </div>
                            {{-- TDS --}}
                            <div id="card_tds" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">TDS Nutrisi</span>
                                    <svg id="icon_pump_tds" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_tds">-- <span class="text-sm font-medium text-gray-400">ppm</span></p>
                                <p id="status_tds" class="text-[11px] text-gray-400 mt-1">Target Min: {{ $setting->min_tds }} ppm</p>
                            </div>
                            {{-- Turbidity --}}
                            <div id="card_turbidity" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Turbidity</span>
                                    <svg id="icon_pump_turbidity" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_turbidity">--</p>
                                <p id="status_turbidity" class="text-[11px] text-gray-400 mt-1">Target Max: {{ $setting->max_turb }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Manual Pump Override (1 col) --}}
                <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 h-full flex flex-col">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            Manual Override
                        </h3>
                        <div class="flex-1 flex flex-col justify-center space-y-3">
                            <button onclick="triggerPump('ph', 5000)" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-green-600 text-white shadow-sm hover:bg-green-700 border border-green-700">
                                <div><span class="text-sm font-bold">pH Pump</span><br><span class="text-[10px] text-green-200">Injeksi Buffer</span></div>
                                <span class="bg-green-800 text-green-100 text-[10px] font-bold px-2 py-0.5 rounded">5s</span>
                            </button>
                            <button onclick="triggerPump('tds', 5000)" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-yellow-500 text-white shadow-sm hover:bg-yellow-600 border border-yellow-600">
                                <div><span class="text-sm font-bold">TDS Pump</span><br><span class="text-[10px] text-yellow-100">Injeksi AB Mix</span></div>
                                <span class="bg-yellow-700 text-yellow-100 text-[10px] font-bold px-2 py-0.5 rounded">5s</span>
                            </button>
                            <button onclick="triggerPump('water', 10000)" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg bg-blue-600 text-white shadow-sm hover:bg-blue-700 border border-blue-700">
                                <div><span class="text-sm font-bold">Water Pump</span><br><span class="text-[10px] text-blue-200">Sirkulasi / Flush</span></div>
                                <span class="bg-blue-800 text-blue-100 text-[10px] font-bold px-2 py-0.5 rounded">10s</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 4: HISTORICAL CHART (ORIGINAL STYLE) ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Historical Data Trends</h3>
                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <select name="range" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="-1h" {{ $range == '-1h' ? 'selected' : '' }}>Last 1 Hour</option>
                                <option value="-6h" {{ $range == '-6h' ? 'selected' : '' }}>Last 6 Hours</option>
                                <option value="-12h" {{ $range == '-12h' ? 'selected' : '' }}>Last 12 Hours</option>
                                <option value="-24h" {{ $range == '-24h' ? 'selected' : '' }}>Last 24 Hours</option>
                                <option value="-7d" {{ $range == '-7d' ? 'selected' : '' }}>Last 7 Days</option>
                            </select>
                            <select name="interval" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="5m" {{ $interval == '5m' ? 'selected' : '' }}>Jarak: 5m</option>
                                <option value="10m" {{ $interval == '10m' ? 'selected' : '' }}>Jarak: 10m</option>
                                <option value="15m" {{ $interval == '15m' ? 'selected' : '' }}>Jarak: 15m</option>
                                <option value="30m" {{ $interval == '30m' ? 'selected' : '' }}>Jarak: 30m</option>
                                <option value="1h" {{ $interval == '1h' ? 'selected' : '' }}>Jarak: 1h</option>
                            </select>
                        </form>
                    </div>
                    <div class="relative h-96 w-full">
                        <canvas id="historicalChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ─── 1. CHART (ORIGINAL STYLE with 3 Y-Axes, dots, all 7 datasets) ─────
            const ctx = document.getElementById('historicalChart').getContext('2d');
            const rawData = @json($historicalData);

            const historicalSize = rawData.labels.length;
            const initRadius = () => Array(historicalSize).fill(3);
            const initHitRadius = () => Array(historicalSize).fill(10);
            const initHoverRadius = () => Array(historicalSize).fill(4);

            const chartData = {
                labels: rawData.labels,
                datasets: [
                    {
                        label: 'Water Temp (°C)', data: rawData.water_temp,
                        borderColor: 'rgb(59, 130, 246)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Air Temp (°C)', data: rawData.air_temp,
                        borderColor: 'rgb(239, 68, 68)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'pH', data: rawData.ph,
                        borderColor: 'rgb(34, 197, 94)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Humidity (%)', data: rawData.humidity,
                        borderColor: 'rgb(6, 182, 212)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Light Level (%)', data: rawData.light,
                        borderColor: 'rgb(168, 85, 247)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'TDS (ppm)', data: rawData.tds,
                        borderColor: 'rgb(234, 179, 8)', tension: 0.3, yAxisID: 'y-large',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Turbidity', data: rawData.turbidity,
                        borderColor: 'rgb(249, 115, 22)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    }
                ]
            };

            const myChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true, maintainAspectRatio: false, spanGaps: false,
                    interaction: { mode: 'index', intersect: false },
                    elements: { point: { hitRadius: 10, hoverRadius: 4 } },
                    scales: {
                        x: {
                            type: 'time',
                            time: { tooltipFormat: 'dd MMM yyyy HH:mm', displayFormats: { minute: 'HH:mm', hour: 'HH:mm' } },
                            ticks: { maxRotation: 45, minRotation: 45 }
                        },
                        'y-regular': {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: 'Temp & pH' },
                            beginAtZero: true, suggestedMax: 40
                        },
                        'y-percent': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'Percentage / Turbidity' },
                            beginAtZero: true, suggestedMax: 100,
                            grid: { drawOnChartArea: false }
                        },
                        'y-large': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'TDS (ppm)' },
                            suggestedMin: 0, suggestedMax: 1000,
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            // ─── 2. MQTT ────────────────────────────────────────────────────────────
            const mqttOptions = {
                keepalive: 60,
                clientId: '{{ config("services.mqtt.client_id") }}' + '-' + Math.random().toString(16).substr(2, 6),
                protocolId: 'MQTT', protocolVersion: 4, clean: true, reconnectPeriod: 1000, connectTimeout: 30000,
                username: '{{ config("services.mqtt.username") }}',
                password: '{{ config("services.mqtt.password") }}',
            };

            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttWsPort = '{{ config("services.mqtt.ws_port") }}';
            const brokerUrl = mqttWsPort ? `ws://${mqttHost}:${mqttWsPort}/mqtt` : `wss://${mqttHost}/mqtt`;
            const pubTopic = `brin/water/{{ $setting->device_id }}/down/cmd`;

            const badge = document.getElementById('mqtt_status_badge');
            const client = mqtt.connect(brokerUrl, mqttOptions);

            client.on('connect', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-emerald-50 text-emerald-700 border-emerald-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online';
                client.subscribe('brin/water/+/up/telemetry');
            });
            client.on('error', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-red-50 text-red-700 border-red-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-red-500"></span> Error';
            });
            client.on('offline', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-gray-50 text-gray-600 border-gray-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-gray-400"></span> Offline';
            });

            // ─── 3. MANUAL PUMP TRIGGER ─────────────────────────────────────────────
            window.triggerPump = function(targetPump, durationMs) {
                if (!client.connected) { alert("MQTT Not Connected!"); return; }
                const payload = { action: 'manual_pump', target: targetPump, duration: durationMs };
                client.publish(pubTopic, JSON.stringify(payload), {qos: 0}, function(err) {
                    if(!err) alert(`Command terkirim ke ${targetPump.toUpperCase()} (${durationMs}ms)`);
                    else alert('Gagal kirim command');
                });
            };

            // ─── 4. INCOMING TELEMETRY ──────────────────────────────────────────────
            client.on('message', function (topic, message) {
                try {
                    const p = JSON.parse(message.toString());

                    const targets = {
                        ph: {{ $setting->min_ph }}, tds: {{ $setting->min_tds }},
                        turb: {{ $setting->max_turb }}, temp: {{ $setting->max_temp ?? 30 }}
                    };

                    // Energy
                    if(p.solar_w !== undefined) document.getElementById('val_solar_w').innerHTML = `${p.solar_w} <span class="text-sm font-medium text-gray-400">W</span>`;
                    if(p.load_w !== undefined) document.getElementById('val_load_w').innerHTML = `${p.load_w} <span class="text-sm font-medium text-gray-400">W</span>`;
                    if(p.battery_pct !== undefined) {
                        document.getElementById('val_battery_pct').innerText = p.battery_pct + '%';
                        document.getElementById('battery_bar_fill').style.width = p.battery_pct + '%';
                    }

                    // Simple sensors
                    if (p.water_temp !== undefined) document.getElementById('val_water_temp').textContent = parseFloat(p.water_temp).toFixed(1) + ' °C';
                    if (p.air_temp !== undefined) document.getElementById('val_air_temp').textContent = parseFloat(p.air_temp).toFixed(1) + ' °C';
                    if (p.humidity !== undefined) document.getElementById('val_humidity').textContent = parseInt(p.humidity) + ' %';
                    if (p.light !== undefined) document.getElementById('val_light').textContent = parseInt(p.light) + ' %';

                    let hasAlert = false;

                    // pH
                    if (p.ph !== undefined) {
                        let v = parseFloat(p.ph);
                        document.getElementById('val_ph').textContent = v.toFixed(2);
                        let card = document.getElementById('card_ph');
                        let icon = document.getElementById('icon_pump_ph');
                        let status = document.getElementById('status_ph');
                        if (v < targets.ph) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Pompa pH Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Min: ${targets.ph}`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // TDS
                    if (p.tds !== undefined) {
                        let v = parseInt(p.tds);
                        document.getElementById('val_tds').innerHTML = `${v} <span class="text-sm font-medium text-gray-400">ppm</span>`;
                        let card = document.getElementById('card_tds');
                        let icon = document.getElementById('icon_pump_tds');
                        let status = document.getElementById('status_tds');
                        if (v < targets.tds) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Pompa TDS Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Min: ${targets.tds} ppm`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // Turbidity
                    if (p.turbidity !== undefined) {
                        let v = parseFloat(p.turbidity);
                        document.getElementById('val_turbidity').textContent = v;
                        let card = document.getElementById('card_turbidity');
                        let icon = document.getElementById('icon_pump_turbidity');
                        let status = document.getElementById('status_turbidity');
                        if (v > targets.turb) {
                            card.classList.add('pump-active-row'); icon.classList.replace('opacity-0','opacity-100');
                            status.innerHTML = '⚠️ Filter Air Aktif'; status.className = 'text-[11px] text-red-600 font-bold mt-1';
                            hasAlert = true;
                        } else {
                            card.classList.remove('pump-active-row'); icon.classList.replace('opacity-100','opacity-0');
                            status.innerHTML = `Target Max: ${targets.turb}`; status.className = 'text-[11px] text-gray-400 mt-1';
                        }
                    }

                    // Health dot
                    const dot = document.getElementById('water_health_dot');
                    const ping = document.getElementById('water_health_ping');
                    if(hasAlert) {
                        dot.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500";
                        ping.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75";
                    } else {
                        dot.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500";
                        ping.className = "absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
                    }

                    // Chart push
                    const now = new Date().toISOString();
                    myChart.data.labels.push(now);
                    const push = (i, val) => {
                        myChart.data.datasets[i].data.push(parseFloat(val));
                        myChart.data.datasets[i].pointRadius.push(0);
                        myChart.data.datasets[i].pointHitRadius.push(0);
                        myChart.data.datasets[i].pointHoverRadius.push(0);
                    };
                    if (p.water_temp !== undefined) push(0, p.water_temp);
                    if (p.air_temp !== undefined) push(1, p.air_temp);
                    if (p.ph !== undefined) push(2, p.ph);
                    if (p.humidity !== undefined) push(3, p.humidity);
                    if (p.light !== undefined) push(4, p.light);
                    if (p.tds !== undefined) push(5, p.tds);
                    if (p.turbidity !== undefined) push(6, p.turbidity);

                    if (myChart.data.labels.length > 200) {
                        myChart.data.labels.shift();
                        myChart.data.datasets.forEach(d => {
                            d.data.shift(); d.pointRadius.shift(); d.pointHitRadius.shift(); d.pointHoverRadius.shift();
                        });
                    }
                    myChart.update('none');

                } catch (e) { console.error('MQTT parse error:', e); }
            });
        });
    </script>
</x-app-layout>
