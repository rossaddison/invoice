<?php
declare(strict_types=1); 

use App\Invoice\Entity\CustomField;

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

// If there are no invoices to make payment against give a warning
echo $alert;
?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($translator->translate('i.payment_form')) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
                <?= Field::errorSummary($form)
                    ->errors($errors_custom)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
                <?php 
                    $optionsDataPaymentMethod = [];
                    foreach ($paymentMethods as $paymentMethod) { 
                        $optionsDataPaymentMethod[(int)$paymentMethod->getId()] = $paymentMethod->getName();                    
                    }
                    echo Field::select($form, 'payment_method_id')
                    ->label($translator->translate('i.payment_method'),['control-label'])
                    ->optionsData($optionsDataPaymentMethod)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
                <?php
                    $optionsDataInvId = [];
                    if ($open_invs_count > 0) {
                        foreach ($open_invs as $inv) { 
                            $inv_amount = $iaR->repoInvquery((int)$inv->getId());
                            $optionsDataInvId[(int)$inv->getId()] = 
                               $inv->getNumber() . 
                               ' - ' . 
                               $clienthelper->format_client($cR->repoClientquery($inv->getClient_id())) . 
                               ' - ' . 
                               $numberhelper->format_currency($inv_amount->getBalance());
                        }                
                    } else {
                        $optionsDataInvId[0] = $translator->translate('i.none');
                    }
                ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= 
                        Field::select($form, 'inv_id') 
                        ->label($translator->translate('invoice.invoice'),['control-label'])
                        ->optionsData($optionsDataInvId)
                        ->hint($translator->translate('invoice.hint.this.field.is.required'))
                    ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'payment_date')
                        ->label($translator->translate('i.date'), ['class' => 'form-label'])
                        ->required(true)
                        ->value($form->getPayment_date() ? ($form->getPayment_date())->format('Y-m-d') : '')
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::textarea($form, 'note')
                        ->label($translator->translate('i.note'), ['form-label'])
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.note'),
                            'value' => Html::encode($form->getNote() ?? ''),
                            'class' => 'form-control',
                            'id' => 'note',    
                        ])
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'amount')
                        ->label($translator->translate('i.amount'), ['form-label'])
                        ->placeholder($translator->translate('i.amount'))
                        ->value(Html::encode($form->getAmount() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?php foreach ($custom_fields as $custom_field): ?>  
                    <?php if ($custom_field instanceof CustomField) { ?>
                    <?= $cvH->print_field_for_form($custom_field, $paymentCustomForm, $translator, $payment_custom_values, $custom_values); ?>
                    <?php } ?>                        
                <?php endforeach; ?>
            <?= Html::closeTag('div'); ?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
<?php foreach ($errors_custom as $error_custom) {
  echo \Yiisoft\VarDumper\VarDumper::dump($errors_custom);
}
?>