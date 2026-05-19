<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Profile\Profile;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProfileEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $p = new Profile();
        $this->assertFalse($p->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $p = new Profile();
        $this->expectException(\LogicException::class);
        $p->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $p = new Profile();
        $p->setId(1);
        $this->assertTrue($p->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $p = new Profile();
        $p->setId(6);
        $this->assertSame(6, $p->reqId());
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $p = new Profile();
        $this->assertInstanceOf(DateTimeImmutable::class, $p->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $p->getDateModified());
    }

    public function testConstructorDefaults(): void
    {
        $p = new Profile();
        $this->assertSame(0, $p->getCurrent());
        $this->assertSame('', $p->getMobile());
        $this->assertSame('', $p->getEmail());
        $this->assertSame('', $p->getDescription());
        $this->assertNull($p->getCompany());
    }

    public function testSetAndGetMobile(): void
    {
        $p = new Profile();
        $p->setMobile('+44 7700 900000');
        $this->assertSame('+44 7700 900000', $p->getMobile());
    }

    public function testSetAndGetEmail(): void
    {
        $p = new Profile();
        $p->setEmail('accounts@example.com');
        $this->assertSame('accounts@example.com', $p->getEmail());
    }

    public function testSetAndGetDescription(): void
    {
        $p = new Profile();
        $p->setDescription('Primary billing profile');
        $this->assertSame('Primary billing profile', $p->getDescription());
    }

    public function testSetAndGetCurrent(): void
    {
        $p = new Profile();
        $p->setCurrent(1);
        $this->assertSame(1, $p->getCurrent());
    }

    public function testReqCompanyIdThrowsWhenNull(): void
    {
        $p = new Profile();
        $this->expectException(\LogicException::class);
        $p->reqCompanyId();
    }

    public function testSetAndReqCompanyId(): void
    {
        $p = new Profile();
        $p->setCompanyId(2);
        $this->assertSame(2, $p->reqCompanyId());
    }
}
