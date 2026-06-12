<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Low-level AS4 HTTP transport.
 *
 * Serializes a signed SOAP 1.2 envelope and optional payload attachments into a
 * multipart/related body (RFC 2387 / eDelivery AS4 2.0 profile §2.2.3), then
 * sends it via the injected PSR-18 HTTP client.
 *
 * MIME structure produced:
 *   Content-Type: multipart/related; type="application/soap+xml"; boundary="…"; start="<…>"
 *   --boundary
 *   Content-Type: application/soap+xml; charset=UTF-8
 *   Content-ID: <soappart@as4.local>
 *   [SOAP 1.2 envelope XML]
 *   --boundary
 *   Content-Type: application/xml          (or per As4MimePart::$contentType)
 *   Content-ID: <invoice@sender.com>
 *   [UBL payload XML]
 *   --boundary--
 *
 * @psalm-suppress UnusedClass
 */
final class As4HttpClient implements As4HttpTransportInterface
{
    private const string SOAP_CONTENT_TYPE = 'application/soap+xml';
    private const string ENVELOPE_CID      = 'soappart@as4.local';
    private const string CRLF              = "\r\n";

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Sends the signed envelope to the given endpoint.
     *
     * @param As4MimePart[] $attachments  Additional MIME parts (e.g. UBL invoice XML)
     *
     * @throws \UnexpectedValueException  When the envelope cannot be serialized to XML
     * @throws \Psr\Http\Client\ClientExceptionInterface  On network/transport failure
     */
    public function send(
        string $endpointUrl,
        DOMDocument $signedEnvelope,
        array $attachments = [],
    ): As4HttpResponse {
        $envelopeXml = $signedEnvelope->saveXML();
        if ($envelopeXml === false) {
            throw new \UnexpectedValueException('Failed to serialize signed SOAP envelope to XML');
        }

        $boundary    = 'MIMEBoundary_' . bin2hex(random_bytes(16));
        $body        = $this->buildMimeBody($boundary, $envelopeXml, $attachments);
        $contentType = sprintf(
            '%s; type="%s"; boundary="%s"; start="<%s>"',
            'multipart/related',
            self::SOAP_CONTENT_TYPE,
            $boundary,
            self::ENVELOPE_CID,
        );

        $request = $this->requestFactory
            ->createRequest('POST', $endpointUrl)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('SOAPAction', '')
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->httpClient->sendRequest($request);

        return new As4HttpResponse(
            statusCode: $response->getStatusCode(),
            body:       (string) $response->getBody(),
        );
    }

    /**
     * @param As4MimePart[] $attachments
     */
    private function buildMimeBody(
        string $boundary,
        string $envelopeXml,
        array $attachments,
    ): string {
        $crlf = self::CRLF;

        $mime  = "--{$boundary}{$crlf}";
        $mime .= 'Content-Type: ' . self::SOAP_CONTENT_TYPE . "; charset=UTF-8{$crlf}";
        $mime .= "Content-Transfer-Encoding: 8bit{$crlf}";
        $mime .= 'Content-ID: <' . self::ENVELOPE_CID . ">{$crlf}";
        $mime .= "{$crlf}";
        $mime .= $envelopeXml;
        $mime .= "{$crlf}";

        foreach ($attachments as $part) {
            $mime .= "--{$boundary}{$crlf}";
            $mime .= "Content-Type: {$part->contentType}{$crlf}";
            $mime .= "Content-Transfer-Encoding: 8bit{$crlf}";
            $mime .= "Content-ID: <{$part->contentId}>{$crlf}";
            $mime .= "{$crlf}";
            $mime .= $part->body;
            $mime .= "{$crlf}";
        }

        $mime .= "--{$boundary}--{$crlf}";
        return $mime;
    }
}
