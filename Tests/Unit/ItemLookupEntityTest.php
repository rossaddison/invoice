<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\ItemLookup;
use Codeception\Test\Unit;

final class ItemLookupEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $itemLookup = new ItemLookup();
        
        $this->assertSame('', $itemLookup->getId());
        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
        $this->assertNull($itemLookup->getPrice());
    }

    public function testConstructorWithAllParameters(): void
    {
        $itemLookup = new ItemLookup(1, 'Web Development', 'Custom website development service', 1500.50);
        
        $this->assertSame('1', $itemLookup->getId());
        $this->assertSame('Web Development', $itemLookup->getName());
        $this->assertSame('Custom website development service', $itemLookup->getDescription());
        $this->assertSame(1500.50, $itemLookup->getPrice());
    }

    public function testIdSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(42);
        
        $this->assertSame('42', $itemLookup->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setName('Consulting Service');
        
        $this->assertSame('Consulting Service', $itemLookup->getName());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setDescription('Professional business consulting services');
        
        $this->assertSame('Professional business consulting services', $itemLookup->getDescription());
    }

    public function testPriceSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(99.99);
        
        $this->assertSame(99.99, $itemLookup->getPrice());
    }

    public function testCommonServiceTypes(): void
    {
        $development = new ItemLookup(1, 'Software Development', 'Custom software development', 2000.00);
        $this->assertSame('Software Development', $development->getName());
        $this->assertSame(2000.00, $development->getPrice());

        $design = new ItemLookup(2, 'Graphic Design', 'Logo and branding design', 500.00);
        $this->assertSame('Graphic Design', $design->getName());
        $this->assertSame(500.00, $design->getPrice());

        $consulting = new ItemLookup(3, 'Business Consulting', 'Strategic business advice', 150.00);
        $this->assertSame('Business Consulting', $consulting->getName());
        $this->assertSame(150.00, $consulting->getPrice());
    }

    public function testLongItemNames(): void
    {
        $longName = 'Very Long Service Name That Could Potentially Exceed Normal Database Field Limits But Should Still Work';
        $itemLookup = new ItemLookup(1, $longName, 'Description', 100.00);
        
        $this->assertSame($longName, $itemLookup->getName());
    }

    public function testLongDescriptions(): void
    {
        $longDescription = str_repeat('This is a very detailed description of the service item. ', 10);
        $itemLookup = new ItemLookup(1, 'Service', $longDescription, 100.00);
        
        $this->assertSame($longDescription, $itemLookup->getDescription());
    }

    public function testSpecialCharactersInContent(): void
    {
        $itemLookup = new ItemLookup(1, 'Web Development & Design', 'HTML/CSS/JS development @ $75/hour', 75.50);
        
        $this->assertSame('Web Development & Design', $itemLookup->getName());
        $this->assertSame('HTML/CSS/JS development @ $75/hour', $itemLookup->getDescription());
    }

    public function testUnicodeInContent(): void
    {
        $itemLookup = new ItemLookup(1, 'Développement Web', 'Création de sites web professionnels 网站开发', 100.00);
        
        $this->assertSame('Développement Web', $itemLookup->getName());
        $this->assertSame('Création de sites web professionnels 网站开发', $itemLookup->getDescription());
    }

    public function testPricePrecision(): void
    {
        $itemLookup = new ItemLookup(1, 'Service', 'Description', 123.45);
        $this->assertSame(123.45, $itemLookup->getPrice());

        $itemLookup->setPrice(0.01);
        $this->assertSame(0.01, $itemLookup->getPrice());

        $itemLookup->setPrice(9999.99);
        $this->assertSame(9999.99, $itemLookup->getPrice());
    }

    public function testZeroPrice(): void
    {
        $itemLookup = new ItemLookup(1, 'Free Service', 'Complimentary service', 0.00);
        
        $this->assertSame(0.00, $itemLookup->getPrice());
    }

    public function testLargePrices(): void
    {
        $itemLookup = new ItemLookup(1, 'Enterprise Service', 'Large scale implementation', 50000.00);
        
        $this->assertSame(50000.00, $itemLookup->getPrice());
    }

    public function testChainedSetterCalls(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(100);
        $itemLookup->setName('Chained Service');
        $itemLookup->setDescription('Service set up with chained calls');
        $itemLookup->setPrice(299.99);
        
        $this->assertSame('100', $itemLookup->getId());
        $this->assertSame('Chained Service', $itemLookup->getName());
        $this->assertSame('Service set up with chained calls', $itemLookup->getDescription());
        $this->assertSame(299.99, $itemLookup->getPrice());
    }

    public function testIdStringConversion(): void
    {
        $itemLookup = new ItemLookup(123, 'Test Service', 'Test Description', 100.00);
        
        // Verify ID getter returns string even though setter accepts int
        $this->assertIsString($itemLookup->getId());
        $this->assertSame('123', $itemLookup->getId());
    }

    public function testZeroAndLargeIds(): void
    {
        $zeroId = new ItemLookup(0, 'Zero ID Service', 'Service with zero ID', 50.00);
        $this->assertSame('0', $zeroId->getId());

        $largeId = new ItemLookup(999999, 'Large ID Service', 'Service with large ID', 75.00);
        $this->assertSame('999999', $largeId->getId());
    }

    public function testEmptyNameAndDescription(): void
    {
        $itemLookup = new ItemLookup(1, '', '', 100.00);
        
        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
    }

    public function testPricePropertyTypes(): void
    {
        $itemLookup = new ItemLookup(1, 'Test', 'Test', 100.50);
        
        // Verify price property returns correct type
        $this->assertIsFloat($itemLookup->getPrice());
        $this->assertSame(100.50, $itemLookup->getPrice());
    }

    public function testCompleteItemLookupSetup(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(999);
        $itemLookup->setName('Complete Setup Service');
        $itemLookup->setDescription('This is a complete item lookup setup with all properties configured properly.');
        $itemLookup->setPrice(999.99);
        
        $this->assertSame('999', $itemLookup->getId());
        $this->assertSame('Complete Setup Service', $itemLookup->getName());
        $this->assertSame('This is a complete item lookup setup with all properties configured properly.', $itemLookup->getDescription());
        $this->assertSame(999.99, $itemLookup->getPrice());
    }

    public function testVariousPriceFormats(): void
    {
        // Integer-like float
        $itemLookup1 = new ItemLookup(1, 'Service 1', 'Description', 100.00);
        $this->assertSame(100.00, $itemLookup1->getPrice());

        // Single decimal
        $itemLookup2 = new ItemLookup(2, 'Service 2', 'Description', 99.9);
        $this->assertSame(99.9, $itemLookup2->getPrice());

        // Two decimals
        $itemLookup3 = new ItemLookup(3, 'Service 3', 'Description', 123.45);
        $this->assertSame(123.45, $itemLookup3->getPrice());
    }

    public function testServiceCategories(): void
    {
        // Professional Services
        $legal = new ItemLookup(1, 'Legal Consultation', 'Legal advice and document review', 250.00);
        $this->assertSame('Legal Consultation', $legal->getName());

        // Technical Services  
        $tech = new ItemLookup(2, 'System Administration', 'Server setup and maintenance', 100.00);
        $this->assertSame('System Administration', $tech->getName());

        // Creative Services
        $creative = new ItemLookup(3, 'Video Production', 'Professional video editing and production', 500.00);
        $this->assertSame('Video Production', $creative->getName());
    }

    public function testReturnTypeConsistency(): void
    {
        $itemLookup = new ItemLookup(1, 'Test Service', 'Test Description', 100.00);
        
        $this->assertIsString($itemLookup->getId());
        $this->assertIsString($itemLookup->getName());
        $this->assertIsString($itemLookup->getDescription());
        $this->assertTrue(is_float($itemLookup->getPrice()) || is_null($itemLookup->getPrice()));
    }
}