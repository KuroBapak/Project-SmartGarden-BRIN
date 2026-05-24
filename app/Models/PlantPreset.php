<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pump_names',
        'rules',
    ];

    protected $casts = [
        'pump_names' => 'array',
        'rules'      => 'array',
    ];
}
