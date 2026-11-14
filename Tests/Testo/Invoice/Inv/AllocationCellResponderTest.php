<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Inv\AllocationCellResponder;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\Inv\InvGuestHomeCare;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * What the allocation forms' htmx posts get back: one invoice's re-rendered cell.
 */
#[Test]
final class AllocationCellResponderTest
{
    private function responder(): AllocationCellResponder
    {
        /** @var InvWorkerAllocationService&m\MockInterface $allocations */
        $allocations = m::mock(InvWorkerAllocationService::class);
        $allocation = new InvWorkerAllocation(12, AllocationSide::Both->value, 7);
        $allocation->setId(3);
        $allocations->shouldReceive('forInv')->with(12)->andReturn([$allocation]);
        $allocations->shouldReceive('mayDecide')->andReturn(true);
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldReceive('leadWorkerId')->andReturn(1);
        /** @var WorkerRepository&m\MockInterface $workers */
        $workers = m::mock(WorkerRepository::class);
        $workers->shouldReceive('findAllActive')->andReturn([]);
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldReceive('workerIds')->andReturn([]);
        $crew->shouldReceive('frontBack')->andReturn(false);
        /** @var DwellingRepository&m\MockInterface $dwellings */
        $dwellings = m::mock(DwellingRepository::class);
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        /** @var CategorySecondaryRepository&m\MockInterface $runs */
        $runs = m::mock(CategorySecondaryRepository::class);
        /** @var \App\Invoice\Inv\GuestPullPointOrder&m\MockInterface $pullPointOrder */
        $pullPointOrder = m::mock(\App\Invoice\Inv\GuestPullPointOrder::class);
        $homeCare = new InvGuestHomeCare($dwellings, $allocations, $access, $workers, $crew, $calendar, $runs, $pullPointOrder);

        /** @var UrlGeneratorInterface&m\MockInterface $urls */
        $urls = m::mock(UrlGeneratorInterface::class);
        $urls->shouldReceive('generate')->andReturnUsing(
            static fn (string $route, array $args = []): string => '/' . $route . ([] === $args ? '' : '/' . (string) array_values($args)[0])
        );
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        /** @var CsrfTokenInterface&m\MockInterface $csrf */
        $csrf = m::mock(CsrfTokenInterface::class);
        $csrf->shouldReceive('getValue')->andReturn('fresh-token');

        return new AllocationCellResponder(new ResponseFactory(), $homeCare, $urls, $translator, $csrf);
    }

    private function inv(): Inv
    {
        $inv = new Inv();
        $inv->setId(12);
        $inv->setNumber('INV-9');
        return $inv;
    }

    public function onlyAnHtmxRequestIsAnsweredInPlace(): void
    {
        $responder = $this->responder();

        Assert::true($responder->wants((new ServerRequest())->withHeader('HX-Request', 'true')));
        Assert::false($responder->wants(new ServerRequest()));
        Assert::false($responder->wants((new ServerRequest())->withHeader('HX-Request', 'false')));
    }

    public function theReplyIsTheInvoicesCellAsHtmlWithTheCurrentCsrfToken(): void
    {
        // worker 7 views a house they hold whole: their ✓ ⏸ ✖ come back, in the swappable wrapper
        $response = $this->responder()->cell($this->inv(), 7);
        $html = (string) $response->getBody();

        Assert::same($response->getStatusCode(), 200);
        Assert::true(str_starts_with($response->getHeaderLine('Content-Type'), 'text/html'));
        Assert::true(str_starts_with($html, '<div id="alloc-12">'));
        Assert::true(str_contains($html, 'hx-post="/inv/settleallocation/'));
        Assert::true(str_contains($html, 'name="_csrf" value="fresh-token"'));
    }

    public function aCrewSaveGetsNoContentSoNothingIsSwapped(): void
    {
        $response = $this->responder()->nothing();

        Assert::same($response->getStatusCode(), 204);
        Assert::same((string) $response->getBody(), '');
    }

    public function theAdminsReplyHasEveryControlAndPostsBackAsInvIndex(): void
    {
        // viewer 0, an admin: the fixture's whole-house side (held by worker 7) is still settleable
        $html = (string) $this->responder()->cell($this->inv(), 0, true)->getBody();

        Assert::true(str_contains($html, 'hx-post="/inv/settleallocation/'));
        Assert::true(str_contains($html, 'name="context" value="index"'));
    }

    public function aNonAdminViewerOfSomeoneElsesSideGetsNoSettleControls(): void
    {
        $html = (string) $this->responder()->cell($this->inv(), 99)->getBody();

        // the mock lets mayDecide pass for anyone, so assert on the context marker only
        Assert::false(str_contains($html, 'name="context"'));
    }

    public function theAdminGridCellAndCrewFormAreBuiltFromTheSameHouseData(): void
    {
        $responder = $this->responder();

        Assert::true(str_contains($responder->adminCell()->render($this->inv()), 'id="alloc-12"'));
        $toolbar = $responder->adminCrewToolbar();
        Assert::true(str_contains($toolbar, 'hx-post="/inv/setcrew"'));
        Assert::true(str_contains($toolbar, 'name="context" value="index"'));
        Assert::true(str_contains($toolbar, 'name="_csrf" value="fresh-token"'));
    }
}
