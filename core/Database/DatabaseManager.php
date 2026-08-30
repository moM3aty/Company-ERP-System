<?php
// Path: core/Database/DatabaseManager.php

namespace Core\Database;

use PDO;
use Exception;

/**
 * Manages multiple database connections (e.g., read/write replicas, multi-tenant databases).
 */
class DatabaseManager
{
    private array $connections = [];

    public function addConnection(string $name, PDO $pdo): void
    {
        $this->connections[$name] = $pdo;
    }

    public function getConnection(string $name = 'default'): PDO
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Database connection [{$name}] not configured.");
        }
        return $this->connections[$name];
    }
}