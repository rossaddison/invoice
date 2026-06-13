<?php

declare(strict_types=1);

namespace Tests\Unit\As4;

use PHPUnit\Framework\TestCase;
use Invoice\As4\As4Constants;
use Invoice\As4\As4MessageBuilder;
use Invoice\As4\PMode;
use DOMDocument;

/**
 * Unit Tests for AS4 Message Construction
 * 
 * Tests ebMS3 message structure compliance with eDelivery AS4 2.0 spec
 */
class As4MessageBuilderTest extends TestCase
{
    private As4MessageBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new As4MessageBuilder();
    }

    /**
     * Test: SOAP envelope has correct namespace declarations
     */
    public function testSoapEnvelopeNamespaces(): void
    {
        $xml = $this->builder->getXml();
        $this->assertStringContainsString('xmlns:soap="' . As4Constants::SOAP_NS . '"', $xml);
        $this->assertStringContainsString('xmlns:wsse="' . As4Constants::WSS_NS . '"', $xml);
        $this->assertStringContainsString('xmlns:eb="' . As4Constants::EBMS3_NS . '"', $xml);
        $this->assertStringContainsString('xmlns:ds="' . As4Constants::XMLDSIG_NS . '"', $xml);
    }

    /**
     * Test: SOAP body is empty (required per section 3.2.3)
     */
    public function testSoapBodyEmpty(): void
    {
        $xml = $this->builder->getXml();
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('soap', As4Constants::SOAP_NS);
        $bodies = $xpath->query('//soap:Body');

        $this->assertEquals(1, $bodies->length, 'SOAP Body not found');

        $body = $bodies->item(0);
        // Body should be empty (no child elements with content)
        $this->assertEquals('', trim($body->nodeValue ?? ''));
    }

    /**
     * Test: UserMessage structure with mandatory elements
     */
    public function testUserMessageStructure(): void
    {
        $messageId = 'uuid-1234@sender.example.com';
        $conversationId = 'conv-001';

        $userMsg = $this->builder->addUserMessage(
            messageId: $messageId,
            conversationId: $conversationId,
            service: 'urn:service:invoice',
            action: 'SendInvoice',
            senderPartyId: '5412345000016',
            senderRole: 'Seller',
            receiverPartyId: '5412345000023',
            receiverRole: 'Buyer'
        );

        $xml = $this->builder->getXml();
        $this->assertStringContainsString($messageId, $xml);
        $this->assertStringContainsString($conversationId, $xml);
        $this->assertStringContainsString('5412345000016', $xml);
        $this->assertStringContainsString('5412345000023', $xml);

        // Verify PartyId has type attribute (ISO 6523 per section 3.4.1)
        $this->assertStringContainsString('type="urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088"', $xml);
    }

    /**
     * Test: PayloadInfo with compression metadata
     */
    public function testPayloadInfoWithCompression(): void
    {
        $userMsg = $this->builder->addUserMessage(
            messageId: 'msg-1@sender.com',
            conversationId: 'conv-001',
            service: 'urn:service:invoice',
            action: 'SendInvoice',
            senderPartyId: '5412345000016',
            senderRole: 'Seller',
            receiverPartyId: '5412345000023',
            receiverRole: 'Buyer'
        );

        $this->builder->addPayloadInfo($userMsg, [
            [
                'contentId' => 'invoice-001@sender.com',
                'mimeType' => 'application/xml',
                'charset' => 'utf-8',
                'compressed' => true,
            ],
        ]);

        $xml = $this->builder->getXml();

        // Verify PartInfo references the MIME part
        $this->assertStringContainsString('href="cid:invoice-001@sender.com"', $xml);

        // Verify MimeType property
        $this->assertStringContainsString('<eb:Property name="MimeType">application/xml</eb:Property>', $xml);

        // Verify CharacterSet property
        $this->assertStringContainsString('<eb:Property name="CharacterSet">utf-8</eb:Property>', $xml);

        // Verify CompressionType property (mandatory when compressed)
        $this->assertStringContainsString('<eb:Property name="CompressionType">application/gzip</eb:Property>', $xml);
    }

    /**
     * Test: SignalMessage with Receipt
     */
    public function testSignalMessageWithReceipt(): void
    {
        $this->builder->addSignalMessage(
            signalId: 'receipt-1@receiver.com',
            refToMessageId: 'msg-1@sender.com',
            receiptData: [
                'refId' => 'body-id-123',
                'digestValue' => base64_encode(hash('sha256', 'test-content', true)),
            ]
        );

        $xml = $this->builder->getXml();

        $this->assertStringContainsString('receipt-1@receiver.com', $xml);
        $this->assertStringContainsString('msg-1@sender.com', $xml);
        $this->assertStringContainsString('<eb:Receipt>', $xml);
    }

    /**
     * Test: SignalMessage with Error
     */
    public function testSignalMessageWithError(): void
    {
        $this->builder->addSignalMessage(
            signalId: 'error-1@receiver.com',
            refToMessageId: 'msg-1@sender.com',
            errorData: [
                'code' => 'EBMS:0202',
                'category' => 'Communication',
                'shortDescription' => 'DeliveryFailure',
                'description' => 'Recipient endpoint not responding',
            ]
        );

        $xml = $this->builder->getXml();

        $this->assertStringContainsString('error-1@receiver.com', $xml);
        $this->assertStringContainsString('EBMS:0202', $xml);
        $this->assertStringContainsString('DeliveryFailure', $xml);
        $this->assertStringContainsString('<eb:Error', $xml);
    }

    /**
     * Test: RefToMessageId for Two-Way exchanges (responses)
     */
    public function testRefToMessageIdForResponse(): void
    {
        $requestMessageId = 'request-uuid@buyer.com';

        $userMsg = $this->builder->addUserMessage(
            messageId: 'response-uuid@seller.com',
            conversationId: 'conv-order-001',
            service: 'urn:service:order:response',
            action: 'OrderResponse',
            senderPartyId: '5412345000016',
            senderRole: 'Seller',
            receiverPartyId: '5412345000023',
            receiverRole: 'Buyer',
            refToMessageId: $requestMessageId  // Correlates response to request
        );

        $xml = $this->builder->getXml();

        $this->assertStringContainsString("<eb:RefToMessageId>{$requestMessageId}</eb:RefToMessageId>", $xml);
    }

    /**
     * Test: MessageProperties for Four Corner Topology
     */
    public function testMessagePropertiesForFourCorner(): void
    {
        $properties = [
            'originalSender' => '5412345000016',       // C1
            'finalRecipient' => '5412345000023',       // C4
            'trackingIdentifier' => 'tracking-ref-001',
        ];

        $userMsg = $this->builder->addUserMessage(
            messageId: 'msg-1@ap-c2.com',
            conversationId: 'conv-001',
            service: 'urn:service:invoice',
            action: 'SendInvoice',
            senderPartyId: '5412345000016',     // C2 Access Point
            senderRole: 'SenderAccessPoint',
            receiverPartyId: '5412345000009',   // C3 Access Point
            receiverRole: 'ReceiverAccessPoint',
            properties: $properties
        );

        $xml = $this->builder->getXml();

        // Verify properties are included
        $this->assertStringContainsString('name="originalSender">5412345000016</eb:Property>', $xml);
        $this->assertStringContainsString('name="finalRecipient">5412345000023</eb:Property>', $xml);
        $this->assertStringContainsString('name="trackingIdentifier">tracking-ref-001</eb:Property>', $xml);
    }

    /**
     * Test: WS-Security Timestamp
     */
    public function testWsSecurityTimestamp(): void
    {
        $this->builder->addTimestamp(expirationSeconds: 3600);

        $xml = $this->builder->getXml();

        $this->assertStringContainsString('<wsu:Timestamp', $xml);
        $this->assertStringContainsString('<wsu:Created>', $xml);
        $this->assertStringContainsString('<wsu:Expires>', $xml);
        $this->assertStringContainsString('wsu:Id="TS-', $xml);
    }

    /**
     * Test: BinarySecurityToken with X.509 certificate
     */
    public function testBinarySecurityToken(): void
    {
        $certData = base64_encode('dummy-certificate-data');

        $this->builder->addBinarySecurityToken(
            certData: $certData,
            tokenId: 'X509-test-token'
        );

        $xml = $this->builder->getXml();

        $this->assertStringContainsString('wsse:BinarySecurityToken', $xml);
        $this->assertStringContainsString('wsu:Id="X509-test-token"', $xml);
        $this->assertStringContainsString('EncodingType="' . As4Constants::WSS_ENCODING_BASE64 . '"', $xml);
        $this->assertStringContainsString('ValueType="' . As4Constants::WSS_TOKEN_X509V3 . '"', $xml);
        $this->assertStringContainsString($certData, $xml);
    }

    /**
     * Test: XML is valid and parseable
     */
    public function testGeneratedXmlIsValid(): void
    {
        $this->builder->addUserMessage(
            messageId: 'msg-1@sender.com',
            conversationId: 'conv-001',
            service: 'urn:service:test',
            action: 'TestAction',
            senderPartyId: '5412345000016',
            senderRole: 'Sender',
            receiverPartyId: '5412345000023',
            receiverRole: 'Receiver'
        );

        $xml = $this->builder->getXml();

        // Should parse without errors
        $doc = new DOMDocument();
        $result = @$doc->loadXML($xml);

        $this->assertTrue($result, 'Generated XML is not valid');

        // Verify root element
        $this->assertEquals('Envelope', $doc->documentElement->localName);
    }
}

