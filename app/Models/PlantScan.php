<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantScan extends Model
{
    protected $fillable = [
        'status',
        'status_label',
        'status_emoji',
        'message',
        'detections',
        'total_detections',
        'image_path',
        'image_original_path',
        'scan_source',
    ];

    protected $casts = [
        'detections'       => 'array',
        'total_detections' => 'integer',
    ];
}
