<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Renders a bulk-status toolbar for the salesorder index.
 * Each button carries data-status-id; the TypeScript handler reads checked
 * salesorder IDs from #table-salesorder and POSTs to salesorder/changeStatus.
 */
final readonly class SalesOrderToolbar
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Maps status IDs to display config.
     * Status 1 (draft) and 8 (invoice generated) are intentionally excluded —
     * drafts are created via quote workflow; status 8 is set automatically.
     *
     * @return array<int, array{label: string, class: string, icon: string}>
     */
    private function statusConfigs(): array
    {
        return [
            2 => [
                'label' => 'salesorder.sent.to.customer',
                'class' => 'btn-outline-primary',
                'icon'  => 'bi-send',
            ],
            3 => [
                'label' => 'salesorder.client.confirmed.terms',
                'class' => 'btn-outline-warning',
                'icon'  => 'bi-check-circle',
            ],
            4 => [
                'label' => 'salesorder.assembled.packaged.prepared',
                'class' => 'btn-outline-info',
                'icon'  => 'bi-box-seam',
            ],
            5 => [
                'label' => 'salesorder.goods.services.delivered',
                'class' => 'btn-outline-success',
                'icon'  => 'bi-truck',
            ],
            6 => [
                'label' => 'salesorder.goods.services.confirmed',
                'class' => 'btn-outline-success',
                'icon'  => 'bi-patch-check',
            ],
            7 => [
                'label' => 'salesorder.invoice.generate',
                'class' => 'btn-outline-warning',
                'icon'  => 'bi-receipt',
            ],
            9 => [
                'label' => 'rejected',
                'class' => 'btn-outline-danger',
                'icon'  => 'bi-x-circle',
            ],
            10 => [
                'label' => 'canceled',
                'class' => 'btn-outline-secondary',
                'icon'  => 'bi-slash-circle',
            ],
        ];
    }

    public function render(): string
    {
        $html = Html::openTag('div', [
            'class' => 'd-flex flex-row flex-wrap gap-1',
            'role'  => 'group',
            'aria-label' => 'Sales order status actions',
            'style' => 'display:flex !important; flex-direction:row; flex-wrap:wrap; gap:0.25rem; margin-top:0.5rem;',
        ]);

        foreach ($this->statusConfigs() as $statusId => $cfg) {
            $label = $this->translator->translate($cfg['label']);
            $icon  = Html::openTag('i', ['class' => 'bi ' . $cfg['icon']])
                   . Html::closeTag('i');

            $html .= (new Button())
                ->type('button')
                ->addClass('btn btn-sm ' . $cfg['class'] . ' so-status-btn')
                ->addAttributes([
                    'id'             => 'btn-so-status-' . $statusId,
                    'data-status-id' => (string) $statusId,
                    'data-bs-toggle' => 'tooltip',
                    'title'          => $label,
                ])
                ->content($icon . ' ' . Html::encode($label))
                ->encode(false)
                ->render();
        }

        $html .= Html::closeTag('div');
        return $html;
    }
}
