<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'interval_ms',
        'pump_names',
        'rules',
        'calibration',
    ];

    protected $casts = [
        'pump_names'  => 'array',
        'rules'       => 'array',
        'calibration' => 'array',
    ];

    /**
     * Default pump names when none are configured.
     */
    public static function defaultPumpNames(): array
    {
        return [
            'pump_1' => 'Pump 1',
            'pump_2' => 'Pump 2',
            'pump_3' => 'Pump 3',
            'pump_4' => 'Pump 4',
        ];
    }

    /**
     * Default calibration constants.
     */
    public static function defaultCalibration(): array
    {
        return [
            'ph'   => ['p1_ph' => 6.86, 'p1_mv' => 1621, 'p2_ph' => 4.01, 'p2_mv' => 2117],
            'tds'  => ['k' => 1.1013],
            'turb' => ['zero_v' => 2.1],
        ];
    }
}
