<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\AllowanceCharge\AllowanceChargeForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?php echo Html::openTag('h1'); ?>
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open(); ?>
<?php echo Html::openTag('div'); ?>
    <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'identifier')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge'))
    ->value(Html::encode(true == $form->getIdentifier()
    ? $translator->translate('allowance.or.charge.charge')
    : $translator->translate('allowance.or.charge.allowance')))
    ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'reason_code')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.reason.code'))
    ->value(Html::encode($form->getReasonCode() ?? ''))
    ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'reason')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.reason'))
    ->value(Html::encode($form->getReason() ?? ''))
    ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'multiplier_factor_numeric')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.multiplier.factor.numeric'))
    ->value(Html::encode($form->getMultiplierFactorNumeric() ?? ''))
    ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'base_amount')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.amount'))
    ->value(Html::encode($form->getBaseAmount() ?? ''))
    ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>    
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo $button::back(); ?>
<?php echo Form::tag()->close(); ?>