/**
 * Unit Tests for P-Mode Configuration
 */
class PModeTest extends TestCase
{
    /**
     * Test: P-Mode construction with basic parameters
     */
    public function testPmodeConstruction(): void
    {
        $pmode = new PMode(
            initiatorParty: '5412345000016',
            responderParty: '5412345000023',
            responderProtocolAddress: 'https://buyer-as4.example.com/as4',
            service: 'urn:service:invoice',
            action: 'SendInvoice'
        );

        $this->assertEquals('5412345000016', $pmode->getInitiatorParty());
        $this->assertEquals('5412345000023', $pmode->getResponderParty());
        $this->assertEquals('https://buyer-as4.example.com/as4', $pmode->getResponderProtocolAddress());
    }

    /**
     * Test: P-Mode fluent interface
     */
    public function testPmodeFluentInterface(): void
    {
        $pmode = (new PMode(
            initiatorParty: '5412345000016',
            responderParty: '5412345000023',
            responderProtocolAddress: 'https://example.com/as4',
            service: 'urn:service:invoice',
            action: 'SendInvoice'
        ))
            ->setInitiatorRole('Seller')
            ->setResponderRole('Buyer')
            ->setMep(As4Constants::MEP_ONE_WAY)
            ->setMepBinding(As4Constants::MEPBINDING_PUSH)
            ->setCompressionEnabled(true)
            ->setMaxRetries(5)
            ->setRetryIntervalSeconds(600);

        $this->assertEquals('Seller', $pmode->getInitiatorRole());
        $this->assertEquals('Buyer', $pmode->getResponderRole());
        $this->assertEquals(As4Constants::MEP_ONE_WAY, $pmode->getMep());
        $this->assertEquals(As4Constants::MEPBINDING_PUSH, $pmode->getMepBinding());
        $this->assertTrue($pmode->isCompressionEnabled());
        $this->assertEquals(5, $pmode->getMaxRetries());
        $this->assertEquals(600, $pmode->getRetryIntervalSeconds());
    }

