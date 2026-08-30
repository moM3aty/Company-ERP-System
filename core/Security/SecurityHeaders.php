<?php
// Path: core/Security/SecurityHeaders.php

namespace Core\Security;

/**
 * يوفر مجموعة من رؤوس HTTP الأمنية القياسية لحماية التطبيق من المتصفح.
 */
class SecurityHeaders
{
    /**
     * جلب مصفوفة الهيدرز الأمنية.
     */
    public static function getHeaders(): array
    {
        return [
            // منع تحميل التطبيق داخل iFrame (حماية Clickjacking)
            'X-Frame-Options' => 'SAMEORIGIN',
            // منع المتصفح من تخمين نوع الملف (MIME Sniffing)
            'X-Content-Type-Options' => 'nosniff',
            // تفعيل حماية المتصفح الافتراضية ضد XSS
            'X-XSS-Protection' => '1; mode=block',
            // إجبار المتصفح على استخدام HTTPS (HSTS)
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            // سياسة مصادر المحتوى (تقييد تحميل السكربتات من مصادر خارجية)
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;",
            // التحكم في معلومات الـ Referer المرسلة للمواقع الخارجية
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // تقييد وصول الموقع لبعض ميزات الجهاز (الكاميرا، المايكروفون)
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
        ];
    }
}