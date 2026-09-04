<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\UserClient\UserClientController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::methods([Method::GET, Method::POST], '/userclient/new/{user_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserClientController::class, 'new'])
                ->name('userclient/new'),
        Route::methods([Method::GET, Method::POST], '/userclient/delete/{id}')
                ->name('userclient/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserClientController::class, 'delete']),
    ), // invoice
];
