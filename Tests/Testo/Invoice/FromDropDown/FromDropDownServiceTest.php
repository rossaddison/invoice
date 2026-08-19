<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\FromDropDown;

use App\Infrastructure\Persistence\FromDropDown\FromDropDown;
use App\Invoice\FromDropDown\FromDropDownRepository;
use App\Invoice\FromDropDown\FromDropDownService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers FromDropDownService: saveFromDropDown's field assignment (including
 * the always-set include/default_email boolean flags) and
 * deleteFromDropDown.
 */
#[Test]
final class FromDropDownServiceTest
{
    private function makeService(?FromDropDownRepository $repository = null): FromDropDownService
    {
        /** @var FromDropDownRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(FromDropDownRepository::class);
        return new FromDropDownService($repository);
    }

    public function saveFromDropDownSetsAllFieldsAndSaves(): void
    {
        $model = new FromDropDown();
        $array = [
            'email' => 'billing@acme.example',
            'include' => '1',
            'default_email' => '1',
        ];

        /** @var FromDropDownRepository&m\MockInterface $repository */
        $repository = m::mock(FromDropDownRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveFromDropDown($model, $array);

        Assert::same('billing@acme.example', $model->getEmail());
        Assert::true($model->getInclude());
        Assert::true($model->getDefaultEmail());
    }

    public function saveFromDropDownSetsBooleanFlagsToFalseWhenNotOne(): void
    {
        $model = new FromDropDown();
        $array = [
            'include' => '0',
            'default_email' => '0',
        ];

        /** @var FromDropDownRepository&m\MockInterface $repository */
        $repository = m::mock(FromDropDownRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveFromDropDown($model, $array);

        Assert::same('', $model->getEmail());
        Assert::false($model->getInclude());
        Assert::false($model->getDefaultEmail());
    }

    public function deleteFromDropDownCallsRepositoryDelete(): void
    {
        $model = new FromDropDown();

        /** @var FromDropDownRepository&m\MockInterface $repository */
        $repository = m::mock(FromDropDownRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteFromDropDown($model);
    }
}
