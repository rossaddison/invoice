<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4InvoiceImportService;
use App\Invoice\As4\As4OrderImportService;
use App\Invoice\As4\As4PayloadRouter;
use App\Invoice\Ubl\Schema;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers As4PayloadRouter's own routing decision only — which of its
 * three delegate outcomes fires for a given payload root element.
 * As4InvoiceImportService/As4OrderImportService's own actual import
 * behaviour is covered by their own dedicated test files.
 */
#[AllowMockObjectsWithoutExpectations]
class As4PayloadRouterTest extends TestCase
{
    private const string SENDER = '0088:1234567890123';
    private const string ACTION = 'busdox-docid-qns::urn:test:1.0';

    private function invoiceXml(): string
    {
        $ns = Schema::INVOICE_NS;
        return "<?xml version=\"1.0\"?><ubl:Invoice xmlns:ubl=\"{$ns}\"><cbc:ID>X</cbc:ID></ubl:Invoice>";
    }

    private function creditNoteXml(): string
    {
        $ns = Schema::CREDIT_NOTE_NS;
        return "<?xml version=\"1.0\"?><ubl:CreditNote xmlns:ubl=\"{$ns}\"><cbc:ID>X</cbc:ID></ubl:CreditNote>";
    }

    private function orderXml(): string
    {
        $ns = Schema::ORDER_NS;
        return "<?xml version=\"1.0\"?><ubl:Order xmlns:ubl=\"{$ns}\"><cbc:ID>X</cbc:ID></ubl:Order>";
    }

    private function unrelatedXml(): string
    {
        return '<?xml version="1.0"?><SomethingElse><cbc:ID>X</cbc:ID></SomethingElse>';
    }

    public function testInvoiceRootRoutesToInvoiceImporter(): void
    {
        $invoiceService = $this->createMock(As4InvoiceImportService::class);
        $orderService   = $this->createMock(As4OrderImportService::class);
        $logger         = $this->createMock(LoggerInterface::class);

        $invoiceService->expects($this->once())->method('handle')
            ->with($this->invoiceXml(), self::SENDER, self::ACTION);
        $orderService->expects($this->never())->method('handle');

        (new As4PayloadRouter($invoiceService, $orderService, $logger))
            ->handle($this->invoiceXml(), self::SENDER, self::ACTION);
    }

    public function testCreditNoteRootRoutesToInvoiceImporter(): void
    {
        $invoiceService = $this->createMock(As4InvoiceImportService::class);
        $orderService   = $this->createMock(As4OrderImportService::class);
        $logger         = $this->createMock(LoggerInterface::class);

        $invoiceService->expects($this->once())->method('handle')
            ->with($this->creditNoteXml(), self::SENDER, self::ACTION);
        $orderService->expects($this->never())->method('handle');

        (new As4PayloadRouter($invoiceService, $orderService, $logger))
            ->handle($this->creditNoteXml(), self::SENDER, self::ACTION);
    }

    public function testOrderRootRoutesToOrderImporter(): void
    {
        $invoiceService = $this->createMock(As4InvoiceImportService::class);
        $orderService   = $this->createMock(As4OrderImportService::class);
        $logger         = $this->createMock(LoggerInterface::class);

        $orderService->expects($this->once())->method('handle')
            ->with($this->orderXml(), self::SENDER, self::ACTION);
        $invoiceService->expects($this->never())->method('handle');

        (new As4PayloadRouter($invoiceService, $orderService, $logger))
            ->handle($this->orderXml(), self::SENDER, self::ACTION);
    }

    public function testUnrecognizedRootLogsWarningAndSkipsBothImporters(): void
    {
        $invoiceService = $this->createMock(As4InvoiceImportService::class);
        $orderService   = $this->createMock(As4OrderImportService::class);
        $logger         = $this->createMock(LoggerInterface::class);

        $invoiceService->expects($this->never())->method('handle');
        $orderService->expects($this->never())->method('handle');
        $logger->expects($this->once())->method('warning');

        (new As4PayloadRouter($invoiceService, $orderService, $logger))
            ->handle($this->unrelatedXml(), self::SENDER, self::ACTION);
    }

    public function testInvalidXmlLogsWarningAndSkipsBothImporters(): void
    {
        $invoiceService = $this->createMock(As4InvoiceImportService::class);
        $orderService   = $this->createMock(As4OrderImportService::class);
        $logger         = $this->createMock(LoggerInterface::class);

        $invoiceService->expects($this->never())->method('handle');
        $orderService->expects($this->never())->method('handle');
        $logger->expects($this->once())->method('warning');

        (new As4PayloadRouter($invoiceService, $orderService, $logger))
            ->handle('<broken xml <<', self::SENDER, self::ACTION);
    }
}
