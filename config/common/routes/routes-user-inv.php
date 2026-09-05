<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\UserInv\UserInvController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

// Two shape-identical route families below (each differing only in
// path/name/action) -- extracted rather than repeated, per this repo's
// own stated priority on reducing SonarQube string/code duplication
// (see CLAUDE.md). $adminUserInvRoute: the admin userinv/* CRUD/role
// actions, gated by EDIT_INV. $guestUserInvRoute: the observer's own
// self-service actions (view/edit their own UserInv row, list-size and
// sticky-navbar/grid-header preferences), gated by EDIT_USER_INV instead
// -- an observer manages their own preferences, not the admin userinv/*
// CRUD screens.
$adminUserInvRoute = static fn(string $path, string $name, string $action): Route =>
    Route::methods([Method::GET, Method::POST], $path)
        ->name($name)
        ->middleware(RoutePermission::check(Permissions::EDIT_INV))
        ->action([UserInvController::class, $action]);

$guestUserInvRoute = static fn(string $path, string $name, string $action): Route =>
    Route::methods([Method::GET, Method::POST], $path)
        ->name($name)
        ->middleware(RoutePermission::check(Permissions::EDIT_USER_INV))
        ->action([UserInvController::class, $action]);

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

        // Guest self-service -- see App\Invoice\UserInv\UserInvController::
        // guest()/guestlimit()/guestStickyNavbar()/guestStickyGridHeader().
        $guestUserInvRoute('/userinv/guest', 'userinv/guest', 'guest'),
        $guestUserInvRoute(
            '/userinv/guestlimit/{userinv_id}/{limit}/{origin}',
            'userinv/guestlimit',
            'guestlimit',
        ),
        $guestUserInvRoute(
            '/userinv/gueststickynavbar/{userinv_id}/{origin}',
            'userinv/guestStickyNavbar',
            'guestStickyNavbar',
        ),
        $guestUserInvRoute(
            '/userinv/gueststickygridheader/{userinv_id}/{origin}',
            'userinv/guestStickyGridHeader',
            'guestStickyGridHeader',
        ),

        // Admin userinv/* CRUD and role-assignment actions.
        $adminUserInvRoute('/userinv/client/{id}', 'userinv/client', 'client'),
        $adminUserInvRoute('/userinv/delete/{id}', 'userinv/delete', 'delete'),
        $adminUserInvRoute('/userinv/view/{id}', 'userinv/view', 'view'),
        $adminUserInvRoute('/userinv/accountant/{user_id}', 'userinv/accountant', 'assignAccountantRole'),
        $adminUserInvRoute('/userinv/revoke/{user_id}', 'userinv/revoke', 'revokeAllRoles'),
        $adminUserInvRoute('/userinv/observer/{user_id}', 'userinv/observer', 'assignObserverRole'),
        $adminUserInvRoute('/userinv/sync-rbac-link/{user_id}', 'userinv/sync-rbac-link', 'syncRbacLink'),
        $adminUserInvRoute('/userinv/admin/{user_id}', 'userinv/admin', 'assignAdminRole'),
        $adminUserInvRoute('/userinv/worker/{user_id}', 'userinv/worker', 'assignWorkerRole'),
    ), // invoice

    // Not under RoutePermission::invoiceGroup(): a brand-new invitee clicking
    // this emailed link has no app session yet. Secured by the masked,
    // time-limited token in the URL itself, not RBAC — the group's
    // Authentication middleware would otherwise reject every call before
    // the token is ever checked.
    Route::methods(
        [Method::GET, Method::POST],
        '/userinv/signup/{language}/{token}/{tokenType}'
    )
        ->name('userinv/signup')
        ->action([UserInvController::class, 'signup']),
];
