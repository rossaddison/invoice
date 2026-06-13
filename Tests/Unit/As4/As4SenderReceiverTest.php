<?php

declare(strict_types=1);

namespace Tests\Unit\As4;

use PHPUnit\Framework\TestCase;
use Invoice\As4\As4Receiver;
use Invoice\As4\As4Sender;
use Invoice\As4\As4SendResponse;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;

/**
 * Unit Tests for AS4 Sender
 */
class As4SenderTest extends TestCase
{
    private As4Sender $sender;
    private ClientInterface $httpClientMock;
    private RequestFactoryInterface $requestFactoryMock;
    private StreamFactoryInterface $streamFactoryMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactoryMock = $this->createMock(StreamFactoryInterface::class);

        $this->sender = new As4Sender(
            httpClient: $this->httpClientMock,
            requestFactory: $this->requestFactoryMock,
            streamFactory: $this->streamFactoryMock,
            logger: new NullLogger()
        );
    }

    /**
     * Test: Successful message transmission (HTTP 200)
     */
    public function testSendMessageSuccess(): void
    {
        // Mock HTTP response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($this->createMock(StreamInterface::class));
        
        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn($responseMock);

        // Mock request factory
        $requestMock = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        
        $this->requestFactoryMock->expects($this->once())
            ->method('createRequest')
            ->willReturn($requestMock);

        // Mock stream factory
        $streamMock = $this->createMock(StreamInterface::class);
        $this->streamFactoryMock->expects($this->once())
            ->method('createStream')
            ->willReturn($streamMock);

        // Send message
        $response = $this->sender->send(
            endpoint: 'https://as4.example.com/endpoint',
            soapMessage: '<soap:Envelope></soap:Envelope>',
            parts: []
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(200, $response->statusCode);
    }

    /**
     * Test: Retriable error (HTTP 503 Service Unavailable)
     */
    public function testSendMessageRetriable(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(503);
        $responseMock->method('getBody')->willReturn($this->createMock(StreamInterface::class));
        
        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn($responseMock);

        $requestMock = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();
        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);

        $streamMock = $this->createMock(StreamInterface::class);
        $this->streamFactoryMock->method('createStream')->willReturn($streamMock);

        $response = $this->sender->send(
            endpoint: 'https://as4.example.com/endpoint',
            soapMessage: '<soap:Envelope></soap:Envelope>',
            parts: []
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRetriable());
        $this->assertEquals(503, $response->statusCode);
    }
}

/**
 * Unit Tests for AS4 Receiver
 */
class As4ReceiverTest extends TestCase
{
    private As4Receiver $receiver;

    protected function setUp(): void
    {
        $this->receiver = new As4Receiver(new NullLogger());
    }

    /**
     * Test: Parse incoming UserMessage
     */
    public function testReceiveUserMessage(): void
    {
        $soapXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
  <soap:Header>
    <eb:Messaging soap:mustUnderstand="true">
      <eb:UserMessage>
        <eb:MessageInfo>
          <eb:Timestamp>2024-06-12T14:30:00Z</eb:Timestamp>
          <eb:MessageId>msg-1@sender.com</eb:MessageId>
        </eb:MessageInfo>
        <eb:PartyInfo>
          <eb:From>
            <eb:PartyId type="urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088">5412345000016</eb:PartyId>
            <eb:Role>Seller</eb:Role>
          </eb:From>
          <eb:To>
            <eb:PartyId type="urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088">5412345000023</eb:PartyId>
            <eb:Role>Buyer</eb:Role>
          </eb:To>
        </eb:PartyInfo>
        <eb:CollaborationInfo>
          <eb:ConversationId>conv-001</eb:ConversationId>
          <eb:Service>urn:service:invoice</eb:Service>
          <eb:Action>SendInvoice</eb:Action>
        </eb:CollaborationInfo>
        <eb:PayloadInfo>
          <eb:PartInfo href="cid:invoice-001@sender.com">
            <eb:PartProperties>
              <eb:Property name="MimeType">application/xml</eb:Property>
            </eb:PartProperties>
          </eb:PartInfo>
        </eb:PayloadInfo>
      </eb:UserMessage>
    </eb:Messaging>
  </soap:Header>
  <soap:Body/>
</soap:Envelope>
XML;

        $boundary = 'test-boundary';
        $multipart = <<<MIME
--{$boundary}
Content-Type: application/xop+xml; charset=UTF-8
Content-ID: <root.message@as4.example.org>

{$soapXml}

--{$boundary}
Content-Type: application/gzip
Content-ID: <invoice-001@sender.com>

binary-gzip-data-here
--{$boundary}--
MIME;

        $contentType = "multipart/related; boundary={$boundary}";
        $message = $this->receiver->receive($contentType, $multipart);

        $this->assertTrue($message->isUserMessage());
        $this->assertEquals('msg-1@sender.com', $message->messageId);
        $this->assertEquals('conv-001', $message->conversationId);
        $this->assertEquals('5412345000016', $message->senderPartyId);
        $this->assertEquals('5412345000023', $message->receiverPartyId);
        $this->assertEquals('urn:service:invoice', $message->service);
        $this->assertEquals('SendInvoice', $message->action);
    }

