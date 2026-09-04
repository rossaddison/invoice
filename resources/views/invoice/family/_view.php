<?php

declare(strict_types=1);

use App\Invoice\FamilyCustom\FamilyCustomForm;
use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Th;

/**
 * @var App\Invoice\Family\FamilyForm $form
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $familyCustomValues
 * @var array $customValues
 * @var array $custom_fields
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $categoryPrimaries
 * @psalm-var array<array-key, array<array-key, string>|string> $categorySecondaries
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
                <?php
                    ReadOnlyField::render(
                        $translator->translate('family.name'),
                        $form->getFamilyName(),
                    );
ReadOnlyField::render(
    $translator->translate('category.primary'),
    $selectLabel($categoryPrimaries, $form->getCategoryPrimaryId()),
);
ReadOnlyField::render(
    $translator->translate('category.secondary'),
    $selectLabel($categorySecondaries, $form->getCategorySecondaryId()),
);
ReadOnlyField::render(
    $translator->translate('family.comma.list'),
    $form->getFamilyCommalist(),
);
ReadOnlyField::render(
    $translator->translate('family.product.prefix'),
    $form->getFamilyProductprefix(),
);
?>
                <?php
$i = 1;
/**
  * @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field
  */
foreach ($custom_fields as $custom_field) : ?>
                    <?php if ($custom_field->getLocation() !== 0) {
                        continue;
                    } ?>
                    <?= Html::openTag('tr'); ?>
                        <?=  new Th()->addAttributes(['id' => 'family-cf-'. $i]); ?>
                        <?= Html::openTag('td'); ?>
                    <?php
                        $familyCustomForm = new FamilyCustomForm();
    $cvH->printFieldForView($custom_field, $familyCustomForm, $familyCustomValues);?>
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>
                <?php
                        $i = $i + 1;
endforeach; ?>
              <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
          <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
      <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
