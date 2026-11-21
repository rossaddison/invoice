<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolProductItemClassificationCodeSchemeIdNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolProductUnitCodeNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException;
use App\Invoice\Helpers\Peppol\PeppolHelperInvDeps;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\Ubl\LegalMonetaryTotal;
use App\Invoice\Ubl\Schema;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use Mockery as m;
use ReflectionMethod;
use ReflectionProperty;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The UBL invoice line builders shared by the Peppol invoice XML: the legal monetary total, the invoice
 * lines, the optional line elements, the item and price elements, line allowances and charges, and the
 * purchase order item and line ids. Tested through a small host class; every collaborator is a mock.
 */
#[Test]
final class PeppolHelperInvoiceLineTraitTest
{
    private const string CBC = Schema::CBC;
    private const string CAC = Schema::CAC;

    private function translator(): TranslatorInterface
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return $translator;
    }

    private function settings(): SettingRepository
    {
        $s = TypedMock::of(SettingRepository::class);
        $s->shouldReceive('getSetting')->andReturnUsing(static fn (string $key): string => $key === 'peppol_document_currency' ? 'GBP' : '');
        $s->shouldReceive('currencyConverter')->andReturnUsing(static fn (mixed $v): string => (string) $v);
        return $s;
    }

    private function host(?InvAmount $invAmount = null, bool $creditNote = false): PeppolInvoiceLineTraitHarness
    {
        return new PeppolInvoiceLineTraitHarness(
            $this->settings(),
            $this->translator(),
            $invAmount ?? TypedMock::of(InvAmount::class),
            $creditNote,
        );
    }

    /** @param list<mixed> $args */
    private function call(PeppolInvoiceLineTraitHarness $host, string $method, array $args): mixed
    {
        return (new ReflectionMethod($host, $method))->invoke($host, ...$args);
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

    /**
     * @param list<object> $items
     */
    private function reader(array $items): EntityReader
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($items): \Generator {
            yield from $items;
        });
        return $reader;
    }

    /**
     * @param array<array-key, mixed> $elements
     * @return array<string, mixed>|null the first element with this name
     * @psalm-suppress MixedAssignment
     */
    private function element(array $elements, string $name): ?array
    {
        foreach ($elements as $element) {
            if (is_array($element) && ($element['name'] ?? null) === $name) {
                /** @var array<string, mixed> $element */
                return $element;
            }
        }
        return null;
    }

    private function private(object $object, string $property): mixed
    {
        return (new ReflectionProperty($object, $property))->getValue($object);
    }

    // ── fixtures ─────────────────────────────────────────────────────────────

    private function product(int $unitPeppolId = 4, ?string $iccListId = '1', string $sku = 'SKU-1', string $siiId = 'SII-1', string $origin = 'GB'): Product
    {
        $product = TypedMock::of(Product::class);
        $product->shouldReceive('getUnitPeppolId')->andReturn($unitPeppolId);
        $product->shouldReceive('getProductIccListid')->andReturn($iccListId);
        $product->shouldReceive('getProductIccId')->andReturn('ICC-1');
        $product->shouldReceive('getProductIccListversionid')->andReturn('v1');
        $product->shouldReceive('getProductCountryOfOriginCode')->andReturn($origin);
        $product->shouldReceive('getProductSku')->andReturn($sku);
        $product->shouldReceive('getProductSiiId')->andReturn($siiId);
        $product->shouldReceive('getProductSiiSchemeid')->andReturn('0160');
        $product->shouldReceive('getProductAdditionalItemPropertyName')->andReturn('Colour');
        $product->shouldReceive('getProductAdditionalItemPropertyValue')->andReturn('Blue');
        return $product;
    }

    private function item(
        int $id = 21,
        ?Product $product = null,
        int $soItemId = 0,
        string $description = 'Front and back',
        ?string $itemPoItemId = null,
        ?string $itemPoLineId = null,
    ): InvItem {
        $taxRate = TypedMock::of(TaxRate::class);
        $taxRate->shouldReceive('getPeppolTaxRateCode')->andReturn('S');
        $taxRate->shouldReceive('getTaxRatePercent')->andReturn(20.0);
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('reqId')->andReturn($id);
        $item->shouldReceive('getProduct')->andReturn($product);
        $item->shouldReceive('getTaxRate')->andReturn($taxRate);
        $item->shouldReceive('getSoItemId')->andReturn($soItemId);
        $item->shouldReceive('getDescription')->andReturn($description);
        $item->shouldReceive('getName')->andReturn('Window clean');
        $item->shouldReceive('getQuantity')->andReturn(2.0);
        $item->shouldReceive('getPrice')->andReturn(50.0);
        $item->shouldReceive('getDiscountAmount')->andReturn(5.0);
        $item->shouldReceive('getPeppolPoItemid')->andReturn($itemPoItemId);
        $item->shouldReceive('getPeppolPoLineid')->andReturn($itemPoLineId);
        return $item;
    }

    private function soItem(?string $poItemId, ?string $poLineId): SalesOrderItem
    {
        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn($poItemId);
        $soItem->shouldReceive('getPeppolPoLineid')->andReturn($poLineId);
        return $soItem;
    }

    private function soiRepo(?SalesOrderItem $soItem): SalesOrderItemRepository
    {
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->andReturn($soItem);
        return $soiR;
    }

    private function unitPeppol(): UnitPeppol
    {
        $unit = TypedMock::of(UnitPeppol::class);
        $unit->shouldReceive('getCode')->andReturn('HUR');
        return $unit;
    }

    /** @return array{0: Inv, 1: ClientPeppolRepository} */
    private function invoice(bool $withClient = true, bool $withClientPeppol = true): array
    {
        $inv = new Inv();
        $inv->setId(15);
        if ($withClient) {
            $client = TypedMock::of(Client::class);
            $client->shouldReceive('reqId')->andReturn(7);
            $inv->setClient($client);
        }
        $cp = TypedMock::of(ClientPeppol::class);
        $cp->shouldReceive('getAccountingCost')->andReturn('CC-42');
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($withClientPeppol ? $cp : null);
        return [$inv, $cpR];
    }

    private function amountRepo(int $itemId = 21, bool $found = true): InvItemAmountRepository
    {
        $amount = TypedMock::of(InvItemAmount::class);
        $amount->shouldReceive('getSubtotal')->andReturn(90.0);
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->with($itemId)->andReturn($found ? $amount : null);
        return $iiaR;
    }

    private function unitRepo(?int $unitId = 4, bool $found = true): UnitPeppolRepository
    {
        $unpR = TypedMock::of(UnitPeppolRepository::class);
        $unpR->shouldReceive('repoUnitPeppolLoadedquery')->with($unitId ?? 0)->andReturn($found ? $this->unitPeppol() : null);
        return $unpR;
    }

    private function emptyAllowances(): InvItemAllowanceChargeRepository
    {
        $aciiR = TypedMock::of(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldReceive('repoInvItemquery')->andReturn($this->reader([]));
        return $aciiR;
    }

    private function period(): InvoicePeriod
    {
        return new InvoicePeriod('2026-11-01', '2026-11-30', '');
    }

    /** @return array<array-key, mixed> */
    private function lines(Inv $inv, ClientPeppolRepository $cpR, InvItemAmountRepository $iiaR, SalesOrderItemRepository $soiR, UnitPeppolRepository $unpR, bool $creditNote = false): array
    {
        /** @var array<array-key, mixed> $lines */
        $lines = $this->call(
            $this->host(creditNote: $creditNote),
            'buildInvoiceLinesArray',
            [$inv, $this->period(), $iiaR, $cpR, $soiR, $this->emptyAllowances(), $unpR],
        );
        return $lines;
    }

    // ── buildLegalMonetaryTotal ──────────────────────────────────────────────

    public function theLegalMonetaryTotalReconcilesLineExtensionTaxExclusiveInclusiveAndPayable(): void
    {
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(21);
        $iiR = TypedMock::of(InvItemRepository::class);
        $iiR->shouldReceive('repoInvItemIdquery')->with(15)->andReturn($this->reader([$item]));
        $itemAmount = TypedMock::of(InvItemAmount::class);
        $itemAmount->shouldReceive('getSubtotal')->andReturn(100.0);
        $itemAmount->shouldReceive('getTaxTotal')->andReturn(20.0);
        $itemAmount->shouldReceive('getDiscount')->andReturn(10.0);
        $itemAmount->shouldReceive('getCharge')->andReturn(0.0);
        $itemAmount->shouldReceive('getAllowance')->andReturn(0.0);
        $itemAmount->shouldReceive('getTotal')->andReturn(110.0);
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->with(21)->andReturn($itemAmount);
        $invAmount = TypedMock::of(InvAmount::class);
        $invAmount->shouldReceive('getItemSubtotal')->andReturn(90.0);
        $invAmount->shouldReceive('getItemTaxTotal')->andReturn(18.0);
        $deps = new PeppolHelperInvDeps(
            TypedMock::of(SalesOrderRepository::class),
            TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class),
            $iiaR,
            $iiR,
            TypedMock::of(PostalAddressRepository::class),
            TypedMock::of(ClientPeppolRepository::class),
        );

        $total = $this->call($this->host($invAmount), 'buildLegalMonetaryTotal', [15, $deps]);

        Assert::instanceOf($total, LegalMonetaryTotal::class);
        Assert::same($this->private($total, 'lineExtensionAmount'), 90.0);
        Assert::same($this->private($total, 'taxExclusiveAmount'), 90.0);
        Assert::same($this->private($total, 'taxInclusiveAmount'), 108.0);
        Assert::same($this->private($total, 'allowanceTotalAmount'), 10.0);
        Assert::same($this->private($total, 'payableAmount'), 110.0);
        Assert::same($this->private($total, 'document_currency'), 'GBP');
    }

    // ── buildInvoiceLinesArray ───────────────────────────────────────────────

    public function aMissingClientOrPeppolRecordIsAnError(): void
    {
        [$noClient, $cpR] = $this->invoice(withClient: false);
        [$noPeppol, $cpRNone] = $this->invoice(withClientPeppol: false);

        $a = $this->thrown(fn (): mixed => $this->lines($noClient, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo()));
        $b = $this->thrown(fn (): mixed => $this->lines($noPeppol, $cpRNone, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo()));

        Assert::instanceOf($a, PeppolClientNotFoundException::class);
        Assert::instanceOf($b, PeppolClientNotFoundException::class);
    }

    public function anInvoiceWithNoItemsHasNoLines(): void
    {
        [$inv, $cpR] = $this->invoice();

        Assert::same($this->lines($inv, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo()), []);
    }

    public function anItemBecomesAnInvoiceLineWithItsQuantityAmountPeriodAndItemDetails(): void
    {
        [$inv, $cpR] = $this->invoice();
        $inv->getItems()->add($this->item(21, $this->product()));

        $lines = $this->lines($inv, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo());
        /** @var array<string, mixed> $line */
        $line = $lines[21];
        /** @var list<array<string, mixed>> $value */
        $value = $line['value'];

        Assert::same($line['name'], self::CAC . 'InvoiceLine');
        Assert::same($this->element($value, self::CBC . 'ID')['value'] ?? null, '21');
        Assert::same($this->element($value, self::CBC . 'InvoicedQuantity')['value'] ?? null, '2');
        Assert::same($this->element($value, self::CBC . 'InvoicedQuantity')['attributes'] ?? null, ['unitCode' => 'HUR']);
        Assert::same($this->element($value, self::CBC . 'LineExtensionAmount')['value'] ?? null, '90');
        Assert::same($this->element($value, self::CBC . 'LineExtensionAmount')['attributes'] ?? null, ['currencyID' => 'GBP']);
        Assert::same($this->element($value, self::CBC . 'AccountingCost')['value'] ?? null, 'CC-42');
        Assert::same($this->element($value, self::CBC . 'Note')['value'] ?? null, 'Front and back');
        Assert::true($this->element($value, self::CAC . 'InvoicePeriod') !== null);
        Assert::true($this->element($value, self::CAC . 'DocumentReference') !== null);
        Assert::true($this->element($value, self::CAC . 'Item') !== null);
        Assert::true($this->element($value, self::CAC . 'Price') !== null);
    }

    public function aCreditNoteUsesCreditNoteLineAndCreditedQuantity(): void
    {
        [$inv, $cpR] = $this->invoice();
        $inv->getItems()->add($this->item(21, $this->product()));

        $lines = $this->lines($inv, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo(), creditNote: true);
        /** @var array<string, mixed> $line */
        $line = $lines[21];
        /** @var list<array<string, mixed>> $value */
        $value = $line['value'];

        Assert::same($line['name'], self::CAC . 'CreditNoteLine');
        Assert::true($this->element($value, self::CBC . 'CreditedQuantity') !== null);
        Assert::same($this->element($value, self::CBC . 'InvoicedQuantity'), null);
    }

    public function anItemWithoutAnAmountRowOrAUnitIsLeftOutOfTheLines(): void
    {
        [$inv, $cpR] = $this->invoice();
        $inv->getItems()->add($this->item(21, $this->product()));
        $noProduct = $this->invoice()[0];
        $noProduct->getItems()->add($this->item(21, null));
        $noUnit = $this->invoice()[0];
        $noUnit->getItems()->add($this->item(21, $this->product()));

        Assert::same($this->lines($inv, $cpR, $this->amountRepo(21, false), $this->soiRepo(null), $this->unitRepo()), []);
        Assert::same($this->lines($noProduct, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo()), []);
        Assert::same($this->lines($noUnit, $cpR, $this->amountRepo(), $this->soiRepo(null), $this->unitRepo(4, false)), []);
    }

    // ── validateInvItem ──────────────────────────────────────────────────────

    public function aProductWithoutAPeppolUnitIsAnError(): void
    {
        $item = $this->item(21, $this->product(unitPeppolId: 0));

        $error = $this->thrown(fn (): mixed => $this->call($this->host(), 'validateInvItem', [$item, $this->soiRepo(null)]));

        Assert::instanceOf($error, PeppolProductUnitCodeNotFoundException::class);
    }

    public function aProductWithoutAnItemClassificationListIdIsAnError(): void
    {
        $item = $this->item(21, $this->product(iccListId: null));

        $error = $this->thrown(fn (): mixed => $this->call($this->host(), 'validateInvItem', [$item, $this->soiRepo(null)]));

        Assert::instanceOf($error, PeppolProductItemClassificationCodeSchemeIdNotFoundException::class);
    }

    public function aValidItemReturnsItsPurchaseOrderItemAndLineIds(): void
    {
        $item = $this->item(21, $this->product(), soItemId: 11);

        $ids = $this->call($this->host(), 'validateInvItem', [$item, $this->soiRepo($this->soItem('PO-ITEM', 'PO-LINE'))]);

        Assert::same($ids, ['PO-ITEM', 'PO-LINE']);
    }

    // ── purchase order item and line ids ─────────────────────────────────────

    public function theItemsOwnPurchaseOrderIdsAreUsedWhenThereIsNoSalesOrderItem(): void
    {
        $host = $this->host();
        $item = $this->item(21, null, 0, itemPoItemId: 'OWN-ITEM', itemPoLineId: 'OWN-LINE');
        $blank = $this->item(21, null, 0, itemPoItemId: '', itemPoLineId: null);

        Assert::same($this->call($host, 'peppolPoItemid', [$item, $this->soiRepo(null)]), 'OWN-ITEM');
        Assert::same($this->call($host, 'peppolPoLineid', [$item, $this->soiRepo(null)]), 'OWN-LINE');
        Assert::same($this->call($host, 'peppolPoItemid', [$blank, $this->soiRepo(null)]), null);
        Assert::same($this->call($host, 'peppolPoLineid', [$blank, $this->soiRepo(null)]), null);
    }

    public function theSalesOrderItemsPurchaseOrderIdsAreUsedWhenThereIsOne(): void
    {
        $host = $this->host();
        $item = $this->item(21, null, 11);
        $soiR = $this->soiRepo($this->soItem('SO-ITEM', 'SO-LINE'));

        Assert::same($this->call($host, 'peppolPoItemid', [$item, $soiR]), 'SO-ITEM');
        Assert::same($this->call($host, 'peppolPoLineid', [$item, $soiR]), 'SO-LINE');
    }

    public function aMissingSalesOrderItemIsAnErrorForBothIds(): void
    {
        $host = $this->host();
        $item = $this->item(21, null, 11);

        $a = $this->thrown(fn (): mixed => $this->call($host, 'peppolPoItemid', [$item, $this->soiRepo(null)]));
        $b = $this->thrown(fn (): mixed => $this->call($host, 'peppolPoLineid', [$item, $this->soiRepo(null)]));

        Assert::instanceOf($a, PeppolSalesOrderItemNotExistException::class);
        Assert::instanceOf($b, PeppolSalesOrderItemNotExistException::class);
    }

    public function aSalesOrderItemWithoutAPurchaseOrderItemOrLineNumberIsAnError(): void
    {
        $host = $this->host();
        $item = $this->item(21, null, 11);

        $a = $this->thrown(fn (): mixed => $this->call($host, 'peppolPoItemid', [$item, $this->soiRepo($this->soItem(null, 'L'))]));
        $b = $this->thrown(fn (): mixed => $this->call($host, 'peppolPoLineid', [$item, $this->soiRepo($this->soItem('I', null))]));

        Assert::instanceOf($a, PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException::class);
        Assert::instanceOf($b, PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException::class);
    }

    // ── optional line elements ───────────────────────────────────────────────

    public function theOptionalLineElementsAppearOnlyWhenTheyHaveAValue(): void
    {
        /** @var array<string, list<mixed>> $full */
        $full = $this->call($this->host(), 'buildOptionalInvoiceLineElements', [$this->item(21, $this->product()), 'LINE-1', 'ITEM-1']);
        /** @var array<string, list<mixed>> $empty */
        $empty = $this->call($this->host(), 'buildOptionalInvoiceLineElements', [$this->item(21, $this->product(origin: ''), description: ''), null, '']);
        Assert::same(count($full['lineNote']), 1);
        Assert::same(count($full['itemDesc']), 1);
        Assert::same(count($full['originCountry']), 1);
        Assert::same(count($full['orderLineRef']), 1);
        Assert::same(count($full['buyersItemId']), 1);
        Assert::same($empty, ['lineNote' => [], 'itemDesc' => [], 'originCountry' => [], 'orderLineRef' => [], 'buyersItemId' => []]);
    }

    // ── item element ─────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function itemElement(Product $product, string $description = 'Front and back'): array
    {
        $item = $this->item(21, $product, description: $description);
        /** @var array<string, mixed> $element */
        $element = $this->call($this->host(), 'buildInvoiceLineItemElement', [$item, [], [], []]);
        return $element;
    }

    public function theItemElementCarriesNameClassificationTaxCategoryAndSellersAndStandardIds(): void
    {
        $element = $this->itemElement($this->product());
        /** @var list<array<string, mixed>> $value */
        $value = $element['value'];

        Assert::same($element['name'], self::CAC . 'Item');
        Assert::same($this->element($value, self::CBC . 'Name')['value'] ?? null, 'Window clean');
        Assert::true($this->element($value, self::CAC . 'SellersItemIdentification') !== null);
        Assert::true($this->element($value, self::CAC . 'StandardItemIdentification') !== null);
        Assert::true($this->element($value, self::CAC . 'CommodityClassification') !== null);
        $tax = $this->element($value, self::CAC . 'ClassifiedTaxCategory');
        /** @var list<array<string, mixed>> $taxValue */
        $taxValue = $tax['value'] ?? [];
        Assert::same($this->element($taxValue, self::CBC . 'ID')['value'] ?? null, 'S');
        Assert::same($this->element($taxValue, self::CBC . 'Percent')['value'] ?? null, 20.0);
    }

    public function aBlankSellersOrStandardItemIdIsNotEmitted(): void
    {
        $element = $this->itemElement($this->product(sku: '', siiId: ''));
        /** @var list<array<string, mixed>> $value */
        $value = $element['value'];

        Assert::same($this->element($value, self::CAC . 'SellersItemIdentification'), null);
        Assert::same($this->element($value, self::CAC . 'StandardItemIdentification'), null);
    }

    // ── price element, document reference, allowances and charges ───────────

    public function thePriceElementCarriesThePriceBaseQuantityAndDiscountAllowance(): void
    {
        $item = $this->item(21, $this->product());
        /** @var array<string, mixed> $price */
        $price = $this->call($this->host(), 'buildInvoiceLinePriceElement', [$item, $this->unitPeppol(), 50.0, 5.0]);
        /** @var list<array<string, mixed>> $value */
        $value = $price['value'];
        $allowance = $this->element($value, self::CAC . 'AllowanceCharge');
        /** @var list<array<string, mixed>> $allowanceValue */
        $allowanceValue = $allowance['value'] ?? [];

        Assert::same($price['name'], self::CAC . 'Price');
        Assert::same($this->element($value, self::CBC . 'PriceAmount')['value'] ?? null, '50');
        Assert::same($this->element($value, self::CBC . 'BaseQuantity')['attributes'] ?? null, ['unitCode' => 'HUR']);
        Assert::same($this->element($allowanceValue, self::CBC . 'ChargeIndicator')['value'] ?? null, 'false');
        Assert::same($this->element($allowanceValue, self::CBC . 'Amount')['value'] ?? null, '5');
        Assert::same($this->element($allowanceValue, self::CBC . 'BaseAmount')['value'] ?? null, '50');
    }

    public function theLineDocumentReferencePointsBackAtTheInvoice(): void
    {
        [$inv] = $this->invoice();

        /** @var array<string, mixed> $reference */
        $reference = $this->call($this->host(), 'lineDocumentReference', [$inv]);
        /** @var list<array<string, mixed>> $value */
        $value = $reference['value'];

        Assert::same($reference['name'], self::CAC . 'DocumentReference');
        Assert::same($this->element($value, self::CBC . 'ID')['value'] ?? null, 'INV-REF');
        Assert::same($this->element($value, self::CBC . 'DocumentTypeCode')['value'] ?? null, '130');
    }

    private function itemAllowance(int $identifier, float $amount, int $baseAmount): InvItemAllowanceCharge
    {
        $allowance = TypedMock::of(AllowanceCharge::class);
        $allowance->shouldReceive('getIdentifier')->andReturn($identifier);
        $allowance->shouldReceive('getReasonCode')->andReturn('95');
        $allowance->shouldReceive('getReason')->andReturn('Discount');
        $allowance->shouldReceive('getMultiplierFactorNumeric')->andReturn(10);
        $allowance->shouldReceive('getBaseAmount')->andReturn($baseAmount);
        $acii = TypedMock::of(InvItemAllowanceCharge::class);
        $acii->shouldReceive('getAllowanceCharge')->andReturn($allowance);
        $acii->shouldReceive('getAmount')->andReturn($amount);
        return $acii;
    }

    public function aLineWithNoAllowancesOrChargesHasNone(): void
    {
        Assert::same($this->call($this->host(), 'itemLineACs', [$this->emptyAllowances(), 21]), []);
    }

    public function eachLineAllowanceOrChargeCarriesItsIndicatorReasonAndAmounts(): void
    {
        $aciiR = TypedMock::of(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldReceive('repoInvItemquery')->with(21)->andReturn(
            $this->reader([$this->itemAllowance(0, 5.0, 50), $this->itemAllowance(1, 3.0, 30)]),
        );

        /** @var list<array<string, mixed>> $result */
        $result = $this->call($this->host(), 'itemLineACs', [$aciiR, 21]);
        /** @var list<array<string, mixed>> $first */
        $first = $result[0]['value'];
        /** @var list<array<string, mixed>> $second */
        $second = $result[1]['value'];

        Assert::same(count($result), 2);
        Assert::same($result[0]['name'], self::CAC . 'AllowanceCharge');
        Assert::same($this->element($first, self::CBC . 'ChargeIndicator')['value'] ?? null, 'false');
        Assert::same($this->element($second, self::CBC . 'ChargeIndicator')['value'] ?? null, 'true');
        Assert::same($this->element($first, self::CBC . 'AllowanceChargeReasonCode')['value'] ?? null, '95');
        Assert::same($this->element($first, self::CBC . 'AllowanceChargeReason')['value'] ?? null, 'Discount');
        Assert::same($this->element($first, self::CBC . 'MultiplierFactorNumeric')['value'] ?? null, 10);
        Assert::same($this->element($first, self::CBC . 'Amount')['value'] ?? null, '5');
        Assert::same($this->element($first, self::CBC . 'BaseAmount')['value'] ?? null, '50');
    }
}
