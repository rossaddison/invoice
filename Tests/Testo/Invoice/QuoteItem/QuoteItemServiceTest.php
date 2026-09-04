<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteItem;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
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
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers only QuoteItemService::addQuoteItemProduct()'s mergeIfExists
 * behaviour — see InvItemServiceTest's matching pair of tests for the same
 * bug fixed on the Invoice side. Adding the same product twice via the
 * "Choose Items" modal or the manual add-product form must merge the new
 * quantity onto the already-existing line rather than creating a second
 * one; mergeIfExists defaults to false so every other caller (quote-to-
 * invoice, etc.) is unaffected.
 */
#[Test]
final class QuoteItemServiceTest
{
    /** @param list<object> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    private function makeService(
        ?QuoteItemRepository $repository = null,
        ?QuoteItemAllowanceChargeRepository $acqiR = null,
        ?QuoteRepository $qR = null,
        ?TaxRateRepository $tRR = null,
        ?ProductRepository $pR = null,
        ?TaskRepository $taskR = null,
    ): QuoteItemService {
        /** @var QuoteItemRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(QuoteItemRepository::class);
        /** @var QuoteItemAllowanceChargeRepository&m\MockInterface $acqiR */
        $acqiR = $acqiR ?? m::mock(QuoteItemAllowanceChargeRepository::class);
        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = $qR ?? m::mock(QuoteRepository::class);
        /** @var TaxRateRepository&m\MockInterface $tRR */
        $tRR = $tRR ?? m::mock(TaxRateRepository::class);
        /** @var ProductRepository&m\MockInterface $pR */
        $pR = $pR ?? m::mock(ProductRepository::class);
        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = $taskR ?? m::mock(TaskRepository::class);
        return new QuoteItemService($repository, $acqiR, $qR, $tRR, $pR, $taskR);
    }

    /**
     * mergeIfExists: true and a line for the same product already exists on
     * this quote — the new quantity is added onto that existing line
     * instead of a second line being created. persist() (the constructor-
     * level quote/tax_rate/product/task relation lookups) must not run at
     * all on this path.
     */
    public function addQuoteItemProductWithMergeIfExistsMergesQuantityIntoExistingLine(): void
    {
        $model = new QuoteItem();
        $existing = new QuoteItem(id: 77, quantity: 1.0, price: 5.0, discount_amount: 0.5);
        $existing->setTaxRateId(2);

        /** @var QuoteItemRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteItemRepository::class);
        $e = $repository->shouldReceive('repoQuoteProductquery');
        $e->once()->with(200, 9)->andReturn($existing);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($existing);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);
        $qR->shouldNotReceive('repoQuoteUnLoadedquery');

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        $pR->shouldNotReceive('repoProductquery');

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var QuoteItemAllowanceChargeRepository&m\MockInterface $acqiR */
        $acqiR = m::mock(QuoteItemAllowanceChargeRepository::class);
        $e3 = $acqiR->shouldReceive('repoQuoteItemquery');
        $e3->once()->with(77)->andReturn($this->readerYielding([]));

        $service = $this->makeService($repository, $acqiR, $qR, null, $pR, $taskR);

        /** @var TaxRate&m\MockInterface $depsTaxRate */
        $depsTaxRate = m::mock(TaxRate::class);
        $e4 = $depsTaxRate->shouldReceive('getTaxRatePercent');
        $e4->once()->andReturn(10.0);

        /** @var TaxRateRepository&m\MockInterface $depsTRR */
        $depsTRR = m::mock(TaxRateRepository::class);
        $e5 = $depsTRR->shouldReceive('repoTaxRatequery');
        $e5->once()->with(2)->andReturn($depsTaxRate);

        /** @var ProductRepository&m\MockInterface $depsPR */
        $depsPR = m::mock(ProductRepository::class);
        $depsPR->shouldNotReceive('repoProductquery');

        /** @var UnitRepository&m\MockInterface $depsUR */
        $depsUR = m::mock(UnitRepository::class);
        $depsUR->shouldNotReceive('repoUnitquery');

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('translate');

        /** @var QuoteItemAmountRepository&m\MockInterface $depsQiar */
        $depsQiar = m::mock(QuoteItemAmountRepository::class);
        $e6 = $depsQiar->shouldReceive('repoCount');
        $e6->once()->with(77)->andReturn(0);

        // Merged quantity = existing 1.0 + newly-requested 2.0 = 3.0.
        /** @var QuoteItemAmountService&m\MockInterface $depsQias */
        $depsQias = m::mock(QuoteItemAmountService::class);
        $e7 = $depsQias->shouldReceive('saveQuoteItemAmountNoForm');
        $e7->once()->with(m::type(QuoteItemAmount::class), m::on(
            static function (array $arr): bool {
                return $arr['quote_item_id'] === 77
                    && $arr['subtotal'] === 15.0
                    && $arr['discount'] === 1.5
                    && $arr['taxtotal'] === (15.0 - 1.5) * (10.0 / 100.00)
                    && $arr['total'] === 15.0 - 1.5 + (15.0 - 1.5) * (10.0 / 100.00);
            }
        ));

        $deps = new QiAddProductDeps($depsPR, $depsQiar, $depsQias, $depsUR, $depsTRR, $translator);

        $service->addQuoteItemProduct(
            $model,
            ['product_id' => '9', 'quantity' => '2'],
            '200',
            $deps,
            mergeIfExists: true,
        );

        Assert::same(3.0, $existing->getQuantity());
    }

    /**
     * mergeIfExists: true but no existing line for this product yet —
     * falls back to the normal create-a-new-line behaviour exactly as if
     * mergeIfExists were false.
     */
    public function addQuoteItemProductWithMergeIfExistsButNoExistingLineCreatesNewLine(): void
    {
        $model = new QuoteItem();

        /** @var QuoteItemRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteItemRepository::class);
        $e = $repository->shouldReceive('repoQuoteProductquery');
        $e->once()->with(200, 9)->andReturn(null);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with(m::type(QuoteItem::class))->andReturnUsing(
            static function (QuoteItem $item): void {
                $item->setId(78);
            }
        );

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);
        $qR->shouldNotReceive('repoQuoteUnLoadedquery');

        /** @var ProductRepository&m\MockInterface $pR */
        $pR = m::mock(ProductRepository::class);
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(9)->andReturn(null);

        /** @var TaskRepository&m\MockInterface $taskR */
        $taskR = m::mock(TaskRepository::class);
        $taskR->shouldNotReceive('repoTaskquery');

        /** @var QuoteItemAllowanceChargeRepository&m\MockInterface $acqiR */
        $acqiR = m::mock(QuoteItemAllowanceChargeRepository::class);
        $acqiR->shouldNotReceive('repoQuoteItemquery');

        $service = $this->makeService($repository, $acqiR, $qR, null, $pR, $taskR);

        /** @var ProductRepository&m\MockInterface $depsPR */
        $depsPR = m::mock(ProductRepository::class);
        $depsPR->shouldReceive('repoProductquery')->andReturn(null);

        /** @var TaxRateRepository&m\MockInterface $depsTRR */
        $depsTRR = m::mock(TaxRateRepository::class);
        $depsTRR->shouldReceive('repoTaxRatequery')->andReturn(null);

        /** @var UnitRepository&m\MockInterface $depsUR */
        $depsUR = m::mock(UnitRepository::class);
        $depsUR->shouldReceive('repoUnitquery')->andReturn(null);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('translate');

        /** @var QuoteItemAmountRepository&m\MockInterface $depsQiar */
        $depsQiar = m::mock(QuoteItemAmountRepository::class);
        $depsQiar->shouldNotReceive('repoCount');

        /** @var QuoteItemAmountService&m\MockInterface $depsQias */
        $depsQias = m::mock(QuoteItemAmountService::class);
        $depsQias->shouldNotReceive('saveQuoteItemAmountNoForm');

        $deps = new QiAddProductDeps($depsPR, $depsQiar, $depsQias, $depsUR, $depsTRR, $translator);

        $service->addQuoteItemProduct(
            $model,
            ['product_id' => '9', 'quantity' => '2', 'product_unit_id' => '0'],
            '200',
            $deps,
            mergeIfExists: true,
        );

        Assert::same(9, $model->getProductId());
    }
}
