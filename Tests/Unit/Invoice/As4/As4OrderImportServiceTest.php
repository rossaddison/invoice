<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\As4\As4OrderImportService;
use App\Invoice\As4\UblOrderXmlParser;
use App\Invoice\Client\ClientRepository;
use App\Invoice\ClientPeppol\ClientPeppolRepositoryInterface;
use App\Invoice\Group\GroupRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepositoryInterface;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Ubl\Schema;
use App\User\UserRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class As4OrderImportServiceTestFixture
{
    public function __construct(
        public readonly As4OrderImportService $service,
        public readonly ClientPeppolRepositoryInterface&MockObject $clientPeppolRepository,
        public readonly ClientRepository&MockObject $clientRepository,
        public readonly GroupRepository&MockObject $groupRepository,
        public readonly UserRepository&MockObject $userRepository,
        public readonly TaxRateRepository&MockObject $taxRateRepository,
        public readonly SalesOrderRepository&MockObject $salesOrderRepository,
        public readonly SalesOrderItemRepository&MockObject $salesOrderItemRepository,
        public readonly SettingRepositoryInterface&MockObject $settingRepository,
        public readonly LoggerInterface&MockObject $logger,
    ) {}
}

#[AllowMockObjectsWithoutExpectations]
class As4OrderImportServiceTest extends TestCase
{
    private const string SENDER    = '0088:9876543210987';
    private const string ACTION    = 'busdox-docid-qns::urn:test:order:1.0';
    private const int    CLIENT_ID = 7;
    private const int    SO_ID     = 55;

    private static string $orderXml;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        $orderNs = Schema::ORDER_NS;
        $cbcNs   = Schema::CBC_NS;
        $cacNs   = Schema::CAC_NS;

