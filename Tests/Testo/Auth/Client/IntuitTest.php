<?php

declare(strict_types=1);

namespace Tests\Testo\Auth\Client;

use App\Auth\Client\Intuit;
use Mockery as m;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Factory\Factory;
use Yiisoft\Session\Session;
use Yiisoft\Yii\AuthClient\StateStorage\DummyStateStorage;

/**
 * Covers Intuit's own small overrides directly. Its OAuth2-inherited
 * behaviour (applyClientCredentialsToRequest()'s Basic-auth header,
 * $jsonTokenResponse's JSON parsing) is already exercised end-to-end via
 * QuickBooksGatewayTest's refresh-path tests, which is the more
 * meaningful coverage for that logic -- this file only covers what
 * those tests don't touch: the plain identification methods and the
 * default scope.
 */
#[Test]
final class IntuitTest
{
    private function makeIntuit(): Intuit
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        /** @var RequestFactoryInterface&m\MockInterface $requestFactory */
        $requestFactory = m::mock(RequestFactoryInterface::class);
        /** @var Factory&m\MockInterface $factory */
        $factory = m::mock(Factory::class);

        return new Intuit($httpClient, $requestFactory, new DummyStateStorage(), $factory, new Session());
    }

    public function getNameReturnsIntuit(): void
    {
        Assert::same('intuit', $this->makeIntuit()->getName());
    }

    public function getTitleReturnsQuickbooks(): void
    {
        Assert::same('QuickBooks', $this->makeIntuit()->getTitle());
    }

    public function getButtonClassReturnsABootstrapPrimaryButton(): void
    {
        Assert::same('btn btn-primary', $this->makeIntuit()->getButtonClass());
    }

    public function getScopeDefaultsToTheQuickbooksAccountingScope(): void
    {
        Assert::same('com.intuit.quickbooks.accounting', $this->makeIntuit()->getScope());
    }
}
