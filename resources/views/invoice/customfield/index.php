<?php

declare(strict_types=1);

use App\Invoice\Entity\CustomField;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;

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

$translator->translate('i.custom_fields');
?>

<div>
    <h5><?= $translator->translate('i.custom_fields'); ?></h5>
    <div class="btn-group">
        <a href="<?= $urlGenerator->generate('customfield/add');?>" class="btn btn-success" style="text-decoration:none"><i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?></a>
    </div>
</div>
<br>
<br>

<?php
    $gridComponents->header('i.custom_fields');
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static function (CustomField $model) : string {
                return Html::encode($model->getId());
            },   
        ),
        new DataColumn(
            'table',
            header: $translator->translate('i.table'),
            content: static function (CustomField $model) use ($s, $custom_tables): string {
                if (strlen($table = $model->getTable() ?? '') > 0) {
                    return Html::encode(ucfirst($s->lang((string)$custom_tables[$table])));
                }
                return '';
            },   
        ),
        new DataColumn(
            'label',
            header: $translator->translate('i.label'),
            content: static function (CustomField $model) : string {
                return Html::encode(ucfirst($model->getLabel() ?? '#'));
            }      
        ),
        new DataColumn(
            'type',
            header: $translator->translate('i.type'),
            content: static function (CustomField $model) use ($translator): string {
                $alpha = str_replace("-", "_", strtolower($model->getType()));
                return Html::encode($translator->translate('i.'.$alpha.''));
            }      
        ),
        new DataColumn(
            'order',
            header: $translator->translate('i.order'),
            content: static function (CustomField $model) : string {
                return Html::encode($model->getOrder());
            }      
        ), 
        new DataColumn(
            'type',
            header: $translator->translate('i.values'),
            content: static function (CustomField $model) use ($custom_value_fields, $urlGenerator, $translator) :  string {
                if (in_array($model->getType(), $custom_value_fields)) {
                    return A::tag()
                           ->href($urlGenerator->generate('customvalue/field', ['id' => $model->getId()]))
                           ->addClass('btn btn-default')
                           ->addAttributes(['style' => 'text-decoration:none'])
                           ->content(
                                I::tag()
                                ->addClass('fa fa-list fa-margin')
                                ->content(' '.$translator->translate('i.values'))
                            ) 
                           ->render(); 
                }
                return '';
            }
        ),
        new ActionColumn(
                content: static fn(CustomField $model): string => Html::openTag('div', ['class' => 'btn-group']) .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.view')
                ])
                ->content('🔎')
                ->encode(false)
                ->href('customfield/view/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.edit')
                ])
                ->content('✎')
                ->encode(false)
                ->href('customfield/edit/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class'=>'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.delete'),
                    'type'=>'submit', 
                    'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                ])
                ->content('❌')
                ->encode(false)
                ->href('customfield/delete/'. $model->getId())
                ->render() . Html::closeTag('div')
            ),
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
            (int)$s->get_setting('default_list_limit'), 
            $translator->translate('i.custom_fields'),
            ''
        );  
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-customfield'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->enableMultisort(true)
    ->header($gridComponents->header('i.custom_fields'))
    ->id('w75-grid')
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator) 
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'customfield').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>