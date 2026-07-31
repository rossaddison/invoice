<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\FamilyCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\FamilyCustom\FamilyCustomRepository;
use App\Invoice\FamilyCustom\FamilyCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers FamilyCustomService: saveFamilyCustom's family/custom-field relation
 * persistence and field assignment, and deleteFamilyCustom.
 */
#[Test]
final class FamilyCustomServiceTest
{
    private function makeService(
        ?FamilyCustomRepository $repository = null,
        ?FR $fR = null,
        ?CFR $cfR = null,
    ): FamilyCustomService {
        $repository = $repository ?? m::mock(FamilyCustomRepository::class);
        $fR = $fR ?? m::mock(FR::class);
        $cfR = $cfR ?? m::mock(CFR::class);
        return new FamilyCustomService($repository, $fR, $cfR);
    }

    public function saveFamilyCustomSetsAllFieldsAndSaves(): void
    {
        $model = new FamilyCustom();
        $array = [
            'family_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        $family = m::mock(Family::class);
        $fR = m::mock(FR::class);
        /** @var \Mockery\Expectation $e */
        $e = $fR->shouldReceive('repoFamilyquery');
        $e->once()->with(1)->andReturn($family);

        $customField = m::mock(CustomField::class);
        $cfR = m::mock(CFR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        $repository = m::mock(FamilyCustomRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $fR, $cfR);
        $service->saveFamilyCustom($model, $array);

        Assert::same($family, $model->getFamily());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqFamilyId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function saveFamilyCustomSkipsRelationsWhenIdsMissing(): void
    {
        $model = new FamilyCustom();
        $array = [];

        $fR = m::mock(FR::class);
        $fR->shouldNotReceive('repoFamilyquery');

        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        $repository = m::mock(FamilyCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $fR, $cfR);
        $service->saveFamilyCustom($model, $array);

        Assert::null($model->getFamily());
        Assert::null($model->getCustomField());
    }

    public function deleteFamilyCustomCallsRepositoryDelete(): void
    {
        $model = new FamilyCustom();

        $repository = m::mock(FamilyCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteFamilyCustom($model);
    }
}
