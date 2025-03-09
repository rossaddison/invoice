<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Widget\Button $button
 * @var App\Invoice\DeliveryParty\DeliveryPartyForm $form
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryPartyForm')
    ->open() ?>

<?= Html::openTag('h1'); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>            
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
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
<?= $button::backSave(); ?>
<?= Form::tag()->close() ?>