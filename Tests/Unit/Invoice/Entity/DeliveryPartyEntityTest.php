<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\DeliveryParty\DeliveryParty;
use PHPUnit\Framework\TestCase;

class DeliveryPartyEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $dp = new DeliveryParty();
        $this->assertFalse($dp->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $dp = new DeliveryParty();
        $this->expectException(\LogicException::class);
        $dp->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $dp = new DeliveryParty();
        $dp->setId(7);
        $this->assertTrue($dp->hasIdentity());
        $this->assertSame(7, $dp->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $dp = new DeliveryParty();
        $dp->setId(1);
        $this->assertIsInt($dp->reqId());
    }

    public function testPartyNameIsEmptyStringByDefault(): void
    {
        $dp = new DeliveryParty();
        $this->assertSame('', $dp->getPartyName());
    }

    public function testPartyNameSetterAndGetter(): void
    {
        $dp = new DeliveryParty();
        $dp->setPartyName('Acme Corp');
        $this->assertSame('Acme Corp', $dp->getPartyName());
    }

    public function testConstructorWithPartyName(): void
    {
        $dp = new DeliveryParty(party_name: 'Widgets Ltd');
        $this->assertSame('Widgets Ltd', $dp->getPartyName());
    }
}
