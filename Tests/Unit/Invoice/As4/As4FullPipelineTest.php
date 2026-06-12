<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4DispatchRequest;
use App\Invoice\As4\As4EnvelopeSignerInterface;
use App\Invoice\As4\As4HttpClient;
use App\Invoice\As4\As4MessageDispatcher;
use App\Invoice\As4\As4ReceiptParser;
use App\Invoice\As4\As4ReceiptSignal;
use App\Invoice\As4\As4SmpEndpoint;
use App\Invoice\As4\As4SmpResolverInterface;
use App\Invoice\As4\SoapEnvelopeBuilder;
use DOMDocument;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Full-pipeline integration test: exercises real SoapEnvelopeBuilder,
 * real As4HttpClient (multipart assembly), and real As4ReceiptParser
 * together. The signer is a pass-through stub and the HTTP client
 * returns a realistic Oxalis-style AS4 receipt.
 */
class As4FullPipelineTest extends TestCase
{
    private const string SENDER_ID    = '0088:1111111111111';
    private const string RECIPIENT_ID = '0088:9999999999999';
    private const string ENDPOINT_URL = 'https://as4.oxalis.example.com/as4';
    private const string CERT_PEM     = "-----BEGIN CERTIFICATE-----\nMIIB==\n-----END CERTIFICATE-----\n";
    private const string DOCTYPE      = 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1';
    private const string PROCESS      = As4Constants::PEPPOL_PROCESS_BIS3;
    private const string PAYLOAD_XML  = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';
    private const string RECEIPT_ID   = 'receipt-abc123@oxalis.test';
    private const string ORIG_REF_ID  = 'original-msg-id@test.local';

    // ── Fixtures ──────────────────────────────────────────────────────────────

    private function endpoint(): As4SmpEndpoint
    {
        return new As4SmpEndpoint(
            endpointUrl:      self::ENDPOINT_URL,
            certificatePem:   self::CERT_PEM,
            transportProfile: As4Constants::PEPPOL_TRANSPORT_PROFILE,
        );
    }

    private function request(): As4DispatchRequest
    {
        return new As4DispatchRequest(
            recipientPartyId: self::RECIPIENT_ID,
            documentTypeId:   self::DOCTYPE,
            processId:        self::PROCESS,
            payloadXml:       self::PAYLOAD_XML,
        );
    }

    /** Oxalis-style AS4 receipt — plain SOAP XML with NonRepudiationInformation. */
    private function oxalisReceiptXml(): string
    {
        $receiptId = self::RECEIPT_ID;
        $origRefId = self::ORIG_REF_ID;
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope
                xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soap:Header>
                <eb:Messaging>
                  <eb:SignalMessage>
                    <eb:MessageInfo>
                      <eb:Timestamp>2026-06-12T12:00:00Z</eb:Timestamp>
                      <eb:MessageId>{$receiptId}</eb:MessageId>
                      <eb:RefToMessageId>{$origRefId}</eb:RefToMessageId>
                    </eb:MessageInfo>
                    <eb:Receipt>
                      <ebbp:NonRepudiationInformation
                          xmlns:ebbp="http://docs.oasis-open.org/ebcore/ns/NonRepudiation/v1.0">
                        <ebbp:MessagePartNRInformation>
                          <ds:Reference xmlns:ds="http://www.w3.org/2000/09/xmldsig#" URI="#Body-test">
                            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                            <ds:DigestValue>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=</ds:DigestValue>
                          </ds:Reference>
                        </ebbp:MessagePartNRInformation>
                      </ebbp:NonRepudiationInformation>
                    </eb:Receipt>
                  </eb:SignalMessage>
                </eb:Messaging>
              </soap:Header>
              <soap:Body/>
            </soap:Envelope>
            XML;
    }

    // ── Dispatcher factory ────────────────────────────────────────────────────

