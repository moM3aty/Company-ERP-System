<?php
// Path: core/Http/Middleware/PermissionMiddleware.php

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;

/**
 * وسيط فحص الصلاحيات (ACL/Permissions).
 * يتأكد من أن المستخدم الحالي لديه الصلاحية اللازمة لتنفيذ العملية المطلوبة.
 */
class PermissionMiddleware implements MiddlewareInterface
{
    private string $requiredPermission;

    public function __construct(string $requiredPermission)
    {
        $this->requiredPermission = $requiredPermission;
    }

    public function process(Request $request, callable $next): Response
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return new JsonResponse(['message' => 'غير مصرح'], 401);
        }

        // TODO: استدعاء خدمة الصلاحيات للفحص من قاعدة البيانات
        // PermissionService::hasPermission($userId, $this->requiredPermission)
        $hasPermission = true; // محاكاة

        if (!$hasPermission) {
            return new JsonResponse([
                'success' => false,
                'message' => 'لا تملك الصلاحية الكافية للوصول إلى هذا الإجراء.'
            ], 403); // 403 Forbidden
        }

        return $next($request);
    }
}