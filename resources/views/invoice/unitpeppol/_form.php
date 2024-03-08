<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
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
    <?= $button::back_save(); ?>
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
                        ->label($translator->translate('i.id'), ['class' => 'form-label'])
                        ->optionsData($optionsDataUnits)
                        ->value(Html::encode($form->getUnit_id() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'name')
                        ->label($translator->translate('i.name'), ['class' => 'form-label'])
                        ->value(Html::encode($form->getName() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'code')
                        ->label($translator->translate('invoice.unit.peppol.code'), ['class' => 'form-label'])
                        ->optionsData($optionsDataEneces)
                        ->value(Html::encode($form->getCode() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'description')
                        ->label($translator->translate('i.description'), ['class' => 'form-label'])
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
                            <?php foreach ($eneces as $key => $value) {
                                $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
                                echo Html::openTag('tr');
                                    echo Html::openTag('td');
                                        echo $eneces[$key]['Id'];
                                    echo Html::closeTag('td');
                                echo Html::closeTag('tr');
                                echo Html::openTag('tr');
                                    echo Html::openTag('td');
                                        echo $eneces[$key]['Name'];
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
