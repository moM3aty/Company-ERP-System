<?php
// Path: core/Database/TransactionManager.php

namespace Core\Database;

use PDO;
use Exception;

class TransactionManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Executes a closure within a database transaction.
     */
    public function execute(callable $callback)
    {
        try {
            $this->db->beginTransaction();
            $result = $callback($this->db);
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}