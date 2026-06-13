<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4InboundMessage;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4MessageState;
use App\Invoice\As4\As4ParseException;
use App\Invoice\As4\As4ReceiveController;
use App\Invoice\As4\As4Receiver;
use App\Invoice\As4\As4UserMessageHandlerInterface;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class As4ReceiveControllerTestFixture
{
    public function __construct(
        public readonly As4ReceiveController $controller,
        public readonly As4Receiver&MockObject $receiver,
        public readonly As4UserMessageHandlerInterface&MockObject $userMessageHandler,
        public readonly As4MessageRepositoryInterface&MockObject $repository,
    ) {}
}

#[AllowMockObjectsWithoutExpectations]
class As4ReceiveControllerTest extends TestCase
{
    private const string MULTIPART_CT = 'multipart/related; boundary=AS4Boundary';
    private const string SOAP_BODY    = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"/>';
    private const string RECEIPT_XML  = '<receipt/>';
    private const string MSG_ID       = 'msg-001@test.local';
    private const string REF_ID       = 'orig-001@test.local';

    // ── Fixture factory ───────────────────────────────────────────────────────

    private function createFixture(): As4ReceiveControllerTestFixture
    {
        $receiver           = $this->createMock(As4Receiver::class);
        $userMessageHandler = $this->createMock(As4UserMessageHandlerInterface::class);
        $repository         = $this->createMock(As4MessageRepositoryInterface::class);

        return new As4ReceiveControllerTestFixture(
            controller: new As4ReceiveController(
                receiver:           $receiver,
                userMessageHandler: $userMessageHandler,
                repository:         $repository,
                responseFactory:    new ResponseFactory(),
                streamFactory:      new StreamFactory(),
                logger:             $this->createStub(LoggerInterface::class),
            ),
            receiver:           $receiver,
            userMessageHandler: $userMessageHandler,
            repository:         $repository,
        );
    }

    private function makeRequest(): Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeaderLine')->willReturn(self::MULTIPART_CT);
        $request->method('getBody')->willReturn((new StreamFactory())->createStream(''));
        return $request;
    }

    private function userMessage(string $messageId = self::MSG_ID): As4InboundMessage
    {
        return new As4InboundMessage(
            type:            'UserMessage',
            messageId:       $messageId,
            conversationId:  'conv-001',
            service:         'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:          'busdox-docid-qns::urn:test:invoice:1.0',
            senderPartyId:   '0088:1234567890123',
            receiverPartyId: '0088:9876543210987',
            xmlBody:         self::SOAP_BODY,
        );
    }

    private function receiptSignal(string $refToMessageId = self::REF_ID): As4InboundMessage
    {
        return new As4InboundMessage(
            type:           'Receipt',
            messageId:      'rcpt-001@test.local',
            refToMessageId: $refToMessageId,
        );
    }

    private function errorSignal(string $refToMessageId = self::REF_ID): As4InboundMessage
    {
        return new As4InboundMessage(
            type:                  'Error',
            messageId:             'err-001@test.local',
            refToMessageId:        $refToMessageId,
            errorCode:             'EBMS:0202',
            errorShortDescription: 'MIME_PROBLEM',
        );
    }

    private function makeSentMessage(string $messageId = self::REF_ID): As4Message
    {
        $m = new As4Message(
            messageId:        $messageId,
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       'Seller',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     'Buyer',
            service:          'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:           'busdox-docid-qns::urn:test:invoice:1.0',
            receiverEndpoint: 'https://ap.example.com/as4',
            soapMessage:      self::SOAP_BODY,
        );
        $m->markSent();
        return $m;
    }

    // ── Parse failure ─────────────────────────────────────────────────────────

    public function testReturns500SoapFaultWhenParserThrows(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willThrowException(new As4ParseException('bad MIME'));

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('env:Fault', (string) $response->getBody());
    }

    public function testSoapFaultResponseHasSoapContentType(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willThrowException(new As4ParseException('x'));

        $this->assertStringContainsString(
            'application/soap+xml',
            $f->controller->receive($this->makeRequest())->getHeaderLine('Content-Type'),
        );
    }

    // ── UserMessage ───────────────────────────────────────────────────────────

    public function testUserMessageHandlerCalledOnce(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willReturn($this->userMessage());
        $f->userMessageHandler->expects($this->once())->method('handle')->willReturn(self::RECEIPT_XML);

        $f->controller->receive($this->makeRequest());
    }

    public function testUserMessageResponseHasStatus200AndReceiptBody(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willReturn($this->userMessage());
        $f->userMessageHandler->method('handle')->willReturn(self::RECEIPT_XML);

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(self::RECEIPT_XML, (string) $response->getBody());
    }

    public function testUserMessageResponseHasSoapXmlContentType(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willReturn($this->userMessage());
        $f->userMessageHandler->method('handle')->willReturn(self::RECEIPT_XML);

        $this->assertStringContainsString(
            'application/soap+xml',
            $f->controller->receive($this->makeRequest())->getHeaderLine('Content-Type'),
        );
    }

    // ── Inbound Receipt signal ────────────────────────────────────────────────

    public function testAppliesInboundReceiptToOutboundMessage(): void
    {
        $f        = $this->createFixture();
        $outbound = $this->makeSentMessage(self::REF_ID);
        $f->receiver->method('receive')->willReturn($this->receiptSignal(self::REF_ID));
        $f->repository->method('findByMessageId')->willReturn($outbound);
        $f->repository->expects($this->once())->method('save');

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(As4MessageState::receiptReceived, $outbound->getState());
    }

    public function testReturns200ForReceiptWithUnknownRef(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willReturn($this->receiptSignal('unknown@test.local'));
        $f->repository->method('findByMessageId')->willReturn(null);
        $f->repository->expects($this->never())->method('save');

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
    }

    // ── Inbound Error signal ──────────────────────────────────────────────────

    public function testAppliesInboundErrorToOutboundMessage(): void
    {
        $f        = $this->createFixture();
        $outbound = $this->makeSentMessage(self::REF_ID);
        $f->receiver->method('receive')->willReturn($this->errorSignal(self::REF_ID));
        $f->repository->method('findByMessageId')->willReturn($outbound);
        $f->repository->expects($this->once())->method('save');

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(As4MessageState::failed, $outbound->getState());
        $this->assertSame('EBMS:0202', $outbound->getErrorCode());
    }

    public function testReturns200ForErrorWithUnknownRef(): void
    {
        $f = $this->createFixture();
        $f->receiver->method('receive')->willReturn($this->errorSignal('unknown@test.local'));
        $f->repository->method('findByMessageId')->willReturn(null);
        $f->repository->expects($this->never())->method('save');

        $response = $f->controller->receive($this->makeRequest());

        $this->assertSame(200, $response->getStatusCode());
    }
}
