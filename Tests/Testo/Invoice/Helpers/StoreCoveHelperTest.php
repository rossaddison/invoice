<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Contract\Contract;
use App\Infrastructure\Persistence\Delivery\Delivery;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\Upload\Upload;
use App\Invoice\Upload\UploadRepository;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\Helpers\Peppol\Exception\PeppolBuyerReferenceNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientsAccountingCostNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\ContactEmailNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\ContactFirstNameNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\ContactLastNameNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\ContactNameNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\ContactTelephoneNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\LegalEntityCompanyIdNotFoundException;
use App\Invoice\Helpers\StoreCove\Exceptions\TaxSchemeCompanyIdNotFoundException;
use App\Invoice\Helpers\StoreCove\StoreCoveHelper;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperChargeDeps;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperInvDeps;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperNetDeps;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\InvoicePeriod;
use DateTimeImmutable;
use Mockery as m;
use ReflectionMethod;
use ReflectionProperty;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The Storecove JSON builder: tax point description code, supplier contact validation, references,
 * invoice period, payment means and the sender identifiers. Collaborators are mocks, so no database,
 * file system or network is touched.
 */
#[Test]
final class StoreCoveHelperTest
{
    private function translator(): TranslatorInterface
    {
        $words = ['storecove.tax' => 'Tax', 'storecove.legal' => 'Legal'];
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')
            ->andReturnUsing(static fn (string $id): string => $words[$id] ?? $id);
        return $translator;
    }

    /**
     * @param array<string, string> $settings
     * @param array<string, mixed> $peppol
     * @return SettingRepository&m\MockInterface
     */
    private function settings(array $settings = [], array $peppol = []): SettingRepository
    {
        $s = TypedMock::of(SettingRepository::class);
        $s->shouldReceive('getSetting')->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');
        $s->shouldReceive('getConfigPeppol')->andReturn($peppol);
        return $s;
    }

    private function helper(?SettingRepository $s = null, ?DeliveryRepository $delRepo = null): StoreCoveHelper
    {
        return new StoreCoveHelper(
            $s ?? $this->settings(),
            $delRepo ?? TypedMock::of(DeliveryRepository::class),
            new DeliveryLocation(),
            $this->translator(),
        );
    }

    private function client(string $vatId = '', int $id = 7, string $email = 'buyer@example.test'): Client
    {
        $client = TypedMock::of(Client::class);
        $client->shouldReceive('getClientVatId')->andReturn($vatId);
        $client->shouldReceive('reqId')->andReturn($id);
        $client->shouldReceive('getClientEmail')->andReturn($email);
        return $client;
    }

    private function invoice(?Client $client = null): Inv
    {
        $inv = new Inv();
        $inv->setClient($client);
        return $inv;
    }

    /** @param callable(): mixed $call */
    private function thrown(callable $call): ?\Throwable
    {
        try {
            $call();
        } catch (\Throwable $e) {
            return $e;
        }
        return null;
    }

    /** @param list<mixed> $args */
    private function privately(object $object, string $method, array $args): mixed
    {
        return (new ReflectionMethod($object, $method))->invoke($object, ...$args);
    }

    private function sameDate(Inv $inv): void
    {
        (new ReflectionProperty(Inv::class, 'date_tax_point'))->setValue($inv, $inv->getDateCreated());
    }

    // ── getDescriptionCodeForTaxPoint ────────────────────────────────────────

    public function aClientWithoutAVatIdUsesTheDateSuppliedCode(): void
    {
        $code = $this->helper()->getDescriptionCodeForTaxPoint(
            $this->invoice($this->client('')),
            new DateTimeImmutable('2026-11-01'),
            new DateTimeImmutable('2026-11-30'),
        );

        Assert::same($code, '35');
    }

    public function aVatClientInvoicedMoreThanFourteenDaysAfterSupplyUsesTheDateSuppliedCode(): void
    {
        $code = $this->helper()->getDescriptionCodeForTaxPoint(
            $this->invoice($this->client('GB123')),
            new DateTimeImmutable('2026-11-01'),
            new DateTimeImmutable('2026-11-20'),
        );

        Assert::same($code, '35');
    }

    public function aVatClientInvoicedWithinFourteenDaysOfSupplyUsesTheIssueDateCode(): void
    {
        $code = $this->helper()->getDescriptionCodeForTaxPoint(
            $this->invoice($this->client('GB123')),
            new DateTimeImmutable('2026-11-01'),
            new DateTimeImmutable('2026-11-10'),
        );

        Assert::same($code, '3');
    }

