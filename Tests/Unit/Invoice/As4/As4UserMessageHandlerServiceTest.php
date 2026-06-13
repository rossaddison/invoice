<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4DuplicateDetectorInterface;
use App\Invoice\As4\As4InboundMessage;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4PayloadHandlerInterface;
use App\Invoice\As4\As4ReceiptGeneratorInterface;
use App\Invoice\As4\As4UserMessageHandlerService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class As4UserMessageHandlerServiceTestFixture
{
    public function __construct(
        public readonly As4UserMessageHandlerService $service,
        public readonly As4MessageRepositoryInterface&MockObject $repository,
        public readonly As4DuplicateDetectorInterface&MockObject $duplicateDetector,
        public readonly As4ReceiptGeneratorInterface&MockObject $receiptGenerator,
        public readonly As4PayloadHandlerInterface&MockObject $payloadHandler,
    ) {}
}

#[AllowMockObjectsWithoutExpectations]
class As4UserMessageHandlerServiceTest extends TestCase
{
    private const string MSG_ID     = 'msg-001@test.local';
    private const string RECEIPT_XML = '<receipt/>';
    private const string SENDER     = '0088:1234567890123';
    private const string ACTION     = 'busdox-docid-qns::urn:test:invoice:1.0';
    private const string PAYLOAD_A  = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"/>';
    private const string PAYLOAD_B  = '<CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2"/>';
    private const string SOAP_BODY  = '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"/>';

    // ── Fixture factory ───────────────────────────────────────────────────────

    private function createFixture(): As4UserMessageHandlerServiceTestFixture
    {
        $repository        = $this->createMock(As4MessageRepositoryInterface::class);
        $duplicateDetector = $this->createMock(As4DuplicateDetectorInterface::class);
        $receiptGenerator  = $this->createMock(As4ReceiptGeneratorInterface::class);
        $payloadHandler    = $this->createMock(As4PayloadHandlerInterface::class);

        return new As4UserMessageHandlerServiceTestFixture(
            service: new As4UserMessageHandlerService(
                repository:        $repository,
                duplicateDetector: $duplicateDetector,
                receiptGenerator:  $receiptGenerator,
                payloadHandler:    $payloadHandler,
                logger:            $this->createStub(LoggerInterface::class),
            ),
            repository:        $repository,
            duplicateDetector: $duplicateDetector,
            receiptGenerator:  $receiptGenerator,
            payloadHandler:    $payloadHandler,
        );
    }

    private function userMessage(string $messageId = self::MSG_ID): As4InboundMessage
    {
        return new As4InboundMessage(
            type:            'UserMessage',
            messageId:       $messageId,
            action:          self::ACTION,
            senderPartyId:   self::SENDER,
            receiverPartyId: '0088:9876543210987',
            xmlBody:         self::SOAP_BODY,
            payloads:        [self::PAYLOAD_A, self::PAYLOAD_B],
        );
    }

    // ── Duplicate detection ───────────────────────────────────────────────────

    public function testDuplicateMessageNeverSaves(): void
    {
        $f = $this->createFixture();
        $f->duplicateDetector->method('isDuplicate')->willReturn(true);
        $f->repository->expects($this->never())->method('save');

        $f->service->handle($this->userMessage());
    }

    public function testDuplicateMessageNeverCallsPayloadHandler(): void
    {
        $f = $this->createFixture();
        $f->duplicateDetector->method('isDuplicate')->willReturn(true);
        $f->payloadHandler->expects($this->never())->method('handle');

        $f->service->handle($this->userMessage());
    }

    public function testDuplicateMessageReturnsReceiptXml(): void
    {
        $f = $this->createFixture();
        $f->duplicateDetector->method('isDuplicate')->willReturn(true);
        $f->receiptGenerator->method('generate')->willReturn(self::RECEIPT_XML);

        $this->assertSame(self::RECEIPT_XML, $f->service->handle($this->userMessage()));
    }

    // ── New message ───────────────────────────────────────────────────────────

    public function testNewMessageSavesRecord(): void
    {
        $f = $this->createFixture();
        $f->repository->expects($this->once())->method('save');

        $f->service->handle($this->userMessage());
    }

    public function testNewMessageCallsPayloadHandlerForEachPayload(): void
    {
        $f = $this->createFixture();
        $f->payloadHandler->expects($this->exactly(2))->method('handle');

        $f->service->handle($this->userMessage());
    }

    public function testNewMessageReturnsReceiptXml(): void
    {
        $f = $this->createFixture();
        $f->receiptGenerator->method('generate')->willReturn(self::RECEIPT_XML);

        $this->assertSame(self::RECEIPT_XML, $f->service->handle($this->userMessage()));
    }

    public function testReceiptGeneratorCalledWithCorrectMessageId(): void
    {
        $f = $this->createFixture();
        $f->receiptGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(self::MSG_ID, $this->anything())
            ->willReturn(self::RECEIPT_XML);

        $f->service->handle($this->userMessage(self::MSG_ID));
    }

    public function testPayloadHandlerCalledWithCorrectSenderAndAction(): void
    {
        $f = $this->createFixture();
        $f->payloadHandler
            ->expects($this->exactly(2))
            ->method('handle')
            ->with($this->anything(), self::SENDER, self::ACTION);

        $f->service->handle($this->userMessage());
    }
}
