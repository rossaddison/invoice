<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

// Include CSS for the number picker
echo Html::cssFile('/assets/css/family-commalist-picker.css');

/**
 * @var App\Invoice\Family\FamilyForm $form
 * @var App\Invoice\FamilyCustom\FamilyCustomForm $familyCustomForm
 * @var App\Invoice\Entity\Family $family
 * 
 * Related logic: see config\common\params.php 'cvH'
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH 
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $customValues
 * @var array $customFields
 * @var string $csrf
 * @var array $familyCustomValues
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string,list<string>> $errorsCustom
 * @psalm-var array<array-key, array<array-key, string>|string> $categoryPrimaries
 * @psalm-var array<array-key, array<array-key, string>|string> $categorySecondaries
 *
 */

?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('FamilyForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
  <?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
    <?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
      <?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
        <?= Html::openTag('div', ['class' => 'card-header']); ?>
          <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
            <?= Html::encode($title) ?>
          <?= Html::closeTag('h1'); ?>
          <?= Html::openTag('div', ['id' => 'headerbar']); ?>
            <?= $button::backSave(); ?>
            <?= Html::openTag('div', ['id' => 'content']); ?>
              <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyProperties(...['family_name', 'category_primary_id', 'category_secondary_id'])
                    ->onlyCommonErrors()
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'family_name')
                    ->label($translator->translate('family.name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('family.name'),
                        'value' => Html::encode($form->getFamilyName() ?? ''),
                        'class' => 'form-control',
                        'id' => 'family_name',
                    ])
                    ->hint($translator->translate('hint.this.field.is.required'));
                  ?>
                <?= Html::closeTag('div'); ?>  
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'category_primary_id')
    ->label($translator->translate('category.primary'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value($form->getCategoryPrimaryId())
    ->prompt($translator->translate('none'))
    ->optionsData($categoryPrimaries);
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'category_secondary_id')
    ->label($translator->translate('category.secondary'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value($form->getCategorySecondaryId())
    ->prompt($translator->translate('none'))
    ->optionsData($categorySecondaries);
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::textarea($form, 'family_commalist')
                    ->label($translator->translate('family.comma.list'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('family.comma.list'),
                        'value' => Html::encode($form->getFamilyCommalist() ?? ''),
                        'class' => 'form-control',
                        'id' => 'family_commalist',
                        'rows' => '3',
                    ])
                    ->hint($translator->translate('hint.this.field.is.not.required'));
                  ?>
                  
                  <!-- Angular Number Picker Container -->
                  <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="toggleCommalistPicker()" id="toggle-picker-btn">
                      <i class="bi bi-grid-3x3-gap"></i> Show Number Picker
                    </button>
                  </div>
                  
                  <div id="commalist-picker-container" class="mt-3" style="display: none;">
                    <div class="alert alert-info">
                      <small><i class="bi bi-info-circle"></i> Click numbers below to add them to your comma list. The textarea above will be automatically updated.</small>
                    </div>
                    <!-- Angular app will be mounted here -->
                  </div>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'family_productprefix')
                    ->label($translator->translate('family.product.prefix'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('family.product.prefix'),
                        'value' => Html::encode($form->getFamilyProductprefix() ?? ''),
                        'class' => 'form-control',
                        'id' => 'family_productprefix',
                    ])
                    ->hint($translator->translate('hint.this.field.is.not.required'));
                  ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php
                    /**
                     * @var App\Invoice\Entity\CustomField $customField
                     */
                    foreach ($customFields as $customField): ?>
                        <?php
                            if ($customField->getLocation() !== 0) {
                                continue;
                            }
                        ?>
                        <?php $cvH->printFieldForForm(
                            $customField,
                            $familyCustomForm,
                            $translator,
                            $urlGenerator,
                            $familyCustomValues,
                            $customValues); ?>
                    <?php endforeach; ?>
                <?= Html::closeTag('div'); ?>
              <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
<script>
(function () {
    document.getElementById('FamilyForm').addEventListener('submit', function (e) {
        var commalist = document.getElementById('family_commalist').value.trim();
        var prefix    = document.getElementById('family_productprefix').value.trim();
        if (commalist !== '' && prefix === '') {
            e.preventDefault();
            document.getElementById('family_productprefix').focus();
            document.getElementById('family_productprefix').classList.add('is-invalid');
            var existing = document.getElementById('prefix-required-feedback');
            if (!existing) {
                var msg = document.createElement('div');
                msg.id = 'prefix-required-feedback';
                msg.className = 'invalid-feedback d-block';
                msg.textContent = 'Product prefix is required when a comma list is provided.';
                document.getElementById('family_productprefix').insertAdjacentElement('afterend', msg);
            }
        } else {
            document.getElementById('family_productprefix').classList.remove('is-invalid');
            var existing = document.getElementById('prefix-required-feedback');
            if (existing) { existing.remove(); }
        }
    });
    document.getElementById('family_productprefix').addEventListener('input', function () {
        if (this.value.trim() !== '') {
            this.classList.remove('is-invalid');
            var existing = document.getElementById('prefix-required-feedback');
            if (existing) { existing.remove(); }
        }
    });
}());
</script>
          <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
      <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?=  new Form()->close() ?>