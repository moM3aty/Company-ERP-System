<?php
// Path: core/Auth/AuthManager.php

namespace Core\Auth;

use PDO;
use Throwable;

class AuthManager {
    private static ?array $user = null;

    public static function init(PDO $pdo): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $pdo->prepare("SELECT u.*, r.is_system FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
                $stmt->execute([$_SESSION['user_id']]);
                self::$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) {
                self::$user = null;
            }
        }
    }

    public static function user(): ?array {
        return self::$user;
    }

    /**
     * الفحص الذكي للصلاحيات المتوافق مع جدول role_permissions والـ Super Admin
     */
    public static function hasAccess(string $module, string $resource, string $action): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // حساب الأدمن الرئيسي (Role ID = 1) يتخطى الفحص دائماً
        if (($_SESSION['user_role_id'] ?? null) == 1) {
            return true;
        }

        $permKey = strtolower("{$module}.{$resource}.{$action}");
        $shortKey = strtolower("{$resource}.{$action}");

        $userPerms = $_SESSION['user_permissions'] ?? [];

       foreach ($userPerms as $perm) {
    $perm = strtolower($perm);
    // السماح إذا تطابق المفتاح بالكامل، أو إذا كان يمتلك صلاحية شاملة للموديول (مثال: sales.*)
    if ($perm === $permKey || $perm === $shortKey || $perm === strtolower("{$module}.*")) {
        return true;
    }
}

        return false;
    }
}