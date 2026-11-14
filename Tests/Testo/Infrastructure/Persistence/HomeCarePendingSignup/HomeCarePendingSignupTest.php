<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\HomeCarePendingSignup;

use App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup;
use LogicException;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * The answers a HomeCare signup keeps between the signup form and the emailed confirmation link,
 * including the service the customer chose.
 */
#[Test]
final class HomeCarePendingSignupTest
{
    public function aNewPendingSignupIsNotPersistedAndHasNoServiceYet(): void
    {
        $pending = new HomeCarePendingSignup();

        Assert::false($pending->hasIdentity());
        Assert::same($pending->getService(), '');
    }

    public function theServiceChosenIsKept(): void
    {
        $pending = new HomeCarePendingSignup();
        $pending->setService('bin_cleaning');

        Assert::same($pending->getService(), 'bin_cleaning');
    }

    public function theServiceCanBeGivenWhenItIsBuilt(): void
    {
        $pending = new HomeCarePendingSignup(service: 'hedge_cutting');

        Assert::same($pending->getService(), 'hedge_cutting');
    }

    public function onceTheIdIsSetItIsPersistedAndRequiredIdWorks(): void
    {
        $pending = new HomeCarePendingSignup();
        $pending->setId(5);

        Assert::true($pending->hasIdentity());
        Assert::same($pending->reqId(), 5);
    }

    #[ExpectException(LogicException::class)]
    public function theIdOfAPendingSignupThatIsNotPersistedCannotBeRequired(): void
    {
        new HomeCarePendingSignup()->reqId();
    }
}
