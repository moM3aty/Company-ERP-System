<?php
// Path: core/Config/AppConfig.php

namespace Core\Config;

/**
 * Strongly typed wrapper for Application settings.
 */
class AppConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->config->get('app.name', 'Nour Trust ERP');
    }

    public function getEnvironment(): string
    {
        return $this->config->get('app.env', 'production');
    }

    public function isDebug(): bool
    {
        return (bool) $this->config->get('app.debug', false);
    }

    public function getUrl(): string
    {
        return rtrim($this->config->get('app.url', 'http://localhost'), '/');
    }

    public function getTimezone(): string
    {
        return $this->config->get('app.timezone', 'UTC');
    }
}