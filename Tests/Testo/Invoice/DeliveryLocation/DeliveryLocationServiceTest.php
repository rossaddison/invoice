<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\DeliveryLocation;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers DeliveryLocationService: saveDeliveryLocation's client-relation
 * persistence and field assignment, and deleteDeliveryLocation.
 */
#[Test]
final class DeliveryLocationServiceTest
{
    private function makeService(
        ?DeliveryLocationRepository $repository = null,
        ?CR $cR = null,
    ): DeliveryLocationService {
        $repository = $repository ?? m::mock(DeliveryLocationRepository::class);
        $cR = $cR ?? m::mock(CR::class);
        return new DeliveryLocationService($repository, $cR);
    }

    public function saveDeliveryLocationSetsAllFieldsAndSaves(): void
    {
        $model = new DeliveryLocation();
        $array = [
            'client_id' => 3,
            'name' => 'Warehouse A',
            'building_number' => '12',
            'address_1' => 'Industrial Estate',
            'address_2' => 'Unit 4',
            'city' => 'Leeds',
            'state' => 'West Yorkshire',
            'zip' => 'LS1 1AA',
            'country' => 'UK',
            'global_location_number' => '1234567890123',
            'electronic_address_scheme' => '0088',
        ];

        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(3)->andReturn($client);

        $repository = m::mock(DeliveryLocationRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveDeliveryLocation($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(3, $model->reqClientId());
        Assert::same('Warehouse A', $model->getName());
        Assert::same('12', $model->getBuildingNumber());
        Assert::same('Industrial Estate', $model->getAddress1());
        Assert::same('Unit 4', $model->getAddress2());
        Assert::same('Leeds', $model->getCity());
        Assert::same('West Yorkshire', $model->getState());
        Assert::same('LS1 1AA', $model->getZip());
        Assert::same('UK', $model->getCountry());
        Assert::same('1234567890123', $model->getGlobalLocationNumber());
        Assert::same('0088', $model->getElectronicAddressScheme());
    }

    public function saveDeliveryLocationSkipsFieldsWhenNotProvided(): void
    {
        $model = new DeliveryLocation();
        $array = [];

        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        $repository = m::mock(DeliveryLocationRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveDeliveryLocation($model, $array);

        Assert::null($model->getClient());
        Assert::same('', $model->getName());
    }

    public function deleteDeliveryLocationCallsRepositoryDelete(): void
    {
        $model = new DeliveryLocation();

        /** @var DeliveryLocationRepository&m\MockInterface $repository */
        $repository = m::mock(DeliveryLocationRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteDeliveryLocation($model);
    }
}
