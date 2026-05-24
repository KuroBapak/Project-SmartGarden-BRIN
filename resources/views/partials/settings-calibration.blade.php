<div class="space-y-6">
    {{-- pH Calibration --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                    <span class="text-green-600 font-bold text-sm">pH</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Kalibrasi pH</h3>
                    <p class="text-xs text-gray-500">Auto-calibration 2 titik + kompensasi suhu Nernst</p>
                </div>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-green-700">Live Raw Voltage</span>
                    <span class="text-lg font-bold text-green-800" id="live_ph_mv" x-text="liveRaw.ph_mv + ' mV'">-- mV</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Buffer 1</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Nilai pH Buffer</label>
                            <input type="number" step="0.01" x-model="formData.calibration.ph.p1_ph" class="input-glow border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm block w-full" placeholder="6.86">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Voltage (mV)</label>
                            <div class="flex gap-2">
                                <input type="number" step="0.1" x-model="formData.calibration.ph.p1_mv" class="input-glow border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm block w-full" placeholder="1621">
                                <button type="button" @click="formData.calibration.ph.p1_mv = liveRaw.ph_mv" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700 transition">
                                    Ambil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Buffer 2</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Nilai pH Buffer</label>
                            <input type="number" step="0.01" x-model="formData.calibration.ph.p2_ph" class="input-glow border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm block w-full" placeholder="4.01">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Voltage (mV)</label>
                            <div class="flex gap-2">
                                <input type="number" step="0.1" x-model="formData.calibration.ph.p2_mv" class="input-glow border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm block w-full" placeholder="2117">
                                <button type="button" @click="formData.calibration.ph.p2_mv = liveRaw.ph_mv" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700 transition">
                                    Ambil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-lg bg-blue-50 border border-blue-200">
                <p class="text-xs text-blue-700">
                    <strong>Preview:</strong> Dengan kalibrasi ini,
                    <span x-text="formData.calibration.ph.p1_mv"></span> mV = pH <span x-text="formData.calibration.ph.p1_ph"></span>,
                    <span x-text="formData.calibration.ph.p2_mv"></span> mV = pH <span x-text="formData.calibration.ph.p2_ph"></span>
                </p>
            </div>
        </div>
    </div>

    {{-- TDS Calibration --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                    <span class="text-yellow-700 font-bold text-xs">TDS</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Kalibrasi TDS</h3>
                    <p class="text-xs text-gray-500">K-Value dari larutan standar</p>
                </div>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-yellow-700">Live Raw Voltage</span>
                    <span class="text-lg font-bold text-yellow-800" x-text="liveRaw.tds_v + ' V'">-- V</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Larutan Standar (ppm)</label>
                    <input type="number" step="1" x-model="calHelper.tds_standard" class="input-glow border-gray-300 focus:border-yellow-500 focus:ring-yellow-500 rounded-lg shadow-sm block w-full" placeholder="500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">K-Value</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.0001" x-model="formData.calibration.tds.k" class="input-glow border-gray-300 focus:border-yellow-500 focus:ring-yellow-500 rounded-lg shadow-sm block w-full" placeholder="1.1013">
                        <button type="button" @click="autoCalibrateTDS()" class="shrink-0 px-3 py-2 bg-yellow-600 text-white rounded-lg text-xs font-semibold hover:bg-yellow-700 transition">
                            Hitung K
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Turbidity Calibration --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <span class="text-orange-700 font-bold text-xs">NTU</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Kalibrasi Turbidity</h3>
                    <p class="text-xs text-gray-500">Zero-point dari air jernih</p>
                </div>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-orange-50 border border-orange-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-orange-700">Live Raw Voltage</span>
                    <span class="text-lg font-bold text-orange-800" x-text="liveRaw.turb_v + ' V'">-- V</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Zero Voltage (Air Jernih)</label>
                    <input type="number" step="0.001" x-model="formData.calibration.turb.zero_v" class="input-glow border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm block w-full" placeholder="2.1">
                </div>
                <div>
                    <button type="button" @click="formData.calibration.turb.zero_v = liveRaw.turb_v" class="w-full px-4 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition shadow-sm">
                        🔬 Kalibrasi Air Jernih (Set Zero)
                    </button>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-lg bg-blue-50 border border-blue-200">
                <p class="text-xs text-blue-700">
                    <strong>Info:</strong> Celup sensor di air jernih, lalu klik tombol di atas. Semua pembacaan di atas titik nol ini akan dihitung sebagai kekeruhan (NTU).
                </p>
            </div>
        </div>
    </div>
</div>
