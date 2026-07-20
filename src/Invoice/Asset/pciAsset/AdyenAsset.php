<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use Yiisoft\View\WebView;

/**
 * Registered from within payment_information_adyen_pci.php only, not the
 * shared layouts — loads only on the Adyen payment page rather than
 * app-wide (the mistake originally made with StripeVersionTenAsset, fixed
 * later the same day this gateway was added).
 *
 * Points at the test CDN unconditionally for now — this codebase isn't
 * live-testing Adyen yet (see docs/... testing notes). Before going live:
 * Adyen's live CDN is region-specific
 * (https://docs.adyen.com/development-resources/live-endpoints) —
 * checkoutshopper-live.adyen.com only covers the default/NZ region; AU/US/
 * APSE etc. need their own subdomain. The exact SDK version below should
 * also be re-verified against https://docs.adyen.com/online-payments/release-notes
 * before going live; deliberately not SRI-pinned here since a stale/wrong
 * integrity hash would silently break script loading entirely, which is
 * worse than no integrity check while this is still sandbox-only.
 *
 * v6 (bumped from v5.40.0): v5 hit a hard client-side error — "The
 * following properties should not be passed to the client: askDonation" —
 * because Adyen's /sessions response now always includes the Giving/
 * Donation-related `askDonation` field, and v5's Drop-in rejects any
 * session field it doesn't recognise. v6.36.0+ added real support for that
 * field. This also required corresponding changes in payment-adyen.ts (the
 * CDN global renamed AdyenCheckout -> AdyenWeb, Drop-in creation moved from
 * checkout.create('dropin') to `new AdyenWeb.Dropin(checkout, ...)`, and
 * countryCode became a mandatory AdyenCheckout() config field) — see that
 * file and AdyenPaymentController::adyenInForm() for the countryCode
 * plumbing. Confirmed against the real UMD bundle (not just docs): v6's
 * window.AdyenWeb object is `{Core, AdyenCheckout, createComponent, Dropin,
 * ...componentClasses}` — there is no longer a bare window.AdyenCheckout.
 */
class AdyenAsset extends Asset
{
    private const string SDK_VERSION = '6.41.0';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'https://checkoutshopper-test.adyen.com/checkoutshopper/sdk/'
            . self::SDK_VERSION . '/adyen.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'https://checkoutshopper-test.adyen.com/checkoutshopper/sdk/'
            . self::SDK_VERSION . '/adyen.js',
    ];

    // Load in <head> so the AdyenCheckout global is defined before the
    // end-of-body IIFE runs, matching StripeVersionTenAsset's approach.
    public ?int $jsPosition = WebView::POSITION_HEAD;
}
