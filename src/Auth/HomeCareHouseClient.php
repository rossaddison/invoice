<?php

declare(strict_types=1);

namespace App\Auth;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientDwellingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\UserClient\UserClientRepository;

/**
 * The client of the house a HomeCare resident signs up for.
 *
 * A house (Dwelling) belongs to one client. The run-sheet import already makes a client for each house, with a name
 * and phone but no email and no user. When a resident then signs up for that house, a second client used to be made
 * for the same house. Instead the resident's account joins the client the house already has, so one household is one
 * client. That is only safe when the house has exactly one client, that client has no user yet (a client has one
 * user), and it does not already carry a different email (that is staff's data, not this resident's to replace).
 * Anything else is left for staff.
 */
final readonly class HomeCareHouseClient
{
    public function __construct(
        private ClientDwellingRepository $clientsByDwelling,
        private UserClientRepository $userClients,
        private ClientRepository $clients,
    ) {
    }

    public function decide(int $dwellingId, string $accountEmail): HomeCareHouseDecision
    {
        $clients = $this->clientsByDwelling->repoClientsByDwelling($dwellingId);
        if ([] === $clients) {
            return HomeCareHouseDecision::create();
        }
        if (count($clients) > 1) {
            return HomeCareHouseDecision::taken();
        }
        $client = $clients[0];
        if (null !== $this->userClients->repoUserquery($client->reqId())) {
            return HomeCareHouseDecision::taken();
        }
        $existingEmail = mb_strtolower(trim($client->getClientEmail()));
        if ('' !== $existingEmail && $existingEmail !== mb_strtolower(trim($accountEmail))) {
            return HomeCareHouseDecision::taken();
        }

        return HomeCareHouseDecision::link($client);
    }

    /**
     * Fills in what the existing client is missing from the sign-up, and nothing else: a value staff already entered
     * is never replaced. The account's email is the one the resident proved they own by confirming it.
     */
    public function adopt(
        Client $client,
        string $email,
        string $language,
        string $name,
        string $surname,
        string $street,
        string $buildingNumber,
    ): Client {
        $client->setClientActive(true);
        if ('' === trim($client->getClientEmail())) {
            $client->setClientEmail($email);
        }
        if ('' === trim((string) $client->getClientLanguage())) {
            $client->setClientLanguage($language);
        }
        if ('' === trim($client->getClientName())) {
            $client->setClientName($name);
        }
        if ('' === trim((string) $client->getClientSurname())) {
            $client->setClientSurname($surname);
        }
        if ('' === trim((string) $client->getClientAddress1())) {
            $client->setClientAddress1($street);
        }
        if ('' === trim((string) $client->getClientBuildingNumber())) {
            $client->setClientBuildingNumber($buildingNumber);
        }
        $this->clients->save($client);

        return $client;
    }
}
