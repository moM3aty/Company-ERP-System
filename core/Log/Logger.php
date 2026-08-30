<?php
// Path: core/Log/Logger.php

namespace Core\Log;

/**
 * نظام تسجيل الأخطاء (Logger).
 * يقوم بكتابة السجلات في ملفات نصية بناءً على مستوى الخطورة.
 */
class Logger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/\\') . '/';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logEntry = "[{$date}] [{$level}] {$message} {$contextString}" . PHP_EOL;
        
        $filename = $this->logPath . 'app-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND);
    }
}