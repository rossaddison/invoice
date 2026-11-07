<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
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
            Assert::true(str_contains($html, '○ ' . $name . '</button>'));
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
        Assert::true(str_contains($html, 'btn btn-sm alloc-btn btn-primary'));
        Assert::true(str_contains($html, 'name="worker_id" value>'));
        // the unassigned crew member's chip posts their own id
        Assert::true(str_contains($html, 'name="worker_id" value="1"'));
        // the back is unassigned, so no back chip is pressed
        Assert::same(substr_count($html, 'aria-pressed="true"'), 1);
    }

    public function aCrewMemberWhoIsNoLongerActiveShowsTheirIdOnTheirChip(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [1, 42])->render($this->inv());

        Assert::true(str_contains($html, '>○ #42</button>'));
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

    public function theSettleButtonsExplainThemselvesInPlainLanguage(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        foreach (['completed', 'postponed', 'cancelled'] as $status) {
            Assert::true(str_contains($html, 'title="allocation.tip.button.' . $status . '"'));
        }
        // side picture + status badge + done + postpone (on its wrapper) + cancel: each a Bootstrap
        // tooltip, which the page initialises on load
        Assert::same(substr_count($html, 'data-bs-toggle="tooltip"'), 5);
    }

    public function theStatusBadgeExplainsWhatTheStatusMeans(): void
    {
        $html = $this->cell([
            $this->allocation(3, AllocationSide::Front, 8, AllocationStatus::Completed),
            $this->allocation(4, AllocationSide::Back, 8, AllocationStatus::Postponed),
        ], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'title="allocation.status.completed: allocation.tip.status.completed"'));
        Assert::true(str_contains($html, 'title="allocation.status.postponed: allocation.tip.status.postponed"'));
    }

    public function anUnassignedChipSaysWhatTappingItGivesAndToWhom(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [1, 7])->render($this->inv());

        Assert::true(str_contains($html, 'title="allocation.tip.assign.both Lee Lead"'));
        Assert::true(str_contains($html, 'title="allocation.tip.assign.both Dave Smith"'));
    }

    public function anAssignedChipSaysTappingItAgainTakesItOff(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME)],
            self::LEAD,
            self::LEAD,
            [1, 7],
            true,
        )->render($this->inv());

        Assert::true(str_contains($html, 'title="Dave Smith allocation.tip.assigned"'));
        Assert::true(str_contains($html, 'title="allocation.tip.assign.front Lee Lead"'));
        Assert::true(str_contains($html, 'title="allocation.tip.assign.back Dave Smith"'));
    }

    public function aWorkerNameInATooltipIsEscaped(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [8])->render($this->inv());

        Assert::true(str_contains($html, 'title="allocation.tip.assign.both Sam &lt;b&gt;Jones&lt;/b&gt;"'));
        Assert::false(str_contains($html, 'title="allocation.tip.assign.both Sam <b>'));
    }

    public function theFallbackAllocateFormIsExplainedToo(): void
    {
        $html = $this->cell([], self::LEAD, self::LEAD, [])->render($this->inv());

        foreach (['side', 'worker', 'allocate'] as $tip) {
            Assert::true(str_contains($html, 'title="allocation.tip.' . $tip . '"'));
        }
    }

    public function noGreyIsUsedSoItReadsOnLightAndDarkPages(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME)],
            self::LEAD,
            self::LEAD,
            [1, 7],
            true,
        )->render($this->inv());

        // assigned = solid blue, unassigned = blue outline, "Allocated" = light blue with dark text
        Assert::true(str_contains($html, 'btn btn-sm alloc-btn btn-primary'));
        Assert::true(str_contains($html, 'btn btn-sm alloc-btn btn-outline-primary'));
        Assert::true(str_contains($html, 'border-dark bg-info text-dark'));
        Assert::false(str_contains($html, 'secondary'));
    }

    public function postponeIsOneDropdownLabelledWithThePauseSymbolNotASeparateSelect(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'data-bs-toggle="dropdown"'));
        Assert::true(str_contains($html, 'class="dropdown-menu"'));
        Assert::true(str_contains($html, '<i class="bi bi-pause-fill" aria-hidden="true"></i></button>'));
        Assert::true(str_contains($html, 'allocation.postpone.because'));
        Assert::false(str_contains($html, '<select'));
    }

    public function everyReasonIsAMenuItemThatPostponesWithThatReasonInOneStep(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        foreach (['gate_locked', 'no_access', 'customer_away', 'other'] as $reason) {
            Assert::true(str_contains($html, 'class="dropdown-item" name="reason" value="' . $reason . '"'));
            Assert::true(str_contains($html, '</i>allocation.postpone.reason.' . $reason . '</button>'));
        }
        Assert::same(substr_count($html, 'class="dropdown-item"'), 4);
        // the menu items submit the SAME form that fixes status to postponed
        Assert::true(str_contains($html, 'type="hidden" name="status" value="postponed"'));
    }

    public function theReasonBelongsToThePauseFormOnlyNotToDoneOrCancel(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        // three small forms, same action
        Assert::same(substr_count($html, 'action="/inv/settleallocation/3"'), 3);
        // the reason field is only ever on a dropdown item, never next to done/cancelled
        Assert::same(substr_count($html, 'name="reason"'), 4);
        $done = strpos($html, 'name="status" value="completed"');
        $pause = strpos($html, 'data-bs-toggle="dropdown"');
        $cancel = strpos($html, 'name="status" value="cancelled"');
        Assert::true($done !== false && $pause !== false && $cancel !== false);
        // reading order is done, pause, cancel
        Assert::true($done < $pause && $pause < $cancel);
    }

    public function theDropdownNeedsNoIdSoNothingIsDuplicatedAcrossRows(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        // the only id is the cell's own, unique per invoice: the dropdown adds none
        Assert::same(substr_count($html, ' id='), 1);
        Assert::true(str_contains($html, '<div id="alloc-12">'));
        Assert::false(str_contains($html, 'aria-labelledby'));
    }

    public function thePauseToggleNamesTheInvoiceForScreenReaders(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        Assert::true(str_contains($html, 'aria-label="allocation.status.postponed #INV-9"'));
        Assert::true(str_contains($html, 'aria-expanded="false"'));
    }

    /**
     * The html of one crew line: from this side's label up to the next side's label (or the end).
     */
    private function line(string $html, string $side): string
    {
        $start = strpos($html, '<span class="visually-hidden">allocation.side.' . $side . '</span>');
        if (false === $start) {
            throw new \LogicException('No line for side ' . $side);
        }
        $next = strpos($html, '<span class="visually-hidden">allocation.side.', $start + 10);
        return substr($html, $start, false === $next ? null : $next - $start);
    }

    public function eachSidesControlsSitOnItsOwnLineToTheRightOfTheLastWorker(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME), $this->allocation(4, AllocationSide::Back, 8)],
            self::LEAD,
            self::LEAD,
            [1, 7, 8],
            true,
        )->render($this->inv());

        $fronts = $this->line($html, 'front');
        $backs = $this->line($html, 'back');

        // the Fronts line holds the front's controls (allocation 3), the Backs line the back's (allocation 4)
        Assert::true(str_contains($fronts, 'settleallocation/3'));
        Assert::false(str_contains($fronts, 'settleallocation/4'));
        Assert::true(str_contains($backs, 'settleallocation/4'));
        Assert::false(str_contains($backs, 'settleallocation/3'));
        // ... and each comes AFTER that line's last worker chip (the third crew member)
        Assert::true(strrpos($fronts, 'allocation.side.front Sam') < strpos($fronts, 'settleallocation/3'));
        Assert::true(strrpos($backs, 'allocation.side.back Sam') < strpos($backs, 'settleallocation/4'));
    }

    public function theStatusBadgeAlsoSitsOnTheSidesLine(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME, AllocationStatus::Completed)],
            self::LEAD,
            self::LEAD,
            [1, 7],
            true,
        )->render($this->inv());

        Assert::true(str_contains($this->line($html, 'front'), 'allocation.status.completed'));
        // a completed side has a badge but no controls; the unallocated back has neither
        Assert::false(str_contains($this->line($html, 'front'), 'settleallocation'));
        Assert::false(str_contains($this->line($html, 'back'), 'allocation.status'));
        Assert::false(str_contains($this->line($html, 'back'), 'settleallocation'));
    }

    public function withACrewTheOldSeparateNameAndStatusLinesAboveAreGone(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        Assert::false(str_contains($html, 'class="mb-1 small"'));
        Assert::same(substr_count($html, 'badge '), 1);
    }

    public function aSplitHouseWhileAllocatingWholeHousesKeepsItsSidesLinesWithoutChips(): void
    {
        $html = $this->cell(
            [$this->allocation(3, AllocationSide::Front, self::ME), $this->allocation(4, AllocationSide::Back, 8)],
            self::LEAD,
            self::LEAD,
            [1, 7],
            false,
        )->render($this->inv());

        // the whole-house line has chips; the existing Front and Back lines do not
        Assert::true(str_contains($this->line($html, 'both'), 'name="side" value="both"'));
        Assert::false(str_contains($html, 'name="side" value="front"'));
        Assert::false(str_contains($html, 'name="side" value="back"'));
        // but their holders, statuses and controls are still there
        Assert::true(str_contains($this->line($html, 'front'), 'Dave Smith'));
        Assert::true(str_contains($this->line($html, 'front'), 'settleallocation/3'));
        Assert::true(str_contains($this->line($html, 'back'), 'Sam &lt;b&gt;Jones&lt;/b&gt;'));
        Assert::true(str_contains($this->line($html, 'back'), 'settleallocation/4'));
    }

    public function aWholeHouseWhileAllocatingFrontsAndBacksKeepsItsOwnLine(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Both, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        Assert::true(str_contains($this->line($html, 'both'), 'settleallocation/3'));
        Assert::false(str_contains($this->line($html, 'both'), 'name="side" value="both"'));
        Assert::true(str_contains($this->line($html, 'front'), 'name="side" value="front"'));
    }

    public function someoneOutsideTodaysCrewWhoHoldsASideIsShownPressedSoItIsNotHidden(): void
    {
        // worker 8 is not in the crew [1, 7] but holds the whole house
        $html = $this->cell([$this->allocation(3, AllocationSide::Both, 8)], self::LEAD, self::LEAD, [1, 7])
            ->render($this->inv());

        Assert::same(substr_count($html, 'aria-pressed="true"'), 1);
        Assert::true(str_contains($html, '>● Sam &lt;b&gt;Jones&lt;/b&gt;</button>'));
        // tapping that pressed chip clears the side
        Assert::true(str_contains($html, 'name="worker_id" value>'));
    }

    public function ordinaryWorkersAndALeadWithNoCrewKeepTheOlderLayout(): void
    {
        $worker = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());
        $leadNoCrew = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [])
            ->render($this->inv());

        Assert::true(str_contains($worker, 'class="mb-1 small"'));
        Assert::true(str_contains($leadNoCrew, 'class="mb-1 small"'));
        Assert::true(str_contains($leadNoCrew, '<select'));
    }

    public function everyButtonSharesTheBlackEdgedEqualHeightClassSoNoneDependsOnColour(): void
    {
        $crew = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());
        $fallback = $this->cell([], self::LEAD)->render($this->inv());

        // front line: 1 pressed + 1 free chip, back line: 2 free chips, plus done / pause / cancel
        Assert::same(substr_count($crew, 'alloc-btn'), 7);
        // the fallback form: both selects and the save button too
        Assert::same(substr_count($fallback, 'alloc-btn'), 3);
        // badges get a black edge as well
        Assert::true(str_contains($crew, 'border border-2 border-dark'));
    }

    public function symbolsCarryTheMeaningSoWordsAreNotNeeded(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        // side pictures (hidden from screen readers, which read the word), status picture, reason pictures
        Assert::true(str_contains($html, '<i class="bi bi-door-closed-fill" aria-hidden="true"></i><span class="visually-hidden">allocation.side.front</span>'));
        Assert::true(str_contains($html, '<i class="bi bi-tree-fill" aria-hidden="true"></i><span class="visually-hidden">allocation.side.back</span>'));
        Assert::true(str_contains($html, '<i class="bi bi-hourglass-split" aria-hidden="true"></i><span class="visually-hidden">allocation.status.allocated</span>'));
        foreach (['lock-fill', 'shield-fill-x', 'airplane-fill', 'question-circle-fill'] as $icon) {
            Assert::true(str_contains($html, '<i class="bi bi-' . $icon . ' me-2" aria-hidden="true"></i>allocation.postpone.reason.'));
        }
        // assigned vs free differs by shape (● / ○) as well as by colour
        Assert::true(str_contains($html, '>● Dave Smith</button>'));
        Assert::true(str_contains($html, '>○ Lee Lead</button>'));
    }

    public function lineAndCancelCarryTheHooksTheTouchScreenCssUsesForSpacing(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        // one spaced line per side (front and back) ...
        Assert::same(substr_count($html, 'alloc-line'), 2);
        // ... and only the cancel form is set apart from done / postpone
        Assert::same(substr_count($html, 'alloc-cancel'), 1);
        Assert::true(strpos($html, 'alloc-cancel') > strpos($html, 'data-bs-toggle="dropdown"'));
    }

    public function theStatusIconsAreNeverTheSameAsTheActionIcons(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::ME)->render($this->inv());

        // the status (an hourglass) differs from the buttons (tick, pause, cross), so
        // "this is done" is never mistaken for "mark it done"
        Assert::true(str_contains($html, 'bi-hourglass-split'));
        foreach (['bi-check-lg', 'bi-pause-fill', 'bi-x-lg'] as $action) {
            Assert::true(str_contains($html, $action));
        }
        foreach (AllocationStatus::cases() as $status) {
            Assert::false(in_array($status->icon(), ['bi-check-lg', 'bi-pause-fill', 'bi-x-lg'], true));
        }
    }

    public function aPostponedSidePillAlsoShowsTheReasonIcon(): void
    {
        $allocation = $this->allocation(3, AllocationSide::Front, self::ME);
        $allocation->decide(AllocationStatus::Postponed, self::ME, new \DateTimeImmutable(), PostponeReason::GateLocked);
        $html = $this->cell([$allocation], self::LEAD, self::LEAD, [1, 7], true)->render($this->inv());

        Assert::true(str_contains($html, 'bi-clock-fill'));
        Assert::true(str_contains($html, 'bi bi-lock-fill ms-1'));
        Assert::true(str_contains($html, 'title="allocation.status.postponed: allocation.tip.status.postponed (allocation.postpone.reason.gate_locked)"'));
    }

    public function aWholeHouseHolderIsShownPressedOnBothTheFrontAndTheBack(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Both, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        // Dave (7) holds the whole house, so his front and back chips both show ● and are pressed
        Assert::same(substr_count($html, '>● Dave Smith</button>'), 2);
        Assert::same(substr_count($html, '>○ Lee Lead</button>'), 2);
        Assert::same(substr_count($html, 'aria-pressed="true"'), 2);
        // while the whole house is open its chips are live: tapping one posts the clear that splits it
        Assert::false(str_contains($html, ' disabled'));
        Assert::true(str_contains($this->line($html, 'front'), 'name="worker_id" value>'));
    }

    public function aSettledWholeHousesFrontAndBackChipsShowTheMarkButCannotBePressed(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Both, self::ME, AllocationStatus::Completed)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        Assert::same(substr_count($html, '>● Dave Smith</button>'), 2);
        // every front/back chip (2 sides x 2 crew) is disabled
        Assert::same(substr_count($html, ' disabled'), 4);
    }

    public function aSplitSideKeepsItsOwnWorkerEvenWithoutAWholeHouse(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        Assert::same(substr_count($html, '>● Dave Smith</button>'), 1);
        Assert::false(str_contains($html, ' disabled'));
    }

    public function everyFormPostsInPlaceWithHtmxIntoItsOwnInvoicesCell(): void
    {
        $html = $this->cell([$this->allocation(3, AllocationSide::Front, self::ME)], self::LEAD, self::LEAD, [1, 7], true)
            ->render($this->inv());

        // 4 chips + 3 settle forms: every one an htmx post that swaps this invoice's cell
        Assert::same(substr_count($html, '<form '), 7);
        Assert::same(substr_count($html, 'hx-target="#alloc-12"'), 7);
        Assert::same(substr_count($html, 'hx-swap="outerHTML"'), 7);
        Assert::true(str_contains($html, 'hx-post="/inv/allocateside/12"'));
        Assert::true(str_contains($html, 'hx-post="/inv/settleallocation/3"'));
        // and each stays an ordinary POST form for when htmx is not there
        Assert::same(substr_count($html, 'action="'), 7);
    }

    public function theFallbackAllocateFormAlsoPostsInPlace(): void
    {
        $html = $this->cell([], self::LEAD)->render($this->inv());

        Assert::true(str_contains($html, 'hx-post="/inv/allocateside/12"'));
        Assert::true(str_contains($html, 'hx-target="#alloc-12"'));
    }

    public function anEmptyCellIsStillEmptyNotAnEmptyWrapper(): void
    {
        Assert::same($this->cell([], self::ME)->render($this->inv()), '');
    }
}
