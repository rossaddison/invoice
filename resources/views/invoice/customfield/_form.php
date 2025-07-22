<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\CustomField\CustomFieldForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $custom_value_fields
 * @var array $user_input_types
 * @var string $actionName
 * @var string $csrf
 * @var string $positions
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $tables
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CustomFieldForm')
    ->open();
?>

    <?php echo Html::openTag('div'); ?>
    <?php echo Html::openTag('h1'); ?>
    <?php echo Html::encode($title); ?>
    <?php echo Html::closeTag('h1'); ?>
    <?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
    <?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
    <?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
    <?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
    <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?php echo $translator->translate('custom.field.form'); ?>
    <?php echo Html::closeTag('h1'); ?>
        <?php echo $button::backSave(); ?>
    <?php echo Html::closeTag('div'); ?>

    <?php echo Html::Tag('br'); ?>
    <?php echo Html::Tag('br'); ?>
    <?php echo Html::openTag('div'); ?>    
        <?php echo Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>    
            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('custom.field.error.summary'))
                ->onlyProperties(...['table', 'label', 'location', 'type'])
                ->onlyCommonErrors();
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::select($form, 'table')
    ->label($translator->translate('table'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id'    => 'table',
    ])
    ->value(Html::encode($form->getTable() ?? ''))
    ->optionsData($tables);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::text($form, 'label')
                ->label($translator->translate('label'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('label'),
                    'class'       => 'form-control',
                    'id'          => 'label',
                ])
                ->value(Html::encode($form->getLabel() ?? ''));
?>
            <?php echo Html::closeTag('div'); ?>

            <?php
$arrays          = [$user_input_types, $custom_value_fields];
$types           = array_merge(...$arrays);
$optionsDataType = [];
/**
 * @var string $type
 */
foreach ($types as $type) {
    $alpha                  = str_replace('-', '_', strtolower($type));
    $optionsDataType[$type] = $translator->translate(''.$alpha);
}
?>    
            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::select($form, 'type')
                ->label($translator->translate('type'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('type'),
                    'class'       => 'form-control',
                    'id'          => 'type',
                ])
                ->value(Html::encode($form->getType() ?? ''))
                ->optionsData($optionsDataType);
?>
            <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::checkbox($form, 'required')
                ->inputLabelAttributes(['class' => 'form-check-label'])
                ->inputClass('form-check-input')
                ->ariaDescribedBy($translator->translate('custom.field.required'));
?>
            <?php echo Html::closeTag('div'); ?>    

            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <?php echo Field::range($form, 'order')
                ->label($translator->translate('order'))
                ->addInputAttributes([
                    'type'  => 'range',
                    'min'   => 1,
                    'max'   => 20,
                    'value' => Html::encode($form->getOrder() ?? ''),
                    'class' => 'form-control form-range',
                    'id'    => 'order',
                ]);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                <label for="location"><?php echo $translator->translate('position'); ?></label> 
                <?php $valueSelected = Html::encode($form->getLocation() ?? ''); ?>
                <select name="location" id="location" class="form-control"></select>
            <?php echo Html::closeTag('div'); ?>

        <?php echo Html::closeTag('div'); ?>

        <?php echo Html::closeTag('div'); ?>
        <?php
        // double dropdown box
        $js2 = '$(function () {'."\n".
               "var jsonPositions ='".$positions."';\n".
               'jsonPositions = JSON.parse(jsonPositions);'."\n".
               'function updatePositions(index, selKey) {'."\n".
    '$("#location option").remove();'."\n".
    'var pos = 0;'."\n".
    'var key = Object.keys(jsonPositions)[index];'."\n".
    'for (pos in jsonPositions[key]) {'."\n".
       'var opt = $("<option>");'."\n".
       'opt.attr("value", pos);'."\n".
       'opt.text(jsonPositions[key][pos]);'."\n".
       'if (selKey == pos) {'."\n".
          'opt.attr("selected", "selected");'."\n".
       '}'."\n".
       '$("#location").append(opt);'."\n".
    '}'."\n".
'}'."\n".
'var optionIndex = $("#table option:selected").index();'."\n".
'$("#table").on("change", function () {'."\n".
'optionIndex = $("#table option:selected").index();'."\n".
'updatePositions(optionIndex);'."\n".
'});'."\n".
'updatePositions(optionIndex,'.$valueSelected.');'.
'});';
echo Html::script($js2)->type('module');
?> 
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>                
<?php echo Form::tag()->close(); ?>

