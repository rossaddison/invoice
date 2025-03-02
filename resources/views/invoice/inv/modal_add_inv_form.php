<?php

declare(strict_types=1); 

/**
 * @see src\Widget\Bootstrap5ModalInv renderPartialLayoutWithFormAsString $this->formParameters
 * @see inv\modal_layout which accepts this form via 'inv\add' controller action
 */

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $defaultGroupId
 * @var string $actionName
 * @var string $alert
 * @var string $csrf
 * @var string $urlKey
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $clients
 * @psalm-var array<array-key, array<array-key, string>|string> $groups
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
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
    <?= $button::save(); ?>
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
                    ->value(Html::encode($form->getGroup_id() >= 0 ? $form->getGroup_id() : $defaultGroupId))
                    ->prompt($translator->translate('i.none'))
                    ->optionsData($groups)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>                                       
            <?= Html::openTag('div'); ?>
                <?= Field::date($form,'date_created')
                    ->label($translator->translate('i.date_created'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(!is_string($form->getDate_created()) && null!==$form->getDate_created() ? 
                                                    $form->getDate_created()->format($dateHelper->style()) : ''))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_modified')
                    ->hideLabel()
                    ->label($translator->translate('i.date_modified'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(!is_string($form->getDate_modified()) && null!==$form->getDate_modified() ? 
                                                    $form->getDate_modified()->format($dateHelper->style()) : ''))
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
                    ->value(Html::encode(date('h:i:s',(!is_string($form->getTime_created()) && null!==$form->getTime_created() ? 
                                                              $form->getTime_created()->getTimestamp() : null))))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_tax_point')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.invoice.tax.point'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(!is_string($form->getDate_tax_point()) && null!==$form->getDate_tax_point() ? 
                                                    $form->getDate_tax_point()->format($dateHelper->style()) : ''));
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
                    ->value(Html::encode(!is_string($form->getDate_supplied()) && null!==$form->getDate_supplied() ? 
                                                    $form->getDate_supplied()->format($dateHelper->style()) : ''));
                ?>    
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'date_due')
                    ->hideLabel(true)
                    ->label($translator->translate('i.date_due'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode(!is_string($form->getDate_due()) && null!==$form->getDate_due() ? 
                                                    $form->getDate_due()->format($dateHelper->style()) : ''));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'number')
                    ->hideLabel(true)
                    ->label($translator->translate('i.number'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getNumber()));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_amount')
                    ->hideLabel(true)
                    ->label($translator->translate('i.discount_amount'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($s->format_amount(($form->getDiscount_amount() ?? 0.00))))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'discount_percent')
                    ->hideLabel(true)
                    ->label($translator->translate('i.discount_percent'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($s->format_amount(($form->getDiscount_percent() ?? 0.00))))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'terms')
                    ->hideLabel(true)
                    ->label($translator->translate('i.terms'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getTerms() ?? $s->getSetting('default_invoice_terms') ?: $translator->translate('invoice.payment.term.general')));                   
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
                    ->value(Html::encode($form->getUrl_key() ?? $urlKey));
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'payment_method')
                    ->hideLabel(true)
                    ->label($translator->translate('i.payment_method'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getPayment_method() ?? ($s->getSetting('invoice_default_payment_method') ?: 1)))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'contract_id')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.contract.id'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getContract_id() ?? 0))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'delivery_id')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.delivery'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getDelivery_id() ?? 0))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'delivery_location_id')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.delivery.location'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getDelivery_location_id() ?? 0))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form,'postal_address_id')
                    ->hideLabel(true)
                    ->label($translator->translate('invoice.postal.address'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->value(Html::encode($form->getPostal_address_id() ?? 0))
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