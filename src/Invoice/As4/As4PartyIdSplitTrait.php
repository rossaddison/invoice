<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Splits an AS4 `senderPartyId` ("{schemeId}:{endpointId}", e.g.
 * "0088:1234567890123") into its two halves — shared by every
 * As4PayloadHandlerInterface implementation (As4InvoiceImportService,
 * As4OrderImportService) that needs to look up the sender in
 * ClientPeppolRepositoryInterface::findByEndpointId(), purely to avoid
 * duplicating this one small method in each.
 */
trait As4PartyIdSplitTrait
{
    /** @return array{string, string} */
    private function splitPartyId(string $partyId): array
    {
        $pos = strpos($partyId, ':');
        if ($pos === false) {
            return ['', $partyId];
        }
        return [substr($partyId, 0, $pos), substr($partyId, $pos + 1)];
    }
}
