<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4HttpClient;
use App\Invoice\As4\As4HttpResponse;
use App\Invoice\As4\As4MimePart;
use DOMDocument;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class As4HttpClientTest extends TestCase
{
    private const string ENDPOINT       = 'https://as4.receiver.example.com/as4';
    private const string SOAP_CT        = 'application/soap+xml';
    private const string ENVELOPE_CID   = 'soappart@as4.local';
    private const string ATTACHMENT_CID = 'invoice@sender.example.com';
    private const string ATTACHMENT_CT  = 'application/xml';

    private const string ENVELOPE_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">
          <soapenv:Body><payload/></soapenv:Body>
        </soapenv:Envelope>
        XML;

    private const string PAYLOAD_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
          <ID>INV-001</ID>
        </Invoice>
        XML;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Psr17Factory $factory;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private DOMDocument $envelope;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory  = new Psr17Factory();
        $this->envelope = new DOMDocument('1.0', 'UTF-8');
        $this->envelope->loadXML(self::ENVELOPE_XML);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param As4MimePart[] $attachments
     * @return array{request: RequestInterface, response: As4HttpResponse}
     */
    private function sendAndCapture(
        array $attachments = [],
        int $responseStatusCode = 200,
        string $responseBody = '',
    ): array {
        /** @var RequestInterface|null $captured */
        $captured = null;

        $mockResponse = $this->factory->createResponse($responseStatusCode)
            ->withBody($this->factory->createStream($responseBody));

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient
            ->method('sendRequest')
            ->willReturnCallback(
                static function (RequestInterface $req) use (&$captured, $mockResponse): ResponseInterface {
                    $captured = $req;
                    return $mockResponse;
                }
            );

        $client   = new As4HttpClient($httpClient, $this->factory, $this->factory);
        $response = $client->send(self::ENDPOINT, $this->envelope, $attachments);

        $this->assertInstanceOf(RequestInterface::class, $captured);
        assert($captured instanceof RequestInterface);

        return ['request' => $captured, 'response' => $response];
    }

    private function attachment(): As4MimePart
    {
        return new As4MimePart(
            contentId:   self::ATTACHMENT_CID,
            contentType: self::ATTACHMENT_CT,
            body:        self::PAYLOAD_XML,
        );
    }

    // ── HTTP method / URL ─────────────────────────────────────────────────────

    public function testRequestMethodIsPost(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertSame('POST', $req->getMethod());
    }

    public function testRequestTargetsEndpointUrl(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertSame(self::ENDPOINT, (string) $req->getUri());
    }

    // ── Content-Type header ───────────────────────────────────────────────────

    public function testContentTypeIsMultipartRelated(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringStartsWith('multipart/related', $req->getHeaderLine('Content-Type'));
    }

    public function testContentTypeIncludesSoapXmlType(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringContainsString(
            'type="' . self::SOAP_CT . '"',
            $req->getHeaderLine('Content-Type'),
        );
    }

    public function testContentTypeIncludesBoundary(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $ct = $req->getHeaderLine('Content-Type');
        $this->assertMatchesRegularExpression('/boundary="MIMEBoundary_[0-9a-f]+"/', $ct);
    }

    public function testContentTypeIncludesStartContentId(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringContainsString(
            'start="<' . self::ENVELOPE_CID . '>"',
            $req->getHeaderLine('Content-Type'),
        );
    }

    // ── SOAPAction header ─────────────────────────────────────────────────────

    public function testSoapActionHeaderPresent(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertTrue($req->hasHeader('SOAPAction'));
    }

    public function testSoapActionHeaderIsEmpty(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertSame('', $req->getHeaderLine('SOAPAction'));
    }

    // ── MIME body — SOAP part ─────────────────────────────────────────────────

    public function testMimeBodyContainsBoundary(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $ct    = $req->getHeaderLine('Content-Type');
        preg_match('/boundary="([^"]+)"/', $ct, $m);
        $this->assertNotEmpty($m[1] ?? '');
        $this->assertStringContainsString('--' . $m[1], (string) $req->getBody());
    }

    public function testMimeBodyContainsSoapContentType(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringContainsString(
            'Content-Type: ' . self::SOAP_CT . '; charset=UTF-8',
            (string) $req->getBody(),
        );
    }

    public function testMimeBodyContainsSoapEnvelopeCid(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringContainsString(
            'Content-ID: <' . self::ENVELOPE_CID . '>',
            (string) $req->getBody(),
        );
    }

    public function testMimeBodyContainsEnvelopeXml(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $this->assertStringContainsString('<soapenv:Envelope', (string) $req->getBody());
        $this->assertStringContainsString('<payload/>', (string) $req->getBody());
    }

    public function testMimeBodyEndsWithTerminator(): void
    {
        ['request' => $req] = $this->sendAndCapture();
        $ct  = $req->getHeaderLine('Content-Type');
        preg_match('/boundary="([^"]+)"/', $ct, $m);
        $this->assertStringEndsWith('--' . $m[1] . "--\r\n", (string) $req->getBody());
    }

    // ── MIME body — attachment parts ──────────────────────────────────────────

    public function testAttachmentContentTypeInBody(): void
    {
        ['request' => $req] = $this->sendAndCapture([$this->attachment()]);
        $this->assertStringContainsString(
            'Content-Type: ' . self::ATTACHMENT_CT,
            (string) $req->getBody(),
        );
    }

    public function testAttachmentContentIdInBody(): void
    {
        ['request' => $req] = $this->sendAndCapture([$this->attachment()]);
        $this->assertStringContainsString(
            'Content-ID: <' . self::ATTACHMENT_CID . '>',
            (string) $req->getBody(),
        );
    }

    public function testAttachmentBodyContentInMime(): void
    {
        ['request' => $req] = $this->sendAndCapture([$this->attachment()]);
        $this->assertStringContainsString('<Invoice', (string) $req->getBody());
        $this->assertStringContainsString('<ID>INV-001</ID>', (string) $req->getBody());
    }

    public function testNoAttachmentsProducesOnePartOnly(): void
    {
        ['request' => $req] = $this->sendAndCapture([]);
        $body = (string) $req->getBody();
        $ct   = $req->getHeaderLine('Content-Type');
        preg_match('/boundary="([^"]+)"/', $ct, $m);
        $boundary = $m[1];

        // Only SOAP part + terminator → exactly 2 boundary occurrences starting with '--'
        $count = substr_count($body, '--' . $boundary);
        $this->assertSame(2, $count, 'Expected opening boundary + closing terminator only');
    }

    public function testMultipleAttachmentsAreAllIncluded(): void
    {
        $parts = [
            new As4MimePart('a@test', 'application/xml', '<A/>'),
            new As4MimePart('b@test', 'application/xml', '<B/>'),
        ];
        ['request' => $req] = $this->sendAndCapture($parts);
        $body = (string) $req->getBody();
        $this->assertStringContainsString('Content-ID: <a@test>', $body);
        $this->assertStringContainsString('Content-ID: <b@test>', $body);
        $this->assertStringContainsString('<A/>', $body);
        $this->assertStringContainsString('<B/>', $body);
    }

    // ── Boundary uniqueness ───────────────────────────────────────────────────

    public function testTwoCallsProduceDifferentBoundaries(): void
    {
        ['request' => $req1] = $this->sendAndCapture();
        ['request' => $req2] = $this->sendAndCapture();

        preg_match('/boundary="([^"]+)"/', $req1->getHeaderLine('Content-Type'), $m1);
        preg_match('/boundary="([^"]+)"/', $req2->getHeaderLine('Content-Type'), $m2);

        $this->assertNotSame($m1[1] ?? '', $m2[1] ?? '', 'Boundary must be unique per send');
    }

    // ── Response propagation ──────────────────────────────────────────────────

    public function testResponseStatusCode200IsReturned(): void
    {
        ['response' => $resp] = $this->sendAndCapture(responseStatusCode: 200);
        $this->assertSame(200, $resp->statusCode);
    }

    public function testResponseStatusCode202IsReturned(): void
    {
        ['response' => $resp] = $this->sendAndCapture(responseStatusCode: 202);
        $this->assertSame(202, $resp->statusCode);
    }

    public function testResponseStatusCode500IsReturned(): void
    {
        ['response' => $resp] = $this->sendAndCapture(responseStatusCode: 500);
        $this->assertSame(500, $resp->statusCode);
    }

    public function testResponseBodyIsReturned(): void
    {
        ['response' => $resp] = $this->sendAndCapture(responseBody: '<Receipt/>');
        $this->assertSame('<Receipt/>', $resp->body);
    }

    public function testEmptyResponseBodyIsReturned(): void
    {
        ['response' => $resp] = $this->sendAndCapture(responseBody: '');
        $this->assertSame('', $resp->body);
    }

    // ── As4HttpResponse — isSuccess / isRetriable ─────────────────────────────

    public function testIsSuccessFor200(): void
    {
        $this->assertTrue((new As4HttpResponse(200, ''))->isSuccess());
    }

    public function testIsSuccessFor202(): void
    {
        $this->assertTrue((new As4HttpResponse(202, ''))->isSuccess());
    }

    public function testIsNotSuccessFor400(): void
    {
        $this->assertFalse((new As4HttpResponse(400, ''))->isSuccess());
    }

    public function testIsNotSuccessFor500(): void
    {
        $this->assertFalse((new As4HttpResponse(500, ''))->isSuccess());
    }

    public function testIsRetriableFor408(): void
    {
        $this->assertTrue((new As4HttpResponse(408, ''))->isRetriable());
    }

    public function testIsRetriableFor429(): void
    {
        $this->assertTrue((new As4HttpResponse(429, ''))->isRetriable());
    }

    public function testIsRetriableFor500(): void
    {
        $this->assertTrue((new As4HttpResponse(500, ''))->isRetriable());
    }

    public function testIsRetriableFor502(): void
    {
        $this->assertTrue((new As4HttpResponse(502, ''))->isRetriable());
    }

    public function testIsRetriableFor503(): void
    {
        $this->assertTrue((new As4HttpResponse(503, ''))->isRetriable());
    }

    public function testIsRetriableFor504(): void
    {
        $this->assertTrue((new As4HttpResponse(504, ''))->isRetriable());
    }

    public function testIsNotRetriableFor200(): void
    {
        $this->assertFalse((new As4HttpResponse(200, ''))->isRetriable());
    }

    public function testIsNotRetriableFor400(): void
    {
        $this->assertFalse((new As4HttpResponse(400, ''))->isRetriable());
    }

    public function testIsNotRetriableFor404(): void
    {
        $this->assertFalse((new As4HttpResponse(404, ''))->isRetriable());
    }
}
