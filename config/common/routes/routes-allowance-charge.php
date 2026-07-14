<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\AllowanceCharge\AllowanceChargeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/allowancecharge')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'index'])
                ->name('allowancecharge/index'),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/addAllowance')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'addAllowance'])
                ->name('allowancecharge/addAllowance'),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/addCharge')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'addCharge'])
                ->name('allowancecharge/addCharge'),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/editAllowance/{id}')
                ->name('allowancecharge/editAllowance')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'editAllowance']),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/editCharge/{id}')
                ->name('allowancecharge/editCharge')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'editCharge']),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/delete/{id}')
                ->name('allowancecharge/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'delete']),

            Route::methods([Method::GET, Method::POST],
                    '/allowancecharge/view/{id}')
                ->name('allowancecharge/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AllowanceChargeController::class, 'view']),
        ), // invoice
];
