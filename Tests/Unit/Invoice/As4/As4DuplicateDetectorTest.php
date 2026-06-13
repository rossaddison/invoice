<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4DuplicateDetector;
use App\Invoice\As4\As4MessageRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class As4DuplicateDetectorTestFixture
{
    public function __construct(
        public readonly As4DuplicateDetector $detector,
        public readonly As4MessageRepositoryInterface&MockObject $repository,
    ) {}
}

class As4DuplicateDetectorTest extends TestCase
{
    private function createFixture(): As4DuplicateDetectorTestFixture
    {
        $repository = $this->createMock(As4MessageRepositoryInterface::class);
        return new As4DuplicateDetectorTestFixture(
            detector:   new As4DuplicateDetector($repository),
            repository: $repository,
        );
    }

    private function makeMessage(string $messageId = 'msg-001@test.local'): As4Message
    {
        $m = new As4Message(
            messageId:        $messageId,
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       '',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     '',
            service:          'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:           'busdox-docid-qns::urn:test:invoice:1.0',
            receiverEndpoint: '',
            soapMessage:      '',
        );
        return $m->markReceived();
    }

    public function testReturnsTrueWhenMessageAlreadyStored(): void
    {
        $f = $this->createFixture();
        $f->repository->method('findByMessageId')->willReturn($this->makeMessage());

        $this->assertTrue($f->detector->isDuplicate('msg-001@test.local'));
    }

    public function testReturnsFalseWhenMessageNotFound(): void
    {
        $f = $this->createFixture();
        $f->repository->method('findByMessageId')->willReturn(null);

        $this->assertFalse($f->detector->isDuplicate('msg-001@test.local'));
    }

    public function testPassesMessageIdToRepository(): void
    {
        $f = $this->createFixture();
        $f->repository
            ->expects($this->once())
            ->method('findByMessageId')
            ->with('specific-id@test.local')
            ->willReturn(null);

        $f->detector->isDuplicate('specific-id@test.local');
    }
}
