<?php
// Path: core/Helpers/Number.php

namespace Core\Helpers;

class Number
{
    /**
     * Format a number with grouped thousands and configurable decimals.
     */
    public static function format(float $number, int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a number as currency based on the system configuration.
     * (Simplified version)
     */
    public static function currency(float $amount, string $currencyCode = 'EGP'): string
    {
        return self::format($amount) . ' ' . $currencyCode;
    }

    /**
     * Calculate percentage.
     */
    public static function percentage(float $part, float $total, int $decimals = 2): float
    {
        if ($total == 0) {
            return 0.0;
        }
        return round(($part / $total) * 100, $decimals);
    }
}