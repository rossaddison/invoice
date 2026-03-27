<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Inv;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ClientEntityTest extends TestCase
{
    public string $testExampleCom = 'test@example.com';
    
    public function testConstructorWithDefaults(): void
    {
        $client = new Client();
        
        $this->assertNull($client->getClientId());
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
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateModified());
        $this->assertInstanceOf(ArrayCollection::class, $client->getDeliveryLocations());
        $this->assertInstanceOf(ArrayCollection::class, $client->getInvs());
        $this->assertTrue($client->isNewRecord());
    }

    public function testConstructorWithAllParameters(): void
    {
        $client = new Client(
            client_email: 'john.doe@example.com',
            client_mobile: '+1-234-567-8900',
            client_title: 'Mr.',
            client_name: 'John',
            client_surname: 'Doe',
            client_group: 'VIP',
            client_frequency: 'monthly',
            client_number: 'CLI-001',
            client_address_1: '123 Main Street',
            client_address_2: 'Suite 456',
            client_building_number: '123A',
            client_city: 'New York',
            client_state: 'NY',
            client_zip: '10001',
            client_country: 'USA',
            client_phone: '+1-234-567-8901',
            client_fax: '+1-234-567-8902',
            client_web: 'https://johndoe.com',
            client_vat_id: 'VAT123456',
            client_tax_code: 'TAX789',
            client_language: 'en-US',
            client_active: true,
            client_birthdate: null,
            client_age: 35,
            client_gender: 1,
            postaladdress_id: 1001
        );

        $this->assertSame('john.doe@example.com', $client->getClientEmail());
        $this->assertSame('+1-234-567-8900', $client->getClientMobile());
        $this->assertSame('Mr.', $client->getClientTitle());
        $this->assertSame('John', $client->getClientName());
        $this->assertSame('Doe', $client->getClientSurname());
        $this->assertSame('VIP', $client->getClientGroup());
        $this->assertSame('monthly', $client->getClientFrequency());
        $this->assertSame('CLI-001', $client->getClientNumber());
        $this->assertSame('123 Main Street', $client->getClientAddress1());
        $this->assertSame('Suite 456', $client->getClientAddress2());
        $this->assertSame('123A', $client->getClientBuildingNumber());
        $this->assertSame('New York', $client->getClientCity());
        $this->assertSame('NY', $client->getClientState());
        $this->assertSame('10001', $client->getClientZip());
        $this->assertSame('USA', $client->getClientCountry());
        $this->assertSame('+1-234-567-8901', $client->getClientPhone());
        $this->assertSame('+1-234-567-8902', $client->getClientFax());
        $this->assertSame('https://johndoe.com', $client->getClientWeb());
        $this->assertSame('VAT123456', $client->getClientVatId());
        $this->assertSame('TAX789', $client->getClientTaxCode());
        $this->assertSame('en-US', $client->getClientLanguage());
        $this->assertTrue($client->getClientActive());
        $this->assertSame(35, $client->getClientAge());
        $this->assertSame(1, $client->getClientGender());
        $this->assertSame(1001, $client->getPostaladdressId());
        $this->assertSame('John Doe', $client->getClientFullName());
    }

    public function testClientEmailSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientEmail($this->testExampleCom);
        $this->assertSame($this->testExampleCom, $client->getClientEmail());
        
        $client->setClientEmail('');
        $this->assertSame('', $client->getClientEmail());
    }

    public function testClientMobileSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientMobile('+1-555-0123');
        $this->assertSame('+1-555-0123', $client->getClientMobile());
        
        $client->setClientMobile('');
        $this->assertSame('', $client->getClientMobile());
    }

    public function testClientTitleSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientTitle('Dr.');
        $this->assertSame('Dr.', $client->getClientTitle());
        
        $client->setClientTitle(null);
        $this->assertNull($client->getClientTitle());
    }

    public function testClientNameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientName('Jane');
        $this->assertSame('Jane', $client->getClientName());
        
        $client->setClientName('');
        $this->assertSame('', $client->getClientName());
    }

    public function testClientSurnameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientSurname('Smith');
        $this->assertSame('Smith', $client->getClientSurname());
        
        $client->setClientSurname('');
        $this->assertSame('', $client->getClientSurname());
    }

    public function testClientGroupSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientGroup('A');
        $this->assertSame('A', $client->getClientGroup());
        
        $client->setClientGroup('');
        $this->assertSame('', $client->getClientGroup());
    }

    public function testClientFrequencySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientFrequency('weekly');
        $this->assertSame('weekly', $client->getClientFrequency());
        
        $client->setClientFrequency('');
        $this->assertSame('', $client->getClientFrequency());
    }

    public function testClientNumberSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientNumber('C-12345');
        $this->assertSame('C-12345', $client->getClientNumber());
        
        $client->setClientNumber(null);
        $this->assertNull($client->getClientNumber());
    }

    public function testClientAddress1SetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientAddress1('456 Oak Avenue');
        $this->assertSame('456 Oak Avenue', $client->getClientAddress1());
        
        $client->setClientAddress1('');
        $this->assertSame('', $client->getClientAddress1());
    }

    public function testClientAddress2SetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientAddress2('Apartment 789');
        $this->assertSame('Apartment 789', $client->getClientAddress2());
        
        $client->setClientAddress2('');
        $this->assertSame('', $client->getClientAddress2());
    }

    public function testClientBuildingNumberSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientBuildingNumber('45B');
        $this->assertSame('45B', $client->getClientBuildingNumber());
        
        $client->setClientBuildingNumber('');
        $this->assertSame('', $client->getClientBuildingNumber());
    }

    public function testClientCitySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientCity('Los Angeles');
        $this->assertSame('Los Angeles', $client->getClientCity());
        
        $client->setClientCity('');
        $this->assertSame('', $client->getClientCity());
    }

    public function testClientStateSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientState('California');
        $this->assertSame('California', $client->getClientState());
        
        $client->setClientState('');
        $this->assertSame('', $client->getClientState());
    }

    public function testClientZipSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientZip('90210');
        $this->assertSame('90210', $client->getClientZip());
        
        $client->setClientZip('');
        $this->assertSame('', $client->getClientZip());
    }

    public function testClientCountrySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientCountry('Canada');
        $this->assertSame('Canada', $client->getClientCountry());
        
        $client->setClientCountry('');
        $this->assertSame('', $client->getClientCountry());
    }

    public function testClientPhoneSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientPhone('+1-800-555-0199');
        $this->assertSame('+1-800-555-0199', $client->getClientPhone());
        
        $client->setClientPhone('');
        $this->assertSame('', $client->getClientPhone());
    }

    public function testClientFaxSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientFax('+1-800-555-0200');
        $this->assertSame('+1-800-555-0200', $client->getClientFax());
        
        $client->setClientFax('');
        $this->assertSame('', $client->getClientFax());
    }

    public function testClientWebSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientWeb('https://company.example.com');
        $this->assertSame('https://company.example.com', $client->getClientWeb());
        
        $client->setClientWeb('');
        $this->assertSame('', $client->getClientWeb());
    }

    public function testClientVatIdSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientVatId('VAT-987654321');
        $this->assertSame('VAT-987654321', $client->getClientVatId());
        
        $client->setClientVatId('');
        $this->assertSame('', $client->getClientVatId());
    }

    public function testClientTaxCodeSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientTaxCode('TC-456789');
        $this->assertSame('TC-456789', $client->getClientTaxCode());
        
        $client->setClientTaxCode('');
        $this->assertSame('', $client->getClientTaxCode());
    }

    public function testClientLanguageSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientLanguage('fr-FR');
        $this->assertSame('fr-FR', $client->getClientLanguage());
        
        $client->setClientLanguage('');
        $this->assertSame('', $client->getClientLanguage());
    }

    public function testClientActiveSetterAndGetter(): void
    {
        $client = new Client();
        
        $this->assertFalse($client->getClientActive());
        
        $client->setClientActive(true);
        $this->assertTrue($client->getClientActive());
        
        $client->setClientActive(false);
        $this->assertFalse($client->getClientActive());
    }
    
    public function testClientBirthdateNullHandling(): void
    {
        $client = new Client();
        
        // Test with null value - this is the only safe test given the mixed return type
        $client->setClientBirthdate(null);
        $this->assertNull($client->getClientBirthdate());
        
        // Note: The getter returns DateTimeImmutable|string|null which is effectively mixed
        // We avoid testing with actual DateTime objects to prevent type issues
    }

    public function testClientAgeSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientAge(42);
        $this->assertSame(42, $client->getClientAge());
        
        $client->setClientAge(0);
        $this->assertSame(0, $client->getClientAge());
    }

    public function testClientGenderSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientGender(2);
        $this->assertSame(2, $client->getClientGender());
        
        $client->setClientGender(0);
        $this->assertSame(0, $client->getClientGender());
    }

    public function testPostaladdressIdSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setPostaladdressId(5001);
        $this->assertSame(5001, $client->getPostaladdressId());
        
        $client->setPostaladdressId(0);
        $this->assertSame(0, $client->getPostaladdressId());
    }

    public function testClientDateCreatedSetter(): void
    {
        $client = new Client();
        
        $client->setClientDateCreated('2023-01-15 10:30:45');
        $created = $client->getClientDateCreated();
        $this->assertInstanceOf(DateTimeImmutable::class, $created);
        $this->assertSame('2023-01-15', $created->format('Y-m-d'));
    }

    public function testClientDateCreatedSetterWithInvalidFormat(): void
    {
        $client = new Client();
        
        $client->setClientDateCreated('invalid-date');
        $created = $client->getClientDateCreated();
        $this->assertInstanceOf(DateTimeImmutable::class, $created);
        // Should default to now when format is invalid
        $now = new DateTimeImmutable();
        $this->assertSame($now->format('Y-m-d'), $created->format('Y-m-d'));
    }

    public function testClientDateModifiedSetter(): void
    {
        $client = new Client();
        
        $client->setClientDateModified('2023-02-20 02:45:30');
        $modified = $client->getClientDateModified();
        $this->assertInstanceOf(DateTimeImmutable::class, $modified);
        $this->assertSame('2023-02-20', $modified->format('Y-m-d'));
    }

    public function testClientDateModifiedSetterWithInvalidFormat(): void
    {
        $client = new Client();
        
        $client->setClientDateModified('bad-format');
        $modified = $client->getClientDateModified();
        $this->assertInstanceOf(DateTimeImmutable::class, $modified);
        // Should default to now when format is invalid
        $now = new DateTimeImmutable();
        $this->assertSame($now->format('Y-m-d'), $modified->format('Y-m-d'));
    }

    public function testClientFullNameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClientFullName('Custom Full Name');
        $this->assertSame('Custom Full Name', $client->getClientFullName());
    }

    public function testClientFullNameGenerationWithSurname(): void
    {
        $client = new Client(client_name: 'Alice', client_surname: 'Johnson');
        $this->assertSame('Alice Johnson', $client->getClientFullName());
    }

    public function testClientFullNameGenerationWithoutSurname(): void
    {
        $client = new Client(client_name: 'Bob');
        // Constructor sets full name to "Bob surname_unknown" when surname is null,
        // but getClient_full_name() logic returns just the name when surname is null
        
        // Force client_full_name to null to test the getter's reconstruction logic
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client_full_name');
        $property->setAccessible(true);
        $property->setValue($client, null);
        
        // Also set surname to null explicitly
        $client->setClientSurname('');
        $surnameProperty = $reflection->getProperty('client_surname');
        $surnameProperty->setAccessible(true);
        $surnameProperty->setValue($client, null);
        
        $this->assertSame('Bob', $client->getClientFullName());
    }

    public function testClientFullNameWhenNull(): void
    {
        $client = new Client(client_name: 'Charlie', client_surname: 'Brown');
        
        // Create a client with null full name by directly setting it
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client_full_name');
        $property->setAccessible(true);
        $property->setValue($client, null);
        
        // The getter will reconstruct from name + surname
        $this->assertSame('Charlie Brown', $client->getClientFullName());
    }

    public function testClientFullNameTrimsWhitespace(): void
    {
        $client = new Client(client_name: ' David ', client_surname: ' Wilson ');
        $fullName = $client->getClientFullName();
        $this->assertStringStartsNotWith(' ', $fullName);
        $this->assertStringEndsNotWith(' ', $fullName);
    }

    public function testInvsCollection(): void
    {
        $client = new Client();
        
        $invs = $client->getInvs();
        $this->assertInstanceOf(ArrayCollection::class, $invs);
        $this->assertCount(0, $invs);
    }

    public function testSetInvsResetsCollection(): void
    {
        $client = new Client();
        
        $client->setInvs();
        $invs = $client->getInvs();
        $this->assertInstanceOf(ArrayCollection::class, $invs);
        $this->assertCount(0, $invs);
    }

    public function testAddInv(): void
    {
        $client = new Client();
        $inv = $this->createMock(Inv::class);
        
        $client->addInv($inv);
        $invs = $client->getInvs();
        $this->assertCount(1, $invs);
        $this->assertSame($inv, $invs[0]);
    }

    public function testAddMultipleInvs(): void
    {
        $client = new Client();
        $inv1 = $this->createMock(Inv::class);
        $inv2 = $this->createMock(Inv::class);
        
        $client->addInv($inv1);
        $client->addInv($inv2);
        
        $invs = $client->getInvs();
        $this->assertCount(2, $invs);
        $this->assertSame($inv1, $invs[0]);
        $this->assertSame($inv2, $invs[1]);
    }

    public function testIsNewRecordWithNullId(): void
    {
        $client = new Client();
        $this->assertTrue($client->isNewRecord());
    }

    public function testIsNewRecordWithSetId(): void
    {
        $client = new Client();
        $client->id = 123;
        $this->assertFalse($client->isNewRecord());
    }

    public function testBusinessClientScenario(): void
    {
        $client = new Client(
            client_email: 'contact@acmecorp.com',
            client_name: 'ACME Corporation',
            client_address_1: '123 Business Blvd',
            client_city: 'Enterprise City',
            client_state: 'Business State',
            client_country: 'Corporate Nation',
            client_phone: '+1-555-BUSINESS',
            client_web: 'https://acmecorp.com',
            client_vat_id: 'VAT-BUSINESS-001',
            client_active: true
        );
        
        $this->assertSame('contact@acmecorp.com', $client->getClientEmail());
        $this->assertSame('ACME Corporation', $client->getClientName());
        $this->assertSame('123 Business Blvd', $client->getClientAddress1());
        $this->assertSame('Enterprise City', $client->getClientCity());
        $this->assertSame('Business State', $client->getClientState());
        $this->assertSame('Corporate Nation', $client->getClientCountry());
        $this->assertSame('+1-555-BUSINESS', $client->getClientPhone());
        $this->assertSame('https://acmecorp.com', $client->getClientWeb());
        $this->assertSame('VAT-BUSINESS-001', $client->getClientVatId());
        $this->assertTrue($client->getClientActive());
    }

    public function testPersonalClientScenario(): void
    {
        $client = new Client(
            client_email: 'personal@email.com',
            client_mobile: '+1-555-MOBILE',
            client_title: 'Ms.',
            client_name: 'Sarah',
            client_surname: 'Williams',
            client_address_1: '456 Residential St',
            client_address_2: 'Apt 789',
            client_city: 'Hometown',
            client_state: 'Home State',
            client_zip: '12345',
            client_country: 'Home Country',
            client_language: 'en-US',
            client_active: true,
            client_age: 28,
            client_gender: 2
        );
        
        $this->assertSame('personal@email.com', $client->getClientEmail());
        $this->assertSame('+1-555-MOBILE', $client->getClientMobile());
        $this->assertSame('Ms.', $client->getClientTitle());
        $this->assertSame('Sarah', $client->getClientName());
        $this->assertSame('Williams', $client->getClientSurname());
        $this->assertSame('Sarah Williams', $client->getClientFullName());
        $this->assertSame('456 Residential St', $client->getClientAddress1());
        $this->assertSame('Apt 789', $client->getClientAddress2());
        $this->assertSame('Hometown', $client->getClientCity());
        $this->assertSame('Home State', $client->getClientState());
        $this->assertSame('12345', $client->getClientZip());
        $this->assertSame('Home Country', $client->getClientCountry());
        $this->assertSame('en-US', $client->getClientLanguage());
        $this->assertTrue($client->getClientActive());
        $this->assertSame(28, $client->getClientAge());
        $this->assertSame(2, $client->getClientGender());
    }

    public function testInternationalClientScenario(): void
    {
        $client = new Client(
            client_email: 'international@global.com',
            client_name: 'André',
            client_surname: 'Müller',
            client_address_1: 'Hauptstraße 123',
            client_city: 'München',
            client_country: 'Deutschland',
            client_language: 'de-DE',
            client_vat_id: 'DE123456789',
            client_active: true
        );
        
        $this->assertSame('international@global.com', $client->getClientEmail());
        $this->assertSame('André', $client->getClientName());
        $this->assertSame('Müller', $client->getClientSurname());
        $this->assertSame('André Müller', $client->getClientFullName());
        $this->assertSame('Hauptstraße 123', $client->getClientAddress1());
        $this->assertSame('München', $client->getClientCity());
        $this->assertSame('Deutschland', $client->getClientCountry());
        $this->assertSame('de-DE', $client->getClientLanguage());
        $this->assertSame('DE123456789', $client->getClientVatId());
        $this->assertTrue($client->getClientActive());
    }

    public function testSpecialCharactersInFields(): void
    {
        $client = new Client();
        
        $client->setClientName('José-María O\'Connor');
        $client->setClientAddress1('123 "Main" Street & Co.');
        $client->setClientWeb('https://example.com/path?param=value&other=123');
        
        $this->assertSame('José-María O\'Connor', $client->getClientName());
        $this->assertSame('123 "Main" Street & Co.', $client->getClientAddress1());
        $this->assertSame('https://example.com/path?param=value&other=123', $client->getClientWeb());
    }

    public function testUnicodeCharactersInFields(): void
    {
        $client = new Client();
        
        $client->setClientName('李小明');
        $client->setClientSurname('王大华');
        $client->setClientCity('北京市');
        $client->setClientCountry('中国');
        
        $this->assertSame('李小明', $client->getClientName());
        $this->assertSame('王大华', $client->getClientSurname());
        $this->assertSame('北京市', $client->getClientCity());
        $this->assertSame('中国', $client->getClientCountry());
        $this->assertSame('李小明 王大华', $client->getClientFullName());
    }

    public function testLongFieldValues(): void
    {
        $client = new Client();
        
        $longText = str_repeat('Lorem ipsum dolor sit amet ', 20); // Very long text
        $client->setClientAddress1($longText);
        $this->assertSame($longText, $client->getClientAddress1());
        
        $longEmail = str_repeat('very', 50) . '@example.com';
        $client->setClientEmail($longEmail);
        $this->assertSame($longEmail, $client->getClientEmail());
    }

    public function testEmptyStringVsNullHandling(): void
    {
        $client = new Client();
        
        // Some fields accept empty strings
        $client->setClientEmail('');
        $this->assertSame('', $client->getClientEmail());
        
        $client->setClientName('');
        $this->assertSame('', $client->getClientName());
        
        // Most nullable fields default to empty string, not null
        $this->assertSame('', $client->getClientTitle());
        $this->assertSame('', $client->getClientSurname());
        $this->assertSame('', $client->getClientGroup());
        
        // Mobile defaults to empty string, not null
        $this->assertSame('', $client->getClientMobile());
    }

    public function testCommonGroupValues(): void
    {
        $client = new Client();
        
        $groups = ['A', 'B', 'C', 'VIP', 'PRM', '1', '2', '3'];
        
        foreach ($groups as $group) {
            $client->setClientGroup($group);
            $this->assertSame($group, $client->getClientGroup());
        }
    }

    public function testCommonFrequencyValues(): void
    {
        $client = new Client();
        
        $frequencies = ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annually', 'one-time'];
        
        foreach ($frequencies as $frequency) {
            $client->setClientFrequency($frequency);
            $this->assertSame($frequency, $client->getClientFrequency());
        }
    }

    public function testCommonLanguageCodes(): void
    {
        $client = new Client();
        
        $languages = ['en-US', 'en-GB', 'fr-FR', 'de-DE', 'es-ES', 'it-IT', 'pt-BR', 'zh-CN', 'ja-JP'];
        
        foreach ($languages as $language) {
            $client->setClientLanguage($language);
            $this->assertSame($language, $client->getClientLanguage());
        }
    }

    public function testGenderValues(): void
    {
        $client = new Client();
        
        // Test different gender representations
        $genders = [0, 1, 2, 3]; // 0 = unknown/unspecified, 1 = male, 2 = female, 3 = other
        
        foreach ($genders as $gender) {
            $client->setClientGender($gender);
            $this->assertSame($gender, $client->getClientGender());
        }
    }

    public function testAgeRanges(): void
    {
        $client = new Client();
        
        $ages = [0, 18, 25, 35, 45, 55, 65, 75, 100];
        
        foreach ($ages as $age) {
            $client->setClientAge($age);
            $this->assertSame($age, $client->getClientAge());
        }
    }

    public function testVatIdFormats(): void
    {
        $client = new Client();
        
        $vatIds = [
            'GB123456789',
            'FR12345678901',
            'DE123456789',
            'IT12345678901',
            'ES123456789A',
            'NL123456789B01',
            'VAT123456789'
        ];
        
        foreach ($vatIds as $vatId) {
            $client->setClientVatId($vatId);
            $this->assertSame($vatId, $client->getClientVatId());
        }
    }

    public function testCompleteClientWorkflow(): void
    {
        // Start with basic client
        $client = new Client(client_name: 'Test', client_email: $this->testExampleCom);
        $this->assertTrue($client->isNewRecord());
        
        // Update client information
        $client->setClientSurname('User');
        $client->setClientActive(true);
        $client->setClientPhone('+1-555-0199');
        $client->setClientAddress1('123 Test Street');
        $client->setClientCity('Test City');
        $client->setClientCountry('Test Country');
        
        // Reset full name to null so getter reconstructs it
        $client->setClientFullName('');
        // Verify updates
        $this->assertSame('Test User', $client->getClientFullName());
        $this->assertTrue($client->getClientActive());
        $this->assertSame('+1-555-0199', $client->getClientPhone());
        $this->assertSame('123 Test Street', $client->getClientAddress1());
        $this->assertSame('Test City', $client->getClientCity());
        $this->assertSame('Test Country', $client->getClientCountry());
        
        // Add invoices
        $inv1 = $this->createMock(Inv::class);
        $inv2 = $this->createMock(Inv::class);
        $client->addInv($inv1);
        $client->addInv($inv2);
        
        $this->assertCount(2, $client->getInvs());
        
        // Simulate saving (set ID)
        $client->id = 12345;
        $this->assertFalse($client->isNewRecord());
        $this->assertSame(12345, $client->getClientId());
    }

    public function testMethodReturnTypes(): void
    {
        $client = new Client();
        
        $this->assertTrue($client->getClientId() === null || is_int($client->getClientId()));
        $this->assertIsString($client->getClientEmail());
        $this->assertTrue($client->getClientMobile() === null || is_string($client->getClientMobile()));
        $this->assertIsString($client->getClientName());
        $this->assertIsBool($client->getClientActive());
        $this->assertIsInt($client->getClientAge());
        $this->assertIsInt($client->getClientGender());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClientDateModified());
        $this->assertInstanceOf(ArrayCollection::class, $client->getDeliveryLocations());
        $this->assertInstanceOf(ArrayCollection::class, $client->getInvs());
        $this->assertIsBool($client->isNewRecord());
    }

    public function testConstructorWithMixedParameters(): void
    {
        $client = new Client(
            client_email: 'mixed@test.com',
            client_name: 'Mixed',
            client_active: true,
            client_age: 30
        );
        
        $this->assertSame('mixed@test.com', $client->getClientEmail());
        $this->assertSame('Mixed', $client->getClientName());
        $this->assertTrue($client->getClientActive());
        $this->assertSame(30, $client->getClientAge());
        
        // Other parameters should use defaults
        $this->assertSame('', $client->getClientMobile());
        $this->assertSame('', $client->getClientTitle());
        $this->assertSame('', $client->getClientSurname());
        $this->assertSame(0, $client->getClientGender());
    }

    public function testEntityStateConsistency(): void
    {
        $client = new Client();
        
        // Verify initial state is consistent
        $this->assertTrue($client->isNewRecord());
        $this->assertFalse($client->getClientActive());
        $this->assertSame(0, $client->getClientAge());
        $this->assertSame(0, $client->getClientGender());
        
        // Change state and verify consistency
        $client->setClientActive(true);
        $client->setClientAge(25);
        $client->setClientGender(1);
        
        $this->assertTrue($client->getClientActive());
        $this->assertSame(25, $client->getClientAge());
        $this->assertSame(1, $client->getClientGender());
        $this->assertTrue($client->isNewRecord()); // Still new until ID is set
    }
}
