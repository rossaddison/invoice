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
 * @var Quote                                         $quote
 * @var App\Invoice\Helpers\DateHelper                $dateHelper
 * @var App\Invoice\Quote\QuoteRepository             $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository         $s
 * @var App\Widget\Button                             $button
 * @var App\Widget\GridComponents                     $gridComponents
 * @var App\Widget\PageSizeLimiter                    $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator        $paginator
 * @var Yiisoft\Router\CurrentRoute                   $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface        $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator         $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator     $urlCreator
 * @var int                                           $defaultPageSizeOffsetPaginator
 * @var array                                         $quoteStatuses
 * @var array                                         $quoteStatuses[$status]
 * @var bool                                          $editInv
 * @var string                                        $alert
 * @var string                                        $csrf
 * @var string                                        $status
 */
echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' '.$translator->translate('quote')),
            ),
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
    <h5><?php echo $translator->translate('quote'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 0]); ?>"
                   class="btn <?php echo 0 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('all'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 2 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('sent'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 3 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('viewed'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 4 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('approved'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 5 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('rejected'); ?>
                </a>                
                <a href="<?php echo $urlGenerator->generate('quote/guest', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 6 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('canceled'); ?>
                </a>
            </div>
    </div>
</div>
<br>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static function (Quote $model): string {
                return (string) $model->getId();
            },
            withSorting: true,
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('status'),
            content: static function (Quote $model) use ($qR): Yiisoft\Html\Tag\CustomTag|string {
                if (null !== $model->getStatus_id()) {
                    $span  = $qR->getSpecificStatusArrayLabel((string) $model->getStatus_id());
                    $class = $qR->getSpecificStatusArrayClass((string) $model->getStatus_id());

                    return Html::tag('span', $span, ['id' => '#quote-guest', 'class' => 'label '.$class]);
                }

                return '';
            },
            withSorting: true,
        ),
        new DataColumn(
            field: 'number',
            property: 'filterQuoteNumber',
            header: $translator->translate('quote.number'),
            content: static function (Quote $model) use ($urlGenerator): A {
                return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view', ['id' => $model->getId()]), ['style' => 'text-decoration:none']);
            },
            filter: Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 80px']),
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('id'),
            content: static fn (Quote $model): string => Html::encode($model->getClient()?->getClient_name() ?? ''),
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('date.created'),
            content: static fn (Quote $model): string => $model->getDate_created()->format('Y-m-d'),
            withSorting: true,
        ),
        new DataColumn(
            'date_expires',
            content: static fn (Quote $model): string => $model->getDate_expires()->format('Y-m-d'),
            withSorting: true,
        ),
        new DataColumn(
            'date_required',
            content: static fn (Quote $model): string => $model->getDate_required()->format('Y-m-d'),
        ),
        new DataColumn(
            'id',
            header: $translator->translate('total'),
            content: static function (Quote $model) use ($s, $qaR): string {
                $quote_id = $model->getId();
                if (null !== $quote_id) {
                    $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);

                    return $s->format_currency(null !== $quote_amount ? $quote_amount->getTotal() : 0.00);
                }

                return '';
            },
        ),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('quotes'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('quote/guest'))->csrf($csrf)->open().
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
    Form::tag()->close();
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-quote-guest'])
    ->dataReader($paginator)
    ->columns(...$columns)
    ->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
    ->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
    ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
    ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->emptyCell($translator->translate('not.set'))
    ->emptyCellAttributes(['style' => 'color:red'])
    ->header($header)
    ->id('w7-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate(($editInv ? $pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'quote') : '').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
