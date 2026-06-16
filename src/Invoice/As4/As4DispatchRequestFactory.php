<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Creates As4DispatchRequest objects from P-Mode configuration.
 *
 * @psalm-suppress UnusedClass
 */
final class As4DispatchRequestFactory
{
    /**
     * Builds an As4DispatchRequest from a PMode and the document payload.
     * The recipient party ID, document type, and process are taken from the PMode.
     */
    public static function fromPMode(PMode $pMode, string $payloadXml): As4DispatchRequest
    {
        return new As4DispatchRequest(
            recipientPartyId: $pMode->getParties()->getResponderParty(),
            documentTypeId:   $pMode->getAction(),
            processId:        $pMode->getService(),
            payloadXml:       $payloadXml,
        );
    }
}
