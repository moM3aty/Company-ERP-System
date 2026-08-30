<?php
// Path: core/Config/SecurityConfig.php

namespace Core\Config;

class SecurityConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getEncryptionKey(): string
    {
        return $this->config->get('security.encryption_key', '');
    }

    public function isRateLimitingEnabled(): bool
    {
        return (bool) $this->config->get('security.rate_limit.enabled', true);
    }

    public function getMaxAttempts(): int
    {
        return (int) $this->config->get('security.rate_limit.max_attempts', 60);
    }

    public function getDecayMinutes(): int
    {
        return (int) $this->config->get('security.rate_limit.decay_minutes', 1);
    }
}