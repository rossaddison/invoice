<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 *
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Entity\InvAmount $invAmount
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Payment\PaymentForm $form
 * @var App\Invoice\PaymentCustom\PaymentCustomForm $paymentCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $customFields
 * @var array $customValues
 * @var array $errorsCustom
 * @var array $openInvs
 * @var array $paymentCustomValues
 * @var array $paymentMethods
 * @var int $openInvsCount
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string,list<string>> $errorsCustom
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataPaymentMethod
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvId
 *
 */

// If there are no invoices to make payment against give a warning
echo $alert;
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($translator->translate('payment.form')) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::backSave(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyCommonErrors()
?>
                <?= Field::errorSummary($form)
    ->errors($errorsCustom)
    ->header($translator->translate('error.summary'))
    ->onlyCommonErrors()
?>
                <?php
    $optionsDataPaymentMethod = [];
/**
 * @var App\Invoice\Entity\PaymentMethod $paymentMethod
 */
foreach ($paymentMethods as $paymentMethod) {
    $paymentMethodId = $paymentMethod->getId();
    $paymentMethodName = $paymentMethod->getName();
    if ((strlen($paymentMethodId) > 0)
        && (strlen(($paymentMethodName ?? '')) > 0) && (null !== $paymentMethodName) && ($paymentMethod->getActive())) {
        $optionsDataPaymentMethod[$paymentMethodId] = $paymentMethodName;
    }
}
echo Field::select($form, 'payment_method_id')
->label($translator->translate('payment.method'))
->optionsData($optionsDataPaymentMethod)
->hint($translator->translate('hint.this.field.is.required'));
?>
                <?php
    $optionsDataInvId = [];
if ($openInvsCount > 0) {
    /**
     * @var App\Invoice\Entity\Inv $inv
     */
    foreach ($openInvs as $inv) {
        $invAmount = $iaR->repoInvquery((int) $inv->getId());
        if (null !== $invAmount) {
            $optionsDataInvId[(int) $inv->getId()]
               = ($inv->getNumber() ?? $translator->translate('number.no'))
               . ' - '
               . ($clientHelper->format_client($cR->repoClientquery($inv->getClient_id())))
               . ' - '
               . ($numberHelper->format_currency($invAmount->getBalance()));
        }
    }
} else {
    $optionsDataInvId[0] = $translator->translate('none');
}
?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?=
        Field::select($form, 'inv_id')
        ->label($translator->translate('invoice'))
        ->optionsData($optionsDataInvId)
        ->hint($translator->translate('hint.this.field.is.required'))
?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'payment_date')
    ->label($translator->translate('date'))
    ->required(true)
    ->value($form->getPayment_date() instanceof DateTimeImmutable ? $form->getPayment_date()->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::textarea($form, 'note')
    ->label($translator->translate('note'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('note'),
        'value' => Html::encode($form->getNote() ?? ''),
        'class' => 'form-control',
        'id' => 'note',
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'amount')
    ->label($translator->translate('amount'))
    ->placeholder($translator->translate('amount'))
    ->value(Html::encode($form->getAmount() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?php
/**
 * @var App\Invoice\Entity\CustomField $customField
 */
foreach ($customFields as $customField): ?>  
                        <?php $cvH->print_field_for_form($customField, $paymentCustomForm, $translator, $paymentCustomValues, $customValues); ?>
                <?php endforeach; ?>
            <?= Html::closeTag('div'); ?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>