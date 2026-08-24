<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepositoryInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Refreshes the Peppol dual-currency conversion pair (`currency_from_to`/
 * `currency_to_from` — the same two settings `SettingRepository::
 * currencyConverter()` already reads for every real invoice conversion,
 * and `App\Webshop\Currency\CurrencyInfoProvider` reads for the storefront
 * display widget) from a live rate instead of requiring an admin to look
 * one up on xe.com and type it in by hand, which is what
 * `partial_settings_peppol.php`'s own "(xe.com)" link next to those
 * fields has always assumed until now.
 *
 * Two callers: `App\Middleware\ExchangeRateAutoUpdateMiddleware` (wired
 * into every `/invoice/*` request via `RoutePermission::invoiceGroup()`
 * — not `App\Invoice\BaseController`'s constructor: that class is
 * extended by ~80 controllers, each hand-forwarding its own constructor
 * params, so a shared middleware avoids touching every one of them, and
 * also covers every way this app can end up in a fully-authenticated
 * session — plain form login, remember-me cookie, TFA-verify, OAuth2 —
 * without threading a call through each one individually), fire-and-
 * forget; and `App\Webshop\Currency\CurrencyController::refreshRate()`
 * (the storefront's own manual "refresh now" button, only rendered when
 * `auto_update_exchange_rate` is on), which wants the
 * `ExchangeRateUpdateResult` back to flash a real outcome.
 *
 * Uses `GuzzleHttp\Client` directly (default-constructed, matching every
 * other direct-HTTP payment gateway service in `src/Invoice/
 * PaymentInformation/Service/`, e.g. `PaystackPaymentService`) rather
 * than a PSR-18 abstraction — same established convention.
 */
final class ExchangeRateUpdateService
{
    private const string API_BASE = 'https://api.frankfurter.app/latest';

    // Deliberately short — this runs inline, inside a page render
    // (BaseController's constructor), not a background job. A slow or
    // hanging FX API must not be able to noticeably delay a page load;
    // updateIfDue() already only attempts this once a day.
    private const float TIMEOUT_SECONDS = 3.0;

    public function __construct(
        private readonly SettingRepositoryInterface $sR,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    public function updateIfDue(): ExchangeRateUpdateResult
    {
        $gate = $this->checkGate();
        if ($gate !== null) {
            return $gate;
        }

        $from = $this->sR->getSetting('currency_code_from');
        $to = $this->sR->getSetting('peppol_document_currency');
        // Nothing to convert (unconfigured), or a same-currency pair —
        // SettingRepository::currencyConverter() already short-circuits
        // that case to a 1:1 passthrough without touching these settings
        // at all, so there is nothing useful to fetch or store here.
        $rate = $from !== '' && $to !== '' && $from !== $to ? $this->fetchRate($from, $to) : null;
        if ($rate === null || $rate <= 0.0) {
            return ExchangeRateUpdateResult::FetchFailed;
        }

        $this->save('currency_from_to', number_format($rate, 4, '.', ''));
        $this->save('currency_to_from', number_format(1 / $rate, 4, '.', ''));
        $this->save('currency_rate_updated_at', $this->today());
        return ExchangeRateUpdateResult::Updated;
    }

    /** Null means "go ahead" — anything else is the final result to return as-is. */
    private function checkGate(): ?ExchangeRateUpdateResult
    {
        if ($this->sR->getSetting('auto_update_exchange_rate') !== '1') {
            return ExchangeRateUpdateResult::Disabled;
        }
        if ($this->updatedToday()) {
            return ExchangeRateUpdateResult::AlreadyCurrent;
        }
        return null;
    }

    private function updatedToday(): bool
    {
        return $this->sR->getSetting('currency_rate_updated_at') === $this->today();
    }

    private function today(): string
    {
        return new \DateTimeImmutable('now')->format('Y-m-d');
    }

    /** Frankfurter (European Central Bank daily reference rates) — free, no API key, no signup. */
    private function fetchRate(string $from, string $to): ?float
    {
        try {
            $response = $this->httpClient->get(self::API_BASE, [
                'query' => ['from' => $from, 'to' => $to],
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::TIMEOUT_SECONDS,
            ]);
            /** @var array{rates?: array<string, mixed>} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            /** @var mixed $value */
            $value = $data['rates'][$to] ?? null;
            return is_numeric($value) ? (float) $value : null;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Exchange rate auto-update failed.', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /** Same find-by-key-then-update-or-create shape as SettingController::tabIndexDebugModeEnsureAllSettingsIncluded(). */
    private function save(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key);
        if ($setting === null) {
            $setting = new Setting();
            $setting->setSettingKey($key);
        }
        $setting->setSettingValue($value);
        $this->sR->save($setting);
    }
}
