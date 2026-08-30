<?php
// Path: core/Application.php

namespace Core;

use Core\DI\Container;
use Core\Routing\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware\MiddlewarePipeline;
use Core\Http\Middleware\ErrorMiddleware;
use Throwable;

/**
 * نواة التطبيق (Application Core).
 * يدير دورة حياة الطلب (Request Lifecycle) وحقن الاعتماديات.
 */
class Application extends Container
{
    private string $basePath;
    private Router $router;
    private MiddlewarePipeline $pipeline;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->router = new Router($this);
        $this->pipeline = new MiddlewarePipeline();
        
        $this->registerBaseBindings();
    }

    private function registerBaseBindings(): void
    {
        $this->singleton(self::class, $this);
        $this->singleton(Router::class, $this->router);
        
        // يمكننا تسجيل الكائنات الأساسية مثل قاعدة البيانات، الإعدادات، الخ هنا.
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * إضافة وسيط (Middleware) يعمل على مستوى التطبيق بالكامل.
     */
    public function addGlobalMiddleware(object $middleware): self
    {
        $this->pipeline->pipe($middleware);
        return $this;
    }

    /**
     * تشغيل التطبيق ومعالجة الطلب.
     */
    public function run(Request $request): Response
    {
        try {
            // تغليف التنفيذ بالـ Middlewares (مع ErrorMiddleware كأول طبقة حماية)
            $response = $this->pipeline->handle($request, function ($req) {
                return $this->router->dispatch($req);
            });
            
            return $response;
        } catch (Throwable $e) {
            // في حال فشل كل شيء، إرجاع خطأ 500 كاحتياط أخير
            $res = new Response(json_encode([
                'success' => false, 
                'message' => 'Critical System Error.',
                'error' => $e->getMessage()
            ]), 500);
            $res->setHeader('Content-Type', 'application/json');
            return $res;
        }
    }
}