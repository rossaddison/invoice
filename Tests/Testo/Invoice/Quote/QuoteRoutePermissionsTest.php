<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Auth\Permissions;
use App\Invoice\Quote\QuoteController;
use App\Middleware\AccessChecker;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * quote/quoteToSoConfirm creates a sales order from the quote id, client and group in the request and checks
 * nothing else, so it must stay behind the staff permission: with VIEW_INV any observer could convert any
 * quote. The only button that calls it (QuoteToolbar) is already shown to edit.inv only.
 */
#[Test]
final class QuoteRoutePermissionsTest
{
    /** @param list<Route|Group> $items */
    private function find(array $items, string $name): ?Route
    {
        foreach ($items as $item) {
            if ($item instanceof Group) {
                /** @var list<Route|Group> $inner */
                $inner = $item->getData('routes');
                $found = $this->find($inner, $name);
                if (null !== $found) {
                    return $found;
                }
            } elseif ($item->getData('name') === $name) {
                return $item;
            }
        }
        return null;
    }

    /** The permission the route's own permission middleware asks the access checker for. */
    private function permissionOf(string $routeName): ?string
    {
        /** @var list<Route|Group> $items */
        $items = require dirname(__DIR__, 4) . '/config/common/routes/routes-quote.php';
        $route = $this->find($items, $routeName);
        Assert::true($route instanceof Route, $routeName);

        $asked = null;
        $checker = TypedMock::of(AccessChecker::class);
        $checker->shouldReceive('withPermission')->andReturnUsing(
            static function (string $permission) use (&$asked, $checker): AccessChecker {
                $asked = $permission;
                return $checker;
            },
        );
        foreach ($route->getData('enabledMiddlewares') as $middleware) {
            if ($middleware instanceof \Closure) {
                $middleware($checker);
            }
        }
        return $asked;
    }

    public function quoteToSalesOrderConfirmIsStaffOnly(): void
    {
        Assert::same($this->permissionOf('quote/quoteToSoConfirm'), Permissions::EDIT_INV);
    }

    public function itStillReachesTheSameAction(): void
    {
        /** @var list<Route|Group> $items */
        $items = require dirname(__DIR__, 4) . '/config/common/routes/routes-quote.php';
        $route = $this->find($items, 'quote/quoteToSoConfirm');
        Assert::true($route instanceof Route);
        $middlewares = $route->getData('enabledMiddlewares');
        Assert::same(end($middlewares), [QuoteController::class, 'quoteToSoConfirm']);
    }
}
