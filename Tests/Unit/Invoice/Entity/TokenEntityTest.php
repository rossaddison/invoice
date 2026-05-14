<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\Token\Token;
use Codeception\Test\Unit;
use DateTimeImmutable;
use ReflectionProperty;

class TokenEntityTest extends Unit
{
    public function testNotPersistedByDefault(): void
    {
        $token = new Token(1, 'email-verification');

        $this->assertFalse($token->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $token = new Token(1, 'email-verification');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Token not persisted');
        $token->reqId();
    }

    public function testHasIdentityReturnsTrueWhenIdSet(): void
    {
        $token = new Token(1, 'email-verification');
        $this->setId($token, 10);

        $this->assertTrue($token->hasIdentity());
    }

    public function testReqIdReturnsIntWhenPersisted(): void
    {
        $token = new Token(1, 'email-verification');
        $this->setId($token, 42);

        $this->assertSame(42, $token->reqId());
        $this->assertIsInt($token->reqId());
    }

    public function testConstructorSetsIdentityId(): void
    {
        $token = new Token(99, 'request-password-reset');

        $this->assertSame(99, $token->getIdentityId());
    }

    public function testConstructorSetsType(): void
    {
        $token = new Token(1, 'request-password-reset');

        $this->assertSame('request-password-reset', $token->getType());
    }

    public function testConstructorWithEmailVerificationType(): void
    {
        $token = new Token(5, 'email-verification');

        $this->assertSame('email-verification', $token->getType());
    }

    public function testTokenIsSetOnConstruct(): void
    {
        $token = new Token(1, 'email-verification');

        $tokenString = $token->getToken();
        $this->assertIsString($tokenString);
        $this->assertNotNull($tokenString);
        $this->assertSame(32, strlen($tokenString));
    }

    public function testCreatedAtIsDateTimeImmutableOnConstruct(): void
    {
        $before = new DateTimeImmutable();
        $token = new Token(1, 'email-verification');
        $after = new DateTimeImmutable();

        $createdAt = $token->getCreatedAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $createdAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $createdAt->getTimestamp());
    }

    public function testGetIdentityReturnsNullByDefault(): void
    {
        $token = new Token(1, 'email-verification');

        $this->assertNull($token->getIdentity());
    }

    public function testSetAndGetIdentityId(): void
    {
        $token = new Token(1, 'email-verification');

        $token->setIdentityId(77);
        $this->assertSame(77, $token->getIdentityId());
    }

    public function testSetAndGetToken(): void
    {
        $token = new Token(1, 'email-verification');

        $token->setToken('abc123xyz');
        $this->assertSame('abc123xyz', $token->getToken());
    }

    public function testSetAndGetType(): void
    {
        $token = new Token(1, 'email-verification');

        $token->setType('github-access');
        $this->assertSame('github-access', $token->getType());
    }

    public function testSetCreatedAtFromValidDateString(): void
    {
        $token = new Token(1, 'email-verification');

        $token->setCreatedAt('2024-06-15 02:30:00');
        $this->assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
    }

    public function testSetCreatedAtFallsBackToNowOnInvalidString(): void
    {
        $token = new Token(1, 'email-verification');

        $token->setCreatedAt('not-a-date');
        $this->assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
    }

    public function testTwoFreshTokensHaveDifferentTokenStrings(): void
    {
        $a = new Token(1, 'email-verification');
        $b = new Token(1, 'email-verification');

        $this->assertNotSame($a->getToken(), $b->getToken());
    }

    public function testDefaultConstructorArguments(): void
    {
        $token = new Token();

        $this->assertNull($token->getIdentityId());
        $this->assertSame('', $token->getType());
        $this->assertFalse($token->hasIdentity());
    }

    public function testTimestampFromCreatedAtIsUsableForExpiry(): void
    {
        $token = new Token(1, 'request-password-reset');

        $timestamp = $token->getCreatedAt()->getTimestamp();
        $this->assertIsInt($timestamp);
        // Token should not be created more than 1 second in the future
        $this->assertLessThanOrEqual(time() + 1, $timestamp);
    }

    /**
     * Simulates what the ORM does when hydrating a persisted record.
     */
    private function setId(Token $token, ?int $id): void
    {
        $prop = new ReflectionProperty(Token::class, 'id');
        $prop->setValue($token, $id);
    }
}
