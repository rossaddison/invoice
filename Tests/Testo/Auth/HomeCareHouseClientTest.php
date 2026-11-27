<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\HomeCareHouseClient;
use App\Auth\HouseClientOutcome;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Invoice\Client\ClientDwellingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\UserClient\UserClientRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A HomeCare resident who signs up for a house the run-sheet import already made a client for joins that client
 * instead of getting a second one for the same house. It is only safe when the house has exactly one client, that
 * client has no user yet, and it does not carry a different email; anything else is left for staff.
 */
#[Test]
final class HomeCareHouseClientTest
{
    private const int HOUSE = 5;
    private const string ACCOUNT_EMAIL = 'resident@example.test';

    private int $saves = 0;

    private function client(int $id, string $email = '', string $name = ''): Client
    {
        $client = new Client();
        $client->setId($id);
        $client->setClientEmail($email);
        $client->setClientName($name);

        return $client;
    }

    /**
     * @param list<Client> $onTheHouse
     * @param list<int> $clientsWithAUser ids of the clients that already have a user
     */
    private function house(array $onTheHouse, array $clientsWithAUser = []): HomeCareHouseClient
    {
        $this->saves = 0;
        $byDwelling = TypedMock::of(ClientDwellingRepository::class);
        $byDwelling->shouldReceive('repoClientsByDwelling')->with(self::HOUSE)->andReturn($onTheHouse);

        $userClients = TypedMock::of(UserClientRepository::class);
        $userClients->shouldReceive('repoUserquery')->andReturnUsing(
            static fn (int $clientId): ?UserClient => in_array($clientId, $clientsWithAUser, true)
                ? new UserClient()
                : null,
        );

        $clients = TypedMock::of(ClientRepository::class);
        $clients->shouldReceive('save')->andReturnUsing(function (): void {
            ++$this->saves;
        });

        return new HomeCareHouseClient($byDwelling, $userClients, $clients);
    }

    public function aHouseNobodyHoldsGetsANewClient(): void
    {
        Assert::same($this->house([])->decide(self::HOUSE, self::ACCOUNT_EMAIL)->outcome, HouseClientOutcome::Create);
    }

    public function aHouseWhoseClientHasNoUserAndNoEmailIsJoined(): void
    {
        $existing = $this->client(10);

        $decision = $this->house([$existing])->decide(self::HOUSE, self::ACCOUNT_EMAIL);

        Assert::same($decision->outcome, HouseClientOutcome::Link);
        Assert::same($decision->client, $existing);
    }

    public function aClientWithTheSameEmailInADifferentCaseIsStillJoined(): void
    {
        $decision = $this->house([$this->client(10, ' Resident@Example.Test ')])->decide(self::HOUSE, self::ACCOUNT_EMAIL);

        Assert::same($decision->outcome, HouseClientOutcome::Link);
    }

    public function aHouseWhoseClientAlreadyHasAUserIsLeftForStaff(): void
    {
        $decision = $this->house([$this->client(10)], [10])->decide(self::HOUSE, self::ACCOUNT_EMAIL);

        Assert::true($decision->isTaken());
        Assert::null($decision->client);
    }

    public function aHouseWhoseClientHasADifferentEmailIsLeftForStaff(): void
    {
        $decision = $this->house([$this->client(10, 'someone.else@example.test')])->decide(self::HOUSE, self::ACCOUNT_EMAIL);

        Assert::true($decision->isTaken());
    }

    public function aHouseWithSeveralClientsIsLeftForStaff(): void
    {
        $decision = $this->house([$this->client(10), $this->client(11)])->decide(self::HOUSE, self::ACCOUNT_EMAIL);

        Assert::true($decision->isTaken());
    }

    public function joiningFillsInOnlyWhatTheClientIsMissingAndNeverReplacesStaffsData(): void
    {
        // the import gave the client a name and an address but no email or language
        $existing = $this->client(10, '', 'Importname');
        $existing->setClientAddress1('Virtual Street');

        $house = $this->house([$existing]);
        $joined = $house->adopt($existing, self::ACCOUNT_EMAIL, 'en', 'Signupname', 'Signupsurname', 'Another Street', '12');

        Assert::same($joined->getClientEmail(), self::ACCOUNT_EMAIL);
        Assert::same($joined->getClientLanguage(), 'en');
        // already there, so kept
        Assert::same($joined->getClientName(), 'Importname');
        Assert::same($joined->getClientAddress1(), 'Virtual Street');
        // missing, so filled from the sign-up
        Assert::same($joined->getClientSurname(), 'Signupsurname');
        Assert::same($joined->getClientBuildingNumber(), '12');
        Assert::same($this->saves, 1);
    }
}
