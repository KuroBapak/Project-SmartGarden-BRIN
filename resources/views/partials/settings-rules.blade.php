<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- LEFT: Preset Management --}}
    <div class="lg:col-span-1">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Preset Tanaman</h3>
                            <p class="text-xs text-gray-500">Kelola template aturan</p>
                        </div>
                    </div>
                    <button @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah
                    </button>
                </div>

                @if($presets->isEmpty())
                    <div class="text-center py-8 px-4">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="mt-2 text-sm text-gray-500">Belum ada preset.</p>
                        <p class="text-xs text-gray-400">Klik "Tambah" untuk membuat preset pertama.</p>
                    </div>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                        @foreach($presets as $preset)
                            <div class="preset-tag flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-pointer group" @click="applyPreset({{ $preset->toJson() }})">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                                        <span class="text-white text-xs font-bold">{{ strtoupper(substr($preset->name, 0, 2)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $preset->name }}</p>
                                        <p class="text-xs text-gray-400">{{ count($preset->rules ?? []) }} aturan</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <button @click.stop="openEditModal({{ $preset->toJson() }})" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click.stop="openDeleteModal({{ $preset->id }}, '{{ $preset->name }}')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- RIGHT: Dynamic Rule Builder --}}
    <div class="lg:col-span-2">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Automation Rules</h3>
                            <p class="text-xs text-gray-500">Aturan otomasi aktuator — Pulse & Check</p>
                        </div>
                    </div>
                    <button @click="addRule()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-600 text-white rounded-lg text-xs font-semibold hover:bg-violet-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Aturan
                    </button>
                </div>

                {{-- Active Preset Badge --}}
                <div class="mb-4" x-show="activePresetName" x-cloak>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-full text-sm">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        <span class="text-emerald-700 font-medium">Preset: <span x-text="activePresetName" class="font-bold"></span></span>
                        <button @click="clearPreset()" class="ml-1 text-emerald-400 hover:text-emerald-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Rules Table --}}
                <div x-show="formData.rules.length === 0" class="text-center py-8 text-gray-400">
                    <p class="text-sm">Belum ada aturan. Klik "Tambah Aturan" untuk mulai.</p>
                </div>

                <div class="space-y-3" x-show="formData.rules.length > 0">
                    <template x-for="(rule, idx) in formData.rules" :key="idx">
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 hover:border-violet-200 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide" x-text="'Aturan #' + (idx+1)"></span>
                                <button @click="removeRule(idx)" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            {{-- Row 1: Sensor + Condition + Value --}}
                            <div class="grid grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Sensor</label>
                                    <select x-model="rule.sensor" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                        <option value="ph">pH</option>
                                        <option value="tds">TDS</option>
                                        <option value="turbidity">Turbidity</option>
                                        <option value="water_temp">Water Temp</option>
                                        <option value="air_temp">Air Temp</option>
                                        <option value="humidity">Humidity</option>
                                        <option value="light">Light</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Kondisi</label>
                                    <select x-model="rule.condition" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                        <option value="<">Di bawah (&lt;) Min</option>
                                        <option value=">">Di atas (&gt;) Max</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Threshold</label>
                                    <input type="number" step="any" x-model="rule.value" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="6.0">
                                </div>
                            </div>
                            {{-- Row 2: Pump + Pulse + Stabilize + Max + Cooldown --}}
                            <div class="grid grid-cols-5 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Target Pump</label>
                                    <select x-model="rule.pump" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                        <template x-for="p in 4" :key="p">
                                            <option :value="p" x-text="formData.pump_names['pump_'+p] || ('Pump '+p)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Pulse (s)</label>
                                    <input type="number" min="1" max="60" x-model="rule.pulse" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Stabilize (s)</label>
                                    <input type="number" min="1" max="120" x-model="rule.stabilize" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Max Pulse</label>
                                    <input type="number" min="1" max="100" x-model="rule.max_pulses" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cooldown (s)</label>
                                    <input type="number" min="0" max="3600" x-model="rule.cooldown" class="w-full text-sm border-gray-300 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