        self::$orderXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ubl:Order xmlns:ubl="{$orderNs}"
           xmlns:cbc="{$cbcNs}"
           xmlns:cac="{$cacNs}">
    <cbc:ID>PO-IMPORT-001</cbc:ID>
    <cbc:IssueDate>2026-06-01</cbc:IssueDate>
    <cbc:DocumentCurrencyCode>GBP</cbc:DocumentCurrencyCode>
    <cac:BuyerCustomerParty>
        <cac:Party>
            <cbc:EndpointID schemeID="0088">9876543210987</cbc:EndpointID>
        </cac:Party>
    </cac:BuyerCustomerParty>
    <cac:OrderLine>
        <cac:LineItem>
            <cbc:ID>1</cbc:ID>
            <cbc:Quantity unitCode="EA">1.00</cbc:Quantity>
            <cbc:LineExtensionAmount currencyID="GBP">100.00</cbc:LineExtensionAmount>
            <cac:Price><cbc:PriceAmount currencyID="GBP">100.00</cbc:PriceAmount></cac:Price>
            <cac:Item><cbc:Name>Widget</cbc:Name></cac:Item>
        </cac:LineItem>
    </cac:OrderLine>
    <cac:OrderLine>
        <cac:LineItem>
            <cbc:ID>2</cbc:ID>
            <cbc:Quantity unitCode="EA">3.00</cbc:Quantity>
            <cbc:LineExtensionAmount currencyID="GBP">60.00</cbc:LineExtensionAmount>
            <cac:Price><cbc:PriceAmount currencyID="GBP">20.00</cbc:PriceAmount></cac:Price>
            <cac:Item><cbc:Name>Gadget</cbc:Name></cac:Item>
        </cac:LineItem>
    </cac:OrderLine>
</ubl:Order>
XML;
    }

    // ── Fixture factory ───────────────────────────────────────────────────────

    private function createFixture(bool $groupExists = true): As4OrderImportServiceTestFixture
    {
        $clientPeppolRepository   = $this->createMock(ClientPeppolRepositoryInterface::class);
        $clientRepository         = $this->createMock(ClientRepository::class);
        $groupRepository          = $this->createMock(GroupRepository::class);
        $userRepository           = $this->createMock(UserRepository::class);
        $taxRateRepository        = $this->createMock(TaxRateRepository::class);
        $salesOrderRepository     = $this->createMock(SalesOrderRepository::class);
        $salesOrderItemRepository = $this->createMock(SalesOrderItemRepository::class);
        $settingRepository        = $this->createMock(SettingRepositoryInterface::class);
        $logger                   = $this->createMock(LoggerInterface::class);

        $settingRepository->method('getSetting')->willReturn('1');
        $clientRepository->method('repoClientquery')->willReturn(new Client());
        $userRepository->method('findById')->willReturn(new User('as4', 'as4@example.test', 'irrelevant'));
        $taxRateRepository->method('repoFirstByIdQuery')->willReturn(new TaxRate());
        $groupRepository->method('repoGroupquery')->willReturn($groupExists ? new Group() : null);
        $groupRepository->method('generateNumber')->willReturn('SO-000001');

        return new As4OrderImportServiceTestFixture(
            service: new As4OrderImportService(
                parser:                   new UblOrderXmlParser(),
                clientPeppolRepository:   $clientPeppolRepository,
                clientRepository:         $clientRepository,
                groupRepository:          $groupRepository,
                userRepository:           $userRepository,
                taxRateRepository:        $taxRateRepository,
                salesOrderRepository:     $salesOrderRepository,
                salesOrderItemRepository: $salesOrderItemRepository,
                settingRepository:        $settingRepository,
                logger:                   $logger,
            ),
            clientPeppolRepository:   $clientPeppolRepository,
            clientRepository:         $clientRepository,
            groupRepository:          $groupRepository,
            userRepository:           $userRepository,
            taxRateRepository:        $taxRateRepository,
            salesOrderRepository:     $salesOrderRepository,
            salesOrderItemRepository: $salesOrderItemRepository,
            settingRepository:        $settingRepository,
            logger:                   $logger,
        );
    }

    private function stubbedClientPeppol(): ClientPeppol
    {
        $cp = new ClientPeppol(client_id: self::CLIENT_ID);
        $cp->setId(1);
        return $cp;
    }

    private function salesOrderRepositoryThatAssignsId(As4OrderImportServiceTestFixture $f): void
    {
        $f->salesOrderRepository
            ->method('save')
            ->willReturnCallback(static function (array|SalesOrder|null $so): void {
                if ($so instanceof SalesOrder) {
                    $so->setId(self::SO_ID);
                }
            });
    }

    // ── Unknown sender ────────────────────────────────────────────────────────

    public function testUnknownSenderSkipsSalesOrderSave(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn(null);
        $f->salesOrderRepository->expects($this->never())->method('save');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testUnknownSenderLogsWarning(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn(null);
        $f->logger->expects($this->once())->method('warning');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    // ── Missing Group ─────────────────────────────────────────────────────────

    public function testMissingGroupSkipsSalesOrderSave(): void
    {
        $f = $this->createFixture(groupExists: false);
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $f->salesOrderRepository->expects($this->never())->method('save');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testMissingGroupLogsWarning(): void
    {
        $f = $this->createFixture(groupExists: false);
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $f->logger->expects($this->once())->method('warning');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    // ── Known sender ──────────────────────────────────────────────────────────

    public function testKnownSenderSavesSalesOrder(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->salesOrderRepositoryThatAssignsId($f);
        $f->salesOrderRepository->expects($this->once())->method('save');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testKnownSenderSavesOneSalesOrderItemPerLine(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->salesOrderRepositoryThatAssignsId($f);
        $f->salesOrderItemRepository->expects($this->exactly(2))->method('save');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testKnownSenderLogsInfo(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->salesOrderRepositoryThatAssignsId($f);
        $f->logger->expects($this->once())->method('info');

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testSalesOrderItemCarriesTheLineId(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository->method('findByEndpointId')->willReturn($this->stubbedClientPeppol());
        $this->salesOrderRepositoryThatAssignsId($f);

        $captured = [];
        $f->salesOrderItemRepository
            ->method('save')
            ->willReturnCallback(static function (array|SalesOrderItem|null $item) use (&$captured): void {
                if ($item instanceof SalesOrderItem) {
                    $captured[] = $item->getPeppolPoLineid();
                }
            });

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);

        $this->assertSame(['1', '2'], $captured);
    }

    public function testSenderPartyIdSplitCorrectly(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository
            ->expects($this->once())
            ->method('findByEndpointId')
            ->with('9876543210987', '0088')
            ->willReturn(null);

        $f->service->handle(self::$orderXml, self::SENDER, self::ACTION);
    }

    public function testSenderWithNoColonUsedAsEndpointId(): void
    {
        $f = $this->createFixture();
        $f->clientPeppolRepository
            ->expects($this->once())
            ->method('findByEndpointId')
            ->with('nocolon', '')
            ->willReturn(null);

        $f->service->handle(self::$orderXml, 'nocolon', self::ACTION);
    }
}
