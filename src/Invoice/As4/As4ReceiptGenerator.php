<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Generates an ebMS3 Receipt SOAP response for an inbound UserMessage.
 *
 * Satisfies the Non-Repudiation of Receipt (NRR) requirement of
 * eDelivery AS4 2.0 §5.1.8 by embedding a SHA-256 digest of the
 * original received SOAP envelope inside the eb:Receipt element.
 *
 * @psalm-suppress UnusedClass
 */
final class As4ReceiptGenerator implements As4ReceiptGeneratorInterface
{
    #[\Override]
    public function generate(string $inboundMessageId, string $xmlBody): string
    {
        $builder  = new As4MessageBuilder();
        $signalId = 'receipt-' . bin2hex(random_bytes(8)) . '@as4.local';

        $builder->addTimestamp();
        $builder->addSignalMessage($signalId, $inboundMessageId, [
            'digestValue' => base64_encode(hash('sha256', $xmlBody, true)),
        ]);

        return $builder->getXml();
    }
}
