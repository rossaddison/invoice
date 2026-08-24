<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Delivery;

use App\Webshop\Delivery\DeliveryAddressService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\SessionInterface;

/**
 * Covers DeliveryAddressService — the navbar "Deliver to" widget's
 * session-only storage (see that class's own docblock). Same
 * fake-session-backed-by-an-array approach as CartServiceTest.
 */
#[Test]
final class DeliveryAddressServiceTest
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

    public function getReturnsNullWhenNothingWasEverSet(): void
    {
        $service = new DeliveryAddressService($this->fakeSession());

        Assert::null($service->get());
    }

    public function setThenGetRoundTripsAllFourFields(): void
    {
        $service = new DeliveryAddressService($this->fakeSession());
        $service->set('John', 'Smith', 'Glasgow', 'G32 6AB');

        $address = $service->get();
        Assert::notNull($address);
        Assert::same('John', $address->name);
        Assert::same('Smith', $address->surname);
        Assert::same('Glasgow', $address->city);
        Assert::same('G32 6AB', $address->postcode);
        Assert::same('John Smith', $address->fullName());
        Assert::same('Glasgow G32 6AB', $address->locationLine());
    }

    public function setTrimsWhitespaceFromEveryField(): void
    {
        $service = new DeliveryAddressService($this->fakeSession());
        $service->set('  John  ', '  Smith  ', '  Glasgow  ', '  G32 6AB  ');

        $address = $service->get();
        Assert::notNull($address);
        Assert::same('John', $address->name);
        Assert::same('Smith', $address->surname);
        Assert::same('Glasgow', $address->city);
        Assert::same('G32 6AB', $address->postcode);
    }

    public function aSurnameAloneIsEnoughToCountAsSet(): void
    {
        $service = new DeliveryAddressService($this->fakeSession());
        $service->set('', 'Smith', 'Glasgow', 'G32 6AB');

        $address = $service->get();
        Assert::notNull($address);
        Assert::same('', $address->name);
        Assert::same('Smith', $address->surname);
    }

    public function blankNameAndSurnameTogetherClearTheAddress(): void
    {
        $service = new DeliveryAddressService($this->fakeSession());
        $service->set('John', 'Smith', 'Glasgow', 'G32 6AB');
        $service->set('   ', '   ', 'Edinburgh', 'EH1 1AA');

        Assert::null($service->get());
    }
}
