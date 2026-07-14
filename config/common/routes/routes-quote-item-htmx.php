<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\QuoteItem\QuoteItemHtmxController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::POST], '/quoteitemhtmx/addProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemHtmxController::class, 'addProduct'])
                ->name('quoteitemhtmx/addProduct'),

            Route::methods([Method::POST], '/quoteitemhtmx/addTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemHtmxController::class, 'addTask'])
                ->name('quoteitemhtmx/addTask'),
        ), // invoice
];
