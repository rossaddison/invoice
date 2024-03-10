<?php
declare(strict_types=1);

use App\Widget\GridComponents;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;

/**
 * @var \App\Invoice\Entity\CustomField $customField
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var \Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash 
 * @var \Yiisoft\View\WebView $this
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
    $gridComponents = new GridComponents($currentRoute, $translator, $urlGenerator);
    $gridComponents->header('i.custom_fields');
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static function ($model) : string {
                return Html::encode($model->getId());
            },   
        ),
        new DataColumn(
            'table',
            header: $translator->translate('i.table'),
            content: static function ($model) use ($s, $custom_tables): string {
                return Html::encode(ucfirst($s->lang($custom_tables[$model->getTable()] ?? '')));
            },   
        ),
        new DataColumn(
            'label',
            header: $translator->translate('i.label'),
            content: static function ($model) : string {
                return Html::encode(ucfirst($model->getLabel()));
            }      
        ),
        new DataColumn(
            'type',
            header: $translator->translate('i.type'),
            content: static function ($model) use ($translator): string {
                $alpha = str_replace("-", "_", strtolower($model->getType()));
                return Html::encode($translator->translate('i.'.$alpha.''));
            }      
        ),
        new DataColumn(
            'order',
            header: $translator->translate('i.order'),
            content: static function ($model) : string {
                return Html::encode($model->getOrder());
            }      
        ), 
        new DataColumn(
            'type',
            header: $translator->translate('i.values'),
            content: static function ($model) use ($custom_value_fields, $urlGenerator, $translator) :  string {
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
                content: static fn($model): string => Html::openTag('div', ['class' => 'btn-group']) .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.view')
                ])
                ->content('🔎')
                ->encode(false)
                ->href('/invoice/customfield/view/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.edit')
                ])
                ->content('✎')
                ->encode(false)
                ->href('/invoice/customfield/edit/'. $model->getId())
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
                ->href('/invoice/customfield/delete/'. $model->getId())
                ->render() . Html::closeTag('div')
            ),
    ];
        
 ?>
 <?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->enableMultisort(true)
    ->header($gridComponents->header('i.custom_fields'))
    ->id('w75-grid')
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator) 
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'customfield').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-customfield'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('customfield/index', ['page' => $page]))->csrf($csrf)->open() .  
        Div::tag()
            ->addClass('float-end m-3')
            ->content($gridComponents->toolbarReset($urlGenerator))
            ->encode(false)->render() .
        Form::tag()->close()
    );
?>