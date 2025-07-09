<?php

use App\Invoice\Quote\QuoteController;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\Inv\InvService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as soAS;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as soCS;
use App\Invoice\SalesOrderItem\SalesOrderItemService as soIS;
use App\Invoice\SalesOrder\SalesOrderService as soS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as soTRS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteCustom\QuoteCustomService;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\Quote\QuoteService;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService;
use Psr\Log\LoggerInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

return [
    QuoteController::class => [
        'class' => QuoteController::class,
        '__construct()' => [
            ArrayCache::class => ArrayCache::class,    
            DataResponseFactoryInterface::class => DataResponseFactory::class,
            InvAmountService::class => InvAmountService::class,
            InvService::class => InvService::class,
            InvCustomService::class => InvCustomService::class,
            InvItemService::class => InvItemService::class,
            InvTaxRateService::class => InvTaxRateService::class,
            LoggerInterface::class => LoggerInterface::class,
            MailerInterface::class => MailerInterface::class,
            soAS::class => soAS::class,
            soCS::class => soCS::class,
            soIS::class => soIS::class,
            soS::class => soS::class,
            soTRS::class => soTRS::class,
            QuoteAmountService::class => QuoteAmountService::class,
            QuoteCustomService::class => QuoteCustomService::class,
            QuoteItemService::class => QuoteItemService::class,
            QuoteService::class => QuoteService::class,
            QuoteTaxRateService::class => QuoteTaxRateService::class,
            UrlGenerator::class => UrlGenerator::class,
            SessionInterface::class => SessionInterface::class,
            SR::class => SR::class,
            TranslatorInterface::class => TranslatorInterface::class, 
            UserService::class => UserService::class,
            ViewRenderer::class => ViewRenderer::class,
            WebControllerService::class => WebControllerService::class,
            Flash::class => Flash::class,
        ],
    ],
];