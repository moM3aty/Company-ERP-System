<?php
// Path: core/Bootstrap/AppServiceProvider.php

namespace Core\Bootstrap;

use Bootstrap\Container; // using Core Container logic

class AppServiceProvider extends ServiceProvider
{
    public function register(Container $app): void
    {
        // Register core application services and utilities
        $app->singleton(\Core\Monitoring\Logger::class, function () {
            return new \Core\Monitoring\Logger();
        });

        $app->singleton(\Core\Security\EncryptionManager::class, function ($app) {
            return new \Core\Security\EncryptionManager($app->get(\Core\Config\Config::class));
        });
    }

    public function boot(Container $app): void
    {
        // Initialization logic after all providers are registered
        date_default_timezone_set($app->get(\Core\Config\Config::class)->get('app.timezone', 'UTC'));
    }
}