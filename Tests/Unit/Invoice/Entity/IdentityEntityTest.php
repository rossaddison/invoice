<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\Identity\Identity;
use Codeception\Test\Unit;
use ReflectionProperty;

class IdentityEntityTest extends Unit
{
    public function testNotPersistedByDefault(): void
    {
        $identity = new Identity();

        $this->assertFalse($identity->hasIdentity());
    }

    public function testGetIdReturnsNullWhenNotPersisted(): void
    {
        $identity = new Identity();

        $this->assertNull($identity->getId());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $identity = new Identity();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Identity not persisted');
        $identity->reqId();
    }

    public function testHasIdentityReturnsTrueWhenIdSet(): void
    {
        $identity = new Identity();
        $this->setId($identity, 5);

        $this->assertTrue($identity->hasIdentity());
    }

    public function testReqIdReturnsIntWhenPersisted(): void
    {
        $identity = new Identity();
        $this->setId($identity, 7);

        $this->assertSame(7, $identity->reqId());
        $this->assertIsInt($identity->reqId());
    }

    public function testGetIdReturnsStringIdWhenPersisted(): void
    {
        $identity = new Identity();
        $this->setId($identity, 3);

        $this->assertSame('3', $identity->getId());
        $this->assertIsString($identity->getId());
    }

    public function testAuthKeyIsSetOnConstruct(): void
    {
        $identity = new Identity();

        $key = $identity->getCookieLoginKey();
        $this->assertIsString($key);
        $this->assertSame(32, strlen($key));
    }

    public function testValidateCookieLoginKeyWithCorrectKey(): void
    {
        $identity = new Identity();

        $key = $identity->getCookieLoginKey();
        $this->assertTrue($identity->validateCookieLoginKey($key));
    }

    public function testValidateCookieLoginKeyWithWrongKey(): void
    {
        $identity = new Identity();

        $this->assertFalse($identity->validateCookieLoginKey('wrong-key'));
    }

    public function testGenerateAuthKeyChangesKey(): void
    {
        $identity = new Identity();
        $originalKey = $identity->getCookieLoginKey();

        $identity->generateAuthKey();
        $newKey = $identity->getCookieLoginKey();

        $this->assertSame(32, strlen($newKey));
        // Statistically impossible for two Random::string(32) to collide
        $this->assertNotSame($originalKey, $newKey);
    }

    public function testRegenerateCookieLoginKeyReturns32CharString(): void
    {
        $identity = new Identity();

        $result = $identity->regenerateCookieLoginKey();
        $this->assertIsString($result);
        $this->assertSame(32, strlen($result));
    }

    public function testGetUserReturnsNullByDefault(): void
    {
        $identity = new Identity();

        $this->assertNull($identity->getUser());
    }

    public function testGetUserIdReturnsNullWhenNoUser(): void
    {
        $identity = new Identity();

        $this->assertNull($identity->getUserId());
    }

    public function testTwoFreshIdentitiesHaveDifferentAuthKeys(): void
    {
        $a = new Identity();
        $b = new Identity();

        $this->assertNotSame($a->getCookieLoginKey(), $b->getCookieLoginKey());
    }

    public function testValidateCookieLoginKeyAfterGenerateAuthKey(): void
    {
        $identity = new Identity();
        $oldKey = $identity->getCookieLoginKey();

        $identity->generateAuthKey();

        $this->assertFalse($identity->validateCookieLoginKey($oldKey));
        $this->assertTrue($identity->validateCookieLoginKey($identity->getCookieLoginKey()));
    }

    public function testGetIdReturnsNullAfterIdClearedToNull(): void
    {
        $identity = new Identity();
        $this->setId($identity, 1);
        $this->assertSame('1', $identity->getId());

        $this->setId($identity, null);
        $this->assertNull($identity->getId());
    }

    /**
     * Simulates what the ORM does when hydrating a persisted record.
     */
    private function setId(Identity $identity, ?int $id): void
    {
        $prop = new ReflectionProperty(Identity::class, 'id');
        $prop->setValue($identity, $id);
    }
}
