<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

final class QuoteEmailStage1Data
{
    public function __construct(
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly string $to,
        public readonly string $subject,
        public readonly string $emailBody,
        public readonly string $cc,
        public readonly string $bcc,
        public readonly array $attachFiles,
    ) {
    }
}
