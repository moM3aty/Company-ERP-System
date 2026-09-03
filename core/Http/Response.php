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

    // دالة الحصول على كود الحالة لمنع أخطاء index.php
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function send(): void {
        // التحقق من أن الهيدر لم يتم إرساله مسبقاً لمنع الأخطاء
        if (!headers_sent()) {
            // إزالة أي Content-Type خاطئ مفروض من النظام
            header_remove('Content-Type');
            
            // إجبار المتصفح على قراءة المحتوى كصفحة ويب (HTML)
            header('Content-Type: text/html; charset=UTF-8', true);
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}", true);
            }
        }

        // طباعة الصفحة النهائية
        echo $this->content;
        exit;
    }
}