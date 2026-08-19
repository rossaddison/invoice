<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvAllowanceChargeService: saveInvAllowanceCharge's field
 * assignment and tax-rate-driven VAT computation, and
 * deleteInvAllowanceCharge.
 */
#[Test]
final class InvAllowanceChargeServiceTest
{
    private function makeService(
        ?InvAllowanceChargeRepository $repository = null,
        ?ACR $acR = null,
    ): InvAllowanceChargeService {
        /** @var InvAllowanceChargeRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvAllowanceChargeRepository::class);
        /** @var ACR&m\MockInterface $acR */
        $acR = $acR ?? m::mock(ACR::class);
        return new InvAllowanceChargeService($repository, $acR);
    }

    public function saveInvAllowanceChargeSetsFieldsAndComputesVat(): void
    {
        $model = new InvAllowanceCharge();
        $array = [
            'id' => 1,
            'inv_id' => 2,
            'allowance_charge_id' => 3,
            'amount' => 200.00,
        ];

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e = $taxRate->shouldReceive('getTaxRatePercent');
        $e->once()->andReturn(10.0);

        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e2 = $allowanceCharge->shouldReceive('getTaxRate');
        $e2->twice()->andReturn($taxRate);

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        $e3 = $acR->shouldReceive('repoAllowanceChargequery');
        $e3->once()->with(3)->andReturn($allowanceCharge);

        /** @var InvAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(InvAllowanceChargeRepository::class);
        $e4 = $repository->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService($repository, $acR);
        $service->saveInvAllowanceCharge($model, $array);

        Assert::same(1, $model->reqId());
        Assert::same(2, $model->reqInvId());
        Assert::same(3, $model->reqAllowanceChargeId());
        Assert::same(200.00, $model->getAmount());
        Assert::same(20.0, $model->getVatOrTax());
        // The AllowanceCharge relation itself is never assigned back onto
        // the model - saveInvAllowanceCharge only reads it for the VAT calc.
        Assert::null($model->getAllowanceCharge());
    }

    public function saveInvAllowanceChargeSkipsVatWhenAllowanceChargeNotFound(): void
    {
        $model = new InvAllowanceCharge();
        $array = [
            'id' => 1,
            'inv_id' => 2,
            'allowance_charge_id' => 9,
            'amount' => 50.00,
        ];

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        $e = $acR->shouldReceive('repoAllowanceChargequery');
        $e->once()->with(9)->andReturn(null);

        /** @var InvAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(InvAllowanceChargeRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $acR);
        $service->saveInvAllowanceCharge($model, $array);

        Assert::null($model->getVatOrTax());
    }

    public function saveInvAllowanceChargeSkipsVatWhenAllowanceChargeHasNoTaxRate(): void
    {
        $model = new InvAllowanceCharge();
        $array = [
            'id' => 1,
            'inv_id' => 2,
            'allowance_charge_id' => 3,
            'amount' => 50.00,
        ];

        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $e = $allowanceCharge->shouldReceive('getTaxRate');
        $e->once()->andReturn(null);

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        $e2 = $acR->shouldReceive('repoAllowanceChargequery');
        $e2->once()->with(3)->andReturn($allowanceCharge);

        /** @var InvAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(InvAllowanceChargeRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $acR);
        $service->saveInvAllowanceCharge($model, $array);

        Assert::null($model->getVatOrTax());
    }

    public function deleteInvAllowanceChargeCallsRepositoryDelete(): void
    {
        $model = new InvAllowanceCharge();

        /** @var InvAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(InvAllowanceChargeRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvAllowanceCharge($model);
    }
}
