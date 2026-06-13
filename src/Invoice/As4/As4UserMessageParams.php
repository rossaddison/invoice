<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object carrying all parameters for As4MessageBuilder::addUserMessage().
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4UserMessageParams
{
    /** @param array<string, string> $properties */
    public function __construct(
        public string $messageId,
        public string $conversationId,
        public string $service,
        public string $action,
        public string $senderPartyId,
        public string $senderRole,
        public string $receiverPartyId,
        public string $receiverRole,
        public ?string $refToMessageId = null,
        public array $properties = [],
    ) {}
}