    /**
     * Test: P-Mode serialization to array
     */
    public function testPmodeToArray(): void
    {
        $pmode = (new PMode(
            initiatorParty: '5412345000016',
            responderParty: '5412345000023',
            responderProtocolAddress: 'https://example.com/as4',
            service: 'urn:service:invoice',
            action: 'SendInvoice'
        ))
            ->setInitiatorRole('Seller')
            ->setResponderRole('Buyer');

        $config = $pmode->toArray();

        $this->assertIsArray($config);
        $this->assertEquals('5412345000016', $config['initiator']['party']);
        $this->assertEquals('5412345000023', $config['responder']['party']);
        $this->assertEquals('Seller', $config['initiator']['role']);
        $this->assertEquals('Buyer', $config['responder']['role']);
    }

    /**
     * Test: Default P-Mode values per Common Profile
     */
    public function testPmodeDefaults(): void
    {
        $pmode = new PMode(
            initiatorParty: '5412345000016',
            responderParty: '5412345000023',
            responderProtocolAddress: 'https://example.com/as4',
            service: 'urn:service:test',
            action: 'TestAction'
        );

        // Verify defaults per section 3.5
        $this->assertTrue($pmode->isSigningEnabled());
        $this->assertTrue($pmode->isEncryptionEnabled());
        $this->assertTrue($pmode->shouldSendReceipt());
        $this->assertTrue($pmode->shouldSignReceipt());
        $this->assertTrue($pmode->shouldReportAsResponse());
        $this->assertTrue($pmode->isReceptionAwarenessEnabled());
        $this->assertTrue($pmode->isDuplicateDetectionEnabled());
    }
}
