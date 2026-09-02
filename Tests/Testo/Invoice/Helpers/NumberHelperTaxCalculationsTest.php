<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SOTRR;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * calculateQuoteTaxes()/calculateSalesorderTaxes()/calculateInvTaxes() are
 * three near-identical, previously entirely untested methods -- pure
 * first-party VAT-percentage business logic, deliberately left out of the
 * brick/math NumberHelper swap (see project_number_helper_brick_math_swap
 * memory) as the actual highest-value remaining coverage target: nested
 * conditionals, nothing here can be replaced by a well-tested library.
 *
 * All three share the same shape: an early-out guard (no tax rates, or no
 * amount row yet), then per-tax-rate a strict `=== 1` check on
 * getIncludeItemTax() deciding whether the tax is computed against
 * (item_subtotal + item_tax_total) or item_subtotal alone, always against
 * the *document's* Amount row (QuoteAmount/SalesOrderAmount/InvAmount),
 * never the individual tax rate's own copy of those figures.
 */
#[Test]
final class NumberHelperTaxCalculationsTest
{
    /**
     * repoQuotequery()/repoSalesOrderquery()/repoInvquery() on the three tax
     * rate repositories return Yiisoft\Data\Cycle\Reader\EntityReader, not a
     * plain iterable -- EntityReader implements DataReaderInterface, which
     * extends IteratorAggregate via getIterator(), so a mock stubbing just
     * that one method still iterates correctly with a real foreach.
     *
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
        // None of the three methods under test touch $this->s at all --
        // an unpopulated SettingRepository is enough, matching the
        // existing NumberHelperRecurFrequenciesTest harness.
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        return new NumberHelper($sRepo);
    }

    // -----------------------------------------------------------------
    // calculateQuoteTaxes()
    // -----------------------------------------------------------------

    public function quoteTaxesReturnsZeroWhenNoTaxRatesExist(): void
    {
        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $qtrR->shouldReceive('repoCount')->once()->with(1)->andReturn(0);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldNotReceive('repoQuoteAmountCount');

        Assert::same($this->makeHelper()->calculateQuoteTaxes(1, $qtrR, $qaR), 0.00);
    }

    public function quoteTaxesReturnsZeroWhenNoQuoteAmountRowExistsYet(): void
    {
        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $qtrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(1)->andReturn(0);
        $qaR->shouldNotReceive('repoQuotequery');

        Assert::same($this->makeHelper()->calculateQuoteTaxes(1, $qtrR, $qaR), 0.00);
    }

    public function quoteTaxesReturnsZeroWhenQuoteAmountRowIsMissing(): void
    {
        /** @var QuoteTaxRate&m\MockInterface $rate */
        $rate = m::mock(QuoteTaxRate::class);
        $rate->shouldNotReceive('setQuoteTaxRateAmount');

        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $qtrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $qtrR->shouldNotReceive('save');

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(1)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn(null);

