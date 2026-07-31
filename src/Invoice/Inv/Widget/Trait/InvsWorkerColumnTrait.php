<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\Widget\InvsColumnParams;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * Two single-column builders extracted from
 * InvsColumnBuilder::buildColumns() to stay within S138 (≤ 150 lines per
 * function) without pushing that class over S1448 (≤ 20 methods) —
 * SonarQube doesn't count trait-provided methods toward the consuming
 * class's method total, the same technique already used for
 * AuthController earlier this project (see Auth/Trait/Redirects.php).
 *
 * @property-read \Yiisoft\Translator\TranslatorInterface $translator
 * @property-read \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @property-read string|\Stringable $csrf
 */
trait InvsWorkerColumnTrait
{
    private function buildWorkerColumn(InvsColumnParams $p, bool $homeCareEnabled): DataColumn
    {
        $t             = $this->translator;
        $ug            = $this->urlGenerator;
        $csrf          = $this->csrf;
        $activeWorkers = $p->wR->findAllActive();
        return new DataColumn(
            'worker_id',
            header: $t->translate('worker'),
            content: static function (Inv $model) use ($ug, $t, $activeWorkers, $csrf): string {
                $current = $model->getWorker();
                $options = (new Option())
                    ->value('')
                    ->content($t->translate('worker.unassigned'))
                    ->render();
                foreach ($activeWorkers as $worker) {
                    $option = (new Option())->value((string) $worker->reqId())
                        ->content($worker->getFirstname());
                    if (null !== $current && $current->reqId() === $worker->reqId()) {
                        $option = $option->selected(true);
                    }
                    $options .= $option->render();
                }
                return (new Form())
                    ->post($ug->generate('inv/setworker', ['inv_id' => $model->reqId()]))
                    ->csrf($csrf)
                    ->addAttributes(['class' => 'd-flex gap-1'])
                    ->content(
                        Html::openTag('select', [
                            'name' => 'worker_id', 'class' => 'form-select form-select-sm',
                        ]) . $options . Html::closeTag('select')
                        . Html::tag('button', '💾', [
                            'type' => 'submit', 'class' => 'btn btn-outline-primary btn-sm',
                            'title' => $t->translate('worker.assign'),
                        ])
                    )
                    ->encode(false)
                    ->render();
            },
            encodeContent: false,
            visible: $homeCareEnabled,
            withSorting: false,
        );
    }

    private function buildQuickPayColumn(bool $visible = true): DataColumn
    {
        $t  = $this->translator;
        $ug = $this->urlGenerator;
        return new DataColumn(
            header: (new Label())->content('💰')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('quick.pay')])
                ->render(),
            encodeHeader: false,
            content: static function (Inv $model) use ($ug, $t): string {
                $invId    = $model->reqId();
                $statusId = $model->reqStatusId();
                $paid     = $model->getInvAmount()->getPaid() ?? 0.0;
                $total    = $model->getInvAmount()->getTotal() ?? 0.0;
                if ($statusId === 4 || ($total > 0.0 && $paid >= $total)) {
                    return '<span class="badge bg-success" data-bs-toggle="tooltip" title="'
                        . Html::encode($t->translate('paid')) . '">✅</span>';
                }
                if ((int) $model->getCreditinvoiceParentId() > 0
                    || !in_array($statusId, [2, 3, 5, 6], true)
                    || $total <= 0.0
                ) {
                    return '';
                }
                $qpId = 'qp-' . $invId;
                return '<div id="' . $qpId . '">'
                    . '<button class="btn btn-outline-success btn-sm"'
                    . ' hx-get="' . Html::encode(
                        $ug->generate('inv/quickpayform', [], ['inv_id' => $invId])
                    ) . '"'
                    . ' hx-target="#' . $qpId . '" hx-swap="innerHTML"'
                    . ' data-bs-toggle="tooltip" title="'
                    . Html::encode($t->translate('quick.pay')) . '">💰</button>'
                    . '</div>';
            },
            encodeContent: false,
            withSorting: false,
            visible: $visible,
        );
    }
}
