<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use Yiisoft\View\WebView;

/**
 * Registered from within payment_information_worldpay_pci.php only,
 * not the shared layouts — loads only on the Worldpay payment page
 * rather than app-wide (the mistake originally made with
 * StripeVersionTenAsset, fixed later the same day that gateway was
 * added — see AdyenAsset's own docblock for the same reasoning).
 *
 * Points at the sandbox (Try) host unconditionally for now — this
 * app's Worldpay integration is not live-tested yet. Before going
 * live: swap to `https://access.worldpay.com/access-checkout/v2/checkout.js`
 * — confirmed real from Worldpay's own docs, not a guessed URL
 * pattern (the two hosts differ only in dropping `try.`).
 */
class WorldpayAsset extends Asset
{
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'https://try.access.worldpay.com/access-checkout/v2/checkout.js',
    ];

    // Load in <head> so the Worldpay global is defined before the
    // end-of-body IIFE runs, matching every other gateway's Asset here.
    public ?int $jsPosition = WebView::POSITION_HEAD;
}
