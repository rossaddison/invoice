<?php
declare(strict_types=1);

use App\Invoice\Entity\InvAllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var App\Widget\GridComponents              $gridComponents
 * @var CurrentRoute                           $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var string                                 $alert
 * @var string                                 $csrf
 */
echo $alert;
?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' '.$translator->translate('allowance.or.charge.inv')),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'invallowancecharge/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<div>
    <h5><?php echo $translator->translate('allowance.or.charge.inv'); ?></h5>
    <div class="btn-group">
    </div>
    <br>
    <br>
</div>
<div>
<br>    
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn (InvAllowanceCharge $model) => $model->getId(),
        ),
        new DataColumn(
            'inv_id',
            header: $translator->translate('invoice'),
            content: static function (InvAllowanceCharge $model) use ($urlGenerator): A {
                return Html::a($model->getInv()?->getNumber() ?? '#', $urlGenerator->generate('inv/view', ['id' => $model->getInv_id()]), []);
            },
            encodeContent: false,
        ),
        new DataColumn(
            header: $translator->translate('allowance.or.charge.reason.code'),
            content: static function (InvAllowanceCharge $model): string {
                return $model->getAllowanceCharge()?->getReasonCode() ?? '';
            },
        ),
        new DataColumn(
            header: $translator->translate('allowance.or.charge.reason'),
            content: static function (InvAllowanceCharge $model): string {
                return $model->getAllowanceCharge()?->getReason() ?? '';
            },
        ),
        new DataColumn(
            header: $translator->translate('allowance.or.charge.amount'),
            content: static fn (InvAllowanceCharge $model) => $model->getAmount(),
        ),
        new DataColumn(
            header: $translator->translate('vat'),
            content: static fn (InvAllowanceCharge $model) => $model->getVat(),
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharge/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title'          => $translator->translate('view'),
                ],
            ),
            new ActionButton(
                content: '✎',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharge/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title'          => $translator->translate('edit'),
                ],
            ),
            new ActionButton(
                content: '❌',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharage/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title'   => $translator->translate('delete'),
                    'onclick' => 'return confirm('."'".$translator->translate('delete.record.warning')."');",
                ],
            ),
        ]),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('allowance.or.charge'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('invallowancecharge/index'))->csrf($csrf)->open().
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
    Form::tag()->close();
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-allowancecharge'])
    ->columns(...$columns)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($header)
    ->id('w3-grid')
    ->dataReader($paginator)
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
