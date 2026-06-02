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
            $table->text('analysis_text');              
            $table->string('status');                   // 'normal', 'hoarding', 'emergency'
            $table->float('net_power')->nullable();
            $table->float('solar_power')->nullable();
            $table->float('load_power')->nullable();
            $table->float('battery_pct')->nullable();
            $table->float('endurance_hours')->nullable();
            $table->float('solar_forecast')->nullable();
            $table->json('raw_data')->nullable();
            $table->float('risk_score')->default(0);
            $table->boolean('can_survive_night')->default(true);
            $table->float('time_to_full')->nullable();
            $table->float('time_to_empty')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_analyses');
    }
};
