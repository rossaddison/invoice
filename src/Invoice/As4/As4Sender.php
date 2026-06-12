<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * AS4 Message Sender
 *
 * Transmits AS4 messages to Oxalis (or any AS4 access point) via HTTPS
 * with multipart/related MIME packaging per section 3.2.3.
 */
class As4Sender
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->logger = $logger;
    }

    /**
     * Send AS4 message to endpoint.
     *
     * @param array<string, string> $parts    MIME parts: [contentId => binary_data]
     * @param array<string, string> $headers  Additional HTTP headers
     *
     * @throws Exception on network/protocol errors
     */
    public function send(
        string $endpoint,
        string $soapMessage,
        array $parts = [],
        array $headers = []
    ): As4SendResponse {
        try {
            $boundary = 'boundary-' . bin2hex(random_bytes(16));
            $mimeBody = $this->buildMimeMessage($soapMessage, $parts, $boundary);

            $request = $this->requestFactory->createRequest('POST', $endpoint);
            $request = $request->withHeader(
                'Content-Type',
                "multipart/related; type=\"application/xop+xml\"; boundary=\"{$boundary}\""
            );
            $request = $request->withHeader('SOAPAction', '');

            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $stream = $this->streamFactory->createStream($mimeBody);
            $request = $request->withBody($stream);

            $this->logger->info('Sending AS4 message', [
                'endpoint' => $endpoint,
                'messageSize' => strlen($mimeBody),
            ]);

            $response = $this->httpClient->sendRequest($request);

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            $this->logger->info('AS4 transmission response', [
                'statusCode' => $statusCode,
                'bodySize' => strlen($responseBody),
            ]);

            $receiptOrError = null;
            if ($statusCode === 200 && $responseBody !== '') {
                $receiptOrError = $this->parseMultipartResponse($responseBody);
            }

            return new As4SendResponse(
                statusCode: $statusCode,
                success: in_array($statusCode, [200, 202]),
                receiptOrError: $receiptOrError,
                responseBody: $responseBody
            );
        } catch (Exception $e) {
            $this->logger->error('AS4 transmission failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build multipart/related MIME message per RFC 2387.
     *
     * @param array<string, string> $parts
     */
    private function buildMimeMessage(string $soapMessage, array $parts, string $boundary): string
    {
        $mime = "--{$boundary}\r\n";
        $mime .= "Content-Type: application/xop+xml; charset=UTF-8; type=\"application/soap+xml\"\r\n";
        $mime .= "Content-Transfer-Encoding: 8bit\r\n";
        $mime .= "Content-ID: <root.message@as4.example.org>\r\n";
        $mime .= "\r\n";
        $mime .= $soapMessage;
        $mime .= "\r\n";

        foreach ($parts as $contentId => $data) {
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: application/gzip\r\n";
            $mime .= "Content-Transfer-Encoding: binary\r\n";
            $mime .= "Content-ID: <{$contentId}>\r\n";
            $mime .= "Content-Disposition: attachment; filename=\"payload\"\r\n";
            $mime .= "\r\n";
            $mime .= $data;
            $mime .= "\r\n";
        }

        $mime .= "--{$boundary}--\r\n";
        return $mime;
    }

    private function parseMultipartResponse(string $responseBody): ?string
    {
        if (preg_match('/boundary=(["\']?)([^"\';\s]+)\1/', $responseBody, $matches)) {
            $boundary = $matches[2];
            $parts = explode("--{$boundary}", $responseBody);

            if (isset($parts[1])) {
                $part = $parts[1];
                $bodyStart = strpos($part, "\r\n\r\n");
                if ($bodyStart !== false) {
                    return substr($part, $bodyStart + 4);
                }
            }
        }
        return null;
    }
}

/**
 * Response from AS4 transmission attempt.
 */
class As4SendResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly bool $success,
        public readonly ?string $receiptOrError,
        public readonly string $responseBody
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isRetriable(): bool
    {
        return in_array($this->statusCode, [408, 429, 500, 502, 503, 504]);
    }
}
