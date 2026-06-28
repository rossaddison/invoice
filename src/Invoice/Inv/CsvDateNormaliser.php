<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

final class CsvDateNormaliser
{
    private const FORMATS = ['Y-m-d', 'd/m/y', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y'];

    public static function normalise(string $value): string
    {
        if ($value === '') {
            return '';
        }
        foreach (self::FORMATS as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }
        return '';
    }
}
