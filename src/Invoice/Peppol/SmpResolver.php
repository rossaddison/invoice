<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Resolves a Peppol participant's AS4 endpoint and certificate via the
 * SML DNS lookup + SMP HTTP query chain.
 *
 * DNS algorithm (PEPPOL SML specification):
 *   hash  = md5(lowercase("iso6523-actorid-upis::{participantId}"))
 *   cname = B-{hash}.iso6523-actorid-upis.{smlZone}
 *   → CNAME target is the SMP hostname
 *
 * SMP service URL:
 *   http://{smpHost}/{encodedParticipantId}/services/{encodedDocumentTypeId}
 *
 * Set $smpBaseUrl to bypass DNS — useful in development and tests.
 */
final class SmpResolver implements SmpResolverInterface
{
    private const string SCHEME        = 'iso6523-actorid-upis';
    private const string AS4_TRANSPORT = 'peppol-as4-2.0';
    // NOSONAR: php:S5332 — XML namespace URI mandated by the Peppol SMP specification; not a network connection
    private const string NS_PEPPOL     = 'http://busdox.org/serviceMetadata/publishing/1.0/';
    // NOSONAR: php:S5332 — XML namespace URI mandated by the OASIS BDX SMP specification; not a network connection
    private const string NS_BDX        = 'http://docs.oasis-open.org/bdxr/ns/SMP/2016/05';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string $smlZone    = 'edelivery.tech.ec.europa.eu',
        private readonly ?string $smpBaseUrl = null,
    ) {}

    #[\Override]
    public function resolve(string $participantId, string $documentTypeId): SmpEndpoint
    {
        $baseUrl = $this->smpBaseUrl ?? $this->resolveViaDns($participantId);
        $url     = $this->buildServiceUrl($baseUrl, $participantId, $documentTypeId);
        $xml     = $this->fetchXml($url);

        return $this->parseEndpoint($xml);
    }

    #[\Override]
    public function isRegistered(string $participantId, string $documentTypeId): bool
    {
        try {
            $this->resolve($participantId, $documentTypeId);
            return true;
        } catch (SmpLookupException) {
            return false;
        }
    }

    private function resolveViaDns(string $participantId): string
    {
        // NOSONAR: php:S4790 — MD5 is mandated by the Peppol SML DNS spec (not a security hash)
        $hash    = md5(strtolower(self::SCHEME . '::' . $participantId));
        $cname   = 'B-' . $hash . '.' . self::SCHEME . '.' . $this->smlZone;
        $records = @dns_get_record($cname, DNS_CNAME);

        if (!is_array($records) || $records === [] || !is_string($records[0]['target']) || $records[0]['target'] === '') {
            throw new SmpLookupException(
                'Participant not found in SML: ' . $participantId
            );
        }

        $host = rtrim($records[0]['target'], '.');

        return 'https://' . $host;
    }

    private function buildServiceUrl(
        string $baseUrl,
        string $participantId,
        string $documentTypeId,
    ): string {
        $pid = rawurlencode(self::SCHEME . '::' . $participantId);
        $did = rawurlencode($documentTypeId);

        return rtrim($baseUrl, '/') . "/{$pid}/services/{$did}";
    }

    private function fetchXml(string $url): string
    {
        try {
            $request  = $this->requestFactory
                ->createRequest('GET', $url)
                ->withHeader('Accept', 'application/xml');
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new SmpLookupException(
                'SMP HTTP request failed: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $code = $response->getStatusCode();

        if ($code === 404) {
            throw new SmpLookupException('Document type not registered for participant');
        }

        if ($code >= 400) {
            throw new SmpLookupException("SMP returned HTTP {$code}");
        }

        return (string) $response->getBody();
    }

    private function parseEndpoint(string $xml): SmpEndpoint
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded       = $dom->loadXML($xml);
        $libxmlErrors = libxml_get_errors();
        libxml_clear_errors();

        if (!$loaded) {
            $msg = $libxmlErrors !== []
                ? trim($libxmlErrors[0]->message)
                : 'parse error';
            throw new SmpLookupException("Invalid SMP XML: {$msg}");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('smp', self::NS_PEPPOL);
        $xpath->registerNamespace('bdx', self::NS_BDX);

        $data = null;
        foreach (['smp', 'bdx'] as $pfx) {
            $data = $this->extractAs4Data($xpath, $pfx);
            if ($data !== null) {
                break;
            }
        }

        if ($data === null || $data['endpointUrl'] === '') {
            throw new SmpLookupException(
                'No AS4 endpoint found in SMP response for transport profile: '
                . self::AS4_TRANSPORT
            );
        }

        return new SmpEndpoint(
            endpointUrl:      $data['endpointUrl'],
            certificate:      $data['certificate'],
            transportProfile: $data['transportProfile'],
        );
    }

    /**
     * Search a single SMP namespace prefix for the AS4 endpoint element.
     *
     * @return array{endpointUrl: string, certificate: string, transportProfile: string}|null
     */
    private function extractAs4Data(DOMXPath $xpath, string $pfx): ?array
    {
        $nodes = $xpath->query(
            "//{$pfx}:Endpoint[@transportProfile='" . self::AS4_TRANSPORT . "']"
        );

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        if (!($node instanceof DOMElement)) {
            return null;
        }

        $endpointUrl = '';
        $certificate = '';

        $uriNodes  = $xpath->query("{$pfx}:EndpointURI", $node);
        $certNodes = $xpath->query("{$pfx}:Certificate", $node);

        if ($uriNodes !== false && $uriNodes->length > 0) {
            $endpointUrl = trim($uriNodes->item(0)?->nodeValue ?? '');
        }

        if ($certNodes !== false && $certNodes->length > 0) {
            $certificate = trim($certNodes->item(0)?->nodeValue ?? '');
        }

        return [
            'endpointUrl'      => $endpointUrl,
            'certificate'      => $certificate,
            'transportProfile' => $node->getAttribute('transportProfile'),
        ];
    }
}
