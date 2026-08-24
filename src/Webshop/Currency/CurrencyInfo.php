<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

/**
 * This shop's own Peppol dual-currency setup (`SettingRepository`'s
 * `currency_code_from`/`peppol_document_currency`/`currency_from_to`/
 * `currency_to_from` settings — see `CurrencyInfoProvider`), not an
 * arbitrary multi-currency price list: a single admin-configured
 * native/document currency pair plus the two conversion rates between
 * them, the same values `SettingRepository::currencyConverter()` uses to
 * convert real invoice amounts. Rates are whatever the admin last typed
 * in manually (via xe.com) — not a live market rate.
 */
final readonly class CurrencyInfo
{
    public function __construct(
        public string $native,
        public string $document,
        public float $nativeToDocumentRate,
        public float $documentToNativeRate,
    ) {
    }

    /**
     * A fresh/default install has `native === document` (both default to
     * the same currency, rate 1.00) — nothing to actually offer a
     * currency switch over in that case.
     */
    public function hasDualCurrency(): bool
    {
        return $this->native !== '' && $this->document !== '' && $this->native !== $this->document;
    }
}
