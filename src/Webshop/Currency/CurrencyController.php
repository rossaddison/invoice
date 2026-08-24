<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

use App\Invoice\Enum\FlashScope;
use App\Invoice\Helpers\Peppol\ExchangeRateUpdateResult;
use App\Invoice\Helpers\Peppol\ExchangeRateUpdateService;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

final readonly class CurrencyController
{
    use FlashMessage;

    public function __construct(
        private WebControllerService $webService,
        private CurrencyPreferenceService $preferenceService,
        private ExchangeRateUpdateService $exchangeRateUpdateService,
        private TranslatorInterface $translator,
        // Not read directly in this class — the FlashMessage trait's own
        // flashMessage() method reads/writes $this->flash (same pattern
        // as App\Webshop\Checkout\CheckoutController's own $flash
        // property).
        private Flash $flash,
    ) {
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();

        $this->preferenceService->set((string) ($body['preference'] ?? ''));

        // The `redirect` field is a hidden input the storefront layout
        // fills in with `(string) $currentRoute->getUri()` (see
        // resources/views/layout/templates/storefront/main.php) — see
        // WebControllerService::getRedirectToSameOriginPathResponse()'s
        // own docblock for why it's still validated here.
        return $this->webService->getRedirectToSameOriginPathResponse((string) ($body['redirect'] ?? '/'));
    }

    /**
     * The "Refresh now" button next to "Change currency" — only rendered
     * at all when `auto_update_exchange_rate` is on (see the storefront
     * layout). Public/unauthenticated, same as every other `/shop`
     * route, but rate-limited (`RateLimiter::perIp()`, see
     * routes-shop.php) and effectively harmless even if hit
     * repeatedly: `ExchangeRateUpdateService::updateIfDue()` — the exact
     * same method `App\Middleware\ExchangeRateAutoUpdateMiddleware`
     * already calls unconditionally on every staff page load — only
     * ever does the real fetch once per calendar day regardless of how
     * many times this is called; every call after the first that day is
     * a cheap no-op. The worst a stranger spamming this button can do is
     * decide *when* during the day the already-going-to-happen daily
     * refresh occurs, not whether it happens or how often.
     */
    public function refreshRate(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->exchangeRateUpdateService->updateIfDue();
        $this->flashMessage(
            $result === ExchangeRateUpdateResult::FetchFailed ? 'danger' : 'info',
            $this->translator->translate('webshop.currency.refresh.' . match ($result) {
                ExchangeRateUpdateResult::Updated => 'updated',
                ExchangeRateUpdateResult::AlreadyCurrent => 'already.current',
                ExchangeRateUpdateResult::Disabled => 'disabled',
                ExchangeRateUpdateResult::FetchFailed => 'failed',
            }),
            FlashScope::Shop,
        );

        $body = (array) $request->getParsedBody();
        return $this->webService->getRedirectToSameOriginPathResponse((string) ($body['redirect'] ?? '/'));
    }
}
