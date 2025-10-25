<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
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

<?=
    Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CustomFieldForm')
    ->open()
?>

    <?= Html::openTag('div'); ?>
    <?= Html::openTag('h1');?>
    <?= Html::encode($title); ?>
    <?=Html::closeTag('h1'); ?>
    <?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
    <?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
    <?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('custom.field.form'); ?>
    <?= Html::closeTag('h1'); ?>
        <?= $button::backSave(); ?>
    <?= Html::closeTag('div'); ?>

    <?= Html::Tag('br'); ?>
    <?= Html::Tag('br'); ?>
    <?= Html::openTag('div'); ?>    
        <?= Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>    
            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?=
                Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('custom.field.error.summary'))
                ->onlyProperties(...['table', 'label', 'location', 'type'])
                ->onlyCommonErrors()
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?= Field::select($form, 'table')
        ->label($translator->translate('table'))
        ->addInputAttributes([
            'class' => 'form-control',
            'id' => 'table',
        ])
        ->value(Html::encode($form->getTable() ?? ''))
        ->optionsData($tables);
?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?= Field::text($form, 'label')
    ->label($translator->translate('label'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('label'),
        'class' => 'form-control',
        'id' => 'label',
    ])
    ->value(Html::encode($form->getLabel() ?? ''))
?>
            <?= Html::closeTag('div'); ?>

            <?php
                $arrays = [$user_input_types, $custom_value_fields];
$types = array_merge(...$arrays);
$optionsDataType = [];
/**
 * @var string $type
 */
foreach ($types as $type) {
    $alpha = str_replace("-", ".", strtolower($type));
    $translated = $translator->translate('' . $alpha);
    if ($translated === '' || $translated === '#') {
        continue;
    }
    $optionsDataType[$type] = $translated;
}
?>    
            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?= Field::select($form, 'type')
->label($translator->translate('type'))
->addInputAttributes([
    'placeholder' => $translator->translate('type'),
    'class' => 'form-control',
    'id' => 'type',
])
->value(Html::encode($form->getType() ?? ''))
->optionsData($optionsDataType);
?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?= Field::checkbox($form, 'required')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('custom.field.required'));
?>
            <?= Html::closeTag('div'); ?>    

            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <?= Field::range($form, 'order')
    ->label($translator->translate('order'))
    ->addInputAttributes([
        'type' => 'range',
        'min' => 1,
        'max' => 20,
        'value' => Html::encode($form->getOrder() ?? ''),
        'class' => 'form-control form-range',
        'id' => 'order',
    ])
?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div', ['class' => 'form-group']); ?>
                <label for="location"><?= $translator->translate('position'); ?></label> 
                <?php $valueSelected = Html::encode($form->getLocation() ??  ''); ?>
                <select name="location" id="location" class="form-control"></select>
            <?= Html::closeTag('div'); ?>

        <?= Html::closeTag('div'); ?>

        <?= Html::closeTag('div'); ?>
        <?php
            // Normalize $positions into an array for use in the view
            // Normalize $positions into an array for use in the view
/**
 * The view may receive:
 *  - $positions as a JSON-encoded string,
 *  - or as a PHP array,
 *  - or as a Traversable (e.g. ArrayObject),
 *  - or null.
 *
 * @psalm-var array<string, list<string>>|\Traversable<string, list<string>>|string|null $positions
 * @psalm-var array|string|null $valueSelected
 */

// Normalize $positions into an array for use in the view
$positionsArray = [];

if (is_string($positions)) {
    /**
     * json_decode may return array|null|scalar. Tell Psalm the expected decoded shape.
     * @psalm-var array<string, list<string>>|null $decoded
     */
    $decoded = json_decode($positions, true);

    if (is_array($decoded)) {
        $positionsArray = $decoded;
    } else {
        // treat a plain (non-JSON) string as a single element array
        $positionsArray = [$positions];
    }
} elseif (is_array($positions)) {
    $positionsArray = $positions;
} elseif ($positions instanceof \Traversable) {
    $positionsArray = iterator_to_array($positions);
} elseif ($positions === null) {
    $positionsArray = [];
} else {
    // scalars / objects — cast to array as a last resort
    $positionsArray = (array) $positions;
}

$positionsJson = json_encode($positionsArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$valueSelectedJson = isset($valueSelected) ? json_encode($valueSelected) : 'null';

$js = <<<JS
                document.addEventListener('DOMContentLoaded', function () {
                    "use strict";

                    const jsonPositions = {$positionsJson};

                    const tableEl = document.getElementById('table');
                    const locationEl = document.getElementById('location');

                    function updatePositions(index, selKey) {
                        if (!locationEl) return;

                        locationEl.innerHTML = '';

                        const keys = Object.keys(jsonPositions);
                        if (keys.length === 0) return;

                        if (typeof index !== 'number' || index < 0 || index >= keys.length) {
                            index = 0;
                        }

                        const key = keys[index];
                        const map = jsonPositions[key] || {};

                        for (const pos in map) {
                            if (!Object.prototype.hasOwnProperty.call(map, pos)) continue;
                            const opt = document.createElement('option');
                            opt.value = pos;
                            opt.textContent = map[pos];
                            if (selKey !== undefined && selKey !== null && String(selKey) === String(pos)) {
                                opt.selected = true;
                            }
                            locationEl.appendChild(opt);
                        }
                    }

                    let optionIndex = 0;
                    if (tableEl && typeof tableEl.selectedIndex === 'number') {
                        optionIndex = tableEl.selectedIndex;
                    }

                    if (tableEl) {
                        tableEl.addEventListener('change', function () {
                            optionIndex = tableEl.selectedIndex;
                            updatePositions(optionIndex);
                        }, false);
                    }

                    updatePositions(optionIndex, {$valueSelectedJson});
                });
            JS;

echo Html::script($js)->type('module');
?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>                
<?= Form::tag()->close(); ?>