    /**
     * Test: Parse incoming Receipt signal
     */
    public function testReceiveReceipt(): void
    {
        $soapXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/"
               xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <soap:Header>
    <eb:Messaging soap:mustUnderstand="true">
      <eb:SignalMessage>
        <eb:MessageInfo>
          <eb:Timestamp>2024-06-12T14:31:00Z</eb:Timestamp>
          <eb:MessageId>receipt-1@receiver.com</eb:MessageId>
          <eb:RefToMessageId>msg-1@sender.com</eb:RefToMessageId>
        </eb:MessageInfo>
        <eb:Receipt>
          <ds:Reference URI="#body-id">
            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
            <ds:DigestValue>jyTXyVrh+cX3iJzgmxqiHdnnJQxcX6kTGHPES1YUYEs=</ds:DigestValue>
          </ds:Reference>
        </eb:Receipt>
      </eb:SignalMessage>
    </eb:Messaging>
  </soap:Header>
  <soap:Body/>
</soap:Envelope>
XML;

        $boundary = 'test-boundary';
        $multipart = <<<MIME
--{$boundary}
Content-Type: application/xop+xml; charset=UTF-8
Content-ID: <root.message@as4.example.org>

{$soapXml}

--{$boundary}--
MIME;

        $contentType = "multipart/related; boundary={$boundary}";
        $message = $this->receiver->receive($contentType, $multipart);

        $this->assertTrue($message->isReceipt());
        $this->assertEquals('receipt-1@receiver.com', $message->messageId);
        $this->assertEquals('msg-1@sender.com', $message->refToMessageId);
        $this->assertEquals('jyTXyVrh+cX3iJzgmxqiHdnnJQxcX6kTGHPES1YUYEs=', $message->digestValue);
    }

    /**
     * Test: Parse incoming Error signal
     */
    public function testReceiveError(): void
    {
        $soapXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
               xmlns:eb="http://docs.oasis-open.org/ebxml-msg/ebms/v3.0/ns/core/200704/">
  <soap:Header>
    <eb:Messaging soap:mustUnderstand="true">
      <eb:SignalMessage>
        <eb:MessageInfo>
          <eb:Timestamp>2024-06-12T14:31:15Z</eb:Timestamp>
          <eb:MessageId>error-1@receiver.com</eb:MessageId>
          <eb:RefToMessageId>msg-1@sender.com</eb:RefToMessageId>
        </eb:MessageInfo>
        <eb:Error category="Communication" errorCode="EBMS:0202">
          <eb:ErrorCode>EBMS:0202</eb:ErrorCode>
          <eb:ShortDescription>DeliveryFailure</eb:ShortDescription>
          <eb:Description>Consumer not available</eb:Description>
        </eb:Error>
      </eb:SignalMessage>
    </eb:Messaging>
  </soap:Header>
  <soap:Body/>
</soap:Envelope>
XML;

        $boundary = 'test-boundary';
        $multipart = <<<MIME
--{$boundary}
Content-Type: application/xop+xml; charset=UTF-8
Content-ID: <root.message@as4.example.org>

{$soapXml}

--{$boundary}--
MIME;

        $contentType = "multipart/related; boundary={$boundary}";
        $message = $this->receiver->receive($contentType, $multipart);

        $this->assertTrue($message->isError());
        $this->assertEquals('error-1@receiver.com', $message->messageId);
        $this->assertEquals('msg-1@sender.com', $message->refToMessageId);
        $this->assertEquals('EBMS:0202', $message->errorCode);
        $this->assertEquals('DeliveryFailure', $message->errorShortDescription);
        $this->assertEquals('Consumer not available', $message->errorDescription);
    }
}
