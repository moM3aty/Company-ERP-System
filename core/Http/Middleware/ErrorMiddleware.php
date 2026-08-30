<?php
// Path: core/Http/Middleware/ErrorMiddleware.php

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use Core\Log\Logger; // هنا تم إصلاح المسار الصحيح
use Throwable;

/**
 * وسيط التقاط الأخطاء العالمي (Global Error Handler).
 * يغلف النظام بالكامل لضمان عدم خروج أي أخطاء PHP للمستخدم، بل إرجاع JSON نظيف.
 */
class ErrorMiddleware implements MiddlewareInterface
{
    private Logger $logger;
    private bool $debugMode;

    public function __construct(Logger $logger, bool $debugMode = false)
    {
        $this->logger = $logger;
        $this->debugMode = $debugMode;
    }

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            // تسجيل الخطأ الفعلي للإدارة
            if (method_exists($this->logger, 'error')) {
                $this->logger->error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // تكوين استجابة للمستخدم
            $response = [
                'success' => false,
                'message' => 'حدث خطأ داخلي في الخادم.',
            ];

            // إظهار التفاصيل إذا كنا في بيئة التطوير
            if ($this->debugMode) {
                $response['message'] = $e->getMessage();
                $response['file'] = $e->getFile();
                $response['line'] = $e->getLine();
            }

            // استجابة 500 (Internal Server Error)
            return new JsonResponse($response, 500);
        }
    }
}