<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Merchant\MerchantForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $invs
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInv
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('MerchantForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::backSave(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
?>
            <?= Html::closeTag('div'); ?>
            <?php
    /**
     * @var App\Invoice\Entity\Inv $inv
     */
    foreach ($invs as $inv) {
        $invId = $inv->getId();
        if (null !== $invId) {
            $optionsDataInv[$invId] = $inv->getNumber() ?? $translator->translate('invoice.invoice.number.no');
        }
    }
echo Field::select($form, 'inv_id')
->label($translator->translate('invoice.invoice'))
->optionsData($optionsDataInv)
->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::checkbox($form, 'successful')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('invoice.successful'))
?>        
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::date($form, 'date')
    ->label($translator->translate('i.date'))
    ->required(true)
    ->value(!is_string($form->getDate()) ? ($form->getDate())->format('Y-m-d') : '')
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'driver')
    ->label($translator->translate('invoice.merchant.driver'))
    ->placeholder($translator->translate('invoice.merchant.driver'))
    ->value(Html::encode($form->getDriver() ?? ''))
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'response')
    ->label($translator->translate('invoice.merchant.response'))
    ->placeholder($translator->translate('invoice.merchant.response'))
    ->value(Html::encode($form->getResponse() ?? ''))
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'reference')
    ->label($translator->translate('invoice.merchant.reference'))
    ->placeholder($translator->translate('invoice.merchant.reference'))
    ->value(Html::encode($form->getReference() ?? ''))
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>