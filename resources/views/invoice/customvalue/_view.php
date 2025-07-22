<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CustomValueForm')
    ->open();
?> 

<?php echo Html::openTag('h1'); ?><?php echo Html::encode($title); ?><?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
    <?php echo Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                <?php echo Field::text($form, 'value')
    ->label($translator->translate('value'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'style'    => 'background:lightblue',
        'disabled' => 'disabled',
        'id'       => 'value'])
    ->readonly(true)
    ->value(Html::encode($form->getValue()));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                <?php echo Field::text($form, 'custom_field_id')
    ->label($translator->translate('field'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'style'    => 'background:lightblue',
        'disabled' => 'disabled',
        'id'       => 'value'])
    ->readonly(true)
    ->value(Html::encode(strlen($label = $form->getCustomField()?->getLabel() ?? '') > 0 ? $label : ''));
?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo $button::back(); ?>
<?php echo Form::tag()->close(); ?>
