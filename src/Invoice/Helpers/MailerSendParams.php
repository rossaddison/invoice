<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

final class MailerSendParams
{
    public function __construct(
        public readonly string $from_email,
        public readonly string $from_name,
        public readonly string $to,
        public readonly string $subject,
        public readonly string $html_body,
        public readonly array|string|null $cc,
        public readonly array|string|null $bcc,
    ) {
    }
}
