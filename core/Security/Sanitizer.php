<?php
// Path: core/Security/Sanitizer.php

namespace Core\Security;

/**
 * منظف البيانات المتقدم.
 * يستخدم لتنظيف أنواع معينة من البيانات قبل حفظها في قاعدة البيانات.
 */
class Sanitizer
{
    public static function email(?string $email): string
    {
        return filter_var(trim($email ?? ''), FILTER_SANITIZE_EMAIL);
    }

    public static function url(?string $url): string
    {
        return filter_var(trim($url ?? ''), FILTER_SANITIZE_URL);
    }

    public static function numeric(?string $number): float|int
    {
        $clean = preg_replace('/[^0-9.]/', '', $number ?? '');
        return strpos($clean, '.') !== false ? (float)$clean : (int)$clean;
    }

    public static function phone(?string $phone): string
    {
        // الاحتفاظ بالأرقام وعلامة + فقط
        return preg_replace('/[^0-9+]/', '', $phone ?? '');
    }

    public static function integer(?string $int): int
    {
        return (int) filter_var($int, FILTER_SANITIZE_NUMBER_INT);
    }
}