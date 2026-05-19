<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\UserInv\UserInv;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserInvEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $ui = new UserInv();
        $this->assertFalse($ui->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $ui = new UserInv();
        $this->expectException(\LogicException::class);
        $ui->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $ui = new UserInv();
        $ui->setId(1);
        $this->assertTrue($ui->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $ui = new UserInv();
        $ui->setId(4);
        $this->assertSame(4, $ui->reqId());
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $ui = new UserInv();
        $this->assertInstanceOf(DateTimeImmutable::class, $ui->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $ui->getDateModified());
    }

    public function testConstructorDefaults(): void
    {
        $ui = new UserInv();
        $this->assertFalse($ui->getActive());
        $this->assertSame('', $ui->getLanguage());
        $this->assertSame('', $ui->getName());
        $this->assertSame('', $ui->getCity());
        $this->assertSame('', $ui->getCountry());
        $this->assertSame('', $ui->getMobile());
        $this->assertNull($ui->getUser());
    }

    public function testSetAndGetName(): void
    {
        $ui = new UserInv();
        $ui->setName('Jane Smith');
        $this->assertSame('Jane Smith', $ui->getName());
    }

    public function testSetAndGetActive(): void
    {
        $ui = new UserInv();
        $ui->setActive(true);
        $this->assertTrue($ui->getActive());
    }

    public function testSetAndGetLanguage(): void
    {
        $ui = new UserInv();
        $ui->setLanguage('en');
        $this->assertSame('en', $ui->getLanguage());
    }

    public function testSetAndGetType(): void
    {
        $ui = new UserInv();
        $ui->setType(0);
        $this->assertSame(0, $ui->getType());
    }

    public function testSetAndGetIban(): void
    {
        $ui = new UserInv();
        $ui->setIban('GB82WEST12345698765432');
        $this->assertSame('GB82WEST12345698765432', $ui->getIban());
    }

    public function testSetAndGetGln(): void
    {
        $ui = new UserInv();
        $ui->setGln(1234567890123);
        $this->assertSame(1234567890123, $ui->getGln());
    }

    public function testReqUserIdThrowsWhenNull(): void
    {
        $ui = new UserInv();
        $this->expectException(\LogicException::class);
        $ui->reqUserId();
    }

    public function testSetAndReqUserId(): void
    {
        $ui = new UserInv();
        $ui->setUserId(2);
        $this->assertSame(2, $ui->reqUserId());
    }
}
