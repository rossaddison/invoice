<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves a Peppol receiver's AS4 endpoint by querying their SMP (Service Metadata Publisher).
 *
 * Implements OASIS BDXR SMP 1.0 over HTTP (no DNS/SML lookup — caller supplies the SMP base URL).
 *
 * URL pattern: {smpBaseUrl}/{iso6523-actorid-upis::{participantId}}/services/{documentTypeId}
 *
 * @psalm-suppress UnusedClass
 */
final class As4SmpResolver implements As4SmpResolverInterface
{
    private const string SMP_NS        = As4Constants::SMP_NS;
    private const string PARTICIPANT_SCHEME = As4Constants::SMP_PARTICIPANT_SCHEME;

    private const string PEM_HEADER = '-----BEGIN CERTIFICATE-----';
    private const string PEM_FOOTER = '-----END CERTIFICATE-----';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly LoggerInterface $logger,
        /** Base URL of the SMP, e.g. "https://smp.test.peppol.eu" */
        private readonly string $smpBaseUrl,
        /** Transport profile to match in the SMP response */
        private readonly string $transportProfile = As4Constants::PEPPOL_TRANSPORT_PROFILE,
    ) {}

    /**
     * Resolves the AS4 endpoint for the participant described in $query.
     *
     * @throws \UnexpectedValueException                  On unparseable or incomplete SMP XML
     * @throws \Psr\Http\Client\ClientExceptionInterface  On transport failure
     */
    #[\Override]
    public function resolve(As4SmpQuery $query): As4SmpEndpoint
    {
        $url = $this->buildUrl($query->participantId, $query->documentTypeId);
        $this->logger->info('Querying SMP', ['url' => $url, 'participantId' => $query->participantId]);

        $xml      = $this->fetchXml($url);
        $endpoint = $this->parseSmpXml($xml, $query->processId);

        $this->logger->info('SMP endpoint resolved', ['endpointUrl' => $endpoint->endpointUrl]);
        return $endpoint;
    }

    // ── URL construction ──────────────────────────────────────────────────────

    private function buildUrl(string $participantId, string $documentTypeId): string
    {
        $participantEncoded = urlencode(self::PARTICIPANT_SCHEME . '::' . $participantId);
        $doctypeEncoded     = urlencode($documentTypeId);
        return sprintf('%s/%s/services/%s', $this->smpBaseUrl, $participantEncoded, $doctypeEncoded);
    }

    // ── HTTP fetch ────────────────────────────────────────────────────────────

    private function fetchXml(string $url): string
    {
        $request  = $this->requestFactory
            ->createRequest('GET', $url)
            ->withHeader('Accept', 'application/xml');
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException(sprintf(
                'SMP returned HTTP %d for URL: %s',
                $response->getStatusCode(),
                $url,
            ));
        }

        return (string) $response->getBody();
    }

    // ── XML parsing ───────────────────────────────────────────────────────────

    private function parseSmpXml(string $xml, string $processId): As4SmpEndpoint
    {
        $doc   = $this->loadDocument($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('smp', self::SMP_NS);

        $endpointEl      = $this->findEndpointElement($xpath, $processId);
        $endpointUrl     = $this->nodeText($xpath, 'smp:EndpointURI', $endpointEl);
        $certBase64      = $this->nodeText($xpath, 'smp:Certificate', $endpointEl);
        $transportProfile = $endpointEl->getAttribute('transportProfile');

        if ($endpointUrl === '' || $certBase64 === '') {
            throw new \UnexpectedValueException(
                'SMP Endpoint element is missing EndpointURI or Certificate'
            );
        }

        return new As4SmpEndpoint(
            endpointUrl:      $endpointUrl,
            certificatePem:   $this->toPem($certBase64),
            transportProfile: $transportProfile,
        );
    }

    private function loadDocument(string $xml): DOMDocument
    {
        $doc        = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded     = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if (!$loaded) {
            throw new \UnexpectedValueException('SMP response body is not valid XML');
        }

        return $doc;
    }

    /**
     * Finds the Endpoint element whose transportProfile matches $this->transportProfile.
     * Prefers the endpoint under the process that matches $processId; falls back to the
     * first matching endpoint in the document when no process match is found.
     */
    private function findEndpointElement(DOMXPath $xpath, string $processId): DOMElement
    {
        // transportProfile is a class constant — safe to embed in XPath
        $nodes = $xpath->query(
            '//smp:Endpoint[@transportProfile="' . $this->transportProfile . '"]'
        );

        if ($nodes === false || $nodes->length === 0) {
            throw new \UnexpectedValueException(sprintf(
                'No Endpoint with transportProfile "%s" found in SMP response',
                $this->transportProfile,
            ));
        }

        // Prefer the endpoint under the requested process (PHP-level filter avoids XPath injection)
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement && $this->isUnderProcess($xpath, $node, $processId)) {
                return $node;
            }
        }

        // Fallback: first endpoint regardless of process
        $first = $nodes->item(0);
        if (!$first instanceof DOMElement) {
            throw new \UnexpectedValueException('SMP Endpoint node is not a DOMElement');
        }
        return $first;
    }

    /** Returns true when $endpoint is a descendant of an smp:Process whose identifier equals $processId. */
    private function isUnderProcess(DOMXPath $xpath, DOMElement $endpoint, string $processId): bool
    {
        $nodes = $xpath->query('ancestor::smp:Process/smp:ProcessIdentifier', $endpoint);
        if ($nodes === false) {
            return false;
        }
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement && trim($node->textContent) === $processId) {
                return true;
            }
        }
        return false;
    }

    private function nodeText(DOMXPath $xpath, string $expr, DOMElement $context): string
    {
        $nodes = $xpath->query($expr, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        $node = $nodes->item(0);
        return $node instanceof DOMElement ? trim($node->textContent) : '';
    }

    // ── Certificate conversion ────────────────────────────────────────────────

    /**
     * Converts a base64 DER certificate (as stored in SMP Certificate elements)
     * to PEM format with 64-character line wrapping.
     */
    private function toPem(string $base64): string
    {
        // Strip any existing PEM headers and whitespace to get raw base64
        $raw = (string) preg_replace('/-----[^-]+-----|\\s/', '', $base64);
        if ($raw === '') {
            throw new \UnexpectedValueException('Certificate field in SMP response is empty');
        }
        return self::PEM_HEADER . "\n"
            . chunk_split($raw, 64, "\n")
            . self::PEM_FOOTER . "\n";
    }
}
