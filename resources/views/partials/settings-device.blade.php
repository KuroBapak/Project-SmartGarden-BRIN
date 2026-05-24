<div class="space-y-6">
    {{-- General Settings --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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

            <div class="mb-5">
                <label for="interval_ms" class="block font-medium text-sm text-gray-700 mb-1">Telemetry Interval</label>
                <div class="relative">
                    <input id="interval_ms" type="number" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full pr-12" x-model="formData.interval_ms" required min="1000">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-sm">ms</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">60000 = 60 detik | 300000 = 5 menit</p>
            </div>

            <div class="mb-5">
                <label class="block font-medium text-sm text-gray-700 mb-1">Device ID</label>
                <input type="text" class="border-gray-200 bg-gray-50 rounded-lg shadow-sm block w-full text-gray-500" value="{{ $setting->device_id }}" readonly>
            </div>
        </div>
    </div>

    {{-- Pump Names --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Nama Pompa</h3>
                    <p class="text-xs text-gray-500">Label custom untuk tiap relay (tampilan di Dashboard)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template x-for="i in 4" :key="i">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :class="['bg-green-500','bg-yellow-500','bg-orange-500','bg-red-500'][i-1]"></span>
                                <span x-text="'Relay ' + i"></span>
                            </span>
                        </label>
                        <input type="text" class="input-glow border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm block w-full" x-model="formData.pump_names['pump_'+i]" :placeholder="'Pump ' + i">
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
