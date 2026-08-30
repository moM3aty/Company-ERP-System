<?php
// Path: bootstrap/Container.php
// Adding simple Dependency Injection Container for the core

namespace Bootstrap;

class Container
{
    private array $instances = [];

    public function singleton(string $abstract, callable $concrete): void
    {
        $this->instances[$abstract] = $concrete($this);
    }

    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        throw new \Exception("Service {$abstract} not found in container.");
    }
}