<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteAmount;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountForm;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers every public method of QuoteAmountService: initializeQuoteAmount(),
 * saveQuoteAmount(), saveQuoteAmountViaCalculations(), updateQuoteAmount()
 * and deleteQuoteAmount(). Repository interactions (save/delete) and the
 * arithmetic performed on the QuoteAmount model are verified precisely.
 */
#[Test]
final class QuoteAmountServiceTest
{
    public function initializeQuoteAmountSetsDefaultsAndSaves(): void
    {
        $model = new QuoteAmount();

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);
        $qR->shouldNotReceive('repoQuoteUnLoadedquery');

        $service = new QuoteAmountService($repo, $qR);
        $service->initializeQuoteAmount($model, 7);

        Assert::same(7, $model->reqQuoteId());
        Assert::same(0.00, $model->getItemSubtotal());
        Assert::same(0.00, $model->getItemTaxTotal());
        Assert::same(0.00, $model->getPackhandleshipTotal());
        Assert::same(0.00, $model->getPackhandleshipTax());
        Assert::same(0.00, $model->getTaxTotal());
        Assert::same(0.00, $model->getTotal());
    }

    public function saveQuoteAmountSetsAllFieldsFromFormAndSaves(): void
    {
        $model = new QuoteAmount();

        /** @var QuoteAmountForm&m\MockInterface $form */
        $form = m::mock(QuoteAmountForm::class);
        /** @var \Mockery\Expectation $e */
        $e = $form->shouldReceive('getQuoteId');
        $e->twice()->andReturn(9);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $form->shouldReceive('getItemSubtotal');
        $e2->once()->andReturn(10.5);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $form->shouldReceive('getItemTaxTotal');
        $e3->once()->andReturn(2.5);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $form->shouldReceive('getPackhandleshipTotal');
        $e4->once()->andReturn(1.0);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $form->shouldReceive('getPackhandleshipTax');
        $e5->once()->andReturn(0.5);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $form->shouldReceive('getTaxTotal');
        $e6->once()->andReturn(3.0);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $form->shouldReceive('getTotal');
        $e7->once()->andReturn(16.5);

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $repo->expects('save');
        $e8->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        $service = new QuoteAmountService($repo, $qR);
        $service->saveQuoteAmount($model, $form);

        Assert::same(9, $model->reqQuoteId());
        Assert::same(10.5, $model->getItemSubtotal());
        Assert::same(2.5, $model->getItemTaxTotal());
        Assert::same(1.0, $model->getPackhandleshipTotal());
        Assert::same(0.5, $model->getPackhandleshipTax());
        Assert::same(3.0, $model->getTaxTotal());
        Assert::same(16.5, $model->getTotal());
    }

    public function saveQuoteAmountKeepsExistingQuoteIdWhenFormQuoteIdIsNull(): void
    {
        $model = new QuoteAmount(quote_id: 55);

        /** @var QuoteAmountForm&m\MockInterface $form */
        $form = m::mock(QuoteAmountForm::class);
        /** @var \Mockery\Expectation $e */
        $e = $form->shouldReceive('getQuoteId');
        $e->once()->andReturn(null);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $form->shouldReceive('getItemSubtotal');
        $e2->once()->andReturn(null);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $form->shouldReceive('getItemTaxTotal');
        $e3->once()->andReturn(null);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $form->shouldReceive('getPackhandleshipTotal');
        $e4->once()->andReturn(null);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $form->shouldReceive('getPackhandleshipTax');
        $e5->once()->andReturn(null);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $form->shouldReceive('getTaxTotal');
        $e6->once()->andReturn(null);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $form->shouldReceive('getTotal');
        $e7->once()->andReturn(null);

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $repo->expects('save');
        $e8->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        $service = new QuoteAmountService($repo, $qR);
        $service->saveQuoteAmount($model, $form);

        Assert::same(55, $model->reqQuoteId());
        Assert::same(0.00, $model->getItemSubtotal());
        Assert::same(0.00, $model->getItemTaxTotal());
        Assert::same(0.00, $model->getPackhandleshipTotal());
        Assert::same(0.00, $model->getPackhandleshipTax());
        Assert::same(0.00, $model->getTaxTotal());
        Assert::same(0.00, $model->getTotal());
    }

    public function saveQuoteAmountViaCalculationsAttachesQuoteAndSetsFieldsFromArray(): void
    {
        $model = new QuoteAmount();
        $quote = new Quote();

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $qR->expects('repoQuoteUnLoadedquery');
        $e2->once()->with(5)->andReturn($quote);

        $service = new QuoteAmountService($repo, $qR);
        $service->saveQuoteAmountViaCalculations($model, [
            'quote_id' => 5,
            'item_subtotal' => 100.00,
            'item_taxtotal' => 20.00,
            'packhandleship_total' => 10,
            'packhandleship_tax' => 2,
            'tax_total' => 22.00,
            'total' => 132.00,
        ]);

        Assert::same($quote, $model->getQuote());
        Assert::same(5, $model->reqQuoteId());
        Assert::same(100.00, $model->getItemSubtotal());
        Assert::same(20.00, $model->getItemTaxTotal());
        Assert::same(10.00, $model->getPackhandleshipTotal());
        Assert::same(2.00, $model->getPackhandleshipTax());
        Assert::same(22.00, $model->getTaxTotal());
        Assert::same(132.00, $model->getTotal());
    }

    public function saveQuoteAmountViaCalculationsLeavesQuoteUnsetWhenNotFound(): void
    {
        $model = new QuoteAmount();

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $qR->expects('repoQuoteUnLoadedquery');
        $e2->once()->with(6)->andReturn(null);

        $service = new QuoteAmountService($repo, $qR);
        $service->saveQuoteAmountViaCalculations($model, [
            'quote_id' => 6,
            'item_subtotal' => 5.00,
            'item_taxtotal' => 1.00,
            'packhandleship_total' => 0,
            'packhandleship_tax' => 0,
            'tax_total' => 1.00,
            'total' => 6.00,
        ]);

        Assert::null($model->getQuote());
        Assert::same(6, $model->reqQuoteId());
    }

    public function updateQuoteAmountDoesNothingWhenModelNotFound(): void
    {
        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoQuotequery');
        $e->once()->with(3)->andReturn(null);
        $repo->shouldNotReceive('save');

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        /** @var QuoteAmountRepository&m\MockInterface $qaR */
        $qaR = m::mock(QuoteAmountRepository::class);
        /** @var QuoteItemAmountRepository&m\MockInterface $qiaR */
        $qiaR = m::mock(QuoteItemAmountRepository::class);
        $qiaR->shouldNotReceive('repoQuoteItemAmountquery');
        /** @var QuoteTaxRateRepository&m\MockInterface $qtrR */
        $qtrR = m::mock(QuoteTaxRateRepository::class);
        /** @var NumberHelper&m\MockInterface $numberHelper */
        $numberHelper = m::mock(NumberHelper::class);
        $numberHelper->shouldNotReceive('calculateQuoteTaxes');

        $service = new QuoteAmountService($repo, $qR);
        $service->updateQuoteAmount(3, $qaR, $qiaR, $qtrR, $numberHelper);
    }

    public function updateQuoteAmountDoesNothingWhenQuoteRelationMissing(): void
    {
        $model = new QuoteAmount();

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoQuotequery');
        $e->once()->with(4)->andReturn($model);
        $repo->shouldNotReceive('save');

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        /** @var QuoteAmountRepository&m\MockInterface $qaR */
        $qaR = m::mock(QuoteAmountRepository::class);
        /** @var QuoteItemAmountRepository&m\MockInterface $qiaR */
        $qiaR = m::mock(QuoteItemAmountRepository::class);
        $qiaR->shouldNotReceive('repoQuoteItemAmountquery');
        /** @var QuoteTaxRateRepository&m\MockInterface $qtrR */
        $qtrR = m::mock(QuoteTaxRateRepository::class);
        /** @var NumberHelper&m\MockInterface $numberHelper */
        $numberHelper = m::mock(NumberHelper::class);
        $numberHelper->shouldNotReceive('calculateQuoteTaxes');

        $service = new QuoteAmountService($repo, $qR);
        $service->updateQuoteAmount(4, $qaR, $qiaR, $qtrR, $numberHelper);
    }

    public function updateQuoteAmountSumsItemAmountsAndAppliesAdditionalQuoteTaxTotal(): void
    {
        $quote = new Quote();
        $item1 = new QuoteItem(id: 1);
        $item2 = new QuoteItem(id: 2);
        $item3 = new QuoteItem(id: 3);
        $quote->getItems()->add($item1);
        $quote->getItems()->add($item2);
        $quote->getItems()->add($item3);

        $model = new QuoteAmount();
        $model->setQuote($quote);

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoQuotequery');
        $e->once()->with(10)->andReturn($model);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        $qia1 = new QuoteItemAmount(subtotal: 100.00, tax_total: 10.00);
        $qia2 = new QuoteItemAmount(subtotal: 50.00, tax_total: 5.00);

        /** @var QuoteItemAmountRepository&m\MockInterface $qiaR */
        $qiaR = m::mock(QuoteItemAmountRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $qiaR->expects('repoQuoteItemAmountquery');
        $e3->once()->with(1)->andReturn($qia1);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $qiaR->expects('repoQuoteItemAmountquery');
        $e4->once()->with(2)->andReturn($qia2);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $qiaR->expects('repoQuoteItemAmountquery');
        $e5->once()->with(3)->andReturn(null);

        /** @var QuoteAmountRepository&m\MockInterface $qaR */
        $qaR = m::mock(QuoteAmountRepository::class);
        /** @var QuoteTaxRateRepository&m\MockInterface $qtrR */
        $qtrR = m::mock(QuoteTaxRateRepository::class);

        /** @var NumberHelper&m\MockInterface $numberHelper */
        $numberHelper = m::mock(NumberHelper::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $numberHelper->expects('calculateQuoteTaxes');
        $e6->once()->with(10, $qtrR, $qaR)->andReturn(12.34);

        $service = new QuoteAmountService($repo, $qR);
        $service->updateQuoteAmount(10, $qaR, $qiaR, $qtrR, $numberHelper);

        Assert::same(150.00, $model->getItemSubtotal());
        Assert::same(15.00, $model->getItemTaxTotal());
        Assert::same(0.00, $model->getPackhandleshipTotal());
        Assert::same(0.00, $model->getPackhandleshipTax());
        Assert::same(12.34, $model->getTaxTotal());
        Assert::same(177.34, $model->getTotal());
    }

    public function deleteQuoteAmountCallsRepositoryDeleteWithModel(): void
    {
        $model = new QuoteAmount();

        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('delete');
        $e->once()->with($model);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        $service = new QuoteAmountService($repo, $qR);
        $service->deleteQuoteAmount($model);
    }

    public function deleteQuoteAmountCallsRepositoryDeleteWithNull(): void
    {
        /** @var QuoteAmountRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteAmountRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('delete');
        $e->once()->with(null);

        /** @var QuoteRepository&m\MockInterface $qR */
        $qR = m::mock(QuoteRepository::class);

        $service = new QuoteAmountService($repo, $qR);
        $service->deleteQuoteAmount(null);
    }
}
