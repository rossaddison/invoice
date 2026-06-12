<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\SoapEnvelopeBuilder;
use App\Invoice\As4\SoapEnvelopeParams;
use App\Invoice\As4\WsSecuritySigner;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class SoapEnvelopeBuilderTest extends TestCase
{
    private const string NS_SOAP = 'http://www.w3.org/2003/05/soap-envelope';
    private const string NS_EB   = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/';

    private const string SENDER_ID      = '0088:1234567890123';
    private const string RECEIVER_ID    = '0088:9876543210987';
    private const string MESSAGE_ID     = 'msg-001@as4.test';
    private const string CONVERSATION_ID = 'conv-001';
    private const string SERVICE        = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    private const string ACTION         = 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    private const string PAYLOAD_CID    = 'invoice@as4.test';
    private const string TIMESTAMP      = '2024-06-01T10:00:00Z';

    private const string ROLE_INITIATOR = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/initiator';
    private const string ROLE_RESPONDER = 'http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/responder';
    private const string PARTY_TYPE_PREFIX = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:';
    private const string MIME_XML       = 'application/xml';

    private const string UBL_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
          <ID>TEST-001</ID>
          <IssueDate>2024-06-01</IssueDate>
        </Invoice>
        XML;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private SoapEnvelopeBuilder $builder;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private DOMDocument $envelope;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder  = new SoapEnvelopeBuilder();
        $this->envelope = $this->build();
    }

    private function build(?string $payloadXml = null): DOMDocument
    {
        return $this->builder->build(new SoapEnvelopeParams(
            messageId:        self::MESSAGE_ID,
            conversationId:   self::CONVERSATION_ID,
            senderPartyId:    self::SENDER_ID,
            receiverPartyId:  self::RECEIVER_ID,
            service:          self::SERVICE,
            action:           self::ACTION,
            payloadXml:       $payloadXml ?? self::UBL_XML,
            payloadContentId: self::PAYLOAD_CID,
            timestamp:        self::TIMESTAMP,
        ));
    }

    private function makeXPath(DOMDocument $doc): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('soap', self::NS_SOAP);
        $xpath->registerNamespace('eb', self::NS_EB);
        return $xpath;
    }

    private function requireNode(DOMDocument $doc, string $query): DOMElement
    {
        $nodes = $this->makeXPath($doc)->query($query);
        $node  = ($nodes !== false) ? $nodes->item(0) : null;
        self::assertInstanceOf(DOMElement::class, $node, "Expected element at: {$query}");
        return $node;
    }

    private function text(DOMDocument $doc, string $query): string
    {
        return trim($this->requireNode($doc, $query)->textContent);
    }

    // ── SOAP structure ────────────────────────────────────────────────────────

    public function testRootIsSOAP12Envelope(): void
    {
        $root = $this->envelope->documentElement;
        $this->assertNotNull($root);
        $this->assertSame(self::NS_SOAP, $root->namespaceURI);
        $this->assertSame('Envelope', $root->localName);
    }

    public function testHasSoapHeader(): void
    {
        $this->requireNode($this->envelope, '//soap:Header');
    }

    public function testHasSoapBody(): void
    {
        $this->requireNode($this->envelope, '//soap:Body');
    }

    // ── eb:Messaging ──────────────────────────────────────────────────────────

    public function testMessagingInHeader(): void
    {
        $this->requireNode($this->envelope, '//soap:Header/eb:Messaging');
    }

    public function testMessagingMustUnderstand(): void
    {
        $messaging = $this->requireNode($this->envelope, '//eb:Messaging');
        $value     = $messaging->getAttributeNS(self::NS_SOAP, 'mustUnderstand');
        $this->assertSame('true', $value);
    }

    // ── eb:MessageInfo ────────────────────────────────────────────────────────

    public function testMessageIdSet(): void
    {
        $this->assertSame(self::MESSAGE_ID, $this->text($this->envelope, '//eb:MessageId'));
    }

    public function testTimestampUsedAsProvided(): void
    {
        $this->assertSame(self::TIMESTAMP, $this->text($this->envelope, '//eb:Timestamp'));
    }

    public function testTimestampDefaultsToUtcNow(): void
    {
        $before = gmdate('Y-m-d\TH:i:s\Z');
        $doc    = $this->builder->build(new SoapEnvelopeParams(
            messageId:        'msg@test',
            conversationId:   'conv',
            senderPartyId:    self::SENDER_ID,
            receiverPartyId:  self::RECEIVER_ID,
            service:          self::SERVICE,
            action:           self::ACTION,
            payloadXml:       self::UBL_XML,
            payloadContentId: self::PAYLOAD_CID,
        ));
        $after = gmdate('Y-m-d\TH:i:s\Z');
        $ts    = $this->text($doc, '//eb:Timestamp');
        $this->assertGreaterThanOrEqual($before, $ts);
        $this->assertLessThanOrEqual($after, $ts);
    }

    // ── eb:PartyInfo ──────────────────────────────────────────────────────────

    public function testSenderPartyIdValue(): void
    {
        $partyId = $this->requireNode($this->envelope, '//eb:From/eb:PartyId');
        $this->assertSame('1234567890123', trim($partyId->textContent));
    }

    public function testSenderPartyIdType(): void
    {
        $partyId = $this->requireNode($this->envelope, '//eb:From/eb:PartyId');
        $this->assertSame(self::PARTY_TYPE_PREFIX . '0088', $partyId->getAttribute('type'));
    }

    public function testSenderRoleIsInitiator(): void
    {
        $this->assertSame(self::ROLE_INITIATOR, $this->text($this->envelope, '//eb:From/eb:Role'));
    }

    public function testReceiverPartyIdValue(): void
    {
        $partyId = $this->requireNode($this->envelope, '//eb:To/eb:PartyId');
        $this->assertSame('9876543210987', trim($partyId->textContent));
    }

    public function testReceiverPartyIdType(): void
    {
        $partyId = $this->requireNode($this->envelope, '//eb:To/eb:PartyId');
        $this->assertSame(self::PARTY_TYPE_PREFIX . '0088', $partyId->getAttribute('type'));
    }

    public function testReceiverRoleIsResponder(): void
    {
        $this->assertSame(self::ROLE_RESPONDER, $this->text($this->envelope, '//eb:To/eb:Role'));
    }

    public function testDifferentSchemesParsedCorrectly(): void
    {
        $doc    = $this->builder->build(new SoapEnvelopeParams(
            messageId:        self::MESSAGE_ID,
            conversationId:   self::CONVERSATION_ID,
            senderPartyId:    '9906:IT01234567890',
            receiverPartyId:  '9907:FR12345678901',
            service:          self::SERVICE,
            action:           self::ACTION,
            payloadXml:       self::UBL_XML,
            payloadContentId: self::PAYLOAD_CID,
            timestamp:        self::TIMESTAMP,
        ));
        $fromId = $this->requireNode($doc, '//eb:From/eb:PartyId');
        $this->assertSame('IT01234567890', trim($fromId->textContent));
        $this->assertSame(self::PARTY_TYPE_PREFIX . '9906', $fromId->getAttribute('type'));
    }

    // ── eb:CollaborationInfo ──────────────────────────────────────────────────

    public function testServiceSet(): void
    {
        $this->assertSame(self::SERVICE, $this->text($this->envelope, '//eb:Service'));
    }

    public function testServiceTypeAttribute(): void
    {
        $serviceEl = $this->requireNode($this->envelope, '//eb:Service');
        $this->assertSame(self::SERVICE, $serviceEl->getAttribute('type'));
    }

    public function testActionSet(): void
    {
        $this->assertSame(self::ACTION, $this->text($this->envelope, '//eb:Action'));
    }

    public function testConversationIdSet(): void
    {
        $this->assertSame(self::CONVERSATION_ID, $this->text($this->envelope, '//eb:ConversationId'));
    }

    // ── eb:PayloadInfo ────────────────────────────────────────────────────────

    public function testPayloadInfoPresent(): void
    {
        $this->requireNode($this->envelope, '//eb:PayloadInfo/eb:PartInfo');
    }

    public function testPartInfoHref(): void
    {
        $partInfo = $this->requireNode($this->envelope, '//eb:PartInfo');
        $this->assertSame('cid:' . self::PAYLOAD_CID, $partInfo->getAttribute('href'));
    }

    public function testMimeTypeProperty(): void
    {
        $property = $this->requireNode($this->envelope, "//eb:Property[@name='MimeType']");
        $this->assertSame(self::MIME_XML, trim($property->textContent));
    }

    // ── SOAP Body / payload ───────────────────────────────────────────────────

    public function testBodyContainsPayloadRootElement(): void
    {
        $xpath  = new DOMXPath($this->envelope);
        $xpath->registerNamespace('soap', self::NS_SOAP);
        $xpath->registerNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $nodes  = $xpath->query('//soap:Body/ubl:Invoice');
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length);
    }

    public function testInvalidPayloadXmlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payloadXml is not well-formed XML');
        $this->build('this is not XML <<>>');
    }

    public function testInvalidPartyIdFormatThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Party ID must be 'scheme:value'");
        $this->builder->build(new SoapEnvelopeParams(
            messageId:        self::MESSAGE_ID,
            conversationId:   self::CONVERSATION_ID,
            senderPartyId:    'no-colon-here',
            receiverPartyId:  self::RECEIVER_ID,
            service:          self::SERVICE,
            action:           self::ACTION,
            payloadXml:       self::UBL_XML,
            payloadContentId: self::PAYLOAD_CID,
            timestamp:        self::TIMESTAMP,
        ));
    }

    // ── Integration with WsSecuritySigner ────────────────────────────────────

    public function testEnvelopeIsCompatibleWithWsSecuritySigner(): void
    {
        $privateKey  = (string) file_get_contents(__DIR__ . '/test_key.pem');
        $certificate = (string) file_get_contents(__DIR__ . '/test_cert.pem');

        $signer = new WsSecuritySigner($privateKey, $certificate);
        $signed = $signer->sign($this->envelope);

        $xpath = new DOMXPath($signed);
        $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $sigNodes = $xpath->query('//wsse:Security/ds:Signature');
        $this->assertNotFalse($sigNodes);
        $this->assertSame(1, $sigNodes->length, 'Signed envelope should contain exactly one ds:Signature');
    }
}
