<?php

declare(strict_types=1);

namespace App\Invoice\As4;

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

        $signed   = $this->signer->sign($this->envelopeBuilder->build($params));
        $response = $this->httpTransport->send(
            $endpoint->endpointUrl,
            $signed,
            [new As4MimePart(
                contentId:   $payloadContentId,
                contentType: As4Constants::MIME_XML,
                body:        $request->payloadXml,
            )],
        );

        $this->logger->info('AS4 dispatch: response received', [
            'statusCode' => $response->statusCode,
            'success'    => $response->isSuccess(),
        ]);

        return new As4DispatchResult(
            messageId:  $messageId,
            httpStatus: $response->statusCode,
            signal:     $this->receiptParser->parse($response->body),
            success:    $response->isSuccess(),
        );
    }

    private function generateId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(8)) . '@as4.local';
    }
}
