<?php

namespace App\Support;

/**
 * Validação de CPF e CNPJ (dígitos verificadores).
 */
class Document
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    public static function isValid(?string $value): bool
    {
        $d = static::digits($value);

        return match (strlen($d)) {
            11 => static::isValidCpf($d),
            14 => static::isValidCnpj($d),
            default => false,
        };
    }

    protected static function isValidCpf(string $d): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $d)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $d[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $d[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    protected static function isValidCnpj(string $d): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $d)) {
            return false;
        }

        $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];

        foreach ([12, 13] as $k => $len) {
            $sum = 0;
            for ($i = 0; $i < $len; $i++) {
                $sum += (int) $d[$i] * $weights[$k][$i];
            }
            $rest = $sum % 11;
            $digit = $rest < 2 ? 0 : 11 - $rest;
            if ((int) $d[$len] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
