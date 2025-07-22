<?php

declare(strict_types=1);

use App\Invoice\Libraries\Crypt;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\Setting\SettingRepository;

return [
    AmazonPayPaymentService::class => [
        '__construct()' => [
            'settingRepository' => SettingRepository::class,
            'crypt'             => Crypt::class,
        ],
    ],
    StripePaymentService::class => [
        '__construct()' => [
            'settingRepository' => SettingRepository::class,
            'crypt'             => Crypt::class,
        ],
    ],
];
