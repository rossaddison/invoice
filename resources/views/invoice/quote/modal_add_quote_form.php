<?php

declare(strict_types=1);

/**
 * Related logic: see src\Widget\Bootstrap5ModalQuote renderPartialLayoutWithFormAsString $this->formParameters
 * Related logic: see quote\modal_layout which accepts this form via 'quote\add' controller action
 */

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Quote\QuoteForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var string $alert
 * @var string $csrf
 * @var string $urlKey
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $clients
 * @psalm-var array<array-key, array<array-key, string>|string> $groups
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('create.quote'); ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div', ['id' => 'headerbar-modal-add-quote-form']); ?>
    <?= $button::save(); ?>
    <?= Html::openTag('div', ['class' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group' ]); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyCommonErrors()
?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'client_id')
    ->label($translator->translate('user.account.clients'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getClient_id()))
    ->prompt($translator->translate('none'))
    ->optionsData($clients)
    ->tabIndex(1)
    ->autofocus(true)
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>            
            <?= Html::openTag('div'); ?>
                <?= Field::select($form, 'group_id')
    ->label($translator->translate('quote.group'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getGroup_id() >= 0 ? $form->getGroup_id() : 2))
    ->prompt($translator->translate('none'))
    ->optionsData($groups)
    ->tabIndex(2)
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>                                       
            <?= Html::openTag('div'); ?>
                <?= Field::password($form, 'password')
    ->label($translator->translate('password'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getPassword()))
    ->placeholder($translator->translate('password'))
    ->tabIndex(3)
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'number')
    ->hideLabel(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'discount_amount')
    ->hideLabel(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::hidden($form, 'discount_percent')
    ->hideLabel(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::textarea($form, 'notes')
    ->label($translator->translate('note'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getNotes()))
    ->placeholder($translator->translate('note'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'url_key')
    ->disabled(true)
    ->label($translator->translate('upload.url.key'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($urlKey));
?>
            <?= Html::closeTag('div'); ?>                                    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
                
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?> 