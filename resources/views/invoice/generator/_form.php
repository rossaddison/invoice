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

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('generator.add'); ?>
<?= Html::closeTag('h1'); ?>

<?=  new Form()
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
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?><?= $translator->translate('generator.table'); ?><?= Html::closeTag('h5'); ?>
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
->label($translator->translate('generator.table.used.to.generate.entity.controller.repository'))
->addInputAttributes([
    'placeholder' => $translator->translate('generator.table.used.to.generate.entity.controller.repository'),
])
->optionsData($optionsDataTable)
->value(Html::encode($form->getPreEntityTable()));
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?>
                        <?= $translator->translate('generator.namespace'); ?>
                    <?= Html::closeTag('h5'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                    <?= Field::text($form, 'namespace_path')
        ->label($translator->translate('generator.namespace.before.entity'))
        ->addInputAttributes([
            'placeholder' => $translator->translate('generator.namespace.before.entity'),
        ])
        ->value(Html::encode($form->getNamespacePath() ?: 'App\Invoice'))
?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?>
                    <?= $translator->translate('generator.controller.and.repository'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_prefix')
->label($translator->translate('generator.route.prefix'))
->addInputAttributes([
    'placeholder' => $translator->translate('generator.route.prefix'),
])
->value(Html::encode($form->getRoutePrefix() ?: 'invoice'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_suffix')
    ->label($translator->translate('generator.route.suffix'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.route.suffix'),
    ])
    ->value(Html::encode($form->getRouteSuffix() ?: 'product'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'camelcase_capital_name')
    ->label($translator->translate('generator.camelcase.capital.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.camelcase.capital.name.product'),
    ])
    ->value(Html::encode($form->getCamelcaseCapitalName() ?: ''))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_singular_name')
    ->label($translator->translate('generator.small.singular.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.small.singular.name.product'),
    ])
    ->value(Html::encode($form->getSmallSingularName() ?: ''))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_plural_name')
    ->label($translator->translate('generator.small.plural.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.small.plural.name.products'),
    ])
    ->value(Html::encode($form->getSmallPluralName()))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'flash_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.flash.include'))
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?><?= $translator->translate('generator.controller.path.layout'); ?><?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir')
    ->label($translator->translate('generator.controller.layout.directory'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.controller.layout.directory.placeholder'),
    ])
    ->value(Html::encode($form->getControllerLayoutDir() ?: 'dirname(dirname(__DIR__))'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir_dot_path')
    ->label($translator->translate('generator.controller.layout.directory.dot.path'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.controller.layout.directory.dot.path.placeholder'),
    ])
    ->value(Html::encode($form->getControllerLayoutDirDotPath() ?: '@views/layout/invoice.php'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'created_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.created.at.include'))
?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'modified_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.modified.at.include'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'updated_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.updated.at.include'))
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'deleted_include')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('generator.deleted.at.include'))
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
