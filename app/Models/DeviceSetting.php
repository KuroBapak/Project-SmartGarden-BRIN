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
        'min_ph',
        'min_tds',
        'max_turb',
    ];
}
