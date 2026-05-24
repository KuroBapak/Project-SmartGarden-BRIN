<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_analyses', function (Blueprint $table) {
            $table->id();
            $table->text('analysis_text');              // Ollama LLM output
            $table->string('status');                   // 'normal', 'hoarding', 'emergency'
            $table->string('model')->nullable();        // LLM model used
            $table->float('net_power')->nullable();
            $table->float('solar_power')->nullable();
            $table->float('load_power')->nullable();
            $table->float('battery_pct')->nullable();
            $table->float('endurance_hours')->nullable();
            $table->float('solar_forecast')->nullable();
            $table->json('raw_data')->nullable();       // Full payload from AI Server
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_analyses');
    }
};
