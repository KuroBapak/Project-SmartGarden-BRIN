<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyAnalysis extends Model
{
    protected $fillable = [
        'analysis_text',
        'status',
        'model',
        'net_power',
        'solar_power',
        'load_power',
        'battery_pct',
        'endurance_hours',
        'solar_forecast',
        'raw_data',
    ];

    protected $casts = [
        'raw_data'        => 'array',
        'net_power'       => 'float',
        'solar_power'     => 'float',
        'load_power'      => 'float',
        'battery_pct'     => 'float',
        'endurance_hours' => 'float',
        'solar_forecast'  => 'float',
    ];
}
