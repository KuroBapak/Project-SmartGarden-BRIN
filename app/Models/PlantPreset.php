<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_ph',
        'min_tds',
        'max_turb',
        'max_temp',
    ];

    protected $casts = [
        'min_ph' => 'float',
        'min_tds' => 'float',
        'max_turb' => 'float',
        'max_temp' => 'float',
    ];
}
