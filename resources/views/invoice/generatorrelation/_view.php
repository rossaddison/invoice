<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\GeneratorRelation\GeneratorRelationForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $generators
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$optionsDataGenerators = [];
/**
 * @var App\Infrastructure\Persistence\Gentor\Gentor $generator
 */
foreach ($generators as $generator) {
    $optionsDataGenerators[$generator->reqGentorId()] = $generator->getCamelcaseCapitalName();
}
$selectLabel = static function (array $optionsData, int|string|null $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $key = (string) $value;
    /** @var string|array|null $label */
    $label = $optionsData[$key] ?? null;
    return is_string($label) ? $label : $key;
};
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= $button::back(); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render(
            $translator->translate('generator.relation.form.entity.generator'),
            $selectLabel($optionsDataGenerators, $form->reqGentorId()),
        );
        ReadOnlyField::render(
            $translator->translate('generator.relation.form.lowercase.name'),
            $form->getLowercaseName(),
        );
        ReadOnlyField::render(
            $translator->translate('generator.relation.form.camelcase.name'),
            $form->getCamelcaseName(),
        );
        ReadOnlyField::render(
            $translator->translate('generator.relation.form.view.field.name'),
            $form->getViewFieldName(),
        );
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
