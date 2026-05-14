<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Settings') }}
        </h2>
    </x-slot>

    {{-- Alpine.js for modals --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Smooth modal backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        /* Subtle card hover */
        .settings-card {
            transition: box-shadow 0.3s ease, transform 0.2s ease;
        }
        .settings-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }
        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        /* Preset tag styles */
        .preset-tag {
            transition: all 0.2s ease;
        }
        .preset-tag:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>

    <div class="py-12" x-data="settingsApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('status') === 'config-updated')
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span><span class="font-medium">Berhasil!</span> Konfigurasi tersimpan dan dikirim ke perangkat.</span>
                </div>
            @endif
            @if (session('status') === 'preset-created')
                <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <span><span class="font-medium">Berhasil!</span> Preset tanaman baru berhasil ditambahkan.</span>
                </div>
            @endif
            @if (session('status') === 'preset-updated')
                <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <span><span class="font-medium">Berhasil!</span> Preset tanaman berhasil diperbarui.</span>
                </div>
            @endif
            @if (session('status') === 'preset-deleted')
                <div class="p-4 text-sm text-orange-800 rounded-lg bg-orange-50 border border-orange-200 flex items-center gap-2" role="alert">
                    <svg class="w-5 h-5 text-orange-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <span>Preset tanaman berhasil dihapus.</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200" role="alert">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- LEFT COLUMN: General Settings --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="lg:col-span-1 space-y-6">

                    {{-- General Settings Card --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg settings-card">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">General Settings</h3>
                                    <p class="text-xs text-gray-500">Pengaturan umum perangkat</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('settings.config') }}" id="configForm">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $setting->device_id }}">
                                {{-- These hidden fields will be filled by Alpine from the right panel --}}
                                <input type="hidden" name="min_ph" :value="formData.min_ph">
                                <input type="hidden" name="min_tds" :value="formData.min_tds">
                                <input type="hidden" name="max_turb" :value="formData.max_turb">
                                <input type="hidden" name="max_temp" :value="formData.max_temp">

                                <div class="mb-5">
                                    <label for="interval_ms" class="block font-medium text-sm text-gray-700 mb-1">Telemetry Interval</label>
                                    <div class="relative">
                                        <input id="interval_ms" type="number" name="interval_ms"
                                            class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full pr-12"
                                            value="{{ old('interval_ms', $setting->interval_ms) }}" required min="1000">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-sm">ms</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1.5">Contoh: 60000 = 60 detik | 300000 = 5 menit</p>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Preset Management Card --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg settings-card">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Preset Tanaman</h3>
                                        <p class="text-xs text-gray-500">Kelola template parameter</p>
                                    </div>
                                </div>
                                <button @click="openCreateModal()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
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
                                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                    @foreach($presets as $preset)
                                        <div class="preset-tag flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 cursor-pointer group"
                                             @click="applyPreset({{ $preset->toJson() }})">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                                                    <span class="text-white text-xs font-bold">{{ strtoupper(substr($preset->name, 0, 2)) }}</span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $preset->name }}</p>
                                                    <p class="text-xs text-gray-400">pH {{ $preset->min_ph }} · TDS {{ $preset->min_tds }} · Turb {{ $preset->max_turb }} · {{ $preset->max_temp }}°C</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                <button @click.stop="openEditModal({{ $preset->toJson() }})"
                                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click.stop="openDeleteModal({{ $preset->id }}, '{{ $preset->name }}')"
                                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition" title="Hapus">
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

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- RIGHT COLUMN: Automation Parameters --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg settings-card">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Automation Parameters</h3>
                                    <p class="text-xs text-gray-500">Parameter otomasi aktuator — pilih preset atau isi manual</p>
                                </div>
                            </div>

                            {{-- Active Preset Badge --}}
                            <div class="mb-5" x-show="activePresetName" x-cloak>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-full text-sm">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    <span class="text-emerald-700 font-medium">Preset aktif: <span x-text="activePresetName" class="font-bold"></span></span>
                                    <button @click="clearPreset()" class="ml-1 text-emerald-400 hover:text-emerald-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Parameter Grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Min pH --}}
                                <div>
                                    <label for="field_min_ph" class="block font-medium text-sm text-gray-700 mb-1">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                            Minimum pH
                                        </span>
                                    </label>
                                    <input id="field_min_ph" type="number" step="0.1"
                                        class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                        x-model="formData.min_ph" required>
                                    <p class="text-xs text-gray-400 mt-1">Pompa pH aktif jika di bawah nilai ini</p>
                                </div>

                                {{-- Min TDS --}}
                                <div>
                                    <label for="field_min_tds" class="block font-medium text-sm text-gray-700 mb-1">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                                            Minimum TDS
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input id="field_min_tds" type="number" step="1"
                                            class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full pr-14"
                                            x-model="formData.min_tds" required>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-sm">ppm</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Pompa nutrisi aktif jika di bawah nilai ini</p>
                                </div>

                                {{-- Max Turbidity --}}
                                <div>
                                    <label for="field_max_turb" class="block font-medium text-sm text-gray-700 mb-1">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                                            Maximum Turbidity
                                        </span>
                                    </label>
                                    <input id="field_max_turb" type="number" step="0.1"
                                        class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                        x-model="formData.max_turb" required>
                                    <p class="text-xs text-gray-400 mt-1">Filter air aktif jika melebihi nilai ini</p>
                                </div>

                                {{-- Max Temp --}}
                                <div>
                                    <label for="field_max_temp" class="block font-medium text-sm text-gray-700 mb-1">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                            Maximum Temperature
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input id="field_max_temp" type="number" step="0.1"
                                            class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full pr-10"
                                            x-model="formData.max_temp" required>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-sm">°C</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Sprayer mist aktif jika melebihi nilai ini</p>
                                </div>
                            </div>

                            {{-- Save Button --}}
                            <div class="flex items-center justify-end mt-6 pt-5 border-t border-gray-100">
                                <button type="submit" form="configForm" id="saveConfigBtn"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition ease-in-out duration-150 shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Save & Sync Config
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> {{-- end grid --}}
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- MODAL: Create / Edit Preset --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="showPresetModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @keydown.escape.window="showPresetModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg transform"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 @click.outside="showPresetModal = false">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingPresetId ? 'Edit Preset' : 'Tambah Preset Baru'"></h3>
                    <button @click="showPresetModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <form :action="editingPresetId ? '{{ url('plant-presets') }}/' + editingPresetId : '{{ route('plant-presets.store') }}'" method="POST" class="p-5 space-y-4">
                    @csrf
                    <template x-if="editingPresetId">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Nama Tanaman</label>
                        <input type="text" name="name" x-model="presetForm.name"
                            class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                            placeholder="Contoh: Tomat Cherry" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Min pH</label>
                            <input type="number" step="0.1" name="min_ph" x-model="presetForm.min_ph"
                                class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                placeholder="6.0" required>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Min TDS (ppm)</label>
                            <input type="number" step="1" name="min_tds" x-model="presetForm.min_tds"
                                class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                placeholder="300" required>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Max Turbidity</label>
                            <input type="number" step="0.1" name="max_turb" x-model="presetForm.max_turb"
                                class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                placeholder="25.0" required>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Max Temp (°C)</label>
                            <input type="number" step="0.1" name="max_temp" x-model="presetForm.max_temp"
                                class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full"
                                placeholder="30.0" required>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showPresetModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow-sm">
                            <span x-text="editingPresetId ? 'Simpan Perubahan' : 'Tambah Preset'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- MODAL: Delete Confirmation --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="showDeleteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @keydown.escape.window="showDeleteModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm transform"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 @click.outside="showDeleteModal = false">
                <div class="p-6 text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Hapus Preset?</h3>
                    <p class="text-sm text-gray-500 mb-5">
                        Apakah yakin ingin menghapus preset <span class="font-bold text-gray-700" x-text="deletingPresetName"></span>? Tindakan ini tidak bisa dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <form :action="'{{ url('plant-presets') }}/' + deletingPresetId" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-sm">
                                Ya, Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div> {{-- end x-data --}}

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- MQTT + Alpine.js Logic --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script>
        function settingsApp() {
            return {
                // Form state bound to the Automation Parameters inputs
                formData: {
                    min_ph: {{ old('min_ph', $setting->min_ph) }},
                    min_tds: {{ old('min_tds', $setting->min_tds) }},
                    max_turb: {{ old('max_turb', $setting->max_turb) }},
                    max_temp: {{ old('max_temp', $setting->max_temp ?? 30) }},
                },
                activePresetName: null,

                // Preset Modal state
                showPresetModal: false,
                editingPresetId: null,
                presetForm: { name: '', min_ph: '', min_tds: '', max_turb: '', max_temp: '' },

                // Delete Modal state
                showDeleteModal: false,
                deletingPresetId: null,
                deletingPresetName: '',

                // Apply a preset's values to the main form
                applyPreset(preset) {
                    this.formData.min_ph = preset.min_ph;
                    this.formData.min_tds = preset.min_tds;
                    this.formData.max_turb = preset.max_turb;
                    this.formData.max_temp = preset.max_temp;
                    this.activePresetName = preset.name;
                },

                clearPreset() {
                    this.activePresetName = null;
                },

                // Modal actions
                openCreateModal() {
                    this.editingPresetId = null;
                    this.presetForm = { name: '', min_ph: '', min_tds: '', max_turb: '', max_temp: '' };
                    this.showPresetModal = true;
                },

                openEditModal(preset) {
                    this.editingPresetId = preset.id;
                    this.presetForm = {
                        name: preset.name,
                        min_ph: preset.min_ph,
                        min_tds: preset.min_tds,
                        max_turb: preset.max_turb,
                        max_temp: preset.max_temp,
                    };
                    this.showPresetModal = true;
                },

                openDeleteModal(id, name) {
                    this.deletingPresetId = id;
                    this.deletingPresetName = name;
                    this.showDeleteModal = true;
                },
            };
        }

        // MQTT WebSocket connection for syncing config to ESP32
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

            const mqttHost = '{{ config("services.mqtt.host") }}';
            const mqttWsPort = '{{ config("services.mqtt.ws_port") }}';
            const brokerUrl = mqttWsPort
                ? `ws://${mqttHost}:${mqttWsPort}/mqtt`
                : `wss://${mqttHost}/mqtt`;

            const client = mqtt.connect(brokerUrl, mqttOptions);
            const pubTopic = `brin/water/{{ $setting->device_id }}/down/cmd`;

            client.on('connect', function () {
                console.log('Settings: Connected to MQTT via WebSockets!');
            });

            // Intercept Config Form to push MQTT before standard POST save
            const configForm = document.getElementById('configForm');
            configForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!client.connected) {
                    alert("MQTT Not Connected! Menunggu koneksi WebSocket...");
                    return;
                }

                const btn = document.getElementById('saveConfigBtn');
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Syncing...';

                const alpineData = Alpine.$data(document.querySelector('[x-data="settingsApp()"]'));
                const cfgPayload = {
                    action: 'set_config',
                    preset: alpineData.activePresetName || 'default',
                    interval: parseInt(document.getElementById('interval_ms').value),
                    min_ph: parseFloat(document.querySelector('[x-model="formData.min_ph"]').value),
                    min_tds: parseFloat(document.querySelector('[x-model="formData.min_tds"]').value),
                    max_turb: parseFloat(document.querySelector('[x-model="formData.max_turb"]').value),
                    max_temp: parseFloat(document.querySelector('[x-model="formData.max_temp"]').value),
                };

                client.publish(pubTopic, JSON.stringify(cfgPayload), {qos: 0}, function(err) {
                    if(!err) {
                        console.log('Config synced via WebSockets. Saving to DB...');
                        configForm.submit();
                    } else {
                        alert('Sync gagal via WebSockets!');
                        btn.disabled = false;
                        btn.innerHTML = 'Save & Sync Config';
                    }
                });
            });
        });
    </script>
</x-app-layout>
