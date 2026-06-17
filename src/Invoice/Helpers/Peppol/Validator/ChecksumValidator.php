<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

/**
 * Pure checksum algorithms for Peppol endpoint and party ID scheme validation.
 * All methods are stateless and static; no XPath or translator dependency.
 */
final class ChecksumValidator
{
    private const string REGEX_10_DIGITS = '/^\d{10}$/';
    private const string REGEX_11_DIGITS = '/^\d{11}$/';

    public static function checkGLN(string $val): bool
    {
        if (!preg_match('/^\d+$/', $val)) {
            return false;
        }
        $len        = strlen($val);
        $checkDigit = (int) $val[$len - 1];
        $main       = array_reverse(str_split(substr($val, 0, $len - 1)));
        $sum        = 0;
        foreach ($main as $i => $d) {
            $sum += (int) $d * (1 + (($i + 1) % 2) * 2);
        }
        return (10 - ($sum % 10)) % 10 === $checkDigit;
    }

    public static function checkMod11(string $val): bool
    {
        if (!preg_match('/^\d+$/', $val) || (float) $val <= 0) {
            return false;
        }
        $len        = strlen($val);
        $checkDigit = (int) $val[$len - 1];
        $main       = array_reverse(str_split(substr($val, 0, $len - 1)));
        $sum        = 0;
        foreach ($main as $i => $d) {
            $sum += (int) $d * (($i % 6) + 2);
        }
        return (11 - ($sum % 11)) % 11 === $checkDigit;
    }

    public static function checkMod97BE(string $val): bool
    {
        if (!preg_match(self::REGEX_10_DIGITS, $val)) {
            return false;
        }
        $check      = (int) substr($val, 8, 2);
        $calculated = 97 - ((int) substr($val, 0, 8) % 97);
        return $check === $calculated;
    }

    public static function checkDanishCVR(string $val): bool
    {
        if (strlen($val) === 10 && str_starts_with($val, 'DK')) {
            return (bool) preg_match('/^DK\d{8}$/', $val);
        }
        return (bool) preg_match('/^\d{8}$/', $val);
    }

    public static function checkDanishCC(string $val): bool
    {
        return (bool) preg_match(self::REGEX_10_DIGITS, $val);
    }

    public static function checkDanishERSTORG(string $val): bool
    {
        return (bool) preg_match('/^DK\d{8}$/', $val);
    }

    public static function checkCodiceIPA(string $val): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{6}$/', $val);
    }

    public static function checkItalianPIVA(string $val): int
    {
        /** @var array<int,int> $doubleMap */
        $doubleMap = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9];
        $sum = 0;
        $len = strlen($val);
        for ($i = 0; $i < $len; $i++) {
            $d    = (int) $val[$i];
            $sum += ($i % 2 === 0) ? $d : $doubleMap[$d];
        }
        return $sum % 10;
    }

    public static function checkCF(string $val): bool
    {
        $val = trim($val);
        if (strlen($val) === 16) {
            return (bool) preg_match(
                '/^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/i',
                $val
            );
        }
        if (strlen($val) === 11 && preg_match(self::REGEX_11_DIGITS, $val)) {
            return self::checkItalianPIVA($val) === 0;
        }
        return false;
    }

    public static function checkPIVAseIT(string $val): bool
    {
        return preg_match(self::REGEX_11_DIGITS, $val) === 1
            && self::checkItalianPIVA($val) === 0;
    }

    public static function checkSEOrgnr(string $val): bool
    {
        if (!preg_match(self::REGEX_10_DIGITS, $val)) {
            return false;
        }
        $main       = strrev(substr($val, 0, 9));
        $checkDigit = (int) $val[9];
        $sum        = 0;
        for ($i = 0; $i < 9; $i++) {
            $n = (int) $main[$i];
            if ($i % 2 === 0) {
                $d    = $n * 2;
                $sum += ($d % 10) + intdiv($d, 10);
            } else {
                $sum += $n;
            }
        }
        return (10 - $sum % 10) % 10 === $checkDigit;
    }

    public static function checkABN(string $val): bool
    {
        if (!preg_match(self::REGEX_11_DIGITS, $val)) {
            return false;
        }
        /** @var array<int<0,10>, int> $weights */
        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $digits  = str_split($val);
        $digits[0] = (string) ((int) $digits[0] - 1);
        $sum = 0;
        for ($i = 0; $i <= 10; $i++) {
            $sum += (int) $digits[$i] * $weights[$i];
        }
        return $sum % 89 === 0;
    }
}
