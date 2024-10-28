<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Widget\Button $button
 * @var App\Invoice\UnitPeppol\UnitPeppolForm $form
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\View\View $this
 * @var array $eneces
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataUnits
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataEneces
 */
?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UnitPeppolForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::backSave(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::hidden($form, 'id')
                        ->hideLabel(true)
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'unit_id')
                        ->label($translator->translate('i.id'))
                        ->optionsData($optionsDataUnits)
                        ->value(Html::encode($form->getUnit_id() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'name')
                        ->label($translator->translate('i.name'))
                        ->value(Html::encode($form->getName() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'code')
                        ->label($translator->translate('invoice.unit.peppol.code'))
                        ->optionsData($optionsDataEneces)
                        ->value(Html::encode($form->getCode() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'description')
                        ->label($translator->translate('i.description'))
                        ->value(Html::encode($form->getDescription() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <!-- https://dev.to/dcodeyt/creating-beautiful-html-tables-with-css-428l
                    class styled-table found at C:\wamp64\www\yii3-i\src\Invoice\Asset\invoice\css\yii3i.css
                    --> 
                    <?= Html::openTag('table', ['class' => 'styled-table']); ?>
                        <?= Html::openTag('thead'); ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('i.id'); ?>
                                <?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('i.name'); ?>
                                <?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('i.description'); ?>
                                <?= Html::closeTag('th'); ?>
                            <?= Html::closeTag('tr'); ?>
                        <?= Html::closeTag('thead'); ?>
                        <?= Html::openTag('tbody'); ?>
                            <?php 
                            /**
                             * @var string $key
                             * @var string $value
                             */
                             foreach ($eneces as $key => $value) {
                                /**
                                 * @var array $eneces[$key] 
                                 * @var string $eneces[$key]['Description']
                                 */ 
                                $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
                                echo Html::openTag('tr');
                                    echo Html::openTag('td');
                                        echo (string)$eneces[$key]['Id'];
                                    echo Html::closeTag('td');
                                echo Html::closeTag('tr');
                                echo Html::openTag('tr');
                                    echo Html::openTag('td');
                                        echo (string)$eneces[$key]['Name'];
                                    echo Html::closeTag('td');
                                echo Html::closeTag('tr');
                                echo Html::openTag('tr');
                                    echo Html::openTag('td');
                                        echo $description;
                                    echo Html::closeTag('td');
                                echo Html::closeTag('tr');
                            } ?>
                        <?= Html::closeTag('tbody'); ?>
                    <?= Html::closeTag('table'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
