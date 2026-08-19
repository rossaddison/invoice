<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrderItemAmount;

use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SalesOrderItemAmountService: saveSalesOrderItemAmountNoForm's
 * sales-order-item relation persistence and field assignment, and
 * deleteSalesOrderItemAmount.
 */
#[Test]
final class SalesOrderItemAmountServiceTest
{
    private function makeService(
        ?SalesOrderItemAmountRepository $repository = null,
        ?SOIR $soiR = null,
    ): SalesOrderItemAmountService {
        /** @var SalesOrderItemAmountRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(SalesOrderItemAmountRepository::class);
        /** @var SOIR&m\MockInterface $soiR */
        $soiR = $soiR ?? m::mock(SOIR::class);
        return new SalesOrderItemAmountService($repository, $soiR);
    }

    public function saveSalesOrderItemAmountNoFormSetsAllFieldsAndSaves(): void
    {
        $model = new SalesOrderItemAmount();
        $soitem = [
            'sales_order_item_id' => 4,
            'charge' => 5.00,
            'allowance' => 2.50,
            'subtotal' => 100.00,
            'taxtotal' => 20.00,
            'discount' => 1.00,
            'total' => 121.50,
        ];

        /** @var SalesOrderItem&m\MockInterface $salesOrderItem */
        $salesOrderItem = m::mock(SalesOrderItem::class);
        $e = $salesOrderItem->shouldReceive('reqId');
        $e->once()->andReturn(4);

        /** @var SOIR&m\MockInterface $soiR */
        $soiR = m::mock(SOIR::class);
        $e2 = $soiR->shouldReceive('repoSalesOrderItemquery');
        $e2->once()->with(4)->andReturn($salesOrderItem);

        /** @var SalesOrderItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderItemAmountRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $soiR);
        $service->saveSalesOrderItemAmountNoForm($model, $soitem);

        Assert::same($salesOrderItem, $model->getSalesOrderItem());
        Assert::same(4, $model->reqSalesOrderItemId());
        Assert::same(5.00, $model->getCharge());
        Assert::same(2.50, $model->getAllowance());
        Assert::same(100.00, $model->getSubtotal());
        Assert::same(20.00, $model->getTaxTotal());
        Assert::same(1.00, $model->getDiscount());
        Assert::same(121.50, $model->getTotal());
    }

    public function saveSalesOrderItemAmountNoFormSkipsRelationWhenNotFound(): void
    {
        $model = new SalesOrderItemAmount();
        $soitem = [
            'sales_order_item_id' => 9,
            'charge' => 0.00,
            'allowance' => 0.00,
            'subtotal' => 0.00,
            'taxtotal' => 0.00,
            'discount' => 0.00,
            'total' => 0.00,
        ];

        /** @var SOIR&m\MockInterface $soiR */
        $soiR = m::mock(SOIR::class);
        $e = $soiR->shouldReceive('repoSalesOrderItemquery');
        $e->once()->with(9)->andReturn(null);

        /** @var SalesOrderItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderItemAmountRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $soiR);
        $service->saveSalesOrderItemAmountNoForm($model, $soitem);

        Assert::null($model->getSalesOrderItem());
    }

    public function deleteSalesOrderItemAmountCallsRepositoryDelete(): void
    {
        $model = new SalesOrderItemAmount();

        /** @var SalesOrderItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderItemAmountRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteSalesOrderItemAmount($model);
    }
}
