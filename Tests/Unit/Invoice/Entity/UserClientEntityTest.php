<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\UserClient\UserClient;
use PHPUnit\Framework\TestCase;

class UserClientEntityTest extends TestCase
{
    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $uc = new UserClient();
        $this->expectException(\LogicException::class);
        $uc->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $uc = new UserClient();
        $uc->setId(6);
        $this->assertSame(6, $uc->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $uc = new UserClient();
        $this->assertNull($uc->getUser());
        $this->assertNull($uc->getClient());
    }

    public function testReqUserIdThrowsWhenNull(): void
    {
        $uc = new UserClient();
        $this->expectException(\LogicException::class);
        $uc->reqUserId();
    }

    public function testSetAndReqUserId(): void
    {
        $uc = new UserClient();
        $uc->setUserId(11);
        $this->assertSame(11, $uc->reqUserId());
    }

    public function testReqClientIdThrowsWhenNull(): void
    {
        $uc = new UserClient();
        $this->expectException(\LogicException::class);
        $uc->reqClientId();
    }

    public function testSetAndReqClientId(): void
    {
        $uc = new UserClient();
        $uc->setClientId(22);
        $this->assertSame(22, $uc->reqClientId());
    }
}
