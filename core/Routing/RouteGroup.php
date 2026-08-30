<?php
// Path: core/Routing/RouteGroup.php

namespace Core\Routing;

/**
 * يدير مجموعات المسارات (مثال: مجموعة تبدأ بـ /api/v1 وتحتاج AuthMiddleware).
 */
class RouteGroup
{
    public readonly string $prefix;
    public readonly array $middlewares;

    public function __construct(string $prefix = '', array $middlewares = [])
    {
        $this->prefix = '/' . trim($prefix, '/');
        $this->middlewares = $middlewares;
    }
}