<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\HomeCareRunImport\HomeCareRunImportController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/homecarerunimport')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'index'])
                ->name('homecarerunimport/index'),
        Route::post('/homecarerunimport/preview')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'preview'])
                ->name('homecarerunimport/preview'),
        Route::post('/homecarerunimport/template')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'template'])
                ->name('homecarerunimport/template'),
        Route::post('/homecarerunimport/upload')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'upload'])
                ->name('homecarerunimport/upload'),
        Route::post('/homecarerunimport/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'save'])
                ->name('homecarerunimport/save'),
        Route::post('/homecarerunimport/export')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunImportController::class, 'export'])
                ->name('homecarerunimport/export'),
    ), // invoice
];
