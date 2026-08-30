<?php
// Path: core/Helpers/functions.php

/**
 * File: core/Helpers/functions.php
 * الدوال المساعدة للنظام (Helpers)
 */

if (!function_exists('env')) {
    function env($key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $baseUrl = rtrim(env('APP_URL', '/ERP'), '/');
        $path = ltrim($path, '/');
        return "{$baseUrl}/public/{$path}";
    }
}

if (!function_exists('url')) {
    function url($path) {
        $baseUrl = rtrim(env('APP_URL', '/ERP'), '/');
        $path = ltrim($path, '/');
        return "{$baseUrl}/{$path}";
    }
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        echo '<div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; font-family: monospace; border-radius: 8px; margin: 20px; direction: ltr; text-align: left;">';
        foreach ($vars as $v) {
            echo '<pre>';
            var_dump($v);
            echo '</pre>';
        }
        echo '</div>';
        die(1);
    }
}

if (!function_exists('has_permission')) {
    function has_permission($permissionCode) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] == 1) {
            return true;
        }
        $userPermissions = $_SESSION['permissions'] ?? [];
        return in_array($permissionCode, $userPermissions);
    }
}

if (!function_exists('current_company')) {
    function current_company() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['company_id'] ?? 1;
    }
}

if (!function_exists('current_company_id')) {
    function current_company_id(): int {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }
}

if (!function_exists('current_company_name')) {
    function current_company_name(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        if ($isAr) {
            return $_SESSION['company_name_ar'] ?? $_SESSION['company_name'] ?? 'مؤسسة نور الثقة';
        }
        return $_SESSION['company_name_en'] ?? $_SESSION['company_name'] ?? 'NOUR TRUST ERP';
    }
}

if (!function_exists('current_currency')) {
    function current_currency(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['currency'] ?? $_SESSION['company_currency'] ?? 'EGP';
    }
}

if (!function_exists('current_branch')) {
    function current_branch() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return !empty($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : null;
    }
}

if (!function_exists('is_hq')) {
    function is_hq() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['is_hq']) && $_SESSION['is_hq'] === true;
    }
}

if (!function_exists('__')) {
    function __(string $ar_text, string $en_text) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        return $isAr ? $ar_text : $en_text;
    }
}

if (!function_exists('isRtl')) {
    function isRtl(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return ($_SESSION['locale'] ?? 'ar') === 'ar';
    }
}

// ==========================================
// دوال العملات والتسعير (تم إضافتها لمنع التكرار)
// ==========================================
if (!function_exists('get_exchange_rate')) {
    function get_exchange_rate($targetCurrency = null): float {
        $currency = $targetCurrency ?? current_currency();
        $rates = ['EGP' => 1.0, 'USD' => 0.02, 'SAR' => 0.075, 'EUR' => 0.018];
        return $rates[strtoupper($currency)] ?? 1.0;
    }
}

if (!function_exists('convert_amount')) {
    function convert_amount($amount, $targetCurrency = null): float {
        return (float)$amount * get_exchange_rate($targetCurrency);
    }
}

if (!function_exists('convert_to_base')) {
    function convert_to_base($amount): float {
        $rate = get_exchange_rate();
        return $rate > 0 ? ((float)$amount / $rate) : (float)$amount;
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, int $decimals = 2): string {
        $formatted = number_format((float)$amount, $decimals);
        $curr = current_currency();
        return "{$formatted} {$curr}";
    }
}