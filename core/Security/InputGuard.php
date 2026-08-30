<?php
// Path: core/Security/InputGuard.php

namespace Core\Security;

/**
 * حارس المدخلات.
 * يفحص المصفوفات (مثل $_POST) بعمق لمنع هجمات XSS وحقن الأكواد.
 */
class InputGuard
{
    /**
     * تنظيف مصفوفة من المدخلات بشكل عميق (Recursive).
     */
    public static function sanitizeArray(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $safeKey = self::cleanString($key);
            
            if (is_array($value)) {
                $clean[$safeKey] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $clean[$safeKey] = self::cleanString($value);
            } else {
                $clean[$safeKey] = $value; // (Int, Bool, Float) تترك كما هي
            }
        }
        return $clean;
    }

    /**
     * تنظيف نص من أكواد HTML و JavaScript.
     */
    public static function cleanString(string $value): string
    {
        // إزالة الفراغات الزائدة
        $value = trim($value);
        // تحويل الرموز الخاصة إلى كيانات HTML لمنع التنفيذ
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $value;
    }
}