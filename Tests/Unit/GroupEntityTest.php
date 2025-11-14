<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\Group;
use Codeception\Test\Unit;

final class GroupEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $group = new Group();
        
        $this->assertSame('', $group->getId());
        $this->assertSame('', $group->getName());
        $this->assertSame('', $group->getIdentifier_format());
        $this->assertSame('', $group->getNext_id());
        $this->assertNull($group->getLeft_pad());
    }

    public function testConstructorWithAllParameters(): void
    {
        $group = new Group(1, 'Test Group', 'TG-{id}', 100, 3);
        
        $this->assertSame('1', $group->getId());
        $this->assertSame('Test Group', $group->getName());
        $this->assertSame('TG-{id}', $group->getIdentifier_format());
        $this->assertSame('100', $group->getNext_id());
        $this->assertSame(3, $group->getLeft_pad());
    }

    public function testIdSetterAndGetter(): void
    {
        $group = new Group();
        $group->setId(42);
        
        $this->assertSame('42', $group->getId());
    }

    public function testNameSetterAndGetter(): void
    {
        $group = new Group();
        $group->setName('Invoice Group');
        
        $this->assertSame('Invoice Group', $group->getName());
    }

    public function testIdentifierFormatSetterAndGetter(): void
    {
        $group = new Group();
        $group->setIdentifier_format('INV-{year}-{id}');
        
        $this->assertSame('INV-{year}-{id}', $group->getIdentifier_format());
    }

    public function testNextIdSetterAndGetter(): void
    {
        $group = new Group();
        $group->setNext_id(1000);
        
        $this->assertSame('1000', $group->getNext_id());
    }

    public function testLeftPadSetterAndGetter(): void
    {
        $group = new Group();
        $group->setLeft_pad(5);
        
        $this->assertSame(5, $group->getLeft_pad());
    }

    public function testCommonGroupTypes(): void
    {
        $invoiceGroup = new Group(1, 'Invoices', 'INV-{id}', 1001, 4);
        $this->assertSame('Invoices', $invoiceGroup->getName());
        $this->assertSame('INV-{id}', $invoiceGroup->getIdentifier_format());
        $this->assertSame('1001', $invoiceGroup->getNext_id());
        $this->assertSame(4, $invoiceGroup->getLeft_pad());

        $quoteGroup = new Group(2, 'Quotes', 'QT-{id}', 2001, 3);
        $this->assertSame('Quotes', $quoteGroup->getName());
        $this->assertSame('QT-{id}', $quoteGroup->getIdentifier_format());
        $this->assertSame('2001', $quoteGroup->getNext_id());
        $this->assertSame(3, $quoteGroup->getLeft_pad());
    }

    public function testLongGroupNames(): void
    {
        $longName = 'Very Long Group Name That Could Potentially Exceed Normal Database Limits';
        $group = new Group(1, $longName, 'LONG-{id}', 1, 0);
        
        $this->assertSame($longName, $group->getName());
    }

    public function testComplexIdentifierFormats(): void
    {
        $complexFormat = '{prefix}-{year}-{month}-{id}';
        $group = new Group(1, 'Complex', $complexFormat, 1, 6);
        
        $this->assertSame($complexFormat, $group->getIdentifier_format());
    }

    public function testZeroValues(): void
    {
        $group = new Group(0, 'Zero Test', 'ZERO-{id}', 0, 0);
        
        $this->assertSame('0', $group->getId());
        $this->assertSame('0', $group->getNext_id());
        $this->assertSame(0, $group->getLeft_pad());
    }

    public function testLargeValues(): void
    {
        $group = new Group(999999, 'Large Values', 'LARGE-{id}', 888888, 10);
        
        $this->assertSame('999999', $group->getId());
        $this->assertSame('888888', $group->getNext_id());
        $this->assertSame(10, $group->getLeft_pad());
    }

    public function testChainedSetterCalls(): void
    {
        $group = new Group();
        $group->setId(100);
        $group->setName('Chained Group');
        $group->setIdentifier_format('CH-{id}');
        $group->setNext_id(500);
        $group->setLeft_pad(4);
        
        $this->assertSame('100', $group->getId());
        $this->assertSame('Chained Group', $group->getName());
        $this->assertSame('CH-{id}', $group->getIdentifier_format());
        $this->assertSame('500', $group->getNext_id());
        $this->assertSame(4, $group->getLeft_pad());
    }

    public function testNullNameHandling(): void
    {
        $group = new Group(1, null, 'NULL-{id}', 1, 1);
        
        $this->assertNull($group->getName());
    }

    public function testSpecialCharactersInName(): void
    {
        $group = new Group(1, 'Group & Associates', 'GA-{id}', 1, 1);
        
        $this->assertSame('Group & Associates', $group->getName());
    }

    public function testUnicodeInName(): void
    {
        $group = new Group(1, 'Grôup Tëst 测试', 'UNI-{id}', 1, 1);
        
        $this->assertSame('Grôup Tëst 测试', $group->getName());
    }

    public function testIdStringConversion(): void
    {
        $group = new Group(123, 'Test', 'T-{id}', 456, 1);
        
        // Verify ID getter returns string even though setter accepts int
        $this->assertIsString($group->getId());
        $this->assertIsString($group->getNext_id());
        $this->assertSame('123', $group->getId());
        $this->assertSame('456', $group->getNext_id());
    }

    public function testCompleteGroupSetup(): void
    {
        $group = new Group();
        $group->setId(999);
        $group->setName('Complete Setup Group');
        $group->setIdentifier_format('CSG-{year}-{id}');
        $group->setNext_id(5000);
        $group->setLeft_pad(6);
        
        $this->assertSame('999', $group->getId());
        $this->assertSame('Complete Setup Group', $group->getName());
        $this->assertSame('CSG-{year}-{id}', $group->getIdentifier_format());
        $this->assertSame('5000', $group->getNext_id());
        $this->assertSame(6, $group->getLeft_pad());
    }
}
