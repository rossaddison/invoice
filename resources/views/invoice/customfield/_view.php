<?php

declare(strict_types=1); 

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
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('id'))
                    ->value(Html::encode($form->getId() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'table')
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('table'))
                    ->value(Html::encode($form->getTable() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'label')
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('label'))
                    ->value(Html::encode($form->getLabel() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'type')
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('type'))
                    ->value(Html::encode($form->getType() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'location')
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('location'))
                    ->value(Html::encode($form->getLocation() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'order')
                    ->addInputAttribute(['style' => 'background:lightblue'])     
                    ->label($s->trans('order'))
                    ->value(Html::encode($form->getOrder() ?? ''))     
                    ->readonly(true);
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>    
<?= Html::closeTag('div'); ?>
