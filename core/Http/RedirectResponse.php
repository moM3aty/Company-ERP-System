<?php
// Path: core/Http/RedirectResponse.php

namespace Core\Http;

class RedirectResponse extends Response {
    protected string $targetUrl;

    public function __construct(string $url, int $status = 302, array $headers = []) {
        $this->targetUrl = $url;
        $this->statusCode = $status;
        $this->headers = array_merge([
            'Location' => $url
        ], $headers);
    }

    public function send(): void {
        // 1. تنظيف أي مخرجات معلقة في الـ Buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. إرسال الهيدر الصريح للتوجيه في حال لم يُرسل مسبقاً
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        // 3. توجيه احتياطي ذكي لمنع الشاشة البيضاء نهائياً
        echo "<!DOCTYPE html><html><head>";
        echo "<meta http-equiv='refresh' content='0;url=" . htmlspecialchars($this->targetUrl) . "'>";
        echo "<script>window.location.href = " . json_encode($this->targetUrl) . ";</script>";
        echo "</head><body>";
        echo "<p>جاري التوجيه تلقائياً... <a href='" . htmlspecialchars($this->targetUrl) . "'>اضغط هنا إذا لم يتم التوجيه</a></p>";
        echo "</body></html>";
        
        exit;
    }
}