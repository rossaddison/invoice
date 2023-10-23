<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use App\Invoice\Asset\pciAsset\__Asset;

class amazon_pay_v2_4_Asset extends __Asset
{
    public array $js = [
        // amazon pay v2. using composer require amzn/amazon-pay-api-sdk-php 01-12-2022
        // see https://developer.amazon.com/docs/amazon-pay-checkout/get-set-up-for-integration.html
        'https://static-eu.payments-amazon.com/checkout.js',
    ];
}
