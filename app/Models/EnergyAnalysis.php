<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyAnalysis extends Model
{
    protected $fillable = [
        'analysis_text',
        'status',
        'risk_score',
        'net_power',
        'solar_power',
        'load_power',
        'battery_pct',
        'endurance_hours',
        'solar_forecast',
        'can_survive_night',
        'time_to_full',
        'time_to_empty',
        'raw_data',
    ];

    protected $casts = [
        'raw_data'          => 'array',
        'risk_score'        => 'float',
        'net_power'         => 'float',
        'solar_power'       => 'float',
        'load_power'        => 'float',
        'battery_pct'       => 'float',
        'endurance_hours'   => 'float',
        'solar_forecast'    => 'float',
        'can_survive_night' => 'boolean',
        'time_to_full'      => 'float',
        'time_to_empty'     => 'float',
    ];
}
