<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * invCalculateTotalsofItemTotals() and the two remaining
 * *IncludeCustomerDiscountRequest() methods (quote/inv -- the third,
 * salesorderIncludeCustomerDiscountRequest(), was deleted alongside
 * calculateSo() -- see the note on that method's own former location and
 * project_number_helper_tax_calculation_tests: NumberHelper::calculateSo()
 * had zero callers anywhere in src/ and relied on a raw object-iteration
 * trick that only works because QuoteItem::$id is deliberately public --
 * SalesOrderItem::$id is private, so the method would have silently
 * computed all-zero totals for every sales order had anything ever called
 * it. Real SalesOrder totals are computed by the entirely separate
 * SalesOrderAmountService, not NumberHelper).
 */
#[Test]
final class NumberHelperDiscountAndItemTotalsTest
{
    /**
     * @param array<int, object> $items
     */
    private function entityReaderOf(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(
            static function () use ($items) {
                yield from $items;
            },
        );
        return $reader;
    }

    private function makeHelper(): NumberHelper
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        return new NumberHelper($sRepo);
    }

    // -----------------------------------------------------------------
    // invCalculateTotalsofItemTotals()
    // -----------------------------------------------------------------

    public function itemTotalsAreAllZeroWhenThereAreNoItems(): void
    {
        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(5)->andReturn($this->entityReaderOf([]));

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldNotReceive('repoInvItemAmountquery');

        $totals = $this->makeHelper()->invCalculateTotalsofItemTotals(5, $iiR, $iiaR);

        Assert::same($totals, [
            'subtotal' => 0.00, 'tax_total' => 0.00, 'discount' => 0.00,
            'charge' => 0.00, 'allowance' => 0.00, 'total' => 0.00,
        ]);
    }

    public function itemTotalsSumASingleFullyPopulatedItem(): void
    {
        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(101);

        /** @var InvItemAmount&m\MockInterface $itemAmount */
        $itemAmount = m::mock(InvItemAmount::class);
        $itemAmount->shouldReceive('getSubtotal')->andReturn(100.0);
        $itemAmount->shouldReceive('getTaxTotal')->andReturn(20.0);
        $itemAmount->shouldReceive('getDiscount')->andReturn(5.0);
        $itemAmount->shouldReceive('getCharge')->andReturn(2.0);
        $itemAmount->shouldReceive('getAllowance')->andReturn(1.0);
        $itemAmount->shouldReceive('getTotal')->andReturn(116.0);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(5)->andReturn($this->entityReaderOf([$item]));

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(101)->andReturn($itemAmount);

        $totals = $this->makeHelper()->invCalculateTotalsofItemTotals(5, $iiR, $iiaR);

        Assert::same($totals, [
            'subtotal' => 100.0, 'tax_total' => 20.0, 'discount' => 5.0,
            'charge' => 2.0, 'allowance' => 1.0, 'total' => 116.0,
        ]);
    }

    public function itemTotalsAccumulateAcrossMultipleItems(): void
    {
        /** @var InvItem&m\MockInterface $itemA */
        $itemA = m::mock(InvItem::class);
        $itemA->shouldReceive('reqId')->andReturn(101);
        /** @var InvItemAmount&m\MockInterface $amountA */
        $amountA = m::mock(InvItemAmount::class);
        $amountA->shouldReceive('getSubtotal')->andReturn(100.0);
        $amountA->shouldReceive('getTaxTotal')->andReturn(20.0);
        $amountA->shouldReceive('getDiscount')->andReturn(0.0);
        $amountA->shouldReceive('getCharge')->andReturn(0.0);
        $amountA->shouldReceive('getAllowance')->andReturn(0.0);
        $amountA->shouldReceive('getTotal')->andReturn(120.0);

        /** @var InvItem&m\MockInterface $itemB */
        $itemB = m::mock(InvItem::class);
        $itemB->shouldReceive('reqId')->andReturn(102);
        /** @var InvItemAmount&m\MockInterface $amountB */
        $amountB = m::mock(InvItemAmount::class);
        $amountB->shouldReceive('getSubtotal')->andReturn(50.0);
        $amountB->shouldReceive('getTaxTotal')->andReturn(10.0);
        $amountB->shouldReceive('getDiscount')->andReturn(0.0);
        $amountB->shouldReceive('getCharge')->andReturn(0.0);
        $amountB->shouldReceive('getAllowance')->andReturn(0.0);
        $amountB->shouldReceive('getTotal')->andReturn(60.0);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(5)
            ->andReturn($this->entityReaderOf([$itemA, $itemB]));

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(101)->andReturn($amountA);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(102)->andReturn($amountB);

        $totals = $this->makeHelper()->invCalculateTotalsofItemTotals(5, $iiR, $iiaR);

        Assert::same($totals['subtotal'], 150.0);
        Assert::same($totals['tax_total'], 30.0);
        Assert::same($totals['total'], 180.0);
    }

    public function itemTotalsSkipAnItemWhoseAmountRowIsMissing(): void
    {
        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(101);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(5)->andReturn($this->entityReaderOf([$item]));

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(101)->andReturn(null);

        $totals = $this->makeHelper()->invCalculateTotalsofItemTotals(5, $iiR, $iiaR);

        Assert::same($totals, [
            'subtotal' => 0.00, 'tax_total' => 0.00, 'discount' => 0.00,
            'charge' => 0.00, 'allowance' => 0.00, 'total' => 0.00,
        ]);
    }

    public function itemTotalsTreatNullGettersAsZero(): void
    {
        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(101);

        /** @var InvItemAmount&m\MockInterface $itemAmount */
        $itemAmount = m::mock(InvItemAmount::class);
        $itemAmount->shouldReceive('getSubtotal')->andReturn(100.0);
        $itemAmount->shouldReceive('getTaxTotal')->andReturn(null);
        $itemAmount->shouldReceive('getDiscount')->andReturn(null);
        $itemAmount->shouldReceive('getCharge')->andReturn(null);
        $itemAmount->shouldReceive('getAllowance')->andReturn(null);
        $itemAmount->shouldReceive('getTotal')->andReturn(null);

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(5)->andReturn($this->entityReaderOf([$item]));

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(101)->andReturn($itemAmount);

        $totals = $this->makeHelper()->invCalculateTotalsofItemTotals(5, $iiR, $iiaR);

        Assert::same($totals, [
            'subtotal' => 100.0, 'tax_total' => 0.00, 'discount' => 0.00,
            'charge' => 0.00, 'allowance' => 0.00, 'total' => 0.00,
        ]);
    }

    // -----------------------------------------------------------------
    // quoteIncludeCustomerDiscountRequest()
    // -----------------------------------------------------------------

    public function quoteDiscountIsSubtractedFromTheTotal(): void
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('getDiscountAmount')->andReturn(15.0);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnloadedquery')->once()->with(1)->andReturn($quote);

        Assert::same($this->makeHelper()->quoteIncludeCustomerDiscountRequest(1, 100.0, $qR), 85.0);
    }

    public function quoteDiscountIsZeroWhenTheQuoteIsMissing(): void
    {
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnloadedquery')->once()->with(1)->andReturn(null);

        Assert::same($this->makeHelper()->quoteIncludeCustomerDiscountRequest(1, 100.0, $qR), 100.0);
    }

    // -----------------------------------------------------------------
    // invIncludeCustomerDiscountRequest()
    // -----------------------------------------------------------------

    public function invDiscountIsSubtractedFromTheTotal(): void
    {
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('getDiscountAmount')->andReturn(10.0);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->once()->with(1)->andReturn($inv);

        Assert::same($this->makeHelper()->invIncludeCustomerDiscountRequest(1, 50.0, $iR), 40.0);
    }

    public function invDiscountIsZeroWhenTheInvoiceIsMissing(): void
    {
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnloadedquery')->once()->with(1)->andReturn(null);

        Assert::same($this->makeHelper()->invIncludeCustomerDiscountRequest(1, 50.0, $iR), 50.0);
    }
}
