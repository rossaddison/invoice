<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use Psr\Log\LoggerInterface;

/**
 * AS4 message sender — high-level orchestrator.
 *
 * Accepts a signed DOMDocument envelope and optional raw attachment parts,
 * converts them into typed value objects, and delegates HTTP transport to
 * As4HttpClient which applies the correct Peppol multipart/related MIME packaging.
 *
 * @psalm-suppress UnusedClass
 */
final class As4Sender
{
    public function __construct(
        private readonly As4HttpClient $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send a signed AS4 envelope to the given endpoint.
     *
     * @param array<string, string> $parts  Attachment parts keyed by Content-ID
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface  On network failure
     */
    public function send(
        string $endpoint,
        DOMDocument $envelope,
        array $parts = [],
    ): As4HttpResponse {
        $attachments = [];
        foreach ($parts as $contentId => $data) {
            $attachments[] = new As4MimePart(
                contentId:   $contentId,
                contentType: As4Constants::MIME_XML,
                body:        $data,
            );
        }

        $this->logger->info('Sending AS4 message', ['endpoint' => $endpoint]);

        $response = $this->httpClient->send($endpoint, $envelope, $attachments);

        $this->logger->info('AS4 transmission response', [
            'statusCode' => $response->statusCode,
            'bodySize'   => strlen($response->body),
        ]);

        return $response;
    }
}
