<?php
// Path: core/Config/Config.php

namespace Core\Config;

/**
 * مدير الإعدادات (Configuration Manager).
 * يقرأ ملفات الإعدادات من مجلد config/ ويسمح بالوصول إليها.
 */
class Config
{
    private array $items = [];

    public function __construct(string $configPath)
    {
        $this->loadConfigFiles($configPath);
    }

    private function loadConfigFiles(string $path): void
    {
        if (!is_dir($path)) return;

        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $this->items[$name] = require $file;
        }
    }

    /**
     * جلب قيمة باستخدام نقطة (Dot Notation). مثال: 'database.connections.mysql.host'
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $array = $this->items;

        foreach ($parts as $part) {
            if (isset($array[$part])) {
                $array = $array[$part];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $array = &$this->items;

        foreach ($parts as $part) {
            if (!isset($array[$part]) || !is_array($array[$part])) {
                $array[$part] = [];
            }
            $array = &$array[$part];
        }

        $array = $value;
    }
}