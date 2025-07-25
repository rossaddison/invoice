<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

class stripe_v10_Asset extends __Asset
{
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        // stripe v10 15-11-2022  ./stripe/css/checkout.css
        // Related logic: see paymentinformation/form
        // Related logic: see ...views/invoice/paymentinformation/paymentinformation.php
        'stripe/css/checkout.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        // stripe v10 15-11-2022
        '//js.stripe.com/v3/',
    ];
}
