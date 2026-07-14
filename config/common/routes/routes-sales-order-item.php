<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\SalesOrderItem\SalesOrderItemController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST], '/salesorderitem/edit/{id}')
                ->name('salesorderitem/edit')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderItemController::class, 'edit']),
        ), // invoice
];
