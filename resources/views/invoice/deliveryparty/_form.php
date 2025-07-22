<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryPartyForm')
    ->open(); ?>

    <?php echo Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?php echo Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?php echo Html::encode($title); ?>
        <?php echo Html::closeTag('h1'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('error.summary'))
            ->onlyProperties(...['party_name'])
            ->onlyCommonErrors();
?>    
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php echo Field::text($form, 'party_name')
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->label($translator->translate('delivery.party.name'))
            ->value(Html::encode($form->getParty_name() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo $button::backSave(); ?>
<?php echo Form::tag()->close(); ?>