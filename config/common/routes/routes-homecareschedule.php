<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\HomeCareSchedule\HomeCareScheduleController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/homecareschedule')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareScheduleController::class, 'index'])
                ->name('homecareschedule/index'),
        Route::post('/homecareschedule/preview')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareScheduleController::class, 'preview'])
                ->name('homecareschedule/preview'),
        Route::post('/homecareschedule/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareScheduleController::class, 'save'])
                ->name('homecareschedule/save'),
        Route::post('/homecareschedule/clear')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareScheduleController::class, 'clear'])
                ->name('homecareschedule/clear'),
    ), // invoice
];
