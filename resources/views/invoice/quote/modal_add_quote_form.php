<?php
declare(strict_types=1); 

/**
 * @see quote\modal_layout which accepts this form via 'quote\add' controller action
 */

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;



echo Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteForm')
    ->open();
?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
    <?= $translator->translate('i.create_quote'); ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div', ['id' => 'headerbar-modal-add-quote-form']); ?>
    <?= $button::save($translator); ?>
    <?= Html::openTag('div', ['class' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group' ]); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary')) 
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'client_id')
                    ->label($translator->translate('invoice.user.account.clients'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getClient_id()))
                    ->prompt($translator->translate('i.none'))
                    ->optionsData($clients)
                    ->tabIndex(1)
                    ->autofocus(true)
                    ->required(true)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>            
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'group_id')
                    ->label($translator->translate('i.quote_group'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getGroup_id() ?? $defaultGroupId))
                    ->prompt($translator->translate('i.none'))
                    ->optionsData($groups)
                    ->tabIndex(2)
                    ->required(true)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>                                       
            <?= Html::openTag('div'); ?>
                <?= Field::password($form,'password')
                    ->label($translator->translate('i.password'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getPassword()))
                    ->placeholder($translator->translate('i.password'))
                    ->tabIndex(3)
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'number')
                    ->hideLabel(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_amount')
                    ->hideLabel(true);                    
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_percent')
                    ->hideLabel(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::textarea($form,'notes')
                    ->label($translator->translate('i.note'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getNotes()))
                    ->placeholder($translator->translate('i.note'))
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form,'url_key')
                    ->disabled(true)
                    ->label($translator->translate('invoice.upload.url.key'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($urlKey));
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