<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4SmpEndpoint;
use App\Invoice\As4\As4SmpQuery;
use App\Invoice\As4\As4SmpResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class As4SmpResolverTest extends TestCase
{
    private const string SMP_BASE      = 'https://smp.test.peppol.eu';
    private const string PARTICIPANT   = '0088:1234567890123';
    private const string DOCTYPE       = 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1';
    private const string PROCESS       = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    private const string ENDPOINT_URL  = 'https://as4.receiver.example.com/as4';
    private const string PROFILE       = 'peppol-transport-as4-v2_0';
    private const string FAKE_CERT_B64 = 'MIIBpDCCAQ2gAwIBAgIBATANBgkqhkiG9w0BAQsFADAiMSAwHgYD';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Psr17Factory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function query(): As4SmpQuery
    {
        return new As4SmpQuery(
            participantId:  self::PARTICIPANT,
            documentTypeId: self::DOCTYPE,
            processId:      self::PROCESS,
        );
    }

    /**
     * Builds a resolver whose HTTP stub returns the given response body/status.
     * Also captures the outgoing request if $captured is provided.
     */
    private function resolver(
        string $responseBody,
        int $statusCode = 200,
        ?RequestInterface &$captured = null,
        string $transportProfile = self::PROFILE,
    ): As4SmpResolver {
        $response   = $this->factory->createResponse($statusCode)
            ->withBody($this->factory->createStream($responseBody));
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $req) use (&$captured, $response) {
                $captured = $req;
                return $response;
            });

        $logger = new class extends \Psr\Log\AbstractLogger {
            /** @param mixed $level @param mixed[] $context */
            #[\Override]
            public function log($level, \Stringable|string $message, array $context = []): void {}
        };

        return new As4SmpResolver($httpClient, $this->factory, $logger, self::SMP_BASE, $transportProfile);
    }

    private function smpXml(
        string $endpointUrl = self::ENDPOINT_URL,
        string $certBase64  = self::FAKE_CERT_B64,
        string $processId   = self::PROCESS,
        string $profile     = self::PROFILE,
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://docs.oasis-open.org/bdxr/ns/SMP/1.0/">
              <ServiceInformation>
                <ParticipantIdentifier scheme="iso6523-actorid-upis">0088:1234567890123</ParticipantIdentifier>
                <DocumentIdentifier scheme="busdox-docid-qns">urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1</DocumentIdentifier>
                <ProcessList>
                  <Process>
                    <ProcessIdentifier scheme="cenbii-procid-ubl">{$processId}</ProcessIdentifier>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="{$profile}">
                        <EndpointURI>{$endpointUrl}</EndpointURI>
                        <Certificate>{$certBase64}</Certificate>
                        <ServiceDescription>Peppol AS4</ServiceDescription>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
    }

    // ── URL construction ──────────────────────────────────────────────────────

    public function testRequestMethodIsGet(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertSame('GET', $req->getMethod());
    }

    public function testUrlContainsSmpBaseUrl(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertStringStartsWith(self::SMP_BASE, (string) $req->getUri());
    }

    public function testUrlContainsEncodedParticipantScheme(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertStringContainsString(
            urlencode('iso6523-actorid-upis::' . self::PARTICIPANT),
            (string) $req->getUri(),
        );
    }

    public function testUrlContainsEncodedDocumentType(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertStringContainsString(
            urlencode(self::DOCTYPE),
            (string) $req->getUri(),
        );
    }

    public function testUrlHasServicesSegment(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertStringContainsString('/services/', (string) $req->getUri());
    }

    public function testAcceptHeaderIsApplicationXml(): void
    {
        $req = null;
        $this->resolver($this->smpXml(), captured: $req)->resolve($this->query());
        $this->assertNotNull($req);
        $this->assertSame('application/xml', $req->getHeaderLine('Accept'));
    }

    // ── Happy-path result fields ──────────────────────────────────────────────

    public function testResolveReturnsEndpointInstance(): void
    {
        $result = $this->resolver($this->smpXml())->resolve($this->query());
        $this->assertInstanceOf(As4SmpEndpoint::class, $result);
    }

    public function testResolvedEndpointUrl(): void
    {
        $result = $this->resolver($this->smpXml())->resolve($this->query());
        $this->assertSame(self::ENDPOINT_URL, $result->endpointUrl);
    }

    public function testResolvedTransportProfile(): void
    {
        $result = $this->resolver($this->smpXml())->resolve($this->query());
        $this->assertSame(self::PROFILE, $result->transportProfile);
    }

    public function testResolvedCertificateIsPem(): void
    {
        $result = $this->resolver($this->smpXml())->resolve($this->query());
        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $result->certificatePem);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $result->certificatePem);
    }

    public function testPemHas64CharLines(): void
    {
        $result = $this->resolver($this->smpXml())->resolve($this->query());
        $lines  = explode("\n", trim($result->certificatePem));
        // Strip header/footer and check data lines are ≤64 chars
        array_shift($lines); // header
        array_pop($lines);   // footer
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(64, strlen($line), "PEM line too long: {$line}");
        }
    }

    public function testCertAlreadyWithPemHeadersIsHandled(): void
    {
        $pemWrapped = "-----BEGIN CERTIFICATE-----\n" . self::FAKE_CERT_B64 . "\n-----END CERTIFICATE-----\n";
        $result     = $this->resolver($this->smpXml(certBase64: $pemWrapped))->resolve($this->query());
        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $result->certificatePem);
        // The base64 data should not contain the header text
        $body = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n"], '', $result->certificatePem);
        $this->assertStringNotContainsString('-----', $body);
    }

    // ── Process-matching preference ───────────────────────────────────────────

    public function testFallsBackToFirstEndpointWhenProcessNotFound(): void
    {
        $xml    = $this->smpXml(processId: 'urn:some:other:process');
        $result = $this->resolver($xml)->resolve($this->query());
        // Resolves successfully using the first (only) endpoint
        $this->assertSame(self::ENDPOINT_URL, $result->endpointUrl);
    }

    public function testPrefersEndpointUnderMatchingProcess(): void
    {
        // Two processes, second matches the query
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://docs.oasis-open.org/bdxr/ns/SMP/1.0/">
              <ServiceInformation>
                <ProcessList>
                  <Process>
                    <ProcessIdentifier>urn:some:other:process</ProcessIdentifier>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="peppol-transport-as4-v2_0">
                        <EndpointURI>https://wrong.example.com/as4</EndpointURI>
                        <Certificate>AAAA</Certificate>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                  <Process>
                    <ProcessIdentifier>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</ProcessIdentifier>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="peppol-transport-as4-v2_0">
                        <EndpointURI>https://correct.example.com/as4</EndpointURI>
                        <Certificate>BBBB</Certificate>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
        $result = $this->resolver($xml)->resolve($this->query());
        $this->assertSame('https://correct.example.com/as4', $result->endpointUrl);
    }

    // ── Error cases ───────────────────────────────────────────────────────────

    public function testThrowsOnHttp404(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('HTTP 404');
        $this->resolver('Not Found', 404)->resolve($this->query());
    }

    public function testThrowsOnHttp500(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('HTTP 500');
        $this->resolver('Error', 500)->resolve($this->query());
    }

    public function testThrowsOnInvalidXml(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('not valid XML');
        $this->resolver('this is not xml <<>>')->resolve($this->query());
    }

    public function testThrowsWhenNoEndpointMatchesProfile(): void
    {
        $xml = $this->smpXml(profile: 'some-other-profile');
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('transportProfile');
        $this->resolver($xml)->resolve($this->query());
    }

    public function testThrowsWhenEndpointUriMissing(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://docs.oasis-open.org/bdxr/ns/SMP/1.0/">
              <ServiceInformation>
                <ProcessList>
                  <Process>
                    <ProcessIdentifier>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</ProcessIdentifier>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="peppol-transport-as4-v2_0">
                        <Certificate>AAAA</Certificate>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('EndpointURI or Certificate');
        $this->resolver($xml)->resolve($this->query());
    }

    public function testThrowsWhenCertificateMissing(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://docs.oasis-open.org/bdxr/ns/SMP/1.0/">
              <ServiceInformation>
                <ProcessList>
                  <Process>
                    <ProcessIdentifier>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</ProcessIdentifier>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="peppol-transport-as4-v2_0">
                        <EndpointURI>https://as4.example.com/as4</EndpointURI>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('EndpointURI or Certificate');
        $this->resolver($xml)->resolve($this->query());
    }

    public function testThrowsWhenCertificateReducesToEmpty(): void
    {
        // PEM headers with no actual base64 body → toPem() throws after stripping headers
        $headersOnly = "-----BEGIN CERTIFICATE-----\n-----END CERTIFICATE-----";
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('empty');
        $this->resolver($this->smpXml(certBase64: $headersOnly))->resolve($this->query());
    }

    // ── Constants ─────────────────────────────────────────────────────────────

    public function testPeppolTransportProfileConstant(): void
    {
        $this->assertSame('peppol-transport-as4-v2_0', As4Constants::PEPPOL_TRANSPORT_PROFILE);
    }

    public function testSmpNsConstant(): void
    {
        $this->assertStringContainsString('bdxr', As4Constants::SMP_NS);
        $this->assertStringContainsString('SMP', As4Constants::SMP_NS);
    }
}
