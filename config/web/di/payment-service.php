<?php

declare(strict_types=1);

use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\Libraries\Crypt;

return [
    AmazonPayPaymentService::class => [
        '__construct()' => [
            'crypt' => new Crypt(),
            'salt' => (new Crypt())->salt(),
        ],
    ],
    BraintreePaymentService::class => [
        '__construct()' => [
            'crypt' => new Crypt(),
            'salt' => (new Crypt())->salt(),
        ],
    ],
    StripePaymentService::class => [
        '__construct()' => [
            'crypt' => new Crypt(),
            'salt' => (new Crypt())->salt(),
        ],
    ],
];
