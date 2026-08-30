<?php
// Path: core/Bootstrap/DatabaseServiceProvider.php

namespace Core\Bootstrap;

use Bootstrap\Container;
use Core\Config\Config;
use PDO;
use Exception;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(Container $app): void
    {
        $app->singleton(PDO::class, function ($app) {
            $config = $app->get(Config::class);
            
            $host = $config->get('database.host', '127.0.0.1');
            $db   = $config->get('database.name', 'erp_system');
            $user = $config->get('database.user', 'root');
            $pass = $config->get('database.password', '');
            $charset = $config->get('database.charset', 'utf8mb4');

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                return new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            }
        });
    }
}