<?php

declare(strict_types=1);

use App\Invoice\Entity\Task;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Entity\Task $task
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\Project\ProjectRepository $prjctR
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var array $statuses
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $tasks 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var WebView $this 
 * @psalm-var positive-int $page
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
    $statuses = [
        1 => [
            'label' => $translator->translate('i.not_started'),
            'class' => 'draft'
        ],
        2 => [
            'label' => $translator->translate('i.in_progress'),
            'class' => 'viewed'
        ],
        3 => [
            'label' => $translator->translate('i.complete'),
            'class' => 'sent'
        ],
        4 => [
            'label' => $translator->translate('i.invoiced'),
            'class' => 'paid'
        ]
    ];
    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'task/index'))
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
            content: static fn (Task $model) => Html::encode($model->getId())
        ), 
        new DataColumn(
            'project_id',
            header: $translator->translate('i.project'),                
            content: static function (Task $model) use ($prjctR) : string {
                return Html::encode(($prjctR->count($model->getProject_id()) > 0 ? $prjctR->repoProjectquery($model->getProject_id())?->getName() : '')); 
            }                   
        ),
        new DataColumn(
            'status',
            header: $translator->translate('i.status'),
            withSorting: true,
            content: static function (Task $model) use ($statuses) : string { 
                $status = $model->getStatus();
                if ($status > 0) {
                    /**
                     * @var int $status
                     * @var array $statuses[$status]
                     */
                    $statusArray = $statuses[$status];
                    /**
                     * @var string $statusArray['label'])
                     */
                    return Html::encode($statusArray['label']);
                }
                return '';
            }
        ),            
        new DataColumn(
            'name',
            header: $translator->translate('i.name'),
            withSorting: true,
            content: static fn (Task $model): string => Html::encode($model->getName())
        ),
        new DataColumn(
            'description',    
            header: $translator->translate('i.description'),                
            content: static fn (Task $model): string => Html::encode(ucfirst($model->getDescription())) 
        ),
        new DataColumn(
            'price',
            header: $translator->translate('i.price'),   
            content: static fn (Task $model): string => Html::encode($s->format_currency(null!==$model->getPrice() ? $model->getPrice() : 0.00))                        
        ),
        new DataColumn(
            'finish_date',
            header: $translator->translate('i.task_finish_date'),   
            content: static function (Task $model) : string {
                /**
                 * @psalm-suppress PossiblyInvalidMethodCall $model->getFinish_date()->format('Y-m-d')
                 */    
                return Html::encode($model->getFinish_date() instanceof \DateTimeImmutable ? $model->getFinish_date()->format('Y-m-d') : ''); 
            }                       
        ),
        new DataColumn(
            'tax_rate_id',    
            header: $translator->translate('i.tax_rate'),
            content: static fn (Task $model): string => ($model->getTaxrate()?->getTaxRateId() > 0) ? Html::encode($model->getTaxrate()?->getTaxRateName()) : $translator->translate('i.none')                       
        ),
        new ActionColumn(
            content: static fn(Task $model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('task/view/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(Task $model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('task/edit/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(Task $model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('task/delete/'. $model->getId())
            ->render(),
        )
    ];       
?>

<?php
    $paginator = (new OffsetPaginator($tasks))
        ->withPageSize($s->positiveListLimit())
        ->withCurrentPage($page)
        ->withToken(PageToken::next((string)$page));    

    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.products'),
        ''
    );
    $toolbarString = Form::tag()->post($urlGenerator->generate('task/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    /**
     * @see vendor\yiisoft\yii-dataview\src\GridView.php for the sequence of functions which can effect rendering
     */
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->urlCreator(new UrlCreator($urlGenerator))        
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-task'])        
    ->header($header)
    ->id('w64-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>
</div>