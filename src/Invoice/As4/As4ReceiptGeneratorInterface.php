<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4ReceiptGeneratorInterface
{
    /**
     * Build an ebMS3 Receipt SOAP response for a received UserMessage.
     *
     * The receipt includes a ds:Reference with the SHA-256 digest of the
     * original SOAP envelope body, satisfying the Non-Repudiation of Receipt
     * requirement of eDelivery AS4 2.0 §5.1.8.
     *
     * @param string $inboundMessageId  eb:MessageId from the received UserMessage
     * @param string $xmlBody           Raw SOAP envelope body (digest source)
     * @return string                   Complete SOAP 1.2 XML string
     */
    public function generate(string $inboundMessageId, string $xmlBody): string;
}
