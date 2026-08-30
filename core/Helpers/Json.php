<?php
// Path: core/Helpers/Json.php

namespace Core\Helpers;

class Json
{
    /**
     * Safely encode data to JSON.
     */
    public static function encode($value, int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, int $depth = 512): string|false
    {
        $json = json_encode($value, $options, $depth);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        return $json;
    }

    /**
     * Safely decode JSON data.
     */
    public static function decode(string $json, bool $associative = true, int $depth = 512, int $options = 0): mixed
    {
        $data = json_decode($json, $associative, $depth, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $data;
    }
}