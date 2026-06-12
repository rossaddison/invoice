<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4ReceiptParser;
use App\Invoice\As4\As4ReceiptSignal;
use PHPUnit\Framework\TestCase;

class As4ReceiptParserTest extends TestCase
{
    private const string RECEIPT_MSG_ID  = 'receipt-001@receiver.example.com';
    private const string ORIGINAL_MSG_ID = 'msg-001@sender.example.com';
    private const string TIMESTAMP       = '2024-06-01T10:05:00Z';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private As4ReceiptParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new As4ReceiptParser();
    }

    // ── Fixtures ──────────────────────────────────────────────────────────────

    private function receiptSoap(
        string $messageId = self::RECEIPT_MSG_ID,
        string $refToMessageId = self::ORIGINAL_MSG_ID,
        string $timestamp = self::TIMESTAMP,
    ): string {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header>
                <eb:Messaging>
                  <eb:SignalMessage>
                    <eb:MessageInfo>
                      <eb:Timestamp>{$timestamp}</eb:Timestamp>
                      <eb:MessageId>{$messageId}</eb:MessageId>
                      <eb:RefToMessageId>{$refToMessageId}</eb:RefToMessageId>
                    </eb:MessageInfo>
                    <eb:Receipt>
                      <ebbp:NonRepudiationInformation
                          xmlns:ebbp="http://docs.oasis-open.org/ebxml-bp/ebbp-signals-2.0"/>
                    </eb:Receipt>
                  </eb:SignalMessage>
                </eb:Messaging>
              </soapenv:Header>
              <soapenv:Body/>
            </soapenv:Envelope>
            XML;
    }

    private function errorSoap(
        string $category = 'Processing',
        string $errorCode = 'EBMS:0010',
        string $severity = 'failure',
        string $shortDescription = 'InvalidHeader',
        string $description = 'Required message header not found',
    ): string {
        $ts    = self::TIMESTAMP;
        $refId = self::ORIGINAL_MSG_ID;
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header>
                <eb:Messaging>
                  <eb:SignalMessage>
                    <eb:MessageInfo>
                      <eb:Timestamp>{$ts}</eb:Timestamp>
                      <eb:MessageId>error-001@receiver.example.com</eb:MessageId>
                      <eb:RefToMessageId>{$refId}</eb:RefToMessageId>
                    </eb:MessageInfo>
                    <eb:Error category="{$category}"
                              errorCode="{$errorCode}"
                              severity="{$severity}"
                              shortDescription="{$shortDescription}"
                              refToMessageInError="{self::ORIGINAL_MSG_ID}">
                      <eb:Description xml:lang="en">{$description}</eb:Description>
                    </eb:Error>
                  </eb:SignalMessage>
                </eb:Messaging>
              </soapenv:Header>
              <soapenv:Body/>
            </soapenv:Envelope>
            XML;
    }

    private function noSignalSoap(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header>
                <eb:Messaging>
                  <eb:UserMessage>
                    <eb:MessageInfo>
                      <eb:MessageId>user-msg@example.com</eb:MessageId>
                    </eb:MessageInfo>
                  </eb:UserMessage>
                </eb:Messaging>
              </soapenv:Header>
              <soapenv:Body/>
            </soapenv:Envelope>
            XML;
    }

    private function emptyMessagingSoap(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope"
                              xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
              <soapenv:Header>
                <eb:Messaging/>
              </soapenv:Header>
              <soapenv:Body/>
            </soapenv:Envelope>
            XML;
    }

    /** Wraps a SOAP XML string in a simple MIME multipart body. */
    private function wrapInMime(string $soapXml, string $boundary = 'TestBoundary_AS4'): string
    {
        return "--{$boundary}\r\n"
            . "Content-Type: application/soap+xml; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "Content-ID: <soappart@as4.local>\r\n"
            . "\r\n"
            . $soapXml
            . "\r\n"
            . "--{$boundary}--\r\n";
    }

    private function mimeContentType(string $boundary = 'TestBoundary_AS4'): string
    {
        return 'multipart/related; type="application/soap+xml"; boundary="' . $boundary . '"';
    }

    // ── Empty / blank body ────────────────────────────────────────────────────

    public function testEmptyBodyReturnsNull(): void
    {
        $this->assertNull($this->parser->parse(''));
    }

    public function testWhitespaceBodyReturnsNull(): void
    {
        $this->assertNull($this->parser->parse("   \r\n\t  "));
    }

    // ── Plain SOAP — Receipt signal ───────────────────────────────────────────

    public function testPlainSoapReceiptReturnsReceiptSignal(): void
    {
        $result = $this->parser->parse($this->receiptSoap());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
    }

    public function testReceiptSignalMessageId(): void
    {
        /** @var As4ReceiptSignal $result */
        $result = $this->parser->parse($this->receiptSoap());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
        $this->assertSame(self::RECEIPT_MSG_ID, $result->messageId);
    }

    public function testReceiptSignalRefToMessageId(): void
    {
        /** @var As4ReceiptSignal $result */
        $result = $this->parser->parse($this->receiptSoap());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
        $this->assertSame(self::ORIGINAL_MSG_ID, $result->refToMessageId);
    }

    public function testReceiptSignalTimestamp(): void
    {
        /** @var As4ReceiptSignal $result */
        $result = $this->parser->parse($this->receiptSoap());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
        $this->assertSame(self::TIMESTAMP, $result->timestamp);
    }

    // ── Plain SOAP — Error signal ─────────────────────────────────────────────

    public function testPlainSoapErrorReturnsErrorSignal(): void
    {
        $result = $this->parser->parse($this->errorSoap());
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
    }

    public function testErrorSignalMessageId(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap());
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('error-001@receiver.example.com', $result->messageId);
    }

    public function testErrorSignalRefToMessageId(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap());
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame(self::ORIGINAL_MSG_ID, $result->refToMessageId);
    }

    public function testErrorSignalTimestamp(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap());
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame(self::TIMESTAMP, $result->timestamp);
    }

    public function testErrorSignalCategory(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(category: 'Security'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('Security', $result->category);
    }

    public function testErrorSignalErrorCode(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(errorCode: 'EBMS:0301'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('EBMS:0301', $result->errorCode);
    }

    public function testErrorSignalSeverityFailure(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(severity: 'failure'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('failure', $result->severity);
        $this->assertTrue($result->isFailure());
    }

    public function testErrorSignalSeverityWarning(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(severity: 'warning'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertFalse($result->isFailure());
    }

    public function testErrorSignalShortDescription(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(shortDescription: 'MissingReceipt'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('MissingReceipt', $result->shortDescription);
    }

    public function testErrorSignalDescription(): void
    {
        /** @var As4ErrorSignal $result */
        $result = $this->parser->parse($this->errorSoap(description: 'Receipt not received within timeout'));
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('Receipt not received within timeout', $result->description);
    }

    // ── No recognisable signal ────────────────────────────────────────────────

    public function testUserMessageReturnsNull(): void
    {
        $this->assertNull($this->parser->parse($this->noSignalSoap()));
    }

    public function testEmptyMessagingReturnsNull(): void
    {
        $this->assertNull($this->parser->parse($this->emptyMessagingSoap()));
    }

    public function testMalformedXmlReturnsNull(): void
    {
        $this->assertNull($this->parser->parse('<not valid xml <<>>'));
    }

    public function testPlainTextReturnsNull(): void
    {
        $this->assertNull($this->parser->parse('HTTP 200 OK'));
    }

    // ── MIME multipart — via Content-Type header ──────────────────────────────

    public function testMultipartReceiptViaContentType(): void
    {
        $body   = $this->wrapInMime($this->receiptSoap());
        $ct     = $this->mimeContentType();
        $result = $this->parser->parse($body, $ct);
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
        $this->assertSame(self::RECEIPT_MSG_ID, $result->messageId);
    }

    public function testMultipartErrorViaContentType(): void
    {
        $body   = $this->wrapInMime($this->errorSoap());
        $ct     = $this->mimeContentType();
        $result = $this->parser->parse($body, $ct);
        $this->assertInstanceOf(As4ErrorSignal::class, $result);
        $this->assertSame('EBMS:0010', $result->errorCode);
    }

    // ── MIME multipart — heuristic boundary detection ─────────────────────────

    public function testMultipartReceiptHeuristic(): void
    {
        // No contentType provided — parser must detect from body's first line
        $body   = $this->wrapInMime($this->receiptSoap());
        $result = $this->parser->parse($body);
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
        $this->assertSame(self::RECEIPT_MSG_ID, $result->messageId);
    }

    // ── MIME multipart — quoted boundary ─────────────────────────────────────

    public function testMultipartReceiptWithQuotedBoundary(): void
    {
        $boundary = 'MIMEBoundary_abc123xyz';
        $body     = $this->wrapInMime($this->receiptSoap(), $boundary);
        $ct       = 'multipart/related; type="application/soap+xml"; boundary="' . $boundary . '"';
        $result   = $this->parser->parse($body, $ct);
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
    }

    // ── MIME multipart — LF-only line endings ─────────────────────────────────

    public function testMultipartWithLfOnlyLineEndings(): void
    {
        $soap   = $this->receiptSoap();
        $body   = "--TestBoundary_AS4\n"
            . "Content-Type: application/soap+xml; charset=UTF-8\n"
            . "\n"
            . $soap . "\n"
            . "--TestBoundary_AS4--\n";
        $result = $this->parser->parse($body, $this->mimeContentType());
        $this->assertInstanceOf(As4ReceiptSignal::class, $result);
    }
}
