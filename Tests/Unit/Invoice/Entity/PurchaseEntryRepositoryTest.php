<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryVatAggregator;
use PHPUnit\Framework\TestCase;

/**
 * Tests PurchaseEntryVatAggregator::aggregate() — the summation logic that feeds
 * VAT100 Box 4 (input_vat) and Box 7 (purchases_ex_vat).
 *
 * PurchaseEntryRepository::repoVatTotalsForPeriod() delegates to this aggregator
 * after the ORM date-range query is built. Testing the aggregator independently
 * avoids the need for ORM/database infrastructure in unit tests.
 */
class PurchaseEntryRepositoryTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private PurchaseEntryVatAggregator $aggregator;

    #[\Override]
    protected function setUp(): void
    {
        $this->aggregator = new PurchaseEntryVatAggregator();
    }


    private function makeEntry(float $amountExVat, float $vatAmount): PurchaseEntry
    {
        $entry = new PurchaseEntry();
        $entry->setAmountExVat($amountExVat);
        $entry->setVatAmount($vatAmount);
        return $entry;
    }

    // --- empty iterable ---

    public function testEmptyIterableReturnsZeros(): void
    {
        $result = $this->aggregator->aggregate([]);

        $this->assertSame(0.0, $result['input_vat']);
        $this->assertSame(0.0, $result['purchases_ex_vat']);
    }

    // --- return shape ---

    public function testReturnArrayHasCorrectKeys(): void
    {
        $result = $this->aggregator->aggregate([]);

        $this->assertArrayHasKey('input_vat', $result);
        $this->assertArrayHasKey('purchases_ex_vat', $result);
        $this->assertIsFloat($result['input_vat']);
        $this->assertIsFloat($result['purchases_ex_vat']);
    }

    // --- single entry ---

    public function testSingleEntryIsPassedThrough(): void
    {
        $result = $this->aggregator->aggregate([
            $this->makeEntry(100.00, 20.00),
        ]);

        $this->assertSame(20.00, $result['input_vat']);
        $this->assertSame(100.00, $result['purchases_ex_vat']);
    }

    // --- multiple entries ---

    public function testMultipleEntriesAreAccumulated(): void
    {
        $entries = [
            $this->makeEntry(200.00, 40.00),
            $this->makeEntry(100.00, 20.00),
            $this->makeEntry(50.00, 10.00),
        ];

        $result = $this->aggregator->aggregate($entries);

        $this->assertSame(70.00, $result['input_vat']);
        $this->assertSame(350.00, $result['purchases_ex_vat']);
    }

    // --- rounding ---

    public function testRoundingToTwoDecimalPlaces(): void
    {
        $entries = [
            $this->makeEntry(100.005, 20.005),
            $this->makeEntry(100.005, 20.005),
        ];

        $result = $this->aggregator->aggregate($entries);

        $this->assertSame(round(40.010, 2), $result['input_vat']);
        $this->assertSame(round(200.010, 2), $result['purchases_ex_vat']);
    }

    public function testThirdDecimalIsDiscarded(): void
    {
        $entries = [
            $this->makeEntry(10.001, 2.001),
            $this->makeEntry(10.001, 2.001),
            $this->makeEntry(10.001, 2.001),
        ];

        $result = $this->aggregator->aggregate($entries);

        // 3 × 10.001 = 30.003 → rounds to 30.00
        $this->assertSame(6.00, $result['input_vat']);
        $this->assertSame(30.00, $result['purchases_ex_vat']);
    }

    // --- zero amounts ---

    public function testEntriesWithZeroAmountsContributeNothing(): void
    {
        $result = $this->aggregator->aggregate([
            $this->makeEntry(0.00, 0.00),
            $this->makeEntry(0.00, 0.00),
        ]);

        $this->assertSame(0.0, $result['input_vat']);
        $this->assertSame(0.0, $result['purchases_ex_vat']);
    }

    // --- zero-rated entry (e.g. zero-rated supplies) ---

    public function testZeroRatedEntryContributesOnlyExVat(): void
    {
        $result = $this->aggregator->aggregate([
            $this->makeEntry(500.00, 0.00),
        ]);

        $this->assertSame(0.0, $result['input_vat']);
        $this->assertSame(500.00, $result['purchases_ex_vat']);
    }

    // --- large amounts ---

    public function testLargeAmountsDoNotOverflow(): void
    {
        $entries = [];
        for ($i = 0; $i < 100; $i++) {
            $entries[] = $this->makeEntry(9999.99, 1999.99);
        }

        $result = $this->aggregator->aggregate($entries);

        $this->assertSame(round(1999.99 * 100, 2), $result['input_vat']);
        $this->assertSame(round(9999.99 * 100, 2), $result['purchases_ex_vat']);
    }

    // --- generator iterable (not just array) ---

    public function testAcceptsGeneratorIterable(): void
    {
        $e1 = $this->makeEntry(80.00, 16.00);
        $e2 = $this->makeEntry(20.00, 4.00);

        $result = $this->aggregator->aggregate(
            (static function () use ($e1, $e2): \Generator {
                yield $e1;
                yield $e2;
            })()
        );

        $this->assertSame(20.00, $result['input_vat']);
        $this->assertSame(100.00, $result['purchases_ex_vat']);
    }

    // --- mixed VAT rates in one period ---

    public function testMixedVatRates(): void
    {
        // Standard 20 %, reduced 5 %, zero 0 %
        $entries = [
            $this->makeEntry(1000.00, 200.00),
            $this->makeEntry(200.00, 10.00),
            $this->makeEntry(50.00, 0.00),
        ];

        $result = $this->aggregator->aggregate($entries);

        $this->assertSame(210.00, $result['input_vat']);
        $this->assertSame(1250.00, $result['purchases_ex_vat']);
    }
}
