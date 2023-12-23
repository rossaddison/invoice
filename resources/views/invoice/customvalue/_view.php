<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $title
 */

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div',['class' => 'row']); ?>
    <?= Html::openTag('div',['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?= 
                Field::errorSummary($form)
               ->errors($errors)
               ->header($translator->translate('invoice.custom.value.error.summary'))
               ->onlyCommonErrors()   
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
                <?= Field::text($form, 'value')
                    ->label($s->trans('value'))    
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'style' => 'background:lightblue',
                        'disabled' => 'disabled',
                        'id' => 'value'])
                    ->readonly(true)    
                    ->value(Html::encode($form->getValue())); 
                ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
                <?= Field::text($form, 'custom_field_id')
                    ->label($s->trans('custom_field'))    
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'style' => 'background:lightblue',
                        'disabled' => 'disabled',
                        'id' => 'value'])
                    ->readonly(true)    
                    ->value(Html::encode($customvalue->getCustomField()->getId())); 
                ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
