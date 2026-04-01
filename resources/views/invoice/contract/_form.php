<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Contract\ContractForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $client_id
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */
?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?=
     new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ContractForm')
    ->open()
?>

    <?= Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('client.error.summary'))
        ->onlyCommonErrors()
?>
    <?= Field::text($form, 'client_id')
    ->readonly(true)
    ->value(Html::encode($form->getClientId() ?? $client_id))
?>    
    <?= Field::text($form, 'reference')
   ->label($translator->translate('contract.reference'))
   ->addInputAttributes([
       'value' => Html::encode($form->getReference() ?? ''),
   ])
   ->required(true)
   ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Field::text($form, 'name')
    ->label($translator->translate('contract.name'))
    ->addInputAttributes([
        'value' => Html::encode($form->getName() ?? ''),
    ])
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Field::date($form, 'period_start')
    ->label($translator->translate('contract.period.start'))
    ->addInputAttributes([
        'role' => 'presentation',
        'autocomplete' => 'off',
    ])
    ->value(Html::encode(Html::encode($form->getPeriodStart()->format('Y-m-d'))))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Field::date($form, 'period_end')
    ->label($translator->translate('contract.period.end'))
    ->addInputAttributes([
        'autocomplete' => 'off',
    ])
    ->value(Html::encode(Html::encode($form->getPeriodEnd()->format('Y-m-d'))))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>

<?= Html::closeTag('h1'); ?>
<?= $button::backSave(); ?>
<?=  new Form()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
