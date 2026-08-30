<?php
// Path: core/Authorization/PolicyResolver.php

namespace Core\Authorization;

use Exception;

class PolicyResolver
{
    private array $policies = [];

    /**
     * Map a Domain Entity/Model to its corresponding Policy Class.
     */
    public function register(string $entityClass, string $policyClass): void
    {
        $this->policies[$entityClass] = $policyClass;
    }

    /**
     * Retrieve the instantiated policy for a given entity object.
     */
    public function resolve(object $entity): Policy
    {
        $class = get_class($entity);
        
        if (!isset($this->policies[$class])) {
            throw new Exception("No policy defined for entity: {$class}");
        }

        $policyClass = $this->policies[$class];
        return new $policyClass(); // Assumes policies have no constructor args for simplicity, or use DI Container
    }
}