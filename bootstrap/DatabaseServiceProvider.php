<?php
// Path: bootstrap/DatabaseServiceProvider.php

namespace Bootstrap;

use Core\Bootstrap\ServiceProvider;
use Bootstrap\Container;
use Core\Database\Connection;
use Core\Config\Config;
use PDO;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(Container $app): void
    {
        // Bind the PDO instance into the container as a singleton
        // It relies on the Config service being available.
        $app->singleton(PDO::class, function ($app) {
            $config = $app->get(Config::class);
            return Connection::getInstance($config);
        });
    }
}