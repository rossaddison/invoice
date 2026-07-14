<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PaymentInformation\PaymentInformationController as PICLR;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

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
                    '/paymentinformation/stripeIncomplete/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PICLR::class, 'stripeIncomplete'])
                ->name('paymentinformation/stripeIncomplete'),

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
        ), // invoice
];
