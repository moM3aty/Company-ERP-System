<?php
// Path: core/Config/DatabaseConfig.php

namespace Core\Config;

class DatabaseConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getHost(): string
    {
        return $this->config->get('database.host', '127.0.0.1');
    }

    public function getDatabaseName(): string
    {
        return $this->config->get('database.name', 'erp_system');
    }

    public function getUsername(): string
    {
        return $this->config->get('database.user', 'root');
    }

    public function getPassword(): string
    {
        return $this->config->get('database.password', '');
    }

    public function getCharset(): string
    {
        return $this->config->get('database.charset', 'utf8mb4');
    }
}