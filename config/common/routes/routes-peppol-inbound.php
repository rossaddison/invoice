<?php

declare(strict_types=1);

use App\Invoice\Peppol\PeppolInboundController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // No auth middleware: Oxalis AS4 access point calls this directly.
            Route::methods([Method::GET, Method::POST], '/peppol/inbound/delivery')
                ->action([PeppolInboundController::class, 'delivery'])
                ->name('peppol/inbound/delivery'),
        ), // invoice
];
