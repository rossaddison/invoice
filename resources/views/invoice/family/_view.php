<?php

declare(strict_types=1);

use App\Invoice\FamilyCustom\FamilyCustomForm;
use App\Invoice\Entity\FamilyCustom;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Th;
use Yiisoft\Html\Tag\Form;

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
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('FamilyForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
  <?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
    <?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
      <?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
        <?= Html::openTag('div', ['class' => 'card-header']); ?>
          <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
            <?= Html::encode($title) ?>
          <?= Html::closeTag('h1'); ?>
          <?= Html::openTag('div', ['id' => 'headerbar']); ?>
            <?= $button::back(); ?>
            <?= Html::openTag('div', ['id' => 'content']); ?>
              <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyProperties(...['family_name'])
                    ->onlyCommonErrors()
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'family_name')
                        ->label($translator->translate('family.name'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('family.name'),
                            'value' => Html::encode($form->getFamily_name() ?? ''),
                            'class' => 'form-control',
                            'id' => 'family_name',
                        ])
                        ->readonly(true);
                  ?>
                <?= Html::closeTag('div'); ?>  
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'category_primary_id')
                        ->label($translator->translate('category.primary'))
                        ->addInputAttributes([
                            'class' => 'form-control  alert alert-warning',
                        ])
                        ->value($form->getCategory_primary_id())
                        ->prompt($translator->translate('none'))
                        ->optionsData($categoryPrimaries)
                        ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'category_secondary_id')
                        ->label($translator->translate('category.secondary'))
                        ->addInputAttributes([
                            'class' => 'form-control  alert alert-warning',
                        ])
                        ->value($form->getCategory_secondary_id())
                        ->prompt($translator->translate('none'))
                        ->optionsData($categorySecondaries)
                        ->disabled(true)
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'family_commalist')
                        ->label($translator->translate('family.comma.list'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('family.comma.list'),
                            'value' => Html::encode($form->getFamily_commalist() ?? ''),
                            'class' => 'form-control',
                            'id' => 'family_commalist',
                        ])
                        ->disabled(true);
                  ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'family_productprefix')
                        ->label($translator->translate('family.product.prefix'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('family.product.prefix'),
                            'value' => Html::encode($form->getFamily_productprefix() ?? ''),
                            'class' => 'form-control',
                            'id' => 'family_productprefix',
                        ])
                        ->disabled(true);
                  ?>
                <?= Html::closeTag('div'); ?>
                <?php
                $i = 1;
                /**
                  * @var App\Invoice\Entity\CustomField $custom_field
                  */
                foreach ($custom_fields as $custom_field) : ?>
                    <?php if ($custom_field->getLocation() !== 0) {
                        continue;
                    } ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Th::tag()->addAttributes(['id' => 'family-cf-'. $i]); ?> 
                        <?= Html::openTag('td'); ?>    
                    <?php
                        $familyCustomForm = new FamilyCustomForm(new FamilyCustom());
$cvH->print_field_for_view($custom_field, $familyCustomForm, $familyCustomValues, $customValues);?>
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

<?= Form::tag()->close() ?>