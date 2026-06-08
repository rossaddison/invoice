<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

final class InvEmailStage1Data
{
    public function __construct(
        public readonly array $from,
        public readonly string $to,
        public readonly string $subject,
        public readonly string $emailBody,
        public readonly string $cc,
        public readonly string $bcc,
        public readonly array $attachFiles,
    ) {
    }
}
