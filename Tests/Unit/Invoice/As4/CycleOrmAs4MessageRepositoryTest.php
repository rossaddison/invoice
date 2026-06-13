<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4MessageParams;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4MessageState;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Select;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Unit tests for CycleOrmAs4MessageRepository.
 *
 * The find*() methods rely on the Cycle ORM Select query chain and require a
 * live ORM instance to exercise meaningfully — those are deferred to a future
 * integration test suite.  This file covers the two behaviours that can be
 * verified without a database:
 *
 *  1. claimForRetry() — the raw SQL CAS update and its isPersisted() guard
 *  2. save() — delegation to EntityWriter::write()
 */
#[AllowMockObjectsWithoutExpectations]
class CycleOrmAs4MessageRepositoryTest extends TestCase
{
    // ── Fixture helpers ───────────────────────────────────────────────────────

    private function makeRepository(DatabaseInterface&MockObject $database): CycleOrmAs4MessageRepository
    {
        /** @var Select<As4Message>&MockObject $select */
        $select = $this->getMockBuilder(Select::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $entityWriter = $this->createMock(EntityWriter::class);

        return new CycleOrmAs4MessageRepository($select, $entityWriter, $database);
    }

    private function makeEntityWriterRepository(EntityWriter&MockObject $entityWriter): CycleOrmAs4MessageRepository
    {
        /** @var Select<As4Message>&MockObject $select */
        $select = $this->getMockBuilder(Select::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $database = $this->createMock(DatabaseInterface::class);

        return new CycleOrmAs4MessageRepository($select, $entityWriter, $database);
    }

    private function newMessage(): As4Message
    {
        return new As4Message(new As4MessageParams(
            messageId:        'msg-001@test.local',
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       'Seller',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     'Buyer',
            service:          'urn:test:service',
            action:           'urn:test:action',
            receiverEndpoint: 'https://receiver.example.com/as4',
            soapMessage:      '<soap/>',
        ));
    }

    private function persistedMessage(int $id = 99): As4Message
    {
        $msg = $this->newMessage();
        $msg->setId($id);
        return $msg;
    }

    // ── isPersisted() guard ───────────────────────────────────────────────────

    public function testClaimReturnsFalseForUnpersistedMessage(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->never())->method('execute');

        $result = $this->makeRepository($database)->claimForRetry($this->newMessage());

        $this->assertFalse($result);
    }

    public function testUnpersistedMessageHasNoId(): void
    {
        $this->assertFalse($this->newMessage()->isPersisted());
    }

    public function testSetIdMakesMessagePersisted(): void
    {
        $msg = $this->newMessage();
        $msg->setId(1);
        $this->assertTrue($msg->isPersisted());
        $this->assertSame(1, $msg->reqId());
    }

    // ── claimForRetry() SQL ───────────────────────────────────────────────────

    public function testClaimIssuesSqlAgainstAs4MessagesTable(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->once())
                 ->method('execute')
                 ->with($this->stringContains('as4_messages'), $this->anything())
                 ->willReturn(1);

        $this->makeRepository($database)->claimForRetry($this->persistedMessage());
    }

    public function testClaimSqlContainsUpdateAndLockedAt(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->once())
                 ->method('execute')
                 ->with(
                     $this->logicalAnd(
                         $this->stringContains('UPDATE'),
                         $this->stringContains('locked_at'),
                     ),
                     $this->anything(),
                 )
                 ->willReturn(0);

        $this->makeRepository($database)->claimForRetry($this->persistedMessage());
    }

    public function testClaimPassesMessageIdAsThirdParameter(): void
    {
        $msgId    = 42;
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->once())
                 ->method('execute')
                 ->with(
                     $this->anything(),
                     $this->callback(static fn (array $p): bool => $p[2] === $msgId),
                 )
                 ->willReturn(1);

        $this->makeRepository($database)->claimForRetry($this->persistedMessage($msgId));
    }

    public function testClaimPassesSentStateAsFourthParameter(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->once())
                 ->method('execute')
                 ->with(
                     $this->anything(),
                     $this->callback(
                         static fn (array $p): bool => $p[3] === As4MessageState::sent->value
                     ),
                 )
                 ->willReturn(0);

        $this->makeRepository($database)->claimForRetry($this->persistedMessage());
    }

    public function testClaimReturnsTrueWhenOneRowAffected(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->method('execute')->willReturn(1);

        $this->assertTrue($this->makeRepository($database)->claimForRetry($this->persistedMessage()));
    }

    public function testClaimReturnsFalseWhenZeroRowsAffected(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->method('execute')->willReturn(0);

        $this->assertFalse($this->makeRepository($database)->claimForRetry($this->persistedMessage()));
    }

    // ── save() delegation ─────────────────────────────────────────────────────

    public function testSaveDelegatesToEntityWriter(): void
    {
        $message      = $this->newMessage();
        $entityWriter = $this->createMock(EntityWriter::class);
        $entityWriter->expects($this->once())
                     ->method('write')
                     ->with([$message]);

        $this->makeEntityWriterRepository($entityWriter)->save($message);
    }
}
