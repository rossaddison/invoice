<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * Pins how the public HomeCare signup routes are protected, so removing a limiter is a test failure.
 */
#[Test]
final class HomeCareSignupRoutesTest
{
    /** @return array<string, Route> */
    private function routesByName(): array
    {
        /** @var list<Route> $routes */
        $routes = require dirname(__DIR__, 3) . '/config/common/routes/routes-homecare-signup.php';
        $byName = [];
        foreach ($routes as $route) {
            $byName[$route->getData('name')] = $route;
        }
        return $byName;
    }

    public function signupAcceptsGetAndPostOnly(): void
    {
        $methods = $this->routesByName()['homecare/signup']->getData('methods');

        Assert::same($methods, ['GET', 'POST']);
    }

    public function signupHasTheGlobalAndPerIpLimiters(): void
    {
        // action + global limiter + per-IP limiter
        $middlewares = $this->routesByName()['homecare/signup']->getData('enabledMiddlewares');

        Assert::same(count($middlewares), 3);
    }

    public function confirmHasTheGlobalAndPerIpLimiters(): void
    {
        // action + global limiter + per-IP limiter; both count GET (see RateLimiter::*AnyMethod)
        $middlewares = $this->routesByName()['homecare/signup-confirm']->getData('enabledMiddlewares');

        Assert::same(count($middlewares), 3);
    }

    public function theGenericConfirmRouteHasTheSameLimiters(): void
    {
        /** @var list<Route|Group> $routes */
        $routes = require dirname(__DIR__, 3) . '/config/common/routes/routes-user-inv.php';
        $confirm = null;
        foreach ($routes as $route) {
            if ($route instanceof Route && $route->getData('name') === 'userinv/signup') {
                $confirm = $route;
            }
        }

        Assert::true($confirm instanceof Route);
        Assert::same(count($confirm->getData('enabledMiddlewares')), 3);
    }

    public function confirmChangesStateOnGetAsWellAsPost(): void
    {
        // Characterisation: a mail scanner or link previewer that merely fetches the link with GET
        // consumes the one-time token and runs the whole provisioning.
        $methods = $this->routesByName()['homecare/signup-confirm']->getData('methods');

        Assert::true(in_array('GET', $methods, true));
    }
}
