<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\As4Error\As4Error;
use PHPUnit\Framework\TestCase;
use DateTime;

class As4ErrorEntityTest extends TestCase
{
    private function makeError(): As4Error
    {
        return new As4Error(
            errorMessageId: 'err-msg-001@as4.example.com',
            refToMessageId: 'orig-msg-001@as4.example.com',
            errorCode: As4Error::CATEGORY_PROCESSING,
            category: As4Error::CATEGORY_PROCESSING,
            shortDescription: 'ValueNotRecognized',
            originSender: '0088:1234567890123',
            originReceiver: '0088:9876543210987',
            errorXml: '<eb:SignalMessage/>'
        );
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->makeError()->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $this->expectException(\LogicException::class);
        $this->makeError()->reqId();
    }

    public function testSetIdMakesPersisted(): void
    {
        $error = $this->makeError();
        $error->setId(1);
        $this->assertTrue($error->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $error = $this->makeError();
        $error->setId(42);
        $this->assertIsInt($error->reqId());
        $this->assertSame(42, $error->reqId());
    }

    public function testConstructorAssignsAllFields(): void
    {
        $error = $this->makeError();

        $this->assertSame('err-msg-001@as4.example.com', $error->getErrorMessageId());
        $this->assertSame('orig-msg-001@as4.example.com', $error->getRefToMessageId());
        $this->assertSame(As4Error::CATEGORY_PROCESSING, $error->getErrorCode());
        $this->assertSame(As4Error::CATEGORY_PROCESSING, $error->getCategory());
        $this->assertSame('ValueNotRecognized', $error->getShortDescription());
        $this->assertSame('0088:1234567890123', $error->getOriginSender());
        $this->assertSame('0088:9876543210987', $error->getOriginReceiver());
        $this->assertSame('<eb:SignalMessage/>', $error->getErrorXml());
        $this->assertNull($error->getDescription());
        $this->assertTrue($error->isSigned());
    }

    public function testSetDescriptionStoresValue(): void
    {
        $error = $this->makeError();
        $error->setDescription('Full error detail text');
        $this->assertSame('Full error detail text', $error->getDescription());
    }

    public function testCategoryConstants(): void
    {
        $this->assertSame('Communication', As4Error::CATEGORY_COMMUNICATION);
        $this->assertSame('Processing', As4Error::CATEGORY_PROCESSING);
        $this->assertSame('Unpackaging', As4Error::CATEGORY_UNPACKAGING);
    }

    public function testReceivedAtAndCreatedAtAreSetOnConstruction(): void
    {
        $before = new DateTime();
        $error = $this->makeError();
        $after = new DateTime();

        $this->assertGreaterThanOrEqual($before, $error->getReceivedAt());
        $this->assertLessThanOrEqual($after, $error->getReceivedAt());
        $this->assertGreaterThanOrEqual($before, $error->getCreatedAt());
        $this->assertLessThanOrEqual($after, $error->getCreatedAt());
    }

    public function testIsCriticalForKnownCriticalCodes(): void
    {
        foreach (['EBMS:0201', 'EBMS:0202', 'EBMS:0303', 'EBMS:0402'] as $code) {
            $error = new As4Error(
                errorMessageId: 'id',
                refToMessageId: 'ref',
                errorCode: $code,
                category: As4Error::CATEGORY_PROCESSING,
                shortDescription: 'test',
                originSender: 'sender',
                originReceiver: 'receiver',
                errorXml: '<xml/>'
            );
            $this->assertTrue($error->isCritical(), "Expected {$code} to be critical");
        }
    }

    public function testIsCriticalReturnsFalseForNonCriticalCode(): void
    {
        $error = new As4Error(
            errorMessageId: 'id',
            refToMessageId: 'ref',
            errorCode: 'EBMS:0001',
            category: As4Error::CATEGORY_PROCESSING,
            shortDescription: 'test',
            originSender: 'sender',
            originReceiver: 'receiver',
            errorXml: '<xml/>'
        );
        $this->assertFalse($error->isCritical());
    }

    public function testIsRetriableForKnownRetriableCodes(): void
    {
        foreach (['EBMS:0202', 'EBMS:0203'] as $code) {
            $error = new As4Error(
                errorMessageId: 'id',
                refToMessageId: 'ref',
                errorCode: $code,
                category: As4Error::CATEGORY_PROCESSING,
                shortDescription: 'test',
                originSender: 'sender',
                originReceiver: 'receiver',
                errorXml: '<xml/>'
            );
            $this->assertTrue($error->isRetriable(), "Expected {$code} to be retriable");
        }
    }

    public function testIsRetriableReturnsFalseForNonRetriableCode(): void
    {
        $this->assertFalse($this->makeError()->isRetriable());
    }

    public function testReturnTypes(): void
    {
        $error = $this->makeError();
        $error->setId(1);

        $this->assertIsInt($error->reqId());
        $this->assertIsString($error->getErrorMessageId());
        $this->assertIsString($error->getRefToMessageId());
        $this->assertIsString($error->getErrorCode());
        $this->assertIsString($error->getCategory());
        $this->assertIsString($error->getShortDescription());
        $this->assertIsString($error->getOriginSender());
        $this->assertIsString($error->getOriginReceiver());
        $this->assertIsString($error->getErrorXml());
        $this->assertIsBool($error->isSigned());
        $this->assertInstanceOf(DateTime::class, $error->getReceivedAt());
        $this->assertInstanceOf(DateTime::class, $error->getCreatedAt());
    }
}
