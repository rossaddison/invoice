<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Unit\Unit;
use PHPUnit\Framework\TestCase;

class UnitEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $u = new Unit();
        $this->assertFalse($u->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $u = new Unit();
        $this->expectException(\LogicException::class);
        $u->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $u = new Unit();
        $u->setId(1);
        $this->assertTrue($u->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $u = new Unit();
        $u->setId(3);
        $this->assertSame(3, $u->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $u = new Unit();
        $this->assertSame('', $u->getUnitName());
        $this->assertSame('', $u->getUnitNamePlrl());
    }

    public function testSetAndGetUnitName(): void
    {
        $u = new Unit();
        $u->setUnitName('hour');
        $this->assertSame('hour', $u->getUnitName());
    }

    public function testSetAndGetUnitNamePlrl(): void
    {
        $u = new Unit();
        $u->setUnitNamePlrl('hours');
        $this->assertSame('hours', $u->getUnitNamePlrl());
    }

    public function testConstructorWithValues(): void
    {
        $u = new Unit('kilogram', 'kilograms');
        $this->assertSame('kilogram', $u->getUnitName());
        $this->assertSame('kilograms', $u->getUnitNamePlrl());
    }
}
