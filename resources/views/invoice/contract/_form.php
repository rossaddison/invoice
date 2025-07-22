<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ContractForm')
    ->open();
?>

    <?php echo Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('client.error.summary'))
        ->onlyCommonErrors();
?>
    <?php echo Field::text($form, 'client_id')
    ->readonly(true)
    ->value(Html::encode($form->getClient_id() ?? $client_id));
?>    
    <?php echo Field::text($form, 'reference')
        ->label($translator->translate('contract.reference'))
        ->addInputAttributes([
            'value' => Html::encode($form->getReference() ?? ''),
        ])
        ->required(true)
        ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?php echo Field::text($form, 'name')
    ->label($translator->translate('contract.name'))
    ->addInputAttributes([
        'value' => Html::encode($form->getName() ?? ''),
    ])
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?php echo Field::date($form, 'period_start')
    ->label($translator->translate('contract.period.start'))
    ->addInputAttributes([
        'role'         => 'presentation',
        'autocomplete' => 'off',
    ])
    ->value(Html::encode(Html::encode($form->getPeriod_start()->format('Y-m-d'))))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?php echo Field::date($form, 'period_end')
    ->label($translator->translate('contract.period.end'))
    ->addInputAttributes([
        'autocomplete' => 'off',
    ])
    ->value(Html::encode(Html::encode($form->getPeriod_end()->format('Y-m-d'))))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>

<?php echo Html::closeTag('h1'); ?>
<?php echo $button::backSave(); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
