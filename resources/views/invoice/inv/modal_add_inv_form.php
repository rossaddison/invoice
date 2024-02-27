<?php
declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

echo Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvForm')
    ->open();
?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
    <?= $translator->translate('i.create_invoice'); ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div', ['id' => 'headerbar-modal-add-inv-form']); ?>
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
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 

                ?>
            <?= Html::closeTag('div'); ?>            
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'group_id')
                    ->label($translator->translate('i.invoice_group'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getGroup_id() ?? $defaultGroupId))
                    ->prompt($translator->translate('i.none'))
                    ->optionsData($groups)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>                                       
            <?= Html::openTag('div'); ?>
                <?= Field::date($form,'date_created')
                    ->label($translator->translate('i.date_created'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(($form->getDate_created())->format($datehelper->style())))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_modified')
                    ->hideLabel()
                    ->label($translator->translate('i.date_modified'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(($form->getDate_modified())->format($datehelper->style())))
                    //->hint($translator->translate('invoice.hint.this.field.is.required')); 
               ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::password($form,'password')
                    ->label($translator->translate('i.password'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getPassword()))
                    ->placeholder($translator->translate('i.password'))
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form,'time_created')
                    ->label($translator->translate('invoice.time.created'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(date('h:i:s',($form->getTime_created())->getTimestamp())))
                    //->placeholder($translator->translate('invoice.time.created')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_tax_point')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.invoice.tax.point'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(($form->getDate_tax_point())->format($datehelper->style())))
                    //->placeholder($translator->translate('invoice.invoice.tax.point')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'stand_in_code')
                    ->hideLabel(true)
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getStand_in_code()))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_supplied')
                    ->hideLabel(true)
                    ->label($translator->translate('i.date_supplied'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(($form->getDate_supplied())->format($datehelper->style())))
                    //->placeholder($translator->translate('i.date_supplied')); 
                    //->hint($translator->translate('invoice.hint.this.field.is.not.required'));
                ?>    
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_due')
                    ->hideLabel(true)
                    ->label($translator->translate('i.date_due'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(($form->getDate_due())->format($datehelper->style())))
                    //->placeholder($translator->translate('i.date_due')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'number')
                    ->hideLabel(true)
                    ->label($translator->translate('i.number'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getNumber()))
                    //->placeholder($translator->translate('i.number')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_amount')
                    ->hideLabel(true)
                    ->label($translator->translate('i.discount_amount'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($s->format_amount((float)($form->getDiscount_amount() ?? 0.00))))
                    //->placeholder($translator->translate('i.discount_amount')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_percent')
                    ->hideLabel(true)
                    ->label($translator->translate('i.discount_percent'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($s->format_amount((float)($form->getDiscount_percent() ?? 0.00))))
                    //->placeholder($translator->translate('i.discount_percent')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'terms')
                    ->hideLabel(true)
                    ->label($translator->translate('i.terms'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getTerms() ?? $s->get_setting('default_invoice_terms') ?: $translator->translate('invoice.payment.term.general')))
                    //->placeholder($translator->translate('i.terms'));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::textarea($form,'note')
                    ->label($translator->translate('i.note'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getNote()))
                    ->placeholder($translator->translate('i.note'))
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form,'document_description')
                    ->label($translator->translate('invoice.invoice.description.document'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getDocumentDescription()))
                    ->placeholder($translator->translate('invoice.invoice.description.document')) 
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')) 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'url_key')
                    ->hideLabel(true)
                    ->label($translator->translate('i.url_key'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getUrl_key() ?? $urlKey))
                    //->placeholder($translator->translate('i.url_key'));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'payment_method')
                    ->hideLabel(true)
                    ->label($translator->translate('i.payment_method'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getPayment_method() ?? ($s->get_setting('invoice_default_payment_method') ?: 1)))
                    //->placeholder($translator->translate('i.payment_method'));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'contract_id')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.contract.id'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getContract_id() ?? 0))
                    //->placeholder($translator->translate('i.payment_method'));
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