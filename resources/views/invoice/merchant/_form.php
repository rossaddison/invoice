<?php
declare(strict_types=1); 


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('MerchantForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back_save($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?php 
                    foreach ($invs as $inv) { 
                        $optionsDataInv[$inv->getId()] = $inv->getNumber();                    
                    }
                    echo Field::select($form, 'inv_id')
                    ->label($translator->translate('invoice.invoice'),['control-label'])
                    ->optionsData($optionsDataInv)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::checkbox($form, 'successful')
                ->inputLabelAttributes(['class' => 'form-check-label'])    
                ->enclosedByLabel(true)
                ->inputClass('form-check-input')
                ->ariaDescribedBy($translator->translate('invoice.successful'))
            ?>        
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::date($form, 'date')
                ->label($translator->translate('i.date'), ['class' => 'form-label'])
                ->required(true)
                ->value($form->getDate() ? ($form->getDate())->format('Y-m-d') : '')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'driver')
                ->label($translator->translate('invoice.merchant.driver'), ['form-label'])
                ->placeholder($translator->translate('invoice.merchant.driver'))    
                ->value(Html::encode($form->getDriver() ?? ''))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'response')
                ->label($translator->translate('invoice.merchant.response'), ['form-label'])
                ->placeholder($translator->translate('invoice.merchant.response'))    
                ->value(Html::encode($form->getResponse() ?? ''))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'reference')
                ->label($translator->translate('invoice.merchant.reference'), ['form-label'])
                ->placeholder($translator->translate('invoice.merchant.reference'))    
                ->value(Html::encode($form->getReference() ?? ''))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>