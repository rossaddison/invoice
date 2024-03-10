<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
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
                <?= Field::text($form, 'id')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('i.id'))
                    ->value(Html::encode($form->getId() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'table')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('i.table'))
                    ->value(Html::encode(ucfirst($s->lang($custom_tables[$form->getTable()] ?? ''))))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'label')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('i.label'))
                    ->value(Html::encode($form->getLabel() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'type')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('i.type'))
                    ->value(Html::encode($translator->translate('i.'.str_replace("-", "_", strtolower($form->getType())).'')))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'location')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('invoice.custom.field.location'))
                    ->value(Html::encode($form->getLocation() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'order')
                    ->addInputAttributes(['style' => 'background:lightblue'])     
                    ->label($translator->translate('i.order'))
                    ->value(Html::encode($form->getOrder() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>    
<?= Html::closeTag('div'); ?>
