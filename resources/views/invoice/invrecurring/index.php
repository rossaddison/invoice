<?php

declare(strict_types=1);

use App\Invoice\Entity\InvRecurring;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\InvRecurring $invRecurring
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array $recur_frequencies
 * @var bool $visible
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $status
 */

?>
<?= $alert; ?>
<?php

$toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-primary me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'invrecurring/index'))
        ->id('btn-reset')
        ->render();

$toolbar = Div::tag();
?>
<?php
/**
 * @var ColumnInterface[] $columns
 */
$columns = [
    new DataColumn(
        'next',
        header: $translator->translate('status'),
        content: static fn(InvRecurring $model) =>
            Span::tag()
            ->addClass(null !== $model->getNext() ? 'btn btn-success' : 'btn btn-danger')
            ->content(null !== $model->getNext() ? $translator->translate('active') : $translator->translate('inactive')),
    ),
    new DataColumn(
        'inv_id',
        header: $translator->translate('base.invoice'),
        content: static function (InvRecurring $model) use ($urlGenerator): string {
            return Html::a($model->getInv()?->getNumber() ?? '#', $urlGenerator->generate(
                'inv/view',
                ['id' => $model->getInv_id()],
            ), ['style' => 'text-decoration:none'])->render();
        },
    ),
    new DataColumn(
        'id',
        header: $translator->translate('date.created'),
        content: static fn(InvRecurring $model) =>
        Html::encode(!is_string($dateCreated = $model->getInv()?->getDate_created()) && null !== $dateCreated ? $dateCreated->format('Y-m-d') : ''),
        withSorting: false,
    ),
    new DataColumn(
        'start',
        header: $translator->translate('start.date'),
        content: static fn(InvRecurring $model) =>
        Html::encode(!is_string($recurringStart = $model->getStart()) ? $recurringStart->format('Y-m-d') : ''),
    ),
    new DataColumn(
        'end',
        header: $translator->translate('end.date'),
        content: static fn(InvRecurring $model) =>
        Html::encode(!is_string($recurringEnd = $model->getEnd()) && null !== $recurringEnd
                     ? $recurringEnd->format('Y-m-d') : ''),
    ),
    new DataColumn(
        'frequency',
        header: $translator->translate('every'),
        content: static fn(InvRecurring $model) =>
        Html::encode($translator->translate((string) $recur_frequencies[$model->getFrequency()])),
    ),
    new DataColumn(
        'next',
        header: $translator->translate('next.date'),
        content: static fn(InvRecurring $model) =>
        Html::encode(null !== $model->getNext() ? ((!is_string($recurringNext = $model->getNext()) && null !== $recurringNext) ? $recurringNext->format('Y-m-d') : '') : ''),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: static function (InvRecurring $model): string {
                return null !== $model->getNext() ? '🛑' : '🏃';
            },
            url: static function (InvRecurring $model) use ($urlGenerator): string {
                return null !== $model->getNext() ? $urlGenerator->generate('invrecurring/stop', ['id' => $model->getId()]) : $urlGenerator->generate('invrecurring/start', ['id' => $model->getId()]);
            },
            attributes: function (InvRecurring $model) use ($translator): array {
                return [
                    'data-bs-toggle' => 'tooltip',
                    'title' => null !== $model->getNext() ? $translator->translate('stop') : $translator->translate('start'),
                ];
            },
        ),
        new ActionButton(
            content: '🔎',
            url: static function (InvRecurring $model) use ($urlGenerator): string {
                return $urlGenerator->generate('invrecurring/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (InvRecurring $model) use ($urlGenerator): string {
                return $urlGenerator->generate('invrecurring/delete', ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];
?>
<?php
$toolbarString =
    Form::tag()->post($urlGenerator->generate('invrecurring/index'))->csrf($csrf)->open() .
    Form::tag()->close();
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('invoices'),
    '',
);
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-left'])
->tableAttributes(['class' => 'table table-striped table-responsive h-75', 'id' => 'table-invoice'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($gridComponents->header(' ' . $translator->translate('recurring.invoices')))
->id('w31-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'invrecurring') . ' ' . $grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString);
?>
