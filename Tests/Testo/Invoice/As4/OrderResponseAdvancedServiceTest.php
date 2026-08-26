<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\As4;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4DispatchRequest;
use App\Invoice\As4\As4DispatchResult;
use App\Invoice\As4\As4OrderResponseException;
use App\Invoice\As4\As4MessageDispatcher;
use App\Invoice\As4\OrderResponseAdvancedService;
use App\Invoice\ClientPeppol\ClientPeppolRepository as CpR;
use App\Invoice\PostalAddress\PostalAddressRepository as PaR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoiR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Ubl\OrderResponseCode;
use App\Invoice\Ubl\OrderResponseLineStatusCode;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Covers OrderResponseAdvancedService::send() -- the outbound counterpart to
 * As4OrderImportServiceTest, exercised the same way (mocked repositories,
 * no real DB/AS4 network).
 */
#[Test]
final class OrderResponseAdvancedServiceTest
{
    /** @return array{0: SR, 1: array<string, mixed>} */
    private function settingRepositoryFixture(): array
    {
        $config = [
            'SupplierPartyIdentificationId'       => 'GB999999999',
            'SupplierPartyIdentificationSchemeId' => '9930',
            'SupplierPartyIdentificationPostalAddress' => [
                'StreetName'           => '1 Seller St',
                'AdditionalStreetName' => '',
                'AddressLine'          => ['Line' => ''],
                'CityName'             => 'London',
                'PostalZone'           => 'SW1A 1AA',
                'CountrySubentity'     => '',
                'Country'              => ['IdentificationCode' => 'GB', 'ListId' => 'ISO3166-1:Alpha2'],
            ],
            'Contact' => [
                'Name'           => 'Seller Contact',
                'FirstName'      => 'Sam',
                'LastName'       => 'Seller',
                'Telephone'      => '+441111111111',
                'ElectronicMail' => 'seller@example.test',
            ],
            'PartyTaxScheme' => [
                'CompanyID'  => 'GB999999999',
                'TaxScheme'  => ['ID' => 'VAT'],
            ],
            'PartyLegalEntity' => [
                'RegistrationName' => 'Seller Ltd',
                'CompanyID'        => 'GB999999999',
                'Attributes'       => [],
                'CompanyLegalForm' => '',
            ],
            'EndPointID' => [
                'value'    => '1234567890123',
                'schemeID' => '0088',
            ],
        ];

        /** @var SR&m\MockInterface $s */
        $s = m::mock(SR::class);
        $s->shouldReceive('getConfigCompanyDetails')->andReturn(['name' => 'Seller Ltd']);
        $s->shouldReceive('getConfigPeppol')->andReturn($config);
        $s->shouldReceive('getSetting')->with('peppol_document_currency')->andReturn('EUR');

        return [$s, $config];
    }

