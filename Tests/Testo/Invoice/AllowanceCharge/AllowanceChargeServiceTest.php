<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\AllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge as AC;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\AllowanceCharge\AllowanceChargeService;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers AllowanceChargeService: saveAllowanceCharge's tax-rate relation
 * persistence and field assignment, and deleteAllowanceCharge.
 */
#[Test]
final class AllowanceChargeServiceTest
{
    private function makeService(
        ?AllowanceChargeRepository $repository = null,
        ?trR $trR = null,
    ): AllowanceChargeService {
        $repository = $repository ?? m::mock(AllowanceChargeRepository::class);
        $trR = $trR ?? m::mock(trR::class);
        return new AllowanceChargeService($repository, $trR);
    }

    public function saveAllowanceChargeSetsAllFieldsAndSaves(): void
    {
        $model = new AC();
        $array = [
            'identifier' => true,
            'reason_code' => '95',
            'reason' => 'Discount',
            'multiplier_factor_numeric' => 10,
            'amount' => 500,
            'base_amount' => 5000,
            'tax_rate_id' => 3,
        ];

        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(3);
        $trR = m::mock(trR::class);
        /** @var \Mockery\Expectation $e */
        $e = $trR->shouldReceive('repoTaxRatequery');
        $e->once()->with(3)->andReturn($taxRate);

        $repository = m::mock(AllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $trR);
        $result = $service->saveAllowanceCharge($model, $array);

        Assert::same($model, $result);
        Assert::same($taxRate, $model->getTaxRate());
        Assert::true($model->getIdentifier());
        Assert::same('95', $model->getReasonCode());
        Assert::same('Discount', $model->getReason());
        Assert::same(10, $model->getMultiplierFactorNumeric());
        Assert::same(500, $model->getAmount());
        Assert::same(5000, $model->getBaseAmount());
        Assert::same(3, $model->getTaxRateId());
    }

    public function saveAllowanceChargeSkipsTaxRateRelationWhenIdMissing(): void
    {
        $model = new AC();
        $array = ['identifier' => false, 'reason_code' => '95', 'reason' => 'Charge'];

        $trR = m::mock(trR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        $repository = m::mock(AllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $trR);
        $service->saveAllowanceCharge($model, $array);

        Assert::null($model->getTaxRate());
        Assert::false($model->getIdentifier());
    }

    public function deleteAllowanceChargeCallsRepositoryDeleteAndReturnsModel(): void
    {
        $model = new AC();

        $repository = m::mock(AllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $result = $service->deleteAllowanceCharge($model);

        Assert::same($model, $result);
    }
}
