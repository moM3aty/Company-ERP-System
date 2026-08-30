<?php
// Path: core/Bootstrap/RouteServiceProvider.php

namespace Core\Bootstrap;

use Bootstrap\Container;

class RouteServiceProvider extends ServiceProvider
{
    public function register(Container $app): void
    {
        //
    }

    public function boot(Container $app): void
    {
        // التعامل مع $app بأمان للوصول للمسار الجذري للمشروع
        $basePath = method_exists($app, 'basePath') ? $app->basePath() : dirname(__DIR__, 2);

        $routes = [
            'api' => $basePath . '/routes/api.php',
            'web' => $basePath . '/routes/web.php',
            'admin' => $basePath . '/routes/admin.php',
            'core' => $basePath . '/routes/core.php',
        ];

        foreach ($routes as $routeFile) {
            if (file_exists($routeFile)) {
                require_once $routeFile;
            }
        }
    }
}