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
                        <form method="POST" action="{{ route('settings.config') }}">
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
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
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

    <!-- AJAX Script for Manual Pump -->
    <script>
        function triggerPump(targetPump, durationMs) {
            fetch("{{ route('settings.pump') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    device_id: "{{ $setting->device_id }}",
                    target: targetPump,
                    duration: durationMs
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('Command Sent: ' + data.message);
                } else {
                    alert('Error sending command');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Failed to send command.");
            });
        }
    </script>
</x-app-layout>
