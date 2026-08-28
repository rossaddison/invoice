<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;

/**
 * One Peppol Access Point transport, behind a provider-agnostic contract —
 * implemented today by OxalisPeppolSendService (self-hosted AS4 gateway)
 * and StorecovePeppolSendService (managed Access Point API), selected at
 * runtime by PeppolSendServiceRouter via the peppol_access_point_provider
 * setting. See project_storecove_client_openapi_pivot memory for why a
 * second provider matters (redundancy, not just a preference) and
 * docs/OXALIS_INTEGRATION.md for the Oxalis side of this contract.
 */
interface PeppolSendServiceInterface
{
    /**
     * Transmit a UBL XML document to a Peppol participant.
     *
     * @param int    $invId       Invoice entity ID (for audit trail)
     * @param string $ublXml      Raw UBL 2.4 XML string
     * @param string $recipientId Peppol participant identifier, "scheme:id" (e.g. 0088:1234567890123)
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
    ): PeppolMessage;

    /**
     * Increment retry count and re-attempt a previously FAILED message.
     * The caller is responsible for re-supplying the original UBL XML.
     */
    public function retry(
        PeppolMessage $message,
        string $ublXml,
    ): PeppolMessage;
}
