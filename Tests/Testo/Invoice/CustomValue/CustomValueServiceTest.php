<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\CustomValue;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\CustomValue\CustomValue;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\CustomValue\CustomValueService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CustomValueService: saveCustomValue's custom-field relation
 * persistence and field assignment, and deleteCustomValue.
 */
#[Test]
final class CustomValueServiceTest
{
    private function makeService(
        ?CustomValueRepository $repository = null,
        ?CFR $cfR = null,
    ): CustomValueService {
        $repository = $repository ?? m::mock(CustomValueRepository::class);
        $cfR = $cfR ?? m::mock(CFR::class);
        return new CustomValueService($repository, $cfR);
    }

    public function saveCustomValueSetsAllFieldsAndSaves(): void
    {
        $model = new CustomValue();
        $array = [
            'custom_field_id' => 9,
            'value' => 'PO-1234',
        ];

        $customField = m::mock(CustomField::class);
        $cfR = m::mock(CFR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cfR->shouldReceive('repoCustomFieldquery');
        $e->once()->with(9)->andReturn($customField);

        $repository = m::mock(CustomValueRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cfR);
        $service->saveCustomValue($model, $array);

        Assert::same($customField, $model->getCustomField());
        Assert::same(9, $model->reqCustomFieldId());
        Assert::same('PO-1234', $model->getValue());
    }

    public function saveCustomValueSkipsFieldsWhenNotProvided(): void
    {
        $model = new CustomValue();
        $array = [];

        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        $repository = m::mock(CustomValueRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cfR);
        $service->saveCustomValue($model, $array);

        Assert::null($model->getCustomField());
        Assert::same('', $model->getValue());
    }

    public function deleteCustomValueCallsRepositoryDelete(): void
    {
        $model = new CustomValue();

        /** @var CustomValueRepository&m\MockInterface $repository */
        $repository = m::mock(CustomValueRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCustomValue($model);
    }
}
