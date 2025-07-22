<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('MerchantForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::backSave(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('error.summary'))
        ->onlyCommonErrors();
?>
            <?php echo Html::closeTag('div'); ?>
            <?php
    /**
     * @var App\Invoice\Entity\Inv $inv
     */
    foreach ($invs as $inv) {
        $invId = $inv->getId();
        if (null !== $invId) {
            $optionsDataInv[$invId] = $inv->getNumber() ?? $translator->translate('number.no');
        }
    }
echo Field::select($form, 'inv_id')
    ->label($translator->translate('invoice'))
    ->optionsData($optionsDataInv)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::checkbox($form, 'successful')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('successful'));
?>        
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::date($form, 'date')
                ->label($translator->translate('date'))
                ->required(true)
                ->value(!is_string($form->getDate()) ? ($form->getDate())->format('Y-m-d') : '')
                ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'driver')
    ->label($translator->translate('merchant.driver'))
    ->placeholder($translator->translate('merchant.driver'))
    ->value(Html::encode($form->getDriver() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'response')
    ->label($translator->translate('merchant.response'))
    ->placeholder($translator->translate('merchant.response'))
    ->value(Html::encode($form->getResponse() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'reference')
    ->label($translator->translate('merchant.reference'))
    ->placeholder($translator->translate('merchant.reference'))
    ->value(Html::encode($form->getReference() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>