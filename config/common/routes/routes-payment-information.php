<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PaymentInformation\AdyenPaymentController as APICLR;
use App\Invoice\PaymentInformation\GoCardlessPaymentController as GCPICLR;
use App\Invoice\PaymentInformation\PaymentInformationController as PICLR;
use App\Invoice\PaymentInformation\PaymentRefundController as PRCLR;
use App\Invoice\PaymentInformation\RobokassaPaymentController as RPICLR;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/adyenInForm/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([APICLR::class, 'adyenInForm'])
                ->name('paymentinformation/adyenInForm'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/adyenComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([APICLR::class, 'adyenComplete'])
                ->name('paymentinformation/adyenComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/goCardlessInForm/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([GCPICLR::class, 'goCardlessInForm'])
                ->name('paymentinformation/goCardlessInForm'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/goCardlessComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([GCPICLR::class, 'goCardlessComplete'])
                ->name('paymentinformation/goCardlessComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/amazonComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'amazonComplete'])
                ->name('paymentinformation/amazonComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/braintreeComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'braintreeComplete'])
                ->name('paymentinformation/braintreeComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/mollieComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'mollieComplete'])
                ->name('paymentinformation/mollieComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/openbankingOauthComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'openbankingOauthComplete'])
                ->name('paymentinformation/openbankingOauthComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/openbankingTokenComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'openbankingTokenComplete'])
                ->name('paymentinformation/openbankingTokenComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/stripeComplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'stripeComplete'])
                ->name('paymentinformation/stripeComplete'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/wonderfulComplete/{url_key}/{ref}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'wonderfulComplete'])
                ->name('paymentinformation/wonderfulComplete'),

            Route::methods([Method::GET, Method::POST],
             '/paymentinformation/tinkComplete/{url_key}/{payment_request_id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'tinkComplete'])
                ->name('paymentinformation/tinkComplete'),

            Route::methods([Method::GET, Method::POST], '/paymentinformation/fetch')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'fetch'])
                ->name('paymentinformation/fetch'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/inform/{url_key}/{gateway}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'inform'])
                ->name('paymentinformation/inform'),

            Route::methods([Method::GET, Method::POST],
                    '/paymentinformation/refund/{payment_id}/{gateway}')
                ->middleware(RoutePermission::check(Permissions::EDIT_PAYMENT))
                ->action([PRCLR::class, 'refund'])
                ->name('paymentinformation/refund'),
        ), // invoice

    // Not under RoutePermission::invoiceGroup(): Stripe's servers must be able
    // to POST here with no app session. Secured by Stripe-Signature
    // verification in the controller, not RBAC — see
    // StripePaymentService::verifyWebhookSignature() and
    // App\Middleware\CsrfExemptMiddleware (this path is also exempt from the
    // global CSRF check, which would otherwise reject every such call before
    // it reaches the controller).
    Route::methods([Method::POST], '/paymentinformation/stripeWebhook')
        ->action([PICLR::class, 'stripeWebhook'])
        ->name('paymentinformation/stripeWebhook'),

    // Not under RoutePermission::invoiceGroup(): Adyen's servers must be able
    // to POST here with no app session. Secured by HMAC signature
    // verification in the controller, not RBAC — see
    // AdyenPaymentService::verifyWebhookNotification() and
    // App\Middleware\CsrfExemptMiddleware.
    Route::methods([Method::POST], '/paymentinformation/adyenWebhook')
        ->action([APICLR::class, 'adyenWebhook'])
        ->name('paymentinformation/adyenWebhook'),

    // Not under RoutePermission::invoiceGroup(): GoCardless's servers must
    // be able to POST here with no app session. Secured by
    // Webhook-Signature verification in the handler, not RBAC — see
    // GoCardlessPaymentService::isValidWebhookSignature() and
    // App\Middleware\CsrfExemptMiddleware.
    Route::methods([Method::POST], '/paymentinformation/goCardlessWebhook')
        ->action([GCPICLR::class, 'goCardlessWebhook'])
        ->name('paymentinformation/goCardlessWebhook'),

    // Not under RoutePermission::invoiceGroup(): Robokassa's servers must be
    // able to POST here with no app session. Secured by signature
    // verification in the controller, not RBAC — see
    // RobokassaPaymentService::verifyResultUrlCallback() and
    // App\Middleware\CsrfExemptMiddleware.
    Route::methods([Method::POST], '/paymentinformation/robokassaWebhook')
        ->action([RPICLR::class, 'robokassaWebhook'])
        ->name('paymentinformation/robokassaWebhook'),
];
