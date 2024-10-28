<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
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

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('invoice.generator.add'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GeneratorForm')
    ->open()
?> 
<?= $button::backSave() ?>
<?= Html::openTag('div', ['class' => 'container']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator.table'); ?><?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>  
            <?= Html::openTag('div'); ?>  
                <?php 
                    $optionsDataTable = [];
                    /**
                     * @var Cycle\Database\TableInterface $table
                     */
                    foreach ($tables as $table) {
                        /**
                         * @var string $tableName
                         */
                        $tableName = $table->getName();
                        $optionsDataTable[$tableName] = $tableName; 
                    }
                    echo Field::select($form, 'pre_entity_table')
                    ->label($translator->translate('invoice.generator.table.used.to.generate.entity.controller.repository'))    
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.table.used.to.generate.entity.controller.repository')
                    ])
                    ->optionsData($optionsDataTable)
                    ->value(Html::encode($form->getPre_entity_table()));
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?>
                        <?= $translator->translate('invoice.generator.namespace'); ?>
                    <?= Html::closeTag('h5'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                    <?= Field::text($form, 'namespace_path')
                        ->label($translator->translate('invoice.generator.namespace.before.entity'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('invoice.generator.namespace.before.entity'),
                        ])
                        ->value(Html::encode($form->getNamespace_path() ?: 'App\Invoice'))
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?>
                    <?= $translator->translate('invoice.generator.controller.and.repository'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>  
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_prefix')
                    ->label($translator->translate('invoice.generator.route.prefix'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.route.prefix'),
                    ])
                    ->value(Html::encode($form->getRoute_prefix() ?: 'invoice'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_suffix')
                    ->label($translator->translate('invoice.generator.route.suffix'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.route.suffix'),
                    ])
                    ->value(Html::encode($form->getRoute_suffix() ?: 'product'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'camelcase_capital_name')
                    ->label($translator->translate('invoice.generator.camelcase.capital.name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.camelcase.capital.name.product'),
                    ])
                    ->value(Html::encode($form->getCamelcase_capital_name() ?: ''))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_singular_name')
                    ->label($translator->translate('invoice.generator.small.singular.name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.small.singular.name.product'),
                    ])
                    ->value(Html::encode($form->getSmall_singular_name() ?: ''))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_plural_name')
                    ->label($translator->translate('invoice.generator.small.plural.name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.small.plural.name.products'),
                    ])
                    ->value(Html::encode($form->getSmall_plural_name()))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'flash_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])   
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.flash.include'))
                ?>
            <?= Html::closeTag('div'); ?>           
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator.controller.path.layout'); ?><?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir')
                    ->label($translator->translate('invoice.generator.controller.layout.directory'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.controller.layout.directory.placeholder'),
                    ])
                    ->value(Html::encode($form->getController_layout_dir() ?: 'dirname(dirname(__DIR__))'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir_dot_path')
                    ->label($translator->translate('invoice.generator.controller.layout.directory.dot.path'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.generator.controller.layout.directory.dot.path.placeholder'),
                    ])
                    ->value(Html::encode($form->getController_layout_dir_dot_path() ?: '@views/layout/invoice.php'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'created_include')
                    ->inputLabelAttributes(['class' => 'form-check-label']) 
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.created.at.include'))
                ?>
            <?= Html::closeTag('div'); ?>
            
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'modified_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])  
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.modified.at.include'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'updated_include')
                    ->inputLabelAttributes(['class' => 'form-check-label']) 
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.updated.at.include'))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'deleted_include')
                    ->inputLabelAttributes(['class' => 'form-check-label']) 
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.deleted.at.include'))
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
