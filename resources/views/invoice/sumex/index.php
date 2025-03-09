<?php
declare(strict_types=1);

use App\Invoice\Entity\Sumex;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Router\CurrentRoute;

/**
 * @var App\Invoice\Entity\Sumex $sumex
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var WebView $this
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
                I::tag()->addClass('bi bi-receipt')
                        ->content(' ' . Html::encode($translator->translate('i.invoice_sumex')))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'sumex/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (Sumex $model) => Html::encode($model->getId())
        ),
        new DataColumn(
            'casenumber',
            header: $translator->translate('i.case_number'),
            content: static fn (Sumex $model) => Html::encode($model->getCasenumber())
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Sumex $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('sumex/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: static function (Sumex $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('sumex/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: static function (Sumex $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('sumex/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('i.delete'),
                    'onclick' => "return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                ]
            ),
        ]),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('i.invoice_sumex'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('sumex/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
/**
 * @see vendor\yiisoft\yii-dataview\src\GridView.php for the sequence of functions which can effect rendering
 */
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-sumex'])
->header($header)
->id('w142-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.records'))
->toolbar($toolbarString);
?>
</div>

