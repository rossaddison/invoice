<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class InvSentLogEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $log = new InvSentLog();
        $this->assertFalse($log->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $log = new InvSentLog();
        $this->expectException(\LogicException::class);
        $log->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $log = new InvSentLog();
        $log->setId(1);
        $this->assertTrue($log->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $log = new InvSentLog();
        $log->setId(7);
        $this->assertSame(7, $log->reqId());
    }

    public function testConstructorInitialisesDateSent(): void
    {
        $log = new InvSentLog();
        $this->assertInstanceOf(DateTimeImmutable::class, $log->getDateSent());
    }

    public function testSetAndGetDateSent(): void
    {
        $log = new InvSentLog();
        $date = new DateTimeImmutable('2026-05-19 10:00:00');
        $log->setDateSent($date);
        $this->assertSame($date, $log->getDateSent());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $log = new InvSentLog();
        $this->expectException(\LogicException::class);
        $log->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $log = new InvSentLog();
        $log->setInvId(2);
        $this->assertSame(2, $log->reqInvId());
    }

    public function testReqClientIdThrowsWhenNull(): void
    {
        $log = new InvSentLog();
        $this->expectException(\LogicException::class);
        $log->reqClientId();
    }

    public function testSetAndReqClientId(): void
    {
        $log = new InvSentLog();
        $log->setClientId(9);
        $this->assertSame(9, $log->reqClientId());
    }
}
