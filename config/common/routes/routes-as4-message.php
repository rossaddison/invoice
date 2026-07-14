<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\As4\As4MessageController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
Route::get('/as4/messages')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([As4MessageController::class, 'index'])
                ->name('as4message/index'),

            Route::get('/as4/messages/view/{id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([As4MessageController::class, 'view'])
                ->name('as4message/view'),
        ), // invoice
];
