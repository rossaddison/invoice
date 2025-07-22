<?php

declare(strict_types=1);

/**
 * Related logic: see src\Widget\Bootstrap5ModalQuote renderPartialLayoutWithFormAsString $this->formParameters
 * Related logic: see quote\modal_layout which accepts this form via 'quote\add' controller action.
 */

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?php echo $translator->translate('create.quote'); ?>
<?php echo Html::closeTag('h1'); ?>

<?php echo Html::openTag('div', ['id' => 'headerbar-modal-add-quote-form']); ?>
    <?php echo $button::save(); ?>
    <?php echo Html::openTag('div', ['class' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('error.summary'))
    ->onlyCommonErrors();
?>
            <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::select($form, 'client_id')
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
            <?php echo Html::closeTag('div'); ?>            
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::select($form, 'group_id')
                ->label($translator->translate('quote.group'))
                ->addInputAttributes(['class' => 'form-control'])
                ->value(Html::encode($form->getGroup_id() >= 0 ? $form->getGroup_id() : 2))
                ->prompt($translator->translate('none'))
                ->optionsData($groups)
                ->tabIndex(2)
                ->required(true)
                ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>                                       
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::password($form, 'password')
                ->label($translator->translate('password'))
                ->addInputAttributes(['class' => 'form-control'])
                ->value(Html::encode($form->getPassword()))
                ->placeholder($translator->translate('password'))
                ->tabIndex(3)
                ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::hidden($form, 'number')
    ->hideLabel(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::hidden($form, 'discount_amount')
    ->hideLabel(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::hidden($form, 'discount_percent')
    ->hideLabel(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::textarea($form, 'notes')
    ->label($translator->translate('note'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getNotes()))
    ->placeholder($translator->translate('note'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'url_key')
    ->disabled(true)
    ->label($translator->translate('upload.url.key'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($urlKey));
?>
            <?php echo Html::closeTag('div'); ?>                                    
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
                
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>

<?php echo Html::closeTag('form'); ?> 