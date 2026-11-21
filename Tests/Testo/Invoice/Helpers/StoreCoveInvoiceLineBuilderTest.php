<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductProperty\ProductProperty;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryCodeNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolTaxCategoryPercentNotFoundException;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperChargeDeps;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperInvDeps;
use App\Invoice\Helpers\StoreCove\StoreCoveHelperNetDeps;
use App\Invoice\Helpers\StoreCove\StoreCoveInvoiceLineBuilder;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\ProductProperty\ProductPropertyRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Invoice\Upload\UploadRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Builds the Storecove invoice lines, the document-level allowances and charges and the tax subtotals from an
 * invoice, its items and the repositories beneath them. Everything below the builder is a mock.
 */
#[Test]
final class StoreCoveInvoiceLineBuilderTest
{
    private function builder(): StoreCoveInvoiceLineBuilder
    {
        $s = TypedMock::of(SettingRepository::class);
        $s->shouldReceive('currencyConverter')->andReturnUsing(static fn (mixed $v): string => (string) $v);
        $s->shouldReceive('getSetting')->andReturnUsing(
            static fn (string $key): string => ['currency_code_from' => 'GB', 'currency_code_to' => 'GBP'][$key] ?? '',
        );
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return new StoreCoveInvoiceLineBuilder($s, $translator);
    }

