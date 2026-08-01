<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\WhatsApp;

final class WhatsAppSendResult
{
    public function __construct(
        public readonly bool $sent,
        public readonly ?string $messageId,
        public readonly ?string $error,
    ) {
    }
}
