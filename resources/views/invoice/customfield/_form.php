<?php
declare(strict_types=1); 


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 */
?>

<?=
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CustomFieldForm')
    ->open()
    ?>

    <?= Html::openTag('div'); ?>
    <?= Html::openTag('h1');?>
    <?= Html::encode($title); ?>
    <?=Html::closeTag('h1'); ?>
    <?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
    <?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
    <?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
    <?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
    <?= Html::openTag('div',['class'=>'card-header']); ?>
    <?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
    <?= $translator->translate('i.custom_field_form'); ?>
    <?= Html::closeTag('h1'); ?>
        <?= $button::back_save(); ?>
    <?= Html::closeTag('div'); ?>

    <?= Html::Tag('br'); ?>
    <?= Html::Tag('br'); ?>
    <?= Html::openTag('div'); ?>    
        <?= Html::openTag('div' ,['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>    
            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?=
                    Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.custom.field.error.summary'))
                    ->onlyProperties(...['table', 'label', 'location', 'type'])    
                    ->onlyCommonErrors()   
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?= Field::select($form, 'table')
                        ->label($translator->translate('i.table'))
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'table'
                        ])
                        ->value(Html::encode($form->getTable() ?? ''))    
                        ->optionsData($tables);    
                ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?= Field::text($form, 'label')
                    ->label($translator->translate('i.label'), ['class' => 'form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.label') ?? 'Label',
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
                foreach ($types as $type) { 
                        $alpha = str_replace("-", "_", strtolower($type)); 
                        $optionsDataType[$type] = (null!==($translator->translate('i.'.$alpha)) ? $translator->translate('i.'.$alpha) : $translator->translate('invoice.custom.field.number'));
                }        
            ?>    
            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?= Field::select($form, 'type')
                        ->label($translator->translate('i.type') , ['class' => 'form-label'])
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.type'),
                            'class' => 'form-control',
                            'id' => 'type'
                        ])
                        ->value(Html::encode($form->getType() ?? ''))    
                        ->optionsData($optionsDataType);    
                ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?= Field::checkbox($form, 'required')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.custom.field.required'));
                ?>
            <?= Html::closeTag('div'); ?>    

            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <?= Field::range($form, 'order')
                    ->label($translator->translate('i.order'))
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

            <?= Html::openTag('div',['class' => 'form-group']); ?>
                <label for="location"><?= $translator->translate('i.position'); ?></label> 
                <?php $valueSelected = Html::encode($form->getLocation() ??  ''); ?>
                <select name="location" id="location" class="form-control"></select>
            <?= Html::closeTag('div'); ?>

        <?= Html::closeTag('div'); ?>

        <?= Html::closeTag('div'); ?>
        <?php
        // double dropdown box
        $js2 = "$(function () {"."\n".
               "var jsonPositions ='".$positions."';"."\n".
               "jsonPositions = JSON.parse(jsonPositions);"."\n". 
               "function updatePositions(index, selKey) {"."\n".
                    '$("#location option").remove();'."\n".
                    "var pos = 0;"."\n".
                    "var key = Object".'.'.'keys(jsonPositions)[index];'."\n".
                    'for (pos in jsonPositions[key]) {'."\n".
                       'var opt = $("<'."option".'>");'."\n".
                       'opt.attr("value", pos);'."\n".
                       'opt.text(jsonPositions[key][pos]);'."\n".
                       'if (selKey == pos) {'."\n".
                          'opt.attr("selected", "selected");'."\n".
                       "}"."\n".
                       '$("#location").append(opt);'."\n".
                    '}'."\n".
                "}"."\n".
                'var optionIndex = $("#table option:selected").index();'."\n".
                '$("#table").on("change", function () {'."\n".
                'optionIndex = $("#table option:selected").index();'."\n".
                'updatePositions(optionIndex);'."\n".
                '});'."\n".
                'updatePositions(optionIndex,'. $valueSelected. ');'.
                '});';
        echo Html::script($js2)->type('module');
    ?> 
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>                
<?= Form::tag()->close(); ?>

