<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_analyses', function (Blueprint $table) {
            $table->float('risk_score')->nullable()->after('model');
            $table->boolean('can_survive_night')->nullable()->after('solar_forecast');
            $table->float('time_to_full')->nullable()->after('can_survive_night');
            $table->float('time_to_empty')->nullable()->after('time_to_full');
        });
    }

    public function down(): void
    {
        Schema::table('energy_analyses', function (Blueprint $table) {
            $table->dropColumn(['risk_score', 'can_survive_night', 'time_to_full', 'time_to_empty']);
        });
    }
};
