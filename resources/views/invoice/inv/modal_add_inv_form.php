<?php

declare(strict_types=1);

/**
 * Related logic: see src\Widget\Bootstrap5ModalInv renderPartialLayoutWithFormAsString $this->formParameters
 * Related logic: see inv\modal_layout which accepts this form via 'inv\add' controller action
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

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('create.invoice'); ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div', ['id' => 'headerbar-modal-add-inv-form']); ?>
    <?= $button::save(); ?>
    <?= Html::openTag('div', ['class' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>        
            <?= Html::openTag('div', ['class' => 'mb-3 form-group' ]); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyCommonErrors()
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= $button::defaultPaymentMethod($urlGenerator, $translator); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'client_id')
    ->label($translator->translate('user.account.clients'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getClient_id()))
    ->prompt($translator->translate('none'))
    ->optionsData($clients)
    ->hint($translator->translate('hint.this.field.is.required'));

?>
            <?= Html::closeTag('div'); ?>            
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'group_id')
    ->label($translator->translate('group'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($defaultGroupId ?: 0)
    ->prompt($translator->translate('none'))
    ->optionsData($groups)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>                                       
            <?= Html::openTag('div'); ?>
                <?= Field::date($form, 'date_created')
    ->label($translator->translate('date.created'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(!is_string($form->getDate_created()) && null !== $form->getDate_created() ?
                                    $form->getDate_created()->format('Y-m-d') : ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'date_modified')
    ->hideLabel()
    ->label($translator->translate('date.modified'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(!is_string($form->getDate_modified()) && null !== $form->getDate_modified() ?
                                    $form->getDate_modified()->format('Y-m-d') : ''))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::password($form, 'password')
    ->label($translator->translate('password'))
    ->addInputAttributes([
        'class' => 'form-control',
        'autocomplete' => 'current-password',
    ])
    ->value(Html::encode($form->getPassword()))
    ->placeholder($translator->translate('password'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'time_created')
    ->label($translator->translate('time.created'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(date('h:i:s', (!is_string($form->getTime_created()) && null !== $form->getTime_created() ?
                                              $form->getTime_created()->getTimestamp() : null))))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'date_tax_point')
    ->hideLabel(true)
    ->label($translator->translate('tax.point'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(!is_string($form->getDate_tax_point()) && null !== $form->getDate_tax_point() ?
                                    $form->getDate_tax_point()->format('Y-m-d') : ''));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'stand_in_code')
    ->hideLabel(true)
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getStand_in_code()))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'date_supplied')
    ->hideLabel(true)
    ->label($translator->translate('date.supplied'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(!is_string($form->getDate_supplied()) && null !== $form->getDate_supplied() ?
                                    $form->getDate_supplied()->format('Y-m-d') : ''));
?>    
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'date_due')
    ->hideLabel(true)
    ->label($translator->translate('date.due'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode(!is_string($form->getDate_due()) && null !== $form->getDate_due() ?
                                    $form->getDate_due()->format('Y-m-d') : ''));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'number')
    ->hideLabel(true)
    ->label($translator->translate('number'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getNumber()));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'discount_amount')
    ->hideLabel(true)
    ->label($translator->translate('discount.amount'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($s->format_amount(($form->getDiscount_amount() ?? 0.00))))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'discount_percent')
    ->hideLabel(true)
    ->label($translator->translate('discount.percent'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($s->format_amount(($form->getDiscount_percent() ?? 0.00))))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'terms')
    ->hideLabel(true)
    ->label($translator->translate('terms'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getTerms() ?? $s->getSetting('default_invoice_terms') ?: $translator->translate('payment.term.general')));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::textarea($form, 'note')
    ->label($translator->translate('note'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getNote()))
    ->placeholder($translator->translate('note'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'document_description')
    ->label($translator->translate('description.document'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getDocumentDescription()))
    ->placeholder($translator->translate('description.document'))
    ->hint($translator->translate('hint.this.field.is.not.required'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'url_key')
    ->hideLabel(true)
    ->label($translator->translate('url.key'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getUrl_key() ?? $urlKey));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'payment_method')
    ->hideLabel(true)
    ->label($translator->translate('payment.method'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getPayment_method() ?? ($s->getSetting('invoice_default_payment_method') ?: 1)))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'contract_id')
    ->hideLabel(true)
    ->label($translator->translate('contract.id'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getContract_id() ?? 0))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'delivery_id')
    ->hideLabel(true)
    ->label($translator->translate('delivery'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getDelivery_id() ?? 0))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'delivery_location_id')
    ->hideLabel(true)
    ->label($translator->translate('delivery.location'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getDelivery_location_id() ?? 0))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'postal_address_id')
    ->hideLabel(true)
    ->label($translator->translate('postal.address'))
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