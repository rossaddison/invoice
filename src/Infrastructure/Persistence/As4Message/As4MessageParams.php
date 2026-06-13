<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

final readonly class As4MessageParams
{
    public function __construct(
        public string $messageId,
        public string $conversationId,
        public string $senderPartyId,
        public string $senderRole,
        public string $receiverPartyId,
        public string $receiverRole,
        public string $service,
        public string $action,
        public string $receiverEndpoint,
        public string $soapMessage,
    ) {}
}
