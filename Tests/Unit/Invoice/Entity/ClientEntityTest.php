<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\Client\Client;
use Codeception\Test\Unit;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

class ClientEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $client = new Client();

        $this->assertFalse($client->isPersisted());
        $this->assertSame('', $client->getClientEmail());
        $this->assertSame('', $client->getClientMobile());
        $this->assertSame('', $client->getClientTitle());
        $this->assertSame('', $client->getClientName());
        $this->assertSame('', $client->getClientSurname());
        $this->assertSame('', $client->getClientGroup());
        $this->assertSame('', $client->getClientFrequency());
        $this->assertSame('', $client->getClientNumber());
        $this->assertSame('', $client->getClientAddress1());
        $this->assertSame('', $client->getClientAddress2());
        $this->assertSame('', $client->getClientBuildingNumber());
        $this->assertSame('', $client->getClientCity());
        $this->assertSame('', $client->getClientState());
        $this->assertSame('', $client->getClientZip());
        $this->assertSame('', $client->getClientCountry());
        $this->assertSame('', $client->getClientPhone());
        $this->assertSame('', $client->getClientFax());
        $this->assertSame('', $client->getClientWeb());
        $this->assertSame('', $client->getClientVatId());
        $this->assertSame('', $client->getClientTaxCode());
        $this->assertSame('', $client->getClientLanguage());
        $this->assertFalse($client->getClientActive());
        $this->assertNull($client->getClientBirthdate());
        $this->assertSame(0, $client->getClientAge());
        $this->assertSame(0, $client->getClientGender());
        $this->assertNull($client->getPostaladdressId());
    }

    public function testConstructorWithAllParameters(): void
    {
        $client = new Client(
            'john@example.com',
            '07700900000',
            'Mr',
            'John',
            'Doe',
            'VIP',
            'monthly',
            'CLI001',
            '123 Main St',
            'Apt 4',
            '42',
            'Glasgow',
            'Scotland',
            'G1 1AA',
            'GB',
            '01413000000',
            '01413000001',
            'https://example.com',
            'GB123456789',
            'TAX001',
            'en_GB',
            true,
            null,
            30,
            1,
            null,
        );

        $this->assertFalse($client->isPersisted());
        $this->assertSame('john@example.com', $client->getClientEmail());
        $this->assertSame('07700900000', $client->getClientMobile());
        $this->assertSame('Mr', $client->getClientTitle());
        $this->assertSame('John', $client->getClientName());
        $this->assertSame('Doe', $client->getClientSurname());
        $this->assertSame('VIP', $client->getClientGroup());
        $this->assertSame('monthly', $client->getClientFrequency());
        $this->assertSame('CLI001', $client->getClientNumber());
        $this->assertSame('123 Main St', $client->getClientAddress1());
        $this->assertSame('Apt 4', $client->getClientAddress2());
        $this->assertSame('42', $client->getClientBuildingNumber());
        $this->assertSame('Glasgow', $client->getClientCity());
        $this->assertSame('Scotland', $client->getClientState());
        $this->assertSame('G1 1AA', $client->getClientZip());
        $this->assertSame('GB', $client->getClientCountry());
        $this->assertSame('01413000000', $client->getClientPhone());
        $this->assertSame('01413000001', $client->getClientFax());
        $this->assertSame('https://example.com', $client->getClientWeb());
        $this->assertSame('GB123456789', $client->getClientVatId());
        $this->assertSame('TAX001', $client->getClientTaxCode());
        $this->assertSame('en_GB', $client->getClientLanguage());
        $this->assertTrue($client->getClientActive());
        $this->assertSame(30, $client->getClientAge());
        $this->assertSame(1, $client->getClientGender());
        $this->assertNull($client->getPostaladdressId());
    }

    public function testReqClientIdThrowsWhenNotPersisted(): void
    {
        $client = new Client();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client has no ID (not persisted yet)');
        $client->reqId();
        $this->assertFalse($client->isPersisted());
    }

    public function testIsPersistedReturnsTrueWhenIdSet(): void
    {
        $client = new Client();
        $client->setId(1);

        $this->assertTrue($client->isPersisted());
    }

    public function testReqIdReturnsIntWhenPersisted(): void
    {
        $client = new Client();
        $client->setId(42);

        $this->assertSame(42, $client->reqId());
        $this->assertIsInt($client->reqId());
    }

    public function testClientFullNameFromNameAndSurname(): void
    {
        $client = new Client(
            client_name: 'John',
            client_surname: 'Doe'
        );

        $this->assertSame('John Doe', $client->getClientFullName());
    }

    public function testClientFullNameWithNoSurname(): void
    {
        $client = new Client(client_name: 'John');

        $this->assertStringContainsString('John', $client->getClientFullName());
    }

    public function testSetClientFullName(): void
    {
        $client = new Client();

        $client->setClientFullName('Jane Smith');
        $this->assertSame('Jane Smith', $client->getClientFullName());
    }

    public function testClientEmailSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientEmail('test@example.com');
        $this->assertSame('test@example.com', $client->getClientEmail());
    }

    public function testClientMobileSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientMobile('07700900123');
        $this->assertSame('07700900123', $client->getClientMobile());
    }

    public function testClientTitleSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientTitle('Dr');
        $this->assertSame('Dr', $client->getClientTitle());

        $client->setClientTitle(null);
        $this->assertNull($client->getClientTitle());
    }

    public function testClientNameSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientName('Jane');
        $this->assertSame('Jane', $client->getClientName());
    }

    public function testClientSurnameSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientSurname('Smith');
        $this->assertSame('Smith', $client->getClientSurname());
    }

    public function testClientGroupSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientGroup('Premium');
        $this->assertSame('Premium', $client->getClientGroup());
    }

    public function testClientFrequencySetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientFrequency('weekly');
        $this->assertSame('weekly', $client->getClientFrequency());
    }

    public function testClientNumberSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientNumber('CLI999');
        $this->assertSame('CLI999', $client->getClientNumber());

        $client->setClientNumber(null);
        $this->assertNull($client->getClientNumber());
    }

    public function testClientAddress1SetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientAddress1('456 High Street');
        $this->assertSame('456 High Street', $client->getClientAddress1());
    }

    public function testClientAddress2SetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientAddress2('Floor 2');
        $this->assertSame('Floor 2', $client->getClientAddress2());
    }

    public function testClientBuildingNumberSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientBuildingNumber('10B');
        $this->assertSame('10B', $client->getClientBuildingNumber());
    }

    public function testClientCitySetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientCity('Edinburgh');
        $this->assertSame('Edinburgh', $client->getClientCity());
    }

    public function testClientStateSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientState('Scotland');
        $this->assertSame('Scotland', $client->getClientState());
    }

    public function testClientZipSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientZip('EH1 1AA');
        $this->assertSame('EH1 1AA', $client->getClientZip());
    }

    public function testClientCountrySetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientCountry('GB');
        $this->assertSame('GB', $client->getClientCountry());
    }

    public function testClientPhoneSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientPhone('01312000000');
        $this->assertSame('01312000000', $client->getClientPhone());
    }

    public function testClientFaxSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientFax('01312000001');
        $this->assertSame('01312000001', $client->getClientFax());
    }

    public function testClientWebSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientWeb('https://example.co.uk');
        $this->assertSame('https://example.co.uk', $client->getClientWeb());
    }

    public function testClientVatIdSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientVatId('GB987654321');
        $this->assertSame('GB987654321', $client->getClientVatId());
        $this->assertIsString($client->getClientVatId());
    }

    public function testClientTaxCodeSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientTaxCode('TAX999');
        $this->assertSame('TAX999', $client->getClientTaxCode());
    }

    public function testClientLanguageSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientLanguage('fr_FR');
        $this->assertSame('fr_FR', $client->getClientLanguage());
    }

    public function testClientActiveSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientActive(true);
        $this->assertTrue($client->getClientActive());

        $client->setClientActive(false);
        $this->assertFalse($client->getClientActive());
    }

    public function testClientActiveToggling(): void
    {
        $client = new Client();

        $this->assertFalse($client->getClientActive());
        $client->setClientActive(!$client->getClientActive());
        $this->assertTrue($client->getClientActive());
        $client->setClientActive(!$client->getClientActive());
        $this->assertFalse($client->getClientActive());
    }

    public function testClientBirthdateSetterAndGetter(): void
    {
        $client = new Client();
        $birthdate = new DateTimeImmutable('1990-01-15');

        $client->setClientBirthdate($birthdate);
        $this->assertSame($birthdate, $client->getClientBirthdate());
    }

    public function testClientBirthdateNullable(): void
    {
        $client = new Client();

        $client->setClientBirthdate(null);
        $this->assertNull($client->getClientBirthdate());
    }

    public function testClientAgeSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientAge(35);
        $this->assertSame(35, $client->getClientAge());
    }

    public function testClientGenderSetterAndGetter(): void
    {
        $client = new Client();

        $client->setClientGender(1);
        $this->assertSame(1, $client->getClientGender());

        $client->setClientGender(0);
        $this->assertSame(0, $client->getClientGender());
    }

    public function testPostaladdressIdSetterAndGetter(): void
    {
        $client = new Client();

        $client->setPostaladdressId(5);
        $this->assertSame(5, $client->getPostaladdressId());
    }

    public function testPostaladdressIdIsNullByDefault(): void
    {
        $client = new Client();

        $this->assertNull($client->getPostaladdressId());
    }

    public function testClientDateCreatedIsDateTimeImmutable(): void
    {
        $client = new Client();

        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateCreated());
    }

    public function testClientDateModifiedIsDateTimeImmutable(): void
    {
        $client = new Client();

        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateModified());
    }

    public function testSetClientDateCreated(): void
    {
        $client = new Client();

        $client->setClientDateCreated('2024-01-15 09:30:00');
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateCreated());
    }

    public function testSetClientDateModified(): void
    {
        $client = new Client();

        $client->setClientDateModified('2024-06-01 12:00:00');
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateModified());
    }

    public function testDeliveryLocationsIsArrayCollectionByDefault(): void
    {
        $client = new Client();

        $this->assertInstanceOf(ArrayCollection::class, $client->getDeliveryLocations());
        $this->assertCount(0, $client->getDeliveryLocations());
    }

    public function testInvsIsArrayCollectionByDefault(): void
    {
        $client = new Client();

        $this->assertInstanceOf(ArrayCollection::class, $client->getInvs());
        $this->assertCount(0, $client->getInvs());
    }

    public function testSetInvsResetsCollection(): void
    {
        $client = new Client();

        $client->setInvs();
        $this->assertInstanceOf(ArrayCollection::class, $client->getInvs());
        $this->assertCount(0, $client->getInvs());
    }

    public function testUnicodeCharactersInName(): void
    {
        $client = new Client();

        $client->setClientName('Ünäme');
        $client->setClientSurname('Ö\'Brien');
        $this->assertSame('Ünäme', $client->getClientName());
        $this->assertSame('Ö\'Brien', $client->getClientSurname());
    }

    public function testSpecialCharactersInEmail(): void
    {
        $client = new Client();

        $client->setClientEmail('user+tag@example.co.uk');
        $this->assertSame('user+tag@example.co.uk', $client->getClientEmail());
    }

    public function testCompleteClientSetup(): void
    {
        $client = new Client(
            client_name: 'John',
            client_surname: 'Doe',
            client_email: 'john.doe@example.com',
        );

        $client->setId(1);
        $client->setClientActive(true);
        $client->setClientCity('Glasgow');
        $client->setClientCountry('GB');
        $client->setClientAge(40);

        $this->assertTrue($client->isPersisted());
        $this->assertSame(1, $client->reqId());
        $this->assertSame('john.doe@example.com', $client->getClientEmail());
        $this->assertSame('John', $client->getClientName());
        $this->assertSame('Doe', $client->getClientSurname());
        $this->assertSame('John Doe', $client->getClientFullName());
        $this->assertTrue($client->getClientActive());
        $this->assertSame('Glasgow', $client->getClientCity());
        $this->assertSame('GB', $client->getClientCountry());
        $this->assertSame(40, $client->getClientAge());
    }

    public function testMultipleEmailUpdates(): void
    {
        $client = new Client();

        foreach (['a@a.com', 'b@b.com', 'c@c.com'] as $email) {
            $client->setClientEmail($email);
            $this->assertSame($email, $client->getClientEmail());
        }
    }

    public function testClientVatIdAlwaysReturnsString(): void
    {
        $client = new Client();

        $this->assertIsString($client->getClientVatId());

        $client->setClientVatId('GB123');
        $this->assertIsString($client->getClientVatId());
    }
}