<?php
// Path: core/Http/JsonResponse.php

namespace Core\Http;

/**
 * استجابة مخصصة للـ API تقوم بتحويل البيانات إلى JSON تلقائياً.
 */
class JsonResponse extends Response
{
    public function __construct($data = null, int $statusCode = 200, array $headers = [])
    {
        parent::__construct('', $statusCode, $headers);
        
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->setData($data);
    }

    /**
     * تعيين وتشفير البيانات إلى JSON.
     */
    public function setData($data): self
    {
        // استخدام خيارات JSON لمنع تحويل الحروف العربية والرموز لكيانات غير مقروءة
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('البيانات المدخلة لا يمكن تحويلها إلى صيغة JSON: ' . json_last_error_msg());
        }

        return $this;
    }
}