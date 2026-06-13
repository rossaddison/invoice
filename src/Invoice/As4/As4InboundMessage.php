<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Parsed inbound AS4 message.
 *
 * Produced by As4Receiver::receive() for each valid inbound POST to /as4/receive.
 * The type field controls which handler in As4ReceiveController processes the message.
 */
final class As4InboundMessage
{
    /**
     * @param string[] $payloads  Map of contentId → body content
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $messageId = null,
        public readonly ?string $conversationId = null,
        public readonly ?string $refToMessageId = null,
        public readonly ?string $service = null,
        public readonly ?string $action = null,
        public readonly ?string $senderPartyId = null,
        public readonly ?string $receiverPartyId = null,
        public readonly ?string $digestValue = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorShortDescription = null,
        public readonly ?string $errorDescription = null,
        public readonly ?string $errorCategory = null,
        public readonly array $payloads = [],
        public readonly string $xmlBody = ''
    ) {}

    public function isReceipt(): bool { return $this->type === 'Receipt'; }
    public function isError(): bool { return $this->type === 'Error'; }
    public function isUserMessage(): bool { return $this->type === 'UserMessage'; }
}