    /**
     * @template T of object
     * @param list<T> $items
     */
    private function reader(array $items): EntityReader
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($items): \Generator {
            yield from $items;
        });
        return $reader;
    }

    private function invoice(bool $withClient = true): Inv
    {
        $inv = new Inv();
        $inv->setId(15);
        if ($withClient) {
            $client = TypedMock::of(Client::class);
            $client->shouldReceive('reqId')->andReturn(7);
            $inv->setClient($client);
        }
        return $inv;
    }

    private function period(): InvoicePeriod
    {
        return new InvoicePeriod('2026-11-01', '2026-11-30', '');
    }

    private function clientPeppolRepo(bool $found = true): ClientPeppolRepository
    {
        $cp = TypedMock::of(ClientPeppol::class);
        $cp->shouldReceive('getAccountingCost')->andReturn('CC-42');
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($found ? $cp : null);
        return $cpR;
    }

    /**
     * @param array<string, object> $overrides
     * @return array{0: StoreCoveHelperInvDeps, 1: StoreCoveHelperNetDeps, 2: StoreCoveHelperChargeDeps}
     */
    private function deps(array $overrides = []): array
    {
        /** @var InvItemAmountRepository $iiaR */
        $iiaR = $overrides['iiaR'] ?? TypedMock::of(InvItemAmountRepository::class);
        /** @var ClientPeppolRepository $cpR */
        $cpR = $overrides['cpR'] ?? $this->clientPeppolRepo();
        /** @var UnitPeppolRepository $unpR */
        $unpR = $overrides['unpR'] ?? TypedMock::of(UnitPeppolRepository::class);
        /** @var ProductPropertyRepository $ppR */
        $ppR = $overrides['ppR'] ?? TypedMock::of(ProductPropertyRepository::class);
        /** @var InvItemAllowanceChargeRepository $aciiR */
        $aciiR = $overrides['aciiR'] ?? TypedMock::of(InvItemAllowanceChargeRepository::class);
        /** @var SalesOrderItemRepository $soiR */
        $soiR = $overrides['soiR'] ?? TypedMock::of(SalesOrderItemRepository::class);
        /** @var TaxRateRepository $trR */
        $trR = $overrides['trR'] ?? TypedMock::of(TaxRateRepository::class);
        $inv = new StoreCoveHelperInvDeps(
            TypedMock::of(SalesOrderRepository::class),
            $iiaR,
            TypedMock::of(PostalAddressRepository::class),
            $cpR,
        );
        $net = new StoreCoveHelperNetDeps(
            TypedMock::of(ContractRepository::class),
            TypedMock::of(DeliveryRepository::class),
            TypedMock::of(DeliveryPartyRepository::class),
            $unpR,
            TypedMock::of(UploadRepository::class),
            $ppR,
        );
        $charge = new StoreCoveHelperChargeDeps(
            TypedMock::of(InvAllowanceChargeRepository::class),
            $aciiR,
            $soiR,
            $trR,
        );
        return [$inv, $net, $charge];
    }
    /** @param array<string, object> $overrides */
    private function lines(Inv $inv, array $overrides = []): array
    {
        [$invDeps, $netDeps, $chargeDeps] = $this->deps($overrides);
        return $this->builder()->buildInvoiceLinesArray($inv, $this->period(), $invDeps, $netDeps, $chargeDeps);
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

    private function product(): Product
    {
        $unit = TypedMock::of(Unit::class);
        $unit->shouldReceive('reqId')->andReturn(3);
        $taxRate = TypedMock::of(TaxRate::class);
        $taxRate->shouldReceive('getTaxRatePercent')->andReturn(20.0);
        $taxRate->shouldReceive('getStoreCoveTaxType')->andReturn('standard');
        $product = TypedMock::of(Product::class);
        $product->shouldReceive('getProductPriceBaseQuantity')->andReturn(1.0);
        $product->shouldReceive('getUnit')->andReturn($unit);
        $product->shouldReceive('getTaxRate')->andReturn($taxRate);
        $product->shouldReceive('getProductCountryOfOriginCode')->andReturn('GB');
        $product->shouldReceive('getProductSku')->andReturn('SKU-1');
        $product->shouldReceive('getProductSiiId')->andReturn('SII-1');
        $product->shouldReceive('getProductSiiSchemeid')->andReturn('0160');
        $product->shouldReceive('getProductAdditionalItemPropertyName')->andReturn('Colour');
        $product->shouldReceive('getProductAdditionalItemPropertyValue')->andReturn('Blue');
        return $product;
    }

    private function item(int $id = 21, ?Product $product = null, string|int|null $soItemId = 0): InvItem
    {
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('reqId')->andReturn($id);
        $item->shouldReceive('getPrice')->andReturn(12.5);
        $item->shouldReceive('getQuantity')->andReturn(2.0);
        $item->shouldReceive('getName')->andReturn('Window clean');
        $item->shouldReceive('getDescription')->andReturn('Front and back');
        $item->shouldReceive('getNote')->andReturn('Weekly');
        $item->shouldReceive('getProduct')->andReturn($product);
        $item->shouldReceive('getSoItemId')->andReturn($soItemId);
        return $item;
    }

    private function amountRepo(int $itemId = 21, bool $found = true): InvItemAmountRepository
    {
        $amount = TypedMock::of(InvItemAmount::class);
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->with($itemId)->andReturn($found ? $amount : null);
        return $iiaR;
    }

    /** @return array<string, object> */
    private function emptyReaders(): array
    {
        $aciiR = TypedMock::of(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldReceive('repoInvItemquery')->andReturn($this->reader([]));
        $ppR = TypedMock::of(ProductPropertyRepository::class);
        $ppR->shouldReceive('findAllProduct')->andReturn($this->reader([]));
        return ['aciiR' => $aciiR, 'ppR' => $ppR];
    }

    // ── buildInvoiceLinesArray ───────────────────────────────────────────────

    public function aMissingClientOrPeppolRecordIsAnError(): void
    {
        $noClient = $this->thrown(fn () => $this->lines($this->invoice(false)));
        $noPeppol = $this->thrown(fn () => $this->lines($this->invoice(), ['cpR' => $this->clientPeppolRepo(false)]));

        Assert::instanceOf($noClient, PeppolClientNotFoundException::class);
        Assert::instanceOf($noPeppol, PeppolClientNotFoundException::class);
    }

    public function anInvoiceWithNoItemsHasNoLines(): void
    {
        Assert::same($this->lines($this->invoice()), []);
    }

    public function anItemWithoutAnAmountRowProducesNoLine(): void
    {
        $inv = $this->invoice();
        $inv->getItems()->add($this->item());

        $lines = $this->lines($inv, $this->emptyReaders() + ['iiaR' => $this->amountRepo(21, false)]);

        Assert::same($lines, []);
    }

    public function aFullyDescribedItemBecomesAStorecoveLine(): void
    {
        $inv = $this->invoice();
        $inv->getItems()->add($this->item(21, $this->product(), 11));

        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn('PO-ITEM-1');
        $soItem->shouldReceive('getPeppolPoLineid')->andReturn('PO-LINE-1');
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->with(11)->andReturn($soItem);

        $unitPeppol = TypedMock::of(UnitPeppol::class);
        $unitPeppol->shouldReceive('getCode')->andReturn('HUR');
        $unpR = TypedMock::of(UnitPeppolRepository::class);
        $unpR->shouldReceive('repoUnitCount')->with(3)->andReturn(1);
        $unpR->shouldReceive('repoUnit')->with(3)->andReturn($unitPeppol);

        $property = TypedMock::of(ProductProperty::class);
        $property->shouldReceive('getName')->andReturn('Size');
        $property->shouldReceive('getValue')->andReturn('Large');
        $ppR = TypedMock::of(ProductPropertyRepository::class);
        $ppR->shouldReceive('findAllProduct')->with(21)->andReturn($this->reader([$property]));

        $allowance = TypedMock::of(AllowanceCharge::class);
        $allowance->shouldReceive('getReason')->andReturn('Loyalty');
        $allowance->shouldReceive('getBaseAmount')->andReturn(3);
        $acii = TypedMock::of(InvItemAllowanceCharge::class);
        $acii->shouldReceive('getAllowanceCharge')->andReturn($allowance);
        $aciiR = TypedMock::of(InvItemAllowanceChargeRepository::class);
        $aciiR->shouldReceive('repoInvItemquery')->with(21)->andReturn($this->reader([$acii]));

        $lines = $this->lines($inv, [
            'iiaR' => $this->amountRepo(), 'soiR' => $soiR, 'unpR' => $unpR, 'ppR' => $ppR, 'aciiR' => $aciiR,
        ]);
        /** @var array<string, mixed> $line */
        $line = $lines[21];

        Assert::same($line['lineId'], 21);
        Assert::same($line['itemPrice'], '12.5');
        Assert::same($line['quantity'], 2.0);
        Assert::same($line['quantityUnitCode'], 'HUR');
        Assert::same($line['baseQuantity'], 1.0);
        Assert::same($line['tax'], ['percentage' => 20.0, 'country' => 'GB', 'category' => 'standard']);
        Assert::same($line['orderLineReferenceLineId'], 'PO-LINE-1');
        Assert::same($line['buyersItemIdentification'], 'PO-ITEM-1');
        Assert::same($line['accountingCost'], 'CC-42');
        Assert::same($line['name'], 'Window clean');
        Assert::same($line['description'], 'Front and back');
        Assert::same($line['note'], 'Weekly');
        Assert::same($line['invoicePeriod'], '2026-11-01 - 2026-11-30');
        Assert::same($line['sellersItemIdentification'], 'SKU-1');
        Assert::same($line['standardItemIdentification'], 'SII-1');
        Assert::same($line['standardItemIdentificationSchemeId'], '0160');
        Assert::same($line['allowanceCharges'], [['reason' => 'Loyalty', 'amountExcludingTax' => 3]]);
        /** @var array<int, array<array-key, mixed>> $properties */
        $properties = $line['additionalItemProperties'];
        Assert::same($properties[0], ['name' => 'Colour', 'value' => 'Blue']);
        Assert::same($properties[1], [1 => ['name' => 'Size', 'value' => 'Large']]);
    }

    public function anItemWithNoProductOrSalesOrderItemUsesFallbacks(): void
    {
        $inv = $this->invoice();
        $inv->getItems()->add($this->item(21, null, 0));

        $lines = $this->lines($inv, $this->emptyReaders() + ['iiaR' => $this->amountRepo()]);
        /** @var array<string, mixed> $line */
        $line = $lines[21];

        Assert::same($line['orderLineReferenceLineId'], 'client.');
        Assert::same($line['buyersItemIdentification'], null);
        Assert::same($line['quantityUnitCode'], '');
        Assert::same($line['sellersItemIdentification'], null);
        Assert::false(isset($line['allowanceCharges']));
    }

    public function aProductUnitWithoutExactlyOnePeppolUnitGivesAnEmptyUnitCode(): void
    {
        $inv = $this->invoice();
        $inv->getItems()->add($this->item(21, $this->product(), 0));
        $unpR = TypedMock::of(UnitPeppolRepository::class);
        $unpR->shouldReceive('repoUnitCount')->with(3)->andReturn(2);

        $lines = $this->lines($inv, $this->emptyReaders() + ['iiaR' => $this->amountRepo(), 'unpR' => $unpR]);
        /** @var array<string, mixed> $line */
        $line = $lines[21];

        Assert::same($line['quantityUnitCode'], '');
    }

    private function salesOrderItemRepo(?SalesOrderItem $soItem): SalesOrderItemRepository
    {
        $soiR = TypedMock::of(SalesOrderItemRepository::class);
        $soiR->shouldReceive('repoSalesOrderItemquery')->andReturn($soItem);
        return $soiR;
    }

    private function linesForSalesOrderItem(?SalesOrderItem $soItem): array
    {
        $inv = $this->invoice();
        $inv->getItems()->add($this->item(21, null, 11));
        return $this->lines($inv, $this->emptyReaders() + [
            'iiaR' => $this->amountRepo(),
            'soiR' => $this->salesOrderItemRepo($soItem),
        ]);
    }

    public function aMissingSalesOrderItemIsAnError(): void
    {
        $error = $this->thrown(fn () => $this->linesForSalesOrderItem(null));

        Assert::instanceOf($error, PeppolSalesOrderItemNotExistException::class);
    }

    public function aSalesOrderItemWithoutAPurchaseOrderItemNumberIsAnError(): void
    {
        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn(null);

        $error = $this->thrown(fn () => $this->linesForSalesOrderItem($soItem));

        Assert::instanceOf($error, PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException::class);
    }

    public function aSalesOrderItemWithoutAPurchaseOrderLineNumberIsAnError(): void
    {
        $soItem = TypedMock::of(SalesOrderItem::class);
        $soItem->shouldReceive('getPeppolPoItemid')->andReturn('PO-ITEM-1');
        $soItem->shouldReceive('getPeppolPoLineid')->andReturn(null);

        $error = $this->thrown(fn () => $this->linesForSalesOrderItem($soItem));

        Assert::instanceOf($error, PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException::class);
    }

    public function getInvItemAmountReturnsTheItemsAmountRow(): void
    {
        $iiaR = $this->amountRepo(21);

        Assert::true($this->builder()->getInvItemAmount(21, $iiaR) instanceof InvItemAmount);
    }

    // ── documentLevelAllowanceCharges ────────────────────────────────────────

    public function anInvoiceWithNoDocumentAllowancesOrChargesHasNone(): void
    {
        $aciR = TypedMock::of(InvAllowanceChargeRepository::class);
        $aciR->shouldReceive('repoACIquery')->andReturn($this->reader([]));
        $aciR->shouldReceive('repoACICount')->andReturn(0);

        Assert::same($this->builder()->documentLevelAllowanceCharges($this->invoice(), $aciR), []);
    }

    public function documentAllowancesAndChargesCarryReasonAmountsAndTax(): void
    {
        $taxRate = TypedMock::of(TaxRate::class);
        $taxRate->shouldReceive('getTaxRatePercent')->andReturn(20.0);
        $taxRate->shouldReceive('getStorecoveTaxType')->andReturn('standard');
        $allowance = TypedMock::of(AllowanceCharge::class);
        $allowance->shouldReceive('getReason')->andReturn('Delivery');
        $allowance->shouldReceive('getBaseAmount')->andReturn(5);
        $allowance->shouldReceive('getTaxRate')->andReturn($taxRate);
        $ac = TypedMock::of(InvAllowanceCharge::class);
        $ac->shouldReceive('getAllowanceCharge')->andReturn($allowance);
        $ac->shouldReceive('getAmount')->andReturn(6.0);
        $aciR = TypedMock::of(InvAllowanceChargeRepository::class);
        $aciR->shouldReceive('repoACIquery')->andReturn($this->reader([$ac]));
        $aciR->shouldReceive('repoACICount')->andReturn(1);

        $result = $this->builder()->documentLevelAllowanceCharges($this->invoice(), $aciR);

        Assert::same($result, [[
            'reason' => 'Delivery',
            'amountExcludingTax' => 6.0,
            'baseAmountExcludingTax' => 5,
            'tax' => ['percentage' => 20.0, 'country' => 'GB', 'category' => 'standard'],
        ]]);
    }

    // ── buildTaxSubtotalArray ────────────────────────────────────────────────

    private function taxRate(int $id, ?string $code, ?float $percent): TaxRate
    {
        $taxRate = TypedMock::of(TaxRate::class);
        $taxRate->shouldReceive('reqId')->andReturn($id);
        $taxRate->shouldReceive('getPeppolTaxRateCode')->andReturn($code);
        $taxRate->shouldReceive('getTaxRatePercent')->andReturn($percent);
        return $taxRate;
    }

    /** @param list<TaxRate> $rates */
    private function taxRepo(array $rates): TaxRateRepository
    {
        $trR = TypedMock::of(TaxRateRepository::class);
        $trR->shouldReceive('findAllPreloaded')->andReturn($this->reader($rates));
        return $trR;
    }

    public function noTaxRatesGiveNoSubtotals(): void
    {
        $result = $this->builder()->buildTaxSubtotalArray(
            $this->invoice(),
            TypedMock::of(InvItemAmountRepository::class),
            $this->taxRepo([]),
        );

        Assert::same($result, []);
    }

    public function aTaxRateWithoutAPeppolCategoryCodeOrAPercentIsAnError(): void
    {
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        $noCode = $this->thrown(fn () => $this->builder()->buildTaxSubtotalArray(
            $this->invoice(),
            $iiaR,
            $this->taxRepo([$this->taxRate(1, null, 20.0)]),
        ));
        $noPercent = $this->thrown(fn () => $this->builder()->buildTaxSubtotalArray(
            $this->invoice(),
            $iiaR,
            $this->taxRepo([$this->taxRate(1, 'S', null)]),
        ));

        Assert::instanceOf($noCode, PeppolTaxCategoryCodeNotFoundException::class);
        Assert::instanceOf($noPercent, PeppolTaxCategoryPercentNotFoundException::class);
    }

    public function eachTaxRateSumsTheTaxableAndTaxAmountsOfItsOwnItems(): void
    {
        $standard = TypedMock::of(TaxRate::class);
        $standard->shouldReceive('reqId')->andReturn(1);
        $zero = TypedMock::of(TaxRate::class);
        $zero->shouldReceive('reqId')->andReturn(2);

        $inv = $this->invoice();
        foreach ([[21, $standard], [22, $standard], [23, $zero]] as [$id, $rate]) {
            $item = TypedMock::of(InvItem::class);
            $item->shouldReceive('reqId')->andReturn($id);
            $item->shouldReceive('getTaxRate')->andReturn($rate);
            $inv->getItems()->add($item);
        }
        $amounts = [21 => [100.0, 20.0], 22 => [50.0, 10.0], 23 => [30.0, 0.0]];
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        foreach ($amounts as $itemId => [$subtotal, $tax]) {
            $amount = TypedMock::of(InvItemAmount::class);
            $amount->shouldReceive('getSubtotal')->andReturn($subtotal);
            $amount->shouldReceive('getTaxTotal')->andReturn($tax);
            $iiaR->shouldReceive('repoInvItemAmountquery')->with($itemId)->andReturn($amount);
        }

        $result = $this->builder()->buildTaxSubtotalArray(
            $inv,
            $iiaR,
            $this->taxRepo([$this->taxRate(1, 'S', 20.0), $this->taxRate(2, 'Z', 0.0)]),
        );

        Assert::same($result[1], [
            'TaxableAmounts' => 150.0, 'TaxAmount' => 30.0, 'TaxCategory' => 'S',
            'TaxCategoryPercent' => 20.0, 'DocumentCurrency' => 'GBP',
        ]);
        Assert::same($result[2], [
            'TaxableAmounts' => 30.0, 'TaxAmount' => 0.0, 'TaxCategory' => 'Z',
            'TaxCategoryPercent' => 0.0, 'DocumentCurrency' => 'GBP',
        ]);
    }

    public function anItemWithoutAnAmountRowAddsNothingToItsTaxRateSubtotal(): void
    {
        $rate = TypedMock::of(TaxRate::class);
        $rate->shouldReceive('reqId')->andReturn(1);
        $item = TypedMock::of(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(21);
        $item->shouldReceive('getTaxRate')->andReturn($rate);
        $inv = $this->invoice();
        $inv->getItems()->add($item);

        /** @var array<int, array<string, float|string>> $result */
        $result = $this->builder()->buildTaxSubtotalArray($inv, $this->amountRepo(21, false), $this->taxRepo([$this->taxRate(1, 'S', 20.0)]));

        Assert::same($result[1]['TaxableAmounts'], 0.0);
        Assert::same($result[1]['TaxAmount'], 0.0);
    }
}
