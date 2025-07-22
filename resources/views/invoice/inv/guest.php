<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var Inv                                             $inv
 * @var App\Invoice\Entity\UserInv                      $userInv
 * @var App\Invoice\Helpers\DateHelper                  $dateHelper
 * @var App\Invoice\Inv\InvRepository                   $iR
 * @var App\Invoice\InvAmount\InvAmountRepository       $iaR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\SalesOrder\SalesOrderRepository     $soR
 * @var App\Invoice\Setting\SettingRepository           $s
 * @var Button                                          $button
 * @var App\Widget\GridComponents                       $gridComponents
 * @var App\Widget\PageSizeLimiter                      $pageSizeLimiter
 * @var OffsetPaginator                                 $sortedAndPagedPaginator
 * @var Sort                                            $sort
 * @var Yiisoft\Router\CurrentRoute                     $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface          $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator           $urlGenerator
 * @var UrlCreator                                      $urlCreator
 * @var Yiisoft\Data\Cycle\Reader\EntityReader          $invs
 * @var bool                                            $viewInv
 * @var int                                             $decimalPlaces
 * @var int                                             $defaultPageSizeOffsetPaginator
 * @var int                                             $userInvListLimit
 * @var string                                          $alert
 * @var string                                          $csrf
 * @var string                                          $label
 * @var string                                          $modal_add_quote
 * @var string                                          $sortString
 * @var string                                          $status
 *
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 */
$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

