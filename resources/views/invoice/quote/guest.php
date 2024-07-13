<?php

declare(strict_types=1);

use App\Invoice\Entity\Quote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var array $quoteStatuses
 * @var array $quoteStatuses[$status]
 * @var bool $editInv
 * @var string $alert
 * @var string $csrf
 * @var string $status
 */

echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('i.quote'))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'quote/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

?>
<div>
    <h5><?= $translator->translate('i.quote'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>0]); ?>"
                   class="btn <?= $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.approved'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.rejected'); ?>
                </a>                
                <a href="<?= $urlGenerator->generate('quote/guest',['page'=>1,'status'=>6]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 6 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.canceled'); ?>
                </a>
            </div>
    </div>
</div>
<br>
<?php
    $columns = [
        new DataColumn(
            'id',
            $translator->translate('i.id'),
            content: static fn (Quote $model) => $model->getId()
        ),        
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function (Quote $model) use ($qR): Yiisoft\Html\Tag\CustomTag|string { 
                if (null!==$model->getStatus_id()) {
                    $span = $qR->getSpecificStatusArrayLabel((string)$model->getStatus_id());
                    $class = $qR->getSpecificStatusArrayClass((string)$model->getStatus_id());
                    return (string)Html::tag('span', $span, ['id'=>'#quote-guest','class'=>'label '. $class]);
                }
                return '';
            }       
        ),
        new DataColumn(
            'number',
            header: '#',
            content: static function (Quote $model) use ($urlGenerator): string {
               return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
            }
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('i.id'),
            content: static fn (Quote $model): string|null => $model->getClient()?->getClient_name()
        ),        
        new DataColumn(
            'date_created',
            header: $translator->translate('i.date_created'),
            content: static fn (Quote $model): string => ($model->getDate_created())->format($dateHelper->style())
        ),                    
        new DataColumn(
            'date_expires',
            content: static fn (Quote $model): string => ($model->getDate_expires())->format($dateHelper->style())
        ),
        new DataColumn(
            'date_required',
            content: static fn (Quote $model): string => ($model->getDate_required())->format($dateHelper->style())
        ), 
        new DataColumn(
            'id',
            header: $translator->translate('i.total'),
            content: static function (Quote $model) use ($s, $qaR) : string {
               $quote_id = $model->getId();
               if (null!==$quote_id) {
                    $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
                    return $s->format_currency(null!==$quote_amount ? $quote_amount->getTotal() : 0.00);
               }
               return '';
            }
        ),
    ];                
?>
<?php 
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->get_setting('default_list_limit'), 
        $translator->translate('invoice.quotes'),
        ''
    );
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('quote/guest'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-quote-guest'])
    ->dataReader($paginator)        
    ->columns(...$columns)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])         
    ->header($header)        
    ->id('w7-grid')
    ->pagination(
       $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate(($editInv ? $pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'quote') : '').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);          
?>
