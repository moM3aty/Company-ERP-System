<?php
// Path: core/Bootstrap/AuthServiceProvider.php

namespace Core\Bootstrap;

use Bootstrap\Container;

class AuthServiceProvider extends ServiceProvider
{
    public function register(Container $app): void
    {
        $app->singleton(\Core\Auth\AuthManager::class, function() {
            return new \Core\Auth\AuthManager();
        });
        
        $app->singleton(\Core\Authorization\AuthorizationManager::class, function() use ($app) {
            // هنا نمرر AuthManager لأنه مطلوب في الـ Constructor
            return new \Core\Authorization\AuthorizationManager($app->get(\Core\Auth\AuthManager::class));
        });
        
        $app->singleton(\Core\Authorization\PolicyResolver::class, function() {
            return new \Core\Authorization\PolicyResolver();
        });
    }

    public function boot(Container $app): void
    {
        $gate = $app->get(\Core\Authorization\AuthorizationManager::class)->getGate();
        
        $gate->define('is-admin', function ($user) {
            return $user->roleId === 1; 
        });
    }
}