<?php
// Path: core/Http/Response.php

namespace Core\Http;

class Response {
    protected string $content = '';
    protected int $statusCode = 200;
    protected array $headers = [];

    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void {
        // إجبار المتصفح على قراءة المخرج كـ HTML
        header('Content-Type: text/html; charset=UTF-8');
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
        exit;
    }
}