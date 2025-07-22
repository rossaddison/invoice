<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Generator\GeneratorForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $tables
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
<?php echo $translator->translate('generator.add'); ?>
<?php echo Html::closeTag('h1'); ?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GeneratorForm')
    ->open();
?> 
<?php echo $button::backSave(); ?>
<?php echo Html::openTag('div', ['class' => 'container']); ?>
    <?php echo Html::openTag('div', ['class' => 'row']); ?>
        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                    <?php echo Html::openTag('h5'); ?><?php echo $translator->translate('generator.table'); ?><?php echo Html::closeTag('h5'); ?>
            <?php echo Html::closeTag('div'); ?>  
            <?php echo Html::openTag('div'); ?>  
                <?php
                    $optionsDataTable = [];
/**
 * @var Cycle\Database\TableInterface $table
 */
foreach ($tables as $table) {
    /**
     * @var string $tableName
     */
    $tableName                    = $table->getName();
    $optionsDataTable[$tableName] = $tableName;
}
echo Field::select($form, 'pre_entity_table')
    ->label($translator->translate('generator.table.used.to.generate.entity.controller.repository'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.table.used.to.generate.entity.controller.repository'),
    ])
    ->optionsData($optionsDataTable)
    ->value(Html::encode($form->getPre_entity_table()));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                    <?php echo Html::openTag('h5'); ?>
                        <?php echo $translator->translate('generator.namespace'); ?>
                    <?php echo Html::closeTag('h5'); ?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                    <?php echo Field::text($form, 'namespace_path')
    ->label($translator->translate('generator.namespace.before.entity'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.namespace.before.entity'),
    ])
    ->value(Html::encode($form->getNamespace_path() ?: 'App\Invoice'));
?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                <?php echo Html::openTag('h5'); ?>
                    <?php echo $translator->translate('generator.controller.and.repository'); ?>
                <?php echo Html::closeTag('h5'); ?>
            <?php echo Html::closeTag('div'); ?>  
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'route_prefix')
                ->label($translator->translate('generator.route.prefix'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('generator.route.prefix'),
                ])
                ->value(Html::encode($form->getRoute_prefix() ?: 'invoice'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'route_suffix')
    ->label($translator->translate('generator.route.suffix'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.route.suffix'),
    ])
    ->value(Html::encode($form->getRoute_suffix() ?: 'product'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'camelcase_capital_name')
    ->label($translator->translate('generator.camelcase.capital.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.camelcase.capital.name.product'),
    ])
    ->value(Html::encode($form->getCamelcase_capital_name() ?: ''));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'small_singular_name')
    ->label($translator->translate('generator.small.singular.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.small.singular.name.product'),
    ])
    ->value(Html::encode($form->getSmall_singular_name() ?: ''));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'small_plural_name')
    ->label($translator->translate('generator.small.plural.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.small.plural.name.products'),
    ])
    ->value(Html::encode($form->getSmall_plural_name()));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::checkbox($form, 'flash_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.flash.include'));
?>
            <?php echo Html::closeTag('div'); ?>           
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                <?php echo Html::openTag('h5'); ?><?php echo $translator->translate('generator.controller.path.layout'); ?><?php echo Html::closeTag('h5'); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'controller_layout_dir')
                ->label($translator->translate('generator.controller.layout.directory'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('generator.controller.layout.directory.placeholder'),
                ])
                ->value(Html::encode($form->getController_layout_dir() ?: 'dirname(dirname(__DIR__))'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::text($form, 'controller_layout_dir_dot_path')
    ->label($translator->translate('generator.controller.layout.directory.dot.path'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.controller.layout.directory.dot.path.placeholder'),
    ])
    ->value(Html::encode($form->getController_layout_dir_dot_path() ?: '@views/layout/invoice.php'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::checkbox($form, 'created_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.created.at.include'));
?>
            <?php echo Html::closeTag('div'); ?>
            
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::checkbox($form, 'modified_include')
                ->inputLabelAttributes(['class' => 'form-check-label'])
                ->inputClass('form-check-input')
                ->ariaDescribedBy($translator->translate('generator.modified.at.include'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::checkbox($form, 'updated_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.updated.at.include'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?php echo Field::checkbox($form, 'deleted_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.deleted.at.include'));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('form'); ?>
