<?php
// Path: core/Security/CsrfManager.php

namespace Core\Security;

class CsrfManager
{
    private string $sessionKey = '_csrf_token';

    public function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$this->sessionKey];
    }

    public function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $storedToken = $_SESSION[$this->sessionKey] ?? null;
        
        if (!$storedToken || !$token) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }
}