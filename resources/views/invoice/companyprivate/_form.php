<?php

declare(strict_types=1); 

use App\Widget\Button;
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
            <?= $title; ?>
        <?= Html::closeTag('h1'); ?>
        <?= Html::openTag('div', ['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::errorSummary($form)
                        ->errors($errors)
                        ->header($translator->translate('invoice.error.summary'))
                        ->onlyCommonErrors()
                    ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?php
                        $optionsDataCompany = [];
                        foreach ($companies as $company) {
                            $optionsDataCompany[$company->getId()] = $company->getName();
                        }
                    ?>
                    <?=
                        Field::select($form, 'company_id')
                        ->label($company_public, ['control-label'])
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'company_id'
                        ])    
                        ->optionsData($optionsDataCompany)        
                        ->required(true)    
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::hidden($form, 'id')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->hideLabel()
                        ->value(Html::encode($form->getId() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'tax_code')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('i.tax_code'))
                        ->value(Html::encode($form->getTax_code() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'iban')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('i.user_iban'))
                        ->value(Html::encode($form->getIban() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'gln')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('i.gln'))
                        ->value(Html::encode($form->getGln() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'rcc')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('i.sumex_rcc'))
                        ->value(Html::encode($form->getRcc() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?> 
                    <?= Html::openTag('label'); ?>
                        <?= Html::encode($form->getLogo_filename()); ?>
                    <?= Html::closeTag('label'); ?>
                    <?= Field::file($form, 'logo_filename')
                        ->accept('image/*')
                        ->value(Html::encode($form->getLogo_filename())); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_width')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('invoice.company.private.logo.width'))
                        ->value(Html::encode($form->getLogo_width() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_height')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('invoice.company.private.logo.height'))
                        ->value(Html::encode($form->getLogo_height() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_margin')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('invoice.company.private.logo.margin'))
                        ->value(Html::encode($form->getLogo_margin() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $startdate = $datehelper->get_or_set_with_style($form->getStart_date() 
                                   ?? new \DateTimeImmutable('now')); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::datetime($form, 'start_date')
                            ->addInputAttributes(
                                [
                                    'class' => 'form-control input-sm datepicker',
                                    'placeholder' => ' ('.$datehelper->display().')',
                                    'readonly' => 'readonly'
                                ])
                            ->value(Html::encode($startdate instanceof \DateTimeImmutable 
                                               ||$startdate instanceof \DateTime 
                                                ? $startdate->format($datehelper->style()) 
                                                : $startdate)); 
                        ?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $enddate = $datehelper->get_or_set_with_style($form->getEnd_date() 
                                   ?? new \DateTimeImmutable('now')); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::datetime($form, 'end_date')
                            ->addInputAttributes(
                                [
                                    'class' => 'form-control input-sm datepicker',
                                    'placeholder' => ' ('.$datehelper->display().')',
                                    'readonly' => 'readonly'
                                ])
                            ->value(Html::encode($enddate instanceof \DateTimeImmutable 
                                               ||$enddate instanceof \DateTime 
                                                ? $enddate->format($datehelper->style()) 
                                                : $enddate)); 
                        ?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Button::back_save($translator); ?>
<?= Form::tag()->close() ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
