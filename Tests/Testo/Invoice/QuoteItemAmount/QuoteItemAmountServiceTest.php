<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteItemAmount;

use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuoteItemAmountService: saveQuoteItemAmountNoForm's quote-item
 * relation persistence and field assignment, and deleteQuoteItemAmount.
 */
#[Test]
final class QuoteItemAmountServiceTest
{
    private function makeService(
        ?QuoteItemAmountRepository $repository = null,
        ?QuoteItemRepository $quoteItemRepository = null,
    ): QuoteItemAmountService {
        $repository = $repository ?? m::mock(QuoteItemAmountRepository::class);
        $quoteItemRepository = $quoteItemRepository ?? m::mock(QuoteItemRepository::class);
        return new QuoteItemAmountService($repository, $quoteItemRepository);
    }

    public function saveQuoteItemAmountNoFormSetsAllFieldsAndSaves(): void
    {
        $model = new QuoteItemAmount();
        $quoteitem = [
            'quote_item_id' => 4,
            'charge' => 5.00,
            'allowance' => 2.50,
            'subtotal' => 100.00,
            'taxtotal' => 20.00,
            'discount' => 1.00,
            'total' => 121.50,
        ];

        $quoteItem = m::mock(QuoteItem::class);
        $quoteItemRepository = m::mock(QuoteItemRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $quoteItemRepository->shouldReceive('repoQuoteItemquery');
        $e->once()->with(4)->andReturn($quoteItem);

        $repository = m::mock(QuoteItemAmountRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $quoteItemRepository);
        $service->saveQuoteItemAmountNoForm($model, $quoteitem);

        Assert::same($quoteItem, $model->getQuoteItem());
        Assert::same(5.00, $model->getCharge());
        Assert::same(2.50, $model->getAllowance());
        Assert::same(100.00, $model->getSubtotal());
        Assert::same(20.00, $model->getTaxTotal());
        Assert::same(1.00, $model->getDiscount());
        Assert::same(121.50, $model->getTotal());
    }

    public function saveQuoteItemAmountNoFormSkipsQuoteItemWhenNotFound(): void
    {
        $model = new QuoteItemAmount();
        $quoteitem = [
            'quote_item_id' => 9,
            'charge' => 0.00,
            'allowance' => 0.00,
            'subtotal' => 0.00,
            'taxtotal' => 0.00,
            'discount' => 0.00,
            'total' => 0.00,
        ];

        $quoteItemRepository = m::mock(QuoteItemRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $quoteItemRepository->shouldReceive('repoQuoteItemquery');
        $e->once()->with(9)->andReturn(null);

        $repository = m::mock(QuoteItemAmountRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $quoteItemRepository);
        $service->saveQuoteItemAmountNoForm($model, $quoteitem);

        Assert::null($model->getQuoteItem());
    }

    public function deleteQuoteItemAmountCallsRepositoryDelete(): void
    {
        $model = new QuoteItemAmount();

        $repository = m::mock(QuoteItemAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQuoteItemAmount($model);
    }
}
