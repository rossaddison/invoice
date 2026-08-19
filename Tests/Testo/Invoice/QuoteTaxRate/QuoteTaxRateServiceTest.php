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
        /** @var QuoteTaxRateRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(QuoteTaxRateRepository::class);
        /** @var QuoteRepository&m\MockInterface $quoteRepository */
        $quoteRepository = $quoteRepository ?? m::mock(QuoteRepository::class);
        /** @var TaxRateRepository&m\MockInterface $taxRateRepository */
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

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $e = $quote->shouldReceive('reqId');
        $e->once()->andReturn(1);

        /** @var QuoteRepository&m\MockInterface $quoteRepository */
        $quoteRepository = m::mock(QuoteRepository::class);
        $e2 = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e2->once()->with(1)->andReturn($quote);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e3 = $taxRate->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        /** @var TaxRateRepository&m\MockInterface $taxRateRepository */
        $taxRateRepository = m::mock(TaxRateRepository::class);
        $e4 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e4->once()->with(2)->andReturn($taxRate);

        /** @var QuoteTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteTaxRateRepository::class);
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

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $e = $quote->shouldReceive('reqId');
        $e->once()->andReturn(1);

        /** @var QuoteRepository&m\MockInterface $quoteRepository */
        $quoteRepository = m::mock(QuoteRepository::class);
        $e2 = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e2->once()->with(1)->andReturn($quote);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e3 = $taxRate->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        /** @var TaxRateRepository&m\MockInterface $taxRateRepository */
        $taxRateRepository = m::mock(TaxRateRepository::class);
        $e4 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e4->once()->with(2)->andReturn($taxRate);

        /** @var QuoteTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteTaxRateRepository::class);
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

        /** @var QuoteRepository&m\MockInterface $quoteRepository */
        $quoteRepository = m::mock(QuoteRepository::class);
        $e = $quoteRepository->shouldReceive('repoQuoteUnLoadedquery');
        $e->once()->with(9)->andReturn(null);

        /** @var TaxRateRepository&m\MockInterface $taxRateRepository */
        $taxRateRepository = m::mock(TaxRateRepository::class);
        $e2 = $taxRateRepository->shouldReceive('repoTaxRatequery');
        $e2->once()->with(8)->andReturn(null);

        /** @var QuoteTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteTaxRateRepository::class);
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

        /** @var QuoteTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteTaxRateRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQuoteTaxRate($model);
    }
}
