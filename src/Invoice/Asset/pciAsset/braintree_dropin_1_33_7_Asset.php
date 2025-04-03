<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

class braintree_dropin_1_33_7_Asset extends __Asset
{
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        '//assets.braintreegateway.com/web/dropin/1.33.7/css/dropin.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        '//js.braintreegateway.com/web/dropin/1.33.7/js/dropin.min.js',
    ];
}
