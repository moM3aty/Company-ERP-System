<?php
// Path: core/Auth/UserSession.php

namespace Core\Auth;

/**
 * Tracks active sessions in the database for device management and forced logouts.
 */
class UserSession
{
    public readonly string $sessionId;
    public readonly int $userId;
    public readonly string $ipAddress;
    public readonly string $userAgent;
    public readonly string $lastActivity;

    public function __construct(string $sessionId, int $userId, string $ipAddress, string $userAgent, string $lastActivity)
    {
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->lastActivity = $lastActivity;
    }
}