<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_settings', function (Blueprint $table) {
            $table->float('max_temp')->default(30.0)->after('max_turb');
        });
    }

    public function down(): void
    {
        Schema::table('device_settings', function (Blueprint $table) {
            $table->dropColumn('max_temp');
        });
    }
};
