<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrderAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\SalesOrderAllowanceCharge;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SalesOrderAllowanceChargeService: saveSalesOrderAllowanceCharge's
 * allowance-charge/sales-order relation persistence, its VAT/tax
 * recalculation off the linked AllowanceCharge's TaxRate, and
 * deleteSalesOrderAllowanceCharge.
 */
#[Test]
final class SalesOrderAllowanceChargeServiceTest
{
    private function makeService(
        ?SalesOrderAllowanceChargeRepository $repository = null,
        ?ACR $acR = null,
        ?SOR $soR = null,
    ): SalesOrderAllowanceChargeService {
        $repository = $repository ?? m::mock(SalesOrderAllowanceChargeRepository::class);
        $acR = $acR ?? m::mock(ACR::class);
        $soR = $soR ?? m::mock(SOR::class);
        return new SalesOrderAllowanceChargeService($repository, $acR, $soR);
    }

    public function saveWithTaxedAllowanceChargeSetsRelationsAndComputesVatOrTax(): void
    {
        $model = new SalesOrderAllowanceCharge();
        $array = [
            'id' => 10,
            'allowance_charge_id' => 3,
            'sales_order_id' => 4,
            'amount' => 200.0,
        ];

        $taxRate = new TaxRate();
        $taxRate->setTaxRatePercent(20.0);

        $ac = new AllowanceCharge();
        $ac->setId(3);
        $ac->setTaxRate($taxRate);

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acR->expects('repoAllowanceChargequery');
        $e->twice()->with(3)->andReturn($ac);

        /** @var SalesOrder&m\MockInterface $salesOrder */
        $salesOrder = m::mock(SalesOrder::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $salesOrder->shouldReceive('reqId');
        $e2->once()->andReturn(4);

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $soR->expects('repoSalesOrderUnLoadedquery');
        $e3->once()->with(4)->andReturn($salesOrder);

        /** @var SalesOrderAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderAllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repository->expects('save');
        $e4->once()->with($model);

        $service = $this->makeService($repository, $acR, $soR);
        $service->saveSalesOrderAllowanceCharge($model, $array);

        Assert::same(10, $model->reqId());
        Assert::same($ac, $model->getAllowanceCharge());
        Assert::same(3, $model->getAllowanceChargeId());
        Assert::same($salesOrder, $model->getSalesOrder());
        Assert::same(4, $model->getSalesOrderId());
        Assert::same(200.0, $model->getAmount());
        Assert::same(40.0, $model->getVatOrTax());
    }

    public function saveWithUntaxedAllowanceChargeSkipsVatOrTaxRecalculation(): void
    {
        $model = new SalesOrderAllowanceCharge();
        $array = [
            'allowance_charge_id' => 5,
            'sales_order_id' => 6,
            'amount' => 50.0,
        ];

        $ac = new AllowanceCharge();
        $ac->setId(5);

        /** @var ACR&m\MockInterface $acR */
        $acR = m::mock(ACR::class);
        /** @var \Mockery\Expectation $e */
        $e = $acR->expects('repoAllowanceChargequery');
        $e->twice()->with(5)->andReturn($ac);

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $soR->expects('repoSalesOrderUnLoadedquery');
        $e2->once()->with(6)->andReturn(null);

        /** @var SalesOrderAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderAllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->expects('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $acR, $soR);
        $service->saveSalesOrderAllowanceCharge($model, $array);

        Assert::same($ac, $model->getAllowanceCharge());
        Assert::null($model->getSalesOrder());
        Assert::same(50.0, $model->getAmount());
        Assert::null($model->getVatOrTax());
    }

    public function deleteSalesOrderAllowanceChargeCallsRepositoryDelete(): void
    {
        $model = new SalesOrderAllowanceCharge();

        /** @var SalesOrderAllowanceChargeRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderAllowanceChargeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteSalesOrderAllowanceCharge($model);
    }
}
