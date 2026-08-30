<?php
// Path: core/Routing/Route.php

namespace Core\Routing;

class Route
{
    public readonly string $method;
    public readonly string $uri;
    public readonly mixed $action;
    public array $middlewares = [];
    public ?string $name = null;

    public function __construct(string $method, string $uri, mixed $action)
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }

    public function middleware(string|array $middleware): self
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}