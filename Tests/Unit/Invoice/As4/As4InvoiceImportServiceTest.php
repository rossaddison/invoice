<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\As4\As4InvoiceImportService;
use App\Invoice\As4\UblXmlParser;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Ubl\Schema;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class As4InvoiceImportServiceTestFixture
{
    public function __construct(
        public readonly As4InvoiceImportService $service,
        public readonly ClientPeppolRepository&MockObject $clientPeppolRepository,
        public readonly InvRepository&MockObject $invRepository,
        public readonly InvItemRepository&MockObject $invItemRepository,
        public readonly SettingRepository&MockObject $settingRepository,
        public readonly LoggerInterface&MockObject $logger,
    ) {}
}

class As4InvoiceImportServiceTest extends TestCase
{
    private const string SENDER      = '0088:1234567890123';
    private const string ACTION      = 'busdox-docid-qns::urn:test:invoice:1.0';
    private const int    CLIENT_ID   = 7;
    private const int    INV_ID      = 42;

    private static string $invoiceXml;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        $invNs = Schema::INVOICE_NS;
        $cbcNs = Schema::CBC_NS;
        $cacNs = Schema::CAC_NS;

        self::$invoiceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ubl:Invoice xmlns:ubl="{$invNs}"
             xmlns:cbc="{$cbcNs}"
             xmlns:cac="{$cacNs}">
    <cbc:ID>INV-IMPORT-001</cbc:ID>
    <cbc:IssueDate>2026-03-01</cbc:IssueDate>
    <cbc:DocumentCurrencyCode>GBP</cbc:DocumentCurrencyCode>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cbc:EndpointID schemeID="0088">1234567890123</cbc:EndpointID>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:LegalMonetaryTotal>
        <cbc:PayableAmount currencyID="GBP">100.00</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    <cac:InvoiceLine>
        <cbc:ID>1</cbc:ID>
        <cbc:InvoicedQuantity unitCode="EA">1.00</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="GBP">100.00</cbc:LineExtensionAmount>
        <cac:Item><cbc:Name>Widget</cbc:Name></cac:Item>
        <cac:Price><cbc:PriceAmount currencyID="GBP">100.00</cbc:PriceAmount></cac:Price>
    </cac:InvoiceLine>
    <cac:InvoiceLine>
        <cbc:ID>2</cbc:ID>
        <cbc:InvoicedQuantity unitCode="EA">3.00</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="GBP">60.00</cbc:LineExtensionAmount>
        <cac:Item><cbc:Name>Gadget</cbc:Name></cac:Item>
        <cac:Price><cbc:PriceAmount currencyID="GBP">20.00</cbc:PriceAmount></cac:Price>
    </cac:InvoiceLine>
</ubl:Invoice>
XML;
    }

    // ── Fixture factory ───────────────────────────────────────────────────────

    private function createFixture(): As4InvoiceImportServiceTestFixture
    {
        $clientPeppolRepository = $this->createMock(ClientPeppolRepository::class);
        $invRepository          = $this->createMock(InvRepository::class);
        $invItemRepository      = $this->createMock(InvItemRepository::class);
        $settingRepository      = $this->createMock(SettingRepository::class);
        $logger                 = $this->createMock(LoggerInterface::class);

        $settingRepository->method('getSetting')->willReturn('');

        return new As4InvoiceImportServiceTestFixture(
            service: new As4InvoiceImportService(
                parser:                 new UblXmlParser(),
                clientPeppolRepository: $clientPeppolRepository,
                invRepository:          $invRepository,
                invItemRepository:      $invItemRepository,
                settingRepository:      $settingRepository,
                logger:                 $logger,
            ),
            clientPeppolRepository: $clientPeppolRepository,
            invRepository:          $invRepository,
            invItemRepository:      $invItemRepository,
            settingRepository:      $settingRepository,
            logger:                 $logger,
        );
    }

    private function stubbedClientPeppol(): ClientPeppol
    {
        $cp = new ClientPeppol(client_id: self::CLIENT_ID);
        $cp->setId(1);
        return $cp;
    }

    private function invRepositoryThatAssignsId(As4InvoiceImportServiceTestFixture $f): void
    {
        $f->invRepository
            ->method('save')
            ->willReturnCallback(static function (array|Inv|null $inv): void {
                if ($inv instanceof Inv) {
                    $inv->setId(self::INV_ID);
                }
            });
    }

    // ── Unknown sender ────────────────────────────────────────────────────────

    public function testUnknownSenderSkipsInvSave(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn(null);
        $f->invRepository->expects($this->never())->method('save');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testUnknownSenderSkipsInvItemSave(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn(null);
        $f->invItemRepository->expects($this->never())->method('save');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testUnknownSenderLogsWarning(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn(null);
        $f->logger->expects($this->once())->method('warning');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    // ── Known sender ──────────────────────────────────────────────────────────

    public function testKnownSenderSavesInv(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->invRepositoryThatAssignsId($f);
        $f->invRepository->expects($this->once())->method('save');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testKnownSenderSavesOneInvItemPerLine(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->invRepositoryThatAssignsId($f);
        $f->invItemRepository->expects($this->exactly(2))->method('save');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testKnownSenderLogsInfo(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->invRepositoryThatAssignsId($f);
        $f->logger->expects($this->once())->method('info');

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testSenderPartyIdSplitCorrectly(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository
            ->expects($this->once())
            ->method('findByEndpointId')
            ->with('1234567890123', '0088')
            ->willReturn(null);

        $f->service->handle(self::$invoiceXml, self::SENDER, self::ACTION);
    }

    public function testSenderWithNoColonUsedAsEndpointId(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository
            ->expects($this->once())
            ->method('findByEndpointId')
            ->with('nocolon', '')
            ->willReturn(null);

        $f->service->handle(self::$invoiceXml, 'nocolon', self::ACTION);
    }
}
