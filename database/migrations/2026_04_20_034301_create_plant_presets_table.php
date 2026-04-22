<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->float('min_ph');
            $table->float('min_tds');
            $table->float('max_turb');
            $table->float('max_temp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_presets');
    }
};
