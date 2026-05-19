<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use DateTimeImmutable;
use GuzzleHttp\Psr7\MultipartStream;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Sends a UBL 2.1 XML document to the Peppol network via the Oxalis AS4
 * gateway REST API.  Oxalis must be running and reachable at $oxalisBaseUrl.
 *
 * Every send attempt is persisted as a PeppolMessage *before* the HTTP call
 * so that a crash between QUEUED and SENT leaves a recoverable record.
 *
 * Status lifecycle: QUEUED → SENT → (DELIVERED by Oxalis callback)
 *                                 → FAILED / RETRYING on error
 *
 * Oxalis multipart/form-data fields:
 *   file          — raw UBL XML (application/xml)
 *   RecipientId   — iso6523-actorid-upis::<participantId>
 *   DocumentTypeId — Peppol document type URN (passed through)
 *   ProcessTypeId  — cenbii-procid-ubl::<processId>
 *   SenderId      — iso6523-actorid-upis::<senderParticipantId>  (optional)
 */
final class PeppolSendService
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly PeppolMessageRepositoryInterface $pmR,
        private readonly string $oxalisBaseUrl,
        private readonly string $senderParticipantId = '',
    ) {}

    /**
     * Transmit a UBL XML document to a Peppol participant.
     *
     * @param int    $invId       Invoice entity ID (for audit trail)
     * @param string $ublXml      Raw UBL 2.1 XML string
     * @param string $recipientId Peppol participant identifier (e.g. 0088:1234567890123)
     * @param string $documentTypeId  Peppol document type URN (defaults to BIS Billing 3.0 Invoice)
     * @param string $processId       Peppol process URN (defaults to BIS Billing 3.0)
     */
    public function send(
        int $invId,
        string $ublXml,
        string $recipientId,
        string $documentTypeId =
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        string $processId =
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
    ): PeppolMessage {
        $message = new PeppolMessage(
            inv_id: $invId,
            recipient_id: $recipientId,
            document_type_id: $documentTypeId,
            process_id: $processId,
            status: 'QUEUED',
        );
        $message->setUblXml($ublXml);
        $this->pmR->save($message);

        try {
            $parts = [
                [
                    'name'     => 'file',
                    'contents' => $ublXml,
                    'filename' => 'invoice.xml',
                    'headers'  => ['Content-Type' => 'application/xml'],
                ],
                ['name' => 'RecipientId',    'contents' => $this->toOxalisParticipantId($recipientId)],
                ['name' => 'DocumentTypeId', 'contents' => $documentTypeId],
                ['name' => 'ProcessTypeId',  'contents' => $this->toOxalisProcessId($processId)],
            ];
            if ($this->senderParticipantId !== '') {
                $parts[] = ['name' => 'SenderId', 'contents' => $this->toOxalisParticipantId($this->senderParticipantId)];
            }

            $multipart = new MultipartStream($parts);

            $request = $this->requestFactory
                ->createRequest('POST', $this->oxalisBaseUrl . '/outbound/send')
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $multipart->getBoundary())
                ->withHeader('Accept', 'application/json')
                ->withBody($multipart);

            $response     = $this->httpClient->sendRequest($request);
            $statusCode   = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            if ($statusCode >= 400) {
                $message->setStatus('FAILED');
                $message->setErrorMessage('Oxalis HTTP ' . $statusCode . ': ' . $responseBody);
            } else {
                /** @var array{messageId?: string, instanceIdentifier?: string} $decoded */
                $decoded = (array) json_decode($responseBody, true);
                $messageId = $decoded['messageId'] ?? $decoded['instanceIdentifier'] ?? '';

                $message->setStatus('SENT');
                $message->setMessageId($messageId);
                $message->setSentAt(new DateTimeImmutable());
            }
        } catch (ClientExceptionInterface $e) {
            $message->setStatus('FAILED');
            $message->setErrorMessage($e->getMessage());
        }

        $this->pmR->save($message);
        return $message;
    }

    /**
     * Increment retry count and re-attempt a previously FAILED message.
     * The caller is responsible for re-supplying the original UBL XML.
     */
    public function retry(
        PeppolMessage $message,
        string $ublXml,
    ): PeppolMessage {
        $message->incrementRetryCount();
        $message->setStatus('RETRYING');
        $this->pmR->save($message);

        return $this->send(
            $message->getInvId() ?? 0,
            $ublXml,
            (string) $message->getRecipientId(),
            (string) $message->getDocumentTypeId(),
            (string) $message->getProcessId(),
        );
    }

    /**
     * Prepends the Peppol AS4 actor scheme if not already present.
     * '0088:1234567890123' → 'iso6523-actorid-upis::0088:1234567890123'
     */
    private function toOxalisParticipantId(string $id): string
    {
        $prefix = 'iso6523-actorid-upis::';
        return str_starts_with($id, $prefix) ? $id : $prefix . $id;
    }

    /**
     * Prepends the Peppol process scheme if not already present.
     * 'urn:fdc:...' → 'cenbii-procid-ubl::urn:fdc:...'
     */
    private function toOxalisProcessId(string $processId): string
    {
        $prefix = 'cenbii-procid-ubl::';
        return str_starts_with($processId, $prefix) ? $processId : $prefix . $processId;
    }
}
