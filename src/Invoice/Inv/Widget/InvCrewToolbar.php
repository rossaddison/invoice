<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The lead's "today's crew" form above the inv/guest grid: which two or three
 * workers are out today (the lead included) and whether houses are allocated
 * Fronts/Backs separately. The crew becomes the one-tap chips on each house
 * (see InvAllocationCell). Only ever rendered for the lead.
 */
final readonly class InvCrewToolbar
{
    /**
     * @param array<int, string> $workerNames active worker id => name
     * @param list<int> $crewIds today's saved crew
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string|Stringable $csrf,
        private array $workerNames,
        private array $crewIds,
        private bool $frontBack,
    ) {
    }

    public function render(): string
    {
        $t = $this->translator;
        $memberTip = $t->translate('homecare.crew.tip.member');
        $inner = Html::span($t->translate('homecare.crew'), ['class' => 'fw-semibold me-1', 'data-bs-toggle' => 'tooltip', 'title' => $memberTip])->render();
        foreach ($this->workerNames as $workerId => $name) {
            $inner .= $this->checkbox('crew[]', (string) $workerId, 'crew_worker_' . $workerId, $name, in_array($workerId, $this->crewIds, true), $memberTip);
        }
        $inner .= Html::span('|', ['class' => 'text-body mx-1', 'aria-hidden' => 'true'])->render()
            . $this->checkbox('front_back', '1', 'crew_front_back', $t->translate('homecare.crew.front.back'), $this->frontBack, $t->translate('homecare.crew.tip.front.back'))
            . Html::tag('button', $t->translate('homecare.crew.save'), [
                'type' => 'submit',
                'class' => 'btn btn-primary btn-sm alloc-btn',
                'data-bs-toggle' => 'tooltip',
                'title' => $t->translate('homecare.crew.tip.save'),
            ])->render()
            . Html::span($t->translate('homecare.crew.hint'), ['class' => 'text-body small'])->render();
        $action = $this->urlGenerator->generate('inv/setcrew');
        return new Form()
            ->post($action)
            ->csrf($this->csrf)
            ->addAttributes([
                'class' => 'd-flex flex-wrap align-items-center gap-2 border rounded p-2 mb-2',
                // Saved in the background; the server answers 204 and htmx-hooks.ts then refreshes the
                // grid (every row's chips change), so saving the crew does not reload the page.
                'hx-post' => $action,
                'hx-swap' => 'none',
                'data-hx-refresh-main' => '1',
            ])
            ->content($inner)
            ->encode(false)
            ->render();
    }

    private function checkbox(string $name, string $value, string $id, string $label, bool $checked, string $tip): string
    {
        return Html::div(
            Html::tag('input', '', [
                'type' => 'checkbox',
                'class' => 'form-check-input alloc-check',
                'name' => $name,
                'value' => $value,
                'id' => $id,
                'checked' => $checked,
                // The form shows SAVED server state; without this the browser restores
                // whatever was ticked on an earlier visit over what the server rendered.
                'autocomplete' => 'off',
            ])->render()
            . Html::tag('label', $label, ['class' => 'form-check-label', 'for' => $id])->render(),
            ['class' => 'form-check form-check-inline m-0 alloc-check-wrap', 'data-bs-toggle' => 'tooltip', 'title' => $tip]
        )->encode(false)->render();
    }
}
