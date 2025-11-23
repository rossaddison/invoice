<?php

declare(strict_types=1);

use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Libraries\Crypt;
use Psr\Log\LoggerInterface;


$construct = '__construct()';

return [
    AmazonPayPaymentService::class => [
        $construct => [
            'settingRepository' => SettingRepository::class,
        ],
    ],
    BraintreePaymentService::class => [
        $construct => [
            'settings' => SettingRepository::class,
            'logger' => LoggerInterface::class,
            
        ],
    ],
    StripePaymentService::class => [
        $construct => [
            'settings' => SettingRepository::class,
        ],
    ],
];
