<?php

declare(strict_types=1);

use App\Invoice\Entity\SalesOrder;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var SalesOrder                                              $so
 * @var App\Invoice\Helpers\DateHelper                          $dateHelper
 * @var App\Invoice\SalesOrderAmount\SalesOrderAmountRepository $soaR
 * @var App\Invoice\SalesOrder\SalesOrderRepository             $soR
 * @var App\Invoice\Setting\SettingRepository                   $s
 * @var App\Widget\Button                                       $button
 * @var App\Widget\GridComponents                               $gridComponents
 * @var App\Widget\PageSizeLimiter                              $pageSizeLimiter
 * @var CurrentRoute                                            $currentRoute
 * @var OffsetPaginator                                         $paginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator                   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface                  $translator
 * @var string                                                  $status
 * @var array                                                   $so_statuses
 * @var array                                                   $so_statuses[$status]
 * @var int                                                     $defaultPageSizeOffsetPaginator
 * @var string                                                  $alert
 * @var string                                                  $csrf
 */
echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' '.$translator->translate('salesorder')),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'salesorder/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <!-- see SalesOrder/SalesOrderRepository getStatuses function 
     && Invoice\Asset\invoice\css\style.css & yii3i.css -->
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 0]); ?>"
                   class="btn <?php echo 0 == $status ? 'btn-primary' : 'btn-default'; ?>">
                    <?php echo $translator->translate('all'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 1]); ?>" style="text-decoration:none"
                   class="btn <?php echo 1 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(1); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('1'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
                   data-bs-toggle = "tooltip" title="<?php echo '1' === $s->getSetting('debug_mode') ? $translator->translate('payment.term.add.additional.terms.at.setting.repository') : ''; ?>"
                   class="btn  <?php echo 2 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(2); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('2'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 3 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(3); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('3'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 4 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(4); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('4'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 5 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(5); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('5'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 6 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(6); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('6'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 7 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(7); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('7'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 8 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(8); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('8'); ?>
                </a>
                 <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 9 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(9); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('9'); ?>
                </a>
                <a href="<?php echo $urlGenerator->generate('salesorder/index', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
                   class="btn  <?php echo 10 == $status ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(10); ?>">
                    <?php echo $soR->getSpecificStatusArrayLabel('10'); ?>
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
            content: static function (SalesOrder $model): string {
                return (string) $model->getId();
            },
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('status'),
            content: static function (SalesOrder $model) use ($soR): Yiisoft\Html\Tag\CustomTag {
                $statusId = $model->getStatus_id();
                if (null !== $statusId) {
                    $span  = $soR->getSpecificStatusArrayLabel((string) $statusId);
                    $class = $soR->getSpecificStatusArrayClass($statusId);

                    return Html::tag('span', $span, ['id' => '#so-to-invoice', 'class' => 'label '.$class]);
                }

                return Html::tag('span');
            },
        ),
        new DataColumn(
            'number',
            content: static function (SalesOrder $model) use ($urlGenerator): A {
                return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('salesorder/view', ['id' => $model->getId()]), ['style' => 'text-decoration:none']);
            },
        ),
        new DataColumn(
            'quote_id',
            content: static function (SalesOrder $model) use ($urlGenerator): string|A {
                return $model->getQuote_id() ?
                Html::a($model->getQuote_id(), $urlGenerator->generate('quote/view', ['id' => $model->getQuote_id()]), ['style' => 'text-decoration:none']) : '';
            },
        ),
        new DataColumn(
            'inv_id',
            content: static function (SalesOrder $model) use ($urlGenerator): string|A {
                $invId = $model->getInv_id();

                return null !== $invId ?
                Html::a($invId, $urlGenerator->generate('inv/view', ['id' => $invId]), ['style' => 'text-decoration:none']) : '';
            },
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('date.created'),
            content: static function (SalesOrder $model): string {
                /*
                 * @psalm-suppress PossiblyInvalidMethodCall $model->getDate_created()->format('Y-m-d')
                 */
                return $model->getDate_created() instanceof DateTimeImmutable ?
                        $model->getDate_created()->format('Y-m-d')
                        : '';
            },
            encodeContent: true,
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('client'),
            content: static function (SalesOrder $model): string {
                $clientName = $model->getClient()?->getClient_name();
                if (null !== $clientName) {
                    return $clientName;
                } else {
                    return '';
                }
            },
        ),
        new DataColumn(
            'id',
            header: $translator->translate('total'),
            content: function (SalesOrder $model) use ($s, $soaR): string {
                $so_id     = $model->getId();
                $so_amount = (($soaR->repoSalesOrderAmountCount((string) $so_id) > 0) ? $soaR->repoSalesOrderquery((string) $so_id) : null);

                return $s->format_currency(null !== $so_amount ? $so_amount->getTotal() : 0.00);
            },
        ),
        new DataColumn(
            header: $translator->translate('view'),
            content: static function (SalesOrder $model) use ($urlGenerator): A {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('salesorder/view', ['id' => $model->getId()]), []);
            },
        ),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('salesorders'),
    (string) $so_statuses[$status]['label'],
);
$toolbarString = Form::tag()->post($urlGenerator->generate('salesorder/index'))->csrf($csrf)->open().
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
    Form::tag()->close();
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-salesorder'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($header)
    ->id('w12-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'salesorder').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>

 