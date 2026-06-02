<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
            <div id="mqtt_status_badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-amber-50 text-amber-700 border-amber-200">
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span>
                Menghubungkan...
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
        @keyframes fan-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fan-active {
            animation: fan-spin 0.8s linear infinite;
            opacity: 1 !important;
        }
        .pump-modal-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
        }
        .pump-modal-backdrop.active { opacity: 1; pointer-events: auto; }
        .pump-modal {
            background: white; border-radius: 1rem; padding: 1.5rem; width: 90%; max-width: 380px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            transform: scale(0.95); transition: transform 0.2s ease;
        }
        .pump-modal-backdrop.active .pump-modal { transform: scale(1); }
        .unit-btn { transition: all 0.15s ease; }
        .unit-btn.selected { background-color: #3b82f6; color: white; }
        @keyframes pump-running-glow {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            50% { box-shadow: 0 0 12px 4px rgba(34,197,94,0.35); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
        }
        .pump-running {
            animation: pump-running-glow 1.2s ease-in-out infinite;
            cursor: pointer; opacity: 0.85;
        }
        .pump-running .pump-countdown {
            display: inline-flex;
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════ ROW 1: ENERGY MANAGEMENT + SMART BATTERY AI ═══════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                {{-- Combined Energy Management + Solar Chart (2 col) --}}
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        {{-- Energy Management Section --}}
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                Manajemen Energi
                            </h3>
                            <div class="flex items-center gap-2">
                                <span id="solar_updated_at" class="text-xs text-gray-500 font-medium">--:--</span>
                                <span class="flex h-2.5 w-2.5 relative" title="Kesehatan Sistem">
                                    <span id="solar_health_ping" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span id="solar_health_dot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            {{-- Solar --}}
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-amber-50 border border-amber-100">
                                <div class="w-11 h-11 rounded-full bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wide">Panel Surya</p>
                                    <p class="text-2xl font-bold text-gray-900 leading-tight" id="val_solar_w">-- <span class="text-sm font-medium text-gray-400">W</span></p>
                                </div>
                            </div>
                            {{-- Load --}}
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <div class="w-11 h-11 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-wide">Beban Sistem</p>
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
                                        <p class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wide">Baterai</p>
                                        <p class="text-lg font-bold text-gray-900" id="val_battery_pct">--%</p>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden mt-1">
                                        <div id="battery_bar_fill" class="h-full bg-emerald-500 rounded-full transition-all duration-700" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6 border-gray-100">

                        {{-- Compact BMKG Section --}}
                        <div class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-[11px] font-bold text-gray-500 uppercase tracking-wide flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                    Prakiraan Cuaca BMKG
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span id="bmkg_analysis_date" class="hidden"></span>
                                    <span id="bmkg_last_update" class="text-[9px] text-gray-400"></span>
                                    <button onclick="fetchBmkgForecast()" class="text-gray-400 hover:text-gray-600 transition-colors" title="Refresh">
                                        <svg id="bmkg_refresh_icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div id="bmkg_forecast_body" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="col-span-full text-center py-4 text-xs text-gray-400">Memuat data cuaca...</div>
                            </div>
                        </div>

                        {{-- Solar Chart Section --}}
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Riwayat Surya & Beban
                            </h3>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                                <input type="hidden" name="range" value="{{ $range }}">
                                <input type="hidden" name="interval" value="{{ $interval }}">
                                <select name="solar_range" onchange="this.form.submit()" class="text-[10px] sm:text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 py-1 pl-2 pr-6">
                                    <option value="-1h" {{ $solar_range == '-1h' ? 'selected' : '' }}>1 Jam Terakhir</option>
                                    <option value="-6h" {{ $solar_range == '-6h' ? 'selected' : '' }}>6 Jam Terakhir</option>
                                    <option value="-12h" {{ $solar_range == '-12h' ? 'selected' : '' }}>12 Jam Terakhir</option>
                                    <option value="-24h" {{ $solar_range == '-24h' ? 'selected' : '' }}>24 Jam Terakhir</option>
                                    <option value="-7d" {{ $solar_range == '-7d' ? 'selected' : '' }}>7 Hari Terakhir</option>
                                </select>
                                <select name="solar_interval" onchange="this.form.submit()" class="text-[10px] sm:text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 py-1 pl-2 pr-6">
                                    <option value="5m" {{ $solar_interval == '5m' ? 'selected' : '' }}>Jarak: 5m</option>
                                    <option value="10m" {{ $solar_interval == '10m' ? 'selected' : '' }}>Jarak: 10m</option>
                                    <option value="15m" {{ $solar_interval == '15m' ? 'selected' : '' }}>Jarak: 15m</option>
                                    <option value="30m" {{ $solar_interval == '30m' ? 'selected' : '' }}>Jarak: 30m</option>
                                    <option value="1h" {{ $solar_interval == '1h' ? 'selected' : '' }}>Jarak: 1h</option>
                                </select>
                            </form>
                        </div>
                        <div class="w-full" style="height: 250px;">
                            <canvas id="solarHistoryChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Smart Battery AI Panel (1 col) --}}
                <div class="lg:col-span-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 h-full flex flex-col">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            Smart Battery AI
                            <span id="ai_server_dot" class="h-2 w-2 rounded-full bg-gray-300 transition-colors" title="AI Server Status"></span>
                        </h3>

                        {{-- Status Indicator --}}
                        <div id="ai_status_card" class="p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-gray-50 border-gray-200">
                            <div class="flex items-center gap-3 mb-2">
                                <div id="ai_status_icon" class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 transition-all duration-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <p id="ai_status_label" class="text-sm font-bold text-gray-700">Menunggu AI Server...</p>
                                    <p id="ai_status_desc" class="text-[11px] text-gray-400 leading-tight">Menunggu data analisis dari AI Server</p>
                                </div>
                            </div>
                        </div>

                        {{-- Energy Metrics --}}
                        <div class="space-y-3 flex-1">
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Net Daya</span>
                                    <span id="ai_net_power" class="text-sm font-bold text-gray-700">-- W</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Solar IN − System OUT</p>
                            </div>

                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Estimasi Ketahanan</span>
                                    <span id="ai_endurance" class="text-sm font-bold text-gray-700">-- Jam</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Saat cuaca buruk tiba</p>
                            </div>

                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Prakiraan Solar</span>
                                    <span id="ai_solar_forecast" class="text-sm font-bold text-gray-700">-- W</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Besok (prediksi dari awan)</p>
                            </div>

                            <div id="ai_action_box" class="p-3 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50/50 transition-all duration-500">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Rekomendasi AI</p>
                                </div>
                                <p id="ai_recommendation" class="text-xs text-gray-600 leading-relaxed">Menunggu data dari AI Server...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 2: MONITORING + WATER QUALITY + PUMP CONTROL ═══════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Combined Monitoring + Water Quality Panel (2 col) --}}
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        {{-- Realtime Monitoring Section --}}
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Realtime Monitoring
                            </h3>
                            <div class="flex items-center gap-2">
                                <span id="water_updated_at" class="text-xs text-gray-500 font-medium">--:--</span>
                                <span class="flex h-2.5 w-2.5 relative" title="System Health">
                                    <span id="water_health_ping" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span id="water_health_dot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-blue-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Water Temp</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_water_temp">-- °C</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-green-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">WiFi RSSI</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_rssi">-- dBm</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-cyan-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Humidity</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_humidity">-- %</div>
                            </div>
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 border-l-4 border-l-purple-500">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Light Level</div>
                                <div class="mt-2 text-2xl font-bold text-gray-900" id="val_light">-- %</div>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <hr class="border-gray-100 mb-5">

                        {{-- Water Quality & Automation Section --}}
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            Water Quality & Automation Status
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            {{-- pH --}}
                            <div id="card_ph" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">pH Level</span>
                                    <svg id="icon_pump_ph" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_ph">--</p>
                                <p class="text-[11px] text-gray-400 mt-1">Raw: <span id="val_raw_ph">--</span> mV</p>
                                <p id="status_ph" class="text-[11px] text-gray-400 mt-1"></p>
                            </div>
                            {{-- TDS --}}
                            <div id="card_tds" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">TDS Nutrisi</span>
                                    <svg id="icon_pump_tds" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_tds">-- <span class="text-sm font-medium text-gray-400">ppm</span></p>
                                <p class="text-[11px] text-gray-400 mt-1">Raw: <span id="val_raw_tds">--</span> V</p>
                                <p id="status_tds" class="text-[11px] text-gray-400 mt-1"></p>
                            </div>
                            {{-- Turbidity --}}
                            <div id="card_turbidity" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Turbidity</span>
                                    <svg id="icon_pump_turbidity" class="w-4 h-4 text-red-500 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_turbidity">--</p>
                                <p class="text-[11px] text-gray-400 mt-1">Raw: <span id="val_raw_turbidity">--</span> V</p>
                                <p id="status_turbidity" class="text-[11px] text-gray-400 mt-1"></p>
                            </div>
                            {{-- Air Temp (with Fan pump animation) --}}
                            <div id="card_air_temp" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 transition-all duration-300">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Air Temp</span>
                                    <svg id="icon_pump_fan" class="w-5 h-5 text-red-500 opacity-0 transition-opacity fan-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>
                                </div>
                                <p class="text-3xl font-bold text-gray-900" id="val_air_temp">-- <span class="text-sm font-medium text-gray-400">°C</span></p>
                                <p id="status_air_temp" class="text-[11px] text-gray-400 mt-1"></p>
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
                            @php
                                $pumpNames = $setting->pump_names ?? \App\Models\DeviceSetting::defaultPumpNames();
                                $pumpColors = ['pump_1'=>['bg-green-600','hover:bg-green-700','border-green-700','text-green-200'], 'pump_2'=>['bg-yellow-500','hover:bg-yellow-600','border-yellow-600','text-yellow-100'], 'pump_3'=>['bg-blue-600','hover:bg-blue-700','border-blue-700','text-blue-200'], 'pump_4'=>['bg-purple-600','hover:bg-purple-700','border-purple-700','text-purple-200']];
                            @endphp
                            @foreach(['pump_1','pump_2','pump_3','pump_4'] as $pKey)
                                @php $c = $pumpColors[$pKey]; $pName = $pumpNames[$pKey] ?? ucfirst(str_replace('_',' ',$pKey)); @endphp
                                <button id="btn_{{ $pKey }}" onclick="openPumpModal('{{ $pKey }}', '{{ $pName }}', 'Relay {{ substr($pKey,-1) }}')" class="pump-btn w-full flex items-center justify-between p-3.5 rounded-lg {{ $c[0] }} text-white shadow-sm {{ $c[1] }} border {{ $c[2] }}">
                                    <div><span class="text-sm font-bold">{{ $pName }}</span><br><span class="text-[10px] {{ $c[3] }}">Relay {{ substr($pKey,-1) }}</span></div>
                                    <span class="pump-countdown hidden items-center gap-1 text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span class="countdown-text"></span></span>
                                    <svg class="w-4 h-4 {{ $c[3] }} pump-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════ ROW 3: SENSOR HISTORY ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            Riwayat Sensor
                        </h3>
                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <input type="hidden" name="solar_range" value="{{ $solar_range }}">
                            <input type="hidden" name="solar_interval" value="{{ $solar_interval }}">
                            <select name="range" onchange="this.form.submit()" class="text-[10px] sm:text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 py-1 pl-2 pr-6">
                                <option value="-1h" {{ $range == '-1h' ? 'selected' : '' }}>1 Jam Terakhir</option>
                                <option value="-6h" {{ $range == '-6h' ? 'selected' : '' }}>6 Jam Terakhir</option>
                                <option value="-12h" {{ $range == '-12h' ? 'selected' : '' }}>12 Jam Terakhir</option>
                                <option value="-24h" {{ $range == '-24h' ? 'selected' : '' }}>24 Jam Terakhir</option>
                                <option value="-7d" {{ $range == '-7d' ? 'selected' : '' }}>7 Hari Terakhir</option>
                            </select>
                            <select name="interval" onchange="this.form.submit()" class="text-[10px] sm:text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 py-1 pl-2 pr-6">
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

            {{-- ═══════ ROW 4: PLANT DISEASE DETECTION ═══════ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-5">
                    {{-- Header with Capture button --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Deteksi Penyakit Tanaman
                            <span id="plant_scan_status_dot" class="h-2 w-2 rounded-full bg-gray-300 transition-colors"></span>
                        </h3>
                        <div class="flex items-center gap-2 flex-wrap">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        {{-- Status Card --}}
                        <div class="lg:col-span-1 space-y-4">
                            {{-- Health Status --}}
                            <div id="plant_status_card" class="p-4 rounded-xl border-2 transition-all duration-500 bg-gray-50 border-gray-200">
                                <div class="flex items-center gap-3 mb-2">
                                    <div id="plant_status_icon" class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 transition-all duration-500 shrink-0 text-xl">
                                        🌿
                                    </div>
                                    <div>
                                        <p id="plant_status_label" class="text-sm font-bold text-gray-700">Menunggu Scan...</p>
                                        <p id="plant_status_desc" class="text-[11px] text-gray-400 leading-tight">Menunggu AI Server...</p>
                                    </div>
                                </div>
                            </div>
                            {{-- Detected Diseases List --}}
                            <div id="plant_diseases_box" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hidden">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-3">Penyakit Terdeteksi</p>
                                <div id="plant_diseases_list" class="space-y-2">
                                </div>
                            </div>
                            {{-- Detection Summary --}}
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Total Deteksi</span>
                                    <span id="plant_total_detections" class="text-sm font-bold text-gray-700">--</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Jumlah objek terdeteksi</p>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Waktu Scan</span>
                                    <span id="plant_last_scan_time" class="text-sm font-bold text-gray-700">--</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-0.5">Capture dari kamera Raspi</p>
                            </div>
                        </div>
                        {{-- Captured Image --}}
                        <div class="lg:col-span-2">
                            <div id="plant_image_container" class="relative rounded-xl border-2 border-gray-100 overflow-hidden bg-gray-100 flex items-center justify-center" style="min-height: 320px;">
                                {{-- Placeholder --}}
                                <div id="plant_image_placeholder" class="text-center p-8">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm font-medium text-gray-400">Belum ada gambar scan</p>
                                    <p class="text-xs text-gray-300 mt-1">Tekan tombol <strong class="text-lime-500">Capture Now</strong> atau jalankan scanner</p>
                                </div>
                                {{-- Actual Image --}}
                                <img id="plant_scan_image" src="" alt="Plant Scan" class="w-full h-auto object-contain hidden cursor-pointer" onclick="togglePlantImageZoom()" style="max-height: 450px;">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 text-center">Klik gambar untuk zoom • Auto scan setiap 5 menit</p>
                        </div>
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
                        label: 'Suhu Air (°C)', data: rawData.water_temp,
                        borderColor: 'rgb(59, 130, 246)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Suhu Udara (°C)', data: rawData.air_temp,
                        borderColor: 'rgb(239, 68, 68)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'pH', data: rawData.ph,
                        borderColor: 'rgb(34, 197, 94)', tension: 0.3, yAxisID: 'y-regular',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Kelembapan (%)', data: rawData.humidity,
                        borderColor: 'rgb(6, 182, 212)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Intensitas Cahaya (%)', data: rawData.light,
                        borderColor: 'rgb(168, 85, 247)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'TDS (ppm)', data: rawData.tds,
                        borderColor: 'rgb(234, 179, 8)', tension: 0.3, yAxisID: 'y-large',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Kekeruhan (NTU)', data: rawData.turbidity,
                        borderColor: 'rgb(249, 115, 22)', tension: 0.3, yAxisID: 'y-percent',
                        pointRadius: initRadius(), pointHitRadius: initHitRadius(), pointHoverRadius: initHoverRadius()
                    },
                    {
                        label: 'Sinyal WiFi (dBm)', data: rawData.rssi,
                        borderColor: 'rgb(16, 185, 129)', tension: 0.3, yAxisID: 'y-rssi',
                        borderDash: [5, 3],
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
                    plugins: { legend: { align: 'center', labels: { padding: 14 } } },
                    elements: { point: { hitRadius: 10, hoverRadius: 4 } },
                    scales: {
                        x: {
                            type: 'time',
                            time: { tooltipFormat: 'dd MMM yyyy HH:mm', displayFormats: { minute: 'HH:mm', hour: 'HH:mm' } },
                            ticks: { maxRotation: 45, minRotation: 45 }
                        },
                        'y-regular': {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: 'Suhu & pH' },
                            beginAtZero: true, suggestedMax: 40
                        },
                        'y-percent': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'Persentase / Kekeruhan' },
                            beginAtZero: true, suggestedMax: 100,
                            grid: { drawOnChartArea: false }
                        },
                        'y-large': {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: 'TDS (ppm)' },
                            suggestedMin: 0, suggestedMax: 1000,
                            grid: { drawOnChartArea: false }
                        },
                        'y-rssi': {
                            type: 'linear', display: false,
                            suggestedMin: -100, suggestedMax: 0,
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            // ─── INIT SOLAR CHART ──────────────────────────────────────────────────
            const ctxSolar = document.getElementById('solarHistoryChart').getContext('2d');
            const rawSolarData = {!! json_encode($historicalSolarData ?? ['labels'=>[], 'pv_power'=>[], 'load_power'=>[], 'battery_percentage'=>[]]) !!};
            
            const initSolarRadius = () => rawSolarData.labels.map(() => 0);
            const initSolarHitRadius = () => rawSolarData.labels.map(() => 10);
            const initSolarHoverRadius = () => rawSolarData.labels.map(() => 4);

            const solarChartData = {
                labels: rawSolarData.labels,
                datasets: [
                    {
                        label: 'Panel Surya (W)', data: rawSolarData.pv_power,
                        borderColor: 'rgb(245, 158, 11)', backgroundColor: 'rgba(245, 158, 11, 0.1)', tension: 0.3, fill: true,
                        pointRadius: initSolarRadius(), pointHitRadius: initSolarHitRadius(), pointHoverRadius: initSolarHoverRadius()
                    },
                    {
                        label: 'Beban (W)', data: rawSolarData.load_power,
                        borderColor: 'rgb(59, 130, 246)', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: true,
                        pointRadius: initSolarRadius(), pointHitRadius: initSolarHitRadius(), pointHoverRadius: initSolarHoverRadius()
                    },
                    {
                        label: 'Baterai (%)', data: rawSolarData.battery_percentage,
                        borderColor: 'rgb(16, 185, 129)', tension: 0.3, yAxisID: 'y-battery',
                        borderDash: [5, 3],
                        pointRadius: initSolarRadius(), pointHitRadius: initSolarHitRadius(), pointHoverRadius: initSolarHoverRadius()
                    }
                ]
            };

            const solarChart = new Chart(ctxSolar, {
                type: 'line',
                data: solarChartData,
                options: {
                    responsive: true, maintainAspectRatio: false, spanGaps: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { align: 'center', labels: { padding: 14 } } },
                    elements: { point: { hitRadius: 10, hoverRadius: 4 } },
                    scales: {
                        x: { type: 'time', time: { unit: 'minute', displayFormats: { minute: 'HH:mm' }, tooltipFormat: 'dd MMM, HH:mm' }, grid: { display: false } },
                        y: { position: 'left', min: 0, title: { display: true, text: 'Power (W)' } },
                        'y-battery': { position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false }, title: { display: true, text: 'Battery %' } }
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
            });
            client.on('error', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-red-50 text-red-700 border-red-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-red-500"></span> Error';
            });
            client.on('offline', function () {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm bg-gray-50 text-gray-600 border-gray-200';
                badge.innerHTML = '<span class="h-2 w-2 rounded-full bg-gray-400"></span> Offline';
            });

            // ─── 3. MANUAL PUMP MODAL ──────────────────────────────────────────────
            const pumpHistoryKey = (pump) => `pump_history_${pump}`;

            function getPumpHistory(pump) {
                try { return JSON.parse(localStorage.getItem(pumpHistoryKey(pump))) || null; }
                catch { return null; }
            }
            function savePumpHistory(pump, val, unit) {
                localStorage.setItem(pumpHistoryKey(pump), JSON.stringify({ val, unit }));
            }

            let currentPumpTarget = '';
            window.pumpIntervals = {};

            function stopPumpAnimation(pump) {
                const btn = document.getElementById(`btn_${pump}`);
                if (!btn) return;
                const arrow = btn.querySelector('.pump-arrow');
                if (arrow) arrow.classList.remove('hidden');
                btn.classList.remove('pump-running');
                const countdownEl = btn.querySelector('.countdown-text');
                if (countdownEl) countdownEl.textContent = '';
                if (window.pumpIntervals[pump]) {
                    clearInterval(window.pumpIntervals[pump]);
                    delete window.pumpIntervals[pump];
                }
            }

            window.openPumpModal = function(pump, title, subtitle) {
                const btn = document.getElementById(`btn_${pump}`);
                if (btn && btn.classList.contains('pump-running')) {
                    if (confirm(`Hentikan operasi ${title} sekarang?`)) {
                        if (!client.connected) { alert('MQTT Not Connected!'); return; }
                        const payload = { action: 'manual_pump', target: pump, duration: 0 };
                        client.publish(pubTopic, JSON.stringify(payload), {qos: 0}, function(err) {
                            if (err) alert('Gagal kirim command stop');
                            else stopPumpAnimation(pump);
                        });
                    }
                    return;
                }

                currentPumpTarget = pump;
                document.getElementById('modal_pump_title').textContent = title;
                document.getElementById('modal_pump_subtitle').textContent = subtitle;
                document.getElementById('modal_duration_input').value = '';
                // reset unit buttons
                document.getElementById('unit_sec').classList.add('selected');
                document.getElementById('unit_min').classList.remove('selected');
                // show history
                const hist = getPumpHistory(pump);
                const histEl = document.getElementById('modal_history_section');
                if (hist) {
                    histEl.classList.remove('hidden');
                    const label = hist.unit === 'min' ? 'menit' : 'detik';
                    document.getElementById('modal_history_text').textContent = `${hist.val} ${label}`;
                } else {
                    histEl.classList.add('hidden');
                }
                document.getElementById('pump_modal_backdrop').classList.add('active');
            };

            window.closePumpModal = function() {
                document.getElementById('pump_modal_backdrop').classList.remove('active');
            };

            window.selectUnit = function(unit) {
                if (unit === 'sec') {
                    document.getElementById('unit_sec').classList.add('selected');
                    document.getElementById('unit_min').classList.remove('selected');
                } else {
                    document.getElementById('unit_min').classList.add('selected');
                    document.getElementById('unit_sec').classList.remove('selected');
                }
            };

            window.useHistory = function() {
                const hist = getPumpHistory(currentPumpTarget);
                if (hist) {
                    document.getElementById('modal_duration_input').value = hist.val;
                    selectUnit(hist.unit === 'min' ? 'min' : 'sec');
                }
            };

            function startPumpAnimation(pump, durationMs) {
                stopPumpAnimation(pump); // Clear any existing
                const btn = document.getElementById(`btn_${pump}`);
                if (!btn) return;
                const arrow = btn.querySelector('.pump-arrow');
                if (arrow) arrow.classList.add('hidden');
                btn.classList.add('pump-running');
                
                let remaining = Math.ceil(durationMs / 1000);
                const countdownEl = btn.querySelector('.countdown-text');
                countdownEl.textContent = `${remaining}s`;
                
                window.pumpIntervals[pump] = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        stopPumpAnimation(pump);
                    } else {
                        countdownEl.textContent = `${remaining}s`;
                    }
                }, 1000);
            }

            window.confirmPump = function() {
                const val = parseInt(document.getElementById('modal_duration_input').value);
                if (!val || val <= 0) { alert('Masukkan durasi yang valid!'); return; }
                
                const isMin = document.getElementById('unit_min').classList.contains('selected');
                const unit = isMin ? 'min' : 'sec';
                const durationMs = isMin ? val * 60000 : val * 1000;
                
                if (durationMs > 7200000) { 
                    alert('Durasi maksimal adalah 2 Jam (120 menit / 7200 detik) untuk mencegah pompa jebol.'); 
                    return; 
                }

                if (!client.connected) { alert('MQTT Not Connected!'); closePumpModal(); return; }
                
                savePumpHistory(currentPumpTarget, val, unit);
                const payload = { action: 'manual_pump', target: currentPumpTarget, duration: durationMs };
                const pumpTarget = currentPumpTarget;
                client.publish(pubTopic, JSON.stringify(payload), {qos: 0}, function(err) {
                    if (err) alert('Gagal kirim command');
                });
                closePumpModal();
                startPumpAnimation(pumpTarget, durationMs);
            };

            // ─── 4.1. SENSOR DATA FETCH (from InfluxDB) ────────────────────────
            window.fetchSensorData = function() {
                return fetch('/api/sensor').then(r => r.json()).then(p => {
                    if (p.error) return;
                    
                    if (p.updated_at) {
                        if (window.lastSensorUpdate === p.updated_at) return; // Skip if no new data
                        window.lastSensorUpdate = p.updated_at;
                        document.getElementById('water_updated_at').textContent = p.updated_at;
                    }

                    const targets = @json($setting->rules ?? []);

                    // Simple sensors
                    document.getElementById('val_water_temp').textContent = parseFloat(p.water_temp).toFixed(1) + ' °C';
                    document.getElementById('val_rssi').textContent = parseInt(p.rssi) + ' dBm';
                    document.getElementById('val_humidity').textContent = parseInt(p.humidity) + ' %';
                    document.getElementById('val_light').textContent = parseInt(p.light) + ' %';

                    let hasAlert = false;

                    // Display sensor values
                    document.getElementById('val_ph').textContent = parseFloat(p.ph).toFixed(2);
                    if (p.raw_ph_mv !== undefined && p.raw_ph_mv !== null) document.getElementById('val_raw_ph').textContent = parseFloat(p.raw_ph_mv).toFixed(2);
                    
                    document.getElementById('val_tds').innerHTML = `${parseInt(p.tds)} <span class="text-sm font-medium text-gray-400">ppm</span>`;
                    if (p.raw_tds_v !== undefined && p.raw_tds_v !== null) document.getElementById('val_raw_tds').textContent = parseFloat(p.raw_tds_v).toFixed(2);
                    
                    document.getElementById('val_turbidity').textContent = parseFloat(p.turbidity).toFixed(1);
                    if (p.raw_turb_v !== undefined && p.raw_turb_v !== null) document.getElementById('val_raw_turbidity').textContent = parseFloat(p.raw_turb_v).toFixed(2);
                    
                    document.getElementById('val_air_temp').innerHTML = `${parseFloat(p.air_temp).toFixed(1)} <span class="text-sm font-medium text-gray-400">°C</span>`;

                    // Map sensor keys to card/icon/status elements
                    const sensorCardMap = {
                        ph: { card: 'card_ph', icon: 'icon_pump_ph', status: 'status_ph' },
                        tds: { card: 'card_tds', icon: 'icon_pump_tds', status: 'status_tds' },
                        turbidity: { card: 'card_turbidity', icon: 'icon_pump_turbidity', status: 'status_turbidity' },
                        air_temp: { card: 'card_air_temp', icon: 'icon_pump_fan', status: 'status_air_temp' },
                    };

                    // Reset all sensor cards first
                    Object.values(sensorCardMap).forEach(m => {
                        const card = document.getElementById(m.card);
                        const icon = document.getElementById(m.icon);
                        const status = document.getElementById(m.status);
                        if (card) card.classList.remove('pump-active-row');
                        if (icon) { icon.classList.replace('opacity-100','opacity-0'); icon.classList.remove('fan-active'); }
                        if (status) { status.textContent = ''; status.className = 'text-[11px] text-gray-400 mt-1'; }
                    });

                    // Evaluate dynamic rules
                    const sensorValues = { ph: parseFloat(p.ph), tds: parseFloat(p.tds), turbidity: parseFloat(p.turbidity), water_temp: parseFloat(p.water_temp), air_temp: parseFloat(p.air_temp), humidity: parseFloat(p.humidity), light: parseFloat(p.light) };

                    targets.forEach(rule => {
                        const val = sensorValues[rule.sensor];
                        if (val === undefined || isNaN(val)) return;
                        const violated = rule.condition === '<' ? val < parseFloat(rule.value) : val > parseFloat(rule.value);
                        const m = sensorCardMap[rule.sensor];
                        if (m) {
                            const status = document.getElementById(m.status);
                            const condLabel = rule.condition === '<' ? 'Min' : 'Max';
                            if (status) status.textContent = `${condLabel}: ${rule.value}`;
                        }
                        if (violated) {
                            hasAlert = true;
                            if (m) {
                                const card = document.getElementById(m.card);
                                const icon = document.getElementById(m.icon);
                                const status = document.getElementById(m.status);
                                if (card) card.classList.add('pump-active-row');
                                if (icon) { icon.classList.replace('opacity-0','opacity-100'); if (rule.sensor === 'air_temp') icon.classList.add('fan-active'); }
                                if (status) { status.innerHTML = `⚠️ Pump ${rule.pump} Aktif`; status.className = 'text-[11px] text-red-600 font-bold mt-1'; }
                            }
                        }
                    });

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
                    push(0, p.water_temp);
                    push(1, p.air_temp);
                    push(2, p.ph);
                    push(3, p.humidity);
                    push(4, p.light);
                    push(5, p.tds);
                    push(6, p.turbidity);
                    push(7, p.rssi);

                    if (myChart.data.labels.length > 200) {
                        myChart.data.labels.shift();
                        myChart.data.datasets.forEach(d => {
                            d.data.shift(); d.pointRadius.shift(); d.pointHitRadius.shift(); d.pointHoverRadius.shift();
                        });
                    }
                    myChart.update('none');

                }).catch(err => console.error('Sensor data fetch error:', err));
            };

            // ─── 5. BMKG WEATHER FORECAST ─────────────────────────────────────────
            let bmkgData = null;
            let bmkgSelectedDay = 0;

            window.fetchBmkgForecast = function() {
                const refreshIcon = document.getElementById('bmkg_refresh_icon');
                refreshIcon.classList.add('animate-spin');

                fetch('{{ route("api.bmkg.forecast") }}')
                    .then(r => r.json())
                    .then(data => {
                        refreshIcon.classList.remove('animate-spin');
                        if (data.error) {
                            document.getElementById('bmkg_forecast_body').innerHTML =
                                `<div class="col-span-full text-center py-4 text-xs text-red-400">⚠️ ${data.error}</div>`;
                            return;
                        }
                        bmkgData = data;
                        renderBmkgTable(0);
                    })
                    .catch(err => {
                        refreshIcon.classList.remove('animate-spin');
                        console.error('BMKG fetch error:', err);
                        document.getElementById('bmkg_forecast_body').innerHTML =
                            `<div class="col-span-full text-center py-4 text-xs text-red-400">⚠️ Gagal memuat data cuaca</div>`;
                    });
            };

            function renderBmkgTable(dayIndex) {
                if (!bmkgData?.data?.[0]?.cuaca) return;
                
                // Combine today and tomorrow's data to ensure we always have enough items for 4 cards
                let items = [];
                if (bmkgData.data[0].cuaca[0]) items = items.concat(bmkgData.data[0].cuaca[0]);
                if (bmkgData.data[0].cuaca[1]) items = items.concat(bmkgData.data[0].cuaca[1]);
                
                const tbody = document.getElementById('bmkg_forecast_body');

                document.getElementById('bmkg_last_update').textContent =
                    `Diperbarui: ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;

                let rows = '';
                const displayItems = items.slice(0, 4);

                displayItems.forEach((item, idx) => {
                    const localDt = new Date(item.local_datetime.replace(' ', 'T'));
                    const timeStr = localDt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                    let tccColor = 'text-sky-600 bg-sky-50';
                    if (item.tcc > 70) tccColor = 'text-gray-600 bg-gray-100';
                    else if (item.tcc > 40) tccColor = 'text-amber-600 bg-amber-50';

                    rows += `
                        <div class="p-2.5 rounded-lg bg-white border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] font-bold text-gray-500 mb-1">${timeStr}</span>
                            <img src="${item.image}" alt="${item.weather_desc}" class="w-8 h-8 mb-1" onerror="this.style.display='none'">
                            <span class="text-[9px] font-medium text-gray-600 leading-tight mb-1 truncate w-full" title="${item.weather_desc}">${item.weather_desc}</span>
                            <div class="flex items-center gap-2 mt-auto">
                                <span class="text-xs font-bold text-gray-800">${item.t}°</span>
                                <span class="text-[9px] px-1 py-0.5 rounded font-bold ${tccColor}" title="Tutupan Awan">☁ ${item.tcc}%</span>
                            </div>
                        </div>
                    `;
                });

                tbody.innerHTML = rows;
            }

            // ─── 6. AI ENERGY ANALYSIS (polling pre-computed from AI Server) ──────
            function fetchAiEnergyAnalysis() {
                fetch('/api/ai/energy-analysis/latest')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.status) { document.getElementById('ai_server_dot').className = 'h-2 w-2 rounded-full bg-gray-300 transition-colors'; return; }
                        document.getElementById('ai_server_dot').className = 'h-2 w-2 rounded-full bg-emerald-500 transition-colors';
                        const np = data.net_power ?? 0;
                        const netEl = document.getElementById('ai_net_power');
                        netEl.textContent = `${np >= 0 ? '+' : ''}${parseFloat(np).toFixed(1)} W`;
                        netEl.className = `text-sm font-bold ${np >= 0 ? 'text-emerald-600' : 'text-red-600'}`;
                        const eh = data.endurance_hours;
                        const endEl = document.getElementById('ai_endurance');
                        const ttf = data.time_to_full;
                        const tte = data.time_to_empty;

                        if (data.net_power >= 0 && ttf !== null) {
                            // Charging → show time to full
                            endEl.textContent = `~${parseFloat(ttf).toFixed(1)} Jam → Penuh`;
                            endEl.className = 'text-sm font-bold text-emerald-600';
                        } else if (data.net_power < 0 && tte !== null) {
                            // Discharging → show time to empty
                            endEl.textContent = `${parseFloat(tte).toFixed(1)} Jam`;
                            endEl.className = `text-sm font-bold ${tte > 48 ? 'text-emerald-600' : tte > 12 ? 'text-amber-600' : 'text-red-600'}`;
                        } else if (eh !== null && eh > 0) {
                            // Fallback to endurance_hours
                            endEl.textContent = `${parseFloat(eh).toFixed(1)} Jam`;
                            endEl.className = `text-sm font-bold ${eh > 48 ? 'text-emerald-600' : eh > 12 ? 'text-amber-600' : 'text-red-600'}`;
                        } else {
                            // Battery full or no load
                            endEl.textContent = '✅ Stabil';
                            endEl.className = 'text-sm font-bold text-emerald-600';
                        }
                        if (data.solar_forecast !== null) document.getElementById('ai_solar_forecast').textContent = `~${parseFloat(data.solar_forecast).toFixed(1)} W`;
                        const sc = document.getElementById('ai_status_card'), si = document.getElementById('ai_status_icon'), sl = document.getElementById('ai_status_label'), sd = document.getElementById('ai_status_desc');
                        const ts = new Date(data.updated_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
                        if (data.status === 'emergency') {
                            sc.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-red-50 border-red-300'; si.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-red-500 text-white transition-all duration-500 shrink-0';
                            sl.textContent = '⚡ Mode Darurat'; sl.className = 'text-sm font-bold text-red-700'; sd.textContent = `Diperbarui: ${ts}`; sd.className = 'text-[11px] text-red-500 leading-tight';
                        } else if (data.status === 'hoarding') {
                            sc.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-amber-50 border-amber-300'; si.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-amber-500 text-white transition-all duration-500 shrink-0';
                            sl.textContent = '🟡 Mode Hemat Energi'; sl.className = 'text-sm font-bold text-amber-700'; sd.textContent = `Diperbarui: ${ts}`; sd.className = 'text-[11px] text-amber-500 leading-tight';
                        } else {
                            sc.className = 'p-4 rounded-xl border-2 mb-4 transition-all duration-500 bg-emerald-50 border-emerald-300'; si.className = 'w-10 h-10 rounded-full flex items-center justify-center bg-emerald-500 text-white transition-all duration-500 shrink-0';
                            sl.textContent = '✅ Optimal'; sl.className = 'text-sm font-bold text-emerald-700'; sd.textContent = `Diperbarui: ${ts}`; sd.className = 'text-[11px] text-emerald-500 leading-tight';
                        }
                        const fullText = data.analysis_text || '', actionBox = document.getElementById('ai_action_box'), recEl = document.getElementById('ai_recommendation');
                        let boxClass = 'p-3 rounded-lg border-2 border-emerald-200 bg-emerald-50', bodyColor = 'text-emerald-800';
                        if (fullText.includes('🔴') || data.status === 'emergency') { boxClass = 'p-3 rounded-lg border-2 border-red-200 bg-red-50'; bodyColor = 'text-red-800'; }
                        else if (fullText.includes('🟡') || data.status === 'hoarding') { boxClass = 'p-3 rounded-lg border-2 border-amber-200 bg-amber-50'; bodyColor = 'text-amber-800'; }
                        actionBox.className = boxClass;
                        recEl.innerHTML = `<span class="${bodyColor}">${fullText.replace(/\n/g, '<br>')}</span>`;
                        recEl.className = 'text-xs leading-relaxed max-h-96 overflow-y-auto block pr-1';
                    }).catch(err => console.error('AI energy fetch error:', err));
            }

            // ─── 7. PLANT DISEASE DETECTION (polling from DB) ────────────────────
            let plantImageZoomed = false;
            window.fetchPlantScan = function() {
                fetch('/api/ai/plant-scan/latest').then(r => r.json()).then(data => {
                    if (!data.status || data.error) { document.getElementById('plant_scan_status_dot').className = 'h-2 w-2 rounded-full bg-gray-300 transition-colors'; return; }
                    renderPlantScan(data);
                }).catch(err => console.error('Plant scan fetch error:', err));
            };
            function renderPlantScan(data) {
                const sCard = document.getElementById('plant_status_card'), sIcon = document.getElementById('plant_status_icon'), sLabel = document.getElementById('plant_status_label'), sDesc = document.getElementById('plant_status_desc'), sDot = document.getElementById('plant_scan_status_dot'), dBox = document.getElementById('plant_diseases_box'), dList = document.getElementById('plant_diseases_list');
                const s = data.status;
                if (s.status === 'healthy') { sCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-emerald-50 border-emerald-300'; sIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-emerald-500 text-white shrink-0 text-xl'; sIcon.textContent = '✅'; sLabel.textContent = s.status_label; sLabel.className = 'text-sm font-bold text-emerald-700'; sDesc.textContent = s.message; sDesc.className = 'text-[11px] text-emerald-500 leading-tight'; sDot.className = 'h-2 w-2 rounded-full bg-emerald-500 transition-colors'; dBox.classList.add('hidden'); }
                else if (s.status === 'critical') { sCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-red-50 border-red-300'; sIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-red-500 text-white shrink-0 text-xl'; sIcon.textContent = '🔴'; sLabel.textContent = s.status_label; sLabel.className = 'text-sm font-bold text-red-700'; sDesc.textContent = s.message; sDesc.className = 'text-[11px] text-red-500 leading-tight'; sDot.className = 'h-2 w-2 rounded-full bg-red-500 transition-colors'; }
                else { sCard.className = 'p-4 rounded-xl border-2 transition-all duration-500 bg-amber-50 border-amber-300'; sIcon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-amber-500 text-white shrink-0 text-xl'; sIcon.textContent = '🟡'; sLabel.textContent = s.status_label; sLabel.className = 'text-sm font-bold text-amber-700'; sDesc.textContent = s.message; sDesc.className = 'text-[11px] text-amber-500 leading-tight'; sDot.className = 'h-2 w-2 rounded-full bg-amber-500 transition-colors'; }
                if (s.diseases && s.diseases.length > 0) { dBox.classList.remove('hidden'); let h = ''; s.diseases.forEach(d => { const p = d.accuracy, bc = p >= 60 ? 'bg-red-500' : 'bg-amber-500', tc = p >= 60 ? 'text-red-700' : 'text-amber-700', bg = p >= 60 ? 'bg-red-50' : 'bg-amber-50'; h += `<div class="p-2.5 rounded-lg ${bg} border border-gray-100"><div class="flex items-center justify-between mb-1.5"><span class="text-xs font-bold ${tc}">🦠 ${d.name}</span><span class="text-xs font-bold ${tc}">${p}%</span></div><div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden"><div class="h-full ${bc} rounded-full transition-all duration-700" style="width: ${p}%"></div></div></div>`; }); dList.innerHTML = h; } else { dBox.classList.add('hidden'); }
                document.getElementById('plant_total_detections').textContent = data.total_detections || '0';
                document.getElementById('plant_last_scan_time').textContent = data.timestamp_formatted || '--';
                if (data.image) { const img = document.getElementById('plant_scan_image'), ph = document.getElementById('plant_image_placeholder'); img.src = data.image + '?t=' + Date.now(); img.classList.remove('hidden'); ph.classList.add('hidden'); }
            }
            window.togglePlantImageZoom = function() { const c = document.getElementById('plant_image_container'), i = document.getElementById('plant_scan_image'); plantImageZoomed = !plantImageZoomed; if (plantImageZoomed) { c.style.maxHeight = 'none'; i.style.maxHeight = 'none'; i.classList.add('ring-2', 'ring-lime-400'); } else { i.style.maxHeight = '450px'; i.classList.remove('ring-2', 'ring-lime-400'); } };

            // ─── 8. SOLAR PANEL DATA FETCH (display only) ────────────────────────
            window.fetchSolarData = function() {
                return fetch('/api/solar').then(r => r.json()).then(data => {
                    if (data.error) return;
                    
                    if (data.updated_at) {
                        if (window.lastSolarUpdate === data.updated_at) return;
                        window.lastSolarUpdate = data.updated_at;
                        document.getElementById('solar_updated_at').textContent = data.updated_at;
                    }
                    
                    document.getElementById('val_solar_w').innerHTML = `${data.pv_power} <span class="text-sm font-medium text-gray-400">W</span>`;
                    document.getElementById('val_load_w').innerHTML = `${data.load_power} <span class="text-sm font-medium text-gray-400">W</span>`;
                    document.getElementById('val_battery_pct').textContent = `${data.battery_percentage}%`;
                    document.getElementById('battery_bar_fill').style.width = `${data.battery_percentage}%`;

                    // Push to solar chart
                    const now = new Date().toISOString();
                    solarChart.data.labels.push(now);
                    solarChart.data.datasets[0].data.push(parseFloat(data.pv_power));
                    solarChart.data.datasets[0].pointRadius.push(0); solarChart.data.datasets[0].pointHitRadius.push(0); solarChart.data.datasets[0].pointHoverRadius.push(0);
                    
                    solarChart.data.datasets[1].data.push(parseFloat(data.load_power));
                    solarChart.data.datasets[1].pointRadius.push(0); solarChart.data.datasets[1].pointHitRadius.push(0); solarChart.data.datasets[1].pointHoverRadius.push(0);
                    
                    solarChart.data.datasets[2].data.push(parseFloat(data.battery_percentage));
                    solarChart.data.datasets[2].pointRadius.push(0); solarChart.data.datasets[2].pointHitRadius.push(0); solarChart.data.datasets[2].pointHoverRadius.push(0);

                    if (solarChart.data.labels.length > 200) {
                        solarChart.data.labels.shift();
                        solarChart.data.datasets.forEach(d => {
                            d.data.shift(); d.pointRadius.shift(); d.pointHitRadius.shift(); d.pointHoverRadius.shift();
                        });
                    }
                    solarChart.update('none');

                }).catch(err => console.error('Solar data fetch error:', err));
            };

            // ─── INIT ────────────────────────────────────────────────────────────
            fetchBmkgForecast(); fetchAiEnergyAnalysis(); fetchPlantScan();
            setInterval(fetchBmkgForecast, 30 * 60 * 1000);
            setInterval(fetchAiEnergyAnalysis, 30 * 1000);
            setInterval(fetchPlantScan, 30 * 1000);

            // Safe Polling for Telemetry (no race conditions)
            const refreshIntervalMs = {{ $setting->interval_ms ?? 60000 }};
            async function pollTelemetry() {
                try {
                    await Promise.all([fetchSolarData(), fetchSensorData()]);
                } catch (e) {
                    console.error('Telemetry polling error:', e);
                } finally {
                    setTimeout(pollTelemetry, refreshIntervalMs);
                }
            }
            pollTelemetry();

        });
    </script>

    {{-- ═══════ PUMP MODAL ═══════ --}}
    <div id="pump_modal_backdrop" class="pump-modal-backdrop" onclick="if(event.target===this) closePumpModal()">
        <div class="pump-modal">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 id="modal_pump_title" class="text-lg font-bold text-gray-900">Pump</h4>
                    <p id="modal_pump_subtitle" class="text-xs text-gray-400">-</p>
                </div>
                <button onclick="closePumpModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Durasi</label>
            <div class="flex items-center gap-2 mb-3">
                <input id="modal_duration_input" type="number" min="1" max="999" placeholder="Contoh: 6"
                    class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-lg font-bold text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>

            <div class="flex gap-2 mb-4">
                <button id="unit_sec" onclick="selectUnit('sec')" class="unit-btn flex-1 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 selected">Detik</button>
                <button id="unit_min" onclick="selectUnit('min')" class="unit-btn flex-1 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600">Menit</button>
            </div>

            <div id="modal_history_section" class="hidden mb-4">
                <p class="text-[11px] text-gray-400 uppercase tracking-wide font-semibold mb-1">Terakhir digunakan</p>
                <button onclick="useHistory()" class="w-full flex items-center justify-between p-2.5 rounded-lg bg-gray-50 border border-gray-200 hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <span id="modal_history_text" class="text-sm font-medium text-gray-700 group-hover:text-blue-700">-</span>
                    <span class="text-[10px] font-semibold text-gray-400 group-hover:text-blue-500 uppercase">Pakai</span>
                </button>
            </div>

            <div class="flex gap-2">
                <button onclick="closePumpModal()" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                <button onclick="confirmPump()" class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 shadow-sm transition-colors">Kirim</button>
            </div>
        </div>
    </div>

</x-app-layout>
