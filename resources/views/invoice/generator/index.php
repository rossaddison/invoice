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
                            ->content(' ' . Html::encode($translator->translate('invoice.generator')))
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
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator'); ?><?= Html::closeTag('h5'); ?>
        <?= Html::openTag('div'); ?>
            <?= Html::a(I::tag()->addClass('bi bi-plus')->content(' '.Html::encode($translator->translate('i.new'))), $urlGenerator->generate('generator/add'), ['class' => 'btn btn-success']); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::br(); ?>
    <?= Html::openTag('div'); ?>

<?php 
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => Html::encode($model->getGentor_id(). '->'.$model->getCamelcase_capital_name())
        ),  
        new DataColumn(
            'id',
            header: $translator->translate('invoice.generator.relations'),
            content: static function ($model) use ($urlGenerator, $translator, $grr) : string {
                $div_open_tag = Html::openTag('div', ['class' => 'btn-group']);
                
                    $entity_name_render = Html::a(
                            $model->getCamelcase_capital_name(),
                                    $urlGenerator->generate('generator/view',
                                    ['id' => $model->getGentor_id()]),
                                    ['class' => 'btn btn-primary btn-sm active','aria-current' => 'page']
                    )->render();
                
                    $relations = $grr->repoGeneratorquery($model->getGentor_id());
                    $relations_content_render = '';
                    foreach ($relations as $relation) {
                        $relations_content_render .= Html::a($relation->getLowercase_name(),
                                $urlGenerator->generate('generatorrelation/edit',
                                ['id' => $relation->getRelation_id()]),
                                ['class' => 'btn btn-primary btn-sm'])->render();
                    }
                
                //modal delete button
                $div_close_tag = Html::closeTag('div');
                
                return 
                
                $div_open_tag.
                    $entity_name_render.
                    $relations_content_render.
                $div_close_tag;
            }
        ),
        
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.view')
            ])
            ->content('🔎')
            ->encode(false)
            ->href('/invoice/generator/view/'. $model->getGentor_id())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.edit')
            ])
            ->content('✎')
            ->encode(false)
            ->href('/invoice/generator/edit/'. $model->getGentor_id())
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
            ->href('/invoice/generator/delete/'. $model->getGentor_id())
            ->render(),
        ),        
        new DataColumn(
            'id',
            header : 'Entity',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    'Entity'.DIRECTORY_SEPARATOR.$model->getCamelcase_capital_name(),
                    $urlGenerator->generate('generator/entity',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id', 
            header: 'Controller',    
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    $model->getCamelcase_capital_name().'Controller',
                    $urlGenerator->generate('generator/controller',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id', 
            header: 'Form',    
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    $model->getCamelcase_capital_name().'Form',
                    $urlGenerator->generate('generator/form',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id',
            header: 'Repository',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    $model->getCamelcase_capital_name().'Repository',
                    $urlGenerator->generate('generator/repo',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),        
        new DataColumn(
            'id',
            header: 'Service',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    $model->getCamelcase_capital_name().'Service',
                    $urlGenerator->generate('generator/service',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),        
        new DataColumn(
            'id',
            header: 'index',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    'index',
                    $urlGenerator->generate('generator/_index',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id',
            header: '_view',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                    '_view',
                    $urlGenerator->generate('generator/_view',['id' => $model->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id',
            header: '_form',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a(
                   '_form',
                   $urlGenerator->generate('generator/_form',['id' => $model->getGentor_id()]),
                   ['class' => 'btn btn-secondary btn-sm ms-2'])->render();
            }
        ),
        new DataColumn(
            'id',
            header: '_route',
            content: static function ($model) use ($urlGenerator) : string {
                return 
                
                Html::a('_route', $urlGenerator->generate('generator/_route',['id' => $model->getGentor_id()]),
                        ['class' => 'btn btn-secondary btn-sm ms-2']
                        )->render();
            }
        ),
    ];       
?>
<?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w21-grid')
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
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-generator'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('generator/index'))->csrf($csrf)->open() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>

