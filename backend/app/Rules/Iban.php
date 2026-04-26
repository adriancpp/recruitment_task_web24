<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Iban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('The :attribute must be a valid IBAN.');
        }
    }

    public static function isValid(string $iban): bool
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');

        if ($iban === '' || strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $converted = '';

        for ($i = 0, $len = strlen($rearranged); $i < $len; $i++) {
            $char = $rearranged[$i];
            $converted .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;

        for ($i = 0, $len = strlen($converted); $i < $len; $i++) {
            $remainder = ($remainder * 10 + (int) $converted[$i]) % 97;
        }

        return $remainder === 1;
    }
}
