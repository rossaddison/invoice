<?php
declare(strict_types=1);

use App\Invoice\Entity\Project;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
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
 * @var App\Invoice\Entity\Project $project
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
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
                            ->content(' ' . Html::encode($translator->translate('i.project')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'project/index'))
        ->id('btn-reset')
        ->render();
    
    $toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('i.project'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('project/add'); ?>">
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
            content: static fn (Project $model) => Html::encode($model->getId())
        ),        
        new DataColumn(
            'client_id',
            header: $translator->translate('i.client'),                
            content: static function (Project $model): string {
                $clientName = $model->getClient()?->getClient_name() ?? '';
                $clientSurname = $model->getClient()?->getClient_surname() ?? '';
                if ((strlen($clientName) > 0) && (strlen(($clientSurname)) > 0)) {
                    return Html::encode($clientName . ' '. $clientSurname);
                } else {
                    return '#';
                }
            }                  
        ),
        new DataColumn(
            'name',    
            header: $translator->translate('i.project_name'),                
            content: static fn (Project $model): string => Html::encode(ucfirst($model->getName() ?? '')) 
        ),
        new ActionColumn(
            content: static fn(Project $model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('project/view/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(Project $model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('project/edit/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(Project $model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('project/delete/'. $model->getId())
            ->render(),
        )
    ];       
?>
<?php
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.projects'),
        ''
    );
    $toolbarString = Form::tag()->post($urlGenerator->generate('project/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-project'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w84-grid')
    ->pagination(
    OffsetPagination::widget()
        ->paginator($paginator)
         ->render(),
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>
</div>


