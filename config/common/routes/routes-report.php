<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Report\ReportController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/report')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'index'])
                ->name('report/index'),
        Route::methods([Method::GET, Method::POST], '/sales_by_client_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'salesByClientIndex'])
                ->name('report/salesByClientIndex'),
        Route::methods([Method::GET, Method::POST], '/sales_by_product_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'salesByProductIndex'])
                ->name('report/salesByProductIndex'),
        Route::methods([Method::GET, Method::POST], '/sales_by_task_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'salesByTaskIndex'])
                ->name('report/salesByTaskIndex'),
        Route::methods([Method::GET, Method::POST], '/sales_by_year_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'salesByYearIndex'])
                ->name('report/salesByYearIndex'),
        Route::methods([Method::GET, Method::POST], '/invoice_aging_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'invoiceAgingIndex'])
                ->name('report/invoiceAgingIndex'),
        Route::methods([Method::GET, Method::POST], '/payment_history_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'paymentHistoryIndex'])
                ->name('report/paymentHistoryIndex'),
        Route::methods([Method::GET, Method::POST], '/sales_by_year')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ReportController::class, 'salesByYear'])
                ->name('report/salesByYear'),
    ), // invoice
];