echo $alert;
?>
<div>
    <h5><?php echo $translator->translate('invoice'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 0]); ?>"
                   class="btn btn-<?php echo 0 == $status ? $iR->getSpecificStatusArrayClass(0) : 'btn-default'; ?>">
                   <?php echo $iR->getSpecificStatusArrayEmoji(0).' '.$translator->translate('all'); ?>
                </a>
                
                <?php // Guests are never sent draft invoices i.e. status = '1'?>
                
                <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
                   class="btn btn-<?php echo 2 == $status ? $iR->getSpecificStatusArrayClass(2) : 'btn-default'; ?>">
                       <?php echo $iR->getSpecificStatusArrayEmoji(2).' '.$translator->translate('sent'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
                   class="btn btn-<?php echo 3 == $status ? $iR->getSpecificStatusArrayClass(3) : 'btn-default'; ?>">
                       <?php echo $iR->getSpecificStatusArrayEmoji(3).' '.$translator->translate('viewed'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
                   class="btn btn-<?php echo 4 == $status ? $iR->getSpecificStatusArrayClass(4) : 'btn-default'; ?>">
                       <?php echo $iR->getSpecificStatusArrayEmoji(4).' '.$translator->translate('paid'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
                   class="btn btn-<?php echo 5 == $status ? $iR->getSpecificStatusArrayClass(5) : 'btn-default'; ?>">
                    <?php echo $iR->getSpecificStatusArrayEmoji(5).' '.$translator->translate('overdue'); ?>
                </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 6 == $status ? $iR->getSpecificStatusArrayClass(6) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(6).' '.$translator->translate('unpaid'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 7 == $status ? $iR->getSpecificStatusArrayClass(7) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(7).' '.$translator->translate('reminder'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 8 == $status ? $iR->getSpecificStatusArrayClass(8) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(8).' '.$translator->translate('letter'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 9 == $status ? $iR->getSpecificStatusArrayClass(9) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(9).' '.$translator->translate('claim'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 10 == $status ? $iR->getSpecificStatusArrayClass(10) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(10).' '.$translator->translate('judgement'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 11 == $status ? $iR->getSpecificStatusArrayClass(11) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(11).' '.$translator->translate('enforcement'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 12 == $status ? $iR->getSpecificStatusArrayClass(12) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(12).' '.$translator->translate('credit.invoice.for.invoice'); ?>
                 </a>
                 <a href="<?php echo $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
                    class="btn btn-<?php echo 13 == $status ? $iR->getSpecificStatusArrayClass(13) : 'btn-default'; ?>">
                     <?php echo $iR->getSpecificStatusArrayEmoji(13).' '.$translator->translate('loss'); ?>
                 </a>
            </div>
    </div>
</div>
<br>
<?php
    /**
     * @var ColumnInterface[] $columns
     */
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn (Inv $model) => (string) $model->getId(),
            withSorting: true,
        ),
        new ActionColumn(
            buttons: [
                new ActionButton(
                    url: static function (Inv $model) use ($urlGenerator): string {
                        return $urlGenerator->generate('inv/pdf', ['include' => 0]);
                    },
                    attributes: [
                        'style'          => 'text-decoration:none',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $translator->translate('download.pdf'),
                        'class'          => 'bi bi-file-pdf',
                    ],
                ),
                new ActionButton(
                    url: static function (Inv $model) use ($urlGenerator): string {
                        return $urlGenerator->generate('inv/pdf', ['include' => 1]);
                    },
                    attributes: [
                        'style'          => 'text-decoration:none',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $translator->translate('download.pdf').'➡️'.$translator->translate('custom.field'),
                        'class'          => 'bi bi-file-pdf-fill',
                    ],
                ),
            ],
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('status'),
            content: static function (Inv $model) use ($s, $iR, $irR, $translator): Yiisoft\Html\Tag\CustomTag {
                $label = $iR->getSpecificStatusArrayLabel((string) $model->getStatus_id());
                if ($model->getIs_read_only() && $s->getSetting('disable_read_only') === (string) 0) {
                    $label .= ' 🚫';
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $label .= $translator->translate('recurring').' 🔄';
                }

                return Html::tag('span', $iR->getSpecificStatusArrayEmoji((int) $model->getStatus_id()).$label, ['class' => 'label label-'.$iR->getSpecificStatusArrayClass((int) $model->getStatus_id())]);
            },
            withSorting: true,
        ),
        new DataColumn(
            property: 'filterInvNumber',
            field: 'number',
            header: $translator->translate('number'),
            content: static function (Inv $model) use ($urlGenerator): A {
                return A::tag()
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->content(($model->getNumber() ?? '#').' 🔍')
                    ->href($urlGenerator->generate('inv/view', ['id' => $model->getId()]));
            },
            filter: $optionsDataInvNumberDropDownFilter,
            withSorting: false,
        ),
        new DataColumn(
            header: '💳',
            field: 'creditinvoice_parent_id',
            content: static function (Inv $model) use ($urlGenerator, $iR): A {
                $visible = $iR->repoInvUnLoadedquery($model->getCreditinvoice_parent_id());
                $url     = ($model->getNumber() ?? '#').'💳';

                return A::tag()
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->content($visible ? $url : '')
                    ->href($urlGenerator->generate('inv/view', ['id' => $model->getCreditinvoice_parent_id()]));
            },
            withSorting: false,
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('client'),
            content: static fn (Inv $model): string => $model->getClient()?->getClient_name() ?? '',
            withSorting: false,
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('date.created'),
            content: static fn (Inv $model): string => !is_string($dateCreated = $model->getDate_created()) ? $dateCreated->format('Y-m-d') : '',
            withSorting: false,
        ),
        new DataColumn(
            'date_due',
            header: $translator->translate('due.date'),
            content: static function (Inv $model): Yiisoft\Html\Tag\CustomTag {
                $now = new DateTimeImmutable('now');

                return Html::tag('label')
                    ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                    ->content(!is_string($dateDue = $model->getDate_due()) ? $dateDue->format('Y-m-d') : '');
            },
            withSorting: true,
        ),
        new DataColumn(
            field: 'id',
            property: 'filterInvAmountTotal',
            header: $translator->translate('total').' ( '.$s->getSetting('currency_symbol').' ) ',
            content: static function (Inv $model) use ($decimalPlaces): Label {
                $invAmountTotal = $model->getInvAmount()->getTotal();

                return
                    Label::tag()
                        ->attributes(['class' => $invAmountTotal > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null !== $invAmountTotal
                                ? number_format($invAmountTotal, $decimalPlaces)
                                : number_format(0, $decimalPlaces)));
            },
            filter: Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px']),
            withSorting: false,
        ),
        new DataColumn(
            'id',
            header: $translator->translate('paid').' ( '.$s->getSetting('currency_symbol').' ) ',
            content: static function (Inv $model) use ($decimalPlaces): Label {
                $invAmountPaid = $model->getInvAmount()->getPaid();

                return Label::tag()
                    ->attributes(['class' => $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal() ? 'label label-danger' : 'label label-success'])
                    ->content(Html::encode(null !== $invAmountPaid
                            ? number_format($invAmountPaid > 0.00 ? $invAmountPaid : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
            },
            withSorting: false,
        ),
        new DataColumn(
            'id',
            header: $translator->translate('balance').' ( '.$s->getSetting('currency_symbol').' ) ',
            content: static function (Inv $model) use ($decimalPlaces): Label {
                $invAmountBalance = $model->getInvAmount()->getBalance();

                return Label::tag()
                    ->attributes(['class' => $invAmountBalance > 0.00 ? 'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $invAmountBalance
                            ? number_format($invAmountBalance > 0.00 ? $invAmountBalance : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
            },
            withSorting: false,
        ),
    ];
?>
<?php
$sort = Sort::only(['status_id', 'number', 'date_created', 'date_due', 'id', 'client_id'])
    ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invs))
    ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$toolbarString = Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open().
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('client'), true))->encode(false)->render().
        Form::tag()->close();

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    !empty($userInvListLimit) ? $userInvListLimit : 10,
    $translator->translate('invoices'),
    $label,
);

$urlCreator = new UrlCreator($urlGenerator);
$order      = OrderHelper::stringToArray($sortString);
$urlCreator->__invoke([], $order);

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-invoice-guest'])
    ->columns(...$columns)
    ->dataReader($sortedAndPagedPaginator)
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
    ->header($gridComponents->header(' '.$translator->translate('invoice')))
    ->id('w9-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate(($viewInv ?
                       $pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'inv', $defaultPageSizeOffsetPaginator) : '').' '.
                       $grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
