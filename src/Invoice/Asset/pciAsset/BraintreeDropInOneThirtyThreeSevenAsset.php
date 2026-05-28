<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use Yiisoft\View\WebView;

class BraintreeDropInOneThirtyThreeSevenAsset extends Asset
{
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'https://assets.braintreegateway.com/web/dropin/1.33.7/css/dropin.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'https://js.braintreegateway.com/web/dropin/1.33.7/js/dropin.min.js',
    ];

    // Load in <head> so the Braintree global is defined before the end-of-body IIFE runs.
    public ?int $jsPosition = WebView::POSITION_HEAD;
}