    public function aVatClientInvoicedBeforeSupplyUsesTheIssueDateCode(): void
    {
        $code = $this->helper()->getDescriptionCodeForTaxPoint(
            $this->invoice($this->client('GB123')),
            new DateTimeImmutable('2026-11-20'),
            new DateTimeImmutable('2026-11-01'),
        );

        Assert::same($code, '3');
    }

    public function anInvoiceWithNoClientUsesTheDateSuppliedCode(): void
    {
        $code = $this->helper()->getDescriptionCodeForTaxPoint(
            $this->invoice(null),
            new DateTimeImmutable('2026-11-01'),
            new DateTimeImmutable('2026-11-30'),
        );

        Assert::same($code, '35');
    }

    // ── validateSupplierContact ──────────────────────────────────────────────

    public function aCompleteSupplierContactIsAccepted(): void
    {
        $this->helper()->validateSupplierContact(new Contact('Name', 'First', 'Last', '01632 960001', '', 'a@example.test'));

        Assert::true(true);
    }

    public function eachMissingSupplierContactFieldHasItsOwnException(): void
    {
        $helper = $this->helper();
        $cases = [
            [new Contact('Name', 'First', 'Last', '01632 960001', '', null), ContactEmailNotFoundException::class],
            [new Contact(null, 'First', 'Last', '01632 960001', '', 'a@example.test'), ContactNameNotFoundException::class],
            [new Contact('Name', null, 'Last', '01632 960001', '', 'a@example.test'), ContactFirstNameNotFoundException::class],
            [new Contact('Name', 'First', null, '01632 960001', '', 'a@example.test'), ContactLastNameNotFoundException::class],
            [new Contact('Name', 'First', 'Last', null, '', 'a@example.test'), ContactTelephoneNotFoundException::class],
        ];

        foreach ($cases as [$contact, $exception]) {
            Assert::instanceOf($this->thrown(static fn () => $helper->validateSupplierContact($contact)), $exception);
        }
    }

    public function theSupplierContactComesFromThePeppolConfig(): void
    {
        $peppol = ['Contact' => [
            'Name' => 'Supplier Ltd', 'FirstName' => 'Sam', 'LastName' => 'Supplier',
            'Telephone' => '01632 960002', 'Telefax' => '01632 960003', 'ElectronicMail' => 'sam@example.test',
        ]];

        $contact = $this->helper($this->settings([], $peppol))->supplierContact();

        Assert::same($contact->getName(), 'Supplier Ltd');
        Assert::same($contact->getFirstName(), 'Sam');
        Assert::same($contact->getLastName(), 'Supplier');
        Assert::same($contact->getTelephone(), '01632 960002');
        Assert::same($contact->getTelefax(), '01632 960003');
        Assert::same($contact->getElectronicMail(), 'sam@example.test');
    }

    // ── buildReferencesArray ─────────────────────────────────────────────────

    /** @return array<int, array<string, string>> */
    private function references(Inv $inv, ?ContractRepository $contractRepo = null, ?ClientPeppolRepository $cpR = null, ?SalesOrderItemRepository $soiR = null, ?SalesOrderRepository $soR = null): array
    {
        /** @var array<int, array<string, string>> $references */
        $references = $this->helper()->buildReferencesArray(
            $inv,
            $contractRepo ?? TypedMock::of(ContractRepository::class),
            $cpR ?? TypedMock::of(ClientPeppolRepository::class),
            $soiR ?? TypedMock::of(SalesOrderItemRepository::class),
            $soR ?? TypedMock::of(SalesOrderRepository::class),
        );
        return $references;
    }

    private function buyerReferenceRepo(string $reference = 'BR-1'): ClientPeppolRepository
    {
        $clientPeppol = TypedMock::of(ClientPeppol::class);
        $clientPeppol->shouldReceive('getBuyerReference')->andReturn($reference);
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->with(7)->andReturn($clientPeppol);
        return $cpR;
    }

    private function salesOrderRepo(?string $number = 'SO-9'): SalesOrderRepository
    {
        $salesOrder = TypedMock::of(SalesOrder::class);
        $salesOrder->shouldReceive('getNumber')->andReturn($number);
        $soR = TypedMock::of(SalesOrderRepository::class);
        $soR->shouldReceive('repoSalesOrderUnLoadedquery')->with(3)->andReturn($salesOrder);
        return $soR;
    }

