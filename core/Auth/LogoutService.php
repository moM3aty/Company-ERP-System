<?php
// Path: core/Auth/LogoutService.php

namespace Core\Auth;

class LogoutService
{
    public function logout(): void
    {
        // Remove auth data
        SessionManager::remove('user_auth');
        
        // Destroy session entirely
        SessionManager::destroy();
        
        // Optional: Remove remember-me cookies here if implemented
    }
}