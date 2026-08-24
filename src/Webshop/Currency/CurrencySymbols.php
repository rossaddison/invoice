<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

/**
 * Display-only lookups keyed by ISO 4217 currency code — invoice's own
 * currency setup (`SettingRepository`, see `CurrencyInfoProvider`) only
 * ever carries bare codes, never a symbol or flag, so the storefront
 * supplies both for whatever pair an admin happens to have configured.
 * Covers a reasonably broad set of real-world currencies; an unmapped
 * code still renders correctly (the code itself, or no flag), it just
 * won't get a symbol/flag.
 */
final class CurrencySymbols
{
    private const SYMBOLS = [
        'GBP' => '£', 'EUR' => '€', 'USD' => '$', 'CNY' => '¥', 'JPY' => '¥',
        'DKK' => 'kr', 'SEK' => 'kr', 'NOK' => 'kr', 'CHF' => 'CHF', 'AUD' => 'A$',
        'CAD' => 'C$', 'NZD' => 'NZ$', 'INR' => '₹', 'ZAR' => 'R', 'HKD' => 'HK$',
        'SGD' => 'S$', 'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft', 'RON' => 'lei',
        'BRL' => 'R$', 'MXN' => 'MX$', 'AED' => 'د.إ',
    ];

    /** flagcdn.com's own lowercase country codes — 'eu' is its documented special case for the EU flag. */
    private const COUNTRIES = [
        'GBP' => 'gb', 'EUR' => 'eu', 'USD' => 'us', 'CNY' => 'cn', 'DKK' => 'dk',
        'JPY' => 'jp', 'SEK' => 'se', 'NOK' => 'no', 'CHF' => 'ch', 'AUD' => 'au',
        'CAD' => 'ca', 'NZD' => 'nz', 'INR' => 'in', 'ZAR' => 'za', 'HKD' => 'hk',
        'SGD' => 'sg', 'PLN' => 'pl', 'CZK' => 'cz', 'HUF' => 'hu', 'RON' => 'ro',
        'BRL' => 'br', 'MXN' => 'mx', 'AED' => 'ae',
    ];

    /** For the "£ - GBP - British Pound" style row the currency picker dropdown renders — matches Amazon's own currency-picker wording. */
    private const NAMES = [
        'GBP' => 'British Pound', 'EUR' => 'Euro', 'USD' => 'US Dollar', 'CNY' => 'Chinese Yuan',
        'JPY' => 'Japanese Yen', 'DKK' => 'Danish Krone', 'SEK' => 'Swedish Krona', 'NOK' => 'Norwegian Krone',
        'CHF' => 'Swiss Franc', 'AUD' => 'Australian Dollar', 'CAD' => 'Canadian Dollar', 'NZD' => 'New Zealand Dollar',
        'INR' => 'Indian Rupee', 'ZAR' => 'South African Rand', 'HKD' => 'Hong Kong Dollar', 'SGD' => 'Singapore Dollar',
        'PLN' => 'Polish Zloty', 'CZK' => 'Czech Koruna', 'HUF' => 'Hungarian Forint', 'RON' => 'Romanian Leu',
        'BRL' => 'Brazilian Real', 'MXN' => 'Mexican Peso', 'AED' => 'UAE Dirham',
    ];

    public static function format(float $amount, string $currencyCode): string
    {
        $symbol = self::SYMBOLS[$currencyCode] ?? null;
        if ($symbol !== null) {
            return $symbol . number_format($amount, 2);
        }
        return $currencyCode !== ''
            ? $currencyCode . ' ' . number_format($amount, 2)
            : number_format($amount, 2);
    }

    public static function flagCountryCode(string $currencyCode): ?string
    {
        return self::COUNTRIES[$currencyCode] ?? null;
    }

    /** "£ - GBP - British Pound" — falls back to the bare code when a symbol/name isn't mapped. */
    public static function pickerLabel(string $currencyCode): string
    {
        $symbol = self::SYMBOLS[$currencyCode] ?? null;
        $name = self::NAMES[$currencyCode] ?? null;

        if ($symbol === null || $name === null) {
            return $currencyCode;
        }
        return $symbol . ' - ' . $currencyCode . ' - ' . $name;
    }
}
