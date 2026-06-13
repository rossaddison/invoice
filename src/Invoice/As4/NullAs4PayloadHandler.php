<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use Psr\Log\LoggerInterface;

final class NullAs4PayloadHandler implements As4PayloadHandlerInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    #[\Override]
    public function handle(string $payloadXml, string $senderPartyId, string $action): void
    {
        $this->logger->info('AS4 inbound payload received — no handler registered', [
            'senderPartyId' => $senderPartyId,
            'action'        => $action,
            'payloadBytes'  => strlen($payloadXml),
        ]);
    }
}
