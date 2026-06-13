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
        private readonly As4SenderInterface $sender,
        private readonly LoggerInterface $logger,
        private readonly As4ReceiptParserInterface $receiptParser,
        private readonly As4RetryPolicyInterface $retryPolicy = new As4FixedIntervalRetryPolicy(),
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
                $delay = $this->retryPolicy->delaySeconds(
                    $message->getAttemptCount(),
                    $message->getRetryIntervalSeconds()
                );
                if (!$message->isReadyForRetry($delay)) {
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
     * Check for missing receipts and generate EBMS:0301 errors on timeout.
     *
     * The deadline is anchored to the first transmission time, not the last
     * retry. This matches eDelivery AS4 2.0 section 3.3.2: the MissingReceipt
     * error is raised after the configured receipt timeout has expired since
     * the timestamp of the original UserMessage.
     */
    public function detectMissingReceipts(): int
    {
        $count = 0;
        try {
            $now = new DateTime();
            foreach ($this->repository->findAwaitingReceipts() as $message) {
                $firstSent = $message->getFirstSentAt();
                if ($firstSent === null) {
                    continue;
                }

                $receiptDeadline = (clone $firstSent)->modify(
                    sprintf('+%d seconds', ($message->getMaxAttempts() + 1) * $message->getRetryIntervalSeconds())
                );

                if ($now > $receiptDeadline) {
                    $this->logger->warning('Missing receipt timeout — raising EBMS:0301', [
                        'messageId'       => $message->getMessageId(),
                        'firstSentAt'     => $firstSent->format('c'),
                        'receiptDeadline' => $receiptDeadline->format('c'),
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
            $envelope = $this->parseEnvelope($message->getSoapMessage());
        } catch (\UnexpectedValueException $e) {
            // Malformed persisted SOAP — cannot retry, permanent failure
            $message->markFailed('MALFORMED_SOAP', $e->getMessage());
            $this->repository->save($message);
            return null;
        }

        return $this->executeSend($message, $envelope);
    }

    private function executeSend(As4Message $message, DOMDocument $envelope): ?As4HttpResponse
    {
        // Record the attempt before the HTTP call so the counter is persisted
        // even when the send fails — prevents infinite retry on permanent errors
        $message->recordAttempt();
        $this->repository->save($message);

        try {
            return $this->sender->send(
                endpoint: $message->getReceiverEndpoint(),
                envelope: $envelope,
                parts:    []
            );
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            $this->handleTransportException($message, $e);
            return null;
        } catch (\Exception $e) {
            // Unexpected failure — treat as permanent to prevent infinite retry
            $message->markFailed('UNEXPECTED_ERROR', $e->getMessage());
            $this->repository->save($message);
            return null;
        }
    }

    private function handleTransportException(
        As4Message $message,
        \Psr\Http\Client\ClientExceptionInterface $e,
    ): void {
        if (!($e instanceof \Psr\Http\Client\RequestExceptionInterface)) {
            // Network-level failure — transient, will retry after interval
            $this->logger->warning('Transient network failure during AS4 send, will retry', [
                'messageId' => $message->getMessageId(),
                'error'     => $e->getMessage(),
            ]);
            return;
        }

        // Malformed request — permanent, do not retry
        $message->markFailed('REQUEST_ERROR', $e->getMessage());
        $this->repository->save($message);
    }

    private function processResponse(As4Message $message, As4HttpResponse $response): bool
    {
        if ($response->isSuccess()) {
            return $this->handleSuccessResponse($message, $response);
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

    /**
     * Inspects the body of a successful (2xx) HTTP response for an ebMS3 signal.
     *
     * HTTP 200/202 does NOT guarantee delivery — the body may contain a receipt
     * signal (confirmed delivery), an error signal (receiver rejected the message),
     * or nothing at all (async 202 acceptance, receipt will arrive separately).
     */
    private function handleSuccessResponse(As4Message $message, As4HttpResponse $response): bool
    {
        $signal = $this->receiptParser->parse($response->body, $response->contentType);

        if ($signal instanceof As4ReceiptSignal) {
            $message->markReceiptReceived($signal->messageId);
            $this->repository->save($message);
            $this->logger->info('Receipt signal confirmed delivery', [
                'messageId'       => $message->getMessageId(),
                'signalMessageId' => $signal->messageId,
                'refToMessageId'  => $signal->refToMessageId,
            ]);
            return true;
        }

        if ($signal instanceof As4ErrorSignal && $signal->isFailure()) {
            $message->markFailed($signal->errorCode, $signal->shortDescription);
            $this->repository->save($message);
            $this->logger->error('ebMS error signal received — message rejected by receiver', [
                'messageId'        => $message->getMessageId(),
                'signalMessageId'  => $signal->messageId,
                'errorCode'        => $signal->errorCode,
                'shortDescription' => $signal->shortDescription,
                'severity'         => $signal->severity->value,
            ]);
            return false;
        }

        if ($signal instanceof As4ErrorSignal) {
            // Warning-level error — receiver accepted the message but flagged an issue
            $this->logger->warning('ebMS warning signal in AS4 response', [
                'messageId'        => $message->getMessageId(),
                'signalMessageId'  => $signal->messageId,
                'errorCode'        => $signal->errorCode,
                'shortDescription' => $signal->shortDescription,
            ]);
        }

        // null (async 202) or warning signal — transport accepted, state already sent,
        // attempt count already persisted by recordAttempt() in executeSend()
        $this->logger->info('AS4 transport accepted, awaiting receipt signal', [
            'messageId' => $message->getMessageId(),
        ]);
        return true;
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
