<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\DeliveryParty;

use App\Infrastructure\Persistence\DeliveryParty\DeliveryParty;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;
use App\Invoice\DeliveryParty\DeliveryPartyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers DeliveryPartyService: saveDeliveryParty's field assignment and
 * deleteDeliveryParty.
 */
#[Test]
final class DeliveryPartyServiceTest
{
    private function makeService(?DeliveryPartyRepository $repository = null): DeliveryPartyService
    {
        $repository = $repository ?? m::mock(DeliveryPartyRepository::class);
        return new DeliveryPartyService($repository);
    }

    public function saveDeliveryPartySetsPartyNameAndSaves(): void
    {
        $model = new DeliveryParty();
        $array = ['party_name' => 'Receiving Dock B'];

        /** @var DeliveryPartyRepository&m\MockInterface $repository */
        $repository = m::mock(DeliveryPartyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveDeliveryParty($model, $array);

        Assert::same('Receiving Dock B', $model->getPartyName());
    }

    public function saveDeliveryPartySkipsPartyNameWhenMissing(): void
    {
        $model = new DeliveryParty();

        /** @var DeliveryPartyRepository&m\MockInterface $repository */
        $repository = m::mock(DeliveryPartyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveDeliveryParty($model, []);

        Assert::same('', $model->getPartyName());
    }

    public function deleteDeliveryPartyCallsRepositoryDelete(): void
    {
        $model = new DeliveryParty();

        /** @var DeliveryPartyRepository&m\MockInterface $repository */
        $repository = m::mock(DeliveryPartyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteDeliveryParty($model);
    }
}
