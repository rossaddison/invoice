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
                <?= Field::text($form, 'party_name')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.invoice.delivery.party.name'))
                    ->value(Html::encode($form->getParty_name() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
