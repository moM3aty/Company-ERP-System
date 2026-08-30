<?php
// Path: core/Security/HashManager.php

namespace Core\Security;

/**
 * مدير التشفير أحادي الاتجاه (Hashing).
 * يستخدم لتشفير كلمات المرور بطريقة آمنة (Bcrypt / Argon2).
 */
class HashManager
{
    private string $algo;
    private array $options;

    public function __construct(string $algo = PASSWORD_BCRYPT, array $options = ['cost' => 12])
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    /**
     * إنشاء Hash لكلمة المرور.
     */
    public function make(string $value): string
    {
        return password_hash($value, $this->algo, $this->options);
    }

    /**
     * التحقق من تطابق النص مع الـ Hash.
     */
    public function check(string $value, string $hashedValue): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }
        return password_verify($value, $hashedValue);
    }

    /**
     * التحقق مما إذا كان الـ Hash يحتاج إلى إعادة تشفير (بسبب تغيير خوارزمية أو زيادة الـ Cost).
     */
    public function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, $this->algo, $this->options);
    }
}