<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\PostalAddress;
use Codeception\Test\Unit;

final class PostalAddressEntityTest extends Unit
{
    public string $mainStreet = 'Main Street';
    
    public string $newYork = 'New York';
    
    public string $oneThousandOne = '10001';
    
    public string $oneToFive = '12345';
    
    public string $testCity = 'Test City';
    
    public function testConstructorWithDefaults(): void
    {
        $postalAddress = new PostalAddress();
        
        $this->assertSame('', $postalAddress->getId());
        $this->assertSame('', $postalAddress->getClientId());
        $this->assertSame('', $postalAddress->getStreetName());
        $this->assertSame('', $postalAddress->getAdditionalStreetName());
        $this->assertSame('', $postalAddress->getBuildingNumber());
        $this->assertSame('', $postalAddress->getCityName());
        $this->assertSame('', $postalAddress->getPostalzone());
        $this->assertSame('', $postalAddress->getCountrysubentity());
        $this->assertSame('', $postalAddress->getCountry());
    }

    public function testConstructorWithAllParameters(): void
    {
        $postalAddress = new PostalAddress(
            1,
            123,
            $this->mainStreet,
            'Apt 2B',
            '456',
            $this->newYork,
            $this->oneThousandOne,
            'NY',
            'USA'
        );
        
        $this->assertSame('1', $postalAddress->getId());
        $this->assertSame('123', $postalAddress->getClientId());
        $this->assertSame($this->mainStreet, $postalAddress->getStreetName());
        $this->assertSame('Apt 2B', $postalAddress->getAdditionalStreetName());
        $this->assertSame('456', $postalAddress->getBuildingNumber());
        $this->assertSame($this->newYork, $postalAddress->getCityName());
        $this->assertSame($this->oneThousandOne, $postalAddress->getPostalzone());
        $this->assertSame('NY', $postalAddress->getCountrysubentity());
        $this->assertSame('USA', $postalAddress->getCountry());
    }

    public function testIdSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setId(42);
        
        $this->assertSame('42', $postalAddress->getId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setClientId(999);
        
        $this->assertSame('999', $postalAddress->getClientId());
    }

    public function testStreetNameSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setStreetName('Oak Avenue');
        
