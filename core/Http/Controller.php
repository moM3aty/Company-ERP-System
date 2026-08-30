<?php
// Path: core/Http/Controller.php

namespace Core\Http;

/**
 * الكنترولر الأساسي (Base Controller).
 * ترث منه جميع الكنترولرز الأخرى، ويوفر دوال مساعدة لإرجاع استجابات قياسية.
 */
abstract class Controller
{
    /**
     * إرجاع استجابة JSON ناجحة.
     */
    protected function json(array|object $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * إرجاع استجابة JSON تحتوي على خطأ (قياسية لجميع أخطاء الـ API).
     */
    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * إرجاع رسالة نجاح بدون بيانات معقدة.
     */
    protected function success(string $message, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message
        ], $status);
    }
}