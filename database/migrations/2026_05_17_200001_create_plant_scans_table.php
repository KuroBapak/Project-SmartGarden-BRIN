<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_scans', function (Blueprint $table) {
            $table->id();
            $table->string('status');                   // 'healthy', 'warning', 'critical', 'mild'
            $table->string('status_label');
            $table->string('status_emoji');
            $table->text('message')->nullable();
            $table->json('detections')->nullable();     // Array of detected diseases
            $table->integer('total_detections')->default(0);
            $table->string('image_path')->nullable();           // Annotated image path
            $table->string('image_original_path')->nullable();  // Original image path
            $table->string('scan_source')->default('auto');     // 'auto' or 'manual'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_scans');
    }
};
