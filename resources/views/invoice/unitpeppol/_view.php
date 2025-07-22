<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Widget\Button $button
 * @var App\Invoice\UnitPeppol\UnitPeppolForm $form
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\View\View $this
 * @var array $eneces
 * @var string $actionName
 * @var string $action
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataUnits
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataEneces
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UnitPeppolForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::hidden($form, 'id')
        ->hideLabel(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::select($form, 'unit_id')
    ->label($translator->translate('id'))
    ->optionsData($optionsDataUnits)
    ->value(Html::encode($form->getUnit_id() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->value(Html::encode($form->getName() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::select($form, 'code')
    ->label($translator->translate('unit.peppol.code'))
    ->optionsData($optionsDataEneces)
    ->value(Html::encode($form->getCode() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'description')
    ->label($translator->translate('description'))
    ->value(Html::encode($form->getDescription() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <!-- https://dev.to/dcodeyt/creating-beautiful-html-tables-with-css-428l
                    class styled-table found at C:\wamp64\www\yii3-i\src\Invoice\Asset\invoice\css\yii3i.css
                    --> 
                    <?php echo Html::openTag('table', ['class' => 'styled-table']); ?>
                        <?php echo Html::openTag('thead'); ?>
                            <?php echo Html::openTag('tr'); ?>
                                <?php echo Html::openTag('th'); ?>
                                    <?php $translator->translate('id'); ?>
                                <?php echo Html::closeTag('th'); ?>
                                <?php echo Html::openTag('th'); ?>
                                    <?php $translator->translate('name'); ?>
                                <?php echo Html::closeTag('th'); ?>
                                <?php echo Html::openTag('th'); ?>
                                    <?php $translator->translate('description'); ?>
                                <?php echo Html::closeTag('th'); ?>
                            <?php echo Html::closeTag('tr'); ?>
                        <?php echo Html::closeTag('thead'); ?>
                        <?php echo Html::openTag('tbody'); ?>
                            <?php
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($eneces as $key => $value) {
            /**
             * @var array $eneces[$key]
             */
            $enece       = $eneces[$key];
            $description = (string) (array_key_exists('Description', $enece) ? $enece['Description'] : '');
            echo Html::openTag('tr');
            echo Html::openTag('td');
            echo (string) $enece['Id'];
            echo Html::closeTag('td');
            echo Html::closeTag('tr');
            echo Html::openTag('tr');
            echo Html::openTag('td');
            echo (string) $enece['Name'];
            echo Html::closeTag('td');
            echo Html::closeTag('tr');
            echo Html::openTag('tr');
            echo Html::openTag('td');
            echo $description;
            echo Html::closeTag('td');
            echo Html::closeTag('tr');
        } ?>
                        <?php echo Html::closeTag('tbody'); ?>
                    <?php echo Html::closeTag('table'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
