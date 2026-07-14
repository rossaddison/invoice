<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CommonErrors\CommonErrorsController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/commonerrors/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CommonErrorsController::class, 'index'])
                ->name('commonerrors/index'),
        ), // invoice
];
