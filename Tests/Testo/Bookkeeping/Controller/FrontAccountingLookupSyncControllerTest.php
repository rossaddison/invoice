<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Controller;

use App\Bookkeeping\Controller\FrontAccountingLookupSyncController;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupSyncService;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupSyncSummary;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

#[Test]
final class FrontAccountingLookupSyncControllerTest
{
    private function makeController(FrontAccountingLookupSyncSummary $summary, Flash $flash): FrontAccountingLookupSyncController
    {
        /** @var FrontAccountingLookupSyncService&m\MockInterface $service */
        $service = m::mock(FrontAccountingLookupSyncService::class);
        $service->shouldReceive('sync')->once()->andReturn($summary);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturn('synced: %d entries');

        /** @var WebControllerService&m\MockInterface $web */
        $web = m::mock(WebControllerService::class);
        $web->shouldReceive('getRedirectResponse')
            ->once()
            ->with('setting/tabIndex', [], ['active' => 'online-bookkeeping'])
            ->andReturn(new Psr7Response(302));

        return new FrontAccountingLookupSyncController($service, $flash, $translator, $web);
    }

    public function flashesAnInfoSummaryWhenNothingFailed(): void
    {
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('info', 'synced: 7 entries', true);

        $response = $this->makeController(new FrontAccountingLookupSyncSummary(7, []), $flash)->sync();

        Assert::same($response->getStatusCode(), 302);
    }

    public function flashesAWarningSummaryAndEachFailureMessageWhenSomethingFailed(): void
    {
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once()->with('warning', 'synced: 3 entries', true);
        $flash->shouldReceive('add')->once()->with('warning', 'tax_group: boom', true);

        $this->makeController(new FrontAccountingLookupSyncSummary(3, ['tax_group: boom']), $flash)->sync();
    }
}
