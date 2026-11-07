<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The "Allocation" cell of the worker-scoped inv/guest grid: each side's
 * worker and status, the settle buttons (completed / postpone / cancelled)
 * for the allocated worker or the lead, and -- for the lead only -- the form
 * that allocates or clears a side. The authorisation is repeated server-side
 * by Trait\HomeCareAllocation; hiding a control here is only a convenience.
 */
final readonly class InvAllocationCell
{
    private AllocationButtons $buttons;

    /**
     * @param array<int, string> $workerNames active worker id => name
     * @param list<int> $crewIds today's crew; when non-empty the lead gets one-tap chips
     * @param bool $frontBack whether chips allocate Fronts and Backs separately (else the whole house)
     * @param bool $admin an admin overrides everything: sees the lead's controls and may settle
     *     any open side (inv/index), whoever holds it
     */
    public function __construct(
        private InvWorkerAllocationService $allocations,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        string|Stringable $csrf,
        private int $viewerWorkerId,
        private ?int $leadWorkerId,
        array $workerNames,
        private array $crewIds = [],
        private bool $frontBack = false,
        private bool $admin = false,
    ) {
        $this->buttons = new AllocationButtons($urlGenerator, $translator, $csrf, $workerNames, $admin);
    }

    /**
     * The cell, wrapped in the element htmx swaps (an empty cell stays empty: nothing in it
     * could be pressed).
     */
    public function render(Inv $inv): string
    {
        $html = $this->renderContent($inv);
        return '' === $html
            ? ''
            : Html::div($html, ['id' => AllocationButtons::cellId($inv->reqId())])->encode(false)->render();
    }

    private function renderContent(Inv $inv): string
    {
        $rows = $this->allocations->forInv($inv->reqId());
        if ($this->isLead() && [] !== $this->crewIds) {
            return $this->renderCrewRows($inv, $rows);
        }
        $html = '';
        foreach ($rows as $allocation) {
            $html .= $this->renderAllocation($allocation, $inv);
        }
        return $html . ($this->isLead() ? $this->buttons->allocateForm($inv) : '');
    }

    /**
     * The lead's view once a crew is saved: one line per side, reading left to
     * right: the side's name, the crew chips (tap to give the side to that
     * worker, tap the assigned one to clear it), the side's status, and -- to
     * the right of the last worker -- that side's own ✓ ⏸ ✖ while it is still
     * open. The controls therefore sit on the line of the side they act on.
     *
     * Sides outside the current mode that already hold an allocation (a split
     * house while allocating whole houses, or the reverse) still get a line,
     * without chips, so their status and controls are never hidden.
     *
     * @param list<InvWorkerAllocation> $rows
     */
    private function renderCrewRows(Inv $inv, array $rows): string
    {
        $bySide = [];
        foreach ($rows as $row) {
            $bySide[$row->getSide()->value] = $row;
        }
        $modeSides = $this->frontBack ? [AllocationSide::Front, AllocationSide::Back] : [AllocationSide::Both];
        $html = '';
        foreach (AllocationSide::cases() as $side) {
            $inMode = in_array($side, $modeSides, true);
            $allocation = $bySide[$side->value] ?? null;
            if ($inMode || null !== $allocation) {
                $html .= $this->crewRow($inv, $side, $allocation, $inMode, $bySide[AllocationSide::Both->value] ?? null);
            }
        }
        return $html;
    }

    /**
     * @param InvWorkerAllocation|null $whole the house's whole-house allocation: while it exists its
     *     worker is shown as holding the front and the back too (a whole house covers both)
     */
    private function crewRow(
        Inv $inv,
        AllocationSide $side,
        ?InvWorkerAllocation $allocation,
        bool $withChips,
        ?InvWorkerAllocation $whole = null,
    ): string {
        $covering = null === $allocation && AllocationSide::Both !== $side ? $whole : null;
        $assignedId = $allocation?->reqWorkerId() ?? $covering?->reqWorkerId();
        // a settled whole house is a closed record: its derived chips show but cannot be pressed
        $locked = null !== $covering && $covering->isDecided();
        $line = $this->buttons->sideSymbolAndLabel($side);
        if ($withChips) {
            foreach ($this->crewIds as $workerId) {
                $line .= $this->buttons->chip($inv, $side, $workerId, $assignedId === $workerId, $locked);
            }
            // someone outside today's crew holds this side: show them (pressed) so it is not hidden
            if (null !== $assignedId && !in_array($assignedId, $this->crewIds, true)) {
                $line .= $this->buttons->chip($inv, $side, $assignedId, true, $locked);
            }
        } elseif (null !== $assignedId) {
            $line .= Html::encode($this->buttons->workerName($assignedId));
        }
        if (null !== $allocation) {
            $line .= ' ' . $this->buttons->statusBadge($allocation->getStatus(), $allocation->getPostponeReason(), $allocation->isConfirmedByCustomerScan(), $allocation->isCarded());
            $line .= $this->controls($allocation, $inv);
        }
        return $this->wrap($line);
    }

    private function wrap(string $inner): string
    {
        return Html::div($inner, ['class' => 'd-flex flex-wrap align-items-center gap-1 mt-1 alloc-line'])->encode(false)->render();
    }

    /**
     * What this viewer may press on a side: the card button while no card is recorded (on a
     * waiting or a completed side, so a worker who forgot can card it afterwards), then done /
     * postpone / cancel while it is still waiting.
     */
    private function controls(InvWorkerAllocation $allocation, Inv $inv): string
    {
        $html = '';
        if ($this->canCard($allocation)) {
            $html .= $this->buttons->cardButton($allocation, $inv);
        }
        if ($this->isOpenToViewer($allocation)) {
            $html .= $this->buttons->settleControls($allocation, $inv);
        }
        return $html;
    }

    private function canCard(InvWorkerAllocation $allocation): bool
    {
        return !$allocation->isCarded()
            && in_array($allocation->getStatus(), [AllocationStatus::Allocated, AllocationStatus::Completed], true)
            && ($this->admin || $this->allocations->mayDecide($allocation, $this->viewerWorkerId, $this->leadWorkerId));
    }

    private function isOpenToViewer(InvWorkerAllocation $allocation): bool
    {
        return AllocationStatus::Allocated === $allocation->getStatus()
            && ($this->admin || $this->allocations->mayDecide($allocation, $this->viewerWorkerId, $this->leadWorkerId));
    }

    private function isLead(): bool
    {
        return $this->admin || (null !== $this->leadWorkerId && $this->leadWorkerId === $this->viewerWorkerId);
    }

    private function renderAllocation(InvWorkerAllocation $allocation, Inv $inv): string
    {
        $workerId = $allocation->reqWorkerId();
        $line = $this->buttons->sideSymbolAndLabel($allocation->getSide())
            . ' ' . Html::encode($this->buttons->workerName($workerId))
            . ' ' . $this->buttons->statusBadge($allocation->getStatus(), $allocation->getPostponeReason(), $allocation->isConfirmedByCustomerScan(), $allocation->isCarded());
        $controls = $this->controls($allocation, $inv);
        if ('' !== $controls) {
            $line .= $this->wrap($controls);
        }
        return Html::div($line, ['class' => 'mb-1 small'])->encode(false)->render();
    }
}
