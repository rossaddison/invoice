<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    private function makeUser(): User
    {
        return new User('testuser', 'test@example.com', 'password123');
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $u = $this->makeUser();
        $this->expectException(\LogicException::class);
        $u->reqId();
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $u = $this->makeUser();
        $this->assertInstanceOf(DateTimeImmutable::class, $u->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $u->getUpdatedAt());
    }

    public function testConstructorSetsLoginAndEmail(): void
    {
        $u = $this->makeUser();
        $this->assertSame('testuser', $u->getLogin());
        $this->assertSame('test@example.com', $u->getEmail());
    }

    public function testConstructorDefaults(): void
    {
        $u = $this->makeUser();
        $this->assertFalse($u->is2FAEnabled());
        $this->assertSame('', $u->getTotpSecret());
    }

    public function testSetAndGetLogin(): void
    {
        $u = $this->makeUser();
        $u->setLogin('newlogin');
        $this->assertSame('newlogin', $u->getLogin());
    }

    public function testValidatePasswordCorrect(): void
    {
        $u = $this->makeUser();
        $this->assertTrue($u->validatePassword('password123'));
    }

    public function testValidatePasswordIncorrect(): void
    {
        $u = $this->makeUser();
        $this->assertFalse($u->validatePassword('wrongpassword'));
    }

    public function testSetPasswordChangesHash(): void
    {
        $u = $this->makeUser();
        $u->setPassword('newpassword');
        $this->assertTrue($u->validatePassword('newpassword'));
        $this->assertFalse($u->validatePassword('password123'));
    }

    public function testSet2FAEnabled(): void
    {
        $u = $this->makeUser();
        $u->set2FAEnabled(true);
        $this->assertTrue($u->is2FAEnabled());
    }

    public function testSetAndGetTotpSecret(): void
    {
        $u = $this->makeUser();
        $u->setTotpSecret('JBSWY3DPEHPK3PXP');
        $this->assertSame('JBSWY3DPEHPK3PXP', $u->getTotpSecret());
    }

    public function testSetTotpSecretNull(): void
    {
        $u = $this->makeUser();
        $u->setTotpSecret(null);
        $this->assertNull($u->getTotpSecret());
    }

    public function testGetIdentityReturnsIdentityInstance(): void
    {
        $u = $this->makeUser();
        $this->assertInstanceOf(Identity::class, $u->getIdentity());
    }
}
