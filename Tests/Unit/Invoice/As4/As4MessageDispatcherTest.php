<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4DispatchRequest;
use App\Invoice\As4\As4EnvelopeBuilderInterface;
use App\Invoice\As4\As4EnvelopeSignerInterface;
use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4HttpResponse;
use App\Invoice\As4\As4HttpTransportInterface;
use App\Invoice\As4\As4MessageDispatcher;
use App\Invoice\As4\As4MimePart;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4ReceiptSignal;
use App\Invoice\As4\As4SmpEndpoint;
use App\Invoice\As4\As4SmpQuery;
use App\Invoice\As4\As4SmpResolverInterface;
use App\Invoice\As4\SoapEnvelopeParams;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class As4MessageDispatcherTest extends TestCase
{
    private const string SENDER_ID    = '0088:1111111111111';
    private const string RECIPIENT_ID = '0088:9999999999999';
    private const string DOCTYPE      = 'busdox-docid-qns::urn:test:doc:1.0';
    private const string PROCESS      = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    private const string PAYLOAD_XML  = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';
    private const string ENDPOINT_URL = 'https://as4.receiver.example.com/as4';
    private const string CERT_PEM     = "-----BEGIN CERTIFICATE-----\nMIIB==\n-----END CERTIFICATE-----\n";

    // ── Fixtures ──────────────────────────────────────────────────────────────

    private function makeDoc(): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->appendChild($doc->createElement('Envelope'));
        return $doc;
    }

    private function endpoint(): As4SmpEndpoint
    {
        return new As4SmpEndpoint(
            endpointUrl:      self::ENDPOINT_URL,
            certificatePem:   self::CERT_PEM,
            transportProfile: As4Constants::PEPPOL_TRANSPORT_PROFILE,
        );
    }

    private function request(?string $messageId = null): As4DispatchRequest
    {
        return new As4DispatchRequest(
            recipientPartyId: self::RECIPIENT_ID,
            documentTypeId:   self::DOCTYPE,
            processId:        self::PROCESS,
            payloadXml:       self::PAYLOAD_XML,
            messageId:        $messageId,
        );
    }

    private function receiptSignal(): As4ReceiptSignal
    {
        return new As4ReceiptSignal(
            messageId:      'rcpt-001@example.com',
            refToMessageId: 'orig-001@example.com',
            timestamp:      '2026-01-01T12:00:00Z',
        );
    }

    private function errorSignal(string $severity = 'failure'): As4ErrorSignal
    {
        return new As4ErrorSignal(
            messageId:        'err-001@example.com',
            refToMessageId:   'orig-001@example.com',
            timestamp:        '2026-01-01T12:00:00Z',
            category:         'Content',
            errorCode:        'EBMS:0004',
            severity:         $severity,
            shortDescription: 'Other',
            description:      'Unknown error',
        );
    }

    private function nullLogger(): \Psr\Log\LoggerInterface
    {
        return new class extends \Psr\Log\AbstractLogger {
            /** @param mixed $level @param mixed[] $context */
            #[\Override]
            public function log($level, \Stringable|string $message, array $context = []): void {}
        };
    }

    // ── Stub factories ────────────────────────────────────────────────────────

    private function smpReturning(As4SmpEndpoint $endpoint): As4SmpResolverInterface
    {
        $stub = $this->createStub(As4SmpResolverInterface::class);
        $stub->method('resolve')->willReturn($endpoint);
        return $stub;
    }

    private function smpCapturing(?As4SmpQuery &$captured): As4SmpResolverInterface
    {
        $endpoint = $this->endpoint();
        $stub     = $this->createStub(As4SmpResolverInterface::class);
        $stub->method('resolve')
            ->willReturnCallback(static function (As4SmpQuery $q) use (&$captured, $endpoint) {
                $captured = $q;
                return $endpoint;
            });
        return $stub;
    }

    private function builderReturning(DOMDocument $doc): As4EnvelopeBuilderInterface
    {
        $stub = $this->createStub(As4EnvelopeBuilderInterface::class);
        $stub->method('build')->willReturn($doc);
        return $stub;
    }

    private function builderCapturing(?SoapEnvelopeParams &$captured): As4EnvelopeBuilderInterface
    {
        $doc  = $this->makeDoc();
        $stub = $this->createStub(As4EnvelopeBuilderInterface::class);
        $stub->method('build')
            ->willReturnCallback(static function (SoapEnvelopeParams $p) use (&$captured, $doc) {
                $captured = $p;
                return $doc;
            });
        return $stub;
    }

    private function signerReturning(DOMDocument $doc): As4EnvelopeSignerInterface
    {
        $stub = $this->createStub(As4EnvelopeSignerInterface::class);
        $stub->method('sign')->willReturn($doc);
        return $stub;
    }

    private function transportReturning(As4HttpResponse $response): As4HttpTransportInterface
    {
        $stub = $this->createStub(As4HttpTransportInterface::class);
        $stub->method('send')->willReturn($response);
        return $stub;
    }

    /** @param-out array<array-key, mixed> $capturedAttachments */
    private function transportCapturing(
        ?string &$capturedUrl,
        ?DOMDocument &$capturedDoc,
        array &$capturedAttachments,
        int $statusCode = 200,
    ): As4HttpTransportInterface {
        $response = new As4HttpResponse($statusCode, '');
        $stub     = $this->createStub(As4HttpTransportInterface::class);
        $stub->method('send')
            ->willReturnCallback(
                static function (string $url, DOMDocument $doc, array $atts) use (
                    &$capturedUrl,
                    &$capturedDoc,
                    &$capturedAttachments,
                    $response,
                ) {
                    $capturedUrl         = $url;
                    $capturedDoc         = $doc;
                    $capturedAttachments = $atts;
                    return $response;
                }
            );
        return $stub;
    }

    private function parserReturning(As4ReceiptSignal|As4ErrorSignal|null $signal): As4ReceiptParserInterface
    {
        $stub = $this->createStub(As4ReceiptParserInterface::class);
        $stub->method('parse')->willReturn($signal);
        return $stub;
    }

    private function dispatcher(
        ?As4SmpResolverInterface $smpResolver = null,
        ?As4EnvelopeBuilderInterface $envelopeBuilder = null,
        ?As4EnvelopeSignerInterface $signer = null,
        ?As4HttpTransportInterface $httpTransport = null,
        ?As4ReceiptParserInterface $receiptParser = null,
        string $senderPartyId = self::SENDER_ID,
        int $httpStatus = 200,
    ): As4MessageDispatcher {
        $doc = $this->makeDoc();
        return new As4MessageDispatcher(
            $smpResolver     ?? $this->smpReturning($this->endpoint()),
            $envelopeBuilder ?? $this->builderReturning($doc),
            $signer          ?? $this->signerReturning($doc),
            $httpTransport   ?? $this->transportReturning(new As4HttpResponse($httpStatus, '')),
            $receiptParser   ?? $this->parserReturning(null),
            $senderPartyId,
            $this->nullLogger(),
        );
    }

    // ── HTTP status / success ─────────────────────────────────────────────────

    public function testReturnsSuccessOnHttp200(): void
    {
        $result = $this->dispatcher(httpStatus: 200)->dispatch($this->request());
        $this->assertTrue($result->success);
    }

    public function testReturnsSuccessOnHttp202(): void
    {
        $result = $this->dispatcher(httpStatus: 202)->dispatch($this->request());
        $this->assertTrue($result->success);
    }

    public function testReturnsFalseSuccessOnHttp500(): void
    {
        $result = $this->dispatcher(httpStatus: 500)->dispatch($this->request());
        $this->assertFalse($result->success);
    }

    public function testHttpStatusCodePreservedInResult(): void
    {
        $result = $this->dispatcher(httpStatus: 202)->dispatch($this->request());
        $this->assertSame(202, $result->httpStatus);
    }

    // ── Message IDs ───────────────────────────────────────────────────────────

    public function testExplicitMessageIdIsUsed(): void
    {
        $result = $this->dispatcher()->dispatch($this->request(messageId: 'explicit-id@test.com'));
        $this->assertSame('explicit-id@test.com', $result->messageId);
    }

    public function testMessageIdIsGeneratedWhenOmitted(): void
    {
        $result = $this->dispatcher()->dispatch($this->request());
        $this->assertNotEmpty($result->messageId);
    }

    public function testGeneratedMessageIdsAreUnique(): void
    {
        $d       = $this->dispatcher();
        $result1 = $d->dispatch($this->request());
        $result2 = $d->dispatch($this->request());
        $this->assertNotSame($result1->messageId, $result2->messageId);
    }

    public function testResultMessageIdMatchesSentId(): void
    {
        $params = null;
        $d      = $this->dispatcher(envelopeBuilder: $this->builderCapturing($params));
        $result = $d->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame($params->messageId, $result->messageId);
    }

    // ── SMP query ─────────────────────────────────────────────────────────────

    public function testSmpQueryUsesRecipientPartyId(): void
    {
        $query = null;
        $this->dispatcher(smpResolver: $this->smpCapturing($query))->dispatch($this->request());
        $this->assertNotNull($query);
        $this->assertSame(self::RECIPIENT_ID, $query->participantId);
    }

    public function testSmpQueryUsesDocumentTypeId(): void
    {
        $query = null;
        $this->dispatcher(smpResolver: $this->smpCapturing($query))->dispatch($this->request());
        $this->assertNotNull($query);
        $this->assertSame(self::DOCTYPE, $query->documentTypeId);
    }

    public function testSmpQueryUsesProcessId(): void
    {
        $query = null;
        $this->dispatcher(smpResolver: $this->smpCapturing($query))->dispatch($this->request());
        $this->assertNotNull($query);
        $this->assertSame(self::PROCESS, $query->processId);
    }

    // ── Envelope params ───────────────────────────────────────────────────────

    public function testEnvelopeParamsUseSenderPartyId(): void
    {
        $params = null;
        $this->dispatcher(envelopeBuilder: $this->builderCapturing($params))->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame(self::SENDER_ID, $params->senderPartyId);
    }

    public function testEnvelopeParamsUseRecipientPartyId(): void
    {
        $params = null;
        $this->dispatcher(envelopeBuilder: $this->builderCapturing($params))->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame(self::RECIPIENT_ID, $params->receiverPartyId);
    }

    public function testEnvelopeParamsActionMatchesDocumentTypeId(): void
    {
        $params = null;
        $this->dispatcher(envelopeBuilder: $this->builderCapturing($params))->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame(self::DOCTYPE, $params->action);
    }

    public function testEnvelopeParamsServiceMatchesProcessId(): void
    {
        $params = null;
        $this->dispatcher(envelopeBuilder: $this->builderCapturing($params))->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame(self::PROCESS, $params->service);
    }

    public function testEnvelopeParamsContainPayloadXml(): void
    {
        $params = null;
        $this->dispatcher(envelopeBuilder: $this->builderCapturing($params))->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertSame(self::PAYLOAD_XML, $params->payloadXml);
    }

    // ── Pipeline wiring ───────────────────────────────────────────────────────

    public function testTransportReceivesEndpointUrl(): void
    {
        $url  = null;
        $doc  = null;
        $atts = [];
        $this->dispatcher(
            httpTransport: $this->transportCapturing($url, $doc, $atts),
        )->dispatch($this->request());
        $this->assertNotNull($url);
        $this->assertSame(self::ENDPOINT_URL, $url);
    }

    public function testAttachmentBodyMatchesPayloadXml(): void
    {
        $url  = null;
        $doc  = null;
        $atts = [];
        $this->dispatcher(
            httpTransport: $this->transportCapturing($url, $doc, $atts),
        )->dispatch($this->request());
        $this->assertCount(1, $atts);
        /** @var As4MimePart $att */
        $att = $atts[0];
        $this->assertInstanceOf(As4MimePart::class, $att);
        $this->assertSame(self::PAYLOAD_XML, $att->body);
    }

    public function testAttachmentContentTypeIsXml(): void
    {
        $url  = null;
        $doc  = null;
        $atts = [];
        $this->dispatcher(
            httpTransport: $this->transportCapturing($url, $doc, $atts),
        )->dispatch($this->request());
        $this->assertCount(1, $atts);
        /** @var As4MimePart $att */
        $att = $atts[0];
        $this->assertInstanceOf(As4MimePart::class, $att);
        $this->assertSame(As4Constants::MIME_XML, $att->contentType);
    }

    public function testAttachmentContentIdMatchesEnvelopeParams(): void
    {
        $params = null;
        $url    = null;
        $doc    = null;
        $atts   = [];
        $this->dispatcher(
            envelopeBuilder: $this->builderCapturing($params),
            httpTransport:   $this->transportCapturing($url, $doc, $atts),
        )->dispatch($this->request());
        $this->assertNotNull($params);
        $this->assertCount(1, $atts);
        /** @var As4MimePart $att */
        $att = $atts[0];
        $this->assertInstanceOf(As4MimePart::class, $att);
        $this->assertSame($params->payloadContentId, $att->contentId);
    }

    // ── Signal handling ───────────────────────────────────────────────────────

    public function testReceiptSignalPropagatedToResult(): void
    {
        $signal = $this->receiptSignal();
        $result = $this->dispatcher(receiptParser: $this->parserReturning($signal))->dispatch($this->request());
        $this->assertSame($signal, $result->signal);
    }

    public function testErrorSignalPropagatedToResult(): void
    {
        $signal = $this->errorSignal();
        $result = $this->dispatcher(receiptParser: $this->parserReturning($signal))->dispatch($this->request());
        $this->assertSame($signal, $result->signal);
    }

    public function testNullSignalWhenParserReturnsNull(): void
    {
        $result = $this->dispatcher(receiptParser: $this->parserReturning(null))->dispatch($this->request());
        $this->assertNull($result->signal);
    }

    public function testHasErrorReturnsTrueForFailureErrorSignal(): void
    {
        $signal = $this->errorSignal('failure');
        $result = $this->dispatcher(receiptParser: $this->parserReturning($signal))->dispatch($this->request());
        $this->assertTrue($result->hasError());
    }

    public function testHasErrorReturnsFalseForReceiptSignal(): void
    {
        $signal = $this->receiptSignal();
        $result = $this->dispatcher(receiptParser: $this->parserReturning($signal))->dispatch($this->request());
        $this->assertFalse($result->hasError());
    }

    public function testHasErrorReturnsFalseForNullSignal(): void
    {
        $result = $this->dispatcher(receiptParser: $this->parserReturning(null))->dispatch($this->request());
        $this->assertFalse($result->hasError());
    }
}
