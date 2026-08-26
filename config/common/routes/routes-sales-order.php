<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\SalesOrder\SalesOrderController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
Route::get(
                  '/client_salesorders[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'guest'])
                ->name('salesorder/guest'),

            Route::methods([Method::GET, Method::POST], '/salesorder/agreeToTerms/{url_key}')
                ->name('salesorder/agreeToTerms')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'agreeToTerms']),

            Route::methods([Method::GET, Method::POST], '/salesorder/edit/{id}')
                ->name('salesorder/edit')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/salesorder/pdf/{include}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'pdf'])
                ->name('salesorder/pdf'),

            Route::methods([Method::GET, Method::POST], '/salesorder/reject/{url_key}')
                ->name('salesorder/reject')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'reject']),

            Route::methods([Method::GET, Method::POST], '/salesorder/view/{id}')
                ->name('salesorder/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'view']),

            Route::methods([Method::GET, Method::POST], '/salesorder/urlKey/{key}')
                ->name('salesorder/urlKey')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SalesOrderController::class, 'urlKey']),

            Route::get('/salesorder[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'index'])
                ->name('salesorder/index'),

            Route::methods([Method::GET, Method::POST], '/salesorder/delete/{id}')
                ->name('salesorder/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/salesorder/soToInvoice/{id}')
                ->name('salesorder/soToInvoice')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'soToInvoiceConfirm']),

            Route::methods([Method::GET, Method::POST], '/salesorder/soToInvoiceConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'soToInvoiceConfirm'])
                ->name('salesorder/soToInvoiceConfirm'),

            Route::methods([Method::GET, Method::POST], '/salesorder/changeStatus')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'changeStatus'])
                ->name('salesorder/changeStatus'),

            Route::methods([Method::GET, Method::POST], '/salesorder/sendOrderResponse/{id}')
                ->name('salesorder/sendOrderResponse')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'sendOrderResponse']),

            Route::get('/salesorder/previewOrderResponse/{id}')
                ->name('salesorder/previewOrderResponse')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'previewOrderResponse']),

            Route::methods([Method::GET, Method::POST], '/salesorder/sendOrderResponsePerLine/{id}')
                ->name('salesorder/sendOrderResponsePerLine')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'sendOrderResponsePerLine']),

            Route::get('/salesorder/previewOrderResponsePerLine/{id}')
                ->name('salesorder/previewOrderResponsePerLine')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SalesOrderController::class, 'previewOrderResponsePerLine']),
        ), // invoice
];
