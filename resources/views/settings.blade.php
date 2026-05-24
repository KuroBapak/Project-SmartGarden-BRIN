<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pengaturan Perangkat') }}</h2>
    </x-slot>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .modal-backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .settings-card { transition: box-shadow 0.3s ease, transform 0.2s ease; }
        .settings-card:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .input-glow:focus { box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .preset-tag { transition: all 0.2s ease; }
        .preset-tag:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .tab-btn { transition: all 0.2s ease; }
        .tab-btn.active { border-bottom: 2px solid #6366f1; color: #4f46e5; font-weight: 600; }
    </style>

    <div class="py-12" x-data="settingsApp()" @mqtt-telemetry.window="updateRaw($event.detail)">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('status') === 'config-updated')
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span><span class="font-medium">Berhasil!</span> Konfigurasi tersimpan dan dikirim ke perangkat.</span>
                </div>
            @endif
            @if (session('status') === 'preset-created')
                <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center gap-2">✅ Preset tanaman baru berhasil ditambahkan.</div>
            @endif
            @if (session('status') === 'preset-updated')
                <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center gap-2">✅ Preset tanaman berhasil diperbarui.</div>
            @endif
            @if (session('status') === 'preset-deleted')
                <div class="p-4 text-sm text-orange-800 rounded-lg bg-orange-50 border border-orange-200 flex items-center gap-2">⚠️ Preset tanaman berhasil dihapus.</div>
            @endif
            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
                    <ul class="list-disc pl-5 space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Sub-Tab Navigation --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex border-b border-gray-200 px-4">
                    <button type="button" @click="activeTab = 'device'" :class="activeTab === 'device' ? 'active' : ''" class="tab-btn px-4 py-3 text-sm text-gray-600 hover:text-indigo-600">
                        ⚙️ Perangkat & Pompa
                    </button>
                    <button type="button" @click="activeTab = 'rules'" :class="activeTab === 'rules' ? 'active' : ''" class="tab-btn px-4 py-3 text-sm text-gray-600 hover:text-indigo-600">
                        🌱 Preset & Aturan
                    </button>
                    <button type="button" @click="activeTab = 'calibration'" :class="activeTab === 'calibration' ? 'active' : ''" class="tab-btn px-4 py-3 text-sm text-gray-600 hover:text-indigo-600">
                        🔬 Kalibrasi Sensor
                    </button>
                </div>
            </div>

            {{-- Dynamic Tutorial Card --}}
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-start gap-4 transition-all duration-300">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-indigo-900 mb-1" x-text="
                        activeTab === 'device' ? 'Panduan Pengaturan Perangkat' :
                        (activeTab === 'rules' ? 'Panduan Preset & Aturan Otomasi' : 'Panduan Kalibrasi Sensor')
                    "></h4>
                    
                    {{-- Device Tab Tutorial --}}
                    <div x-show="activeTab === 'device'" class="text-sm text-indigo-700 leading-relaxed space-y-1">
                        <p>Tab ini digunakan untuk mengatur identitas dan interval komunikasi dasar perangkat.</p>
                        <ul class="list-disc pl-4 space-y-1 mt-2">
                            <li><strong>Telemetry Interval:</strong> Waktu jeda (dalam milidetik) antara pengiriman data sensor dari ESP32 ke server. Standar yang disarankan adalah 5000ms (5 detik).</li>
                            <li><strong>Nama Pompa:</strong> Berikan label yang mudah dibaca untuk keempat aktuator Anda (misal: "Pompa Nutrisi A", "Kipas Pendingin"). Nama ini akan tampil di Dashboard utama.</li>
                        </ul>
                    </div>

                    {{-- Rules Tab Tutorial --}}
                    <div x-show="activeTab === 'rules'" x-cloak class="text-sm text-indigo-700 leading-relaxed space-y-1">
                        <p>Aturan otomasi bekerja secara berurutan mengevaluasi sensor setiap detik menggunakan algoritma <strong>Pulse & Check</strong> agar nutrisi tidak *overdosis*.</p>
                        <ul class="list-disc pl-4 space-y-1 mt-2">
                            <li><strong>Pulse (s):</strong> Durasi aktuator dinyalakan saat sensor melewati batas (threshold).</li>
                            <li><strong>Stabilize (s):</strong> Waktu tunggu (jeda) setelah Pulse agar larutan tercampur rata dan sensor stabil membaca efeknya.</li>
                            <li><strong>Max Pulse:</strong> Batas maksimal siklus penyiraman berturut-turut. Mencegah tangki meluap jika sensor rusak.</li>
                            <li><strong>Cooldown (s):</strong> Masa istirahat/jeda panjang jika siklus mencapai batas Max Pulse.</li>
                        </ul>
                    </div>

                    {{-- Calibration Tab Tutorial --}}
                    <div x-show="activeTab === 'calibration'" x-cloak class="text-sm text-indigo-700 leading-relaxed space-y-1">
                        <p>Sistem ini menggunakan metode <strong>Real-Time Raw Voltage Calibration</strong> yang berarti kalibrasi dilakukan di web tanpa perlu mengubah kode ESP32.</p>
                        <ul class="list-disc pl-4 space-y-1 mt-2">
                            <li><strong>pH (2-Titik):</strong> Celupkan probe pH ke buffer pertama (misal 6.86), tunggu sampai Live Raw Voltage stabil, lalu klik "Ambil". Ulangi untuk buffer kedua.</li>
                            <li><strong>TDS (1-Titik):</strong> Masukkan nilai ppm larutan standar, celupkan probe TDS, lalu klik "Hitung K" untuk menyesuaikan kemiringan grafik (K-Value).</li>
                            <li><strong>Turbidity (Zero-Point):</strong> Celupkan sensor ke dalam air bersih, klik "Set Zero" untuk merekam batas 0 NTU.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Hidden form for POST --}}
            <form method="POST" action="{{ route('settings.config') }}" id="configForm" @submit.prevent="saveConfig($event.target)">
                @csrf
                <input type="hidden" name="device_id" value="{{ $setting->device_id }}">
                <input type="hidden" name="interval_ms" :value="formData.interval_ms">
                <template x-for="p in 4" :key="p">
                    <input type="hidden" :name="'pump_names[pump_'+p+']'" :value="formData.pump_names['pump_'+p]">
                </template>
                <input type="hidden" name="rules" :value="JSON.stringify(formData.rules)">
                <input type="hidden" name="calibration" :value="JSON.stringify(formData.calibration)">
            </form>

            {{-- Tab Content --}}
            <div x-show="activeTab === 'device'" x-cloak>
                @include('partials.settings-device')
            </div>
            <div x-show="activeTab === 'rules'" x-cloak>
                @include('partials.settings-rules')
            </div>
            <div x-show="activeTab === 'calibration'" x-cloak>
                @include('partials.settings-calibration')
            </div>

            {{-- Global Save Button --}}
            <div class="flex items-center justify-end">
                <button type="submit" form="configForm" id="saveConfigBtn"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-800 transition shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Simpan & Sinkronisasi
                </button>
            </div>
        </div>

        {{-- ══════════ MODAL: Create / Edit Preset ══════════ --}}
        <div x-show="showPresetModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @keydown.escape.window="showPresetModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto transform"
                 @click.outside="showPresetModal = false">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingPresetId ? 'Edit Preset' : 'Tambah Preset Baru'"></h3>
                    <button @click="showPresetModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form :action="editingPresetId ? '{{ url('plant-presets') }}/' + editingPresetId : '{{ route('plant-presets.store') }}'" method="POST" class="p-5 space-y-4">
                    @csrf
                    <template x-if="editingPresetId"><input type="hidden" name="_method" value="PUT"></template>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Nama Preset</label>
                        <input type="text" name="name" x-model="presetForm.name" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" placeholder="Contoh: Tomat Cherry" required>
                    </div>



                    {{-- Preset Rules --}}
                    <div class="border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-gray-700">Aturan Preset</p>
                            <button type="button" @click="addPresetRule()" class="text-xs text-violet-600 font-semibold hover:text-violet-800">+ Tambah</button>
                        </div>
                        <template x-for="(rule, idx) in presetForm.rules" :key="idx">
                            <div class="grid grid-cols-6 gap-2 mb-2 items-end">
                                <select :name="'rules['+idx+'][sensor]'" x-model="rule.sensor" class="text-xs border-gray-300 rounded-lg">
                                    <option value="ph">pH</option><option value="tds">TDS</option><option value="turbidity">Turbidity</option>
                                    <option value="water_temp">Water Temp</option><option value="air_temp">Air Temp</option>
                                    <option value="humidity">Humidity</option><option value="light">Light</option>
                                </select>
                                <select :name="'rules['+idx+'][condition]'" x-model="rule.condition" class="text-xs border-gray-300 rounded-lg">
                                    <option value="<">&lt; Min</option><option value=">">&gt; Max</option>
                                </select>
                                <input type="number" step="any" :name="'rules['+idx+'][value]'" x-model="rule.value" class="text-xs border-gray-300 rounded-lg" placeholder="Value">
                                <select :name="'rules['+idx+'][pump]'" x-model="rule.pump" class="text-xs border-gray-300 rounded-lg w-24">
                                    <template x-for="p in 4"><option :value="p" x-text="formData.pump_names['pump_'+p] || ('Pump '+p)"></option></template>
                                </select>
                                <input type="number" :name="'rules['+idx+'][pulse]'" x-model="rule.pulse" class="text-xs border-gray-300 rounded-lg" placeholder="Pulse(s)">
                                <div class="flex gap-1">
                                    <input type="number" :name="'rules['+idx+'][stabilize]'" x-model="rule.stabilize" class="text-xs border-gray-300 rounded-lg w-full" placeholder="Stab(s)">
                                    <input type="hidden" :name="'rules['+idx+'][max_pulses]'" x-model="rule.max_pulses">
                                    <input type="hidden" :name="'rules['+idx+'][cooldown]'" x-model="rule.cooldown">
                                    <button type="button" @click="presetForm.rules.splice(idx,1)" class="shrink-0 p-1 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showPresetModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow-sm">
                            <span x-text="editingPresetId ? 'Simpan Perubahan' : 'Tambah Preset'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══════════ MODAL: Delete Confirmation ══════════ --}}
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition @keydown.escape.window="showDeleteModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm" @click.outside="showDeleteModal = false">
                <div class="p-6 text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Hapus Preset?</h3>
                    <p class="text-sm text-gray-500 mb-5">Hapus <span class="font-bold text-gray-700" x-text="deletingPresetName"></span>?</p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                        <form :action="'{{ url('plant-presets') }}/' + deletingPresetId" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-sm">Ya, Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ MQTT + Alpine.js Logic ══════════ --}}
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script>
        function settingsApp() {
            return {
                activeTab: 'device',
                formData: {
                    interval_ms: {{ old('interval_ms', $setting->interval_ms) }},
                    pump_names: @json($setting->pump_names ?? \App\Models\DeviceSetting::defaultPumpNames()),
                    rules: @json($setting->rules ?? []),
                    calibration: @json($setting->calibration ?? \App\Models\DeviceSetting::defaultCalibration()),
                },
                activePresetName: null,
                liveRaw: { ph_mv: '--', tds_v: '--', turb_v: '--' },
                calHelper: { tds_standard: 500 },

                updateRaw(p) {
                    if (p.raw_ph_mv !== undefined) this.liveRaw.ph_mv = parseFloat(p.raw_ph_mv).toFixed(1);
                    if (p.raw_tds_v !== undefined) this.liveRaw.tds_v = parseFloat(p.raw_tds_v).toFixed(3);
                    if (p.raw_turb_v !== undefined) this.liveRaw.turb_v = parseFloat(p.raw_turb_v).toFixed(3);
                },

                // Preset Modal
                showPresetModal: false,
                editingPresetId: null,
                presetForm: { name: '', rules: [] },

                // Delete Modal
                showDeleteModal: false,
                deletingPresetId: null,
                deletingPresetName: '',

                // Default rule template
                newRule() {
                    return { sensor: 'ph', condition: '<', value: 6.0, pump: 1, pulse: 3, stabilize: 5, max_pulses: 10, cooldown: 300 };
                },

                addRule() { this.formData.rules.push(this.newRule()); },
                removeRule(idx) { this.formData.rules.splice(idx, 1); },
                addPresetRule() { this.presetForm.rules.push(this.newRule()); },

                applyPreset(preset) {
                    this.formData.rules = JSON.parse(JSON.stringify(preset.rules || []));
                    this.activePresetName = preset.name;
                },

                clearPreset() { this.activePresetName = null; },

                openCreateModal() {
                    this.editingPresetId = null;
                    this.presetForm = { name: '', rules: [] };
                    this.showPresetModal = true;
                },

                openEditModal(preset) {
                    this.editingPresetId = preset.id;
                    this.presetForm = {
                        name: preset.name,
                        rules: JSON.parse(JSON.stringify(preset.rules || [])),
                    };
                    this.showPresetModal = true;
                },

                openDeleteModal(id, name) {
                    this.deletingPresetId = id;
                    this.deletingPresetName = name;
                    this.showDeleteModal = true;
                },

                autoCalibrateTDS() {
                    const rawV = parseFloat(this.liveRaw.tds_v);
                    if (isNaN(rawV) || rawV <= 0) { alert('Belum ada data raw TDS dari sensor!'); return; }
                    const std = parseFloat(this.calHelper.tds_standard);
                    if (isNaN(std) || std <= 0) { alert('Masukkan nilai larutan standar!'); return; }
                    // Polynomial formula (same as ESP32)
                    const v = rawV;
                    const tdsBase = (133.42 * Math.pow(v,3) - 255.86 * Math.pow(v,2) + 857.39 * v) * 0.417;
                    if (tdsBase <= 0) { alert('Voltage terlalu rendah untuk kalibrasi'); return; }
                    this.formData.calibration.tds.k = parseFloat((std / tdsBase).toFixed(4));
                },

                saveConfig(form) {
                    const btn = document.getElementById('saveConfigBtn');
                    btn.disabled = true;
                    btn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Syncing...';

                    // Prepare hidden inputs for rules array
                    form.querySelectorAll('.dynamic-rule-input').forEach(el => el.remove());
                    this.formData.rules.forEach((rule, idx) => {
                        Object.entries(rule).forEach(([key, val]) => {
                            const inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = `rules[${idx}][${key}]`;
                            inp.value = val;
                            inp.className = 'dynamic-rule-input';
                            form.appendChild(inp);
                        });
                    });

                    // Disable the json fields so they don't submit natively as strings
                    form.querySelector('[name="rules"]').disabled = true;

                    if (!window.mqttClient || !window.mqttClient.connected) {
                        console.warn('MQTT not connected, saving to DB only');
                        form.submit();
                        return;
                    }

                    const cal = this.formData.calibration;
                    const cfgPayload = {
                        action: 'set_config',
                        interval: parseInt(this.formData.interval_ms),
                        preset: this.activePresetName || 'default',
                        cal: {
                            ph: { 
                                p1_ph: parseFloat(cal.ph.p1_ph), 
                                p1_mv: parseFloat(cal.ph.p1_mv), 
                                p2_ph: parseFloat(cal.ph.p2_ph), 
                                p2_mv: parseFloat(cal.ph.p2_mv) 
                            },
                            tds: { k: parseFloat(cal.tds.k) },
                            turb: { zero_v: parseFloat(cal.turb.zero_v) },
                        },
                        rules: this.formData.rules.map(r => ({
                            s: r.sensor, c: r.condition, v: parseFloat(r.value),
                            p: parseInt(r.pump), pulse: parseInt(r.pulse),
                            stab: parseInt(r.stabilize), max: parseInt(r.max_pulses), cd: parseInt(r.cooldown)
                        }))
                    };

                    window.mqttClient.publish(`brin/water/{{ $setting->device_id }}/down/cmd`, JSON.stringify(cfgPayload), {qos: 0}, function(err) {
                        if (!err) {
                            console.log('Config synced via MQTT');
                            form.submit();
                        } else {
                            alert('MQTT sync gagal!');
                            btn.disabled = false;
                            btn.innerHTML = 'Simpan & Sinkronisasi';
                            form.querySelector('[name="rules"]').disabled = false;
                        }
                    });
                }
            };
        }

        // MQTT for live raw values + sync config
        document.addEventListener('DOMContentLoaded', function() {
            const mqttOptions = {
                keepalive: 60,
                clientId: '{{ config("services.mqtt.client_id") }}' + '-cfg-' + Math.random().toString(16).substr(2,6),
                protocolId: 'MQTT', protocolVersion: 4, clean: true, reconnectPeriod: 1000, connectTimeout: 30000,
                username: '{{ config("services.mqtt.username") }}',
                password: '{{ config("services.mqtt.password") }}',
            };

            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttWsPort = '{{ config("services.mqtt.ws_port") }}';
            const brokerUrl = mqttWsPort ? `ws://${mqttHost}:${mqttWsPort}/mqtt` : `wss://${mqttHost}/mqtt`;
            const client = mqtt.connect(brokerUrl, mqttOptions);
            window.mqttClient = client; // Expose globally for Alpine JS

            client.on('connect', function() {
                console.log('Settings: MQTT connected');
                client.subscribe('brin/water/+/up/telemetry');
            });

            // Update live raw values from telemetry via Custom Event
            client.on('message', function(topic, message) {
                try {
                    const p = JSON.parse(message.toString());
                    window.dispatchEvent(new CustomEvent('mqtt-telemetry', { detail: p }));
                } catch(e) { console.error('MQTT Parse Error:', e); }
            });
        });
    </script>
</x-app-layout>