    public function anInvoiceWithoutASalesOrderHasNoReferences(): void
    {
        Assert::same($this->references($this->invoice($this->client())), []);
    }

    public function aSalesOrderThatCannotBeFoundGivesNoReferences(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setSoId(3);
        $soR = TypedMock::of(SalesOrderRepository::class);
        $soR->shouldReceive('repoSalesOrderUnLoadedquery')->with(3)->andReturn(null);

        Assert::same($this->references($inv, soR: $soR), []);
    }

    public function aSalesOrderInvoiceGetsPurchaseOrderLineBuyerSalesOrderBillingContractAndOriginatorReferences(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setSoId(3);
        $inv->setContractId(4);
        $inv->setNumber('INV-100');
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('getSoItemId')->andReturn('11');
        $inv->getItems()->add($item);
        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn('PO-ITEM-1');
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->with(11)->andReturn($soItem);
        $contract = TypedMock::of(Contract::class);
        $contract->shouldReceive('getReference')->andReturn('CONTRACT-REF');
        $contractRepo = TypedMock::of(ContractRepository::class);
        $contractRepo->shouldReceive('repoContractquery')->with(4)->andReturn($contract);

        $refs = $this->references($inv, $contractRepo, $this->buyerReferenceRepo(), $soiR, $this->salesOrderRepo());

        Assert::same($refs[0]['documentType'], 'purchase_order');
        Assert::same($refs[0]['documentId'], 'So_item_id/Po_item_id - 11/PO-ITEM-1');
        Assert::same($refs[0]['lineId'], 'Seller Inv Line - 1');
        Assert::same($refs[1], ['documentType' => 'buyer_reference', 'documentId' => 'BR-1']);
        Assert::same($refs[2], ['documentType' => 'sales_order', 'documentId' => 'SO-9']);
        Assert::same($refs[3], ['documentType' => 'billing', 'documentId' => 'refers to a previous invoice']);
        Assert::same($refs[4], ['documentType' => 'contract', 'documentId' => 'CONTRACT-REF']);
        Assert::same($refs[5], ['documentType' => 'originator', 'documentId' => 'storecove.invoiceINV-100']);
    }

    public function missingContractNumbersAndPurchaseOrderItemIdsFallBackToTranslatedText(): void
    {
        $inv = $this->invoice($this->client());
        (new ReflectionProperty(Inv::class, 'number'))->setValue($inv, null);
        $inv->setId(15);
        $inv->setSoId(3);
        $inv->setContractId(4);
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('getSoItemId')->andReturn('11');
        $inv->getItems()->add($item);
        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn(null);
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->andReturn($soItem);
        $contractRepo = TypedMock::of(ContractRepository::class);
        $contractRepo->shouldReceive('repoContractquery')->andReturn(null);

        $refs = $this->references($inv, $contractRepo, $this->buyerReferenceRepo(), $soiR, $this->salesOrderRepo(null));

        Assert::same($refs[0]['documentId'], 'So_item_id/Po_item_id - 11/storecove.purchase.order.item.id.null');
        Assert::same($refs[2]['documentId'], 'storecove.salesorder.number.not.exist');
        Assert::same($refs[4]['documentId'], 'storecove.no.contract.exists');
        Assert::same($refs[5]['documentId'], 'storecove.invoicenumber.missing.therefore.use.invoice.id15');
    }

    public function anItemWithoutASalesOrderItemAddsNoPurchaseOrderLine(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setSoId(3);
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('getSoItemId')->andReturn('11');
        $inv->getItems()->add($item);
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->andReturn(null);

        $refs = $this->references($inv, null, $this->buyerReferenceRepo(), $soiR, $this->salesOrderRepo());

        Assert::same($refs[0]['documentType'], 'buyer_reference');
    }

    public function aSalesOrderInvoiceWithoutAPeppolClientCannotGetABuyerReference(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setSoId(3);
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn(null);

        $error = $this->thrown(fn () => $this->references($inv, null, $cpR, null, $this->salesOrderRepo()));

        Assert::instanceOf($error, PeppolBuyerReferenceNotFoundException::class);
    }

