<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvCustom\InvCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvCustom\InvCustomRepository;
use App\Invoice\InvCustom\InvCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvCustomService: saveInvCustom's inv/custom-field relation
 * persistence and field assignment, and deleteInvCustom.
 */
#[Test]
final class InvCustomServiceTest
{
    private function makeService(
        ?InvCustomRepository $repository = null,
        ?IR $iR = null,
        ?CFR $cfR = null,
    ): InvCustomService {
        $repository = $repository ?? m::mock(InvCustomRepository::class);
        $iR = $iR ?? m::mock(IR::class);
        $cfR = $cfR ?? m::mock(CFR::class);
        return new InvCustomService($repository, $iR, $cfR);
    }

    public function saveInvCustomSetsAllFieldsAndSaves(): void
    {
        $model = new InvCustom();
        $array = [
            'inv_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        $inv = m::mock(Inv::class);
        $iR = m::mock(IR::class);
        /** @var \Mockery\Expectation $e */
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(1)->andReturn($inv);

        $customField = m::mock(CustomField::class);
        $cfR = m::mock(CFR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        $repository = m::mock(InvCustomRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $iR, $cfR);
        $service->saveInvCustom($model, $array);

        Assert::same($inv, $model->getInv());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqInvId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function saveInvCustomSkipsRelationsWhenIdsMissing(): void
    {
        $model = new InvCustom();
        $array = [];

        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        $repository = m::mock(InvCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $iR, $cfR);
        $service->saveInvCustom($model, $array);

        Assert::null($model->getInv());
        Assert::null($model->getCustomField());
    }

    public function deleteInvCustomCallsRepositoryDelete(): void
    {
        $model = new InvCustom();

        $repository = m::mock(InvCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvCustom($model);
    }
}
