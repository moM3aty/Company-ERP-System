<?php
// Path: core/Http/Request.php

namespace Core\Http;

class Request
{
    public array $query;
    public array $request;
    public array $server;
    public array $headers;
    public array $attributes = [];

    public function __construct(array $query = [], array $request = [], array $server = [], array $headers = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
        $this->headers = $headers;
    }

    public static function capture(): self
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        return new self($_GET, $_POST, $_SERVER, $headers);
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri = preg_replace('#^/ERP(/public)?(/index\.php)?#i', '', $uri);
        $uri = preg_replace('#^/public(/index\.php)?#i', '', $uri);
        $uri = preg_replace('#^/index\.php#i', '', $uri);
        return '/' . ltrim($uri, '/');
    }

    public function input(string $key, $default = null)
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function getParsedBody(): array
    {
        return $this->request;
    }

    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    // الدالة التي كانت مفقودة وتسببت في الخطأ
    public function header(string $key, $default = null): ?string
    {
        $normalizedKey = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
        return $this->headers[$normalizedKey] ?? $this->headers[$key] ?? $this->server['HTTP_' . strtoupper(str_replace('-', '_', $key))] ?? $default;
    }
}