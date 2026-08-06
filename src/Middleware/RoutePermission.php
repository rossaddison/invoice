<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Permissions;
use Closure;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * Shared route-config helpers to avoid repeating the same permission-check
 * closure and invoice-group wrapper across every file in
 * config/common/routes/.
 */
final class RoutePermission
{
    public static function check(string $permission): Closure
    {
        return static fn (AccessChecker $checker) => $checker->withPermission($permission);
    }

    public static function invoiceGroup(Route|Group ...$routes): Group
    {
        // Yiisoft\Auth\Middleware\Authentication used to sit alongside this
        // middleware. Removed: it requires a Yiisoft\Auth\AuthenticatorInterface
        // DI binding that has never existed anywhere in this app (no
        // implementation, no config binding). That made this middleware
        // unconstructable — every route wrapped by invoiceGroup() (almost the
        // entire /invoice/* app) 500s with a DI NotFoundException the moment
        // this middleware is actually resolved. self::check() below is the
        // same, real, working RBAC gate every other protected route relies on
        // alone; see the identical fix/rationale in routes-backend.php.
        return Group::create('/invoice')
            ->middleware(self::check(Permissions::ENTRY_TO_BASE_CONTROLLER))
            ->routes(...$routes);
    }
}
