<?php
// Path: core/Bootstrap/Application.php

namespace Core\Bootstrap;

use Core\DI\Container; // المسار الصحيح

class Application extends Container
{
    private string $basePath;
    private ProviderRegistry $providerRegistry;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        
        $this->singleton(self::class, function() {
            return $this;
        });
        
        $this->providerRegistry = new ProviderRegistry($this);
        $this->registerCoreBindings();
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    private function registerCoreBindings(): void
    {
        $this->singleton(\Core\Config\Config::class, function() {
            return new \Core\Config\Config($this->basePath . '/config');
        });
        
        $this->singleton(\Core\Http\Request::class, function() {
            return \Core\Http\Request::capture();
        });
        
        $this->singleton(\Core\Http\Response::class, function() {
            return new \Core\Http\Response();
        });
        
        $this->singleton(\Core\Routing\Router::class, function() {
            return new \Core\Routing\Router($this);
        });
    }

    public function registerProvider(ServiceProvider $provider): void
    {
        $this->providerRegistry->register($provider);
    }

    public function boot(): void
    {
        $this->providerRegistry->boot();
    }
}