<?php

namespace App\Providers;

use App\Services\MqttService;
use Illuminate\Support\ServiceProvider;

class MqttServiceProvider extends ServiceProvider
{
    /**
     * Register the MqttService as a singleton.
     * Config-only — no persistent connection held.
     */
    public function register(): void
    {
        $this->app->singleton(MqttService::class, function ($app) {
            return new MqttService(config('services.mqtt'));
        });
    }

    public function boot(): void
    {
        //
    }
}
