<?php

declare(strict_types=1);

use App\Invoice\Entity\Profile;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
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
 * @var App\Invoice\Entity\Profile $profile
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
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
                            ->content(' ' . Html::encode($translator->translate('invoice.profile.singular')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'profile/index'))
        ->id('btn-reset')
        ->render();
    
    $toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('invoice.profile.singular'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('profile/add'); ?>">
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
            content: static fn (Profile $model) => Html::encode($model->getId())
        ),
        new DataColumn(
            'company_id',
            header: $translator->translate('i.company'),                
            content: static fn (Profile $model): string => Html::encode($model->getCompany()?->getName() ?? '')                  
        ),
        new DataColumn(
            'email',    
            header: $translator->translate('i.email_address'),                
            content: static fn (Profile $model): string => Html::encode(ucfirst($model->getEmail() ?? '')) 
        ),
        new DataColumn(
            'description',    
            header: $translator->translate('i.description'),                
            content: static fn (Profile $model): string => Html::encode(ucfirst($model->getDescription() ?? '')) 
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function(Profile $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('profile/view', ['id' => $model->getId()]);     
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.view'),
                ]      
            ),
            new ActionButton(
                content: '✎',
                url: static function(Profile $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('profile/edit', ['id' => $model->getId()]);     
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.edit'),
                ]      
            ),
            new ActionButton(
                content: '❌',
                url: static function(Profile $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('profile/delete', ['id' => $model->getId()]);     
                },
                attributes: [
                    'title' => $translator->translate('i.delete'),
                    'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
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
        $translator->translate('invoice.profiles'),
        ''
    );
    $toolbarString = Form::tag()->post($urlGenerator->generate('profile/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-profile'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w122-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>
</div>

