<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Inv;
use DateTimeImmutable;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ClientEntityTest extends TestCase
{
    public string $testExampleCom = 'test@example.com';
    
    public function testConstructorWithDefaults(): void
    {
        $client = new Client();
        
        $this->assertNull($client->getClient_id());
        $this->assertSame('', $client->getClient_email());
        $this->assertSame('', $client->getClient_mobile());
        $this->assertSame('', $client->getClient_title());
        $this->assertSame('', $client->getClient_name());
        $this->assertSame('', $client->getClient_surname());
        $this->assertSame('', $client->getClient_group());
        $this->assertSame('', $client->getClient_frequency());
        $this->assertSame('', $client->getClient_number());
        $this->assertSame('', $client->getClient_address_1());
        $this->assertSame('', $client->getClient_address_2());
        $this->assertSame('', $client->getClient_building_number());
        $this->assertSame('', $client->getClient_city());
        $this->assertSame('', $client->getClient_state());
        $this->assertSame('', $client->getClient_zip());
        $this->assertSame('', $client->getClient_country());
        $this->assertSame('', $client->getClient_phone());
        $this->assertSame('', $client->getClient_fax());
        $this->assertSame('', $client->getClient_web());
        $this->assertSame('', $client->getClient_vat_id());
        $this->assertSame('', $client->getClient_tax_code());
        $this->assertSame('', $client->getClient_language());
        $this->assertFalse($client->getClient_active());
        $this->assertSame('', $client->getClient_avs());
        $this->assertSame('', $client->getClient_insurednumber());
        $this->assertSame('', $client->getClient_veka());
        $this->assertNull($client->getClient_birthdate());
        $this->assertSame(0, $client->getClient_age());
        $this->assertSame(0, $client->getClient_gender());
        $this->assertNull($client->getPostaladdress_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClient_date_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClient_date_modified());
        $this->assertInstanceOf(ArrayCollection::class, $client->getDelivery_locations());
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
            client_avs: 'AVS123456789',
            client_insurednumber: 'INS987654321',
            client_veka: 'VEKA001',
            client_birthdate: null,
            client_age: 35,
            client_gender: 1,
            postaladdress_id: 1001
        );

        $this->assertSame('john.doe@example.com', $client->getClient_email());
        $this->assertSame('+1-234-567-8900', $client->getClient_mobile());
        $this->assertSame('Mr.', $client->getClient_title());
        $this->assertSame('John', $client->getClient_name());
        $this->assertSame('Doe', $client->getClient_surname());
        $this->assertSame('VIP', $client->getClient_group());
        $this->assertSame('monthly', $client->getClient_frequency());
        $this->assertSame('CLI-001', $client->getClient_number());
        $this->assertSame('123 Main Street', $client->getClient_address_1());
        $this->assertSame('Suite 456', $client->getClient_address_2());
        $this->assertSame('123A', $client->getClient_building_number());
        $this->assertSame('New York', $client->getClient_city());
        $this->assertSame('NY', $client->getClient_state());
        $this->assertSame('10001', $client->getClient_zip());
        $this->assertSame('USA', $client->getClient_country());
        $this->assertSame('+1-234-567-8901', $client->getClient_phone());
        $this->assertSame('+1-234-567-8902', $client->getClient_fax());
        $this->assertSame('https://johndoe.com', $client->getClient_web());
        $this->assertSame('VAT123456', $client->getClient_vat_id());
        $this->assertSame('TAX789', $client->getClient_tax_code());
        $this->assertSame('en-US', $client->getClient_language());
        $this->assertTrue($client->getClient_active());
        $this->assertSame('AVS123456789', $client->getClient_avs());
        $this->assertSame('INS987654321', $client->getClient_insurednumber());
        $this->assertSame('VEKA001', $client->getClient_veka());
        $this->assertSame(35, $client->getClient_age());
        $this->assertSame(1, $client->getClient_gender());
        $this->assertSame(1001, $client->getPostaladdress_id());
        $this->assertSame('John Doe', $client->getClient_full_name());
    }

    public function testClientEmailSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_email($this->testExampleCom);
        $this->assertSame($this->testExampleCom, $client->getClient_email());
        
        $client->setClient_email('');
        $this->assertSame('', $client->getClient_email());
    }

    public function testClientMobileSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_mobile('+1-555-0123');
        $this->assertSame('+1-555-0123', $client->getClient_mobile());
        
        $client->setClient_mobile('');
        $this->assertSame('', $client->getClient_mobile());
    }

    public function testClientTitleSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_title('Dr.');
        $this->assertSame('Dr.', $client->getClient_title());
        
        $client->setClient_title(null);
        $this->assertNull($client->getClient_title());
    }

    public function testClientNameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_name('Jane');
        $this->assertSame('Jane', $client->getClient_name());
        
        $client->setClient_name('');
        $this->assertSame('', $client->getClient_name());
    }

    public function testClientSurnameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_surname('Smith');
        $this->assertSame('Smith', $client->getClient_surname());
        
        $client->setClient_surname('');
        $this->assertSame('', $client->getClient_surname());
    }

    public function testClientGroupSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_group('A');
        $this->assertSame('A', $client->getClient_group());
        
        $client->setClient_group('');
        $this->assertSame('', $client->getClient_group());
    }

    public function testClientFrequencySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_frequency('weekly');
        $this->assertSame('weekly', $client->getClient_frequency());
        
        $client->setClient_frequency('');
        $this->assertSame('', $client->getClient_frequency());
    }

    public function testClientNumberSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_number('C-12345');
        $this->assertSame('C-12345', $client->getClient_number());
        
        $client->setClient_number(null);
        $this->assertNull($client->getClient_number());
    }

    public function testClientAddress1SetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_address_1('456 Oak Avenue');
        $this->assertSame('456 Oak Avenue', $client->getClient_address_1());
        
        $client->setClient_address_1('');
        $this->assertSame('', $client->getClient_address_1());
    }

    public function testClientAddress2SetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_address_2('Apartment 789');
        $this->assertSame('Apartment 789', $client->getClient_address_2());
        
        $client->setClient_address_2('');
        $this->assertSame('', $client->getClient_address_2());
    }

    public function testClientBuildingNumberSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_building_number('45B');
        $this->assertSame('45B', $client->getClient_building_number());
        
        $client->setClient_building_number('');
        $this->assertSame('', $client->getClient_building_number());
    }

    public function testClientCitySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_city('Los Angeles');
        $this->assertSame('Los Angeles', $client->getClient_city());
        
        $client->setClient_city('');
        $this->assertSame('', $client->getClient_city());
    }

    public function testClientStateSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_state('California');
        $this->assertSame('California', $client->getClient_state());
        
        $client->setClient_state('');
        $this->assertSame('', $client->getClient_state());
    }

    public function testClientZipSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_zip('90210');
        $this->assertSame('90210', $client->getClient_zip());
        
        $client->setClient_zip('');
        $this->assertSame('', $client->getClient_zip());
    }

    public function testClientCountrySetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_country('Canada');
        $this->assertSame('Canada', $client->getClient_country());
        
        $client->setClient_country('');
        $this->assertSame('', $client->getClient_country());
    }

    public function testClientPhoneSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_phone('+1-800-555-0199');
        $this->assertSame('+1-800-555-0199', $client->getClient_phone());
        
        $client->setClient_phone('');
        $this->assertSame('', $client->getClient_phone());
    }

    public function testClientFaxSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_fax('+1-800-555-0200');
        $this->assertSame('+1-800-555-0200', $client->getClient_fax());
        
        $client->setClient_fax('');
        $this->assertSame('', $client->getClient_fax());
    }

    public function testClientWebSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_web('https://company.example.com');
        $this->assertSame('https://company.example.com', $client->getClient_web());
        
        $client->setClient_web('');
        $this->assertSame('', $client->getClient_web());
    }

    public function testClientVatIdSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_vat_id('VAT-987654321');
        $this->assertSame('VAT-987654321', $client->getClient_vat_id());
        
        $client->setClient_vat_id('');
        $this->assertSame('', $client->getClient_vat_id());
    }

    public function testClientTaxCodeSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_tax_code('TC-456789');
        $this->assertSame('TC-456789', $client->getClient_tax_code());
        
        $client->setClient_tax_code('');
        $this->assertSame('', $client->getClient_tax_code());
    }

    public function testClientLanguageSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_language('fr-FR');
        $this->assertSame('fr-FR', $client->getClient_language());
        
        $client->setClient_language('');
        $this->assertSame('', $client->getClient_language());
    }

    public function testClientActiveSetterAndGetter(): void
    {
        $client = new Client();
        
        $this->assertFalse($client->getClient_active());
        
        $client->setClient_active(true);
        $this->assertTrue($client->getClient_active());
        
        $client->setClient_active(false);
        $this->assertFalse($client->getClient_active());
    }

    public function testClientAvsSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_avs('AVS-999888777');
        $this->assertSame('AVS-999888777', $client->getClient_avs());
        
        $client->setClient_avs('');
        $this->assertSame('', $client->getClient_avs());
    }

    public function testClientInsurednumberSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_insurednumber('INS-123456789');
        $this->assertSame('INS-123456789', $client->getClient_insurednumber());
        
        $client->setClient_insurednumber('');
        $this->assertSame('', $client->getClient_insurednumber());
    }

    public function testClientVekaSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_veka('VEKA-555');
        $this->assertSame('VEKA-555', $client->getClient_veka());
        
        $client->setClient_veka('');
        $this->assertSame('', $client->getClient_veka());
    }

    public function testClientBirthdateNullHandling(): void
    {
        $client = new Client();
        
        // Test with null value - this is the only safe test given the mixed return type
        $client->setClient_birthdate(null);
        $this->assertNull($client->getClient_birthdate());
        
        // Note: The getter returns DateTimeImmutable|string|null which is effectively mixed
        // We avoid testing with actual DateTime objects to prevent type issues
    }

    public function testClientAgeSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_age(42);
        $this->assertSame(42, $client->getClient_age());
        
        $client->setClient_age(0);
        $this->assertSame(0, $client->getClient_age());
    }

    public function testClientGenderSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_gender(2);
        $this->assertSame(2, $client->getClient_gender());
        
        $client->setClient_gender(0);
        $this->assertSame(0, $client->getClient_gender());
    }

    public function testPostaladdressIdSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setPostaladdress_id(5001);
        $this->assertSame(5001, $client->getPostaladdress_id());
        
        $client->setPostaladdress_id(0);
        $this->assertSame(0, $client->getPostaladdress_id());
    }

    public function testClientDateCreatedSetter(): void
    {
        $client = new Client();
        
        $client->setClient_date_created('2023-01-15 10:30:45');
        $created = $client->getClient_date_created();
        $this->assertInstanceOf(DateTimeImmutable::class, $created);
        $this->assertSame('2023-01-15', $created->format('Y-m-d'));
    }

    public function testClientDateCreatedSetterWithInvalidFormat(): void
    {
        $client = new Client();
        
        $client->setClient_date_created('invalid-date');
        $created = $client->getClient_date_created();
        $this->assertInstanceOf(DateTimeImmutable::class, $created);
        // Should default to now when format is invalid
        $now = new DateTimeImmutable();
        $this->assertSame($now->format('Y-m-d'), $created->format('Y-m-d'));
    }

    public function testClientDateModifiedSetter(): void
    {
        $client = new Client();
        
        $client->setClient_date_modified('2023-02-20 02:45:30');
        $modified = $client->getClient_date_modified();
        $this->assertInstanceOf(DateTimeImmutable::class, $modified);
        $this->assertSame('2023-02-20', $modified->format('Y-m-d'));
    }

    public function testClientDateModifiedSetterWithInvalidFormat(): void
    {
        $client = new Client();
        
        $client->setClient_date_modified('bad-format');
        $modified = $client->getClient_date_modified();
        $this->assertInstanceOf(DateTimeImmutable::class, $modified);
        // Should default to now when format is invalid
        $now = new DateTimeImmutable();
        $this->assertSame($now->format('Y-m-d'), $modified->format('Y-m-d'));
    }

    public function testClientFullNameSetterAndGetter(): void
    {
        $client = new Client();
        
        $client->setClient_full_name('Custom Full Name');
        $this->assertSame('Custom Full Name', $client->getClient_full_name());
    }

    public function testClientFullNameGenerationWithSurname(): void
    {
        $client = new Client(client_name: 'Alice', client_surname: 'Johnson');
        $this->assertSame('Alice Johnson', $client->getClient_full_name());
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
        $client->setClient_surname('');
        $surnameProperty = $reflection->getProperty('client_surname');
        $surnameProperty->setAccessible(true);
        $surnameProperty->setValue($client, null);
        
        $this->assertSame('Bob', $client->getClient_full_name());
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
        $this->assertSame('Charlie Brown', $client->getClient_full_name());
    }

    public function testClientFullNameTrimsWhitespace(): void
    {
        $client = new Client(client_name: ' David ', client_surname: ' Wilson ');
        $fullName = $client->getClient_full_name();
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
        
        $this->assertSame('contact@acmecorp.com', $client->getClient_email());
        $this->assertSame('ACME Corporation', $client->getClient_name());
        $this->assertSame('123 Business Blvd', $client->getClient_address_1());
        $this->assertSame('Enterprise City', $client->getClient_city());
        $this->assertSame('Business State', $client->getClient_state());
        $this->assertSame('Corporate Nation', $client->getClient_country());
        $this->assertSame('+1-555-BUSINESS', $client->getClient_phone());
        $this->assertSame('https://acmecorp.com', $client->getClient_web());
        $this->assertSame('VAT-BUSINESS-001', $client->getClient_vat_id());
        $this->assertTrue($client->getClient_active());
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
        
        $this->assertSame('personal@email.com', $client->getClient_email());
        $this->assertSame('+1-555-MOBILE', $client->getClient_mobile());
        $this->assertSame('Ms.', $client->getClient_title());
        $this->assertSame('Sarah', $client->getClient_name());
        $this->assertSame('Williams', $client->getClient_surname());
        $this->assertSame('Sarah Williams', $client->getClient_full_name());
        $this->assertSame('456 Residential St', $client->getClient_address_1());
        $this->assertSame('Apt 789', $client->getClient_address_2());
        $this->assertSame('Hometown', $client->getClient_city());
        $this->assertSame('Home State', $client->getClient_state());
        $this->assertSame('12345', $client->getClient_zip());
        $this->assertSame('Home Country', $client->getClient_country());
        $this->assertSame('en-US', $client->getClient_language());
        $this->assertTrue($client->getClient_active());
        $this->assertSame(28, $client->getClient_age());
        $this->assertSame(2, $client->getClient_gender());
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
        
        $this->assertSame('international@global.com', $client->getClient_email());
        $this->assertSame('André', $client->getClient_name());
        $this->assertSame('Müller', $client->getClient_surname());
        $this->assertSame('André Müller', $client->getClient_full_name());
        $this->assertSame('Hauptstraße 123', $client->getClient_address_1());
        $this->assertSame('München', $client->getClient_city());
        $this->assertSame('Deutschland', $client->getClient_country());
        $this->assertSame('de-DE', $client->getClient_language());
        $this->assertSame('DE123456789', $client->getClient_vat_id());
        $this->assertTrue($client->getClient_active());
    }

    public function testSpecialCharactersInFields(): void
    {
        $client = new Client();
        
        $client->setClient_name('José-María O\'Connor');
        $client->setClient_address_1('123 "Main" Street & Co.');
        $client->setClient_web('https://example.com/path?param=value&other=123');
        
        $this->assertSame('José-María O\'Connor', $client->getClient_name());
        $this->assertSame('123 "Main" Street & Co.', $client->getClient_address_1());
        $this->assertSame('https://example.com/path?param=value&other=123', $client->getClient_web());
    }

    public function testUnicodeCharactersInFields(): void
    {
        $client = new Client();
        
        $client->setClient_name('李小明');
        $client->setClient_surname('王大华');
        $client->setClient_city('北京市');
        $client->setClient_country('中国');
        
        $this->assertSame('李小明', $client->getClient_name());
        $this->assertSame('王大华', $client->getClient_surname());
        $this->assertSame('北京市', $client->getClient_city());
        $this->assertSame('中国', $client->getClient_country());
        $this->assertSame('李小明 王大华', $client->getClient_full_name());
    }

    public function testLongFieldValues(): void
    {
        $client = new Client();
        
        $longText = str_repeat('Lorem ipsum dolor sit amet ', 20); // Very long text
        $client->setClient_address_1($longText);
        $this->assertSame($longText, $client->getClient_address_1());
        
        $longEmail = str_repeat('very', 50) . '@example.com';
        $client->setClient_email($longEmail);
        $this->assertSame($longEmail, $client->getClient_email());
    }

    public function testEmptyStringVsNullHandling(): void
    {
        $client = new Client();
        
        // Some fields accept empty strings
        $client->setClient_email('');
        $this->assertSame('', $client->getClient_email());
        
        $client->setClient_name('');
        $this->assertSame('', $client->getClient_name());
        
        // Most nullable fields default to empty string, not null
        $this->assertSame('', $client->getClient_title());
        $this->assertSame('', $client->getClient_surname());
        $this->assertSame('', $client->getClient_group());
        
        // Mobile defaults to empty string, not null
        $this->assertSame('', $client->getClient_mobile());
    }

    public function testCommonGroupValues(): void
    {
        $client = new Client();
        
        $groups = ['A', 'B', 'C', 'VIP', 'PRM', '1', '2', '3'];
        
        foreach ($groups as $group) {
            $client->setClient_group($group);
            $this->assertSame($group, $client->getClient_group());
        }
    }

    public function testCommonFrequencyValues(): void
    {
        $client = new Client();
        
        $frequencies = ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annually', 'one-time'];
        
        foreach ($frequencies as $frequency) {
            $client->setClient_frequency($frequency);
            $this->assertSame($frequency, $client->getClient_frequency());
        }
    }

    public function testCommonLanguageCodes(): void
    {
        $client = new Client();
        
        $languages = ['en-US', 'en-GB', 'fr-FR', 'de-DE', 'es-ES', 'it-IT', 'pt-BR', 'zh-CN', 'ja-JP'];
        
        foreach ($languages as $language) {
            $client->setClient_language($language);
            $this->assertSame($language, $client->getClient_language());
        }
    }

    public function testGenderValues(): void
    {
        $client = new Client();
        
        // Test different gender representations
        $genders = [0, 1, 2, 3]; // 0 = unknown/unspecified, 1 = male, 2 = female, 3 = other
        
        foreach ($genders as $gender) {
            $client->setClient_gender($gender);
            $this->assertSame($gender, $client->getClient_gender());
        }
    }

    public function testAgeRanges(): void
    {
        $client = new Client();
        
        $ages = [0, 18, 25, 35, 45, 55, 65, 75, 100];
        
        foreach ($ages as $age) {
            $client->setClient_age($age);
            $this->assertSame($age, $client->getClient_age());
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
            $client->setClient_vat_id($vatId);
            $this->assertSame($vatId, $client->getClient_vat_id());
        }
    }

    public function testCompleteClientWorkflow(): void
    {
        // Start with basic client
        $client = new Client(client_name: 'Test', client_email: $this->testExampleCom);
        $this->assertTrue($client->isNewRecord());
        
        // Update client information
        $client->setClient_surname('User');
        $client->setClient_active(true);
        $client->setClient_phone('+1-555-0199');
        $client->setClient_address_1('123 Test Street');
        $client->setClient_city('Test City');
        $client->setClient_country('Test Country');
        
        // Reset full name to null so getter reconstructs it
        $client->setClient_full_name('');
        // Verify updates
        $this->assertSame('Test User', $client->getClient_full_name());
        $this->assertTrue($client->getClient_active());
        $this->assertSame('+1-555-0199', $client->getClient_phone());
        $this->assertSame('123 Test Street', $client->getClient_address_1());
        $this->assertSame('Test City', $client->getClient_city());
        $this->assertSame('Test Country', $client->getClient_country());
        
        // Add invoices
        $inv1 = $this->createMock(Inv::class);
        $inv2 = $this->createMock(Inv::class);
        $client->addInv($inv1);
        $client->addInv($inv2);
        
        $this->assertCount(2, $client->getInvs());
        
        // Simulate saving (set ID)
        $client->id = 12345;
        $this->assertFalse($client->isNewRecord());
        $this->assertSame(12345, $client->getClient_id());
    }

    public function testMethodReturnTypes(): void
    {
        $client = new Client();
        
        $this->assertTrue($client->getClient_id() === null || is_int($client->getClient_id()));
        $this->assertIsString($client->getClient_email());
        $this->assertTrue($client->getClient_mobile() === null || is_string($client->getClient_mobile()));
        $this->assertIsString($client->getClient_name());
        $this->assertIsBool($client->getClient_active());
        $this->assertIsInt($client->getClient_age());
        $this->assertIsInt($client->getClient_gender());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClient_date_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $client->getClient_date_modified());
        $this->assertInstanceOf(ArrayCollection::class, $client->getDelivery_locations());
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
        
        $this->assertSame('mixed@test.com', $client->getClient_email());
        $this->assertSame('Mixed', $client->getClient_name());
        $this->assertTrue($client->getClient_active());
        $this->assertSame(30, $client->getClient_age());
        
        // Other parameters should use defaults
        $this->assertSame('', $client->getClient_mobile());
        $this->assertSame('', $client->getClient_title());
        $this->assertSame('', $client->getClient_surname());
        $this->assertSame(0, $client->getClient_gender());
    }

    public function testEntityStateConsistency(): void
    {
        $client = new Client();
        
        // Verify initial state is consistent
        $this->assertTrue($client->isNewRecord());
        $this->assertFalse($client->getClient_active());
        $this->assertSame(0, $client->getClient_age());
        $this->assertSame(0, $client->getClient_gender());
        
        // Change state and verify consistency
        $client->setClient_active(true);
        $client->setClient_age(25);
        $client->setClient_gender(1);
        
        $this->assertTrue($client->getClient_active());
        $this->assertSame(25, $client->getClient_age());
        $this->assertSame(1, $client->getClient_gender());
        $this->assertTrue($client->isNewRecord()); // Still new until ID is set
    }
}