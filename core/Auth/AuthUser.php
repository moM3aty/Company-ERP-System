<?php
// Path: core/Auth/AuthUser.php

namespace Core\Auth;

/**
 * Standardized DTO representing the currently authenticated user.
 */
class AuthUser
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $email;
    public readonly int $roleId;
    public readonly ?int $tenantId;
    public readonly ?int $branchId;

    public function __construct(int $id, string $name, string $email, int $roleId, ?int $tenantId = null, ?int $branchId = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->roleId = $roleId;
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId
        ];
    }
}