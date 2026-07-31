<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Delivery;

use App\Infrastructure\Persistence\Delivery\Delivery;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\Delivery\DeliveryService;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers DeliveryService: saveDelivery's delivery-location relation
 * persistence, start/actual/end date parsing (including the invalid-date
 * fallback that keeps the constructor default), field assignment, and
 * deleteDelivery.
 */
#[Test]
final class DeliveryServiceTest
{
    private function makeService(
        ?DeliveryRepository $repository = null,
        ?DLR $dlR = null,
    ): DeliveryService {
        $repository = $repository ?? m::mock(DeliveryRepository::class);
        $dlR = $dlR ?? m::mock(DLR::class);
        return new DeliveryService($repository, $dlR);
    }

    public function saveDeliverySetsAllFieldsAndSaves(): void
    {
        $model = new Delivery();
        $array = [
            'start_date' => '2026-02-01',
            'actual_delivery_date' => '2026-02-03',
            'end_date' => '2026-02-28',
            'delivery_location_id' => 7,
            'delivery_party_id' => 2,
            'inv_id' => 15,
            'inv_item_id' => 4,
        ];

        $deliveryLocation = m::mock(DeliveryLocation::class);
        $dlR = m::mock(DLR::class);
        /** @var \Mockery\Expectation $e */
        $e = $dlR->shouldReceive('repoDeliveryLocationquery');
        $e->once()->with(7)->andReturn($deliveryLocation);

        $repository = m::mock(DeliveryRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $dlR);
        $service->saveDelivery($model, $array);

        Assert::same($deliveryLocation, $model->getDeliveryLocation());
        Assert::same(7, $model->reqDeliveryLocationId());
        Assert::same(2, $model->reqDeliveryPartyId());
        Assert::same(15, $model->getInvId());
        Assert::same(4, $model->getInvItemId());
        Assert::same('2026-02-01', $model->getStartDate()?->format('Y-m-d'));
        Assert::same('2026-02-03', $model->getActualDeliveryDate()?->format('Y-m-d'));
        Assert::same('2026-02-28', $model->getEndDate()?->format('Y-m-d'));
    }

    public function saveDeliveryKeepsDefaultDatesWhenInvalid(): void
    {
        $model = new Delivery();
        $defaultStart = $model->getStartDate()?->format('Y-m-d');
        $defaultEnd = $model->getEndDate()?->format('Y-m-d');
        $array = [
            'start_date' => 'not-a-date',
            'actual_delivery_date' => 'not-a-date',
            'end_date' => 'not-a-date',
        ];

        $dlR = m::mock(DLR::class);
        $dlR->shouldNotReceive('repoDeliveryLocationquery');

        $repository = m::mock(DeliveryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $dlR);
        $service->saveDelivery($model, $array);

        Assert::null($model->getDeliveryLocation());
        Assert::same($defaultStart, $model->getStartDate()?->format('Y-m-d'));
        Assert::same($defaultEnd, $model->getEndDate()?->format('Y-m-d'));
    }

    public function deleteDeliveryCallsRepositoryDelete(): void
    {
        $model = new Delivery();

        /** @var DeliveryRepository&m\MockInterface $repository */
        $repository = m::mock(DeliveryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteDelivery($model);
    }
}
