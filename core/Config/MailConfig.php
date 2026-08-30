<?php
// Path: core/Config/MailConfig.php

namespace Core\Config;

class MailConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getMailer(): string
    {
        return $this->config->get('mail.mailer', 'smtp');
    }

    public function getHost(): string
    {
        return $this->config->get('mail.host', 'smtp.mailtrap.io');
    }

    public function getPort(): int
    {
        return (int) $this->config->get('mail.port', 2525);
    }

    public function getUsername(): string
    {
        return $this->config->get('mail.username', '');
    }

    public function getPassword(): string
    {
        return $this->config->get('mail.password', '');
    }

    public function getFromAddress(): string
    {
        return $this->config->get('mail.from.address', 'noreply@nourtrust.com');
    }

    public function getFromName(): string
    {
        return $this->config->get('mail.from.name', 'Nour Trust ERP');
    }
}