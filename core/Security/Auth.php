<?php
// Path: core/Security/Auth.php
namespace Core\Security;

class Auth {
    
    // التحقق من تسجيل الدخول
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    // جلب معرف الشركة الحالي
    public static function companyId(): ?int {
        return $_SESSION['company_id'] ?? null;
    }

    // جلب معرف الفرع الحالي
    public static function branchId(): ?int {
        return $_SESSION['branch_id'] ?? null;
    }

    // التحقق من الصلاحيات
    public static function hasPermission(string $permissionCode): bool {
        if (!self::check()) return false;
        
        // مدير النظام يملك كل الصلاحيات (Role ID = 1 كمثال)
        if (($_SESSION['user_role_id'] ?? 0) == 1) return true;

        $permissions = $_SESSION['user_permissions'] ?? [];
        return in_array($permissionCode, $permissions);
    }

    // حظر الوصول وإعادة التوجيه إذا لم يكن لديه صلاحية
    public static function requirePermission(string $permissionCode) {
        if (!self::hasPermission($permissionCode)) {
            $_SESSION['flash_err'] = __('ليس لديك صلاحية للوصول إلى هذه الصفحة.', 'Access Denied.');
            header("Location: /ERP/dashboard");
            exit;
        }
    }
}