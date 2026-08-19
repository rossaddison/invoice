<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvRecurring;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\InvRecurring\InvRecurringService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers InvRecurringService: saveInvRecurring's invoice-relation
 * persistence, the "restart" vs. "currently running" next/start date
 * computation, end-date parsing, and deleteInvRecurring.
 *
 * saveInvRecurring() calls the same InvRepository::repoInvUnLoadedquery()
 * twice per save — once in persist() to set the `inv` relation, once more
 * directly to fetch the "base invoice" gate — so every scenario below
 * expects it ->twice() with the same id, documenting the existing redundant
 * duplicate query rather than treating it as a new bug.
 *
 * See PR #998 "Possible issues found": setNext()/setStart() accept a plain
 * \DateTime, but getNext()/getStart() are declared to return
 * string|DateTimeImmutable(|null), which does not include \DateTime — so
 * calling those getters after saveInvRecurring() actually sets a date
 * throws a TypeError. Reflection on the private properties avoids
 * exercising that crash here.
 */
#[Test]
final class InvRecurringServiceTest
{
    private function nextProperty(InvRecurring $model): mixed
    {
        $property = new \ReflectionProperty(InvRecurring::class, 'next');
        return $property->getValue($model);
    }

    private function startProperty(InvRecurring $model): mixed
    {
        $property = new \ReflectionProperty(InvRecurring::class, 'start');
        return $property->getValue($model);
    }

    private function endProperty(InvRecurring $model): mixed
    {
        $property = new \ReflectionProperty(InvRecurring::class, 'end');
        return $property->getValue($model);
    }

    private function makeService(
        ?InvRecurringRepository $repository = null,
        ?IR $invR = null,
        ?SettingRepository $s = null,
    ): InvRecurringService {
        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvRecurringRepository::class);
        /** @var IR&m\MockInterface $invR */
        $invR = $invR ?? m::mock(IR::class);
        /** @var SettingRepository&m\MockInterface $s */
        $s = $s ?? m::mock(SettingRepository::class);
        return new InvRecurringService($repository, $invR, $s);
    }

    public function saveInvRecurringSkipsSaveWhenBaseInvoiceNotFound(): void
    {
        $model = new InvRecurring();
        $array = ['inv_id' => 3, 'frequency' => 'P1M'];

        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->twice()->with(3)->andReturn(null);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $repository->shouldNotReceive('save');

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        Assert::same(3, $model->reqInvId());
        Assert::null($model->getInv());
        Assert::same('P1M', $model->getFrequency());
    }

    public function saveInvRecurringRestartsAndSetsNextAndStartWhenNextWasEmpty(): void
    {
        $model = new InvRecurring();
        $array = [
            'inv_id' => 7,
            'frequency' => '1M',
            'start' => '2026-01-01',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->twice()->with(7)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        Assert::same($inv, $model->getInv());

        $next = $this->nextProperty($model);
        Assert::true($next instanceof \DateTime);
        Assert::same('2026-02-01', $next->format('Y-m-d'));

        $start = $this->startProperty($model);
        Assert::true($start instanceof \DateTime);
        Assert::same('2026-01-01', $start->format('Y-m-d'));
    }

    /**
     * The "currently running" branch (invoice already has a real `next`
     * DateTime, e.g. from a previous restart-branch save) can't actually be
     * exercised: saveInvRecurring() itself calls `$model->getNext()` to
     * decide which branch to take, and that call is what throws per the
     * class docblock — the bug crashes the method before its own branching
     * logic runs, not just a getter called by the caller afterward.
     */
    #[ExpectException(\TypeError::class)]
    public function saveInvRecurringThrowsWhenInvoiceAlreadyHasARealNextDateTime(): void
    {
        $model = new InvRecurring();
        $property = new \ReflectionProperty(InvRecurring::class, 'next');
        $property->setValue($model, new \DateTime('2025-06-01'));

        $array = [
            'inv_id' => 7,
            'frequency' => 'P1M',
            'start' => '2026-03-01',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->twice()->with(7)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $repository->shouldNotReceive('save');

        $this->makeService($repository, $invR)->saveInvRecurring($model, $array);
    }

    public function saveInvRecurringSetsEndDateWhenProvided(): void
    {
        $model = new InvRecurring();
        $array = [
            'inv_id' => 2,
            'frequency' => 'P1M',
            'end' => '2026-12-31',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->twice()->with(2)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        $end = $this->endProperty($model);
        Assert::true($end instanceof \DateTime);
        Assert::same('2026-12-31', $end->format('Y-m-d'));
    }

    public function saveInvRecurringSkipsEndWhenNotProvided(): void
    {
        $model = new InvRecurring();
        $array = ['inv_id' => 2, 'frequency' => 'P1M'];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->twice()->with(2)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        Assert::same('', $model->getEnd());
    }

    public function deleteInvRecurringCallsRepositoryDelete(): void
    {
        $model = new InvRecurring();

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvRecurring($model);
    }
}
