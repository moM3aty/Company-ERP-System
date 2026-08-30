<?php
// Path: core/Security/EncryptionManager.php

namespace Core\Security;

use RuntimeException;

/**
 * مدير التشفير ثنائي الاتجاه (Two-Way Encryption).
 * يستخدم لتشفير البيانات الحساسة مثل مفاتيح الـ API الخارجية باستخدام AES-256-CBC.
 */
class EncryptionManager
{
    private string $key;
    private string $cipher = 'aes-256-cbc';

    public function __construct(string $appKey)
    {
        // يجب أن يكون المفتاح بطول 32 بايت (Base64 decoded)
        $this->key = base64_decode(str_replace('base64:', '', $appKey));
        
        if (strlen($this->key) !== 32) {
            throw new RuntimeException("Encryption key must be exactly 32 bytes for AES-256.");
        }
    }

    /**
     * تشفير نص مع إضافة MAC للتحقق من سلامة البيانات.
     */
    public function encrypt(string $value): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);
        
        if ($encrypted === false) {
            throw new RuntimeException("Could not encrypt the data.");
        }

        $mac = hash_hmac('sha256', $iv . $encrypted, $this->key);
        $json = json_encode(compact('iv', 'encrypted', 'mac'));

        return base64_encode($json);
    }

    /**
     * فك التشفير بعد التحقق من عدم العبث بالبيانات (MAC Verification).
     */
    public function decrypt(string $payload): string
    {
        $payload = json_decode(base64_decode($payload), true);

        if (!$this->validPayload($payload)) {
            throw new RuntimeException("The payload is invalid or manipulated.");
        }

        $decrypted = openssl_decrypt($payload['encrypted'], $this->cipher, $this->key, 0, $payload['iv']);

        if ($decrypted === false) {
            throw new RuntimeException("Could not decrypt the data.");
        }

        return $decrypted;
    }

    private function validPayload(mixed $payload): bool
    {
        if (!is_array($payload) || !isset($payload['iv'], $payload['encrypted'], $payload['mac'])) {
            return false;
        }

        $calculatedMac = hash_hmac('sha256', $payload['iv'] . $payload['encrypted'], $this->key);
        return hash_equals($calculatedMac, $payload['mac']);
    }
}