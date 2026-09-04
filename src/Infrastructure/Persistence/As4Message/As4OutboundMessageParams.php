<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

/**
 * Constructor arguments for {@see As4MessageFactory::fromOutbound()}.
 *
 * Deliberately omits senderRole/receiverRole, unlike {@see As4MessageParams}:
 * an outbound message's roles are always initiator/responder, so the
 * factory fills those in itself rather than asking every caller to repeat
 * {@see \App\Invoice\As4\As4Constants::ROLE_INITIATOR}/ROLE_RESPONDER.
 */
final readonly class As4OutboundMessageParams
{
    public function __construct(
        public string $messageId,
        public string $conversationId,
        public string $senderPartyId,
        public string $receiverPartyId,
        public string $service,
        public string $action,
        public string $receiverEndpoint,
        public string $soapMessage,
    ) {
    }
}
