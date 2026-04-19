<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
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
        $this->assertSame(0, $deliveryLocation->getClientId());
        $this->assertSame('', $deliveryLocation->getName());
        $this->assertSame('', $deliveryLocation->getBuildingNumber());
        $this->assertSame('', $deliveryLocation->getAddress1());
        $this->assertSame('', $deliveryLocation->getAddress2());
        $this->assertSame('', $deliveryLocation->getCity());
        $this->assertSame('', $deliveryLocation->getState());
        $this->assertSame('', $deliveryLocation->getZip());
        $this->assertSame('', $deliveryLocation->getCountry());
        $this->assertSame('', $deliveryLocation->getGlobalLocationNumber());
        $this->assertSame('', $deliveryLocation->getElectronicAddressScheme());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateModified());
        $this->assertNull($deliveryLocation->getClient());
    }

    public function testConstructorWithAllParameters(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setId(1);
        $deliveryLocation->setClientId(123);
        $deliveryLocation->setName($this->mainWarehouse);
        $deliveryLocation->setBuildingNumber('10A');
        $deliveryLocation->setAddress1('123 Industrial Drive');
        $deliveryLocation->setAddress2('Suite 200');
        $deliveryLocation->setCity('Business City');
        $deliveryLocation->setState('CA');
        $deliveryLocation->setZip('90210');
        $deliveryLocation->setCountry('USA');
        $deliveryLocation->setGlobalLocationNumber($this->seqNumbers);
        $deliveryLocation->setElectronicAddressScheme('GLN');
        
        $this->assertSame(1, $deliveryLocation->getId());
        $this->assertSame(123, $deliveryLocation->getClientId());
        $this->assertSame($this->mainWarehouse, $deliveryLocation->getName());
        $this->assertSame('10A', $deliveryLocation->getBuildingNumber());
        $this->assertSame('123 Industrial Drive', $deliveryLocation->getAddress1());
        $this->assertSame('Suite 200', $deliveryLocation->getAddress2());
        $this->assertSame('Business City', $deliveryLocation->getCity());
        $this->assertSame('CA', $deliveryLocation->getState());
        $this->assertSame('90210', $deliveryLocation->getZip());
        $this->assertSame('USA', $deliveryLocation->getCountry());
        $this->assertSame($this->seqNumbers, $deliveryLocation->getGlobalLocationNumber());
        $this->assertSame('GLN', $deliveryLocation->getElectronicAddressScheme());
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
        $deliveryLocation->setClientId(456);
        
        $this->assertSame(456, $deliveryLocation->getClientId());
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
        $deliveryLocation->setAddress1('456 Commerce Blvd');
        
        $this->assertSame('456 Commerce Blvd', $deliveryLocation->getAddress1());
    }

    public function testAddress2SetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setAddress2('Floor 3');
        
        $this->assertSame('Floor 3', $deliveryLocation->getAddress2());
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
        $deliveryLocation->setGlobalLocationNumber('9876543210987');
        
        $this->assertSame('9876543210987', $deliveryLocation->getGlobalLocationNumber());
    }

    public function testElectronicAddressSchemeSetterAndGetter(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setElectronicAddressScheme('EAN');
        
        $this->assertSame('EAN', $deliveryLocation->getElectronicAddressScheme());
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
        
        $dateCreated = $deliveryLocation->getDateCreated();
        $dateModified = $deliveryLocation->getDateModified();
        
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
        $deliveryLocation->setAddress1('100 Industrial Way');
        $deliveryLocation->setCity('Commerce City');
        $deliveryLocation->setState('CO');
        $deliveryLocation->setZip('80022');
        $deliveryLocation->setCountry('USA');
        
        $this->assertSame($this->mainWarehouse, $deliveryLocation->getName());
        $this->assertSame('100 Industrial Way', $deliveryLocation->getAddress1());
        $this->assertSame('Commerce City', $deliveryLocation->getCity());
    }

    public function testRetailLocation(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('Downtown Store');
        $deliveryLocation->setAddress1('567 Main Street');
        $deliveryLocation->setAddress2('Ground Floor');
        $deliveryLocation->setCity('Downtown');
        $deliveryLocation->setState('NY');
        $deliveryLocation->setZip('10001');
        $deliveryLocation->setCountry($this->unitedStates);
        
        $this->assertSame('Downtown Store', $deliveryLocation->getName());
        $this->assertSame('567 Main Street', $deliveryLocation->getAddress1());
        $this->assertSame('Ground Floor', $deliveryLocation->getAddress2());
    }

    public function testInternationalLocation(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('European Distribution Center');
        $deliveryLocation->setAddress1('Logistics Park 15');
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
        $deliveryLocation->setAddress1($longAddress);
        
        $this->assertSame($longName, $deliveryLocation->getName());
        $this->assertSame($longAddress, $deliveryLocation->getAddress1());
    }

    public function testSpecialCharactersInAddress(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('Müller & Co. Warehouse');
        $deliveryLocation->setAddress1('Straße der Einheit 123');
        $deliveryLocation->setCity('München');
        
        $this->assertSame('Müller & Co. Warehouse', $deliveryLocation->getName());
        $this->assertSame('Straße der Einheit 123', $deliveryLocation->getAddress1());
        $this->assertSame('München', $deliveryLocation->getCity());
    }

    public function testUnicodeCharactersInAddress(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setName('東京配送センター');
        $deliveryLocation->setAddress1('新宿区西新宿1-1-1');
        $deliveryLocation->setCity('東京');
        $deliveryLocation->setCountry('日本');
        
        $this->assertSame('東京配送センター', $deliveryLocation->getName());
        $this->assertSame('新宿区西新宿1-1-1', $deliveryLocation->getAddress1());
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
        
        $deliveryLocation->setClientId(0);
        $this->assertSame(0, $deliveryLocation->getClientId());

        $deliveryLocation->setClientId(999999);
        $this->assertSame(999999, $deliveryLocation->getClientId());
    }

    public function testCompleteDeliveryLocationSetup(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $client = $this->createMock(Client::class);
        
        $deliveryLocation->setId(1);
        $deliveryLocation->setClientId(123);
        $deliveryLocation->setClient($client);
        $deliveryLocation->setName('Complete Test Location');
        $deliveryLocation->setBuildingNumber('100');
        $deliveryLocation->setAddress1('Main Business Street');
        $deliveryLocation->setAddress2('Unit 5');
        $deliveryLocation->setCity($this->testCity);
        $deliveryLocation->setState('Test State');
        $deliveryLocation->setZip($this->oneToFive);
        $deliveryLocation->setCountry($this->testCountry);
        $deliveryLocation->setGlobalLocationNumber($this->seqNumbers);
        $deliveryLocation->setElectronicAddressScheme('GLN');
        
        $this->assertSame(1, $deliveryLocation->getId());
        $this->assertSame(123, $deliveryLocation->getClientId());
        $this->assertSame($client, $deliveryLocation->getClient());
        $this->assertSame('Complete Test Location', $deliveryLocation->getName());
        $this->assertSame('100', $deliveryLocation->getBuildingNumber());
        $this->assertSame('Main Business Street', $deliveryLocation->getAddress1());
        $this->assertSame('Unit 5', $deliveryLocation->getAddress2());
        $this->assertSame($this->testCity, $deliveryLocation->getCity());
        $this->assertSame('Test State', $deliveryLocation->getState());
        $this->assertSame($this->oneToFive, $deliveryLocation->getZip());
        $this->assertSame($this->testCountry, $deliveryLocation->getCountry());
        $this->assertSame($this->seqNumbers, $deliveryLocation->getGlobalLocationNumber());
        $this->assertSame('GLN', $deliveryLocation->getElectronicAddressScheme());
        $this->assertFalse($deliveryLocation->isNewRecord());
    }

    public function testGetterMethodsConsistency(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setId(1);
        $deliveryLocation->setClientId(123);
        $deliveryLocation->setName('Test Location');
        $deliveryLocation->setBuildingNumber('5A');
        $deliveryLocation->setAddress1('Test Street');
        $deliveryLocation->setAddress2('Suite 1');
        $deliveryLocation->setCity($this->testCity);
        $deliveryLocation->setState('TS');
        $deliveryLocation->setZip($this->oneToFive);
        $deliveryLocation->setCountry($this->testCountry);
        $deliveryLocation->setGlobalLocationNumber($this->seqNumbers);
        $deliveryLocation->setElectronicAddressScheme('GLN');

        $this->assertIsInt($deliveryLocation->getId());
        $this->assertIsInt($deliveryLocation->getClientId());
        $this->assertIsString($deliveryLocation->getName());
        $this->assertIsString($deliveryLocation->getBuildingNumber());
        $this->assertIsString($deliveryLocation->getAddress1());
        $this->assertIsString($deliveryLocation->getAddress2());
        $this->assertIsString($deliveryLocation->getCity());
        $this->assertIsString($deliveryLocation->getState());
        $this->assertIsString($deliveryLocation->getZip());
        $this->assertIsString($deliveryLocation->getCountry());
        $this->assertIsString($deliveryLocation->getGlobalLocationNumber());
        $this->assertIsString($deliveryLocation->getElectronicAddressScheme());
    }

    public function testGlobalLocationNumberFormats(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        // Standard 13-digit GLN
        $deliveryLocation->setGlobalLocationNumber($this->seqNumbers);
        $this->assertSame($this->seqNumbers, $deliveryLocation->getGlobalLocationNumber());
        
        // Empty GLN
        $deliveryLocation->setGlobalLocationNumber('');
        $this->assertSame('', $deliveryLocation->getGlobalLocationNumber());
        
        // Null GLN
        $deliveryLocation->setGlobalLocationNumber(null);
        $this->assertNull($deliveryLocation->getGlobalLocationNumber());
    }

    public function testElectronicAddressSchemeTypes(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $schemes = ['GLN', 'EAN', 'DUNS', 'UBL'];
        
        foreach ($schemes as $scheme) {
            $deliveryLocation->setElectronicAddressScheme($scheme);
            $this->assertSame($scheme, $deliveryLocation->getElectronicAddressScheme());
        }
    }

    public function testPropertyTypes(): void
    {
        $deliveryLocation = new DeliveryLocation();
        $deliveryLocation->setId(1);
        $deliveryLocation->setClientId(123);

        $this->assertIsInt($deliveryLocation->getId());
        $this->assertIsInt($deliveryLocation->getClientId());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateModified());
    }

    public function testNegativeIds(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $deliveryLocation->setId(-1);
        $this->assertSame(-1, $deliveryLocation->getId());
        
        $deliveryLocation->setClientId(-100);
        $this->assertSame(-100, $deliveryLocation->getClientId());
    }

    public function testDeliveryLocationWorkflow(): void
    {
        // Create new delivery location
        $deliveryLocation = new DeliveryLocation();
        $this->assertTrue($deliveryLocation->isNewRecord());
        
        // Set basic information
        $deliveryLocation->setName('New Delivery Location');
        $deliveryLocation->setAddress1('123 New Street');
        $deliveryLocation->setCity('New City');
        $deliveryLocation->setZip($this->oneToFive);
        
        // Still new until ID is set
        $this->assertTrue($deliveryLocation->isNewRecord());
        
        // Assign ID (simulating database save)
        $deliveryLocation->setId(1);
        $this->assertFalse($deliveryLocation->isNewRecord());
        
        // Update location
        $deliveryLocation->setAddress1('456 Updated Street');
        $this->assertSame('456 Updated Street', $deliveryLocation->getAddress1());
        $this->assertFalse($deliveryLocation->isNewRecord());
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $deliveryLocation = new DeliveryLocation();
        $afterTime = time();
        
        $createdTime = $deliveryLocation->getDateCreated()->getTimestamp();
        $this->assertGreaterThanOrEqual($beforeTime, $createdTime);
        $this->assertLessThanOrEqual($afterTime, $createdTime);
    }

    public function testEntityStateAfterConstruction(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $this->assertTrue($deliveryLocation->isNewRecord());
        $this->assertNull($deliveryLocation->getClient());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $deliveryLocation->getDateModified());
    }

    public function testNullValueHandling(): void
    {
        $deliveryLocation = new DeliveryLocation();
        
        $deliveryLocation->setGlobalLocationNumber(null);
        $this->assertNull($deliveryLocation->getGlobalLocationNumber());
        
        $deliveryLocation->setElectronicAddressScheme(null);
        $this->assertNull($deliveryLocation->getElectronicAddressScheme());
        
        $deliveryLocation->setClient(null);
        $this->assertNull($deliveryLocation->getClient());
    }
}
