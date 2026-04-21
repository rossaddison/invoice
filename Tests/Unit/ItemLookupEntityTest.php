<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Persistence\ItemLookup\ItemLookup;
use Codeception\Test\Unit;

final class ItemLookupEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $itemLookup = new ItemLookup();

        $this->assertFalse($itemLookup->isPersisted());
        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
        $this->assertNull($itemLookup->getPrice());
    }

    public function testConstructorWithAllParameters(): void
    {
        $itemLookup = new ItemLookup(
            name: 'Web Development',
            description: 'Custom website development service',
            price: 1500.50
        );
        $itemLookup->setId(1);

        $this->assertSame(1, $itemLookup->reqId());
        $this->assertSame('Web Development', $itemLookup->getName());
        $this->assertSame('Custom website development service',
                $itemLookup->getDescription());
        $this->assertSame(1500.50, $itemLookup->getPrice());
    }

    public function testIdSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(42);

        $this->assertSame(42, $itemLookup->reqId());
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

        $this->assertSame('Professional business consulting services',
                $itemLookup->getDescription());
    }

    public function testPriceSetterAndGetter(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setPrice(99.99);

        $this->assertSame(99.99, $itemLookup->getPrice());
    }

    public function testCommonServiceTypes(): void
    {
        $development = new ItemLookup(name: 'Software Development',
                description: 'Custom software development', price: 2000.00);
        $development->setId(1);
        $this->assertSame('Software Development', $development->getName());
        $this->assertSame(2000.00, $development->getPrice());

        $design = new ItemLookup(name: 'Graphic Design',
                description: 'Logo and branding design', price: 500.00);
        $design->setId(2);
        $this->assertSame('Graphic Design', $design->getName());
        $this->assertSame(500.00, $design->getPrice());

        $consulting = new ItemLookup(name: 'Business Consulting',
                description: 'Strategic business advice', price: 150.00);
        $consulting->setId(3);
        $this->assertSame('Business Consulting', $consulting->getName());
        $this->assertSame(150.00, $consulting->getPrice());
    }

    public function testLongItemNames(): void
    {
        $longName = 'Very Long Service Name That Could Potentially Exceed Normal'
                . ' Database Field Limits But Should Still Work';
        $itemLookup = new ItemLookup(name: $longName, description: 'Description', price: 100.00);
        $itemLookup->setId(1);

        $this->assertSame($longName, $itemLookup->getName());
    }

    public function testLongDescriptions(): void
    {
        $longDescription = str_repeat('This is a very detailed description of'
                . ' the service item. ', 10);
        $itemLookup = new ItemLookup(name: 'Service', description: $longDescription, price: 100.00);
        $itemLookup->setId(1);

        $this->assertSame($longDescription, $itemLookup->getDescription());
    }

    public function testSpecialCharactersInContent(): void
    {
        $itemLookup = new ItemLookup(name: 'Web Development & Design',
                description: 'HTML/CSS/JS development @ $75/hour', price: 75.50);
        $itemLookup->setId(1);

        $this->assertSame('Web Development & Design', $itemLookup->getName());
        $this->assertSame('HTML/CSS/JS development @ $75/hour',
                $itemLookup->getDescription());
    }

    public function testUnicodeInContent(): void
    {
        $itemLookup = new ItemLookup(name: 'Développement Web',
                description: 'Création de sites web professionnels 网站开发', price: 100.00);
        $itemLookup->setId(1);

        $this->assertSame('Développement Web', $itemLookup->getName());
        $this->assertSame('Création de sites web professionnels 网站开发',
                $itemLookup->getDescription());
    }

    public function testPricePrecision(): void
    {
        $itemLookup = new ItemLookup(name: 'Service', description: 'Description', price: 123.45);
        $itemLookup->setId(1);
        $this->assertSame(123.45, $itemLookup->getPrice());

        $itemLookup->setPrice(0.01);
        $this->assertSame(0.01, $itemLookup->getPrice());

        $itemLookup->setPrice(9999.99);
        $this->assertSame(9999.99, $itemLookup->getPrice());
    }

    public function testZeroPrice(): void
    {
        $itemLookup = new ItemLookup(name: 'Free Service',
                description: 'Complimentary service', price: 0.00);
        $itemLookup->setId(1);

        $this->assertSame(0.00, $itemLookup->getPrice());
    }

    public function testLargePrices(): void
    {
        $itemLookup = new ItemLookup(name: 'Enterprise Service',
                description: 'Large scale implementation', price: 50000.00);
        $itemLookup->setId(1);

        $this->assertSame(50000.00, $itemLookup->getPrice());
    }

    public function testChainedSetterCalls(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(100);
        $itemLookup->setName('Chained Service');
        $itemLookup->setDescription('Service set up with chained calls');
        $itemLookup->setPrice(299.99);

        $this->assertSame(100, $itemLookup->reqId());
        $this->assertSame('Chained Service', $itemLookup->getName());
        $this->assertSame('Service set up with chained calls', $itemLookup->getDescription());
        $this->assertSame(299.99, $itemLookup->getPrice());
    }

    public function testIdIntType(): void
    {
        $itemLookup = new ItemLookup(name: 'Test Service', description: 'Test Description', price: 100.00);
        $itemLookup->setId(123);

        $this->assertIsInt($itemLookup->reqId());
        $this->assertSame(123, $itemLookup->reqId());
    }

    public function testZeroAndLargeIds(): void
    {
        $zeroId = new ItemLookup(name: 'Zero ID Service',
                description: 'Service with zero ID', price: 50.00);
        $zeroId->setId(0);
        $this->assertSame(0, $zeroId->reqId());

        $largeId = new ItemLookup(name: 'Large ID Service',
                description: 'Service with large ID', price: 75.00);
        $largeId->setId(999999);
        $this->assertSame(999999, $largeId->reqId());
    }

    public function testEmptyNameAndDescription(): void
    {
        $itemLookup = new ItemLookup(name: '', description: '', price: 100.00);
        $itemLookup->setId(1);

        $this->assertSame('', $itemLookup->getName());
        $this->assertSame('', $itemLookup->getDescription());
    }

    public function testPricePropertyTypes(): void
    {
        $itemLookup = new ItemLookup(name: 'Test', description: 'Test', price: 100.50);
        $itemLookup->setId(1);

        $this->assertIsFloat($itemLookup->getPrice());
        $this->assertSame(100.50, $itemLookup->getPrice());
    }

    public function testCompleteItemLookupSetup(): void
    {
        $itemLookup = new ItemLookup();
        $itemLookup->setId(999);
        $itemLookup->setName('Complete Setup Service');
        $itemLookup->setDescription('This is a complete item lookup setup with'
                . ' all properties configured properly.');
        $itemLookup->setPrice(999.99);

        $this->assertSame(999, $itemLookup->reqId());
        $this->assertSame('Complete Setup Service', $itemLookup->getName());
        $this->assertSame('This is a complete item lookup setup with all'
                . ' properties configured properly.', $itemLookup->getDescription());
        $this->assertSame(999.99, $itemLookup->getPrice());
    }

    public function testVariousPriceFormats(): void
    {
        $itemLookup1 = new ItemLookup(name: 'Service 1', description: 'Description', price: 100.00);
        $itemLookup1->setId(1);
        $this->assertSame(100.00, $itemLookup1->getPrice());

        $itemLookup2 = new ItemLookup(name: 'Service 2', description: 'Description', price: 99.9);
        $itemLookup2->setId(2);
        $this->assertSame(99.9, $itemLookup2->getPrice());

        $itemLookup3 = new ItemLookup(name: 'Service 3', description: 'Description', price: 123.45);
        $itemLookup3->setId(3);
        $this->assertSame(123.45, $itemLookup3->getPrice());
    }

    public function testServiceCategories(): void
    {
        $legal = new ItemLookup(name: 'Legal Consultation',
                description: 'Legal advice and document review', price: 250.00);
        $legal->setId(1);
        $this->assertSame('Legal Consultation', $legal->getName());

        $tech = new ItemLookup(name: 'System Administration',
                description: 'Server setup and maintenance', price: 100.00);
        $tech->setId(2);
        $this->assertSame('System Administration', $tech->getName());

        $creative = new ItemLookup(name: 'Video Production',
                description: 'Professional video editing and production', price: 500.00);
        $creative->setId(3);
        $this->assertSame('Video Production', $creative->getName());
    }

    public function testReturnTypeConsistency(): void
    {
        $itemLookup = new ItemLookup(name: 'Test Service', description: 'Test Description', price: 100.00);
        $itemLookup->setId(1);

        $this->assertIsInt($itemLookup->reqId());
        $this->assertIsString($itemLookup->getName());
        $this->assertIsString($itemLookup->getDescription());
        $this->assertTrue(is_float($itemLookup->getPrice()) || is_null($itemLookup->getPrice()));
    }
}
