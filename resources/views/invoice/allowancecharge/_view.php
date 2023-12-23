<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var string $title
 */
?>
<?= Html::openTag('h1'); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
        <?= Html::openTag('div',['class' => 'row']); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'identifier')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.allowance.or.charge'))
                    ->value(Html::encode($form->getIdentifier() === '1' 
                    ? $translator->translate('invoice.invoice.allowance.or.charge.charge') 
                    : $translator->translate('invoice.invoice.allowance.or.charge.allowance')))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reason_code')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.allowance.or.charge.reason.code'))
                    ->value(Html::encode($form->getReason_code() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reason')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.allowance.or.charge.reason'))
                    ->value(Html::encode($form->getReason() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'multiplier_factor_numeric')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'))
                    ->value(Html::encode($form->getMultiplier_factor_numeric() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'base_amount')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.allowance.or.charge.amount'))
                    ->value(Html::encode($form->getBase_amount() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
