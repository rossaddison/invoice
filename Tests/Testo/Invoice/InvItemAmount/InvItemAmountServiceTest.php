<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvItemAmount;

use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvItemAmountService: saveInvItemAmountNoForm's inv_item relation
 * persistence and field assignment, and deleteInvItemAmount.
 */
#[Test]
final class InvItemAmountServiceTest
{
    private function makeService(
        ?InvItemAmountRepository $repository = null,
        ?IIR $iiR = null,
    ): InvItemAmountService {
        /** @var InvItemAmountRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvItemAmountRepository::class);
        /** @var IIR&m\MockInterface $iiR */
        $iiR = $iiR ?? m::mock(IIR::class);
        return new InvItemAmountService($repository, $iiR);
    }

    public function saveInvItemAmountNoFormSetsAllFieldsAndSaves(): void
    {
        $model = new InvItemAmount();
        $array = [
            'inv_item_id' => 6,
            'subtotal' => 100.0,
            'taxtotal' => 20.0,
            'discount' => 5.0,
            'charge' => 2.0,
            'allowance' => 1.0,
            'total' => 116.0,
        ];

        /** @var InvItem&m\MockInterface $invItem */
        $invItem = m::mock(InvItem::class);
        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $e = $iiR->shouldReceive('repoInvItemquery');
        $e->once()->with(6)->andReturn($invItem);

        /** @var InvItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemAmountRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iiR);
        $service->saveInvItemAmountNoForm($model, $array);

        Assert::same($invItem, $model->getInvItem());
        Assert::same(6, $model->reqInvItemId());
        Assert::same(100.0, $model->getSubtotal());
        Assert::same(20.0, $model->getTaxTotal());
        Assert::same(5.0, $model->getDiscount());
        Assert::same(2.0, $model->getCharge());
        Assert::same(1.0, $model->getAllowance());
        Assert::same(116.0, $model->getTotal());
    }

    public function saveInvItemAmountNoFormSkipsRelationWhenInvItemNotFound(): void
    {
        $model = new InvItemAmount();
        $array = [
            'inv_item_id' => 9,
            'subtotal' => 0.0,
            'taxtotal' => 0.0,
            'discount' => 0.0,
            'charge' => 0.0,
            'allowance' => 0.0,
            'total' => 0.0,
        ];

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $e = $iiR->shouldReceive('repoInvItemquery');
        $e->once()->with(9)->andReturn(null);

        /** @var InvItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemAmountRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $iiR);
        $service->saveInvItemAmountNoForm($model, $array);

        Assert::null($model->getInvItem());
    }

    public function deleteInvItemAmountCallsRepositoryDelete(): void
    {
        $model = new InvItemAmount();

        /** @var InvItemAmountRepository&m\MockInterface $repository */
        $repository = m::mock(InvItemAmountRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvItemAmount($model);
    }
}
