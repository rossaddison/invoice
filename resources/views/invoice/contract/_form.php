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
<?=
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ContractForm')
    ->open()
?>
<?= Html::openTag('div',['id' => 'headerbar']); ?>
<h1 class="headerbar-title"><?= $translator->translate('invoice.invoice.contract.add'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?>
    <?= Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('invoice.client.error.summary'))
        ->onlyCommonErrors()
    ?>
    <?= Field::text($form, 'client_id')
        ->addInputAttributes([ 
             'class' => 'form-control',
             'id' => 'client_id',
        ])
        ->readonly(true)
        ->required(true)
        ->value(Html::encode($form->getClient_id() ?? $client_id))
    ?>    
    <?= Field::text($form, 'reference')
       ->label($translator->translate('invoice.invoice.contract.reference'))
       ->addInputAttributes([
           'value' => Html::encode($form->getReference() ?? ''),
           'class' => 'form-control',
           'id' => 'reference',    
       ])
       ->required(true)
       ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::text($form, 'name')
        ->label($translator->translate('invoice.invoice.contract.name'))
        ->addInputAttributes([
            'value' => Html::encode($form->getName() ?? ''),
            'class' => 'form-control',
            'id' => 'name',    
        ])
        ->required(true)
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::text($form, 'period_start')
        ->label($translator->translate('invoice.invoice.contract.period.start'))
        ->addInputAttributes([
            'class' => 'form-control input-dm datepicker',
            'id' => 'period_start',
            'role' => 'presentation',
            'autocomplete' => 'off',
            'placeholder' => $datehelper->display()
        ])
        ->value(Html::encode(Html::encode( $datehelper->get_or_set_with_style($form->getPeriod_start() ?? new DateTimeImmutable('now')))))
        ->required(true)            
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::text($form, 'period_end')
        ->label($translator->translate('invoice.invoice.contract.period.end'))
        ->addInputAttributes([
            'class' => 'form-control input-dm datepicker',
            'id' => 'period_end',
            'role' => 'presentation',
            'autocomplete' => 'off',
            'placeholder' => $datehelper->display()
        ])
        ->value(Html::encode(Html::encode( $datehelper->get_or_set_with_style($form->getPeriod_end() ?? new DateTimeImmutable('now')))))
        ->required(true)
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
<?= Form::tag()->close(); ?>

