<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use Psr\Log\LoggerInterface;

/**
 * AS4 message sender — high-level orchestrator.
 *
 * Accepts a serialized SOAP envelope string and optional raw attachment parts,
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
     * Send a serialized AS4 message to the given endpoint.
     *
     * @param array<string, string> $parts  Attachment parts keyed by Content-ID
     *
     * @throws \InvalidArgumentException  When $soapMessage is not valid XML
     * @throws \Psr\Http\Client\ClientExceptionInterface  On network failure
     */
    public function send(
        string $endpoint,
        string $soapMessage,
        array $parts = [],
    ): As4HttpResponse {
        $doc     = $this->parseEnvelope($soapMessage);
        $attachments = [];
        foreach ($parts as $contentId => $data) {
            $attachments[] = new As4MimePart(
                contentId:   $contentId,
                contentType: As4Constants::MIME_XML,
                body:        $data,
            );
        }

        $this->logger->info('Sending AS4 message', ['endpoint' => $endpoint]);

        $response = $this->httpClient->send($endpoint, $doc, $attachments);

        $this->logger->info('AS4 transmission response', [
            'statusCode' => $response->statusCode,
            'bodySize'   => strlen($response->body),
        ]);

        return $response;
    }

    private function parseEnvelope(string $xml): DOMDocument
    {
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if (!$loaded) {
            throw new \InvalidArgumentException('soapMessage is not well-formed XML');
        }

        return $doc;
    }
}
