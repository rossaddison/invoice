<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Cart;

use App\Webshop\Cart\CartService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\SessionInterface;

/**
 * Covers CartService — the session-backed cart, no database row of its
 * own (see CartService's own docblock). `SessionInterface` is faked with
 * a Mockery mock backed by a captured in-memory array rather than a real
 * PHP session, since a real session needs a started request/cookie
 * context this unit test has no reason to stand up.
 */
#[Test]
final class CartServiceTest
{
    private function fakeSession(): SessionInterface
    {
        /** @var array<string, mixed> $store */
        $store = [];
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('get')->andReturnUsing(
            static function (string $key, mixed $default = null) use (&$store): mixed {
                return $store[$key] ?? $default;
            },
        );
        $session->shouldReceive('set')->andReturnUsing(
            /** @param array<int, array{name: string, price: float, quantity: float}> $value */
            static function (string $key, array $value) use (&$store): void {
                $store[$key] = $value;
            },
        );
        $session->shouldReceive('remove')->andReturnUsing(
            static function (string $key) use (&$store): void {
                unset($store[$key]);
            },
        );
        return $session;
    }

    public function addAccumulatesQuantityForTheSameProduct(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->add(1, 'Widget', 9.99, 2.0);
        $cart->add(1, 'Widget', 9.99, 3.0);

        $items = $cart->getItems();
        Assert::count($items, 1);
        Assert::same(5.0, $items[0]->quantity);
        Assert::same(49.95, round($cart->getTotal(), 2));
    }

    public function addIgnoresAZeroOrNegativeProductIdOrQuantity(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->add(0, 'Widget', 9.99, 2.0);
        $cart->add(1, 'Widget', 9.99, 0.0);
        $cart->add(1, 'Widget', 9.99, -1.0);

        Assert::true($cart->isEmpty());
    }

    public function updateQuantityToZeroRemovesTheItem(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->add(1, 'Widget', 9.99, 2.0);
        $cart->updateQuantity(1, 0.0);

        Assert::true($cart->isEmpty());
    }

    public function updateQuantityOnAnUnknownProductDoesNothing(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->updateQuantity(99, 5.0);

        Assert::true($cart->isEmpty());
    }

    public function removeDropsOnlyThatProduct(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->add(1, 'Widget', 9.99, 1.0);
        $cart->add(2, 'Gadget', 4.5, 1.0);
        $cart->remove(1);

        $items = $cart->getItems();
        Assert::count($items, 1);
        Assert::same(2, $items[0]->productId);
    }

    public function clearEmptiesTheCart(): void
    {
        $cart = new CartService($this->fakeSession());
        $cart->add(1, 'Widget', 9.99, 1.0);
        $cart->clear();

        Assert::true($cart->isEmpty());
        Assert::same(0.0, $cart->getTotal());
    }
}
