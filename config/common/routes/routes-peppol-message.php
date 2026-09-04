<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Peppol\PeppolMessageController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/peppol/messages')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PeppolMessageController::class, 'index'])
                ->name('peppol/messages/index'),
        Route::get('/peppol/messages/view/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PeppolMessageController::class, 'view'])
                ->name('peppol/messages/view'),
    ), // invoice
];
