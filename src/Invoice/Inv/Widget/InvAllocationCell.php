<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Option;
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
    /**
     * @param array<int, string> $workerNames active worker id => name
     * @param list<int> $crewIds today's crew; when non-empty the lead gets one-tap chips
     * @param bool $frontBack whether chips allocate Fronts and Backs separately (else the whole house)
     */
    public function __construct(
        private InvWorkerAllocationService $allocations,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string|Stringable $csrf,
        private int $viewerWorkerId,
        private ?int $leadWorkerId,
        private array $workerNames,
        private array $crewIds = [],
        private bool $frontBack = false,
    ) {
    }

    public function render(Inv $inv): string
    {
        $html = '';
        $rows = $this->allocations->forInv($inv->reqId());
        foreach ($rows as $allocation) {
            $html .= $this->renderAllocation($allocation, $inv);
        }
        if (!$this->isLead()) {
            return $html;
        }
        return $html . ([] !== $this->crewIds ? $this->renderChips($inv, $rows) : $this->renderAllocateForm($inv));
    }

    /**
     * One row of crew chips per side being allocated (the whole house, or
     * Fronts and Backs). Tapping an unassigned chip gives the side to that
     * worker; tapping the assigned one clears it.
     *
     * @param list<InvWorkerAllocation> $rows
     */
    private function renderChips(Inv $inv, array $rows): string
    {
        $html = '';
        foreach ($this->frontBack ? [AllocationSide::Front, AllocationSide::Back] : [AllocationSide::Both] as $side) {
            $assigned = null;
            foreach ($rows as $row) {
                $row->getSide() === $side && $assigned = $row->reqWorkerId();
            }
            $chips = '';
            foreach ($this->crewIds as $workerId) {
                $chips .= $this->chip($inv, $side, $workerId, $assigned === $workerId);
            }
            $html .= Html::div(
                Html::span($this->translator->translate('allocation.side.' . $side->value), ['class' => 'fw-semibold me-1'])->render() . $chips,
                ['class' => 'd-flex flex-wrap align-items-center gap-1 mt-1']
            )->encode(false)->render();
        }
        return Html::div($html, ['class' => 'border-top pt-1 mt-1'])->encode(false)->render();
    }

    private function chip(Inv $inv, AllocationSide $side, int $workerId, bool $assigned): string
    {
        $name = $this->workerNames[$workerId] ?? ('#' . $workerId);
        $hidden = Html::hiddenInput('side', $side->value)->render()
            . Html::hiddenInput('worker_id', $assigned ? '' : (string) $workerId)->render();
        $button = Html::tag('button', $name, [
            'type' => 'submit',
            'class' => 'btn btn-sm ' . ($assigned ? 'btn-primary' : 'btn-outline-secondary'),
            'aria-pressed' => $assigned ? 'true' : 'false',
            'aria-label' => $this->translator->translate('allocation.side.' . $side->value) . ' ' . $name . ' #' . ($inv->getNumber() ?? ''),
        ])->render();
        return $this->form(
            $this->urlGenerator->generate('inv/allocateside', ['inv_id' => $inv->reqId()]),
            $hidden . $button,
            'd-inline'
        );
    }

    private function isLead(): bool
    {
        return null !== $this->leadWorkerId && $this->leadWorkerId === $this->viewerWorkerId;
    }

    private function renderAllocation(InvWorkerAllocation $allocation, Inv $inv): string
    {
        $workerId = $allocation->reqWorkerId();
        $status = $allocation->getStatus();
        $line = Html::span(
            $this->translator->translate('allocation.side.' . $allocation->getSide()->value),
            ['class' => 'fw-semibold']
        )->render()
            . ' ' . Html::encode($this->workerNames[$workerId] ?? ('#' . $workerId))
            . ' ' . Html::span(
                $this->translator->translate('allocation.status.' . $status->value),
                ['class' => 'badge ' . $this->badgeClass($status)]
            )->render();
        if (AllocationStatus::Allocated === $status
            && $this->allocations->mayDecide($allocation, $this->viewerWorkerId, $this->leadWorkerId)
        ) {
            $line .= $this->renderSettleForm($allocation, $inv);
        }
        return Html::div($line, ['class' => 'mb-1 small'])->encode(false)->render();
    }

    private function badgeClass(AllocationStatus $status): string
    {
        return match ($status) {
            AllocationStatus::Allocated => 'bg-secondary',
            AllocationStatus::Completed => 'bg-success',
            AllocationStatus::Postponed => 'bg-warning text-dark',
            AllocationStatus::Cancelled => 'bg-danger',
        };
    }

    private function renderSettleForm(InvWorkerAllocation $allocation, Inv $inv): string
    {
        $t = $this->translator;
        $for = ' #' . ($inv->getNumber() ?? '');
        $reasons = '';
        foreach (PostponeReason::cases() as $reason) {
            $reasons .= new Option()->value($reason->value)
                ->content($t->translate('allocation.postpone.reason.' . $reason->value))
                ->render();
        }
        $inner = $this->decisionButton(AllocationStatus::Completed, '✓', 'btn-success', $for)
            . Html::openTag('select', [
                'name' => 'reason',
                'class' => 'form-select form-select-sm w-auto',
                'aria-label' => $t->translate('allocation.postpone.reason') . $for,
            ]) . $reasons . Html::closeTag('select')
            . $this->decisionButton(AllocationStatus::Postponed, '⏸', 'btn-warning', $for)
            . $this->decisionButton(AllocationStatus::Cancelled, '✖', 'btn-danger', $for);
        return $this->form(
            $this->urlGenerator->generate('inv/settleallocation', ['allocation_id' => $allocation->reqId()]),
            $inner,
            'd-flex flex-wrap gap-1 mt-1'
        );
    }

    private function decisionButton(AllocationStatus $status, string $icon, string $class, string $for): string
    {
        $label = $this->translator->translate('allocation.status.' . $status->value);
        return Html::tag('button', $icon, [
            'type' => 'submit',
            'name' => 'status',
            'value' => $status->value,
            'class' => 'btn btn-sm ' . $class,
            'title' => $label,
            'aria-label' => $label . $for,
        ])->render();
    }

    private function renderAllocateForm(Inv $inv): string
    {
        $t = $this->translator;
        $for = ' #' . ($inv->getNumber() ?? '');
        $sides = '';
        foreach (AllocationSide::cases() as $side) {
            $sides .= new Option()->value($side->value)
                ->content($t->translate('allocation.side.' . $side->value))
                ->render();
        }
        $workers = new Option()->value('')->content($t->translate('worker.unassigned'))->render();
        foreach ($this->workerNames as $workerId => $name) {
            $workers .= new Option()->value((string) $workerId)->content($name)->render();
        }
        $inner = Html::openTag('select', [
            'name' => 'side',
            'class' => 'form-select form-select-sm w-auto',
            'aria-label' => $t->translate('allocation.side') . $for,
        ]) . $sides . Html::closeTag('select')
            . Html::openTag('select', [
                'name' => 'worker_id',
                'class' => 'form-select form-select-sm w-auto',
                'aria-label' => $t->translate('worker.assign') . $for,
            ]) . $workers . Html::closeTag('select')
            . Html::tag('button', '💾', [
                'type' => 'submit',
                'class' => 'btn btn-outline-primary btn-sm',
                'title' => $t->translate('worker.assign'),
            ])->render();
        return $this->form(
            $this->urlGenerator->generate('inv/allocateside', ['inv_id' => $inv->reqId()]),
            $inner,
            'd-flex flex-wrap gap-1 mt-2 border-top pt-1'
        );
    }

    private function form(string $action, string $inner, string $class): string
    {
        return new Form()
            ->post($action)
            ->csrf($this->csrf)
            ->addAttributes(['class' => $class])
            ->content($inner)
            ->encode(false)
            ->render();
    }
}