    /**
     * Builds a full-stack dispatcher:
     *   - real SoapEnvelopeBuilder
     *   - pass-through signer (no XMLSecLibs dependency)
     *   - real As4HttpClient with stub PSR-18
     *   - real As4ReceiptParser
     */
    private function dispatcher(
        string $responseBody,
        int $statusCode = 200,
        ?RequestInterface &$captured = null,
    ): As4MessageDispatcher {
        $psr17    = new Psr17Factory();
        $response = $psr17->createResponse($statusCode)
            ->withBody($psr17->createStream($responseBody));

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $req) use (&$captured, $response) {
                $captured = $req;
                return $response;
            });

        $smpResolver = $this->createStub(As4SmpResolverInterface::class);
        $smpResolver->method('resolve')->willReturn($this->endpoint());

        $signer = new class implements As4EnvelopeSignerInterface {
            #[\Override]
            public function sign(DOMDocument $envelope): DOMDocument { return $envelope; }
        };

        $logger = new class extends \Psr\Log\AbstractLogger {
            /** @param mixed $level @param mixed[] $context */
            #[\Override]
            public function log($level, \Stringable|string $message, array $context = []): void {}
        };

        return new As4MessageDispatcher(
            $smpResolver,
            new SoapEnvelopeBuilder(),
            $signer,
            new As4HttpClient($httpClient, $psr17, $psr17),
            new As4ReceiptParser(),
            self::SENDER_ID,
            $logger,
        );
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function testFullPipelineReturnsSuccess(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml())->dispatch($this->request());
        $this->assertTrue($result->success);
    }

    public function testFullPipelineResultHasNonEmptyMessageId(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml())->dispatch($this->request());
        $this->assertNotEmpty($result->messageId);
    }

    public function testFullPipelineHttpStatusPreserved(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml(), 200)->dispatch($this->request());
        $this->assertSame(200, $result->httpStatus);
    }

    public function testFullPipelineParsesReceiptSignal(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml())->dispatch($this->request());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result->signal);
    }

    public function testFullPipelineReceiptMessageIdMatchesFixture(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml())->dispatch($this->request());
        $this->assertNotNull($result->signal);
        $this->assertInstanceOf(As4ReceiptSignal::class, $result->signal);
        $this->assertSame(self::RECEIPT_ID, $result->signal->messageId);
    }

    public function testFullPipelineReceiptRefIdMatchesFixture(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml())->dispatch($this->request());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result->signal);
        $this->assertSame(self::ORIG_REF_ID, $result->signal->refToMessageId);
    }

    // ── Outbound request structure ────────────────────────────────────────────

    public function testSentRequestUsesEndpointUrl(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertSame(self::ENDPOINT_URL, (string) $req->getUri());
    }

    public function testSentRequestHasMultipartContentType(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString('multipart/related', $req->getHeaderLine('Content-Type'));
    }

    public function testSentRequestContentTypeHasBoundary(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString('boundary=', $req->getHeaderLine('Content-Type'));
    }

    public function testSentRequestContentTypeHasSoapStartParam(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString('start="<soappart@as4.local>"', $req->getHeaderLine('Content-Type'));
    }

    public function testSentRequestHasEmptySoapActionHeader(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertSame('', $req->getHeaderLine('SOAPAction'));
    }

    public function testSentRequestBodyContainsSoapEnvelope(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString('soapenv:Envelope', (string) $req->getBody());
    }

    public function testSentRequestBodyContainsEbMessaging(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString('eb:Messaging', (string) $req->getBody());
    }

    public function testSentRequestBodyContainsPayloadAttachment(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $body = (string) $req->getBody();
        $this->assertStringContainsString('Content-Type: application/xml', $body);
        $this->assertStringContainsString(self::PAYLOAD_XML, $body);
    }

    public function testSentRequestBodyHasSoapPartHeader(): void
    {
        $req = null;
        $this->dispatcher($this->oxalisReceiptXml(), captured: $req)->dispatch($this->request());
        $this->assertNotNull($req);
        $this->assertStringContainsString(
            'Content-Type: application/soap+xml',
            (string) $req->getBody(),
        );
    }

    // ── Error handling ────────────────────────────────────────────────────────

    public function testHttp500ReturnsFalseSuccess(): void
    {
        $result = $this->dispatcher($this->oxalisReceiptXml(), 500)->dispatch($this->request());
        $this->assertFalse($result->success);
    }

    public function testHttp202WithEmptyBodyGivesNullSignal(): void
    {
        $result = $this->dispatcher('', 202)->dispatch($this->request());
        $this->assertNull($result->signal);
        $this->assertTrue($result->success);
    }
}
