<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var string $csrf
 */
?>
<?= Html::openTag('form', ['method' => 'post']); ?>
    <?= Html::tag('input','',['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
    
    <?= Html::openTag('div'); ?>
    <?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
    <?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
    <?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
    <?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
    <?= Html::openTag('div',['class'=>'card-header']); ?>
    <?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
    <?= $translator->translate('i.custom_values_new'); ?>
    <?= Html::closeTag('h1'); ?>
    <?= $header_buttons; ?>
    <?= Html::closeTag('div'); ?>

    <?= Html::openTag('div',['id' => 'content']); ?>

        <?= Html::openTag('div',['class' => 'row']); ?>
            <?= Html::openTag('div',['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
                <?= 
                    Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.custom.value.error.summary'))
                    // if the value is left blank the 'Value cannot be blank' message will appear
                    ->onlyProperties(...['value'])    
                    ->onlyCommonErrors()   
                ?>

                <?php $alpha = str_replace("-", "_", strtolower($custom_field->getType())); ?>
                <?php  // Type eg. Boolean, Single Choice, Multiple Choice and Label eg. My new Field, 
                       //belong to the Field Entity?> 
                <?= Html::openTag('div', ['class' => 'form-group']); ?>
                    <?= Html::openTag('label', ['for' => 'label']); ?>
                        <?= $translator->translate('i.field'); ?>
                    <?= Html::closeTag('label'); ?>
                    <?= Html::openTag('input', [
                        'class' => 'form-control', 
                        'disabled' => 'disabled', 
                        'id' => 'label',
                        'value' => Html::encode($custom_field->getLabel() ?? '')
                        ]);
                    ?>
                <?= Html::closeTag('div'); ?>
    
                <?= Html::openTag('div',['class' => 'form-group']); ?>
                    <?= Html::openTag('label', ['for' => 'label']); ?>
                        <?= $translator->translate('i.type'); ?>
                    <?= Html::closeTag('label'); ?>
                    <?= Html::openTag('input', [
                        'class' => 'form-control', 
                        'disabled' => 'disabled', 
                        'id' => 'type',
                        'value' => Html::encode($s->trans($alpha) ?? '') 
                        ]);
                    ?>
                <?= Html::closeTag('div'); ?>
    
                <?php // Custom Value Form: (1) The two hidden fields
                      //                    (2) The value field where new data will be entered ?>
                <?= Html::openTag('div',['class' => 'form-group']); ?>
                    <?= Field::hidden($form, 'custom_field_id')
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'custom_field_id'])
                        ->value($custom_field->getId())
                        ->hideLabel(); 
                    ?>  
                <?= Html::closeTag('div'); ?>
    
                <?php   // The id here is generated by the new CustomValueForm(new CustomValue); function
                        // the id from the new CustomValue entity that is generated ?>
                <?= Html::openTag('div',['class' => 'form-group']); ?>
                    <?= Field::hidden($form, 'id')
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'id'])
                        ->value(Html::encode($form->getId() ??  ''))
                        ->hideLabel(); 
                    ?>    
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div',['class' => 'form-group']); ?>
                    <?= Field::text($form, 'value')
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'value'])
                        ->value(Html::encode($form->getValue() ??  '')); 
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
