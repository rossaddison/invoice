<?php

declare(strict_types=1);

namespace Invoice\As4;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use DOMDocument;
use DOMElement;
use Exception;

/**
 * SMP (Service Metadata Publisher) Integration
 *
 * Implements Dynamic Sender (Profile Enhancement 4.3) and Dynamic Receiver (4.2)
 * by querying SMP for recipient endpoint URL, encryption certificate, and P-Mode
 * parameters.
 *
 * @psalm-suppress UnusedClass
 */
class As4SmpResolver
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private LoggerInterface $logger;
    private string $smpHostname;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        LoggerInterface $logger,
        string $smpHostname
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
        $this->smpHostname = $smpHostname;
    }

    /**
     * Resolve recipient endpoint and certificate via SMP lookup.
     *
     * @throws Exception if SMP resolution fails
     */
    public function resolveEndpoint(
        string $recipientGln,
        string $documentTypeId,
        string $processId
    ): As4SmpEndpoint {
        try {
            $participantId = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088:' . $recipientGln;
            $docId = urlencode($documentTypeId);
            $procId = urlencode($processId);

            $smpUrl = sprintf(
                'https://%s/%s/services/%s/processes/%s',
                $this->smpHostname,
                $participantId,
                $docId,
                $procId
            );

            $this->logger->info('Resolving endpoint via SMP', [
                'recipientGln' => $recipientGln,
                'smpUrl' => $smpUrl,
            ]);

            $request = $this->requestFactory->createRequest('GET', $smpUrl);
            $request = $request->withHeader('Accept', 'application/xml');

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw new Exception(
                    "SMP query failed with HTTP {$response->getStatusCode()}: " .
                    $response->getReasonPhrase()
                );
            }

            return $this->parseSmpResponse((string) $response->getBody());
        } catch (Exception $e) {
            $this->logger->error('SMP resolution failed', [
                'recipientGln' => $recipientGln,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse SMP XML response and return endpoint details.
     *
     * @throws Exception if the XML is invalid or required fields are missing
     */
    private function parseSmpResponse(string $xmlBody): As4SmpEndpoint
    {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xmlBody)) {
            throw new Exception('Invalid SMP XML response');
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('smd', 'http://docs.oasis-open.org/bdxr/ns/SMP/ServiceMetadata/1.0/');

        $endpoints = $xpath->query(
            "//smd:Endpoint[@transportProfile='" . As4Constants::AS4_TRANSPORT_PROFILE . "'] | " .
            "//Endpoint[@transportProfile='" . As4Constants::AS4_TRANSPORT_PROFILE . "']"
        );

        if ($endpoints === false || $endpoints->length === 0) {
            throw new Exception('No AS4 endpoint found in SMP response');
        }

        $endpoint = $endpoints->item(0);
        if (!$endpoint instanceof DOMElement) {
            throw new Exception('SMP endpoint node is not a DOMElement');
        }

        $uriNode = $xpath->query('smd:EndpointURI | EndpointURI', $endpoint);
        $endpointUrl = ($uriNode !== false && $uriNode->length > 0)
            ? ($uriNode->item(0)?->nodeValue ?? '')
            : '';

        $certNode = $xpath->query('smd:Certificate | Certificate', $endpoint);
        $certificate = ($certNode !== false && $certNode->length > 0)
            ? ($certNode->item(0)?->nodeValue ?? '')
            : '';

        $hashNode = $xpath->query('smd:CertificateHash | CertificateHash', $endpoint);
        $certificateHash = ($hashNode !== false && $hashNode->length > 0)
            ? ($hashNode->item(0)?->nodeValue ?? '')
            : '';

        if ($endpointUrl === '' || $certificate === '') {
            throw new Exception('Missing endpoint URL or certificate in SMP response');
        }

        $this->logger->info('SMP endpoint resolved', [
            'endpointUrl' => $endpointUrl,
            'certificateHash' => substr($certificateHash, 0, 16) . '...',
        ]);

        return new As4SmpEndpoint(
            endpointUrl: $endpointUrl,
            certificate: $certificate,
            certificateHash: $certificateHash,
            transportProfile: As4Constants::AS4_TRANSPORT_PROFILE
        );
    }
}

/**
 * SMP endpoint information.
 */
class As4SmpEndpoint
{
    public function __construct(
        public readonly string $endpointUrl,
        public readonly string $certificate,
        public readonly string $certificateHash,
        public readonly string $transportProfile
    ) {}
}