        Assert::same($this->makeHelper()->calculateQuoteTaxes(1, $qtrR, $qaR), 0.00);
    }

    public function quoteTaxesExcludeItemTaxWhenIncludeItemTaxIsNull(): void
    {
        Assert::same($this->quoteTaxScenario(includeItemTax: null, percent: 20.0), 20.00);
    }

    public function quoteTaxesIncludeItemTaxWhenExactlyOne(): void
    {
        Assert::same($this->quoteTaxScenario(includeItemTax: 1, percent: 20.0), 22.00);
    }

    public function quoteTaxesTreatsIncludeItemTaxZeroAsNotIncluded(): void
    {
        // Proves the strict `=== 1` check -- 0 is not truthy-but-different,
        // it must take the exact same path as null.
        Assert::same($this->quoteTaxScenario(includeItemTax: 0, percent: 20.0), 20.00);
    }

    public function quoteTaxesTreatsAMissingTaxRateRelationAsZeroPercent(): void
    {
        Assert::same($this->quoteTaxScenario(includeItemTax: null, percent: null), 0.00);
    }

    public function quoteTaxesSumsMultipleTaxRates(): void
    {
        /** @var TaxRate&m\MockInterface $taxRateA */
        $taxRateA = m::mock(TaxRate::class);
        $taxRateA->shouldReceive('getTaxRatePercent')->andReturn(10.0);

        /** @var QuoteTaxRate&m\MockInterface $rateA */
        $rateA = m::mock(QuoteTaxRate::class);
        $rateA->shouldReceive('getIncludeItemTax')->andReturn(null);
        $rateA->shouldReceive('getTaxRate')->andReturn($taxRateA);
        $rateA->shouldReceive('setQuoteTaxRateAmount')->once()->with(10.00);

        /** @var TaxRate&m\MockInterface $taxRateB */
        $taxRateB = m::mock(TaxRate::class);
        $taxRateB->shouldReceive('getTaxRatePercent')->andReturn(20.0);

        /** @var QuoteTaxRate&m\MockInterface $rateB */
        $rateB = m::mock(QuoteTaxRate::class);
        $rateB->shouldReceive('getIncludeItemTax')->andReturn(null);
        $rateB->shouldReceive('getTaxRate')->andReturn($taxRateB);
        $rateB->shouldReceive('setQuoteTaxRateAmount')->once()->with(20.00);

        /** @var QuoteAmount&m\MockInterface $amount */
        $amount = m::mock(QuoteAmount::class);
        $amount->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getItemTaxTotal')->andReturn(10.0);

        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($this->entityReaderOf([$rateA, $rateB]));
        $qtrR->shouldReceive('repoCount')->once()->with(1)->andReturn(2);
        $qtrR->shouldReceive('save')->twice();

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(1)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($amount);

        Assert::same($this->makeHelper()->calculateQuoteTaxes(1, $qtrR, $qaR), 30.00);
    }

    private function quoteTaxScenario(?int $includeItemTax, ?float $percent): float
    {
        /** @var TaxRate&m\MockInterface|null $taxRate */
        $taxRate = null;
        if ($percent !== null) {
            $taxRate = m::mock(TaxRate::class);
            $taxRate->shouldReceive('getTaxRatePercent')->andReturn($percent);
        }

        $expected = $includeItemTax === 1
            ? (100.0 + 10.0) * ($percent ?? 0.0) / 100.0
            : 100.0 * (($percent ?? 0.0) / 100.0);

        /** @var QuoteTaxRate&m\MockInterface $rate */
        $rate = m::mock(QuoteTaxRate::class);
        $rate->shouldReceive('getIncludeItemTax')->andReturn($includeItemTax);
        $rate->shouldReceive('getTaxRate')->andReturn($taxRate);
        $rate->shouldReceive('setQuoteTaxRateAmount')->once()->with($expected);

        /** @var QuoteAmount&m\MockInterface $amount */
        $amount = m::mock(QuoteAmount::class);
        $amount->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getItemTaxTotal')->andReturn(10.0);

        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $qtrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $qtrR->shouldReceive('save')->once()->with($rate);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(1)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(1)->andReturn($amount);

        return $this->makeHelper()->calculateQuoteTaxes(1, $qtrR, $qaR);
    }

    // -----------------------------------------------------------------
    // calculateSalesorderTaxes()
    // -----------------------------------------------------------------

    public function salesorderTaxesReturnsZeroWhenNoTaxRatesExist(): void
    {
        /** @var SOTRR&m\MockInterface $sotrR */
        $sotrR = m::mock(SOTRR::class);
        $sotrR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $sotrR->shouldReceive('repoCount')->once()->with(1)->andReturn(0);

        /** @var SOAR&m\MockInterface $soaR */
        $soaR = m::mock(SOAR::class);
        $soaR->shouldNotReceive('repoSalesOrderAmountCount');

        Assert::same($this->makeHelper()->calculateSalesorderTaxes(1, $sotrR, $soaR), 0.00);
    }

    public function salesorderTaxesReturnsZeroWhenNoAmountRowExistsYet(): void
    {
        /** @var SOTRR&m\MockInterface $sotrR */
        $sotrR = m::mock(SOTRR::class);
        $sotrR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $sotrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);

        /** @var SOAR&m\MockInterface $soaR */
        $soaR = m::mock(SOAR::class);
        $soaR->shouldReceive('repoSalesOrderAmountCount')->once()->with(1)->andReturn(0);
        $soaR->shouldNotReceive('repoSalesOrderquery');

        Assert::same($this->makeHelper()->calculateSalesorderTaxes(1, $sotrR, $soaR), 0.00);
    }

    public function salesorderTaxesReturnsZeroWhenAmountRowIsMissing(): void
    {
        /** @var SalesOrderTaxRate&m\MockInterface $rate */
        $rate = m::mock(SalesOrderTaxRate::class);
        $rate->shouldNotReceive('setSalesOrderTaxRateAmount');

        /** @var SOTRR&m\MockInterface $sotrR */
        $sotrR = m::mock(SOTRR::class);
        $sotrR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $sotrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $sotrR->shouldNotReceive('save');

        /** @var SOAR&m\MockInterface $soaR */
        $soaR = m::mock(SOAR::class);
        $soaR->shouldReceive('repoSalesOrderAmountCount')->once()->with(1)->andReturn(1);
        $soaR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn(null);

        Assert::same($this->makeHelper()->calculateSalesorderTaxes(1, $sotrR, $soaR), 0.00);
    }

    public function salesorderTaxesExcludeItemTaxWhenIncludeItemTaxIsNull(): void
    {
        Assert::same($this->salesorderTaxScenario(includeItemTax: null, percent: 15.0), 15.00);
    }

    public function salesorderTaxesIncludeItemTaxWhenExactlyOne(): void
    {
        Assert::same($this->salesorderTaxScenario(includeItemTax: 1, percent: 15.0), 16.50);
    }

    public function salesorderTaxesTreatsIncludeItemTaxZeroAsNotIncluded(): void
    {
        Assert::same($this->salesorderTaxScenario(includeItemTax: 0, percent: 15.0), 15.00);
    }

    public function salesorderTaxesTreatsAMissingTaxRateRelationAsZeroPercent(): void
    {
        Assert::same($this->salesorderTaxScenario(includeItemTax: null, percent: null), 0.00);
    }

    private function salesorderTaxScenario(?int $includeItemTax, ?float $percent): float
    {
        /** @var TaxRate&m\MockInterface|null $taxRate */
        $taxRate = null;
        if ($percent !== null) {
            $taxRate = m::mock(TaxRate::class);
            $taxRate->shouldReceive('getTaxRatePercent')->andReturn($percent);
        }

        $expected = $includeItemTax === 1
            ? (100.0 + 10.0) * ($percent ?? 0.0) / 100.0
            : 100.0 * (($percent ?? 0.0) / 100.0);

        /** @var SalesOrderTaxRate&m\MockInterface $rate */
        $rate = m::mock(SalesOrderTaxRate::class);
        $rate->shouldReceive('getIncludeItemTax')->andReturn($includeItemTax);
        $rate->shouldReceive('getTaxRate')->andReturn($taxRate);
        $rate->shouldReceive('setSalesOrderTaxRateAmount')->once()->with($expected);

        /** @var SalesOrderAmount&m\MockInterface $amount */
        $amount = m::mock(SalesOrderAmount::class);
        $amount->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getItemTaxTotal')->andReturn(10.0);

        /** @var SOTRR&m\MockInterface $sotrR */
        $sotrR = m::mock(SOTRR::class);
        $sotrR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $sotrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $sotrR->shouldReceive('save')->once()->with($rate);

        /** @var SOAR&m\MockInterface $soaR */
        $soaR = m::mock(SOAR::class);
        $soaR->shouldReceive('repoSalesOrderAmountCount')->once()->with(1)->andReturn(1);
        $soaR->shouldReceive('repoSalesOrderquery')->once()->with(1)->andReturn($amount);

        return $this->makeHelper()->calculateSalesorderTaxes(1, $sotrR, $soaR);
    }

    // -----------------------------------------------------------------
    // calculateInvTaxes()
    // -----------------------------------------------------------------

    public function invTaxesReturnsZeroWhenNoTaxRatesExist(): void
    {
        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->once()->with(1)->andReturn(0);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldNotReceive('repoInvAmountCount');

        Assert::same($this->makeHelper()->calculateInvTaxes(1, $itrR, $iaR), 0.00);
    }

    public function invTaxesReturnsZeroWhenNoAmountRowExistsYet(): void
    {
        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->once()->with(1)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(1)->andReturn(0);
        $iaR->shouldNotReceive('repoInvquery');

        Assert::same($this->makeHelper()->calculateInvTaxes(1, $itrR, $iaR), 0.00);
    }

    public function invTaxesReturnsZeroWhenAmountRowIsMissing(): void
    {
        /** @var InvTaxRate&m\MockInterface $rate */
        $rate = m::mock(InvTaxRate::class);
        $rate->shouldNotReceive('setInvTaxRateAmount');

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $itrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $itrR->shouldNotReceive('save');

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(1)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(1)->andReturn(null);

        Assert::same($this->makeHelper()->calculateInvTaxes(1, $itrR, $iaR), 0.00);
    }

    public function invTaxesExcludeItemTaxWhenIncludeItemTaxIsNull(): void
    {
        Assert::same($this->invTaxScenario(includeItemTax: null, percent: 5.0), 5.00);
    }

    public function invTaxesIncludeItemTaxWhenExactlyOne(): void
    {
        Assert::same($this->invTaxScenario(includeItemTax: 1, percent: 5.0), 5.50);
    }

    public function invTaxesTreatsIncludeItemTaxZeroAsNotIncluded(): void
    {
        Assert::same($this->invTaxScenario(includeItemTax: 0, percent: 5.0), 5.00);
    }

    public function invTaxesTreatsAMissingTaxRateRelationAsZeroPercent(): void
    {
        Assert::same($this->invTaxScenario(includeItemTax: null, percent: null), 0.00);
    }

    private function invTaxScenario(?int $includeItemTax, ?float $percent): float
    {
        /** @var TaxRate&m\MockInterface|null $taxRate */
        $taxRate = null;
        if ($percent !== null) {
            $taxRate = m::mock(TaxRate::class);
            $taxRate->shouldReceive('getTaxRatePercent')->andReturn($percent);
        }

        $expected = $includeItemTax === 1
            ? (100.0 + 10.0) * ($percent ?? 0.0) / 100.0
            : 100.0 * (($percent ?? 0.0) / 100.0);

        /** @var InvTaxRate&m\MockInterface $rate */
        $rate = m::mock(InvTaxRate::class);
        $rate->shouldReceive('getIncludeItemTax')->andReturn($includeItemTax);
        $rate->shouldReceive('getTaxRate')->andReturn($taxRate);
        $rate->shouldReceive('setInvTaxRateAmount')->once()->with($expected);

        /** @var InvAmount&m\MockInterface $amount */
        $amount = m::mock(InvAmount::class);
        $amount->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getItemTaxTotal')->andReturn(10.0);

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->once()->with(1)->andReturn($this->entityReaderOf([$rate]));
        $itrR->shouldReceive('repoCount')->once()->with(1)->andReturn(1);
        $itrR->shouldReceive('save')->once()->with($rate);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(1)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(1)->andReturn($amount);

        return $this->makeHelper()->calculateInvTaxes(1, $itrR, $iaR);
    }
}