    public function aSalesOrderInvoiceWithoutAClientCannotGetABuyerReference(): void
    {
        $inv = $this->invoice(null);
        $inv->setSoId(3);

        $error = $this->thrown(fn () => $this->references($inv, null, null, null, $this->salesOrderRepo()));

        Assert::instanceOf($error, PeppolClientNotFoundException::class);
    }

    // ── accountingCost ───────────────────────────────────────────────────────

    private function accountingCostFor(?Client $client, ?string $cost, bool $peppolFound = true): string
    {
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        if ($peppolFound) {
            $clientPeppol = TypedMock::of(ClientPeppol::class);
            $clientPeppol->shouldReceive('getAccountingCost')->andReturn($cost);
            $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($clientPeppol);
        } else {
            $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn(null);
        }
        return (string) $this->privately($this->helper(), 'accountingCost', [$this->invoice($client), $cpR]);
    }

    public function theAccountingCostIsTheClientsPeppolAccountingCost(): void
    {
        Assert::same($this->accountingCostFor($this->client(), 'CC-42'), 'CC-42');
    }

    public function anEmptyAccountingCostIsAnError(): void
    {
        $error = $this->thrown(fn () => $this->accountingCostFor($this->client(), ''));

        Assert::instanceOf($error, PeppolClientsAccountingCostNotFoundException::class);
    }

    public function anAccountingCostWithoutAPeppolClientOrAClientIsAnError(): void
    {
        $noPeppol = $this->thrown(fn () => $this->accountingCostFor($this->client(), 'x', false));
        $noClient = $this->thrown(fn () => $this->accountingCostFor(null, 'x'));

        Assert::instanceOf($noPeppol, PeppolClientNotFoundException::class);
        Assert::instanceOf($noClient, PeppolClientNotFoundException::class);
    }

    // ── payment means and payment reference ──────────────────────────────────

    public function thePaymentMeansComeFromThePeppolConfig(): void
    {
        $peppol = ['PaymentMeans' => ['PayeeFinancialAccount' => [
            'ID' => 'GB00BANK', 'Name' => 'Supplier Ltd',
            'FinancialInstitutionBranch' => ['ID' => '12-34-56'],
        ]]];

        $means = $this->privately($this->helper($this->settings([], $peppol)), 'buildPeppolPaymentMeansArray', []);

        Assert::same($means, ['PayeeFinancialAccount' => [
            'ID' => 'GB00BANK', 'Name' => 'Supplier Ltd', 'FinancialInstitutionBranch' => ['ID' => '12-34-56'],
        ]]);
    }

    public function missingPaymentMeansConfigGivesEmptyStrings(): void
    {
        $means = $this->privately($this->helper(), 'buildPeppolPaymentMeansArray', []);

        Assert::same($means, ['PayeeFinancialAccount' => [
            'ID' => '', 'Name' => '', 'FinancialInstitutionBranch' => ['ID' => ''],
        ]]);
    }

    public function aPaymentReferenceIsAPositiveNumber(): void
    {
        $reference = $this->privately($this->helper(), 'buildPeppolPaymentForReference', ['storecove', 9]);

        Assert::true(is_int($reference) && $reference > 0);
    }

    // ── invoice period ───────────────────────────────────────────────────────

    public function theInvoicePeriodDefaultsToTheMonthOfTheTaxPoint(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setDateTaxPoint(new DateTimeImmutable('2026-02-10'));

        Assert::same($this->helper()->invoicePeriod($inv, $this->settings()), '2026-02-01 - 2026-02-28');
    }

    public function theInvoicePeriodUsesTheDateCreatedMonthWhenItIsTheTaxPoint(): void
    {
        $inv = $this->invoice($this->client());
        $this->sameDate($inv);
        $month = $inv->getDateCreated()->format('Y-m-01') . ' - ' . $inv->getDateCreated()->format('Y-m-t');

        Assert::same($this->helper()->invoicePeriod($inv, $this->settings()), $month);
    }

    private function deliveryRepo(?DateTimeImmutable $start, ?DateTimeImmutable $end, bool $found = true): DeliveryRepository
    {
        $delRepo = TypedMock::of(DeliveryRepository::class);
        if ($found) {
            $delivery = TypedMock::of(Delivery::class);
            $delivery->shouldReceive('getStartDate')->andReturn($start);
            $delivery->shouldReceive('getEndDate')->andReturn($end);
            $delRepo->shouldReceive('repoDeliveryquery')->with(6)->andReturn($delivery);
        } else {
            $delRepo->shouldReceive('repoDeliveryquery')->with(6)->andReturn(null);
        }
        return $delRepo;
    }

