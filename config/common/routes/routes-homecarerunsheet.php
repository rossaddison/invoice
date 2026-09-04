<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/homecarerunsheet')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'index'])
                ->name('homecarerunsheet/index'),
        Route::post('/homecarerunsheet/export')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'export'])
                ->name('homecarerunsheet/export'),
        Route::get('/homecarerunsheet/review/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'review'])
                ->name('homecarerunsheet/review'),
        Route::get('/homecarerunsheet/download/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'downloadSpreadsheet'])
                ->name('homecarerunsheet/download'),
        Route::post('/homecarerunsheet/upload/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'uploadScan'])
                ->name('homecarerunsheet/upload'),
        Route::post('/homecarerunsheet/save/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'save'])
                ->name('homecarerunsheet/save'),
        Route::post('/homecarerunsheet/apply/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareRunSheetController::class, 'apply'])
                ->name('homecarerunsheet/apply'),
    ), // invoice
];
