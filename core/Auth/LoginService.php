<?php
// Path: core/Auth/LoginService.php

namespace Core\Auth;

use Core\Exceptions\AuthenticationException;
use PDO;

class LoginService
{
    private PDO $db;
    private LoginAttemptService $attemptService;
    private AccountLockService $lockService;

    public function __construct(PDO $db, LoginAttemptService $attemptService, AccountLockService $lockService)
    {
        $this->db = $db;
        $this->attemptService = $attemptService;
        $this->lockService = $lockService;
    }

    public function attempt(string $email, string $password): AuthUser
    {
        if ($this->lockService->isLocked($email)) {
            throw new AuthenticationException("Account is temporarily locked due to multiple failed attempts.");
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute(['email' => $email]);
        $userRecord = $stmt->fetch(PDO::FETCH_OBJ);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!$userRecord || !PasswordManager::verify($password, $userRecord->password)) {
            $this->attemptService->recordAttempt($email, $ip, false);
            
            if ($this->attemptService->getRecentFailedAttempts($email) >= 5) {
                $this->lockService->lockAccount($email);
            }
            
            throw new AuthenticationException("Invalid credentials.");
        }

        // Success
        $this->attemptService->recordAttempt($email, $ip, true);
        $this->lockService->resetLock($email);
        SessionManager::regenerate(); // Prevent Session Fixation

        $authUser = new AuthUser(
            $userRecord->id,
            $userRecord->name,
            $userRecord->email,
            $userRecord->role_id,
            $userRecord->tenant_id ?? null,
            $userRecord->branch_id ?? null
        );

        SessionManager::set('user_auth', $authUser->toArray());
        
        return $authUser;
    }
}