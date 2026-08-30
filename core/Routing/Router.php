<?php
// Path: core/Routing/Router.php

namespace Core\Routing;

use Core\DI\Container;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use Exception;

/**
 * موجه الطلبات (Router).
 * يربط بين الروابط (URLs) والكنترولرات المناسبة مع ضمان إرسال هيدر HTML الصحيح لتفادي ظهور الكود النصي.
 */
class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $path, array|callable $handler): void { $this->addRoute('GET', $path, $handler); }
    public function post(string $path, array|callable $handler): void { $this->addRoute('POST', $path, $handler); }
    public function put(string $path, array|callable $handler): void { $this->addRoute('PUT', $path, $handler); }
    public function delete(string $path, array|callable $handler): void { $this->addRoute('DELETE', $path, $handler); }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array|callable $handler): void
    {
        $prefix = '';
        $middlewares = [];

        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'] ?? '', '/');
            if (isset($group['middleware'])) {
                $middlewares = array_merge($middlewares, (array) $group['middleware']);
            }
        }

        $fullPath = $prefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        
        if ($fullPath !== '/') {
            $fullPath = rtrim($fullPath, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $this->convertPathToRegex($fullPath),
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    private function convertPathToRegex(string $path): string
    {
        return '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path) . '$#';
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $uri, $matches)) {
                
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                foreach ($params as $key => $value) {
                    $request->setAttribute($key, $value);
                }

                return $this->executeHandler($route['handler'], $request, $params);
            }
        }

        return new JsonResponse(['success' => false, 'message' => 'Route not found (404).'], 404);
    }

    private function executeHandler($handler, Request $request, array $params): Response
    {
        // إعداد كائن الاستجابة الافتراضي مع ضبط هيدر HTML
        $response = new Response();
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8');

        if (is_callable($handler)) {
            $result = call_user_func($handler, $request, $response, ...array_values($params));
        } else if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            
            $controller = $this->container->get($class);
            
            if (!method_exists($controller, $method)) {
                throw new Exception("Method {$method} not found in controller {$class}");
            }

            $result = $controller->$method($request, $response, ...array_values($params));
        } else {
            throw new Exception("Invalid route handler.");
        }

        // إذا أعاد الكنترولر كائن Response (وليس JsonResponse)، نضمن إجبار هيدر الـ HTML
        if ($result instanceof Response) {
            if (!($result instanceof JsonResponse)) {
                $result->setHeader('Content-Type', 'text/html; charset=UTF-8');
            }
            return $result;
        }

        // إذا أعاد الكنترولر نص HTML مباشر
        if (is_string($result)) {
            $response->setContent($result);
            return $response;
        }

        return $response;
    }
}