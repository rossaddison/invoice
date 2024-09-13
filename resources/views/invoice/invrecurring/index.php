<?php

declare(strict_types=1);

use App\Invoice\Entity\InvRecurring;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Yii\DataView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\CheckboxColumn;
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
            header: $translator->translate('i.status'),
            content: static fn(InvRecurring $model) =>
                Span::tag()
                ->addClass(null!==$model->getNext() ? 'btn btn-success' : 'btn btn-danger')
                ->content(null!==$model->getNext() ? $translator->translate('i.active') : $translator->translate('i.inactive'))
        ),
        new DataColumn(
            'inv_id', 
            header: $translator->translate('i.base_invoice'),    
            content: static function (InvRecurring $model) use ($urlGenerator): string {
                return Html::a($model->getInv()?->getNumber() ?? '#', $urlGenerator->generate('inv/view', 
                               ['id' => $model->getInv_id()]), ['style' => 'text-decoration:none'])->render();
            }  
        ),
        new DataColumn(
            'id',    
            header: $translator->translate('i.date_created'),
            content: static fn(InvRecurring $model) => 
            Html::encode(!is_string($dateCreated = $model->getInv()?->getDate_created()) && null!==$dateCreated ? $dateCreated->format($dateHelper->style()) : ''),    
            withSorting: false
        ), 
        new DataColumn(
            'start',
            header: $translator->translate('i.start_date'),
            content: static fn(InvRecurring $model) => 
            Html::encode(!is_string($recurringStart = $model->getStart()) ? $recurringStart->format($dateHelper->style()) : '')
        ),
        new DataColumn(
            'end',
            header: $translator->translate('i.end_date'),
            content: static fn(InvRecurring $model) => 
            Html::encode(!is_string($recurringEnd = $model->getEnd()) && null!==$recurringEnd 
                         ? $recurringEnd->format($dateHelper->style()) : '')
        ),
        new DataColumn(
            'frequency',
            header: $translator->translate('i.every'),
            content: static fn(InvRecurring $model) => 
            Html::encode($translator->translate((string)$recur_frequencies[$model->getFrequency()]))
        ),
        new DataColumn(
            'next',
            header: $translator->translate('i.next_date'),
            content: static fn(InvRecurring $model) => 
            Html::encode(null!==$model->getNext() ? ((!is_string($recurringNext = $model->getNext()) && null!==$recurringNext) ? $recurringNext->format($dateHelper->style()) : '') : '')
        ),
        new ActionColumn(
            header: '', 
            content: static fn(InvRecurring $model): string =>  
            Html::a()
            ->addAttributes(['class' => 'btn btn-default text-decoration-none', 'title' => null!==$model->getNext() ? $translator->translate('i.stop') : $translator->translate('i.start') ])
            ->content(null!==$model->getNext() ? '🛑' : '🏃')
            ->encode(false)
            ->href(null!==$model->getNext() ? 'invrecurring/stop/'. $model->getId() : 'invrecurring/start/'. $model->getId())
            ->render(),
        ),    
        new ActionColumn(
            header: '',
            content: static fn(InvRecurring $model): string => !empty($id = $model->getId()) ? 
            Html::a()
            ->addAttributes(['class' => 'btn btn-default text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('invrecurring/view/'. $id)
            ->render() : '',
        ),
        new ActionColumn(
            header: '',
            content: static fn(InvRecurring $model): string => !empty($id = $model->getId()) ?
            Html::a()
            ->addAttributes([
                'class'=>'btn btn-default text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('invrecurring/delete/'. $id)
            ->render() : '',
        ),
    ];
?>
<?php
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('invrecurring/index'))->csrf($csrf)->open() .
        Form::tag()->close();
    $grid_summary = $s->grid_summary(
        $paginator,
        $translator,
        (int) $s->get_setting('default_list_limit'),
        $translator->translate('invoice.invoice.invoices'),
        ''
    );
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-left'])
    ->tableAttributes(['class' => 'table table-striped table-responsive h-75', 'id' => 'table-invoice'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])         
    ->header($gridComponents->header(' ' . $translator->translate('i.recurring_invoices')))
    ->id('w31-grid') 
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])    
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'invrecurring').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);    
?>