    public function aDeliveryPeriodOverridesTheMonthBounds(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setDeliveryId(6);
        $delRepo = $this->deliveryRepo(new DateTimeImmutable('2026-03-05'), new DateTimeImmutable('2026-03-09'));

        $period = $this->helper($this->settings(), $delRepo)->invoicePeriod($inv, $this->settings());

        Assert::same($period, '2026-03-05 - 2026-03-09');
    }

    public function aDeliveryWithoutDatesGivesEmptyPeriodBounds(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setDeliveryId(6);

        $period = $this->helper($this->settings(), $this->deliveryRepo(null, null))->invoicePeriod($inv, $this->settings());

        Assert::same($period, ' - ');
    }

    public function aMissingDeliveryFallsBackToTheMonthBounds(): void
    {
        $inv = $this->invoice($this->client());
        $inv->setDeliveryId(6);
        $inv->setDateTaxPoint(new DateTimeImmutable('2026-04-15'));

        $period = $this->helper($this->settings(), $this->deliveryRepo(null, null, false))->invoicePeriod($inv, $this->settings());

        Assert::same($period, '2026-04-01 - 2026-04-30');
    }

    public function theUblInvoicePeriodCarriesADescriptionCodeOnlyWhenTheTaxPointIsTheIssueDate(): void
    {
        $withCode = $this->invoice($this->client(''));
        $this->sameDate($withCode);
        $withoutCode = $this->invoice($this->client(''));
        $withoutCode->setDateTaxPoint(new DateTimeImmutable('2026-05-20'));

        $a = $this->helper()->ublInvoicePeriod($withCode, $this->settings());
        $b = $this->helper()->ublInvoicePeriod($withoutCode, $this->settings());

        Assert::same($this->descriptionCode($a), '35');
        Assert::same($this->descriptionCode($b), '');
        Assert::same($b->getStartDate(), '2026-05-01');
        Assert::same($b->getEndDate(), '2026-05-31');
    }

    private function descriptionCode(InvoicePeriod $period): string
    {
        return (string) (new ReflectionProperty(InvoicePeriod::class, 'descriptionCode'))->getValue($period);
    }

    // ── attachments ──────────────────────────────────────────────────────────

    private function upload(int $id, string $name, string $urlKey = 'KEY', string $description = 'A file'): Upload
    {
        $upload = TypedMock::of(Upload::class);
        $upload->shouldReceive('reqId')->andReturn($id);
        $upload->shouldReceive('getFileNameOriginal')->andReturn($name);
        $upload->shouldReceive('getUrlKey')->andReturn($urlKey);
        $upload->shouldReceive('getDescription')->andReturn($description);
        return $upload;
    }

