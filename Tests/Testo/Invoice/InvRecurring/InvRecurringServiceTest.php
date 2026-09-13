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
use Testo\Test;

/**
 * Covers InvRecurringService: saveInvRecurring's invoice-relation
 * persistence, the "restart" vs. "currently running" next/start date
 * computation, end-date parsing, and deleteInvRecurring.
 *
 * saveInvRecurring() used to call the same
 * InvRepository::repoInvUnLoadedquery() twice per save — once in a
 * private persist() to set the `inv` relation, once more directly to
 * fetch the "base invoice" gate — for the exact same id. Fixed to resolve
 * it once and reuse it for both; every scenario below now expects
 * ->once() rather than the old ->twice().
 *
 * See PR #998 "Possible issues found": setNext()/setStart() used to
 * accept a plain \DateTime while getNext()/getStart() were declared to
 * return string|DateTimeImmutable(|null), which never included \DateTime
 * — so calling those getters after saveInvRecurring() had actually set a
 * date threw a TypeError. Fixed in the InvRecurring entity itself:
 * setStart()/setEnd()/setNext() still accept \DateTime (existing callers
 * are unchanged) but now normalize it to DateTimeImmutable before
 * storing, so the getters' own declared return type is never violated.
 * saveInvRecurringThrowsWhenInvoiceAlreadyHasARealNextDateTime below is
 * kept as a regression test proving the crash is gone (its own
 * #[ExpectException] was removed accordingly), and reflection on the
 * private properties is no longer needed for it, though the other tests
 * still use it purely to avoid depending on \DateTime's own
 * equality/formatting quirks.
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
        $e->once()->with(3)->andReturn(null);

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
        $e->once()->with(7)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        Assert::same($inv, $model->getInv());

        $next = $this->nextProperty($model);
        Assert::true($next instanceof \DateTimeImmutable);
        Assert::same('2026-02-01', $next->format('Y-m-d'));

        $start = $this->startProperty($model);
        Assert::true($start instanceof \DateTimeImmutable);
        Assert::same('2026-01-01', $start->format('Y-m-d'));
    }

    /**
     * The "currently running" branch (invoice already has a real `next`
     * date, e.g. from a previous restart-branch save) -- exercised here by
     * going through setNext() itself (matching a real prior save), not by
     * writing the private property directly via reflection, since it's
     * specifically the setter normalizing \DateTime -> DateTimeImmutable
     * that keeps saveInvRecurring()'s own $model->getNext() call (used to
     * decide which branch to take) from throwing a TypeError. Before the
     * InvRecurring entity fix, this exact scenario crashed inside
     * saveInvRecurring() itself, before its own branching logic ever ran.
     */
    public function saveInvRecurringRecomputesNextAndStartWhenInvoiceAlreadyHasARealNextDate(): void
    {
        $model = new InvRecurring();
        $model->setNext(new \DateTime('2025-06-01'));

        $array = [
            'inv_id' => 7,
            // incrementDateStringToDateTime() prepends its own 'P' --
            // matches saveInvRecurringRestartsAndSetsNextAndStartWhenNextWasEmpty's
            // own '1M' (not 'P1M'), the only other test that actually
            // reaches this call rather than just carrying an unused value.
            'frequency' => '1M',
            'start' => '2026-03-01',
        ];

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $invR */
        $invR = m::mock(IR::class);
        $e = $invR->shouldReceive('repoInvUnLoadedquery');
        $e->once()->with(7)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $this->makeService($repository, $invR)->saveInvRecurring($model, $array);

        $next = $this->nextProperty($model);
        Assert::true($next instanceof \DateTimeImmutable);
        Assert::same('2026-04-01', $next->format('Y-m-d'));

        $start = $this->startProperty($model);
        Assert::true($start instanceof \DateTimeImmutable);
        Assert::same('2026-03-01', $start->format('Y-m-d'));
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
        $e->once()->with(2)->andReturn($inv);

        /** @var InvRecurringRepository&m\MockInterface $repository */
        $repository = m::mock(InvRecurringRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $invR);
        $service->saveInvRecurring($model, $array);

        $end = $this->endProperty($model);
        Assert::true($end instanceof \DateTimeImmutable);
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
        $e->once()->with(2)->andReturn($inv);

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
