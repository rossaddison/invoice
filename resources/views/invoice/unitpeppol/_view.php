<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
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
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?php
                    ReadOnlyField::render(
                        $translator->translate('id'),
                        $selectLabel($optionsDataUnits, $form->getUnitId()),
                    );
                    ReadOnlyField::render(
                        $translator->translate('name'),
                        $form->getName(),
                    );
                    ReadOnlyField::render(
                        $translator->translate('unit.peppol.code'),
                        $selectLabel($optionsDataEneces, $form->getCode()),
                    );
                    ReadOnlyField::render(
                        $translator->translate('description'),
                        $form->getDescription(),
                    );
                ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <!-- https://dev.to/dcodeyt/creating-beautiful-html-tables-with-css-428l
                    class styled-table found at C:\wamp64\www\yii3-i\src\Invoice\Asset\invoice\css\yii3i.css
                    -->
                    <?= Html::openTag('table', ['class' => 'styled-table']); ?>
                        <?= Html::openTag('thead'); ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('id'); ?>
                                <?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('name'); ?>
                                <?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>
                                    <?php $translator->translate('description'); ?>
                                <?= Html::closeTag('th'); ?>
                            <?= Html::closeTag('tr'); ?>
                        <?= Html::closeTag('thead'); ?>
                        <?= Html::openTag('tbody'); ?>
                            <?php
        /**
         * @var string $key
         */
        foreach (array_keys($eneces) as $key) {
            /**
             * @var array $eneces[$key]
             */
            $enece = $eneces[$key];
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
