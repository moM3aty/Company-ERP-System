<?php
// Path: core/Auth/Repositories/UserRepository.php

namespace Core\Auth\Repositories;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        // -------------------------------------------------------------------
        // MOCK DATA: بيانات افتراضية حتى نربط الـ Database الحقيقية لاحقاً
        // -------------------------------------------------------------------
        if ($email === 'admin@system.com') {
            return [
                'id' => 1,
                'name' => 'Ahmed Hassan',
                'email' => 'admin@system.com',
                // الباسورد هو: Admin@123
                'password' => password_hash('Admin@123', PASSWORD_BCRYPT),
                'two_factor_enabled' => false,
                'login_attempts' => 0,
                'locked_until' => null,
                'language' => 'ar'
            ];
        }
        // -------------------------------------------------------------------

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }

    public function incrementLoginAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    public function lockAccount(int $userId, string $lockUntil): void
    {
        $stmt = $this->db->prepare("UPDATE users SET locked_until = :locked_until WHERE id = :id");
        $stmt->execute(['locked_until' => $lockUntil, 'id' => $userId]);
    }

    public function resetLoginAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    public function logSession(int $userId, string $sessionId, string $ip, string $userAgent): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO active_sessions (user_id, session_id, ip_address, user_agent, last_activity, created_at) 
            VALUES (:uid, :sid, :ip, :ua, NOW(), NOW())
        ");
        $stmt->execute([
            'uid' => $userId,
            'sid' => $sessionId,
            'ip'  => $ip,
            'ua'  => $userAgent
        ]);
    }

    public function revokeSession(string $sessionId): void
    {
        $stmt = $this->db->prepare("DELETE FROM active_sessions WHERE session_id = :sid");
        $stmt->execute(['sid' => $sessionId]);
    }
}