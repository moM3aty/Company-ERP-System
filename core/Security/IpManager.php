<?php
// Path: core/Security/IpManager.php

namespace Core\Security;

use Core\Http\Request;

/**
 * مدير عناوين الـ IP.
 * لاستخراج الـ IP الحقيقي (حتى خلف الـ Proxies/Cloudflare) والتحقق من القوائم البيضاء/السوداء.
 */
class IpManager
{
    /**
     * استخراج الـ IP الحقيقي للعميل.
     */
    public static function getClientIp(Request $request): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (isset($request->server[$key]) && !empty($request->server[$key])) {
                $ips = explode(',', $request->server[$key]);
                $ip = trim($ips[0]); // أخذ أول IP في السلسلة
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * التحقق مما إذا كان الـ IP يقع ضمن نطاق معين (CIDR).
     */
    public static function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) == $subnet;
    }
}