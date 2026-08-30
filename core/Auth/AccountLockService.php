<?php
// Path: core/Auth/AccountLockService.php

namespace Core\Auth;

use PDO;

class AccountLockService
{
    private PDO $db;
    private int $maxAttempts = 5;
    private int $lockoutMinutes = 15;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function isLocked(string $email): bool
    {
        $stmt = $this->db->prepare("
            SELECT locked_until FROM users 
            WHERE email = :email AND locked_until > NOW()
        ");
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    public function lockAccount(string $email): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET locked_until = DATE_ADD(NOW(), INTERVAL :mins MINUTE) 
            WHERE email = :email
        ");
        $stmt->execute([
            'mins' => $this->lockoutMinutes,
            'email' => $email
        ]);
    }

    public function resetLock(string $email): void
    {
        $stmt = $this->db->prepare("UPDATE users SET locked_until = NULL WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }
}