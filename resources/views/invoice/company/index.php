<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Company $company
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
                            ->content(' ' . Html::encode($translator->translate('invoice.invoice.company')))
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
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('invoice.invoice.company'); ?>
    <?= Html::closeTag('h5'); ?>    
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'btn-group']); ?>
        <?= A::tag()
            ->addClass('btn btn-success')
            ->content(I::tag()
                      ->addClass('fa fa-plus')) 
            ->href($urlGenerator->generate('company/add')); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>


<br>
    <?php
        $columns = [
            new DataColumn(
                'id',
                header: $translator->translate('i.id'),
                content: static fn (object $model) => Html::encode($model->getId())
            ),
            new DataColumn(
                'current',
                header: $translator->translate('i.active'),
                content: static fn (object $model) => Html::encode($model->getCurrent() == '1' ? ($translator->translate('i.active').' '.'✔️' ) : $translator->translate('i.inactive').' '.'❌')
            ),
            new DataColumn(
                'name',
                header: $translator->translate('i.name'),
                content: static fn (object $model) => Html::encode($model->getName())
            ),
            new DataColumn(
                'email',
                header: $translator->translate('i.email_address'),
                content: static fn (object $model) => Html::encode($model->getEmail())
            ),
            new DataColumn(
                'phone',
                header: $translator->translate('i.phone'),
                content: static fn (object $model) => Html::encode($model->getPhone())
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
                ->href('company/view/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.edit')
                ])
                ->content('✎')
                ->encode(false)
                ->href('company/edit/'. $model->getId())
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
                ->href('company/delete/'. $model->getId())
                ->render() . Html::closeTag('div')
            ),
        ];
    ?>
    <?php 
        $grid_summary = $s->grid_summary(
            $paginator, 
            $translator, 
            (int)$s->get_setting('default_list_limit'), 
            $translator->translate('invoice.company.public'), '');  
        echo GridView::widget()
        ->rowAttributes(['class' => 'align-middle'])    
        ->columns(...$columns)
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ////->filterPosition('header')
        //->filterModelName('company')
        ->header($header)
        ->id('w163-grid')
        ->pagination(
        OffsetPagination::widget()
             ->paginator($paginator)
             ->render(),
        )
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-contract'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('company/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
