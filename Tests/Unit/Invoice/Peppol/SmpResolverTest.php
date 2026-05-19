<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Peppol;

use App\Invoice\Peppol\SmpEndpoint;
use App\Invoice\Peppol\SmpLookupException;
use App\Invoice\Peppol\SmpResolver;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class SmpResolverTest extends TestCase
{
    private const string PARTICIPANT_ID   = '0088:1234567890123';
    private const string DOCUMENT_TYPE_ID =
        'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    private const string SMP_BASE_URL     = 'http://smp.example.com';
    private const string ENDPOINT_URL     = 'https://ap.example.com/as4';
    private const string CERTIFICATE      = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0Z3V';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private ClientInterface&\PHPUnit\Framework\MockObject\Stub $httpClient;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private SmpResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(ClientInterface::class);
        $this->resolver   = $this->buildResolver();
    }

    private function buildResolver(?string $smpBaseUrl = self::SMP_BASE_URL): SmpResolver
    {
        return new SmpResolver(
            httpClient:     $this->httpClient,
            requestFactory: new HttpFactory(),
            smlZone:        'acc.edelivery.tech.ec.europa.eu',
            smpBaseUrl:     $smpBaseUrl,
        );
    }

    private function peppolSmpXml(
        string $endpointUrl = self::ENDPOINT_URL,
        string $certificate = self::CERTIFICATE,
        string $transportProfile = 'peppol-as4-2.0',
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://busdox.org/serviceMetadata/publishing/1.0/">
              <ServiceInformation>
                <ProcessList>
                  <Process>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="{$transportProfile}">
                        <EndpointURI>{$endpointUrl}</EndpointURI>
                        <Certificate>{$certificate}</Certificate>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
    }

    private function bdxSmpXml(
        string $endpointUrl = self::ENDPOINT_URL,
        string $certificate = self::CERTIFICATE,
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <ServiceMetadata xmlns="http://docs.oasis-open.org/bdxr/ns/SMP/2016/05">
              <ServiceInformation>
                <ProcessList>
                  <Process>
                    <ServiceEndpointList>
                      <Endpoint transportProfile="peppol-as4-2.0">
                        <EndpointURI>{$endpointUrl}</EndpointURI>
                        <Certificate>{$certificate}</Certificate>
                      </Endpoint>
                    </ServiceEndpointList>
                  </Process>
                </ProcessList>
              </ServiceInformation>
            </ServiceMetadata>
            XML;
    }

    public function testResolveReturnsEndpointFromPeppolSmpXml(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->peppolSmpXml()));

        $endpoint = $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);

        $this->assertInstanceOf(SmpEndpoint::class, $endpoint);
        $this->assertSame(self::ENDPOINT_URL, $endpoint->endpointUrl);
        $this->assertSame(self::CERTIFICATE, $endpoint->certificate);
        $this->assertSame('peppol-as4-2.0', $endpoint->transportProfile);
    }

    public function testResolveReturnsEndpointFromBdxSmpXml(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->bdxSmpXml()));

        $endpoint = $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);

        $this->assertSame(self::ENDPOINT_URL, $endpoint->endpointUrl);
        $this->assertSame(self::CERTIFICATE, $endpoint->certificate);
    }

    public function testResolveThrowsOnHttp404(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(404));

        $this->expectException(SmpLookupException::class);
        $this->expectExceptionMessageMatches('/not registered/i');

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);
    }

    public function testResolveThrowsOnHttp500(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(500));

        $this->expectException(SmpLookupException::class);
        $this->expectExceptionMessageMatches('/500/');

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);
    }

    public function testResolveThrowsOnNetworkException(): void
    {
        $networkError = new class ('Connection refused') extends \RuntimeException
            implements ClientExceptionInterface {};

        $this->httpClient
            ->method('sendRequest')
            ->willThrowException($networkError);

        $this->expectException(SmpLookupException::class);
        $this->expectExceptionMessageMatches('/Connection refused/');

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);
    }

    public function testResolveThrowsOnInvalidXml(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], 'this is not xml <<>>'));

        $this->expectException(SmpLookupException::class);
        $this->expectExceptionMessageMatches('/Invalid SMP XML/i');

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);
    }

    public function testResolveThrowsWhenNoAs4EndpointPresent(): void
    {
        $xml = $this->peppolSmpXml(transportProfile: 'busdox-transport-ebms3-as4');

        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $xml));

        $this->expectException(SmpLookupException::class);
        $this->expectExceptionMessageMatches('/No AS4 endpoint/i');

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);
    }

    public function testIsRegisteredReturnsTrueOnSuccess(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->peppolSmpXml()));

        $this->assertTrue(
            $this->resolver->isRegistered(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID)
        );
    }

    public function testIsRegisteredReturnsFalseOnFailure(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(404));

        $this->assertFalse(
            $this->resolver->isRegistered(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID)
        );
    }

    public function testResolveBuildsCorrectRequestUrl(): void
    {
        $capturedRequest = null;

        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(
                function (RequestInterface $req) use (&$capturedRequest): Response {
                    $capturedRequest = $req;
                    return new Response(200, [], $this->peppolSmpXml());
                }
            );

        $this->resolver->resolve(self::PARTICIPANT_ID, self::DOCUMENT_TYPE_ID);

        $this->assertInstanceOf(RequestInterface::class, $capturedRequest);
        $this->assertSame('GET', $capturedRequest->getMethod());

        $url = (string) $capturedRequest->getUri();
        $this->assertStringStartsWith(self::SMP_BASE_URL . '/', $url);
        $this->assertStringContainsString(
            rawurlencode('iso6523-actorid-upis::' . self::PARTICIPANT_ID),
            $url
        );
        $this->assertStringContainsString(
            rawurlencode(self::DOCUMENT_TYPE_ID),
            $url
        );
        $this->assertStringContainsString('/services/', $url);
    }
}
