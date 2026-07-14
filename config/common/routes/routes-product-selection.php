<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Product\ProductSelectionController;
use App\Middleware\RoutePermission;
use Yiisoft\DataResponse\Middleware\JsonDataResponseMiddleware;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/product/selection_quote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductSelectionController::class, 'selectionQuote'])
                ->name('product/selectionQuote'),

            Route::get('/product/selection_inv')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->middleware(JsonDataResponseMiddleware::class)
                ->action([ProductSelectionController::class, 'selectionInv'])
                ->name('product/selectionInv'),
        ), // invoice
];
