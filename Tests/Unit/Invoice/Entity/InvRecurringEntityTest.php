<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use PHPUnit\Framework\TestCase;

class InvRecurringEntityTest extends TestCase
{
    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $r = new InvRecurring();
        $this->expectException(\LogicException::class);
        $r->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $r = new InvRecurring();
        $r->setId(5);
        $this->assertSame(5, $r->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $r = new InvRecurring();
        $this->assertSame('', $r->getFrequency());
        $this->assertNull($r->getInv());
    }

    public function testSetAndGetInvId(): void
    {
        $r = new InvRecurring();
        $r->setInvId(10);
        $r->setId(1);
        $this->assertSame(1, $r->reqId());
    }

    public function testSetAndGetFrequency(): void
    {
        $r = new InvRecurring();
        $r->setFrequency('monthly');
        $this->assertSame('monthly', $r->getFrequency());
    }

    public function testSetAndGetStart(): void
    {
        $r = new InvRecurring();
        $r->setStart('2026-01-01');
        $this->assertSame('2026-01-01', $r->getStart());
    }

    public function testEndDefaultsToEmptyString(): void
    {
        $r = new InvRecurring();
        $this->assertSame('', $r->getEnd());
    }

    public function testSetEndNullReturnsNull(): void
    {
        $r = new InvRecurring();
        $r->setEnd(null);
        $this->assertNull($r->getEnd());
    }

    public function testNextDefaultsToEmptyString(): void
    {
        $r = new InvRecurring();
        $this->assertSame('', $r->getNext());
    }

    public function testSetNextNullReturnsNull(): void
    {
        $r = new InvRecurring();
        $r->setNext(null);
        $this->assertNull($r->getNext());
    }
}
