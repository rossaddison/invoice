<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrderTaxRate;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SalesOrderTaxRateService: saveSoTaxRate's sales-order/tax-rate
 * relation persistence and field assignment, and deleteSalesOrderTaxRate.
 */
#[Test]
final class SalesOrderTaxRateServiceTest
{
    private function makeService(
        ?SalesOrderTaxRateRepository $repository = null,
        ?SOR $soR = null,
        ?TRR $trR = null,
    ): SalesOrderTaxRateService {
        /** @var SalesOrderTaxRateRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(SalesOrderTaxRateRepository::class);
        /** @var SOR&m\MockInterface $soR */
        $soR = $soR ?? m::mock(SOR::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = $trR ?? m::mock(TRR::class);
        return new SalesOrderTaxRateService($repository, $soR, $trR);
    }

    public function saveSoTaxRateSetsAllFieldsAndSaves(): void
    {
        $model = new SalesOrderTaxRate();
        $array = [
            'sales_order_id' => 1,
            'tax_rate_id' => 2,
            'include_item_tax' => 1,
            'sales_order_tax_rate_amount' => 12.5,
        ];

        /** @var SalesOrder&m\MockInterface $salesOrder */
        $salesOrder = m::mock(SalesOrder::class);
        $e = $salesOrder->shouldReceive('reqId');
        $e->once()->andReturn(1);

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e2 = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e2->once()->with(1)->andReturn($salesOrder);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $e3 = $taxRate->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $e4 = $trR->shouldReceive('repoTaxRatequery');
        $e4->once()->with(2)->andReturn($taxRate);

        /** @var SalesOrderTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderTaxRateRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $soR, $trR);
        $service->saveSoTaxRate($model, $array);

        Assert::same($salesOrder, $model->getSalesOrder());
        Assert::same($taxRate, $model->getTaxRate());
        // SalesOrderTaxRate has no getSalesOrderId() getter to assert against
        // directly; the relation object assertion above covers persist().
        Assert::same(2, $model->reqTaxRateId());
        Assert::same(1, $model->getIncludeItemTax());
        Assert::same(12.5, $model->getSalesOrderTaxRateAmount());
    }

    public function saveSoTaxRateSkipsRelationsWhenNotFound(): void
    {
        $model = new SalesOrderTaxRate();
        $array = [
            'sales_order_id' => 9,
            'tax_rate_id' => 8,
            'include_item_tax' => 0,
            'sales_order_tax_rate_amount' => 0.00,
        ];

        /** @var SOR&m\MockInterface $soR */
        $soR = m::mock(SOR::class);
        $e = $soR->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(9)->andReturn(null);

        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(8)->andReturn(null);

        /** @var SalesOrderTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderTaxRateRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $soR, $trR);
        $service->saveSoTaxRate($model, $array);

        Assert::null($model->getSalesOrder());
        Assert::null($model->getTaxRate());
    }

    public function deleteSalesOrderTaxRateCallsRepositoryDelete(): void
    {
        $model = new SalesOrderTaxRate();

        /** @var SalesOrderTaxRateRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderTaxRateRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteSalesOrderTaxRate($model);
    }
}
