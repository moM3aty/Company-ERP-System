<?php
// Path: core/Http/Middleware/AuthMiddleware.php

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;

/**
 * وسيط المصادقة (Authentication).
 * يمنع الوصول للمسارات المحمية إلا في حال وجود توكن (Token) أو جلسة صحيحة.
 */
class AuthMiddleware implements MiddlewareInterface
{
    // ملاحظة: سيتم حقن خدمة المصادقة الفعلية (AuthService) هنا مستقبلاً
    
    public function process(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return new JsonResponse([
                'success' => false,
                'message' => 'يرجى تسجيل الدخول للوصول إلى هذا المورد.'
            ], 401);
        }

        // TODO: التحقق الفعلي من صحة الـ JWT Token وجلب المستخدم
        $isValidToken = true; // محاكاة (سيتم استبدالها لاحقاً بـ JWT Validator)
        $userId = 1; // محاكاة
        
        if (!$isValidToken) {
            return new JsonResponse([
                'success' => false,
                'message' => 'رمز التوثيق غير صالح أو منتهي الصلاحية.'
            ], 401);
        }

        // إضافة بيانات المستخدم للطلب لكي يستخدمها الكنترولر
        $request->setAttribute('user_id', $userId);

        return $next($request);
    }
}