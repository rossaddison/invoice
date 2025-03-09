<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\CustomValue\CustomValueForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
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
    ->id('CustomValueForm')
    ->open()
?> 

<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <?= Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                <?= Field::text($form, 'value')
                    ->label($translator->translate('i.value'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'style' => 'background:lightblue',
                        'disabled' => 'disabled',
                        'id' => 'value'])
                    ->readonly(true)
                    ->value(Html::encode($form->getValue()));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                <?= Field::text($form, 'custom_field_id')
    ->label($translator->translate('i.field'))
    ->addInputAttributes([
        'class' => 'form-control',
        'style' => 'background:lightblue',
        'disabled' => 'disabled',
        'id' => 'value'])
    ->readonly(true)
    ->value(Html::encode(strlen($label = $form->getCustomField()?->getLabel() ?? '') > 0 ? $label : ''));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= $button::back(); ?>
<?= Form::tag()->close(); ?>
