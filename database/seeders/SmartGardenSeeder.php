<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeviceSetting;
use App\Models\PlantPreset;

class SmartGardenSeeder extends Seeder
{
    public function run(): void
    {
        // ── Device Settings (from code_amcs.ino calibration values) ──
        DeviceSetting::create([
            'device_id'   => 'esp32_1',
            'interval_ms' => 60000,
            'pump_names'  => [
                'pump_1' => 'Pump 1',
                'pump_2' => 'Pump 2',
                'pump_3' => 'Pump 3',
                'pump_4' => 'Pump 4',
            ],
            'calibration' => [
                'ph' => [
                    'p1_ph' => 6.86,   // Buffer 1: pH 6.86
                    'p1_mv' => 1621.0, // Buffer 1: 1621 mV
                    'p2_ph' => 4.01,   // Buffer 2: pH 4.01
                    'p2_mv' => 2117.0, // Buffer 2: 2117 mV
                ],
                'tds' => [
                    'k' => 1.1013,     // K-Value (koreksi ke 500ppm)
                ],
                'turb' => [
                    'zero_v' => 2.1,   // Zero-point voltage (air jernih)
                ],
            ],
            'rules' => [], // Kosongkan aturan otomasi
        ]);
        
        // Preset Tanaman dikosongkan sesuai permintaan
    }
}
