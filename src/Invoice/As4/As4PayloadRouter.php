<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use Psr\Log\LoggerInterface;

/**
 * Routes an inbound AS4 payload to the handler for its actual document
 * type. As4UserMessageHandlerService only ever holds one
 * As4PayloadHandlerInterface (see config/common/di/as4.php) — before
 * Ordering support existed that single handler was always
 * As4InvoiceImportService, since Invoice/CreditNote were the only
 * documents this app ever received. This class is now that one handler,
 * and delegates by the payload XML's own root element rather than the
 * AS4 `action` string — Peppol's `action` is a busdox document-type URN
 * that already encodes this, but reading it back out of the XML itself
 * needs no coupling to how a given trading partner happens to format
 * that header.
 */
final class As4PayloadRouter implements As4PayloadHandlerInterface
{
    public function __construct(
        private readonly As4InvoiceImportService $invoiceImportService,
        private readonly As4OrderImportService $orderImportService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function handle(string $payloadXml, string $senderPartyId, string $action): void
    {
        match ($this->rootElementName($payloadXml)) {
            'Invoice', 'CreditNote' => $this->invoiceImportService->handle($payloadXml, $senderPartyId, $action),
            'Order' => $this->orderImportService->handle($payloadXml, $senderPartyId, $action),
            default => $this->logUnhandled($payloadXml, $senderPartyId, $action),
        };
    }

    private function rootElementName(string $payloadXml): ?string
    {
        $doc  = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok   = $doc->loadXML($payloadXml);
        libxml_use_internal_errors($prev);
        libxml_clear_errors();

        if (!$ok) {
            return null;
        }
        return $doc->documentElement?->localName;
    }

    private function logUnhandled(string $payloadXml, string $senderPartyId, string $action): void
    {
        $this->logger->warning('AS4 payload: unrecognized or unsupported document type — skipped', [
            'senderPartyId'   => $senderPartyId,
            'action'          => $action,
            'rootElementName' => $this->rootElementName($payloadXml),
        ]);
    }
}
