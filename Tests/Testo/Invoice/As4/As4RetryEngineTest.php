<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4RetryState;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4RetryEngine;
use App\Invoice\As4\As4SenderInterface;
use DateTimeImmutable;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Demonstrates the Mockery bridge: MockeryPlugin calls Mockery::close()
 * automatically after every test — no tearDown() boilerplate needed.
 *
 * All four constructor dependencies of As4RetryEngine are interfaces,
 * so every scenario runs without a live database or HTTP connection.
 */
#[Test]
final class As4RetryEngineTest
{
    public function detectMissingReceiptsReturnsZeroForEmptyQueue(): void
    {
        /** @var As4MessageRepositoryInterface&m\MockInterface $repo */
        $repo = m::mock(As4MessageRepositoryInterface::class);
        $e = $repo->shouldReceive('findAwaitingReceipts');
        $e->once()->andReturn([]);

        /** @var As4SenderInterface&m\MockInterface $sender */
        $sender = m::spy(As4SenderInterface::class);
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);
        /** @var As4ReceiptParserInterface&m\MockInterface $receiptParser */
        $receiptParser = m::spy(As4ReceiptParserInterface::class);
        $engine = new As4RetryEngine($repo, $sender, $logger, $receiptParser);

        Assert::same(0, $engine->detectMissingReceipts());
    }

    public function detectMissingReceiptsSkipsMessageWithNullFirstSentAt(): void
    {
        /** @var As4RetryState&m\MockInterface $retryState */
        $retryState = m::mock(As4RetryState::class);
        $e = $retryState->shouldReceive('getFirstSentAt');
        $e->andReturn(null);

        /** @var As4Message&m\MockInterface $message */
        $message = m::mock(As4Message::class);
        $e = $message->shouldReceive('getRetryState');
        $e->andReturn($retryState);

        /** @var As4MessageRepositoryInterface&m\MockInterface $repo */
        $repo = m::mock(As4MessageRepositoryInterface::class);
        $e = $repo->shouldReceive('findAwaitingReceipts');
        $e->once()->andReturn([$message]);
        $repo->shouldNotReceive('save');

        /** @var As4SenderInterface&m\MockInterface $sender */
        $sender = m::spy(As4SenderInterface::class);
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);
        /** @var As4ReceiptParserInterface&m\MockInterface $receiptParser */
        $receiptParser = m::spy(As4ReceiptParserInterface::class);
        $engine = new As4RetryEngine($repo, $sender, $logger, $receiptParser);

        Assert::same(0, $engine->detectMissingReceipts());
    }

    public function detectMissingReceiptsMarksTimedOutMessageFailed(): void
    {
        /** @var As4RetryState&m\MockInterface $retryState */
        $retryState = m::mock(As4RetryState::class);
        $e = $retryState->shouldReceive('getFirstSentAt');
        $e->andReturn(new DateTimeImmutable('2000-01-01'));
        $e2 = $retryState->shouldReceive('getMaxAttempts');
        $e2->andReturn(3);
        $e3 = $retryState->shouldReceive('getRetryIntervalSeconds');
        $e3->andReturn(300);

        /** @var As4Message&m\MockInterface $message */
        $message = m::mock(As4Message::class);
        $e = $message->shouldReceive('getRetryState');
        $e->andReturn($retryState);
        $e4 = $message->shouldReceive('getMessageId');
        $e4->andReturn('<timed-out@example.com>');
        $e5 = $message->shouldReceive('markFailed');
        $e5->once()
           ->with('EBMS:0301', 'Receipt not received within timeout period')
           ->andReturn($message);

        /** @var As4MessageRepositoryInterface&m\MockInterface $repo */
        $repo = m::mock(As4MessageRepositoryInterface::class);
        $e6 = $repo->shouldReceive('findAwaitingReceipts');
        $e6->once()->andReturn([$message]);
        $e7 = $repo->shouldReceive('save');
        $e7->once()->with($message);

        /** @var As4SenderInterface&m\MockInterface $sender */
        $sender = m::spy(As4SenderInterface::class);
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);
        /** @var As4ReceiptParserInterface&m\MockInterface $receiptParser */
        $receiptParser = m::spy(As4ReceiptParserInterface::class);
        $engine = new As4RetryEngine($repo, $sender, $logger, $receiptParser);

        Assert::same(1, $engine->detectMissingReceipts());
    }
}
