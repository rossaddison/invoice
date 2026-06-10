<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Service;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\PurchaseEntry\PurchaseEntryService;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PurchaseEntryServiceTest extends TestCase
{
    /** @return array{MockObject&PurchaseEntryRepository, PurchaseEntryService} */
    private function makeService(): array
    {
        $mock = $this->createMock(PurchaseEntryRepository::class);
        return [$mock, new PurchaseEntryService($mock)];
    }

    // --- saveEntry: field population ---

    public function testSaveEntryPopulatesSupplier(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['supplier' => 'ACME Ltd']);

        $this->assertSame('ACME Ltd', $entry->getSupplier());
    }

    public function testSaveEntryPopulatesAmounts(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, [
            'amount_ex_vat' => '250.00',
            'vat_amount'    => '50.00',
        ]);

        $this->assertSame(250.00, $entry->getAmountExVat());
        $this->assertSame(50.00, $entry->getVatAmount());
    }

    public function testSaveEntryPopulatesDescription(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['description' => 'Monthly hosting']);

        $this->assertSame('Monthly hosting', $entry->getDescription());
    }

    public function testSaveEntryEmptyDescriptionBecomesNull(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['description' => '']);

        $this->assertNull($entry->getDescription());
    }

    public function testSaveEntryMissingDescriptionBecomesNull(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['supplier' => 'Supplier']);

        $this->assertNull($entry->getDescription());
    }

    // --- saveEntry: date handling ---

    public function testSaveEntryWithValidDateSetsDateTimeImmutable(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['date' => '2026-04-06']);

        $date = $entry->getDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2026-04-06', $date->format('Y-m-d'));
    }

    public function testSaveEntryWithInvalidDateFallsBackToNow(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['date' => 'not-a-date']);

        $this->assertInstanceOf(DateTimeImmutable::class, $entry->getDate());
    }

    public function testSaveEntryWithNoDateKeyLeavesDateNull(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, ['supplier' => 'No Date Supplier']);

        $this->assertNull($entry->getDate());
    }

    // --- saveEntry: created_at handling ---

    public function testSaveEntryNewEntrySetsCreatedAt(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry  = new PurchaseEntry();
        $before = new DateTimeImmutable();

        $service->saveEntry($entry, ['supplier' => 'New Supplier']);

        $this->assertInstanceOf(DateTimeImmutable::class, $entry->getCreatedAt());
        $this->assertGreaterThanOrEqual(
            $before->getTimestamp(),
            $entry->getCreatedAt()->getTimestamp(),
        );
    }

    public function testSaveEntryExistingEntryDoesNotOverwriteCreatedAt(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();
        $entry->setId(1);
        $original = $entry->getCreatedAt();

        $service->saveEntry($entry, ['supplier' => 'Existing Supplier']);

        $this->assertSame($original, $entry->getCreatedAt());
    }

    // --- saveEntry: missing body keys fall back to defaults ---

    public function testSaveEntryEmptyBodyUsesDefaults(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, []);

        $this->assertSame('', $entry->getSupplier());
        $this->assertSame(0.00, $entry->getAmountExVat());
        $this->assertSame(0.00, $entry->getVatAmount());
        $this->assertNull($entry->getDescription());
    }

    // --- saveEntry: zero amounts ---

    public function testSaveEntryZeroAmountsAreAccepted(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->once())->method('save');
        $entry = new PurchaseEntry();

        $service->saveEntry($entry, [
            'amount_ex_vat' => '0.00',
            'vat_amount'    => '0.00',
        ]);

        $this->assertSame(0.00, $entry->getAmountExVat());
        $this->assertSame(0.00, $entry->getVatAmount());
    }

    // --- saveEntry: repository interaction ---

    public function testSaveEntryAlwaysCallsRepositorySave(): void
    {
        [$repo, $service] = $this->makeService();
        $repo->expects($this->exactly(3))->method('save');

        $service->saveEntry(new PurchaseEntry(), []);
        $service->saveEntry(new PurchaseEntry(), ['supplier' => 'A']);
        $service->saveEntry(new PurchaseEntry(), ['supplier' => 'B', 'date' => '2026-01-01']);
    }

    // --- deleteEntry ---

    public function testDeleteEntryCallsRepositoryDelete(): void
    {
        [$repo, $service] = $this->makeService();
        $entry = new PurchaseEntry();
        $entry->setId(7);

        $repo->expects($this->once())
            ->method('delete')
            ->with($entry);

        $service->deleteEntry($entry);
    }

    // --- vatQuarterLabel: UK tax year (April start, month = 4) ---

    public function testUkQ1StartsOnFirstOfApril(): void
    {
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-04-01'), 4));
    }

    public function testUkQ1EndsOnLastOfJune(): void
    {
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-06-30'), 4));
    }

    public function testUkQ2StartsOnFirstOfJuly(): void
    {
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-07-01'), 4));
    }

    public function testUkQ2EndsOnLastOfSeptember(): void
    {
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-09-30'), 4));
    }

    public function testUkQ3StartsOnFirstOfOctober(): void
    {
        $this->assertSame('Q3 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-10-01'), 4));
    }

    public function testUkQ3EndsOnLastOfDecember(): void
    {
        $this->assertSame('Q3 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-12-31'), 4));
    }

    public function testUkQ4StartsOnFirstOfJanuary(): void
    {
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-01-01'), 4));
    }

    public function testUkQ4EndsOnLastOfMarch(): void
    {
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-03-31'), 4));
    }

    // --- vatQuarterLabel: year boundary edge cases (April start) ---

    public function testDecemberBelongsToPriorTaxYearQ3(): void
    {
        $this->assertSame('Q3 2025/2026', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2025-12-15'), 4));
    }

    public function testJanuaryBelongsToPriorTaxYearQ4(): void
    {
        $this->assertSame('Q4 2025/2026', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-01-15'), 4));
    }

    public function testMarchBelongsToPriorTaxYearQ4(): void
    {
        $this->assertSame('Q4 2025/2026', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-03-15'), 4));
    }

    public function testAprilStartsNewTaxYear(): void
    {
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-04-06'), 4));
    }

    // --- vatQuarterLabel: calendar year (January start, month = 1) ---

    public function testCalendarYearQ1JanuaryToMarch(): void
    {
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-01-01'), 1));
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-03-31'), 1));
    }

    public function testCalendarYearQ2AprilToJune(): void
    {
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-04-01'), 1));
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-06-30'), 1));
    }

    public function testCalendarYearQ4OctoberToDecember(): void
    {
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-10-01'), 1));
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-12-31'), 1));
    }

    // --- vatQuarterLabel: Australian tax year (July start, month = 7) ---

    public function testAustralianQ1JulyToSeptember(): void
    {
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-07-01'), 7));
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-09-30'), 7));
    }

    public function testAustralianQ2OctoberToDecember(): void
    {
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-10-01'), 7));
        $this->assertSame('Q2 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-12-31'), 7));
    }

    public function testAustralianQ3JanuaryToMarch(): void
    {
        $this->assertSame('Q3 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-01-01'), 7));
        $this->assertSame('Q3 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-03-31'), 7));
    }

    public function testAustralianQ4AprilToJune(): void
    {
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-04-01'), 7));
        $this->assertSame('Q4 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2027-06-30'), 7));
    }

    public function testAustralianYearBoundaryJuneThenJuly(): void
    {
        $this->assertSame('Q4 2025/2026', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-06-30'), 7));
        $this->assertSame('Q1 2026/2027', PurchaseEntryService::vatQuarterLabel(
            new DateTimeImmutable('2026-07-01'), 7));
    }

    // --- vatQuarterLabel: label format ---

    public function testLabelFormatMatchesPattern(): void
    {
        $label = PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2026-05-01'), 4);

        $this->assertMatchesRegularExpression('/^Q[1-4] \d{4}\/\d{4}$/', $label);
    }

    public function testConsecutiveTaxYearsAreLinked(): void
    {
        $label   = PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2026-07-15'), 4);
        $matches = [];
        preg_match('/Q\d (\d{4})\/(\d{4})/', $label, $matches);

        $this->assertSame((int) $matches[1] + 1, (int) $matches[2]);
    }

    public function testAllFourQuartersDistinctForUkTaxYear(): void
    {
        $labels = [
            PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2026-04-15'), 4),
            PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2026-07-15'), 4),
            PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2026-10-15'), 4),
            PurchaseEntryService::vatQuarterLabel(new DateTimeImmutable('2027-01-15'), 4),
        ];

        $this->assertCount(4, array_unique($labels));
        $this->assertStringStartsWith('Q1', $labels[0]);
        $this->assertStringStartsWith('Q2', $labels[1]);
        $this->assertStringStartsWith('Q3', $labels[2]);
        $this->assertStringStartsWith('Q4', $labels[3]);
    }
}
