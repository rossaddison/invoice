<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\HomeCareVisit\HomeCareVisitController;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/homecarevisit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([HomeCareVisitController::class, 'index'])
                ->name('homecarevisit/index'),
    ), // invoice
];
