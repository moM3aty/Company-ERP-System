<?php
// Path: core/Bootstrap/Container.php

namespace Core\Bootstrap;

use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * Dependency Injection Container for auto-wiring classes.
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        // Mark as singleton by initially setting instance to null
        $this->instances[$abstract] = null; 
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances) && $this->instances[$id] !== null) {
            return $this->instances[$id];
        }

        $concrete = $this->bindings[$id] ?? $id;

        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        if (array_key_exists($id, $this->instances)) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    private function build(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
            if (!$reflector->isInstantiable()) {
                throw new Exception("Class {$concrete} is not instantiable.");
            }

            $constructor = $reflector->getConstructor();
            if (is_null($constructor)) {
                return new $concrete;
            }

            $parameters = $constructor->getParameters();
            $dependencies = $this->getDependencies($parameters);

            return $reflector->newInstanceArgs($dependencies);
        } catch (\ReflectionException $e) {
            throw new Exception("Failed to resolve class {$concrete}: " . $e->getMessage());
        }
    }

    private function getDependencies(array $parameters): array
    {
        $dependencies = [];
        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();
            if ($dependency === null || $dependency->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Unresolvable dependency resolving [{$parameter->name}].");
                }
            } else {
                $dependencies[] = $this->get($dependency->getName());
            }
        }
        return $dependencies;
    }
}