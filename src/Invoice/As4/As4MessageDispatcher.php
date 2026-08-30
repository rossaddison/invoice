<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4MessageFactory;
use App\Infrastructure\Persistence\As4Message\As4OutboundMessageParams;
use Psr\Log\LoggerInterface;

/**
 * High-level orchestrator for outbound Peppol AS4 message dispatch.
 *
 * Pipeline:
 *   1. As4SmpResolverInterface    — resolves endpoint via SMP lookup
 *   2. As4EnvelopeBuilderInterface — builds unsigned SOAP 1.2 / ebMS3 envelope
 *   3. As4EnvelopeSignerInterface  — adds WS-Security X.509 signature
 *   4. As4HttpTransportInterface   — sends MIME multipart/related request
 *   5. As4ReceiptParserInterface   — parses ebMS3 receipt or error signal
 *   6. As4MessageRepositoryInterface — persists the outbound record so
 *      As4RetryEngine has something to retry and a later async
 *      Receipt/Error (see As4ReceiveController::handleInboundReceipt())
 *      has a row to find by messageId
 *
 * @psalm-suppress UnusedClass
 */
final class As4MessageDispatcher
{
    public function __construct(
        private readonly As4SmpResolverInterface $smpResolver,
        private readonly As4EnvelopeBuilderInterface $envelopeBuilder,
        private readonly As4EnvelopeSignerInterface $signer,
        private readonly As4HttpTransportInterface $httpTransport,
        private readonly As4ReceiptParserInterface $receiptParser,
        private readonly As4MessageRepositoryInterface $repository,
        /** Sender's Peppol participant ID in "scheme:value" form, e.g. "0088:1234567890123" */
        private readonly string $senderPartyId,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Dispatches a UBL document to the Peppol receiver identified in $request.
     *
     * @throws \UnexpectedValueException                  When SMP resolution or XML serialization fails
     * @throws \Psr\Http\Client\ClientExceptionInterface  On network failure
     */
    public function dispatch(As4DispatchRequest $request): As4DispatchResult
    {
        $messageId        = $request->messageId        ?? $this->generateId('msg');
        $conversationId   = $request->conversationId   ?? $this->generateId('conv');
        $payloadContentId = $request->payloadContentId ?? $this->generateId('payload');

        $endpoint = $this->smpResolver->resolve(new As4SmpQuery(
            participantId:  $request->recipientPartyId,
            documentTypeId: $request->documentTypeId,
            processId:      $request->processId,
        ));

        $this->logger->info('AS4 dispatch: SMP endpoint resolved', [
            'endpointUrl'      => $endpoint->endpointUrl,
            'recipientPartyId' => $request->recipientPartyId,
        ]);

        $params = new SoapEnvelopeParams(
            messageId:        $messageId,
            conversationId:   $conversationId,
            senderPartyId:    $this->senderPartyId,
            receiverPartyId:  $request->recipientPartyId,
            service:          $request->processId,
            action:           $request->documentTypeId,
            payloadXml:       $request->payloadXml,
            payloadContentId: $payloadContentId,
        );

        $signed  = $this->signer->sign($this->envelopeBuilder->build($params));
        $rawXml  = $signed->saveXML();
        $soapXml = $rawXml === false ? '' : $rawXml;

        $entity = As4MessageFactory::fromOutbound(new As4OutboundMessageParams(
            messageId:        $messageId,
            conversationId:   $conversationId,
            senderPartyId:    $this->senderPartyId,
            receiverPartyId:  $request->recipientPartyId,
            service:          $request->processId,
            action:           $request->documentTypeId,
            receiverEndpoint: $endpoint->endpointUrl,
            soapMessage:      $soapXml,
        ));
        $entity->recordAttempt();
        $this->repository->save($entity);

        try {
            $response = $this->httpTransport->send(
                $endpoint->endpointUrl,
                $signed,
                [new As4MimePart(
                    contentId:   $payloadContentId,
                    contentType: As4Constants::MIME_XML,
                    body:        $request->payloadXml,
                )],
            );
        } catch (\Throwable $e) {
            $entity->markFailed('TRANSPORT_ERROR', $e->getMessage());
            $this->repository->save($entity);
            throw $e;
        }

        $this->logger->info('AS4 dispatch: response received', [
            'statusCode' => $response->statusCode,
            'success'    => $response->isSuccess(),
        ]);

        $signal = $this->receiptParser->parse($response->body, $response->contentType);
        $this->applyOutcome($entity, $response, $signal);
        $this->repository->save($entity);

        return new As4DispatchResult(
            messageId:  $messageId,
            httpStatus: $response->statusCode,
            signal:     $signal,
            success:    $response->isSuccess(),
        );
    }

    /**
     * Advances $entity's lifecycle state to match the outcome of this send —
     * mirrors As4RetryEngine::handleSuccessResponse()'s branching so a
     * first-attempt send and a retried send end up in the same states.
     */
    private function applyOutcome(
        As4Message $entity,
        As4HttpResponse $response,
        As4ReceiptSignal|As4ErrorSignal|null $signal,
    ): void {
        if (!$response->isSuccess()) {
            $entity->markFailed('HTTP_' . $response->statusCode, 'AS4 endpoint returned error response');
            return;
        }

        if ($signal instanceof As4ReceiptSignal) {
            $entity->markReceiptReceived($signal->messageId);
            return;
        }

        if ($signal instanceof As4ErrorSignal && $signal->isFailure()) {
            $entity->markFailed($signal->errorCode, $signal->shortDescription);
            return;
        }

        // null (async 202) or a warning-level error signal — transport
        // accepted the message, a Receipt/Error may still arrive later.
        $entity->markSent();
    }

    private function generateId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(8)) . '@as4.local';
    }
}
