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
 * convert real invoice amounts. Rates are either whatever the admin last
 * typed in manually (via xe.com), or — if `auto_update_exchange_rate` is
 * on — a once-daily live fetch, see `App\Invoice\Helpers\Peppol\
 * ExchangeRateUpdateService`. Either way, never assume it's this exact
 * instant's rate: $rateUpdatedAt is the honest "as of" caveat the
 * storefront widget shows next to it, rather than presenting the
 * conversion as a live fact it never was, even under auto-update.
 */
final readonly class CurrencyInfo
{
    public function __construct(
        public string $native,
        public string $document,
        public float $nativeToDocumentRate,
        public float $documentToNativeRate,
        // 'Y-m-d', or null when never set (a rate typed in before this
        // existed, or an install that's never had one auto-fetched).
        public ?string $rateUpdatedAt = null,
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
