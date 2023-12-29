<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \App\Invoice\CompanyPrivate\CompanyPrivateForm $form
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

?>
<?= Html::openTag('h1');?>
    <?= Html::encode($title); ?>
<?=Html::closeTag('h1'); ?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('invoice.setting.company.private'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyPrivateForm')
    ->open() ?>

    <?= Html::openTag('div',['id' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= $translator->translate('i.view'); ?>
        <?= Html::closeTag('h1'); ?>
        <?= Html::openTag('div', ['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'company_public_name')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'tax_code')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'iban')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'gln')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('i.gln'))
                        ->readonly(true)
                        ->value(Html::encode($form->getGln() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'rcc')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'container-fluid px-1']); ?>
                    <?= Html::openTag('div', ['class' => 'p-3 border bg-light']); ?>
                        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                            <?= Field::text($form, 'logo_filename')
                                ->readonly(true)
                                ->value($form->getLogo_filename()); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                            <?= Field::image()
                               ->src('/logo/'. $form->getLogo_filename())
                               ->height(150)
                               ->width(150); ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>  
                <?= Html::Tag('br'); ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group has-feedback']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::datetime($form, 'start_date')
                            ->readonly(true)
                            ->value(Html::encode(($form->getStart_date())?->format($datehelper->style()))); ?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group has-feedback']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::datetime($form, 'end_date')
                            ->readonly(true)
                            ->value(Html::encode(($form->getEnd_date())?->format($datehelper->style()))); ?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
