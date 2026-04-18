<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Persistence\Group\Group;
use Codeception\Test\Unit;

final class GroupEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $group = new Group();
        
        $this->assertSame('', $group->getId());
        $this->assertSame('', $group->getName());
        $this->assertSame('', $group->getIdentifierFormat());
        $this->assertSame('', $group->getNextId());
        $this->assertNull($group->getLeftPad());
    }

    public function testConstructorWithAllParameters(): void
    {
        $group = new Group(1, 'Test Group', 'TG-{id}', 100, 3);
        
        $this->assertSame('1', $group->getId());
        $this->assertSame('Test Group', $group->getName());
        $this->assertSame('TG-{id}', $group->getIdentifierFormat());
        $this->assertSame('100', $group->getNextId());
        $this->assertSame(3, $group->getLeftPad());
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
        $group->setIdentifierFormat('INV-{year}-{id}');
        
        $this->assertSame('INV-{year}-{id}', $group->getIdentifierFormat());
    }

    public function testNextIdSetterAndGetter(): void
    {
        $group = new Group();
        $group->setNextId(1000);
        
        $this->assertSame('1000', $group->getNextId());
    }

    public function testLeftPadSetterAndGetter(): void
    {
        $group = new Group();
        $group->setLeftPad(5);
        
        $this->assertSame(5, $group->getLeftPad());
    }

    public function testCommonGroupTypes(): void
    {
        $invoiceGroup = new Group(1, 'Invoices', 'INV-{id}', 1001, 4);
        $this->assertSame('Invoices', $invoiceGroup->getName());
        $this->assertSame('INV-{id}', $invoiceGroup->getIdentifierFormat());
        $this->assertSame('1001', $invoiceGroup->getNextId());
        $this->assertSame(4, $invoiceGroup->getLeftPad());

        $quoteGroup = new Group(2, 'Quotes', 'QT-{id}', 2001, 3);
        $this->assertSame('Quotes', $quoteGroup->getName());
        $this->assertSame('QT-{id}', $quoteGroup->getIdentifierFormat());
        $this->assertSame('2001', $quoteGroup->getNextId());
        $this->assertSame(3, $quoteGroup->getLeftPad());
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
        
        $this->assertSame($complexFormat, $group->getIdentifierFormat());
    }

    public function testZeroValues(): void
    {
        $group = new Group(0, 'Zero Test', 'ZERO-{id}', 0, 0);
        
        $this->assertSame('0', $group->getId());
        $this->assertSame('0', $group->getNextId());
        $this->assertSame(0, $group->getLeftPad());
    }

    public function testLargeValues(): void
    {
        $group = new Group(999999, 'Large Values', 'LARGE-{id}', 888888, 10);
        
        $this->assertSame('999999', $group->getId());
        $this->assertSame('888888', $group->getNextId());
        $this->assertSame(10, $group->getLeftPad());
    }

    public function testChainedSetterCalls(): void
    {
        $group = new Group();
        $group->setId(100);
        $group->setName('Chained Group');
        $group->setIdentifierFormat('CH-{id}');
        $group->setNextId(500);
        $group->setLeftPad(4);
        
        $this->assertSame('100', $group->getId());
        $this->assertSame('Chained Group', $group->getName());
        $this->assertSame('CH-{id}', $group->getIdentifierFormat());
        $this->assertSame('500', $group->getNextId());
        $this->assertSame(4, $group->getLeftPad());
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
        $this->assertIsString($group->getNextId());
        $this->assertSame('123', $group->getId());
        $this->assertSame('456', $group->getNextId());
    }

    public function testCompleteGroupSetup(): void
    {
        $group = new Group();
        $group->setId(999);
        $group->setName('Complete Setup Group');
        $group->setIdentifierFormat('CSG-{year}-{id}');
        $group->setNextId(5000);
        $group->setLeftPad(6);
        
        $this->assertSame('999', $group->getId());
        $this->assertSame('Complete Setup Group', $group->getName());
        $this->assertSame('CSG-{year}-{id}', $group->getIdentifierFormat());
        $this->assertSame('5000', $group->getNextId());
        $this->assertSame(6, $group->getLeftPad());
    }
}
