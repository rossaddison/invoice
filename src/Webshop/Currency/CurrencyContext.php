<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

/**
 * What every storefront view actually renders a price through —
 * constructor-injected per request (via `App\Webshop\Controller\
 * StorefrontController`'s controllers / `StorefrontViewParameters`), never
 * registered as a global `yiisoft/view` common parameter the way `$s`/
 * `$session`/etc. are for the staff-facing app (`config/common/params.php`
 * `'yiisoft/view' => ['parameters' => [...]]`).
 *
 * Deliberately lazy: `CurrencyInfoProvider::get()` (and hence the session
 * it reads/writes) is only ever touched the first time a view actually
 * calls a method here, via `resolve()` — never eagerly at construction
 * time. This is a hard-won constraint, not a style preference: the
 * standalone webshop app this was merged from (see
 * docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md) once wired an earlier
 * version of this exact class as a common view parameter built by an
 * eager DI factory closure — that factory ran as soon as the container
 * built the parameter, well before `SessionMiddleware` got a chance to
 * read the incoming session cookie, so `CurrencyInfoProvider`'s own
 * session write became the very first thing in the request to touch PHP's
 * session, starting a fresh random session ID every time and silently
 * breaking session persistence — confirmed live. Keeping resolution
 * strictly inside a request-scoped method call (never a container
 * factory) is what avoids that regression here.
 *
 * Display only — nothing here changes what any price actually *is*.
 * Every float the storefront ever handles (`ProductListing::$price`,
 * cart items, order totals) is the catalog's own native-currency amount;
 * `format()` only ever converts a copy for rendering.
 * `App\Webshop\Checkout\CheckoutForm`/`App\Webshop\Cart\CartService` are
 * entirely untouched by this class — the same "display preference, never
 * what's actually billed" principle already established for
 * `App\Webshop\Delivery`.
 */
final class CurrencyContext
{
    private ?CurrencyInfo $info = null;
    private bool $resolved = false;

    public function __construct(
        private readonly CurrencyInfoProvider $provider,
        private readonly CurrencyPreferenceService $preferenceService,
    ) {
    }

    public function isDualCurrency(): bool
    {
        return $this->info()?->hasDualCurrency() ?? false;
    }

    public function isShowingDocumentCurrency(): bool
    {
        return $this->isDualCurrency() && $this->preferenceService->get() === CurrencyPreferenceService::DOCUMENT;
    }

    public function info(): ?CurrencyInfo
    {
        if (!$this->resolved) {
            $this->info = $this->provider->get();
            $this->resolved = true;
        }
        return $this->info;
    }

    /** The ISO code actually being displayed right now — null only when the currency setup couldn't be resolved at all. */
    public function activeCode(): ?string
    {
        $info = $this->info();
        if ($info === null) {
            return null;
        }
        return $this->isShowingDocumentCurrency() ? $info->document : $info->native;
    }

    /**
     * @param float $nativePrice A price in this shop's native currency —
     *     which is every price the storefront ever has, see this class's
     *     own docblock.
     */
    public function format(float $nativePrice): string
    {
        $info = $this->info();
        if ($info === null) {
            return number_format($nativePrice, 2);
        }
        if ($this->isShowingDocumentCurrency()) {
            return CurrencySymbols::format($nativePrice * $info->nativeToDocumentRate, $info->document);
        }
        return CurrencySymbols::format($nativePrice, $info->native);
    }
}
