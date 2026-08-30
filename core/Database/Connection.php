<?php
// Path: core/Database/Connection.php

namespace Core\Database;

use PDO;
use Core\Config\Config;
use Exception;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Get a singleton PDO connection.
     */
    public static function getInstance(Config $config): PDO
    {
        if (self::$instance === null) {
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
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new Exception("Database Connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}