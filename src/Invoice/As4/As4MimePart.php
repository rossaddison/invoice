<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object representing one MIME part in an AS4 multipart/related message.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4MimePart
{
    public function __construct(
        /** MIME Content-ID without angle brackets, e.g. "invoice@as4.local" */
        public string $contentId,
        /** MIME Content-Type, e.g. "application/xml" */
        public string $contentType,
        /** Raw body bytes */
        public string $body,
    ) {}
}
