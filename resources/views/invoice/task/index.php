<?php
declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Router\CurrentRoute;

/**
 * @var \App\Invoice\Entity\Task $task
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator 
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
                            ->content(' ' . Html::encode($translator->translate('i.tasks')))
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
?>

<div>
    <h5><?= $translator->translate('i.tasks'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('task/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($translator->translate('i.new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
<?php 
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => Html::encode($model->getId())
        ), 
        new DataColumn(
            'project_id',
            header: $translator->translate('i.project'),                
            content: static function ($model) use ($prjct) : string {
                return Html::encode(($prjct->count($model->getProject_id()) > 0 ? $prjct->repoProjectquery($model->getId())->getName() : '')); 
            }                   
        ),
        new DataColumn(
            'status',
            header: $translator->translate('i.status'),
            withSorting: true,
            content: static fn ($model) : string => Html::encode($statuses[$model->getStatus()]['label'])
        ),
        new DataColumn(
            'name',
            header: $translator->translate('i.name'),
            withSorting: true,
            content: static fn ($model): string => Html::encode($model->getName())
        ),
        new DataColumn(
            'description',    
            header: $translator->translate('i.description'),                
            content: static fn ($model): string => Html::encode(ucfirst($model->getDescription())) 
        ),
        new DataColumn(
            'price',
            header: $translator->translate('i.price'),   
            content: static fn ($model): string => Html::encode($s->format_currency(null!==$model->getPrice() ? $model->getPrice() : 0.00))                        
        ),
        new DataColumn(
            'finish_date',
            header: $translator->translate('i.task_finish_date'),   
            content: static fn ($model): string => Html::encode($datehelper->date_from_mysql($model->getFinish_date()))                       
        ),
        new DataColumn(
            'tax_rate_id',    
            header: $translator->translate('i.tax_rate'),
            content: static fn ($model): string => ($model->getTaxrate()->getTax_rate_id()) ? Html::encode($model->getTaxrate()->getTax_rate_name()) : $translator->translate('i.none')                       
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('/invoice/task/view/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('/invoice/task/edit/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('/invoice/task/delete/'. $model->getId())
            ->render(),
        )
    ];       
?>


<?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    //->filterPosition('header')
    //->filterModelName('task')            
    ->header($header)
    ->id('w64-grid')
    ->pagination(
    OffsetPagination::widget()
        ->paginator($paginator)
         ->render(),
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-task'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('task/index'))->csrf($csrf)->open() . 
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>
</div>