<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\QuoteItem\QuoteItemController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/quoteitem')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'index'])
                ->name('quoteitem/index'),
        Route::methods([Method::POST], '/quoteitem/addProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'addProduct'])
                ->name('quoteitem/addProduct'),
        Route::methods([Method::POST], '/quoteitem/addTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'addTask'])
                ->name('quoteitem/addTask'),
        Route::methods([Method::GET, Method::POST], '/quoteitem/editProduct/{id}')
                ->name('quoteitem/editProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'editProduct']),
        Route::methods([Method::GET, Method::POST], '/quoteitem/editTask/{id}')
                ->name('quoteitem/editTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'editTask']),
        Route::methods([Method::GET, Method::POST], '/quoteitem/delete/{id}')
                ->name('quoteitem/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/quoteitem/multiple')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'multiple'])
                ->name('quoteitem/deleteMultiple'),
        Route::methods([Method::GET, Method::POST], '/quoteitem/view/{id}')
                ->name('quoteitem/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemController::class, 'view']),
    ), // invoice
];
