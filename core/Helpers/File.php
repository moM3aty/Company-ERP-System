<?php
// Path: core/Helpers/File.php

namespace Core\Helpers;

/**
 * دوال مساعدة عامة للتعامل مع نظام الملفات.
 */
class File
{
    /**
     * جلب حجم الملف بصيغة مقروءة (MB, KB).
     */
    public static function formatSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * التأكد من وجود المجلد وإنشاؤه إن لم يكن موجوداً.
     */
    public static function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}