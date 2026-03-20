<?php
namespace App\Helpers;

class Validation {
    public static function str($value, bool $nullable = false): ?string {
        if ($nullable && ($value === null || $value === '')) return null;
        if (!is_string($value)) return null;
        return trim($value);
    }

    public static function int($value, bool $nullable = false): ?int {
        if ($nullable && ($value === null || $value === '')) return null;
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    }

    public static function date($value, bool $nullable = false): ?string {
        if ($nullable && ($value === null || $value === '')) return null;
        $t = strtotime($value);
        return $t ? date('Y-m-d', $t) : null;
    }
}
