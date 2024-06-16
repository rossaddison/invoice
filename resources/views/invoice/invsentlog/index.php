<?php

declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Translator\TranslatorInterface;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var \App\Invoice\Entity\InvSentLog $invsentlog
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var OffsetPaginator $paginator
 * @var string $id
 */
 
 echo $alert;

 /*
  * @see https://emojipedia.org/incoming-envelope
  */
?>

<h1>📨</h1>

<?php
    
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
            I::tag()->content('📨')
            )
        )
        ->render();

    $toolbarReset = A::tag()
      ->addAttributes(['type' => 'reset'])
      ->addClass('btn btn-danger me-1 ajax-loader')
      ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
      ->href($urlGenerator->generate($currentRoute->getName()))
      ->id('btn-reset')
      ->render();

    $toolbar = Div::tag();
    
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn($model) => $model->getId()
        ),
        new DataColumn(
            field: 'inv_id',
            property: 'filterInvNumber',
            header: $translator->translate('invoice.invoice.number'),
            content: static function ($model) use ($urlGenerator): string {
                return Html::a($model->getInv()->getNumber().' 🔍', $urlGenerator->generate('inv/view', 
                               ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render();
            },    
            filter: $optionsDataInvNumberDropDownFilter,
            withSorting: false            
        ),
        new DataColumn(
            field: 'client_id',
            property: 'filterClient',
            header: $translator->translate('i.client'),
            content: static fn($model): string => (string)$model->getClient()->getClient_full_name(),
            filter: $optionsDataClientsDropDownFilter,
            withSorting: false
        ),
        new DataColumn(
            'inv_id',
            header: $translator->translate('i.setup_db_username_info'),
            content: static fn($model) => $model->getInv()->getUser()->getLogin()
        ),            
        new DataColumn(
            'date_sent',
            header: $translator->translate('invoice.email.date'),
            content: static fn($model): string => ($model->getDate_sent())->format('l, d-M-Y H:i:s T'),
        ),            
    ];
    
    echo '<br>';
    echo GridView::widget()
      ->columns(...$columns)
      ->dataReader($paginator)
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->header($header)
      ->id('w10463-grid')
      ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
      )
      ->rowAttributes(['class' => 'align-middle'])
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'invsentlog').' '.$grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
      ->tableAttributes(['class' => 'table table-striped text-center h-10463', 'id' => 'table-invsentlog'])
      ->toolbar(
        Form::tag()->post($urlGenerator->generate('invsentlog/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );