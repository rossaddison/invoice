<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Client\ClientService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ClientService: the home-care QR token get-or-create flow, QR image
 * rendering, the saveClient() field-mapping/persist pipeline (including the
 * hasIdentity()-gated active/postal-address reset), and deleteClient().
 */
#[Test]
final class ClientServiceTest
{
    /** @return array<string, mixed> */
    private function minimalBody(): array
    {
        return [
            'client_name' => 'Jane',
            'client_surname' => 'Doe',
            'client_active' => '1',
        ];
    }

    public function getOrCreateQrTokenReturnsExistingTokenWithoutSavingOrGenerating(): void
    {
        $client = new Client();
        $client->setClientQrToken('existing-token');

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        $repo->shouldNotReceive('save');

        $service = new ClientService($repo);

        Assert::same('existing-token', $service->getOrCreateQrToken($client));
    }

    public function getOrCreateQrTokenGeneratesPersistsAndReturnsNewTokenWhenNull(): void
    {
        $client = new Client();

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);
        $token = $service->getOrCreateQrToken($client);

        Assert::same(32, \strlen($token));
        Assert::same($token, $client->getClientQrToken());
    }

    public function getOrCreateQrTokenGeneratesPersistsAndReturnsNewTokenWhenEmptyString(): void
    {
        $client = new Client();
        $client->setClientQrToken('');

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);
        $token = $service->getOrCreateQrToken($client);

        Assert::same(32, \strlen($token));
        Assert::same($token, $client->getClientQrToken());
    }

    public function renderQrDataUriRendersQrCodeAsDataUri(): void
    {
        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);

        $service = new ClientService($repo);
        $dataUri = $service->renderQrDataUri('https://example.test/scan/abc');

        Assert::true(\str_contains($dataUri, 'data:image'));
    }

    public function saveClientAppliesFieldsSavesAndReturnsNullWhenModelHasNoIdentity(): void
    {
        $client = new Client();
        $body = $this->minimalBody() + [
            'client_title' => 'Mrs',
            'client_frequency' => 'weekly',
            'client_group' => 'A',
            'client_number' => 'C-1',
            'client_address_1' => '1 Elm Street',
            'client_address_2' => 'Flat 2',
            'client_building_number' => '1',
            'client_city' => 'Springfield',
            'client_state' => 'IL',
            'client_zip' => '11111',
            'client_country' => 'US',
            'client_phone' => '555-0100',
            'client_fax' => '555-0101',
            'client_mobile' => '555-0102',
            'client_email' => 'jane@example.test',
            'client_web' => 'https://jane.example.test',
            'client_vat_id' => 'VAT1',
            'client_tax_code' => 'TAX1',
            'client_language' => 'en',
            'client_age' => '30',
            'client_gender' => '1',
            'postaladdress_id' => '5',
            'client_telegram_chat_id' => '999',
        ];

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);

        Assert::null($service->saveClient($client, $body));
        Assert::same('Jane', $client->getClientName());
        Assert::same('Doe', $client->getClientSurname());
        Assert::same('Jane Doe', $client->getClientFullName());
        Assert::same('Mrs', $client->getClientTitle());
        Assert::same('1 Elm Street', $client->getClientAddress1());
        Assert::same('jane@example.test', $client->getClientEmail());
        Assert::same('999', $client->getClientTelegramChatId());
        // hasIdentity() is false, so the forced active/postal-address reset
        // does not run and the body-supplied value is used as-is.
        Assert::true($client->getClientActive());
        Assert::same(5, $client->getPostaladdressId());
    }

    public function saveClientLeavesOptionalFieldsAtDefaultsWhenBodyOmitsThem(): void
    {
        $client = new Client();
        $body = $this->minimalBody();

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);
        $service->saveClient($client, $body);

        Assert::same('', $client->getClientTitle());
        Assert::same('', $client->getClientAddress1());
        Assert::null($client->getClientBirthdate());
        Assert::null($client->getClientTelegramChatId());
    }

    public function saveClientForcesActiveAndResetsPostalAddressAndReturnsIdWhenModelHasIdentity(): void
    {
        $client = new Client();
        $client->setId(5);
        $body = $this->minimalBody() + [
            'client_active' => '0',
            'postaladdress_id' => '99',
        ];

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);

        Assert::same(5, $service->saveClient($client, $body));
        Assert::true($client->getClientActive());
        Assert::same(0, $client->getPostaladdressId());
    }

    public function saveClientAppliesBirthdateWhenProvidedInValidFormat(): void
    {
        $client = new Client();
        $body = $this->minimalBody() + ['client_birthdate' => '1990-05-15'];

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($client);

        $service = new ClientService($repo);
        $service->saveClient($client, $body);

        $birthdate = $client->getClientBirthdate();
        Assert::instanceOf($birthdate, \DateTimeImmutable::class);
        Assert::same('1990-05-15', $birthdate->format('Y-m-d'));
    }

    public function deleteClientCallsRepositoryDeleteWithGivenClient(): void
    {
        $client = m::mock(Client::class);

        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('delete');
        $e->once()->with($client);

        $service = new ClientService($repo);
        $service->deleteClient($client);
    }

    public function deleteClientAcceptsNull(): void
    {
        /** @var ClientRepository&m\MockInterface $repo */
        $repo = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('delete');
        $e->once()->with(null);

        $service = new ClientService($repo);
        $service->deleteClient(null);
    }
}
