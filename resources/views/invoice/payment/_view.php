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
    <?= $button::back($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
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
                    ->addInputAttributes([
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                    ])        
                ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= 
                        Field::text($form, 'inv') 
                        ->label($translator->translate('invoice.invoice'),['control-label'])
                        ->addInputAttributes([
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                        ])
                        ->value(Html::encode($form->getInv()?->getNumber()))
                    ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'payment_date')
                        ->label($translator->translate('i.date'), ['class' => 'form-label'])
                        ->addInputAttributes([
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                        ])        
                        ->value(Html::encode($form->getPayment_date() ? ($form->getPayment_date())->format('Y-m-d') : ''))
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
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                    ])        
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'amount')
                        ->label($translator->translate('i.amount'), ['form-label'])
                        ->placeholder($translator->translate('i.amount'))
                        ->value(Html::encode($form->getAmount() ?? ''))
                        ->addInputAttributes([
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                        ])        
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?php foreach ($customFields as $customField): ?>            
                    <div class="mb-3 form-group">
                    <?php if ($customField instanceof CustomField) { ?>
                    <?= $cvH->print_field_for_view($paymentCustomValues,
                        $customField,
                        // Custom values to fill drop down list if a dropdown box has been created
                        $customValues, 
                        // Class for div surrounding input
                        'col-xs-12 col-sm-6',
                        // Class surrounding above div
                        'form-group',
                        // Label class similar to above
                        'control-label',
                        $paymentCustomForm,
                        $translator    
                        ); ?>
                    <?php } ?>    
                    </div>    
                <?php endforeach; ?>
            <?= Html::closeTag('div'); ?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>   