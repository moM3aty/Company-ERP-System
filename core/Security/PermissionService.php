<?php
// Path: core/Security/PermissionService.php

namespace Core\Security;

use PDO;

/**
 * خدمة التحقق من الصلاحيات والنطاقات.
 * تحل محل الكود الوهمي (Mock) الذي كان في الـ Middleware.
 */
class PermissionService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * التحقق مما إذا كان المستخدم يملك الصلاحية المطلوبة (عبر الدور أو بشكل مباشر).
     */
    public function hasPermission(int $userId, string $permissionName): bool
    {
        // 1. التحقق إذا كان Super Admin (role_id = 1) يفعل أي شيء
        $stmtSuper = $this->db->prepare("
            SELECT COUNT(*) FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = :uid AND r.name = 'Super Administrator'
        ");
        $stmtSuper->execute(['uid' => $userId]);
        if ($stmtSuper->fetchColumn() > 0) return true;

        // 2. التحقق من الصلاحيات المباشرة (Direct Permissions / Revoked)
        $stmtDirect = $this->db->prepare("
            SELECT is_revoked FROM user_direct_permissions udp
            JOIN permissions p ON udp.permission_id = p.id
            WHERE udp.user_id = :uid AND p.name = :perm
        ");
        $stmtDirect->execute(['uid' => $userId, 'perm' => $permissionName]);
        $directPerm = $stmtDirect->fetch(PDO::FETCH_ASSOC);

        if ($directPerm) {
            if ($directPerm['is_revoked'] == 1) return false; // ممنوع صراحةً
            return true; // مسموح صراحةً
        }

        // 3. التحقق عبر الأدوار (Roles)
        $stmtRole = $this->db->prepare("
            SELECT COUNT(*) FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :uid AND p.name = :perm
        ");
        $stmtRole->execute(['uid' => $userId, 'perm' => $permissionName]);
        
        return $stmtRole->fetchColumn() > 0;
    }

    /**
     * التحقق من النطاق (Scope) للمستخدم.
     * هل يحق له رؤية بيانات هذا الفرع أو هذه الشركة؟
     */
    public function hasScopeAccess(int $userId, string $scopeType, int $scopeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_scopes 
            WHERE user_id = :uid AND scope_type = :type AND scope_id = :scopeId
        ");
        $stmt->execute(['uid' => $userId, 'type' => $scopeType, 'scopeId' => $scopeId]);
        
        return $stmt->fetchColumn() > 0;
    }
}