<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Service;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteItem\QiAddProductDeps;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService;
use App\Invoice\Task\TaskRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Every test method here mixes real assertions (expects()->method(...))
 * on some mocks with other mocks used as plain stubs (willReturn()/
 * willReturnMap() only, e.g. unrelated repository dependencies a given
 * test doesn't care about) — PHPUnit 13's stub-without-expectations check
 * flags those per mock instance regardless of what other mocks in the
 * same test do. This class-level attribute silences that check; it does
 * not weaken any existing expects() assertion, which still runs and still
 * fails the test if unmet.
 */
#[AllowMockObjectsWithoutExpectations]
final class QuoteItemServiceTest extends TestCase
{
    /**
     * @return array{
     *     repository: MockObject&QuoteItemRepository,
     *     acqiR: MockObject&QuoteItemAllowanceChargeRepository,
     *     qR: MockObject&QuoteRepository,
     *     tRR: MockObject&TaxRateRepository,
     *     pR: MockObject&ProductRepository,
     *     taskR: MockObject&TaskRepository,
     *     service: QuoteItemService,
     * }
     */
    private function makeService(): array
    {
        $repository = $this->createMock(QuoteItemRepository::class);
        $acqiR = $this->createMock(QuoteItemAllowanceChargeRepository::class);
        $qR = $this->createMock(QuoteRepository::class);
        $tRR = $this->createMock(TaxRateRepository::class);
        $pR = $this->createMock(ProductRepository::class);
        $taskR = $this->createMock(TaskRepository::class);

        return [
            'repository' => $repository,
            'acqiR' => $acqiR,
            'qR' => $qR,
            'tRR' => $tRR,
            'pR' => $pR,
            'taskR' => $taskR,
            'service' => new QuoteItemService($repository, $acqiR, $qR, $tRR, $pR, $taskR),
        ];
    }

    private function allowanceChargeReader(array $items): MockObject
    {
        $reader = $this->createMock(EntityReader::class);
        $reader->method('getIterator')->willReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function allowanceCharge(bool $isCharge, float $amount, float $vatOrTax): QuoteItemAllowanceCharge
    {
        $allowanceCharge = new AllowanceCharge(identifier: $isCharge);
        $acqi = new QuoteItemAllowanceCharge();
        $acqi->setAllowanceCharge($allowanceCharge);
        $acqi->setAmount($amount);
        $acqi->setVatOrTax($vatOrTax);
        return $acqi;
    }

    // --- taxratePercentage ---

    public function testTaxratePercentageReturnsPercentWhenTaxRateFound(): void
    {
        ['service' => $service] = $this->makeService();
        $taxRate = $this->createMock(TaxRate::class);
        $taxRate->method('getTaxRatePercent')->willReturn(20.0);
        $trr = $this->createMock(TaxRateRepository::class);
        $trr->expects($this->once())->method('repoTaxRatequery')->with(5)->willReturn($taxRate);

        $result = $service->taxratePercentage(5, $trr);

        $this->assertSame(20.0, $result);
    }

    public function testTaxratePercentageReturnsNullWhenTaxRateNotFound(): void
    {
        ['service' => $service] = $this->makeService();
        $trr = $this->createMock(TaxRateRepository::class);
        $trr->method('repoTaxRatequery')->willReturn(null);

        $result = $service->taxratePercentage(999, $trr);

        $this->assertNull($result);
    }

    // --- deleteQuoteItem ---

    public function testDeleteQuoteItemCallsRepositoryDelete(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $model = new QuoteItem(id: 5);
        $repository->expects($this->once())->method('delete')->with($model);

        $service->deleteQuoteItem($model);
    }

    // --- saveQuoteItemAmount ---

    public function testSaveQuoteItemAmountCreatesNewAmountWhenNoneExists(): void
    {
        ['acqiR' => $acqiR, 'service' => $service] = $this->makeService();
        $acqiR->method('repoQuoteItemquery')->willReturn($this->allowanceChargeReader([]));
        $qiar = $this->createMock(QuoteItemAmountRepository::class);
        $qiar->method('repoCount')->willReturn(0);
        $qias = $this->createMock(QuoteItemAmountService::class);
        $qias->expects($this->once())
            ->method('saveQuoteItemAmountNoForm')
            ->with(
                $this->isInstanceOf(QuoteItemAmount::class),
                $this->callback(function (array $array): bool {
                    return $array['quote_item_id'] === 42
                        && $array['discount'] === 20.0
                        && $array['charge'] === 0.0
                        && $array['allowance'] === 0.0
                        && $array['subtotal'] === 200.0
                        && $array['taxtotal'] === 36.0
                        && $array['total'] === 216.0;
                })
            );

        $service->saveQuoteItemAmount(42, 2.0, 100.0, 10.0, 20.0, $qiar, $qias);
    }

    public function testSaveQuoteItemAmountUpdatesExistingAmountWhenOneExists(): void
    {
        ['acqiR' => $acqiR, 'service' => $service] = $this->makeService();
        $acqiR->method('repoQuoteItemquery')->willReturn($this->allowanceChargeReader([]));
        $existing = new QuoteItemAmount();
        $qiar = $this->createMock(QuoteItemAmountRepository::class);
        $qiar->method('repoCount')->willReturn(1);
        $qiar->expects($this->once())->method('repoQuoteItemAmountquery')->with(42)->willReturn($existing);
        $qias = $this->createMock(QuoteItemAmountService::class);
        $qias->expects($this->once())
            ->method('saveQuoteItemAmountNoForm')
            ->with($this->identicalTo($existing), $this->anything());

        $service->saveQuoteItemAmount(42, 1.0, 10.0, 0.0, null, $qiar, $qias);
    }

    public function testSaveQuoteItemAmountDoesNothingWhenExistingAmountNotFound(): void
    {
        ['acqiR' => $acqiR, 'service' => $service] = $this->makeService();
        $acqiR->method('repoQuoteItemquery')->willReturn($this->allowanceChargeReader([]));
        $qiar = $this->createMock(QuoteItemAmountRepository::class);
        $qiar->method('repoCount')->willReturn(1);
        $qiar->method('repoQuoteItemAmountquery')->willReturn(null);
        $qias = $this->createMock(QuoteItemAmountService::class);
        $qias->expects($this->never())->method('saveQuoteItemAmountNoForm');

        $service->saveQuoteItemAmount(42, 1.0, 10.0, 0.0, null, $qiar, $qias);
    }

    public function testSaveQuoteItemAmountAddsChargeAmountAndVat(): void
    {
        ['acqiR' => $acqiR, 'service' => $service] = $this->makeService();
        $acqiR->method('repoQuoteItemquery')->willReturn(
            $this->allowanceChargeReader([$this->allowanceCharge(true, 15.0, 3.0)])
        );
        $qiar = $this->createMock(QuoteItemAmountRepository::class);
        $qiar->method('repoCount')->willReturn(0);
        $qias = $this->createMock(QuoteItemAmountService::class);
        $qias->expects($this->once())
            ->method('saveQuoteItemAmountNoForm')
            ->with(
                $this->isInstanceOf(QuoteItemAmount::class),
                $this->callback(function (array $array): bool {
                    return $array['charge'] === 15.0
                        && $array['allowance'] === 0.0
                        && $array['subtotal'] === 115.0
                        && $array['taxtotal'] === 3.0
                        && $array['total'] === 118.0;
                })
            );

        // No tax_rate_percentage, so current_tax_total is 0 and only the
        // allowance/charge VAT contributes to the tax total.
        $service->saveQuoteItemAmount(1, 1.0, 100.0, 0.0, null, $qiar, $qias);
    }

    public function testSaveQuoteItemAmountSubtractsAllowanceAmountAndVat(): void
    {
        ['acqiR' => $acqiR, 'service' => $service] = $this->makeService();
        $acqiR->method('repoQuoteItemquery')->willReturn(
            $this->allowanceChargeReader([$this->allowanceCharge(false, 10.0, 2.0)])
        );
        $qiar = $this->createMock(QuoteItemAmountRepository::class);
        $qiar->method('repoCount')->willReturn(0);
        $qias = $this->createMock(QuoteItemAmountService::class);
        $qias->expects($this->once())
            ->method('saveQuoteItemAmountNoForm')
            ->with(
                $this->isInstanceOf(QuoteItemAmount::class),
                $this->callback(function (array $array): bool {
                    return $array['charge'] === 0.0
                        && $array['allowance'] === 10.0
                        && $array['subtotal'] === 90.0
                        && $array['taxtotal'] === -2.0
                        && $array['total'] === 88.0;
                })
            );

        $service->saveQuoteItemAmount(1, 1.0, 100.0, 0.0, null, $qiar, $qias);
    }

    // --- addQuoteItemProduct ---

    private function makeDeps(MockObject&ProductRepository $pr): QiAddProductDeps
    {
        return new QiAddProductDeps(
            pr: $pr,
            qiar: $this->createMock(QuoteItemAmountRepository::class),
            qias: $this->createMock(QuoteItemAmountService::class),
            uR: $this->createMock(UnitRepository::class),
            trr: $this->createMock(TaxRateRepository::class),
            translator: $this->createMock(TranslatorInterface::class),
        );
    }

    public function testAddQuoteItemProductSavesWhenProductIdPresent(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $model = new QuoteItem();
        $product = $this->createMock(Product::class);
        $product->method('getProductName')->willReturn('Widget');
        $product->method('getProductDescription')->willReturn('A fine widget');
        $pr = $this->createMock(ProductRepository::class);
        $pr->expects($this->once())->method('repoProductquery')->with(7)->willReturn($product);
        $pr->expects($this->once())->method('repoCount')->with(7)->willReturn(1);
        $deps = $this->makeDeps($pr);

        $repository->expects($this->once())->method('save')->with($model);

        $service->addQuoteItemProduct($model, [
            'product_id' => '7',
            'product_unit_id' => 0,
        ], '3', $deps);

        $this->assertSame(7, $model->getProductId());
        $this->assertSame(3, $model->reqQuoteId());
        $this->assertSame('Widget', $model->getName());
        $this->assertSame('A fine widget', $model->getDescription());
    }

    public function testAddQuoteItemProductDoesNotSaveWhenProductIdIsZero(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $model = new QuoteItem();
        $pr = $this->createMock(ProductRepository::class);
        $pr->method('repoProductquery')->willReturn(null);
        $deps = $this->makeDeps($pr);

        $repository->expects($this->never())->method('save');

        $service->addQuoteItemProduct($model, [
            'product_unit_id' => 0,
        ], '3', $deps);
    }

    public function testAddQuoteItemProductPersistsQuoteTaxRateAndTaskFromArray(): void
    {
        ['qR' => $qR, 'tRR' => $tRR, 'taskR' => $taskR, 'service' => $service] = $this->makeService();
        $model = new QuoteItem();
        $quote = $this->createMock(Quote::class);
        $taxRate = $this->createMock(TaxRate::class);
        $task = $this->createMock(Task::class);
        $qR->expects($this->once())->method('repoQuoteUnLoadedquery')->with(3)->willReturn($quote);
        $tRR->expects($this->once())->method('repoTaxRatequery')->with(9)->willReturn($taxRate);
        $taskR->expects($this->once())->method('repoTaskquery')->with(4)->willReturn($task);

        $pr = $this->createMock(ProductRepository::class);
        $pr->method('repoProductquery')->willReturn(null);
        $deps = $this->makeDeps($pr);

        $service->addQuoteItemProduct($model, [
            'quote_id' => '3',
            'tax_rate_id' => '9',
            'task_id' => '4',
            'product_unit_id' => 0,
        ], '3', $deps);

        $this->assertSame($quote, $model->getQuote());
        $this->assertSame($taxRate, $model->getTaxRate());
        $this->assertSame($task, $model->getTask());
    }

    // --- saveQuoteItemProduct ---

    public function testSaveQuoteItemProductReturnsTaxRateIdAndSavesWhenProductIdPresent(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $model = new QuoteItem();
        $product = $this->createMock(Product::class);
        $product->method('getProductName')->willReturn('Widget');
        $product->method('getProductDescription')->willReturn('desc');
        $pr = $this->createMock(ProductRepository::class);
        $pr->expects($this->once())->method('repoProductquery')->with(7)->willReturn($product);
        $pr->expects($this->once())->method('repoCount')->with(7)->willReturn(1);
        $uR = $this->createMock(UnitRepository::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $repository->expects($this->once())->method('save')->with($model);

        $taxRateId = $service->saveQuoteItemProduct($model, [
            'product_id' => '7',
            'tax_rate_id' => '11',
            'product_unit_id' => 0,
        ], '3', $pr, $uR, $translator);

        $this->assertSame(11, $taxRateId);
    }

    public function testSaveQuoteItemProductDoesNotSaveWhenProductIdAbsent(): void
    {
        ['repository' => $repository, 'service' => $service] = $this->makeService();
        $model = new QuoteItem();
        $pr = $this->createMock(ProductRepository::class);
        $uR = $this->createMock(UnitRepository::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $repository->expects($this->never())->method('save');

        $taxRateId = $service->saveQuoteItemProduct($model, [
            'product_unit_id' => 0,
        ], '3', $pr, $uR, $translator);

        $this->assertSame(0, $taxRateId);
    }
}
