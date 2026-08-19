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
        /** @var InvCustomRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvCustomRepository::class);
        /** @var IR&m\MockInterface $iR */
        $iR = $iR ?? m::mock(IR::class);
        /** @var CFR&m\MockInterface $cfR */
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

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e = $iR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(1)->andReturn($inv);

        /** @var CustomField&m\MockInterface $customField */
        $customField = m::mock(CustomField::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        /** @var InvCustomRepository&m\MockInterface $repository */
        $repository = m::mock(InvCustomRepository::class);
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

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        /** @var InvCustomRepository&m\MockInterface $repository */
        $repository = m::mock(InvCustomRepository::class);
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

        /** @var InvCustomRepository&m\MockInterface $repository */
        $repository = m::mock(InvCustomRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvCustom($model);
    }
}
