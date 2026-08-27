<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvMarkSentService;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Setting\SettingRepository as SR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvMarkSentService::markSentWithoutEmail() — the "mark as sent"
 * half of the HomeCare run-sheet Apply step (see that class's own docblock
 * and project_homecare_run_signoff_design memory). Exercises the guard that
 * skips an invoice already past draft or blocked by do_not_send, and that a
 * flip is both delegated to SettingRepository::invoiceMarkSent() and logged
 * via InvSentLogRepository — without hitting a real DB.
 */
#[Test]
final class InvMarkSentServiceTest
{
    private function draftInv(int $id, int $clientId, bool $doNotSend = false): Inv
    {
        $inv = new Inv();
        $inv->setId($id);
        $inv->setStatusId(1);
        $inv->setClientId($clientId);
        $inv->setDoNotSend($doNotSend);
        return $inv;
    }

    public function flipsAndLogsAnEligibleDraftInvoice(): void
    {
        $inv = $this->draftInv(311, 4);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        // repoInvLoadedquery is what markSentWithoutEmail() itself calls to
        // read status/do_not_send/client before delegating the flip;
        // SettingRepository::invoiceMarkSent() is mocked out below, so its
        // own internal repoInvUnLoadedquery() call is never reached here.
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(311)->andReturn($inv);

        /** @var ISLR&m\MockInterface $islR */
        $islR = m::mock(ISLR::class);
        $islR->shouldReceive('save')->once()->with(m::type(InvSentLog::class));

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldReceive('invoiceMarkSent')->once()->with(311, $iR);

        $service = new InvMarkSentService($iR, $islR, $sR);

        $flipped = $service->markSentWithoutEmail([311]);

        Assert::same([311], $flipped);
    }

    public function skipsAnInvoiceThatIsNotFoundAtAll(): void
    {
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(999)->andReturn(null);

        /** @var ISLR&m\MockInterface $islR */
        $islR = m::mock(ISLR::class);
        $islR->shouldNotReceive('save');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('invoiceMarkSent');

        $service = new InvMarkSentService($iR, $islR, $sR);

        Assert::same([], $service->markSentWithoutEmail([999]));
    }

    public function skipsAnInvoiceThatIsNotAtDraftStatus(): void
    {
        $inv = $this->draftInv(312, 4);
        $inv->setStatusId(2); // already sent

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(312)->andReturn($inv);

        /** @var ISLR&m\MockInterface $islR */
        $islR = m::mock(ISLR::class);
        $islR->shouldNotReceive('save');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('invoiceMarkSent');

        $service = new InvMarkSentService($iR, $islR, $sR);

        Assert::same([], $service->markSentWithoutEmail([312]));
    }

    public function skipsAnInvoiceThatBlocksSending(): void
    {
        $inv = $this->draftInv(313, 4, doNotSend: true);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(313)->andReturn($inv);

        /** @var ISLR&m\MockInterface $islR */
        $islR = m::mock(ISLR::class);
        $islR->shouldNotReceive('save');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('invoiceMarkSent');

        $service = new InvMarkSentService($iR, $islR, $sR);

        Assert::same([], $service->markSentWithoutEmail([313]));
    }

    public function processesEachInvoiceInTheListIndependently(): void
    {
        $eligible = $this->draftInv(1, 4);
        $blocked = $this->draftInv(2, 4, doNotSend: true);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(1)->andReturn($eligible);
        $iR->shouldReceive('repoInvLoadedquery')->once()->with(2)->andReturn($blocked);

        /** @var ISLR&m\MockInterface $islR */
        $islR = m::mock(ISLR::class);
        $islR->shouldReceive('save')->once()->with(m::type(InvSentLog::class));

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldReceive('invoiceMarkSent')->once()->with(1, $iR);

        $service = new InvMarkSentService($iR, $islR, $sR);

        Assert::same([1], $service->markSentWithoutEmail([1, 2]));
    }
}
