<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use DOMDocument;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * AS4 Reception Awareness & Retry Engine
 *
 * Implements automatic retry logic per eDelivery AS4 2.0 section 3.3.2
 * (Reliable Messaging and Non-Repudiation of Receipt).
 *
 * @psalm-suppress UnusedClass
 */
class As4RetryEngine
{
    public function __construct(
        private readonly As4MessageRepositoryInterface $repository,
        private readonly As4Sender $sender,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Process pending messages and retry those ready for retransmission.
     *
     * @return array{processed: int, succeeded: int, failed: int}
     */
    public function processRetries(): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'failed' => 0];

        try {
            foreach ($this->repository->findPendingRetries() as $message) {
                if (!$message->isReadyForRetry()) {
                    continue;
                }

                $stats['processed']++;

                if ($this->retryMessage($message)) {
                    $stats['succeeded']++;
                } else {
                    $stats['failed']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Retry engine error', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Check for missing receipts and trigger MissingReceipt errors (EBMS:0301).
     */
    public function detectMissingReceipts(): int
    {
        $count = 0;
        try {
            $now = new DateTime();
            foreach ($this->repository->findAwaitingReceipts() as $message) {
                $lastAttempt = $message->getLastAttemptAt();
                if ($lastAttempt === null) {
                    continue;
                }

                $timeoutSeconds = ($message->getMaxAttempts() + 1) * $message->getRetryIntervalSeconds();
                $timeout = (clone $lastAttempt)->modify("+{$timeoutSeconds} seconds");

                if ($now > $timeout) {
                    $this->logger->warning('Missing receipt timeout', [
                        'messageId'   => $message->getMessageId(),
                        'lastAttempt' => $lastAttempt->format('c'),
                        'timeout'     => $timeout->format('c'),
                    ]);
                    $message->markFailed('EBMS:0301', 'Receipt not received within timeout period');
                    $this->repository->save($message);
                    $count++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Missing receipt detection error', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Duplicate detection: check if a message with the same MessageId already exists.
     *
     * Per section 3.3.2: Receivers MUST implement duplicate detection.
     * Placeholder — real implementation belongs in As4DuplicateDetector.
     */
    public function isDuplicate(string $messageId): bool
    {
        $this->logger->debug('Duplicate check', ['messageId' => $messageId]);
        return false;
    }

    public function getNextRetryDelay(As4Message $message): ?int
    {
        return $message->getNextRetryIn();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function retryMessage(As4Message $message): bool
    {
        if (!$this->canRetry($message)) {
            return false;
        }

        $response = $this->attemptSend($message);
        if ($response === null) {
            return false;
        }

        return $this->processResponse($message, $response);
    }

    private function canRetry(As4Message $message): bool
    {
        if ($message->getState() === As4MessageState::receiptReceived) {
            $this->logger->info('Message already has receipt, skipping', [
                'messageId' => $message->getMessageId(),
            ]);
            return false;
        }

        if ($message->getAttemptCount() >= $message->getMaxAttempts()) {
            $message->markFailed(
                'RETRY_EXCEEDED',
                sprintf('Max retries (%d) exceeded without receipt', $message->getMaxAttempts())
            );
            $this->repository->save($message);
            $this->logger->warning('Message max retries exceeded', [
                'messageId' => $message->getMessageId(),
                'attempts'  => $message->getAttemptCount(),
            ]);
            return false;
        }

        return true;
    }

    private function attemptSend(As4Message $message): ?As4HttpResponse
    {
        $this->logger->info('Retrying AS4 message', [
            'messageId'   => $message->getMessageId(),
            'attempt'     => $message->getAttemptCount() + 1,
            'maxAttempts' => $message->getMaxAttempts(),
        ]);

        try {
            return $this->sender->send(
                endpoint: $message->getReceiverEndpoint(),
                envelope: $this->parseEnvelope($message->getSoapMessage()),
                parts:    []
            );
        } catch (\UnexpectedValueException $e) {
            // Malformed persisted SOAP envelope — cannot retry, permanent failure
            $message->markFailed('MALFORMED_SOAP', $e->getMessage());
            $this->repository->save($message);
            return null;
        } catch (\Exception $e) {
            // Transient network failure — leave in current state for next retry cycle
            $this->logger->warning('Transient failure during AS4 send, will retry', [
                'messageId' => $message->getMessageId(),
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function processResponse(As4Message $message, As4HttpResponse $response): bool
    {
        if ($response->isSuccess()) {
            // HTTP transport accepted — message is now in Sent/AwaitingReceipt state
            $message->markSent();
            $this->repository->save($message);
            $this->logger->info('AS4 transport succeeded, awaiting receipt signal', [
                'messageId' => $message->getMessageId(),
            ]);
            return true;
        }

        if ($response->isRetriable()) {
            $this->logger->warning('Retriable error, will retry later', [
                'messageId'  => $message->getMessageId(),
                'statusCode' => $response->statusCode,
            ]);
            return false;
        }

        $message->markFailed('HTTP_' . $response->statusCode, 'AS4 endpoint returned non-retriable error');
        $this->repository->save($message);
        return false;
    }

    private function parseEnvelope(string $xml): DOMDocument
    {
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        if (!$loaded) {
            throw new \UnexpectedValueException('Persisted SOAP envelope is not well-formed XML');
        }
        return $doc;
    }
}
