<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Creates pre-configured PMode objects for standard Peppol BIS Billing 3.0 exchanges.
 *
 * All methods produce OneWay/Push P-Modes with standard ebMS3 initiator/responder roles.
 *
 * @psalm-suppress UnusedClass
 */
final class PeppolPModeFactory
{
    public static function billingInvoice(
        string $senderPartyId,
        string $recipientPartyId,
        string $responderProtocolAddress,
    ): PMode {
        return self::billingBase(
            $senderPartyId,
            $recipientPartyId,
            $responderProtocolAddress,
            As4Constants::PEPPOL_DOCTYPE_INVOICE_BIS3,
        );
    }

    public static function billingCreditNote(
        string $senderPartyId,
        string $recipientPartyId,
        string $responderProtocolAddress,
    ): PMode {
        return self::billingBase(
            $senderPartyId,
            $recipientPartyId,
            $responderProtocolAddress,
            As4Constants::PEPPOL_DOCTYPE_CREDITNOTE_BIS3,
        );
    }

    private static function billingBase(
        string $senderPartyId,
        string $recipientPartyId,
        string $responderProtocolAddress,
        string $documentTypeId,
    ): PMode {
        $pMode = new PMode(
            $senderPartyId,
            $recipientPartyId,
            $responderProtocolAddress,
            As4Constants::PEPPOL_PROCESS_BIS3,
            $documentTypeId,
        );
        $pMode->getParties()
            ->setInitiatorRole(As4Constants::ROLE_INITIATOR)
            ->setResponderRole(As4Constants::ROLE_RESPONDER);
        return $pMode;
    }
}
