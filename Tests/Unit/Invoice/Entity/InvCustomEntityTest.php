<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvCustom\InvCustom;
use PHPUnit\Framework\TestCase;

class InvCustomEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $ic = new InvCustom();
        $this->assertFalse($ic->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $ic = new InvCustom();
        $this->expectException(\LogicException::class);
        $ic->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $ic = new InvCustom();
        $ic->setId(1);
        $this->assertTrue($ic->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $ic = new InvCustom();
        $ic->setId(5);
        $this->assertSame(5, $ic->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $ic = new InvCustom();
        $this->assertSame('', $ic->getValue());
        $this->assertNull($ic->getInv());
        $this->assertNull($ic->getCustomField());
    }

    public function testSetAndGetValue(): void
    {
        $ic = new InvCustom();
        $ic->setValue('PO-12345');
        $this->assertSame('PO-12345', $ic->getValue());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $ic = new InvCustom();
        $this->expectException(\LogicException::class);
        $ic->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $ic = new InvCustom();
        $ic->setInvId(3);
        $this->assertSame(3, $ic->reqInvId());
    }

    public function testReqCustomFieldIdThrowsWhenNull(): void
    {
        $ic = new InvCustom();
        $this->expectException(\LogicException::class);
        $ic->reqCustomFieldId();
    }

    public function testSetAndReqCustomFieldId(): void
    {
        $ic = new InvCustom();
        $ic->setCustomFieldId(7);
        $this->assertSame(7, $ic->reqCustomFieldId());
    }
}
