<?php
// Path: core/Bootstrap/Kernel.php

namespace Core\Bootstrap;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use Core\Routing\Router;
use Core\Http\Middleware\MiddlewarePipeline;
use Exception;

class Kernel
{
    private Application $app;

    protected array $globalMiddleware = [
        \Core\Http\Middleware\CorsMiddleware::class,
        \Core\Http\Middleware\ErrorMiddleware::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request): Response
    {
        try {
            $this->app->boot();

            $router = $this->app->get(Router::class);
            $pipeline = new MiddlewarePipeline();

            // تمرير الـ Middleware
            foreach ($this->globalMiddleware as $middlewareClass) {
                // بما أن Middleware الأساسية لا تتطلب معاملات معقدة، يمكننا استدعاؤها مباشرة
                $middleware = new $middlewareClass();
                $pipeline->pipe($middleware);
            }

            return $pipeline->handle($request, function ($req) use ($router) {
                return $router->dispatch($req);
            });

        } catch (Exception $e) {
            $debug = true; // قم بربطها بالـ Config لاحقاً
            
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Internal Server Error',
                'details' => $debug ? $e->getMessage() : null
            ], 500);
        }
    }
}