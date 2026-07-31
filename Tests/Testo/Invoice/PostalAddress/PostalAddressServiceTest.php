<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PostalAddress;

use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\PostalAddress\PostalAddressService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PostalAddressService: savePostalAddress's field assignment and
 * deletePostalAddress.
 */
#[Test]
final class PostalAddressServiceTest
{
    private function makeService(?PostalAddressRepository $repository = null): PostalAddressService
    {
        $repository = $repository ?? m::mock(PostalAddressRepository::class);
        return new PostalAddressService($repository);
    }

    public function savePostalAddressSetsAllFieldsAndSaves(): void
    {
        $model = new PostalAddress();
        $array = [
            'id' => 1,
            'client_id' => 3,
            'street_name' => 'Main Street',
            'additional_street_name' => 'Floor 2',
            'building_number' => '10',
            'city_name' => 'London',
            'postalzone' => 'SW1A 1AA',
            'countrysubentity' => 'Greater London',
            'country' => 'GB',
        ];

        /** @var PostalAddressRepository&m\MockInterface $repository */
        $repository = m::mock(PostalAddressRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->savePostalAddress($model, $array);

        Assert::same(3, $model->getClientId());
        Assert::same('Main Street', $model->getStreetName());
        Assert::same('Floor 2', $model->getAdditionalStreetName());
        Assert::same('10', $model->getBuildingNumber());
        Assert::same('London', $model->getCityName());
        Assert::same('SW1A 1AA', $model->getPostalzone());
        Assert::same('Greater London', $model->getCountrysubentity());
        Assert::same('GB', $model->getCountry());
    }

    public function savePostalAddressSkipsFieldsWhenMissing(): void
    {
        $model = new PostalAddress();

        /** @var PostalAddressRepository&m\MockInterface $repository */
        $repository = m::mock(PostalAddressRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->savePostalAddress($model, []);

        Assert::null($model->getClientId());
        Assert::same('', $model->getStreetName());
        Assert::same('', $model->getCountry());
    }

    public function deletePostalAddressCallsRepositoryDelete(): void
    {
        $model = new PostalAddress();

        /** @var PostalAddressRepository&m\MockInterface $repository */
        $repository = m::mock(PostalAddressRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deletePostalAddress($model);
    }
}
