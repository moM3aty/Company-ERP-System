<?php
// Path: core/Authorization/Gate.php

namespace Core\Authorization;

class Gate
{
    private array $policies = [];
    private array $abilities = [];

    /**
     * Define a simple closure-based ability.
     */
    public function define(string $ability, callable $callback): void
    {
        $this->abilities[$ability] = $callback;
    }

    /**
     * Register a policy class for a specific model/entity.
     */
    public function policy(string $class, string $policyClass): void
    {
        $this->policies[$class] = $policyClass;
    }

    /**
     * Check if user has an ability.
     */
    public function allows(object $user, string $ability, array $arguments = []): bool
    {
        if (isset($this->abilities[$ability])) {
            return call_user_func($this->abilities[$ability], $user, ...$arguments);
        }
        return false;
    }

    /**
     * Check if user is denied an ability.
     */
    public function denies(object $user, string $ability, array $arguments = []): bool
    {
        return !$this->allows($user, $ability, $arguments);
    }
}