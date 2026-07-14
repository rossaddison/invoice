<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Permissions;
use Closure;
use Yiisoft\Auth\Middleware\Authentication;
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
        return Group::create('/invoice')
            ->middleware(Authentication::class)
            ->middleware(self::check(Permissions::ENTRY_TO_BASE_CONTROLLER))
            ->routes(...$routes);
    }
}
