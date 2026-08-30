<?php
// Path: core/Bootstrap/ProviderRegistry.php

namespace Core\Bootstrap;

use Core\DI\Container;

/**
 * مدير مزودي الخدمات (Provider Registry).
 * مسؤول عن تسجيل وتشغيل الـ Service Providers في النظام.
 */
class ProviderRegistry
{
    private Container $container;
    private array $providers = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function register(ServiceProvider $provider): void
    {
        $this->providers[] = $provider;
        $provider->register();
    }

    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
}