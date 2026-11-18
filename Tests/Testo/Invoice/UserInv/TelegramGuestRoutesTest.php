<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Invoice\UserInv\UserInvController;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * The two Telegram actions on the signed-in user's own profile page are registered, reach the right
 * controller methods, and sit behind a permission check (the same one as the rest of the profile page).
 */
#[Test]
final class TelegramGuestRoutesTest
{
    /**
     * @return array<string, Route> every route in the file, by name, however deeply it is nested in groups
     */
    private function routesByName(): array
    {
        /** @var list<Route|Group> $items */
        $items = require dirname(__DIR__, 4) . '/config/common/routes/routes-user-inv.php';
        $byName = [];
        $this->collect($items, $byName);
        return $byName;
    }

    /**
     * @param list<Route|Group> $items
     * @param array<string, Route> $byName
     */
    private function collect(array $items, array &$byName): void
    {
        foreach ($items as $item) {
            if ($item instanceof Group) {
                /** @var list<Route|Group> $inner */
                $inner = $item->getData('routes');
                $this->collect($inner, $byName);
            } else {
                $byName[$item->getData('name')] = $item;
            }
        }
    }

    /**
     * Route::action() is kept as the last of the enabled middlewares.
     */
    private function actionOf(Route $route): mixed
    {
        $middlewares = $route->getData('enabledMiddlewares');
        return end($middlewares);
    }

    public function theLinkRouteReachesTheLinkAction(): void
    {
        $route = $this->routesByName()['userinv/guestTelegramLink'] ?? null;

        Assert::true($route instanceof Route);
        Assert::same($route->getData('pattern'), '/userinv/guest/telegram-link');
        Assert::same($this->actionOf($route), [UserInvController::class, 'guestTelegramLink']);
    }

    public function theUnlinkRouteReachesTheUnlinkAction(): void
    {
        $route = $this->routesByName()['userinv/guestTelegramUnlink'] ?? null;

        Assert::true($route instanceof Route);
        Assert::same($route->getData('pattern'), '/userinv/guest/telegram-unlink');
        Assert::same($this->actionOf($route), [UserInvController::class, 'guestTelegramUnlink']);
    }

    public function bothRoutesCarryAPermissionCheck(): void
    {
        foreach (['userinv/guestTelegramLink', 'userinv/guestTelegramUnlink'] as $name) {
            $route = $this->routesByName()[$name] ?? null;
            Assert::true($route instanceof Route, $name);
            // the permission check, then the action (which Route keeps as its last middleware)
            Assert::same(count($route->getData('enabledMiddlewares')), 2, $name);
        }
    }

    public function theActionsExistWithTheArgumentsTheContainerWillInject(): void
    {
        $link = new \ReflectionMethod(UserInvController::class, 'guestTelegramLink');
        $unlink = new \ReflectionMethod(UserInvController::class, 'guestTelegramUnlink');

        Assert::same(array_map(static fn (\ReflectionParameter $p): string => $p->getName(), $link->getParameters()), [
            'request', 'uiR', 'links', 'logger',
        ]);
        Assert::same(array_map(static fn (\ReflectionParameter $p): string => $p->getName(), $unlink->getParameters()), [
            'request', 'uiR', 'links',
        ]);
    }
}
