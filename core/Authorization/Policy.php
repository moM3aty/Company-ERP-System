<?php
// Path: core/Authorization/Policy.php

namespace Core\Authorization;

use Core\Auth\AuthUser;

/**
 * Base template for creating entity-specific Policies (e.g., InvoicePolicy, EmployeePolicy)
 */
abstract class Policy
{
    /**
     * Global check: Super Admins bypass all specific policy checks.
     */
    public function before(AuthUser $user, string $ability): ?bool
    {
        if ($user->roleId === 1) { // Assuming 1 is System Admin
            return true;
        }
        return null; // Continue to specific policy method
    }
}