    /**
     * @param list<Upload> $uploads
     * @return array<int, array<string, mixed>>
     */
    private function attachments(Inv $inv, array $uploads, string $folder): array
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($uploads): \Generator {
            yield from $uploads;
        });
        $upR = TypedMock::of(UploadRepository::class);
        $upR->shouldReceive('repoUploadUrlClientquery')->with('KEY', 7)->andReturn($reader);
        $upR->shouldReceive('getContentTypes')->andReturn(['pdf' => 'application/pdf']);
        $upR->shouldReceive('getContentTypeDefaultOctetStream')->andReturn('application/octet-stream');
        $s = $this->settings();
        $s->shouldReceive('getCustomerFilesFolderAliases')->andReturn(new Aliases(['@customer_files' => $folder]));

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->privately($this->helper($s), 'buildAttachmentsArray', [$inv, $upR]);
        return $result;
    }

    private function attachmentInvoice(): Inv
    {
        $inv = $this->invoice($this->client());
        $inv->setId(15);
        $inv->setClientId(7);
        $inv->setUrlKey('KEY');
        return $inv;
    }

    private function tempFolder(): string
    {
        $folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'storecove-test-' . bin2hex(random_bytes(6));
        mkdir($folder);
        return $folder;
    }

    private function removeFolder(string $folder): void
    {
        $files = glob($folder . DIRECTORY_SEPARATOR . '*');
        foreach ($files === false ? [] : $files as $file) {
            unlink($file);
        }
        rmdir($folder);
    }

    public function anInvoiceWithNoUploadsHasNoAttachments(): void
    {
        $folder = $this->tempFolder();
        try {
            Assert::same($this->attachments($this->attachmentInvoice(), [], $folder), []);
        } finally {
            $this->removeFolder($folder);
        }
    }

    public function anUploadWhoseFileIsMissingFromDiskIsSkipped(): void
    {
        $folder = $this->tempFolder();
        try {
            $result = $this->attachments($this->attachmentInvoice(), [$this->upload(1, 'gone.pdf')], $folder);
            Assert::same($result, []);
        } finally {
            $this->removeFolder($folder);
        }
    }

    public function anUploadThatExistsOnDiskBecomesAnAttachmentWithItsMimeType(): void
    {
        $folder = $this->tempFolder();
        file_put_contents($folder . DIRECTORY_SEPARATOR . 'KEY_scan.pdf', 'pdf bytes');
        try {
            $inv = $this->attachmentInvoice();
            $inv->setNumber('INV-100');
            $result = $this->attachments($inv, [$this->upload(1, 'scan.pdf', description: 'Scan')], $folder);

            Assert::same($result[0]['filename'], 'scan.pdf');
            Assert::same($result[0]['mimeType'], 'application/pdf');
            Assert::same($result[0]['documentId'], 'INV-100');
            Assert::same($result[0]['description'], 'Scan');
            Assert::same($result[0]['primaryImage'], false);
            Assert::true(is_string($result[0]['document']));
        } finally {
            $this->removeFolder($folder);
        }
    }

    public function anUnknownFileTypeFallsBackToOctetStreamAndAnUnnumberedInvoiceBuildsADocumentId(): void
    {
        $folder = $this->tempFolder();
        file_put_contents($folder . DIRECTORY_SEPARATOR . 'KEY_data.xyz', 'bytes');
        try {
            $inv = $this->attachmentInvoice();
            (new ReflectionProperty(Inv::class, 'number'))->setValue($inv, null);
            $result = $this->attachments($inv, [$this->upload(8, 'data.xyz')], $folder);

            Assert::same($result[0]['mimeType'], 'application/octet-stream');
            Assert::same($result[0]['documentId'], 'id15uploadid8');
        } finally {
            $this->removeFolder($folder);
        }
    }

    // ── delivery section ─────────────────────────────────────────────────────

    private function deliveryLocation(): DeliveryLocation
    {
        $location = new DeliveryLocation();
        $location->setAddress1('1 Virtual Street');
        $location->setAddress2('Flat 2');
        $location->setBuildingNumber('1');
        $location->setCity('Virtualton');
        $location->setZip('ZZ1 1AA');
        $location->setState('Testshire');
        $location->setCountry('GB');
        $location->setGlobalLocationNumber('5060000000001');
        $location->setElectronicAddressScheme('0088');
        return $location;
    }

    /**
     * @return array{deliveryPartyName: string, actualDeliveryDate: ?string, deliveryLocation: array{id: ?string, schemeId: ?string, address: array<string, ?string>}}
     */
    private function deliverySection(DeliveryLocation $location, DeliveryRepository $delRepo, ?\App\Invoice\DeliveryParty\DeliveryPartyRepository $partyRepo = null): array
    {
        $net = new StoreCoveHelperNetDeps(
            TypedMock::of(ContractRepository::class),
            $delRepo,
            $partyRepo ?? TypedMock::of(\App\Invoice\DeliveryParty\DeliveryPartyRepository::class),
            TypedMock::of(\App\Invoice\UnitPeppol\UnitPeppolRepository::class),
            TypedMock::of(UploadRepository::class),
            TypedMock::of(\App\Invoice\ProductProperty\ProductPropertyRepository::class),
        );
        $helper = new StoreCoveHelper($this->settings(), $delRepo, $location, $this->translator());
        $inv = $this->invoice($this->client());
        $inv->setId(15);
        $inv->setDateSupplied(new DateTimeImmutable('2026-11-10'));
        /** @var array{deliveryPartyName: string, actualDeliveryDate: ?string, deliveryLocation: array{id: ?string, schemeId: ?string, address: array<string, ?string>}} $section */
        $section = $this->privately($helper, 'buildStoreCoveDeliverySection', [$inv, $net]);
        return $section;
    }

    private function deliveryWithParty(?DateTimeImmutable $actual): Delivery
    {
        $delivery = TypedMock::of(Delivery::class);
        $delivery->shouldReceive('hasDeliveryPartyId')->andReturn(true);
        $delivery->shouldReceive('reqDeliveryPartyId')->andReturn(4);
        $delivery->shouldReceive('getActualDeliveryDate')->andReturn($actual);
        return $delivery;
    }

    public function theDeliverySectionNamesThePartyTheActualDeliveryDateAndTheLocation(): void
    {
        $delRepo = TypedMock::of(DeliveryRepository::class);
        $delRepo->shouldReceive('repoPartyquery')->with(15)->andReturn($this->deliveryWithParty(new DateTimeImmutable('2026-11-18')));
        $delRepo->shouldReceive('repoInvoicequery')->with(15)->andReturn($this->deliveryWithParty(new DateTimeImmutable('2026-11-18')));
        $party = TypedMock::of(\App\Infrastructure\Persistence\DeliveryParty\DeliveryParty::class);
        $party->shouldReceive('getPartyName')->andReturn('Receiving Co');
        $partyRepo = TypedMock::of(\App\Invoice\DeliveryParty\DeliveryPartyRepository::class);
        $partyRepo->shouldReceive('repoDeliveryPartyquery')->with(4)->andReturn($party);

        $section = $this->deliverySection($this->deliveryLocation(), $delRepo, $partyRepo);

        Assert::same($section['deliveryPartyName'], 'Receiving Co');
        Assert::same($section['actualDeliveryDate'], '2026-11-18');
        Assert::same($section['deliveryLocation']['id'], '5060000000001');
        Assert::same($section['deliveryLocation']['schemeId'], '0088');
        Assert::same($section['deliveryLocation']['address']['street1'], '1 Virtual Street');
        Assert::same($section['deliveryLocation']['address']['street2'], 'Flat 2');
        Assert::same($section['deliveryLocation']['address']['city'], 'Virtualton');
        Assert::same($section['deliveryLocation']['address']['zip'], 'ZZ1 1AA');
        Assert::same($section['deliveryLocation']['address']['county'], 'Testshire');
        Assert::same($section['deliveryLocation']['address']['country'], 'GB');
    }

    public function withoutADeliveryTheSectionSaysNotAvailableAndUsesTheDateSupplied(): void
    {
        $delRepo = TypedMock::of(DeliveryRepository::class);
        $delRepo->shouldReceive('repoPartyquery')->andReturn(null);
        $delRepo->shouldReceive('repoInvoicequery')->andReturn(null);

        $section = $this->deliverySection($this->deliveryLocation(), $delRepo);

        Assert::same($section['deliveryPartyName'], 'storecove.not.available');
        Assert::same($section['actualDeliveryDate'], '2026-11-10');
    }

    public function aDeliveryWithoutAnActualDateUsesTheDateSupplied(): void
    {
        $delRepo = TypedMock::of(DeliveryRepository::class);
        $delRepo->shouldReceive('repoPartyquery')->andReturn(null);
        $delRepo->shouldReceive('repoInvoicequery')->andReturn($this->deliveryWithParty(null));

        $section = $this->deliverySection($this->deliveryLocation(), $delRepo);

        Assert::same($section['actualDeliveryDate'], '2026-11-10');
    }

    public function aDeliveryPartyWithNoNameLeavesThePartyNameNotAvailable(): void
    {
        $delRepo = TypedMock::of(DeliveryRepository::class);
        $delRepo->shouldReceive('repoPartyquery')->andReturn($this->deliveryWithParty(null));
        $delRepo->shouldReceive('repoInvoicequery')->andReturn(null);
        $party = TypedMock::of(\App\Infrastructure\Persistence\DeliveryParty\DeliveryParty::class);
        $party->shouldReceive('getPartyName')->andReturn(null);
        $partyRepo = TypedMock::of(\App\Invoice\DeliveryParty\DeliveryPartyRepository::class);
        $partyRepo->shouldReceive('repoDeliveryPartyquery')->andReturn($party);

        $section = $this->deliverySection($this->deliveryLocation(), $delRepo, $partyRepo);

        Assert::same($section['deliveryPartyName'], 'storecove.not.available');
    }

    // ── sender identifiers ───────────────────────────────────────────────────

    private function identifierDeps(?SalesOrderRepository $soR = null): array
    {
        $inv = new StoreCoveHelperInvDeps(
            $soR ?? TypedMock::of(SalesOrderRepository::class),
            TypedMock::of(\App\Invoice\InvItemAmount\InvItemAmountRepository::class),
            TypedMock::of(\App\Invoice\PostalAddress\PostalAddressRepository::class),
            TypedMock::of(ClientPeppolRepository::class),
        );
        $net = new StoreCoveHelperNetDeps(
            TypedMock::of(ContractRepository::class),
            TypedMock::of(DeliveryRepository::class),
            TypedMock::of(\App\Invoice\DeliveryParty\DeliveryPartyRepository::class),
            TypedMock::of(\App\Invoice\UnitPeppol\UnitPeppolRepository::class),
            TypedMock::of(\App\Invoice\Upload\UploadRepository::class),
            TypedMock::of(\App\Invoice\ProductProperty\ProductPropertyRepository::class),
        );
        $charge = new StoreCoveHelperChargeDeps(
            TypedMock::of(\App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository::class),
            TypedMock::of(\App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository::class),
            TypedMock::of(SalesOrderItemRepository::class),
            TypedMock::of(\App\Invoice\TaxRate\TaxRateRepository::class),
        );
        return [$inv, $net, $charge];
    }

    /**
     * @param array<string, string> $settings
     * @param array<string, mixed> $peppol
     * @return array{references: array, legal_entity_id: string, tax_scheme_id: string, routing_scheme_identifier: string}
     */
    private function identifiers(array $settings, array $peppol): array
    {
        [$inv, $net, $charge] = $this->identifierDeps();
        /** @var array{references: array, legal_entity_id: string, tax_scheme_id: string, routing_scheme_identifier: string} $result */
        $result = $this->privately(
            $this->helper($this->settings($settings, $peppol)),
            'resolveStoreCoveIdentifiers',
            [$this->invoice($this->client()), $inv, $net, $charge],
        );
        return $result;
    }

    /** @return array<string, mixed> */
    private function peppolIds(string $legal = 'LEGAL-1', string $tax = 'TAX-1'): array
    {
        return [
            'PartyLegalEntity' => ['CompanyID' => $legal],
            'PartyTaxScheme' => ['CompanyID' => $tax],
        ];
    }

    public function theSenderIdentifierBasisPicksTheTaxOrLegalSchemeForTheChosenCountry(): void
    {
        $tax = $this->identifiers(
            ['storecove_sender_identifier' => '1', 'storecove_sender_identifier_basis' => 'Tax'],
            $this->peppolIds(),
        );
        $legal = $this->identifiers(
            ['storecove_sender_identifier' => '1', 'storecove_sender_identifier_basis' => 'Legal'],
            $this->peppolIds(),
        );
        // array_merge() in storeCoveSenderIdentifierArray() renumbers the keys, so the chosen index is
        // looked up in the merged array, exactly as the helper does.
        /** @var array<string, string> $row */
        $row = StoreCoveArrays::storeCoveSenderIdentifierArray()[1];

        Assert::same($tax['routing_scheme_identifier'], $row['Tax']);
        Assert::same($legal['routing_scheme_identifier'], $row['Legal']);
        Assert::notSame($tax['routing_scheme_identifier'], $legal['routing_scheme_identifier']);
        Assert::same($tax['legal_entity_id'], 'LEGAL-1');
        Assert::same($tax['tax_scheme_id'], 'TAX-1');
        Assert::same($tax['references'], []);
    }

    public function anUnknownSenderIdentifierBasisLeavesTheRoutingSchemeEmpty(): void
    {
        $result = $this->identifiers(
            ['storecove_sender_identifier' => '1', 'storecove_sender_identifier_basis' => 'Something else'],
            $this->peppolIds(),
        );

        Assert::same($result['routing_scheme_identifier'], '');
    }

    public function aMissingLegalEntityCompanyIdIsAnError(): void
    {
        $error = $this->thrown(fn () => $this->identifiers([], $this->peppolIds(legal: '')));

        Assert::instanceOf($error, LegalEntityCompanyIdNotFoundException::class);
    }

    public function aMissingTaxSchemeCompanyIdIsAnError(): void
    {
        $error = $this->thrown(fn () => $this->identifiers([], $this->peppolIds(tax: '')));

        Assert::instanceOf($error, TaxSchemeCompanyIdNotFoundException::class);
    }
}
