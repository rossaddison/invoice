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

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('MerchantForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyCommonErrors()
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'inv')
    ->label($translator->translate('number'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->placeholder($translator->translate('successful'))
    ->value(Html::encode($form->getInv()?->getNumber() ?? $translator->translate('reason.uknown')))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'successful')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('successful'))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'date')
    ->label($translator->translate('date'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(!is_string($form->getDate()) ? ($form->getDate())->format('Y-m-d') : '')
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'driver')
    ->label($translator->translate('merchant.driver'))
    ->placeholder($translator->translate('merchant.driver'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getDriver() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'response')
    ->label($translator->translate('merchant.response'))
    ->placeholder($translator->translate('merchant.response'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getResponse() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reference')
    ->label($translator->translate('merchant.reference'))
    ->placeholder($translator->translate('merchant.reference'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getReference() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>