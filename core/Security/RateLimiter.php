<?php
// Path: core/Security/RateLimiter.php

namespace Core\Security;

/**
 * محدد معدل الطلبات (Rate Limiter).
 * ملاحظة: يجب ربطه مستقبلاً بمحرك Cache (مثل Redis) ليعمل بكفاءة عالية في الأنظمة الموزعة.
 */
class RateLimiter
{
    // محاكاة التخزين في الذاكرة (للتبسيط، يجب استبدالها بـ Cache Interface)
    private static array $store = [];

    /**
     * تسجيل طلب جديد وتحديث العداد.
     * 
     * @return int عدد الطلبات المتبقية
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $now = time();
        
        if (!isset(self::$store[$key])) {
            self::$store[$key] = [
                'count' => 1,
                'expires_at' => $now + $decaySeconds
            ];
            return $maxAttempts - 1;
        }

        if ($now > self::$store[$key]['expires_at']) {
            self::$store[$key] = [
                'count' => 1,
                'expires_at' => $now + $decaySeconds
            ];
            return $maxAttempts - 1;
        }

        self::$store[$key]['count']++;
        
        return max(0, $maxAttempts - self::$store[$key]['count']);
    }

    /**
     * التحقق مما إذا كان قد تم تجاوز الحد المسموح.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if (!isset(self::$store[$key])) {
            return false;
        }
        
        if (time() > self::$store[$key]['expires_at']) {
            return false;
        }

        return self::$store[$key]['count'] > $maxAttempts;
    }
}