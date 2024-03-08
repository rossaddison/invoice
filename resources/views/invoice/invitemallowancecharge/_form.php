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

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvItemAllowanceChargeForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1');?>
            <?= Html::encode($title); ?>
        <?=Html::closeTag('h1'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'input-group']); ?>
        <?php 
            $optionsDataAllowanceCharge = [];
            foreach ($allowance_charges as $allowance_charge) 
            {
                $optionsDataAllowanceCharge[$allowance_charge->getId()] =
                ($allowance_charge->getIdentifier() 
                ? $translator->translate('invoice.invoice.allowance.or.charge.charge')
                : $translator->translate('invoice.invoice.allowance.or.charge.allowance')) 
                . ' ' . $allowance_charge->getReason()
                . ' ' . $allowance_charge->getReason_code()
                . ' '. $allowance_charge->getTaxRate()->getTax_rate_name()
                . ' ' . $translator->translate('invoice.invoice.allowance.or.charge.allowance');        
            }
        ?>
        <?= Field::select($form, 'allowance_charge_id')
            ->label($translator->translate('invoice.invoice.allowance.or.charge.item') )    
            ->addInputAttributes(['class' => 'form-control'])
            ->optionsData($optionsDataAllowanceCharge)
            ->value($form->getAllowance_charge_id())                
            ->prompt($translator->translate('i.none'))    
            ->hint($translator->translate('invoice.hint.this.field.is.required'));    
        ?>
        <?= Field::text($form, 'amount')
            ->label($translator->translate('i.amount').'('.$s->get_setting('currency_symbol').')')
            ->addInputAttributes(['class' => 'form-control'])    
            ->value($s->format_amount((float)($form->getAmount() ?? 0.00)))    
            ->hint($translator->translate('invoice.hint.this.field.is.required'));
        ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?> 

<?= $button::back_save(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
