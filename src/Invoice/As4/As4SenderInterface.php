<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;

interface As4SenderInterface
{
    /**
     * Send a signed AS4 envelope to the given endpoint.
     *
     * @param array<string, string> $parts Attachment parts keyed by Content-ID
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface On network failure
     */
    public function send(
        string $endpoint,
        DOMDocument $envelope,
        array $parts = [],
    ): As4HttpResponse;
}
