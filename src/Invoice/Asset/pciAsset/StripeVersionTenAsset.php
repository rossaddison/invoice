<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use Yiisoft\View\WebView;

class StripeVersionTenAsset extends Asset
{
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        // stripe v10 2025-06-30.basil  ./stripe/css/checkout.css
        // Related logic: see paymentinformation/form
        // Related logic: see ...views/invoice/paymentinformation/paymentinformation.php
        'stripe/css/checkout.css',
    ];

    public array $cssOptions = [
        'media' => 'print',
        'onload' => "this.media='all'",
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        // stripe v10 2025-06-30.basil
        'https://js.stripe.com/v3/',
    ];

    // Load in <head> so the Stripe global is defined before the end-of-body IIFE runs.
    public ?int $jsPosition = WebView::POSITION_HEAD;
}
