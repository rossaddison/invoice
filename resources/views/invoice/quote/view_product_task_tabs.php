<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;

/** 
 * @var App\Invoice\Entity\Quote $quote 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $invEdit
 * @var string $add_quote_product
 * @var string $add_quote_task
 */ 

?>
<?php if ($invEdit && $quote->getStatus_id() === 1) { ?>
<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.product')))
        ->href('#add-product-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li'); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.task')))
        ->href('#add-task-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?= Html::closeTag('li'); ?> 
    <?= Html::openTag('li', ['id' => 'back', 'class' => 'tab-pane']); ?>
        <?= A::tag()
        ->addAttributes([
            'type' => 'reset',
            'onclick' => 'window.history.back()',
            'value' => '1',
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-danger bi bi-arrow-left')
        ->id('back')
        ->render(); ?>
    <?= Html::closeTag('li'); ?>    
<?= Html::closeTag('ul'); ?>
    
<?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>
    <?= Html::openTag('div', ['class' => 'tab-content']); ?>
        <?= Html::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']); ?>
            <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                <?= Html::openTag('div'); ?>
                    <?= Html::openTag(
                            'button',
                            [
                                'class' => 'btn btn-primary',
                                'href' => '#modal-choose-items',
                                'id' => '#modal-choose-items',
                                'data-bs-toggle' => 'modal',
                            ],
                        );
                    ?>
                    <?= I::tag()
                        ->addClass('fa fa-list')
                        ->addAttributes([
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate('add.product'),
                        ]);
                    ?>
                    <?= $translator->translate('add.product'); ?>
                    <?= Html::closeTag('button'); ?>
                <?= Html::closeTag('div'); ?>
                <?= $add_quote_product; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['id' => 'add-task-tab', 'class' => 'tab-pane']); ?>
            <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                <?= Html::openTag('div'); ?>
                    <?= Html::openTag('button', [
                        'class' => 'btn btn-primary bi bi-ui-checks w-100',
                        'data-bs-target' => '#modal-choose-tasks-quote',
                        'id' => 'btn-choose-tasks-quote',
                        'data-bs-toggle' => 'modal']);
                    ?>
                    <?= $translator->translate('add.task'); ?>
                    <?= Html::closeTag('button'); ?>
                <?= Html::closeTag('div'); ?>
                <?= $add_quote_task; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?php } ?>