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
    ) {}

    public function delivery(Request $request): Response
    {
        $messageId = $this->extractMessageId($request->getBody()->getContents());
        if ($messageId === '') {
            return $this->factory->createResponse(['status' => 'bad_request']);
        }

        $message = $this->peppolMessageRepository->repoByMessageId($messageId);
        if ($message === null) {
            $this->logger->warning('PeppolInbound: messageId not found — ' . $messageId);
            return $this->factory->createResponse(['status' => 'not_found']);
        }

        return $this->factory->createResponse(['status' => $this->markDelivered($message, $messageId)]);
    }

    private function extractMessageId(string $body): string
    {
        try {
            /** @var array{messageId?: string} $payload */
            $payload = Json::decode($body);
        } catch (Throwable $e) {
            $this->logger->warning('PeppolInbound: invalid JSON body — ' . $e->getMessage());
            return '';
        }

        $messageId = $payload['messageId'] ?? '';
        if ($messageId === '') {
            $this->logger->warning('PeppolInbound: missing messageId in delivery callback.');
        }
        return $messageId;
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
