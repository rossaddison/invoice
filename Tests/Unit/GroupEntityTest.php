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

        $this->assertFalse($group->isPersisted());
        $this->assertSame('', $group->getName());
        $this->assertSame('', $group->getIdentifierFormat());
        $this->assertNull($group->getNextId());
        $this->assertNull($group->getLeftPad());
    }

    public function testConstructorWithAllParameters(): void
    {
        $group = new Group('Test Group', 'TG-{id}', 100, 3);
        $group->setId(1);

        $this->assertSame(1, $group->reqId());
        $this->assertSame('Test Group', $group->getName());
        $this->assertSame('TG-{id}', $group->getIdentifierFormat());
        $this->assertSame(100, $group->getNextId());
        $this->assertSame(3, $group->getLeftPad());
    }

    public function testIdSetterAndGetter(): void
    {
        $group = new Group();
        $group->setId(42);

        $this->assertSame(42, $group->reqId());
    }

    public function testIsPersistedReturnsFalseBeforeSetId(): void
    {
        $group = new Group();

        $this->assertFalse($group->isPersisted());
    }

    public function testIsPersistedReturnsTrueAfterSetId(): void
    {
        $group = new Group();
        $group->setId(1);

        $this->assertTrue($group->isPersisted());
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

        $this->assertSame(1000, $group->getNextId());
    }

    public function testLeftPadSetterAndGetter(): void
    {
        $group = new Group();
        $group->setLeftPad(5);

        $this->assertSame(5, $group->getLeftPad());
    }

    public function testCommonGroupTypes(): void
    {
        $invoiceGroup = new Group('Invoices', 'INV-{id}', 1001, 4);
        $this->assertSame('Invoices', $invoiceGroup->getName());
        $this->assertSame('INV-{id}', $invoiceGroup->getIdentifierFormat());
        $this->assertSame(1001, $invoiceGroup->getNextId());
        $this->assertSame(4, $invoiceGroup->getLeftPad());

        $quoteGroup = new Group('Quotes', 'QT-{id}', 2001, 3);
        $this->assertSame('Quotes', $quoteGroup->getName());
        $this->assertSame('QT-{id}', $quoteGroup->getIdentifierFormat());
        $this->assertSame(2001, $quoteGroup->getNextId());
        $this->assertSame(3, $quoteGroup->getLeftPad());
    }

    public function testLongGroupNames(): void
    {
        $longName = 'Very Long Group Name That Could Potentially Exceed Normal Database Limits';
        $group = new Group($longName, 'LONG-{id}', 1, 0);

        $this->assertSame($longName, $group->getName());
    }

    public function testComplexIdentifierFormats(): void
    {
        $complexFormat = '{prefix}-{year}-{month}-{id}';
        $group = new Group('Complex', $complexFormat, 1, 6);

        $this->assertSame($complexFormat, $group->getIdentifierFormat());
    }

    public function testZeroValues(): void
    {
        $group = new Group('Zero Test', 'ZERO-{id}', 0, 0);
        $group->setId(0);

        $this->assertSame(0, $group->reqId());
        $this->assertSame(0, $group->getNextId());
        $this->assertSame(0, $group->getLeftPad());
    }

    public function testLargeValues(): void
    {
        $group = new Group('Large Values', 'LARGE-{id}', 888888, 10);
        $group->setId(999999);

        $this->assertSame(999999, $group->reqId());
        $this->assertSame(888888, $group->getNextId());
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

        $this->assertSame(100, $group->reqId());
        $this->assertSame('Chained Group', $group->getName());
        $this->assertSame('CH-{id}', $group->getIdentifierFormat());
        $this->assertSame(500, $group->getNextId());
        $this->assertSame(4, $group->getLeftPad());
    }

    public function testNullNameHandling(): void
    {
        $group = new Group(null, 'NULL-{id}', 1, 1);

        $this->assertNull($group->getName());
    }

    public function testSpecialCharactersInName(): void
    {
        $group = new Group('Group & Associates', 'GA-{id}', 1, 1);

        $this->assertSame('Group & Associates', $group->getName());
    }

    public function testUnicodeInName(): void
    {
        $group = new Group('Grôup Tëst 测试', 'UNI-{id}', 1, 1);

        $this->assertSame('Grôup Tëst 测试', $group->getName());
    }

    public function testIdTypeIsInt(): void
    {
        $group = new Group('Test', 'T-{id}', 456, 1);
        $group->setId(123);

        $this->assertIsInt($group->reqId());
        $this->assertIsInt($group->getNextId());
        $this->assertSame(123, $group->reqId());
        $this->assertSame(456, $group->getNextId());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $group = new Group();

        $this->expectException(\LogicException::class);
        $group->reqId();
    }

    public function testCompleteGroupSetup(): void
    {
        $group = new Group();
        $group->setId(999);
        $group->setName('Complete Setup Group');
        $group->setIdentifierFormat('CSG-{year}-{id}');
        $group->setNextId(5000);
        $group->setLeftPad(6);

        $this->assertSame(999, $group->reqId());
        $this->assertSame('Complete Setup Group', $group->getName());
        $this->assertSame('CSG-{year}-{id}', $group->getIdentifierFormat());
        $this->assertSame(5000, $group->getNextId());
        $this->assertSame(6, $group->getLeftPad());
    }
}
