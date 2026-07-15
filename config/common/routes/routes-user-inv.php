<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\UserInv\UserInvController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // UserInv
            Route::get('/userinv[/page/{page:\d+}[/active/{active}]]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'index'])
                ->name('userinv/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/userinv/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'add'])
                ->name('userinv/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/userinv/edit/{id}')
                ->name('userinv/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/userinv/guest')
                ->name('userinv/guest')
                ->middleware(RoutePermission::check(Permissions::EDIT_USER_INV))
                ->action([UserInvController::class, 'guest']),

            Route::methods([Method::GET, Method::POST],
                    '/userinv/guestlimit/{userinv_id}/{limit}/{origin}')
                ->name('userinv/guestlimit')
                ->middleware(RoutePermission::check(Permissions::EDIT_USER_INV))
                ->action([UserInvController::class, 'guestlimit']),

            Route::methods([Method::GET, Method::POST], '/userinv/client/{id}')
                ->name('userinv/client')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'client']),

            Route::methods([Method::GET, Method::POST], '/userinv/delete/{id}')
                ->name('userinv/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/userinv/view/{id}')
                ->name('userinv/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'view']),

            Route::methods([Method::GET, Method::POST], '/userinv/accountant/{user_id}')
                ->name('userinv/accountant')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'assignAccountantRole']),

            Route::methods([Method::GET, Method::POST], '/userinv/revoke/{user_id}')
                ->name('userinv/revoke')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'revokeAllRoles']),

            Route::methods([Method::GET, Method::POST], '/userinv/observer/{user_id}')
                ->name('userinv/observer')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'assignObserverRole']),

            Route::methods([Method::GET, Method::POST], '/userinv/sync-rbac-link/{user_id}')
                ->name('userinv/sync-rbac-link')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'syncRbacLink']),

            Route::methods([Method::GET, Method::POST], '/userinv/admin/{user_id}')
                ->name('userinv/admin')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UserInvController::class, 'assignAdminRole']),
        ), // invoice

    // Not under RoutePermission::invoiceGroup(): a brand-new invitee clicking
    // this emailed link has no app session yet. Secured by the masked,
    // time-limited token in the URL itself, not RBAC — the group's
    // Authentication middleware would otherwise reject every call before
    // the token is ever checked.
    Route::methods([Method::GET, Method::POST],
            '/userinv/signup/{language}/{token}/{tokenType}')
        ->name('userinv/signup')
        ->action([UserInvController::class, 'signup']),
];
