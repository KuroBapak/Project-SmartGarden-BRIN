<?php

use App\Http\Controllers\ProfileController;
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
});

require __DIR__.'/auth.php';
