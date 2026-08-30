<?php
// Path: core/Http/Middleware/MiddlewarePipeline.php

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * خط أنابيب الوسائط (Middleware Pipeline - Onion Architecture).
 * يقوم بترتيب وتنفيذ الـ Middlewares بشكل متسلسل حول الطلب.
 */
class MiddlewarePipeline
{
    /** @var array<MiddlewareInterface> */
    private array $middlewares = [];
    
    /** @var callable */
    private $fallbackHandler;

    /**
     * إضافة وسيط للخط.
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * تنفيذ السلسلة وإرجاع الاستجابة النهائية.
     *
     * @param Request $request
     * @param callable $fallbackHandler الكود النهائي (غالباً الكنترولر) الذي سينفذ إذا مرت جميع الوسائط.
     * @return Response
     */
    public function handle(Request $request, callable $fallbackHandler): Response
    {
        // بناء سلسلة الـ closures من الداخل للخارج (Onion pattern)
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function (Request $request) use ($next, $middleware) {
                    return $middleware->process($request, $next);
                };
            },
            function (Request $request) use ($fallbackHandler) {
                // النهاية: تنفيذ الكنترولر
                return $fallbackHandler($request);
            }
        );

        return $pipeline($request);
    }
}