        $this->assertSame('Oak Avenue', $postalAddress->getStreetName());
    }

    public function testAdditionalStreetNameSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setAdditionalStreetName('Suite 100');
        
        $this->assertSame('Suite 100', $postalAddress->getAdditionalStreetName());
    }

    public function testBuildingNumberSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setBuildingNumber('123A');
        
        $this->assertSame('123A', $postalAddress->getBuildingNumber());
    }

    public function testCityNameSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setCityName('Los Angeles');
        
        $this->assertSame('Los Angeles', $postalAddress->getCityName());
    }

    public function testPostalzoneSetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setPostalzone('90210');
        
        $this->assertSame('90210', $postalAddress->getPostalzone());
    }

    public function testCountrysubentitySetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setCountrysubentity('California');
        
        $this->assertSame('California', $postalAddress->getCountrysubentity());
    }

    public function testCountrySetterAndGetter(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setCountry('United States');
        
        $this->assertSame('United States', $postalAddress->getCountry());
    }

    public function testCommonAddressFormats(): void
    {
        // US Address
        $usAddress = new PostalAddress(
            1, 100, '123 Main St', 'Apt 4B', '123', 'Chicago', '60601', 'IL', 'USA'
        );
        $this->assertSame('123 Main St', $usAddress->getStreetName());
        $this->assertSame('Chicago', $usAddress->getCityName());
        $this->assertSame('60601', $usAddress->getPostalzone());

        // UK Address
        $ukAddress = new PostalAddress(
            2, 101, 'Baker Street', 'Flat 2', '221B', 'London', 'NW1 6XE', 'England', 'UK'
        );
        $this->assertSame('Baker Street', $ukAddress->getStreetName());
        $this->assertSame('London', $ukAddress->getCityName());
        $this->assertSame('NW1 6XE', $ukAddress->getPostalzone());
    }

    public function testSpecialCharactersInAddress(): void
    {
        $postalAddress = new PostalAddress(
            1, 1, 'Römerstraße', 'Haus & Garten', '12-14', 'München', '80331', 'Bayern', 'Deutschland'
        );
        
        $this->assertSame('Römerstraße', $postalAddress->getStreetName());
        $this->assertSame('München', $postalAddress->getCityName());
        $this->assertSame('Deutschland', $postalAddress->getCountry());
    }

    public function testLongAddressFields(): void
    {
        $longStreet = 'Very Long Street Name That Could Exceed Limits';
        $longCity = 'Very Long City Name That Could Exceed Normal Limits';
        
        $postalAddress = new PostalAddress(
            1, 1, $longStreet, 'Suite 1000', '9999', $longCity, $this->oneToFive, 'State', 'Country'
        );
        
        $this->assertSame($longStreet, $postalAddress->getStreetName());
        $this->assertSame($longCity, $postalAddress->getCityName());
    }

    public function testChainedSetterCalls(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setId(1);
        $postalAddress->setClientId(100);
        $postalAddress->setStreetName('Test Street');
        $postalAddress->setAdditionalStreetName('Test Apt');
        $postalAddress->setBuildingNumber('100');
        $postalAddress->setCityName($this->testCity);
        $postalAddress->setPostalzone($this->oneToFive);
        $postalAddress->setCountrysubentity('Test State');
        $postalAddress->setCountry('Test Country');
        
        $this->assertSame('1', $postalAddress->getId());
        $this->assertSame('100', $postalAddress->getClientId());
        $this->assertSame('Test Street', $postalAddress->getStreetName());
        $this->assertSame('Test Apt', $postalAddress->getAdditionalStreetName());
        $this->assertSame('100', $postalAddress->getBuildingNumber());
        $this->assertSame($this->testCity, $postalAddress->getCityName());
        $this->assertSame($this->oneToFive, $postalAddress->getPostalzone());
        $this->assertSame('Test State', $postalAddress->getCountrysubentity());
        $this->assertSame('Test Country', $postalAddress->getCountry());
    }

    public function testStringConversions(): void
    {
        $postalAddress = new PostalAddress(123, 456, 'Street', 'Apt', '789', 'City', $this->oneToFive, 'State', 'Country');
        
        // Verify getters return strings even though setters accept ints for ID fields
        $this->assertIsString($postalAddress->getId());
        $this->assertIsString($postalAddress->getClientId());
        $this->assertSame('123', $postalAddress->getId());
        $this->assertSame('456', $postalAddress->getClientId());
    }

    public function testPublicIdProperty(): void
    {
        $postalAddress = new PostalAddress(999, 1, 'Street', 'Apt', '1', 'City', $this->oneToFive, 'State', 'Country');
        
        // Test that id property is accessible as public
        $this->assertSame(999, $postalAddress->id);
    }

    public function testGetFullAddressMethod(): void
    {
        $postalAddress = new PostalAddress(
            1, 1, $this->mainStreet, 'Suite 200', '123', $this->newYork, $this->oneThousandOne, 'NY', 'USA'
        );
        
        $expectedFullAddress = 'Main Street 123, Suite 200, 10001';
        $this->assertSame($expectedFullAddress, $postalAddress->getFullAddress());
    }

    public function testGetFullAddressWithEmptyFields(): void
    {
        $postalAddress = new PostalAddress(1, 1, 'Oak St', '', '45', 'Boston', '02101', 'MA', 'USA');
        
        $expectedFullAddress = 'Oak St 45, , 02101';
        $this->assertSame($expectedFullAddress, $postalAddress->getFullAddress());
    }

    public function testGetFullAddressAllEmpty(): void
    {
        $postalAddress = new PostalAddress();
        
        $expectedFullAddress = ' , , ';
        $this->assertSame($expectedFullAddress, $postalAddress->getFullAddress());
    }

    public function testCompleteAddressSetup(): void
    {
        $postalAddress = new PostalAddress();
        $postalAddress->setId(1000);
        $postalAddress->setClientId(2000);
        $postalAddress->setStreetName('Complete Street');
        $postalAddress->setAdditionalStreetName('Complete Apt');
        $postalAddress->setBuildingNumber('100');
        $postalAddress->setCityName('Complete City');
        $postalAddress->setPostalzone('54321');
        $postalAddress->setCountrysubentity('Complete State');
        $postalAddress->setCountry('Complete Country');
        
        $this->assertSame('1000', $postalAddress->getId());
        $this->assertSame('2000', $postalAddress->getClientId());
        $this->assertSame('Complete Street', $postalAddress->getStreetName());
        $this->assertSame('Complete Apt', $postalAddress->getAdditionalStreetName());
        $this->assertSame('100', $postalAddress->getBuildingNumber());
        $this->assertSame('Complete City', $postalAddress->getCityName());
        $this->assertSame('54321', $postalAddress->getPostalzone());
        $this->assertSame('Complete State', $postalAddress->getCountrysubentity());
        $this->assertSame('Complete Country', $postalAddress->getCountry());
    }

    public function testNumericalBuildingNumbers(): void
    {
        $postalAddress = new PostalAddress(1, 1, 'Test St', 'Unit A', '999', $this->testCity, '99999', 'State', 'Country');
        
        $this->assertSame('999', $postalAddress->getBuildingNumber());
    }

    public function testAlphanumericBuildingNumbers(): void
    {
        $postalAddress = new PostalAddress(1, 1, 'Test St', 'Unit B', '12A', $this->testCity, $this->oneToFive, 'State', 'Country');
        
        $this->assertSame('12A', $postalAddress->getBuildingNumber());
    }

    public function testInternationalPostalCodes(): void
    {
        // Canadian postal code
        $canadianAddress = new PostalAddress(1, 1, 'Maple Ave', '', '100', 'Toronto', 'M5V 3A8', 'ON', 'Canada');
        $this->assertSame('M5V 3A8', $canadianAddress->getPostalzone());

        // UK postal code
        $ukAddress = new PostalAddress(2, 2, 'King St', '', '10', 'London', 'SW1A 1AA', 'England', 'UK');
        $this->assertSame('SW1A 1AA', $ukAddress->getPostalzone());
    }
}
