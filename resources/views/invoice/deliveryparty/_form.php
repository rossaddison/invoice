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
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryPartyForm')
    ->open() ?>

    <?= Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?= Html::openTag('h1',['class' => 'headerbar-title']); ?>
            <?= Html::encode($title); ?>
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::buttonGroup()
            ->addContainerClass('btn-group btn-toolbar float-end')
            ->buttonsData([
                [
                    $translator->translate('invoice.cancel'),
                    'type' => 'reset',
                    'class' => 'btn btn-sm btn-danger',
                    'name'=> 'btn_cancel'
                ],
                [
                    $translator->translate('invoice.submit'),
                    'type' => 'submit',
                    'class' => 'btn btn-sm btn-primary',
                    'name' => 'btn_send'
                ],
        ]) ?>
       
        <?= Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('invoice.error.summary'))
            ->onlyProperties(...['party_name'])     
            ->onlyCommonErrors();
        ?>    
        <?= Html::openTag('div',['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?= Field::text($form, 'party_name')
                ->addInputAttributes([
                    'class' => 'form-control'
                ])
                ->label($translator->translate('invoice.invoice.delivery.party.name')) 
                ->value(Html::encode($form->getParty_name() ?? '')); 
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>