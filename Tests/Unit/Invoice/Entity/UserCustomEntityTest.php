<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\UserCustom\UserCustom;
use App\Infrastructure\Persistence\User\User;
use PHPUnit\Framework\TestCase;

class UserCustomEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseWhenIdIsNull(): void
    {
        $userCustom = new UserCustom();
        $this->assertFalse($userCustom->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $userCustom = new UserCustom();
        $this->expectException(\LogicException::class);
        $userCustom->reqId();
    }

    public function testConstructorWithAllParameters(): void
    {
        $userCustom = new UserCustom(
            id: 1,
            user_id: 10,
            fieldid: 5,
            fieldvalue: 'custom value',
        );

        $this->assertSame(1, $userCustom->reqId());
        $this->assertTrue($userCustom->hasIdentity());
        $this->assertSame(10, $userCustom->reqUserId());
        $this->assertSame(5, $userCustom->getFieldid());
        $this->assertSame('custom value', $userCustom->getFieldvalue());
    }

    public function testConstructorWithDefaults(): void
    {
        $userCustom = new UserCustom();
        $this->assertNull($userCustom->getFieldid());
        $this->assertSame('', $userCustom->getFieldvalue());
        $this->assertNull($userCustom->getUser());
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $userCustom = new UserCustom();
        $this->assertFalse($userCustom->hasIdentity());
        $userCustom->setId(50);
        $this->assertTrue($userCustom->hasIdentity());
        $this->assertSame(50, $userCustom->reqId());
    }

    public function testUserIdSetterAndGetter(): void
    {
        $userCustom = new UserCustom();
        $userCustom->setUserId(99);
        $this->assertSame(99, $userCustom->reqUserId());
    }

    public function testFieldidSetterAndGetter(): void
    {
        $userCustom = new UserCustom();
        $userCustom->setFieldid(3);
        $this->assertSame(3, $userCustom->getFieldid());
    }

    public function testFieldvalueSetterAndGetter(): void
    {
        $userCustom = new UserCustom();
        $userCustom->setFieldvalue('my field value');
        $this->assertSame('my field value', $userCustom->getFieldvalue());
    }

    public function testUserRelationSetterAndGetter(): void
    {
        $userCustom = new UserCustom();
        $user = $this->createStub(User::class);

        $userCustom->setUser($user);
        $this->assertSame($user, $userCustom->getUser());

        $userCustom->setUser(null);
        $this->assertNull($userCustom->getUser());
    }

    public function testEmptyFieldvalue(): void
    {
        $userCustom = new UserCustom();
        $userCustom->setFieldvalue('');
        $this->assertSame('', $userCustom->getFieldvalue());
    }

    public function testReqIdReturnType(): void
    {
        $userCustom = new UserCustom(id: 1);
        $this->assertIsInt($userCustom->reqId());
    }

    public function testLargeId(): void
    {
        $userCustom = new UserCustom(id: PHP_INT_MAX);
        $this->assertSame(PHP_INT_MAX, $userCustom->reqId());
    }
}
