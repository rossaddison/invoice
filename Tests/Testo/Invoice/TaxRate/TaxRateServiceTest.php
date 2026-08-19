<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\TaxRate;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\TaxRate\TaxRateService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers TaxRateService: saveTaxRate's field assignment, including the
 * "new records are never saved as default" rule that overrides an
 * incoming tax_rate_default=1 for unpersisted models, and deleteTaxRate.
 */
#[Test]
final class TaxRateServiceTest
{
    private function makeService(?TaxRateRepository $repository = null): TaxRateService
    {
        /** @var TaxRateRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(TaxRateRepository::class);
        return new TaxRateService($repository);
    }

    public function saveTaxRateSetsAllFieldsForExistingRecord(): void
    {
        $model = new TaxRate();
        $model->setTaxRateId(5);
        $array = [
            'tax_rate_name' => 'Standard',
            'tax_rate_percent' => 20.0,
            'tax_rate_code' => 'S',
            'tax_rate_default' => '1',
            'peppol_tax_rate_code' => 'AA',
            'storecove_tax_type' => 'standard',
        ];

        /** @var TaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(TaxRateRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveTaxRate($model, $array);

        Assert::same('Standard', $model->getTaxRateName());
        Assert::same(20.0, $model->getTaxRatePercent());
        Assert::same('S', $model->getTaxRateCode());
        Assert::true($model->getTaxRateDefault());
        Assert::same('AA', $model->getPeppolTaxRateCode());
        Assert::same('standard', $model->getStorecoveTaxType());
    }

    public function saveTaxRateForcesDefaultFalseForNewRecord(): void
    {
        $model = new TaxRate();
        $array = [
            'tax_rate_name' => 'Zero',
            'tax_rate_percent' => 0.0,
            'tax_rate_code' => 'Z',
            'tax_rate_default' => '1',
        ];

        /** @var TaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(TaxRateRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveTaxRate($model, $array);

        Assert::false($model->getTaxRateDefault());
    }

    public function deleteTaxRateCallsRepositoryDelete(): void
    {
        $model = new TaxRate();

        /** @var TaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(TaxRateRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteTaxRate($model);
    }
}
