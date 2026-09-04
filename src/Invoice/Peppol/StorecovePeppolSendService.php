<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use RossAddison\StorecoveClient\Exception\StorecoveApiException;
use RossAddison\StorecoveClient\Model\DocumentSubmission;
use RossAddison\StorecoveClient\Model\RawDocumentData;
use RossAddison\StorecoveClient\Model\Routing;
use RossAddison\StorecoveClient\Model\RoutingIdentifier;
use RossAddison\StorecoveClient\Model\SendableDocument;
use RossAddison\StorecoveClient\StorecoveClient;

/**
 * Sends a UBL 2.4 XML document to the Peppol network via Storecove's
 * managed Access Point API (POST /document_submissions), rather than
 * OxalisPeppolSendService's self-hosted AS4 gateway. Same PeppolMessage
 * persistence, same status lifecycle (QUEUED → SENT/FAILED → RETRYING),
 * same $recipientId "scheme:id" convention — a caller behind
 * PeppolSendServiceInterface can't tell the two apart.
 *
 * Uses rossaddison/storecove-client's hand-written, Psalm-clean model
 * classes directly — no generated Api\* class, since this only ever calls
 * the one endpoint. See project_storecove_client_openapi_pivot memory for
 * why that package isn't a generated OpenAPI client.
 */
final class StorecovePeppolSendService implements PeppolSendServiceInterface
{
    public function __construct(
        private readonly PeppolMessageRepositoryInterface $pmR,
        private readonly SR $sR,
    ) {
    }

    #[\Override]
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

        [$scheme, $participantId] = $this->splitRecipientId($recipientId);

        try {
            $client = new StorecoveClient(
                apiKey: (string) $this->sR->decode($this->sR->getSetting('storecove_api_key')),
            );

            $submission = new DocumentSubmission(
                document: new SendableDocument(
                    documentType: 'invoice',
                    rawDocumentData: new RawDocumentData(document: base64_encode($ublXml)),
                ),
                legalEntityId: (int) $this->sR->getSetting('storecove_legal_entity_id'),
                idempotencyGuid: Uuid::uuid4()->toString(),
                routing: new Routing(eIdentifiers: [
                    new RoutingIdentifier(scheme: $scheme, id: $participantId),
                ]),
            );

            $result = $client->createDocumentSubmission($submission);

            $message->setStatus('SENT');
            $message->setMessageId($result->guid);
            $message->setSentAt(new DateTimeImmutable());
        } catch (StorecoveApiException $e) {
            $message->setStatus('FAILED');
            $message->setErrorMessage(
                'Storecove HTTP ' . $e->statusCode . ': ' . $e->responseBody
            );
        } catch (\Throwable $e) {
            $message->setStatus('FAILED');
            $message->setErrorMessage($e->getMessage());
        }

        $this->pmR->save($message);
        return $message;
    }

    #[\Override]
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
     * Splits Trait\Peppol.php's "scheme:id" convention (e.g.
     * "9925:BE0123456789", built from ClientPeppol::getEndpointidSchemeid()
     * . ':' . getEndpointid()) into Storecove's separate scheme/id fields.
     *
     * @return array{0: string, 1: string}
     */
    private function splitRecipientId(string $recipientId): array
    {
        $pos = strpos($recipientId, ':');
        if ($pos === false) {
            return ['', $recipientId];
        }
        return [substr($recipientId, 0, $pos), substr($recipientId, $pos + 1)];
    }
}
