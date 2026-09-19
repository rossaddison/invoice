<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Controller;

use App\Bookkeeping\Application\BookkeepingExportSummary;
use App\Bookkeeping\Application\BookkeepingService;
use App\Bookkeeping\Controller\BookkeepingExportController;
use App\Invoice\Inv\InvLedgerSyncService;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

#[Test]
final class BookkeepingExportControllerTest
{
    private function makeController(BookkeepingExportSummary $summary, Flash $flash): BookkeepingExportController
    {
        /** @var BookkeepingService&m\MockInterface $service */
        $service = m::mock(BookkeepingService::class);
        $service->shouldReceive('exportDueTransactions')->once()->andReturn($summary);

        /** @var InvLedgerSyncService&m\MockInterface $sync */
        $sync = m::mock(InvLedgerSyncService::class);
        $sync->shouldReceive('sync')->once()->andReturn(3);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('done: %d exported, %d failed');

        /** @var WebControllerService&m\MockInterface $web */
        $web = m::mock(WebControllerService::class);
        $web->shouldReceive('getRedirectResponse')
            ->once()
            ->with('setting/tabIndex', [], ['active' => 'online-bookkeeping'])
            ->andReturn(new Psr7Response(302));

        return new BookkeepingExportController($service, $flash, $sync, $translator, $web);
    }

    public function syncsThenExportsAndFlashesAnInfoSummaryWhenNothingFailed(): void
    {
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('info', 'done: 2 exported, 0 failed', true);

        $response = $this->makeController(new BookkeepingExportSummary(2, 0, []), $flash)->export();

        Assert::same($response->getStatusCode(), 302);
    }

    public function flashesAWarningSummaryAndEachFailureMessageWhenSomethingFailed(): void
    {
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('warning', 'done: 1 exported, 1 failed', true);
        $flash->shouldReceive('add')->once()->with('warning', 'INV-9-invoice: boom', true);

        $this->makeController(new BookkeepingExportSummary(1, 1, ['INV-9-invoice: boom']), $flash)->export();
    }
}
