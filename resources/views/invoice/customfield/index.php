<?php

declare(strict_types=1);

use App\Invoice\Entity\CustomField;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var array $custom_value_fields
 * @var array $custom_tables
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $page
 */

echo $alert;

$translator->translate('custom.fields');
?>

<div>
    <h5><?= $translator->translate('custom.fields'); ?></h5>
    <div class="btn-group">
        <a href="<?= $urlGenerator->generate('customfield/add');?>" class="btn btn-success" style="text-decoration:none"><i class="fa fa-plus"></i> <?= $translator->translate('new'); ?></a>
    </div>
</div>
<br>
<br>

<?php
    $gridComponents->header('i.custom_fields');
$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (CustomField $model): string {
            return Html::encode($model->getId());
        },
    ),
    new DataColumn(
        'table',
        header: $translator->translate('table'),
        content: static function (CustomField $model) use ($s, $custom_tables): string {
            if (strlen($table = $model->getTable() ?? '') > 0) {
                return Html::encode(ucfirst($s->lang((string)$custom_tables[$table])));
            }
            return '';
        },
    ),
    new DataColumn(
        'label',
        header: $translator->translate('label'),
        content: static function (CustomField $model): string {
            return Html::encode(ucfirst($model->getLabel() ?? '#'));
        }
    ),
    new DataColumn(
        'type',
        header: $translator->translate('type'),
        content: static function (CustomField $model) use ($translator): string {
            $alpha = str_replace("-", "_", strtolower($model->getType()));
            return Html::encode($translator->translate(''.$alpha.''));
        }
    ),
    new DataColumn(
        'order',
        header: $translator->translate('order'),
        content: static function (CustomField $model): string {
            return Html::encode($model->getOrder());
        }
    ),
    new DataColumn(
        'type',
        header: $translator->translate('values'),
        content: static function (CustomField $model) use ($custom_value_fields, $urlGenerator, $translator): string|A {
            if (in_array($model->getType(), $custom_value_fields)) {
                return A::tag()
                       ->href($urlGenerator->generate('customvalue/field', ['id' => $model->getId()]))
                       ->addClass('btn btn-default')
                       ->addAttributes(['style' => 'text-decoration:none'])
                       ->content(
                           I::tag()
                            ->addClass('fa fa-list fa-margin')
                            ->content(' '.$translator->translate('values'))
                       );
            }
            return '';
        }
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (CustomField $model) use ($urlGenerator): string {
                return $urlGenerator->generate('customfield/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ]
        ),
        new ActionButton(
            content: '✎',
            url: static function (CustomField $model) use ($urlGenerator): string {
                return $urlGenerator->generate('customfield/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ]
        ),
        new ActionButton(
            content: '❌',
            url: static function (CustomField $model) use ($urlGenerator): string {
                return $urlGenerator->generate('customfield/delete', ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm("."'".$translator->translate('delete.record.warning')."');"
            ]
        ),
    ]),
];

?>
 <?php
   $toolbarString =
       Form::tag()->post($urlGenerator->generate('customfield/index', ['page' => $page]))
                  ->csrf($csrf)
                  ->open() .
                   Div::tag()
                       ->addClass('float-end m-3')
                       ->content($gridComponents->toolbarReset($urlGenerator))
                       ->encode(false)->render() .
                   Form::tag()->close();
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('custom.fields'),
    ''
);
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-customfield'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->multiSort(true)
->header($gridComponents->header('custom.fields'))
->id('w75-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'customfield').' '.$grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString);
?>