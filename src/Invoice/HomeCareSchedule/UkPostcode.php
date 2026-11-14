<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

/**
 * A UK postcode as people type it ("ab12cd", " AB1  2CD ", "AB1 2CD") put into the one form Google
 * Maps and the street lists use: upper case, one space before the last three characters.
 */
final class UkPostcode
{
    /** Outward code (area + district), then the inward code (sector digit + unit letters). */
    public const string PATTERN = '/^([A-Z]{1,2}\d[A-Z\d]?)\s*(\d[A-Z]{2})$/';

    /**
     * The canonical postcode, or null when the text is not a UK postcode.
     */
    public static function normalise(string $text): ?string
    {
        $text = strtoupper(trim($text));
        if (1 !== preg_match(self::PATTERN, $text, $m)) {
            return null;
        }
        return $m[1] . ' ' . $m[2];
    }

    public static function isValid(string $text): bool
    {
        return null !== self::normalise($text);
    }
}
