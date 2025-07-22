<?php

declare(strict_types=1);

use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\Inv\InvController;
use App\Invoice\Inv\InvService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\Libraries\Crypt;
use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

return [
    InvController::class => [
        'class'         => InvController::class,
        '__construct()' => [
            'crypt'                              => new Crypt(),
            DataResponseFactoryInterface::class  => DataResponseFactory::class,
            DelRepo::class                       => DelRepo::class,
            InvAmountService::class              => InvAmountService::class,
            InvService::class                    => InvService::class,
            InvCustomService::class              => InvCustomService::class,
            InvItemService::class                => InvItemService::class,
            InvItemAllowanceChargeService::class => InvItemAllowanceChargeService::class,
            InvTaxRateService::class             => InvTaxRateService::class,
            LoggerInterface::class               => LoggerInterface::class,
            MailerInterface::class               => MailerInterface::class,
            UrlGeneratorInterface::class         => UrlGenerator::class,
            SessionInterface::class              => SessionInterface::class,
            SR::class                            => SR::class,
            TranslatorInterface::class           => Translator::class,
            UserService::class                   => UserService::class,
            ViewRenderer::class                  => ViewRenderer::class,
            WebControllerService::class          => WebControllerService::class,
            Flash::class                         => Flash::class,
        ],
    ],
];
