<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AiResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // SmartGarden Config & Command routes
    Route::get('/settings', [\App\Http\Controllers\DeviceCommandController::class, 'settingsView'])->name('settings');
    Route::post('/settings/config', [\App\Http\Controllers\DeviceCommandController::class, 'updateConfig'])->name('settings.config');
    Route::post('/settings/pump', [\App\Http\Controllers\DeviceCommandController::class, 'manualOverride'])->name('settings.pump');

    // Plant Preset CRUD routes
    Route::post('/plant-presets', [\App\Http\Controllers\PlantPresetController::class, 'store'])->name('plant-presets.store');
    Route::put('/plant-presets/{plantPreset}', [\App\Http\Controllers\PlantPresetController::class, 'update'])->name('plant-presets.update');
    Route::delete('/plant-presets/{plantPreset}', [\App\Http\Controllers\PlantPresetController::class, 'destroy'])->name('plant-presets.destroy');

    // Solar Panel Real-Time API
    // InfluxDB Data Fetching (Realtime via Polling)
    Route::get('/api/solar', [\App\Http\Controllers\DashboardController::class, 'solarData'])->name('api.solar');
    Route::get('/api/sensor', [\App\Http\Controllers\DashboardController::class, 'sensorData'])->name('api.sensor');

    // BMKG Weather Forecast API
    Route::get('/api/bmkg/forecast', [\App\Http\Controllers\BmkgController::class, 'forecast'])->name('api.bmkg.forecast');

    // ── AI Results: Frontend fetches pre-computed data from DB ──
    Route::get('/api/ai/energy-analysis/latest', [AiResultController::class, 'latestEnergyAnalysis'])->name('api.ai.energy-analysis.latest');
    Route::get('/api/ai/plant-scan/latest', [AiResultController::class, 'latestPlantScan'])->name('api.ai.plant-scan.latest');
});

// ── AI Server → Dashboard: Receive results (protected by API key, no session auth) ──
Route::middleware(\App\Http\Middleware\ValidateAiApiKey::class)->group(function () {
    Route::post('/api/ai/energy-analysis', [AiResultController::class, 'storeEnergyAnalysis'])->name('api.ai.energy-analysis.store');
    Route::post('/api/ai/plant-scan', [AiResultController::class, 'storePlantScan'])->name('api.ai.plant-scan.store');
});

// ── Proxy: Forward Raspberry Pi photo uploads directly to internal AI Server ──
Route::post('/api/scan/upload', function (\Illuminate\Http\Request $request) {
    if (!$request->hasFile('file')) {
        return response()->json(['error' => 'No file uploaded'], 400);
    }
    
    $file = $request->file('file');
    $response = \Illuminate\Support\Facades\Http::timeout(60)->withHeaders([
        'x-api-key' => $request->header('x-api-key')
    ])->attach(
        'file', file_get_contents($file->path()), $file->getClientOriginalName()
    )->post(env('AI_SERVER_URL') . '/api/scan/upload');

    return response($response->body(), $response->status())
        ->header('Content-Type', $response->header('Content-Type'));
});

require __DIR__.'/auth.php';
