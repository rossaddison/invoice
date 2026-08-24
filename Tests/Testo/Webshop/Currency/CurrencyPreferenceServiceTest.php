<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Currency;

use App\Webshop\Currency\CurrencyPreferenceService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\SessionInterface;

/**
 * Covers CurrencyPreferenceService — the navbar toggle's own two-value
 * choice (see that class's own docblock for why there's never a third).
 */
#[Test]
final class CurrencyPreferenceServiceTest
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
            static function (string $key, string $value) use (&$store): void {
                $store[$key] = $value;
            },
        );
        return $session;
    }

    public function defaultsToNativeWhenNothingWasEverSet(): void
    {
        $service = new CurrencyPreferenceService($this->fakeSession());

        Assert::same(CurrencyPreferenceService::NATIVE, $service->get());
    }

    public function setThenGetRoundTripsDocument(): void
    {
        $service = new CurrencyPreferenceService($this->fakeSession());
        $service->set(CurrencyPreferenceService::DOCUMENT);

        Assert::same(CurrencyPreferenceService::DOCUMENT, $service->get());
    }

    public function anyUnrecognizedValueFallsBackToNative(): void
    {
        $service = new CurrencyPreferenceService($this->fakeSession());
        $service->set('anything-else');

        Assert::same(CurrencyPreferenceService::NATIVE, $service->get());
    }
}
