<?php
// Path: core/Auth/LoginAttemptService.php

namespace Core\Auth;

use PDO;

class LoginAttemptService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function recordAttempt(string $email, string $ipAddress, bool $success): void
    {
        // Fail gracefully if table doesn't exist
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (email, ip_address, success, attempted_at) 
                VALUES (:email, :ip, :success, NOW())
            ");
            $stmt->execute([
                'email' => $email,
                'ip' => $ipAddress,
                'success' => $success ? 1 : 0
            ]);
        } catch (\Exception $e) {
            // Ignore
        }
    }

    public function getRecentFailedAttempts(string $email, int $minutes = 15): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE email = :email AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)
            ");
            $stmt->execute(['email' => $email, 'mins' => $minutes]);
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
}