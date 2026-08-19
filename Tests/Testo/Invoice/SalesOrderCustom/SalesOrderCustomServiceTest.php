<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrderCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderCustom\SalesOrderCustom;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SalesOrderCustomService: saveSoCustom's sales-order/custom-field
 * relation persistence and field assignment, and deleteSalesOrderCustom.
 */
#[Test]
final class SalesOrderCustomServiceTest
{
    private function makeService(
        ?SalesOrderCustomRepository $repository = null,
        ?CustomFieldRepository $customFieldRepository = null,
        ?SalesOrderRepository $salesOrderRepository = null,
    ): SalesOrderCustomService {
        /** @var SalesOrderCustomRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(SalesOrderCustomRepository::class);
        /** @var CustomFieldRepository&m\MockInterface $customFieldRepository */
        $customFieldRepository = $customFieldRepository ?? m::mock(CustomFieldRepository::class);
        /** @var SalesOrderRepository&m\MockInterface $salesOrderRepository */
        $salesOrderRepository = $salesOrderRepository ?? m::mock(SalesOrderRepository::class);
        return new SalesOrderCustomService($repository, $customFieldRepository, $salesOrderRepository);
    }

    public function saveSoCustomSetsAllFieldsAndSaves(): void
    {
        $model = new SalesOrderCustom();
        $array = [
            'sales_order_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        /** @var SalesOrder&m\MockInterface $salesOrder */
        $salesOrder = m::mock(SalesOrder::class);
        $e = $salesOrder->shouldReceive('reqId');
        $e->once()->andReturn(1);

        /** @var SalesOrderRepository&m\MockInterface $salesOrderRepository */
        $salesOrderRepository = m::mock(SalesOrderRepository::class);
        $e2 = $salesOrderRepository->shouldReceive('repoSalesOrderUnLoadedquery');
        $e2->once()->with(1)->andReturn($salesOrder);

        /** @var CustomField&m\MockInterface $customField */
        $customField = m::mock(CustomField::class);
        $e3 = $customField->shouldReceive('reqId');
        $e3->once()->andReturn(2);

        /** @var CustomFieldRepository&m\MockInterface $customFieldRepository */
        $customFieldRepository = m::mock(CustomFieldRepository::class);
        $e4 = $customFieldRepository->shouldReceive('repoCustomFieldquery');
        $e4->once()->with(2)->andReturn($customField);

        /** @var SalesOrderCustomRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderCustomRepository::class);
        $e5 = $repository->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repository, $customFieldRepository, $salesOrderRepository);
        $service->saveSoCustom($model, $array);

        Assert::same($salesOrder, $model->getSalesOrder());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqSalesOrderId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function saveSoCustomSkipsRelationsWhenNotFound(): void
    {
        $model = new SalesOrderCustom();
        $array = [
            'sales_order_id' => 9,
            'custom_field_id' => 8,
        ];

        /** @var SalesOrderRepository&m\MockInterface $salesOrderRepository */
        $salesOrderRepository = m::mock(SalesOrderRepository::class);
        $e = $salesOrderRepository->shouldReceive('repoSalesOrderUnLoadedquery');
        $e->once()->with(9)->andReturn(null);

        /** @var CustomFieldRepository&m\MockInterface $customFieldRepository */
        $customFieldRepository = m::mock(CustomFieldRepository::class);
        $e2 = $customFieldRepository->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(8)->andReturn(null);

        /** @var SalesOrderCustomRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderCustomRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $customFieldRepository, $salesOrderRepository);
        $service->saveSoCustom($model, $array);

        Assert::null($model->getSalesOrder());
        Assert::null($model->getCustomField());
    }

    public function deleteSalesOrderCustomCallsRepositoryDelete(): void
    {
        $model = new SalesOrderCustom();

        /** @var SalesOrderCustomRepository&m\MockInterface $repository */
        $repository = m::mock(SalesOrderCustomRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteSalesOrderCustom($model);
    }
}
