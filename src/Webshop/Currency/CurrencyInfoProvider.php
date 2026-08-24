<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

use App\Invoice\Setting\SettingRepository;
use Yiisoft\Session\SessionInterface;

/**
 * Session-caches this shop's own Peppol dual-currency setting pair for
 * `self::TTL_SECONDS` — an admin-configured setting that changes rarely
 * (manually, via the Peppol settings tab), not worth a `SettingRepository`
 * round trip on every single storefront page view (cart, checkout,
 * product pages, ...) even though it's now an in-process DB call rather
 * than the standalone webshop app's HTTP round trip to `GET /api/currency`
 * — this cache predates the merge and is kept deliberately rather than
 * dropped for no reason (see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md).
 *
 * Reads the exact same four `Setting` rows `App\Api\CurrencyController`
 * (the now-decommissioned `GET /api/currency` HTTP wrapper) used to
 * expose, and the same four `SettingRepository::currencyConverter()`
 * itself converts real invoice amounts with — `currency_code_from`
 * (native), `peppol_document_currency` (document), `currency_from_to`/
 * `currency_to_from` (the two conversion rates).
 */
final readonly class CurrencyInfoProvider
{
    private const SESSION_KEY = 'currency_info_cache';
    private const TTL_SECONDS = 900;

    public function __construct(
        private SessionInterface $session,
        private SettingRepository $settingRepository,
    ) {
    }

    public function get(): ?CurrencyInfo
    {
        /** @var mixed $cached */
        $cached = $this->session->get(self::SESSION_KEY);
        if (is_array($cached) && $this->isFresh($cached)) {
            return $this->fromCache($cached);
        }

        $info = $this->fetch();
        $this->session->set(self::SESSION_KEY, [
            'fetchedAt' => time(),
            'native' => $info?->native,
            'document' => $info?->document,
            'nativeToDocumentRate' => $info?->nativeToDocumentRate,
            'documentToNativeRate' => $info?->documentToNativeRate,
        ]);
        return $info;
    }

    /** A blank `currency_code_from` setting means no currency pair is configured at all (fresh/unset install) — treated as "no info", same as the standalone app's own fail-closed HTTP behaviour when invoice was unreachable. */
    private function fetch(): ?CurrencyInfo
    {
        $native = $this->settingRepository->getSetting('currency_code_from');
        if ($native === '') {
            return null;
        }

        return new CurrencyInfo(
            native: $native,
            document: $this->settingRepository->getSetting('peppol_document_currency'),
            nativeToDocumentRate: self::toFloat($this->settingRepository->getSetting('currency_from_to')),
            documentToNativeRate: self::toFloat($this->settingRepository->getSetting('currency_to_from')),
        );
    }

    /** @param array<array-key, mixed> $cached */
    private function isFresh(array $cached): bool
    {
        /** @var mixed $fetchedAt */
        $fetchedAt = $cached['fetchedAt'] ?? null;
        return is_int($fetchedAt) && (time() - $fetchedAt) < self::TTL_SECONDS;
    }

    /** @param array<array-key, mixed> $cached */
    private function fromCache(array $cached): ?CurrencyInfo
    {
        /** @var mixed $native */
        $native = $cached['native'] ?? null;
        if (!is_string($native) || $native === '') {
            // A cached "no currency pair configured" result.
            return null;
        }

        /** @var mixed $document */
        $document = $cached['document'] ?? null;

        return new CurrencyInfo(
            native: $native,
            document: is_string($document) ? $document : '',
            nativeToDocumentRate: self::toFloat($cached['nativeToDocumentRate'] ?? null),
            documentToNativeRate: self::toFloat($cached['documentToNativeRate'] ?? null),
        );
    }

    private static function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        return is_string($value) && is_numeric($value) ? (float) $value : 0.0;
    }
}