    private function translator(): Translator
    {
        /** @var Translator&m\MockInterface $t */
        $t = m::mock(Translator::class);
        $t->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);
        return $t;
    }

    /** @param list<SalesOrderItem> $items */
    private function itemReaderYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function salesOrderWithClient(): SalesOrder
    {
        $client = new Client();
        $client->setId(7);
        $client->setClientCountry('United Kingdom');

        $salesOrder = new SalesOrder(client_po_number: 'PO-IMPORT-001');
        $salesOrder->setId(55);
        $salesOrder->setClient($client);
        $salesOrder->setNumber('SO-000055');
        return $salesOrder;
    }

    private function clientPeppolFixture(): ClientPeppol
    {
        $cp = new ClientPeppol(
            client_id: 7,
            endpointid: '9876543210987',
            endpointid_schemeid: '0088',
            identificationid: 'GB123456789',
            identificationid_schemeid: '9925',
            taxschemecompanyid: 'GB123456789',
            taxschemeid: 'VAT',
            legal_entity_registration_name: 'Buyer Ltd',
            legal_entity_companyid: 'GB123456789',
        );
        $cp->setId(1);
        return $cp;
    }

    /**
     * @param list<SalesOrderItem> $items
     */
    private function service(
        SR $s,
        ?CpR $cpR = null,
        ?SoR $soR = null,
        ?SoiR $soiR = null,
        ?As4MessageDispatcher $dispatcher = null,
        array $items = [],
    ): OrderResponseAdvancedService {
        /** @var CpR&m\MockInterface $cpR */
        $cpR = $cpR ?? m::mock(CpR::class);
        /** @var PaR&m\MockInterface $paR */
        $paR = m::mock(PaR::class);
        $paR->shouldReceive('repoClient')->andReturn(null);
        /** @var SoR&m\MockInterface $soR */
        $soR = $soR ?? m::mock(SoR::class);
        if ($soiR === null) {
            /** @var SoiR&m\MockInterface $soiR */
            $soiR = m::mock(SoiR::class);
            $soiR->shouldReceive('repoSalesOrderquery')->andReturn($this->itemReaderYielding($items));
            $soiR->shouldReceive('save')->byDefault();
        }
        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = $dispatcher ?? m::mock(As4MessageDispatcher::class);
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->byDefault();
        $logger->shouldReceive('warning')->byDefault();

        return new OrderResponseAdvancedService(
            s: $s,
            t: $this->translator(),
            clientPeppolRepository: $cpR,
            postalAddressRepository: $paR,
            salesOrderRepository: $soR,
            salesOrderItemRepository: $soiR,
            dispatcher: $dispatcher,
            logger: $logger,
        );
    }

    private function successfulResult(): As4DispatchResult
    {
        return new As4DispatchResult(messageId: 'msg-1', httpStatus: 200, signal: null, success: true);
    }

    private function failedResult(): As4DispatchResult
    {
        return new As4DispatchResult(messageId: 'msg-1', httpStatus: 500, signal: null, success: false);
    }

    public function sendThrowsWhenSalesOrderHasNoClient(): void
    {
        [$s] = $this->settingRepositoryFixture();
        $service = $this->service($s);
        $salesOrder = new SalesOrder();
        $salesOrder->setId(1);

        $threw = false;
        try {
            $service->send($salesOrder, OrderResponseCode::Accepted);
        } catch (As4OrderResponseException) {
            $threw = true;
        }
        Assert::true($threw);
    }

    public function sendThrowsWhenClientHasNoPeppolRegistration(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn(null);
        $service = $this->service($s, cpR: $cpR);

        $threw = false;
        try {
            $service->send($this->salesOrderWithClient(), OrderResponseCode::Accepted);
        } catch (As4OrderResponseException) {
            $threw = true;
        }
        Assert::true($threw);
    }

    public function sendDispatchesWithTheAdvancedOrderingIdentifiersAndPersistsTheCodeOnSuccess(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        /** @var As4DispatchRequest|null $captured */
        $captured = null;
        $e = $dispatcher->shouldReceive('dispatch');
        $e->once()->andReturnUsing(function (As4DispatchRequest $request) use (&$captured) {
            $captured = $request;
            return $this->successfulResult();
        });

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        /** @var string|null $savedCode */
        $savedCode = null;
        $e2 = $soR->shouldReceive('save');
        $e2->once()->andReturnUsing(function (SalesOrder $so) use (&$savedCode) {
            $savedCode = $so->getPeppolOrderResponseCode();
        });

        $item = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item->setId(9);

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item]);
        $service->send($this->salesOrderWithClient(), OrderResponseCode::Accepted);

        Assert::notNull($captured);
        Assert::same($captured->recipientPartyId, '0088:9876543210987');
        Assert::same($captured->documentTypeId, As4Constants::PEPPOL_DOCTYPE_ORDER_RESPONSE_ADVANCED);
        Assert::same($captured->processId, As4Constants::PEPPOL_PROCESS_ADVANCED_ORDERING);
        Assert::same($savedCode, 'AP');
    }

    public function sendDoesNotPersistTheCodeWhenDispatchFails(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andReturn($this->failedResult());

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldNotReceive('save');

        $item = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item->setId(9);

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item]);
        $service->send($this->salesOrderWithClient(), OrderResponseCode::Rejected);
    }

    public function previewXmlReturnsTheDocumentWithoutDispatchingOrPersisting(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldNotReceive('save');

        $item = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item->setId(9);

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item]);
        $xml = $service->previewXml($this->salesOrderWithClient(), OrderResponseCode::Rejected);

        Assert::true(str_contains($xml, '<cbc:OrderResponseCode>RE</cbc:OrderResponseCode>'));
        Assert::true(str_contains($xml, 'PO-IMPORT-001'));
    }

    public function previewXmlThrowsWhenClientHasNoPeppolRegistration(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn(null);
        $service = $this->service($s, cpR: $cpR);

        $threw = false;
        try {
            $service->previewXml($this->salesOrderWithClient(), OrderResponseCode::Accepted);
        } catch (As4OrderResponseException) {
            $threw = true;
        }
        Assert::true($threw);
    }

    // ── sendPerLine() ────────────────────────────────────────────────────────

    /** @return array{0: CpR, 1: As4MessageDispatcher, 2: SalesOrderItem, 3: SalesOrderItem} */
    private function perLineFixtures(): array
    {
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andReturn($this->successfulResult());

        $item1 = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item1->setId(9);
        $item2 = new SalesOrderItem(peppol_po_lineid: '2', name: 'Gadget');
        $item2->setId(10);

        return [$cpR, $dispatcher, $item1, $item2];
    }

    public function sendPerLineDerivesAcceptedHeaderWhenAllLinesAccepted(): void
    {
        [$cpR, $dispatcher, $item1, $item2] = $this->perLineFixtures();
        [$s] = $this->settingRepositoryFixture();

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        /** @var string|null $savedHeaderCode */
        $savedHeaderCode = null;
        $soR->shouldReceive('save')->once()->andReturnUsing(function (SalesOrder $so) use (&$savedHeaderCode) {
            $savedHeaderCode = $so->getPeppolOrderResponseCode();
        });

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item1, $item2]);
        $service->sendPerLine($this->salesOrderWithClient(), [
            9  => OrderResponseLineStatusCode::Accepted,
            10 => OrderResponseLineStatusCode::Accepted,
        ]);

        Assert::same($savedHeaderCode, 'AP');
    }

    public function sendPerLineDerivesRejectedHeaderWhenAllLinesRejected(): void
    {
        [$cpR, $dispatcher, $item1, $item2] = $this->perLineFixtures();
        [$s] = $this->settingRepositoryFixture();

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        /** @var string|null $savedHeaderCode */
        $savedHeaderCode = null;
        $soR->shouldReceive('save')->once()->andReturnUsing(function (SalesOrder $so) use (&$savedHeaderCode) {
            $savedHeaderCode = $so->getPeppolOrderResponseCode();
        });

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item1, $item2]);
        $service->sendPerLine($this->salesOrderWithClient(), [
            9  => OrderResponseLineStatusCode::Rejected,
            10 => OrderResponseLineStatusCode::Rejected,
        ]);

        Assert::same($savedHeaderCode, 'RE');
    }

    public function sendPerLineDerivesAcceptedWithChangesHeaderWhenMixed(): void
    {
        [$cpR, $dispatcher, $item1, $item2] = $this->perLineFixtures();
        [$s] = $this->settingRepositoryFixture();

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        /** @var string|null $savedHeaderCode */
        $savedHeaderCode = null;
        $soR->shouldReceive('save')->once()->andReturnUsing(function (SalesOrder $so) use (&$savedHeaderCode) {
            $savedHeaderCode = $so->getPeppolOrderResponseCode();
        });

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item1, $item2]);
        $service->sendPerLine($this->salesOrderWithClient(), [
            9  => OrderResponseLineStatusCode::Accepted,
            10 => OrderResponseLineStatusCode::Rejected,
        ]);

        Assert::same($savedHeaderCode, 'CA');
    }

    public function sendPerLinePersistsEachItemsOwnCodeOnSuccess(): void
    {
        [$cpR, $dispatcher, $item1, $item2] = $this->perLineFixtures();
        [$s] = $this->settingRepositoryFixture();

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldReceive('save')->once();

        /** @var SoiR&m\MockInterface $soiR */
        $soiR = m::mock(SoiR::class);
        $soiR->shouldReceive('repoSalesOrderquery')->andReturn($this->itemReaderYielding([$item1, $item2]));
        /** @var array<int, string|null> $savedCodes */
        $savedCodes = [];
        $soiR->shouldReceive('save')->twice()->andReturnUsing(function (SalesOrderItem $item) use (&$savedCodes) {
            $savedCodes[$item->reqId()] = $item->getPeppolLineResponseCode();
        });

        $service = $this->service($s, cpR: $cpR, soR: $soR, soiR: $soiR, dispatcher: $dispatcher);
        $service->sendPerLine($this->salesOrderWithClient(), [
            9  => OrderResponseLineStatusCode::Accepted,
            10 => OrderResponseLineStatusCode::Changed,
        ]);

        Assert::same($savedCodes, [9 => '5', 10 => '3']);
    }

    public function sendPerLineDefaultsAnItemMissingFromTheMapToAccepted(): void
    {
        [$cpR, $dispatcher, $item1, $item2] = $this->perLineFixtures();
        [$s] = $this->settingRepositoryFixture();

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        /** @var string|null $savedHeaderCode */
        $savedHeaderCode = null;
        $soR->shouldReceive('save')->once()->andReturnUsing(function (SalesOrder $so) use (&$savedHeaderCode) {
            $savedHeaderCode = $so->getPeppolOrderResponseCode();
        });

        /** @var SoiR&m\MockInterface $soiR */
        $soiR = m::mock(SoiR::class);
        $soiR->shouldReceive('repoSalesOrderquery')->andReturn($this->itemReaderYielding([$item1, $item2]));
        /** @var array<int, string|null> $savedCodes */
        $savedCodes = [];
        $soiR->shouldReceive('save')->twice()->andReturnUsing(function (SalesOrderItem $item) use (&$savedCodes) {
            $savedCodes[$item->reqId()] = $item->getPeppolLineResponseCode();
        });

        $service = $this->service($s, cpR: $cpR, soR: $soR, soiR: $soiR, dispatcher: $dispatcher);
        // Only item 9 is decided; item 10 is left out entirely -- defaults to Accepted.
        $service->sendPerLine($this->salesOrderWithClient(), [
            9 => OrderResponseLineStatusCode::Rejected,
        ]);

        Assert::same($savedCodes, [9 => '7', 10 => '5']);
        // One Rejected + one defaulted-Accepted is mixed, not "all rejected".
        Assert::same($savedHeaderCode, 'CA');
    }

    public function sendPerLineDoesNotPersistAnythingWhenDispatchFails(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andReturn($this->failedResult());

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldNotReceive('save');

        $item = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item->setId(9);
        /** @var SoiR&m\MockInterface $soiR */
        $soiR = m::mock(SoiR::class);
        $soiR->shouldReceive('repoSalesOrderquery')->andReturn($this->itemReaderYielding([$item]));
        $soiR->shouldNotReceive('save');

        $service = $this->service($s, cpR: $cpR, soR: $soR, soiR: $soiR, dispatcher: $dispatcher);
        $service->sendPerLine($this->salesOrderWithClient(), [9 => OrderResponseLineStatusCode::Accepted]);
    }

    public function previewPerLineXmlReturnsTheDocumentWithoutDispatchingOrPersisting(): void
    {
        [$s] = $this->settingRepositoryFixture();
        /** @var CpR&m\MockInterface $cpR */
        $cpR = m::mock(CpR::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($this->clientPeppolFixture());

        $item1 = new SalesOrderItem(peppol_po_lineid: '1', name: 'Widget');
        $item1->setId(9);
        $item2 = new SalesOrderItem(peppol_po_lineid: '2', name: 'Gadget');
        $item2->setId(10);

        /** @var As4MessageDispatcher&m\MockInterface $dispatcher */
        $dispatcher = m::mock(As4MessageDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        /** @var SoR&m\MockInterface $soR */
        $soR = m::mock(SoR::class);
        $soR->shouldNotReceive('save');

        $service = $this->service($s, cpR: $cpR, soR: $soR, dispatcher: $dispatcher, items: [$item1, $item2]);
        $xml = $service->previewPerLineXml($this->salesOrderWithClient(), [
            9  => OrderResponseLineStatusCode::Accepted,
            10 => OrderResponseLineStatusCode::Rejected,
        ]);

        Assert::true(str_contains($xml, '<cbc:OrderResponseCode>CA</cbc:OrderResponseCode>'));
        Assert::true(str_contains($xml, '<cbc:LineStatusCode>5</cbc:LineStatusCode>'));
        Assert::true(str_contains($xml, '<cbc:LineStatusCode>7</cbc:LineStatusCode>'));
    }
}
