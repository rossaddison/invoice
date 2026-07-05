<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4RetryState;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4RetryEngine;
use App\Invoice\As4\As4SenderInterface;
use DateTime;
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
        $repo = m::mock(As4MessageRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('findAwaitingReceipts');
        $e->once()->andReturn([]);

        $engine = new As4RetryEngine(
            $repo,
            m::spy(As4SenderInterface::class),
            m::spy(LoggerInterface::class),
            m::spy(As4ReceiptParserInterface::class),
        );

        Assert::same(0, $engine->detectMissingReceipts());
    }

    public function detectMissingReceiptsSkipsMessageWithNullFirstSentAt(): void
    {
        $retryState = m::mock(As4RetryState::class);
        /** @var \Mockery\Expectation $e */
        $e = $retryState->shouldReceive('getFirstSentAt');
        $e->andReturn(null);

        $message = m::mock(As4Message::class);
        /** @var \Mockery\Expectation $e */
        $e = $message->shouldReceive('getRetryState');
        $e->andReturn($retryState);

        $repo = m::mock(As4MessageRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('findAwaitingReceipts');
        $e->once()->andReturn([$message]);
        $repo->shouldNotReceive('save');

        $engine = new As4RetryEngine(
            $repo,
            m::spy(As4SenderInterface::class),
            m::spy(LoggerInterface::class),
            m::spy(As4ReceiptParserInterface::class),
        );

        Assert::same(0, $engine->detectMissingReceipts());
    }

    public function detectMissingReceiptsMarksTimedOutMessageFailed(): void
    {
        $retryState = m::mock(As4RetryState::class);
        /** @var \Mockery\Expectation $e */
        $e = $retryState->shouldReceive('getFirstSentAt');
        $e->andReturn(new DateTime('2000-01-01'));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $retryState->shouldReceive('getMaxAttempts');
        $e2->andReturn(3);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $retryState->shouldReceive('getRetryIntervalSeconds');
        $e3->andReturn(300);

        $message = m::mock(As4Message::class);
        /** @var \Mockery\Expectation $e */
        $e = $message->shouldReceive('getRetryState');
        $e->andReturn($retryState);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $message->shouldReceive('getMessageId');
        $e4->andReturn('<timed-out@example.com>');
        /** @var \Mockery\Expectation $e5 */
        $e5 = $message->shouldReceive('markFailed');
        $e5->once()
           ->with('EBMS:0301', 'Receipt not received within timeout period')
           ->andReturn($message);

        $repo = m::mock(As4MessageRepositoryInterface::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $repo->shouldReceive('findAwaitingReceipts');
        $e6->once()->andReturn([$message]);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $repo->shouldReceive('save');
        $e7->once()->with($message);

        $engine = new As4RetryEngine(
            $repo,
            m::spy(As4SenderInterface::class),
            m::spy(LoggerInterface::class),
            m::spy(As4ReceiptParserInterface::class),
        );

        Assert::same(1, $engine->detectMissingReceipts());
    }
}
