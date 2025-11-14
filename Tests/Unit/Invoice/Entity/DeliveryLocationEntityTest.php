<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\DeliveryLocation;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeliveryLocationEntityTest extends TestCase
{
    public string $mainWarehouse = 'Main Warehouse';
    
    public string $seqNumbers = '1234567890123';
    
    public string $unitedStates = 'United States';
    
    public string $testCity = 'Test City';
    
    public string $oneToFive = '12345';
    
    public string $testCountry = 'Test Country';
    
    public function testConstructorWithDefaults(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $this->assertNull($deliveryLocation->getId());
        $this->assertSame('', $deliveryLocation->getClient_id());
        $this->assertSame('', $deliveryLocation->getName());
        $this->assertSame('', $deliveryLocation->getBuildingNumber());
        $this->assertSame('', $deliveryLocation->getAddress_1());
        $this->assertSame('', $deliveryLocation->getAddress_2());
        $this->assertSame('', $deliveryLocation->getCity());
        $this->assertSame('', $deliveryLocation->getState());
        $this->assertSame('', $deliveryLocation->getZip());
        $this->assertSame('', $deliveryLocation->getCountry());
        $this->assertSame('', $deliveryLocation->getGlobal_location_number());
        $this->assertSame('', $deliveryLocation->getElectronic_address_scheme());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_modified());
        $this->assertNull($deliveryLocation->getClient());
    }

    public function testConstructorWithAllParameters(): void
    {
        $deliveryLocation = new DeliveryLocation(
            id: 1,
            client_id: 123,
            name: $this->mainWarehouse,
            building_number: '10A',
            address_1: '123 Industrial Drive',
            address_2: 'Suite 200',
            city: 'Business City',
            state: 'CA',
            zip: '90210',
            country: 'USA',
            global_location_number: $this->seqNumbers,
            electronic_address_scheme: 'GLN'
        );
        
        $this->assertSame(1, $deliveryLocation->getId());
        $this->assertSame('123', $deliveryLocation->getClient_id());
        $this->assertSame($this->mainWarehouse, $deliveryLocation->getName());
        $this->assertSame('10A', $deliveryLocation->getBuildingNumber());
        $this->assertSame('123 Industrial Drive', $deliveryLocation->getAddress_1());
        $this->assertSame('Suite 200', $deliveryLocation->getAddress_2());
        $this->assertSame('Business City', $deliveryLocation->getCity());
        $this->assertSame('CA', $deliveryLocation->getState());
        $this->assertSame('90210', $deliveryLocation->getZip());
        $this->assertSame('USA', $deliveryLocation->getCountry());
        $this->assertSame($this->seqNumbers, $deliveryLocation->getGlobal_location_number());
        $this->assertSame('GLN', $deliveryLocation->getElectronic_address_scheme());
    }

    public function testIdSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setId(100);
        
        $this->assertSame(100, $deliveryLocation->getId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setClient_id(456);
        
        $this->assertSame('456', $deliveryLocation->getClient_id());
    }

    public function testNameSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('Distribution Center');
        
        $this->assertSame('Distribution Center', $deliveryLocation->getName());
    }

    public function testBuildingNumberSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setBuildingNumber('Building 5');
        
        $this->assertSame('Building 5', $deliveryLocation->getBuildingNumber());
    }

    public function testAddress1SetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setAddress_1('456 Commerce Blvd');
        
        $this->assertSame('456 Commerce Blvd', $deliveryLocation->getAddress_1());
    }

    public function testAddress2SetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setAddress_2('Floor 3');
        
        $this->assertSame('Floor 3', $deliveryLocation->getAddress_2());
    }

    public function testCitySetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setCity('Los Angeles');
        
        $this->assertSame('Los Angeles', $deliveryLocation->getCity());
    }

    public function testStateSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setState('California');
        
        $this->assertSame('California', $deliveryLocation->getState());
    }

    public function testZipSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setZip('90001');
        
        $this->assertSame('90001', $deliveryLocation->getZip());
    }

    public function testCountrySetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setCountry($this->unitedStates);
        
        $this->assertSame($this->unitedStates, $deliveryLocation->getCountry());
    }

    public function testGlobalLocationNumberSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setGlobal_location_number('9876543210987');
        
        $this->assertSame('9876543210987', $deliveryLocation->getGlobal_location_number());
    }

    public function testElectronicAddressSchemeSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setElectronic_address_scheme('EAN');
        
        $this->assertSame('EAN', $deliveryLocation->getElectronic_address_scheme());
    }

    public function testClientSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $client = $this->createMock(Client::class);
        $deliveryLocation->setClient($client);
        
        $this->assertSame($client, $deliveryLocation->getClient());
    }

    public function testIsNewRecord(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $this->assertTrue($deliveryLocation->isNewRecord());
        
        $deliveryLocation->setId(1);
        $this->assertFalse($deliveryLocation->isNewRecord());
    }

    public function testDateTimeImmutableProperties(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $dateCreated = $deliveryLocation->getDate_created();
        $dateModified = $deliveryLocation->getDate_modified();
        
        $this->assertInstanceOf(DateTimeImmutable::class, $dateCreated);
        $this->assertInstanceOf(DateTimeImmutable::class, $dateModified);
        $this->assertLessThanOrEqual(time(), $dateCreated->getTimestamp());
        $this->assertLessThanOrEqual(time(), $dateModified->getTimestamp());
    }

    public function testWarehouseLocation(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName($this->mainWarehouse);
        $deliveryLocation->setBuildingNumber('1');
        $deliveryLocation->setAddress_1('100 Industrial Way');
        $deliveryLocation->setCity('Commerce City');
        $deliveryLocation->setState('CO');
        $deliveryLocation->setZip('80022');
        $deliveryLocation->setCountry('USA');
        
        $this->assertSame($this->mainWarehouse, $deliveryLocation->getName());
        $this->assertSame('100 Industrial Way', $deliveryLocation->getAddress_1());
        $this->assertSame('Commerce City', $deliveryLocation->getCity());
    }

    public function testRetailLocation(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('Downtown Store');
        $deliveryLocation->setAddress_1('567 Main Street');
        $deliveryLocation->setAddress_2('Ground Floor');
        $deliveryLocation->setCity('Downtown');
        $deliveryLocation->setState('NY');
        $deliveryLocation->setZip('10001');
        $deliveryLocation->setCountry($this->unitedStates);
        
        $this->assertSame('Downtown Store', $deliveryLocation->getName());
        $this->assertSame('567 Main Street', $deliveryLocation->getAddress_1());
        $this->assertSame('Ground Floor', $deliveryLocation->getAddress_2());
    }

    public function testInternationalLocation(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('European Distribution Center');
        $deliveryLocation->setAddress_1('Logistics Park 15');
        $deliveryLocation->setCity('Rotterdam');
        $deliveryLocation->setState('Zuid-Holland');
        $deliveryLocation->setZip('3000 AA');
        $deliveryLocation->setCountry('Netherlands');
        
        $this->assertSame('European Distribution Center', $deliveryLocation->getName());
        $this->assertSame('Rotterdam', $deliveryLocation->getCity());
        $this->assertSame('3000 AA', $deliveryLocation->getZip());
        $this->assertSame('Netherlands', $deliveryLocation->getCountry());
    }

    public function testLongAddressFields(): void
    {
        $longName = str_repeat('Very Long Warehouse Name ', 10);
        $longAddress = str_repeat('Extra Long Business Address With Many Details ', 8);
        
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName($longName);
        $deliveryLocation->setAddress_1($longAddress);
        
        $this->assertSame($longName, $deliveryLocation->getName());
        $this->assertSame($longAddress, $deliveryLocation->getAddress_1());
    }

    public function testSpecialCharactersInAddress(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('Müller & Co. Warehouse');
        $deliveryLocation->setAddress_1('Straße der Einheit 123');
        $deliveryLocation->setCity('München');
        
        $this->assertSame('Müller & Co. Warehouse', $deliveryLocation->getName());
        $this->assertSame('Straße der Einheit 123', $deliveryLocation->getAddress_1());
        $this->assertSame('München', $deliveryLocation->getCity());
    }

    public function testUnicodeCharactersInAddress(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('東京配送センター');
        $deliveryLocation->setAddress_1('新宿区西新宿1-1-1');
        $deliveryLocation->setCity('東京');
        $deliveryLocation->setCountry('日本');
        
        $this->assertSame('東京配送センター', $deliveryLocation->getName());
        $this->assertSame('新宿区西新宿1-1-1', $deliveryLocation->getAddress_1());
        $this->assertSame('東京', $deliveryLocation->getCity());
        $this->assertSame('日本', $deliveryLocation->getCountry());
    }

    public function testZeroAndLargeIds(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $deliveryLocation->setId(0);
        $this->assertSame(0, $deliveryLocation->getId());
        
        $deliveryLocation->setId(999999);
        $this->assertSame(999999, $deliveryLocation->getId());
        
        $deliveryLocation->setClient_id(0);
        $this->assertSame('0', $deliveryLocation->getClient_id());
        
        $deliveryLocation->setClient_id(999999);
        $this->assertSame('999999', $deliveryLocation->getClient_id());
    }

    public function testCompleteDeliveryLocationSetup(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $client = $this->createMock(Client::class);
        
        $deliveryLocation->setId(1);
        $deliveryLocation->setClient_id(123);
        $deliveryLocation->setClient($client);
        $deliveryLocation->setName('Complete Test Location');
        $deliveryLocation->setBuildingNumber('100');
        $deliveryLocation->setAddress_1('Main Business Street');
        $deliveryLocation->setAddress_2('Unit 5');
        $deliveryLocation->setCity($this->testCity);
        $deliveryLocation->setState('Test State');
        $deliveryLocation->setZip($this->oneToFive);
        $deliveryLocation->setCountry($this->testCountry);
        $deliveryLocation->setGlobal_location_number($this->seqNumbers);
        $deliveryLocation->setElectronic_address_scheme('GLN');
        
        $this->assertSame(1, $deliveryLocation->getId());
        $this->assertSame('123', $deliveryLocation->getClient_id());
        $this->assertSame($client, $deliveryLocation->getClient());
        $this->assertSame('Complete Test Location', $deliveryLocation->getName());
        $this->assertSame('100', $deliveryLocation->getBuildingNumber());
        $this->assertSame('Main Business Street', $deliveryLocation->getAddress_1());
        $this->assertSame('Unit 5', $deliveryLocation->getAddress_2());
        $this->assertSame($this->testCity, $deliveryLocation->getCity());
        $this->assertSame('Test State', $deliveryLocation->getState());
        $this->assertSame($this->oneToFive, $deliveryLocation->getZip());
        $this->assertSame($this->testCountry, $deliveryLocation->getCountry());
        $this->assertSame($this->mainWarehouse, $deliveryLocation->getGlobal_location_number());
        $this->assertSame('GLN', $deliveryLocation->getElectronic_address_scheme());
        $this->assertFalse($deliveryLocation->isNewRecord());
    }

    public function testGetterMethodsConsistency(): void
    {
        $deliveryLocation = new DeliveryLocation(
            id: 1,
            client_id: 123,
            name: 'Test Location',
            building_number: '5A',
            address_1: 'Test Street',
            address_2: 'Suite 1',
            city: $this->testCity,
            state: 'TS',
            zip: $this->oneToFive,
            country: $this->testCountry,
            global_location_number: $this->seqNumbers,
            electronic_address_scheme: 'GLN'
        );
        
        $this->assertIsInt($deliveryLocation->getId());
        $this->assertIsString($deliveryLocation->getClient_id());
        $this->assertIsString($deliveryLocation->getName());
        $this->assertIsString($deliveryLocation->getBuildingNumber());
        $this->assertIsString($deliveryLocation->getAddress_1());
        $this->assertIsString($deliveryLocation->getAddress_2());
        $this->assertIsString($deliveryLocation->getCity());
        $this->assertIsString($deliveryLocation->getState());
        $this->assertIsString($deliveryLocation->getZip());
        $this->assertIsString($deliveryLocation->getCountry());
        $this->assertIsString($deliveryLocation->getGlobal_location_number());
        $this->assertIsString($deliveryLocation->getElectronic_address_scheme());
    }

    public function testGlobalLocationNumberFormats(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        // Standard 13-digit GLN
        $deliveryLocation->setGlobal_location_number($this->seqNumbers);
        $this->assertSame($this->seqNumbers, $deliveryLocation->getGlobal_location_number());
        
        // Empty GLN
        $deliveryLocation->setGlobal_location_number('');
        $this->assertSame('', $deliveryLocation->getGlobal_location_number());
        
        // Null GLN
        $deliveryLocation->setGlobal_location_number(null);
        $this->assertNull($deliveryLocation->getGlobal_location_number());
    }

    public function testElectronicAddressSchemeTypes(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $schemes = ['GLN', 'EAN', 'DUNS', 'UBL'];
        
        foreach ($schemes as $scheme) {
            $deliveryLocation->setElectronic_address_scheme($scheme);
            $this->assertSame($scheme, $deliveryLocation->getElectronic_address_scheme());
        }
    }

    public function testPropertyTypes(): void
    {
        $deliveryLocation = new DeliveryLocation(
            id: 1,
            client_id: 123
        );
        
        $this->assertIsInt($deliveryLocation->getId());
        $this->assertIsString($deliveryLocation->getClient_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_modified());
    }

    public function testNegativeIds(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $deliveryLocation->setId(-1);
        $this->assertSame(-1, $deliveryLocation->getId());
        
        $deliveryLocation->setClient_id(-100);
        $this->assertSame('-100', $deliveryLocation->getClient_id());
    }

    public function testDeliveryLocationWorkflow(): void
    {
        // Create new delivery location
        $deliveryLocation = new DeliveryLocation();
        $this->assertTrue($deliveryLocation->isNewRecord());
        
        // Set basic information
        $deliveryLocation->setName('New Delivery Location');
        $deliveryLocation->setAddress_1('123 New Street');
        $deliveryLocation->setCity('New City');
        $deliveryLocation->setZip($this->oneToFive);
        
        // Still new until ID is set
        $this->assertTrue($deliveryLocation->isNewRecord());
        
        // Assign ID (simulating database save)
        $deliveryLocation->setId(1);
        $this->assertFalse($deliveryLocation->isNewRecord());
        
        // Update location
        $deliveryLocation->setAddress_1('456 Updated Street');
        $this->assertSame('456 Updated Street', $deliveryLocation->getAddress_1());
        $this->assertFalse($deliveryLocation->isNewRecord());
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $deliveryLocation = new DeliveryLocation();
        $afterTime = time();
        
        $createdTime = $deliveryLocation->getDate_created()->getTimestamp();
        $this->assertGreaterThanOrEqual($beforeTime, $createdTime);
        $this->assertLessThanOrEqual($afterTime, $createdTime);
    }

    public function testEntityStateAfterConstruction(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $this->assertTrue($deliveryLocation->isNewRecord());
        $this->assertNull($deliveryLocation->getClient());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDate_modified());
    }

    public function testNullValueHandling(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $deliveryLocation->setGlobal_location_number(null);
        $this->assertNull($deliveryLocation->getGlobal_location_number());
        
        $deliveryLocation->setElectronic_address_scheme(null);
        $this->assertNull($deliveryLocation->getElectronic_address_scheme());
        
        $deliveryLocation->setClient(null);
        $this->assertNull($deliveryLocation->getClient());
    }
}
