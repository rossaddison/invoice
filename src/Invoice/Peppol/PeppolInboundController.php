<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Throwable;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Json\Json;

/**
 * Handles inbound delivery callbacks from the Oxalis AS4 access point.
 *
 * Route: POST /peppol/inbound/delivery
 * No auth middleware — Oxalis calls this directly; it is not a user-facing endpoint.
 *
 * Expected JSON body: { "messageId": "<uuid-or-opaque-id>" }
 * Response: HTTP 200 { "status": "ok" }  on success
 *           HTTP 200 { "status": "not_found" }  when messageId is unknown
 *           HTTP 200 { "status": "bad_request" }  when body is malformed
 */
final class PeppolInboundController
{
    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
        private readonly PeppolMessageRepositoryInterface $peppolMessageRepository,
        // PEPPOL_INBOUND_SECRET, bound in config/common/di/peppol.php
        private readonly string $callbackSecret = '',
    ) {
    }

    public function delivery(Request $request): Response
    {
        $presented = $request->getHeaderLine('X-Peppol-Callback-Secret');
        if ($presented === '') {
            $presented = (string) ($request->getQueryParams()['secret'] ?? '');
        }
        $refusal = PeppolInboundGuard::refusal($this->callbackSecret, $presented);
        if (null !== $refusal) {
            $this->logger->warning('PeppolInbound: refused — ' . $refusal);
            return $this->factory->createResponse(['status' => 'forbidden'], Status::FORBIDDEN);
        }

        return $this->factory->createResponse(['status' => $this->deliveryStatus($request->getBody()->getContents())]);
    }

    /** Split out of delivery() to keep each method within SonarQube's three-return limit (php:S1142). */
    private function deliveryStatus(string $body): string
    {
        $messageId = $this->extractMessageId($body);
        if ($messageId === '') {
            return 'bad_request';
        }

        $message = $this->peppolMessageRepository->repoByMessageId($messageId);
        if ($message === null) {
            $this->logger->warning('PeppolInbound: messageId not found — ' . $messageId);
            return 'not_found';
        }

        return $this->markDelivered($message, $messageId);
    }

    private function extractMessageId(string $body): string
    {
        try {
            /** @var array<array-key, mixed> $payload */
            $payload = Json::decode($body);
        } catch (Throwable $e) {
            $this->logger->warning('PeppolInbound: invalid JSON body — ' . $e->getMessage());
            return '';
        }

        if (isset($payload['messageId']) && is_string($payload['messageId']) && $payload['messageId'] !== '') {
            return $payload['messageId'];
        }
        $this->logger->warning('PeppolInbound: missing or invalid messageId in delivery callback.');
        return '';
    }

    private function markDelivered(PeppolMessage $message, string $messageId): string
    {
        try {
            $message->setStatus('DELIVERED');
            $message->setDeliveredAt(new DateTimeImmutable());
            $this->peppolMessageRepository->save($message);
        } catch (Throwable $e) {
            $this->logger->error('PeppolInbound: failed to update message ' . $messageId . ' — ' . $e->getMessage());
            return 'error';
        }

        $this->logger->info('PeppolInbound: delivered — ' . $messageId);
        return 'ok';
    }
}
