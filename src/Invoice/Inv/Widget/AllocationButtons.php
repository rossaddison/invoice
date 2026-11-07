<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Every allocation button in one place: the crew chips, the settle controls
 * (✓ done, ⏸ postpone dropdown, ✖ cancel), the status badge and the
 * side/worker allocate form. Each is a small, self-contained form posting to
 * inv/allocateside or inv/settleallocation, with a Bootstrap tooltip and an
 * accessible name, so any grid (the worker's inv/guest today, the admin's
 * inv/index next) can reuse them. It only renders: who may see or press
 * what is decided by the caller, and repeated server-side by the controller.
 */
final readonly class AllocationButtons
{
    private const string CLASS_ATTR = 'class';
    private const string ARIA_LABEL = 'aria-label';
    private const string BUTTON = 'button';
    private const string SUBMIT = 'submit';
    private const string INLINE = 'd-inline';
    private const string SELECT = 'select';
    private const string SELECT_CLASS = 'form-select form-select-sm w-auto alloc-btn';
    /** Black-edged, equal-height, colour-independent buttons (see rebuild/css/buttons-toolbar.css). */
    private const string BTN = 'btn btn-sm alloc-btn ';
    private const string SIDE_KEY = 'allocation.side.';
    private const string STATUS_KEY = 'allocation.status.';
    private const string TIP_BUTTON_KEY = 'allocation.tip.button.';

    /**
     * @param array<int, string> $workerNames active worker id => name
     * @param bool $admin the admin's inv/index grid: every form says so (context=index), so the
     *     server replies with, and returns to, the admin's page rather than the worker's
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string|Stringable $csrf,
        private array $workerNames,
        private bool $admin = false,
    ) {
    }

    /**
     * The element id of one invoice's allocation cell: the target htmx swaps.
     */
    public static function cellId(int $invId): string
    {
        return 'alloc-' . $invId;
    }

    public function workerName(int $workerId): string
    {
        return $this->workerNames[$workerId] ?? ('#' . $workerId);
    }

    public function sideLabel(AllocationSide $side): string
    {
        return $this->translator->translate(self::SIDE_KEY . $side->value);
    }

    /**
     * Only the side's picture shows (door, tree, house); its word is in the
     * tooltip and, hidden, for screen readers.
     */
    public function sideSymbolAndLabel(AllocationSide $side): string
    {
        $label = $this->sideLabel($side);
        return $this->picture($this->glyph($side->icon()), $label, $label, 'alloc-icon me-1');
    }

    /**
     * One crew member as a tap target: gives the side to that worker, or --
     * when already assigned -- clears it. A locked chip (the side belongs to a
     * settled whole house) shows its mark but cannot be pressed.
     */
    public function chip(Inv $inv, AllocationSide $side, int $workerId, bool $assigned, bool $locked = false): string
    {
        $name = $this->workerName($workerId);
        $hidden = Html::hiddenInput('side', $side->value)->render()
            . Html::hiddenInput('worker_id', $assigned ? '' : (string) $workerId)->render();
        // Assigned: say what tapping does (clears it). Unassigned: say what it gives, to whom.
        $tip = $assigned
            ? $name . ' ' . $this->translator->translate('allocation.tip.assigned')
            : $this->translator->translate('allocation.tip.assign.' . $side->value) . ' ' . $name;
        // the ● / ○ marks assigned versus free by shape, not only by colour
        $button = Html::tag(self::BUTTON, ($assigned ? '● ' : '○ ') . $name, [
            'type' => self::SUBMIT,
            self::CLASS_ATTR => self::BTN . ($assigned ? 'btn-primary alloc-on' : 'btn-outline-primary'),
            'aria-pressed' => $assigned ? 'true' : 'false',
            self::ARIA_LABEL => $this->sideLabel($side) . ' ' . $name . $this->numberSuffix($inv),
            'disabled' => $locked,
        ] + $this->tip($tip))->render();
        return $this->form(
            $this->urlGenerator->generate('inv/allocateside', ['inv_id' => $inv->reqId()]),
            $hidden . $button,
            self::INLINE,
            $inv
        );
    }

    /**
     * Only the status picture shows, in a coloured black-edged pill the same
     * height as the buttons; the word and its explanation are the tooltip.
     */
    public function statusBadge(AllocationStatus $status, ?PostponeReason $reason = null): string
    {
        $word = $this->translator->translate(self::STATUS_KEY . $status->value);
        $glyph = $this->glyph($status->icon());
        $tip = $word . ': ' . $this->translator->translate('allocation.tip.status.' . $status->value);
        if (null !== $reason) {
            // a postponed side also shows WHY (lock, shield, plane, ?) beside the clock
            $why = $this->translator->translate('allocation.postpone.reason.' . $reason->value);
            $glyph .= $this->glyph($reason->icon(), 'ms-1');
            $word .= ' ' . $why;
            $tip .= ' (' . $why . ')';
        }
        return $this->picture($glyph, $word, $tip, 'badge alloc-status border border-2 border-dark ' . $this->badgeClass($status));
    }

    /**
     * Tick (done), pause (a dropdown of postpone reasons: picking one postpones the
     * side with that reason in a single step) and ✖ (cancel). Each is its own
     * small form posting to the same action, so the order reads tick, pause, cross and the
     * reason belongs visibly to ⏸ only -- a cancel or a completion records no
     * reason.
     */
    public function settleControls(InvWorkerAllocation $allocation, Inv $inv): string
    {
        $for = $this->numberSuffix($inv);
        $action = $this->urlGenerator->generate('inv/settleallocation', ['allocation_id' => $allocation->reqId()]);
        return $this->form($action, $this->decisionButton(AllocationStatus::Completed, 'bi-check-lg', 'btn-success', $for), self::INLINE, $inv)
            . $this->form(
                $action,
                Html::hiddenInput('status', AllocationStatus::Postponed->value)->render() . $this->postponeDropdown($for),
                self::INLINE,
                $inv
            )
            . $this->form($action, $this->decisionButton(AllocationStatus::Cancelled, 'bi-x-lg', 'btn-danger', $for), self::INLINE . ' alloc-cancel', $inv);
    }

    /**
     * The side + worker selects and save button, for a lead with no saved crew.
     */
    public function allocateForm(Inv $inv): string
    {
        $t = $this->translator;
        $for = $this->numberSuffix($inv);
        $sides = '';
        foreach (AllocationSide::cases() as $side) {
            $sides .= new Option()->value($side->value)->content($this->sideLabel($side))->render();
        }
        $workers = new Option()->value('')->content($t->translate('worker.unassigned'))->render();
        foreach ($this->workerNames as $workerId => $name) {
            $workers .= new Option()->value((string) $workerId)->content($name)->render();
        }
        $inner = $this->select('side', $t->translate('allocation.side') . $for, $t->translate('allocation.tip.side'), $sides)
            . $this->select('worker_id', $t->translate('worker.assign') . $for, $t->translate('allocation.tip.worker'), $workers)
            . Html::tag(self::BUTTON, $this->glyph('bi-floppy-fill'), [
                'type' => self::SUBMIT,
                self::CLASS_ATTR => self::BTN . 'btn-outline-primary',
                self::ARIA_LABEL => $t->translate('worker.assign') . $for,
            ] + $this->tip($t->translate('allocation.tip.allocate')))->encode(false)->render();
        return $this->form(
            $this->urlGenerator->generate('inv/allocateside', ['inv_id' => $inv->reqId()]),
            $inner,
            'd-flex flex-wrap gap-1 mt-2 border-top pt-1',
            $inv
        );
    }

    private function select(string $name, string $label, string $tip, string $options): string
    {
        return Html::openTag(self::SELECT, [
            'name' => $name,
            self::CLASS_ATTR => self::SELECT_CLASS,
            self::ARIA_LABEL => $label,
        ] + $this->tip($tip)) . $options . Html::closeTag(self::SELECT);
    }

    /**
     * A Bootstrap dropdown labelled with the pause icon: its menu lists the postpone reasons and
     * each item submits the surrounding form (status=postponed is a hidden
     * field) with that reason. No ids are needed. The tooltip sits on a
     * wrapper so it does not fight the dropdown toggle.
     */
    private function postponeDropdown(string $for): string
    {
        $t = $this->translator;
        $menu = '<li>'
            . Html::tag('h6', $t->translate('allocation.postpone.because'), [self::CLASS_ATTR => 'dropdown-header'])->render()
            . '</li>';
        foreach (PostponeReason::cases() as $reason) {
            $menu .= '<li>' . Html::tag(
                self::BUTTON,
                $this->glyph($reason->icon(), 'me-2') . Html::encode($t->translate('allocation.postpone.reason.' . $reason->value)),
                [
                    'type' => self::SUBMIT,
                    self::CLASS_ATTR => 'dropdown-item',
                    'name' => 'reason',
                    'value' => $reason->value,
                ]
            )->encode(false)->render() . '</li>';
        }
        $toggle = Html::tag(self::BUTTON, $this->glyph('bi-pause-fill'), [
            'type' => self::BUTTON,
            self::CLASS_ATTR => self::BTN . 'btn-warning dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'aria-expanded' => 'false',
            self::ARIA_LABEL => $t->translate(self::STATUS_KEY . AllocationStatus::Postponed->value) . $for,
        ])->encode(false)->render();
        $dropdown = Html::openTag('div', [self::CLASS_ATTR => 'dropdown'])
            . $toggle
            . Html::openTag('ul', [self::CLASS_ATTR => 'dropdown-menu']) . $menu . Html::closeTag('ul')
            . Html::closeTag('div');
        return Html::span($dropdown, $this->tip($t->translate(self::TIP_BUTTON_KEY . AllocationStatus::Postponed->value)))
            ->encode(false)
            ->render();
    }

    private function decisionButton(AllocationStatus $status, string $icon, string $class, string $for): string
    {
        return Html::tag(self::BUTTON, $this->glyph($icon), [
            'type' => self::SUBMIT,
            'name' => 'status',
            'value' => $status->value,
            self::CLASS_ATTR => self::BTN . $class,
            self::ARIA_LABEL => $this->translator->translate(self::STATUS_KEY . $status->value) . $for,
        ] + $this->tip($this->translator->translate(self::TIP_BUTTON_KEY . $status->value)))->encode(false)->render();
    }

    /**
     * A Bootstrap Icons glyph: scalable, one colour, the same on every device
     * (unlike emoji), and hidden from screen readers.
     */
    private function glyph(string $icon, string $extra = ''): string
    {
        return Html::tag('i', '', [self::CLASS_ATTR => trim('bi ' . $icon . ' ' . $extra), 'aria-hidden' => 'true'])->render();
    }

    /**
     * A picture with its meaning in a tooltip. The word is also in the page,
     * visually hidden, so screen readers read it and the picture is skipped.
     */
    private function picture(string $glyph, string $word, string $tip, string $class): string
    {
        return Html::span(
            $glyph
            . Html::span($word, [self::CLASS_ATTR => 'visually-hidden'])->render(),
            [self::CLASS_ATTR => $class] + $this->tip($tip)
        )->encode(false)->render();
    }

    private function badgeClass(AllocationStatus $status): string
    {
        return match ($status) {
            // light blue with dark text: readable on light and dark pages (grey was not)
            AllocationStatus::Allocated => 'bg-info text-dark',
            AllocationStatus::Completed => 'bg-success',
            AllocationStatus::Postponed => 'bg-warning text-dark',
            AllocationStatus::Cancelled => 'bg-danger',
        };
    }

    private function numberSuffix(Inv $inv): string
    {
        return ' #' . ($inv->getNumber() ?? '');
    }

    /**
     * Bootstrap tooltip attributes (initTooltips() in scripts.ts picks up every
     * [data-bs-toggle="tooltip"] on page load).
     *
     * @return array{'data-bs-toggle': string, title: string}
     */
    private function tip(string $text): array
    {
        return ['data-bs-toggle' => 'tooltip', 'title' => $text];
    }

    /**
     * A plain POST form that htmx upgrades: hx-post sends the same fields (the clicked
     * button's name/value and the CSRF field included) without leaving the page, and the
     * server's reply -- the re-rendered cell -- replaces the cell it sits in. Without htmx
     * or JavaScript the ordinary POST + redirect still works.
     */
    private function form(string $action, string $inner, string $class, Inv $inv): string
    {
        return new Form()
            ->post($action)
            ->csrf($this->csrf)
            ->addAttributes([
                self::CLASS_ATTR => $class,
                'hx-post' => $action,
                'hx-target' => '#' . self::cellId($inv->reqId()),
                'hx-swap' => 'outerHTML',
            ])
            ->content(($this->admin ? Html::hiddenInput('context', 'index')->render() : '') . $inner)
            ->encode(false)
            ->render();
    }
}
