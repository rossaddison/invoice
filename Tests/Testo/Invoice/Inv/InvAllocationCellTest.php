<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Inv\Widget\InvAllocationCell;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * What the inv/guest "Allocation" cell shows each kind of viewer: the
 * allocated worker, another worker, and the lead. (Server-side authorisation
 * is covered by HomeCareAllocationActionsTest; this is only what is offered.)
 */
#[Test]
final class InvAllocationCellTest
{
    private const int INV = 12;
    private const int ME = 7;
    private const int LEAD = 1;

    /** @var array<int, string> */
    private const array NAMES = [1 => 'Lee Lead', 7 => 'Dave Smith', 8 => 'Sam <b>Jones</b>'];

    private function inv(): Inv
    {
        $inv = new Inv();
        $inv->setId(self::INV);
        $inv->setNumber('INV-9');
        return $inv;
    }

    private function allocation(int $id, AllocationSide $side, int $workerId, ?AllocationStatus $decided = null): InvWorkerAllocation
    {
        $allocation = new InvWorkerAllocation(self::INV, $side->value, $workerId);
        $allocation->setId($id);
        $decided !== null && $allocation->decide($decided, $workerId, new \DateTimeImmutable());
        return $allocation;
    }

    /**
     * @param list<InvWorkerAllocation> $allocations
     * @param list<int> $crew
     */
    private function cell(
        array $allocations,
        int $viewer,
        ?int $lead = self::LEAD,
        array $crew = [],
        bool $frontBack = false,
    ): InvAllocationCell {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->with(self::INV)->andReturn($allocations);

        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            static fn (string $name, array $args = []): string => '/' . $name . '/' . implode(
                '-',
                array_map(static fn (mixed $a): string => is_scalar($a) ? (string) $a : '', $args)
            )
        );

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        return new InvAllocationCell(
            new InvWorkerAllocationService($repository),
            $urlGenerator,
            $translator,
            'csrf-token',
            $viewer,
            $lead,
            self::NAMES,
            $crew,
            $frontBack,
        );
    }

    public function showsEachSidesWorkerAndStatusWithTheNameEscaped(): void
    {
        $html = $this->cell([
            $this->allocation(3, AllocationSide::Front, self::ME),
            $this->allocation(4, AllocationSide::Back, 8, AllocationStatus::Completed),
        ], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'allocation.side.front'));
        Assert::true(str_contains($html, 'Dave Smith'));
        Assert::true(str_contains($html, 'allocation.status.allocated'));
        Assert::true(str_contains($html, 'allocation.side.back'));
        Assert::true(str_contains($html, 'allocation.status.completed'));
        Assert::true(str_contains($html, 'Sam &lt;b&gt;Jones&lt;/b&gt;'));
        Assert::false(str_contains($html, '<b>Jones</b>'));
    }

    public function anAllocationToAnUnknownWorkerFallsBackToTheirId(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Both, 99)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, '#99'));
    }

    public function theAllocatedWorkerGetsSettleButtonsOnTheirOwnOpenSide(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'action="/inv/settleallocation/3"'));
        Assert::true(str_contains($html, 'name="status" value="completed"'));
        Assert::true(str_contains($html, 'name="status" value="postponed"'));
        Assert::true(str_contains($html, 'name="status" value="cancelled"'));
        Assert::true(str_contains($html, 'allocation.postpone.reason.gate_locked'));
        Assert::true(str_contains($html, 'name="_csrf" value="csrf-token"'));
        // the allocate form is the lead's only
        Assert::false(str_contains($html, 'inv/allocateside'));
    }

    public function theButtonsNameTheInvoiceForScreenReaders(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'aria-label="allocation.status.completed #INV-9"'));
    }

    public function anotherWorkerSeesSomeoneElsesSideButCannotSettleItOrAllocate(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, 8)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'allocation.status.allocated'));
        Assert::false(str_contains($html, 'inv/settleallocation'));
        Assert::false(str_contains($html, 'inv/allocateside'));
    }

    public function theLeadCanSettleAnotherWorkersSideAndSeesTheAllocateForm(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, 8)], self::LEAD)->render($this->inv());

        Assert::true(str_contains($html, 'action="/inv/settleallocation/3"'));
        Assert::true(str_contains($html, 'action="/inv/allocateside/12"'));
        Assert::true(str_contains($html, 'name="side"'));
        Assert::true(str_contains($html, 'name="worker_id"'));
        Assert::true(str_contains($html, 'option value="both"'));
        Assert::true(str_contains($html, 'option value="front"'));
        Assert::true(str_contains($html, 'option value="back"'));
        Assert::true(str_contains($html, 'option value>worker.unassigned'));
        Assert::true(str_contains($html, 'option value="8">Sam &lt;b&gt;Jones&lt;/b&gt;'));
    }

    public function aSettledSideOffersNoSettleButtonsEvenToTheLead(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, 8, AllocationStatus::Postponed)], self::LEAD)
            ->render($this->inv());

        Assert::true(str_contains($html, 'allocation.status.postponed'));
        Assert::false(str_contains($html, 'inv/settleallocation'));
        // ...but the lead can still re-allocate the side
        Assert::true(str_contains($html, 'inv/allocateside'));
    }

    public function withNoLeadConfiguredNobodyIsOfferedTheAllocateForm(): void
    {
        $html = $this->cell([], self::LEAD, null)->render($this->inv());

        Assert::same($html, '');
    }

    public function anUnallocatedHouseShowsOnlyTheAllocateFormToTheLead(): void
    {
        $forLead = $this->cell([], self::LEAD)->render($this->inv());
        $forWorker = $this->cell([], self::ME)->render($this->inv());

        Assert::true(str_contains($forLead, 'inv/allocateside'));
        Assert::same($forWorker, '');
    }

    public function withACrewTheLeadGetsOneTapChipsForTheWholeHouseInsteadOfTheDropdown(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [1, 7, 8])->render($this->inv());

        Assert::true(str_contains($html, 'action="/inv/allocateside/12"'));
        Assert::true(str_contains($html, 'name="side" value="both"'));
        foreach (['Lee Lead', 'Dave Smith', 'Sam &lt;b&gt;Jones&lt;/b&gt;'] as $name) {
            Assert::true(str_contains($html, '>' . $name . '</button>'));
        }
        Assert::true(str_contains($html, 'name="worker_id" value="7"'));
        // chips replace the dropdown form
        Assert::false(str_contains($html, '<select'));
        Assert::false(str_contains($html, 'name="side"><'));
        Assert::false(str_contains($html, 'name="side" value="front"'));
    }

    public function inFrontBackModeThereIsOneChipRowPerSide(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [1, 7], true)->render($this->inv());

        Assert::true(str_contains($html, 'name="side" value="front"'));
        Assert::true(str_contains($html, 'name="side" value="back"'));
        Assert::false(str_contains($html, 'name="side" value="both"'));
        Assert::same(substr_count($html, 'name="side" value="front"'), 2);
        Assert::same(substr_count($html, 'name="side" value="back"'), 2);
    }

    public function theAssignedChipIsPressedAndTappingItClearsTheSide(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME)],
            self::LEAD,
            self::LEAD,
            [1, 7],
            true,
        )->render($this->inv());

        // Dave (7) holds the front: his front chip is pressed and posts an empty worker_id to clear it
        Assert::true(str_contains($html, 'aria-pressed="true"'));
        Assert::true(str_contains($html, 'btn btn-sm btn-primary'));
        Assert::true(str_contains($html, 'name="worker_id" value>'));
        // the unassigned crew member's chip posts their own id
        Assert::true(str_contains($html, 'name="worker_id" value="1"'));
        // the back is unassigned, so no back chip is pressed
        Assert::same(substr_count($html, 'aria-pressed="true"'), 1);
    }

    public function aCrewMemberWhoIsNoLongerActiveShowsTheirIdOnTheirChip(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [1, 42])->render($this->inv());

        Assert::true(str_contains($html, '>#42</button>'));
    }

    public function anEmptyCrewFallsBackToTheAllocateDropdown(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [])->render($this->inv());

        Assert::true(str_contains($html, 'name="worker_id"'));
        Assert::true(str_contains($html, '<select'));
    }

    public function chipsAreOnlyForTheLead(): void
    {
        $html = $this->cell([], self::ME, self::LEAD, [1, 7])->render($this->inv());

        Assert::same($html, '');
    }

    public function acceptsTheViewRenderersCsrfObjectNotJustAString(): void
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturn('/inv/allocateside/12');
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->andReturn([]);

        // In the guest view $csrf is a Csrf object, not a string (Yiisoft\Yii\View\Renderer\Csrf)
        $cell = new InvAllocationCell(
            new InvWorkerAllocationService($repository),
            $urlGenerator,
            $translator,
            new Csrf('real-token', '_csrf', 'X-CSRF-Token'),
            self::LEAD,
            self::LEAD,
            self::NAMES,
        );

        Assert::true(str_contains($cell->render($this->inv()), 'name="_csrf" value="real-token"'));
    }
}
