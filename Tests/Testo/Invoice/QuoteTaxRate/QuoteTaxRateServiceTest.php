<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteTaxRate;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService;
use App\Invoice\TaxRate\TaxRateRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuoteTaxRateService: saveQuoteTaxRate's quote/tax-rate relation
 * persistence and field assignment, and deleteQuoteTaxRate.
 */
#[Test]
final class QuoteTaxRateServiceTest
{
    private function makeService(
        ?QuoteTaxRateRepository $repository = null,
        ?QuoteRepository $quoteRepository = null,
        ?TaxRateRepository $taxRateRepository = null,
    ): QuoteTaxRateService {
        $repository = $repository ?? m::mock(QuoteTaxRateRepository::class);
        $quoteRepository = $quoteRepository ?? m::mock(QuoteRepository::class);
        $taxRateRepository = $taxRateRepository ?? m::mock(TaxRateRepository::class);
        return new QuoteTaxRateService($repository, $quoteRepository, $taxRateRepository);
    }

    public function saveQuoteTaxRateSetsAllFieldsAndSaves(): void
    {
        $model = new QuoteTaxRate();
        $array = [
            'quote_id' => 1,
            'tax_rate_id' => 2,
            'include_item_tax' => 1,
            // setQuoteTaxRateAmount is only reached when 'tax_rate_amount' is
            // present (the isset guard), but the value actually read is
            // 'quote_tax_rate_amount' - both keys are required together.
            'tax_rate_amount' => true,
            'quote_tax_rate_amount' => 12.5,
        ];

        $quote = m::mock(Quote::class);
        /** @var \Mockery\Expectation $e */
        $e = $quote->shouldReceive('reqId');
        $e->once()->andReturn(1);

        $quoteRepository = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e2->once()->with(1)->andReturn($quote);

        $taxRate = m::mock(TaxRate::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $taxRate->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        $taxRateRepository = m::mock(TaxRateRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e4->once()->with(2)->andReturn($taxRate);

        $repository = m::mock(QuoteTaxRateRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $quoteRepository, $taxRateRepository);
        $service->saveQuoteTaxRate($model, $array);

        Assert::same($quote, $model->getQuote());
        Assert::same($taxRate, $model->getTaxRate());
        Assert::same(1, $model->reqQuoteId());
        Assert::same(2, $model->reqTaxRateId());
        Assert::same(1, $model->getIncludeItemTax());
        Assert::same(12.5, $model->getQuoteTaxRateAmount());
    }

    public function saveQuoteTaxRateSkipsAmountWhenTaxRateAmountKeyAbsent(): void
    {
        // Documents the isset('tax_rate_amount')-vs-read('quote_tax_rate_amount')
        // key mismatch: even though quote_tax_rate_amount is present, the
        // guard checks a different key, so the amount is never applied.
        $model = new QuoteTaxRate();
        $array = [
            'quote_id' => 1,
            'tax_rate_id' => 2,
            'include_item_tax' => 1,
            'quote_tax_rate_amount' => 99.0,
        ];

        $quote = m::mock(Quote::class);
        /** @var \Mockery\Expectation $e */
        $e = $quote->shouldReceive('reqId');
        $e->once()->andReturn(1);

        $quoteRepository = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e2->once()->with(1)->andReturn($quote);

        $taxRate = m::mock(TaxRate::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $taxRate->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        $taxRateRepository = m::mock(TaxRateRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e4->once()->with(2)->andReturn($taxRate);

        $repository = m::mock(QuoteTaxRateRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $quoteRepository, $taxRateRepository);
        $service->saveQuoteTaxRate($model, $array);

        Assert::same(0.00, $model->getQuoteTaxRateAmount());
    }

    public function saveQuoteTaxRateSkipsRelationsWhenNotFound(): void
    {
        $model = new QuoteTaxRate();
        $array = [
            'quote_id' => 9,
            'tax_rate_id' => 8,
            'include_item_tax' => 0,
        ];

        $quoteRepository = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e->once()->with(9)->andReturn(null);

        $taxRateRepository = m::mock(TaxRateRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e2->once()->with(8)->andReturn(null);

        $repository = m::mock(QuoteTaxRateRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $quoteRepository, $taxRateRepository);
        $service->saveQuoteTaxRate($model, $array);

        Assert::null($model->getQuote());
        Assert::null($model->getTaxRate());
    }

    public function deleteQuoteTaxRateCallsRepositoryDelete(): void
    {
        $model = new QuoteTaxRate();

        $repository = m::mock(QuoteTaxRateRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQuoteTaxRate($model);
    }
}
