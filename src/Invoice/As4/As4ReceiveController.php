<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP entry point for inbound AS4 messages.
 *
 * Route:  POST /as4/receive
 * Auth:   None — bilateral trading partners call this directly.
 *
 * Handles three message types from the bilateral partner:
 *  - UserMessage  → duplicate check, store record, return ebMS3 Receipt
 *  - Receipt      → update outbound As4Message state to receiptReceived
 *  - Error        → update outbound As4Message state to failed
 *
 * @psalm-suppress UnusedClass
 */
final class As4ReceiveController
{
    public function __construct(
        private readonly As4Receiver $receiver,
        private readonly As4UserMessageHandlerInterface $userMessageHandler,
        private readonly As4MessageRepositoryInterface $repository,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function receive(Request $request): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $body        = $request->getBody()->getContents();

        try {
            $message = $this->receiver->receive($contentType, $body);
        } catch (As4ParseException $e) {
            $this->logger->error('AS4 receive: failed to parse inbound message', ['error' => $e->getMessage()]);
            return $this->soapFault('Could not parse inbound AS4 message');
        }

        return $this->dispatchMessage($message);
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    private function dispatchMessage(As4InboundMessage $message): Response
    {
        return match (true) {
            $message->isUserMessage() => $this->handleUserMessage($message),
            $message->isReceipt()     => $this->handleInboundReceipt($message),
            $message->isError()       => $this->handleInboundError($message),
            default                   => $this->responseFactory->createResponse(200),
        };
    }

    private function handleUserMessage(As4InboundMessage $message): Response
    {
        return $this->soapResponse($this->userMessageHandler->handle($message));
    }

    private function handleInboundReceipt(As4InboundMessage $signal): Response
    {
        $refId    = $signal->refToMessageId ?? '';
        $outbound = $refId !== '' ? $this->repository->findByMessageId($refId) : null;

        if ($outbound !== null) {
            $outbound->markReceiptReceived($signal->messageId ?? '');
            $this->repository->save($outbound);
            $this->logger->info('AS4 receive: async Receipt applied to outbound message', [
                'refToMessageId' => $refId,
            ]);
        } else {
            $this->logger->warning('AS4 receive: async Receipt has unknown refToMessageId', [
                'refToMessageId' => $refId,
            ]);
        }

        return $this->responseFactory->createResponse(200);
    }

    private function handleInboundError(As4InboundMessage $signal): Response
    {
        $refId    = $signal->refToMessageId ?? '';
        $outbound = $refId !== '' ? $this->repository->findByMessageId($refId) : null;

        if ($outbound !== null) {
            $outbound->markFailed(
                $signal->errorCode ?? 'EBMS:0001',
                $signal->errorShortDescription ?? 'Inbound error signal'
            );
            $this->repository->save($outbound);
            $this->logger->error('AS4 receive: inbound Error applied to outbound message', [
                'refToMessageId'   => $refId,
                'errorCode'        => $signal->errorCode,
                'shortDescription' => $signal->errorShortDescription,
            ]);
        } else {
            $this->logger->warning('AS4 receive: inbound Error has unknown refToMessageId', [
                'refToMessageId' => $refId,
            ]);
        }

        return $this->responseFactory->createResponse(200);
    }

    // ── Response helpers ──────────────────────────────────────────────────────

    private function soapResponse(string $xml): Response
    {
        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', As4Constants::MIME_SOAP)
            ->withBody($this->streamFactory->createStream($xml));
    }

    private function soapFault(string $reason): Response
    {
        $escaped = htmlspecialchars($reason, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<env:Envelope xmlns:env="' . As4Constants::SOAP_NS . '">'
            . '<env:Body><env:Fault>'
            . '<env:Code><env:Value>env:Receiver</env:Value></env:Code>'
            . '<env:Reason><env:Text xml:lang="en">' . $escaped . '</env:Text></env:Reason>'
            . '</env:Fault></env:Body></env:Envelope>';

        return $this->responseFactory
            ->createResponse(500)
            ->withHeader('Content-Type', As4Constants::MIME_SOAP)
            ->withBody($this->streamFactory->createStream($xml));
    }
}
