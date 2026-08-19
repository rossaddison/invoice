<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuoteAllowanceChargeService: saveQuoteAllowanceCharge's
 * quote/allowance-charge relation persistence, field assignment and
 * tax-rate-driven VAT computation, and deleteQuoteAllowanceCharge.
 */
#[Test]
final class QuoteAllowanceChargeServiceTest
{
    private function makeService(
        ?QuoteAllowanceChargeRepository $repository = null,
        ?ACR $acR = null,
        ?QR $qR = null,
    ): QuoteAllowanceChargeService {
        /** @var QuoteAllowanceChargeRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(QuoteAllowanceChargeRepository::class);
        /** @var ACR&m\MockInterface $acR */
        $acR = $acR ?? m::mock(ACR::class);
        /** @var QR&m\MockInterface $qR */
        $qR = $qR ?? m::mock(QR::class);
        return new QuoteAllowanceChargeService($repository, $acR, $qR);
    }

    public function saveQuoteAllowanceChargeSetsFieldsAndComputesVat(): void
    {
        $model = new QuoteAllowanceCharge();
        $array = [
            'id' => 1,
            'quote_id' => 2,
            'allowance_charge_id' => 3,
            'amount' => 200.00,
        ];

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $e = $qR->shouldReceive('repoQuoteUnLoadedquery');
        $e->once()->with(2)->andReturn($quote);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e2 = $taxRate->shouldReceive('getTaxRatePercent');
        $e2->once()->andReturn(10.0);

        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e3 = $allowanceCharge->shouldReceive('getTaxRate');
        $e3->twice()->andReturn($taxRate);

        // repoAllowanceChargequery is called once from persist() (to set the
        // relation) and again unconditionally in saveQuoteAllowanceCharge
        // itself (for the VAT calc) - two calls with the same id.
        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        $e4 = $acR->shouldReceive('repoAllowanceChargequery');
        $e4->twice()->with(3)->andReturn($allowanceCharge);

        /** @var QuoteAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteAllowanceChargeRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $acR, $qR);
        $service->saveQuoteAllowanceCharge($model, $array);

        Assert::same($quote, $model->getQuote());
        Assert::same($allowanceCharge, $model->getAllowanceCharge());
        Assert::same(1, $model->reqId());
        Assert::same(2, $model->reqQuoteId());
        Assert::same(3, $model->reqAllowanceChargeId());
        Assert::same(200.00, $model->getAmount());
        Assert::same(20.0, $model->getVatOrTax());
    }

    public function saveQuoteAllowanceChargeSkipsVatWhenAllowanceChargeHasNoTaxRate(): void
    {
        $model = new QuoteAllowanceCharge();
        $array = [
            'id' => 1,
            'quote_id' => 2,
            'allowance_charge_id' => 3,
            'amount' => 50.00,
        ];

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $e = $qR->shouldReceive('repoQuoteUnLoadedquery');
        $e->once()->with(2)->andReturn($quote);

        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e2 = $allowanceCharge->shouldReceive('getTaxRate');
        $e2->once()->andReturn(null);

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        $e3 = $acR->shouldReceive('repoAllowanceChargequery');
        $e3->twice()->with(3)->andReturn($allowanceCharge);

        /** @var QuoteAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteAllowanceChargeRepository::class);
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService($repository, $acR, $qR);
        $service->saveQuoteAllowanceCharge($model, $array);

        Assert::null($model->getVatOrTax());
    }

    public function deleteQuoteAllowanceChargeCallsRepositoryDelete(): void
    {
        $model = new QuoteAllowanceCharge();

        /** @var QuoteAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteAllowanceChargeRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQuoteAllowanceCharge($model);
    }
}
