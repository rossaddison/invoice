<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\DeliveryParty\DeliveryPartyForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryPartyForm')
    ->open() ?>

    <?= Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title); ?>
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('error.summary'))
            ->onlyProperties(...['party_name'])
            ->onlyCommonErrors();
?>    
        <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?= Field::text($form, 'party_name')
        ->addInputAttributes([
            'class' => 'form-control',
        ])
        ->label($translator->translate('delivery.party.name'))
        ->value(Html::encode($form->getParty_name() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= $button::backSave(); ?>
<?= Form::tag()->close() ?>